<?php

use PHPUnit\Framework\TestCase;

/**
 * Automated Test Suite for PayrollRunEngine (PHP 8.3 / PHPUnit 9+)
 *
 * Execution: vendor/bin/phpunit tests/library/Sentrifugo/Service/PayrollRunEngineTest.php
 *
 * @category   Testing
 * @package    Sentrifugo_Service
 * @subpackage UnitTests
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @version    Sentrifugo v3.2
 */
class PayrollRunEngineTest extends TestCase
{
    /** @var Zend_Db_Adapter_Abstract */
    protected $_db;

    /** @var Sentrifugo_Service_PayrollRunEngine */
    protected $_engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_db = Zend_Db_Table::getDefaultAdapter();
        $this->_db->beginTransaction();

        require_once BASE_PATH . '/library/Sentrifugo/Service/PayrollRunEngine.php';
        $this->_engine = new Sentrifugo_Service_PayrollRunEngine();
    }

    protected function tearDown(): void
    {
        if ($this->_db) {
            $this->_db->rollBack();
        }

        parent::tearDown();
    }

    /* =================================================================================
     * TEST 1: IDEMPOTENCY & CLEAN GENERATION (TC-01)
     * ================================================================================= */

    public function testIdempotencyAndCleanGeneration(): void
    {
        $freqId = $this->_createPayFrequency(array(
            'code'                  => 'TEST_BIW',
            'name'                  => 'Test Bi-Weekly',
            'frequency_type'        => 'FIXED_INTERVAL',
            'interval_count'        => 2,
            'interval_unit'         => 'WEEK',
            'weekly_anchor_day'     => 'Monday',
            'global_reference_date' => '2026-01-05',
        ));

        $groupId = $this->_createPayGroup(array(
            'code'             => 'TEST_PG_1',
            'name'             => 'Test Group 1',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 5,
        ));

        // RUN 1: Generate for all 26 cycles starting in 2026
        $metrics1 = $this->_engine->generateManual($groupId, '2026-01-05', '2026-12-31', 1, false);

        $this->assertEquals(26, $metrics1['created'], 'First run must generate exactly 26 bi-weekly cycles for 2026.');
        $this->assertEquals(0, $metrics1['untouched']);
        $this->assertEquals(0, $metrics1['updated']);

        // RUN 2: Idempotency Check
        $metrics2 = $this->_engine->generateManual($groupId, '2026-01-05', '2026-12-31', 1, false);

        $this->assertEquals(0, $metrics2['created'], 'Second run must create 0 new records.');
        $this->assertEquals(26, $metrics2['untouched'], 'Second run must leave all 26 existing runs untouched.');
        $this->assertEquals(0, $metrics2['updated'], 'Second run must not update identical records.');
    }

    /* =================================================================================
     * TEST 2: SMART GAP FILLING (TC-02)
     * ================================================================================= */

    public function testSmartGapFilling(): void
    {
        $freqId = $this->_createPayFrequency(array(
            'code'                  => 'TEST_GAP',
            'name'                  => 'Test Gap Cadence',
            'frequency_type'        => 'FIXED_INTERVAL',
            'interval_count'        => 2,
            'interval_unit'         => 'WEEK',
            'weekly_anchor_day'     => 'Monday',
            'global_reference_date' => '2026-01-05',
        ));

        $groupId = $this->_createPayGroup(array(
            'code'             => 'TEST_PG_2',
            'name'             => 'Test Group 2',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 0,
        ));

        $this->_engine->generateManual($groupId, '2026-01-05', '2026-12-31', 1, false);

        // Simulate missing runs 5 and 6
        $this->_db->delete('main_payroll_run', array(
            'pay_group_id = ?'      => $groupId,
            'cycle_sequence IN (?)' => array(5, 6),
        ));

        $gapMetrics = $this->_engine->generateManual($groupId, '2026-01-05', '2026-12-31', 1, false);

        $this->assertEquals(2, $gapMetrics['created'], 'Engine must heal the calendar by generating the 2 missing gaps.');
        $this->assertEquals(24, $gapMetrics['untouched'], 'Engine must preserve the other 24 intact runs untouched.');
    }

    /* =================================================================================
     * TEST 3: IMMUTABILITY LOCK ON PROCESSING RUNS (TC-04)
     * ================================================================================= */

    public function testImmutabilityLockOnProcessingRuns(): void
    {
        $freqId = $this->_createPayFrequency(array(
            'code'                  => 'TEST_LOCK',
            'name'                  => 'Test Lock Cadence',
            'frequency_type'        => 'FIXED_INTERVAL',
            'interval_count'        => 2,
            'interval_unit'         => 'WEEK',
            'weekly_anchor_day'     => 'Monday',
            'global_reference_date' => '2026-01-05',
        ));

        $groupId = $this->_createPayGroup(array(
            'code'             => 'TEST_PG_3',
            'name'             => 'Test Group 3',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 2,
        ));

        $this->_engine->generateManual($groupId, '2026-01-05', '2026-12-31', 1, false);

        // Lock Run 1 and put Run 2 in Review
        $this->_db->update('main_payroll_run', array('run_status' => 'Locked'), array('pay_group_id = ?' => $groupId, 'cycle_sequence = ?' => 1));
        $this->_db->update('main_payroll_run', array('run_status' => 'Review'), array('pay_group_id = ?' => $groupId, 'cycle_sequence = ?' => 2));

        // Change lag from 2 to 7 days
        $this->_db->update('main_pay_group', array('payment_lag_days' => 7), array('id = ?' => $groupId));

        $metrics = $this->_engine->generateManual($groupId, '2026-01-05', '2026-12-31', 1, false);

        $this->assertEquals(2, $metrics['locked'], "Runs in 'Locked' and 'Review' status must remain strictly protected.");
        $this->assertEquals(24, $metrics['updated'], 'The remaining 24 Scheduled runs must receive the shifted pay date.');
    }

    /* =================================================================================
     * TEST 4: FEBRUARY & END-OF-MONTH RESOLUTION (TC-05)
     * ================================================================================= */

    public function testFebruaryEomResolution(): void
    {
        $freqId = $this->_createPayFrequency(array(
            'code'                => 'TEST_SEMI',
            'name'                => 'Test Semi-Monthly',
            'frequency_type'      => 'CALENDAR_BASED',
            'missing_date_policy' => 'PREVIOUS_VALID_DAY',
        ));

        $this->_db->insert('main_payfrequency_anchor', array('payfrequency_id' => $freqId, 'anchor_day' => 15));
        $this->_db->insert('main_payfrequency_anchor', array('payfrequency_id' => $freqId, 'anchor_day' => 31));

        $groupId = $this->_createPayGroup(array(
            'code'             => 'TEST_PG_4',
            'name'             => 'Test Group 4',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 0,
        ));

        $this->_engine->generateManual($groupId, '2026-01-01', '2026-03-31', 1, false);

        $select = $this->_db->select()
            ->from('main_payroll_run')
            ->where('pay_group_id = ?', $groupId)
            ->where("cycle_start_date >= '2026-02-01' AND cycle_start_date <= '2026-02-28'")
            ->order('cycle_start_date ASC');

        $febRuns = $this->_db->fetchAll($select);

        $this->assertCount(2, $febRuns, 'February must have exactly two semi-monthly runs.');
        $this->assertEquals('2026-02-15', $febRuns[0]['cycle_start_date']);
        $this->assertEquals('2026-02-27', $febRuns[0]['cycle_end_date']);
        $this->assertEquals('2026-02-28', $febRuns[1]['cycle_start_date'], 'Anchor 31 must resolve to Feb 28th.');
        $this->assertEquals('2026-03-14', $febRuns[1]['cycle_end_date']);
    }

    /* =================================================================================
     * TEST 5: HOLIDAY FORWARD-SHIFT RULE (TC-03)
     * ================================================================================= */

    public function testHolidayForwardShiftRule(): void
    {
        $freqId = $this->_createPayFrequency(array(
            'code'                  => 'TEST_HOL',
            'name'                  => 'Test Holiday Shift',
            'frequency_type'        => 'FIXED_INTERVAL',
            'interval_count'        => 1,
            'interval_unit'         => 'WEEK',
            'weekly_anchor_day'     => 'Monday',
            'global_reference_date' => '2026-01-05',
        ));

        $groupId = $this->_createPayGroup(array(
            'code'             => 'TEST_PG_5',
            'name'             => 'Test Group 5',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 0,
        ));

        $this->_createHolidayFixture('2026-01-11');

        $this->_engine->generateManual($groupId, '2026-01-05', '2026-01-11', 1, false);

        $run = $this->_db->fetchRow(
            $this->_db->select()->from('main_payroll_run')->where('pay_group_id = ?', $groupId)
        );

        $this->assertEquals('2026-01-05', $run['cycle_start_date']);
        $this->assertEquals('2026-01-11', $run['cycle_end_date']);
        $this->assertEquals('2026-01-12', $run['pay_date'], 'Pay date on a Holiday OFF_DAY must roll forward to next working day.');
    }

    /* =================================================================================
     * TEST 6: CROSS-YEAR OVERLAP & SEQUENCE ROLLOVER (TC-06)
     * ================================================================================= */

    public function testCrossYearOverlapAndSequenceRollover(): void
    {
        $freqId = $this->_createPayFrequency(array(
            'code'                  => 'TEST_XOVR',
            'name'                  => 'Test Cross-Year',
            'frequency_type'        => 'FIXED_INTERVAL',
            'interval_count'        => 2,
            'interval_unit'         => 'WEEK',
            'weekly_anchor_day'     => 'Monday',
            'global_reference_date' => '2026-01-05',
        ));

        $groupId = $this->_createPayGroup(array(
            'code'             => 'TEST_PG_6',
            'name'             => 'Test Group 6',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 5,
        ));

        // Window starting 2026-01-01 must catch the overlapping 2025-12-22 cycle + 2026-01-05 cycle
        $metrics = $this->_engine->generateManual($groupId, '2026-01-01', '2026-01-18', 1, false);
        $this->assertEquals(2, $metrics['created']);

        $runs = $this->_db->fetchAll(
            $this->_db->select()
                ->from('main_payroll_run')
                ->where('pay_group_id = ?', $groupId)
                ->order('cycle_start_date ASC')
        );

        // Dec 22, 2025 - Jan 4, 2026 must be Sequence 26 of 2025
        $this->assertEquals('2025-12-22', $runs[0]['cycle_start_date']);
        $this->assertEquals(26, (int)$runs[0]['cycle_sequence']);

        // Jan 5, 2026 - Jan 18, 2026 must roll over to Sequence 1 of 2026
        $this->assertEquals('2026-01-05', $runs[1]['cycle_start_date']);
        $this->assertEquals(1, (int)$runs[1]['cycle_sequence']);
    }

    /* =================================================================================
     * TEST FIXTURE HELPERS
     * ================================================================================= */

    protected function _createPayFrequency(array $data): int
    {
        $data['isactive']    = 1;
        $data['createdby']   = 1;
        $data['createddate'] = gmdate('Y-m-d H:i:s');
        $this->_db->insert('main_payfrequency', $data);
        return (int) $this->_db->lastInsertId('main_payfrequency');
    }

    protected function _createPayGroup(array $data): int
    {
        $data['isactive']    = 1;
        $data['createdby']   = 1;
        $data['createddate'] = gmdate('Y-m-d H:i:s');
        $this->_db->insert('main_pay_group', $data);
        return (int) $this->_db->lastInsertId('main_pay_group');
    }

    protected function _createHolidayFixture(string $date): int
    {
        $catId = (int) $this->_db->fetchOne($this->_db->select()->from('main_holiday_category', array('id'))->limit(1));
        if (!$catId) {
            $this->_db->insert('main_holiday_category', array('code' => 'TEST_CAT', 'name' => 'Test Category', 'isactive' => 1));
            $catId = (int) $this->_db->lastInsertId('main_holiday_category');
        }

        $tagId = (int) $this->_db->fetchOne($this->_db->select()->from('main_holiday_tag', array('id'))->limit(1));
        if (!$tagId) {
            $this->_db->insert('main_holiday_tag', array('code' => 'TEST_TAG', 'name' => 'Test Tag', 'isactive' => 1));
            $tagId = (int) $this->_db->lastInsertId('main_holiday_tag');
        }

        $this->_db->insert('main_holiday', array(
            'name' => 'Test Day Off', 'category_id' => $catId, 'tag_id' => $tagId, 'work_policy' => 'OFF_DAY', 'isactive' => 1,
        ));
        $holId = (int) $this->_db->lastInsertId('main_holiday');

        $this->_db->insert('main_holiday_date', array(
            'holiday_id' => $holId, 'start_date' => $date, 'end_date' => $date,
            'duration_days' => 1.0, 'start_time' => '00:00:00', 'end_time' => '23:59:59',
            'is_paid' => 1, 'is_mandatory' => 1, 'work_policy' => 'OFF_DAY',
            'payroll_multiplier' => 1.0, 'weekend_policy' => 'NO_SUBSTITUTION', 'isactive' => 1,
        ));
        $dateId = (int) $this->_db->lastInsertId('main_holiday_date');

        $this->_db->insert('main_holiday_date_line', array(
            'holiday_date_id' => $dateId, 'sequence_no' => 1, 'effective_date' => $date, 'isactive' => 1,
        ));

        return $holId;
    }
}