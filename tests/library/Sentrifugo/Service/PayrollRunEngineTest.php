<?php

/**
 * Automated Test Suite for PayrollRunEngine
 *
 * Verifies mathematical cadence algorithms, holiday forward-shifting,
 * gap-filling idempotency, and state-machine protection.
 *
 * Execution: phpunit tests/library/Sentrifugo/Service/PayrollRunEngineTest.php
 *
 * @category   Testing
 * @package    Sentrifugo_Service
 * @subpackage UnitTests
 * @version    Sentrifugo v3.2 (SSOT v6.7.7)
 */

require_once 'PHPUnit/Framework/TestCase.php';

class Sentrifugo_Service_PayrollRunEngineTest extends PHPUnit_Framework_TestCase
{
    /** @var Zend_Db_Adapter_Abstract */
    protected $_db;

    /** @var Sentrifugo_Service_PayrollRunEngine */
    protected $_engine;

    /** @var int Temporary Pay Group ID created for testing */
    protected $_testGroupId;

    /** @var int Temporary Pay Frequency ID created for testing */
    protected $_testFreqId;

    protected function setUp()
    {
        parent::setUp();

        // 1. Boot Database Adapter
        $this->_db = Zend_Db_Table::getDefaultAdapter();

        // 2. Wrap the entire test in a transaction so no data persists
        $this->_db->beginTransaction();

        require_once APPLICATION_PATH . '/../library/Sentrifugo/Service/PayrollRunEngine.php';
        $this->_engine = new Sentrifugo_Service_PayrollRunEngine();
    }

    protected function tearDown()
    {
        // 3. Roll back every database modification made during the test
        if ($this->_db) {
            $this->_db->rollBack();
        }

        parent::tearDown();
    }

    /* =================================================================================
     * TEST 1: IDEMPOTENCY & CLEAN GENERATION (TC-01)
     * ================================================================================= */

    public function testIdempotencyAndCleanGeneration()
    {
        // Setup: Create a standard Bi-Weekly Pay Frequency (Every 2 Weeks on Monday)
        $freqId = $this->_createPayFrequency([
            'code'                  => 'TEST_BIW',
            'name'                  => 'Test Bi-Weekly',
            'frequency_type'        => 'FIXED_INTERVAL',
            'interval_count'        => 2,
            'interval_unit'         => 'WEEK',
            'weekly_anchor_day'     => 'Monday',
            'global_reference_date' => '2026-01-05'
        ]);

        $groupId = $this->_createPayGroup([
            'code'             => 'TEST_PG_1',
            'name'             => 'Test Group 1',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 5
        ]);

        // RUN 1: Generate for 2026
        $metrics1 = $this->_engine->generateManual($groupId, '2026-01-01', '2026-12-31', 1, false);

        $this->assertEquals(26, $metrics1['created'], "First run must generate exactly 26 bi-weekly cycles for 2026.");
        $this->assertEquals(0, $metrics1['untouched']);
        $this->assertEquals(0, $metrics1['updated']);

        // RUN 2: Run the exact same engine immediately (Idempotency Check)
        $metrics2 = $this->_engine->generateManual($groupId, '2026-01-01', '2026-12-31', 1, false);

        $this->assertEquals(0, $metrics2['created'], "Second run must create 0 new records.");
        $this->assertEquals(26, $metrics2['untouched'], "Second run must detect all 26 existing runs and leave them untouched.");
        $this->assertEquals(0, $metrics2['updated'], "Second run must not update identical records.");
    }

    /* =================================================================================
     * TEST 2: SMART GAP FILLING (TC-02)
     * ================================================================================= */

    public function testSmartGapFilling()
    {
        $freqId = $this->_createPayFrequency([
            'code'                  => 'TEST_GAP',
            'name'                  => 'Test Gap Cadence',
            'frequency_type'        => 'FIXED_INTERVAL',
            'interval_count'        => 2,
            'interval_unit'         => 'WEEK',
            'weekly_anchor_day'     => 'Monday',
            'global_reference_date' => '2026-01-05'
        ]);

        $groupId = $this->_createPayGroup([
            'code'             => 'TEST_PG_2',
            'name'             => 'Test Group 2',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 0
        ]);

        // Generate baseline 26 runs
        $this->_engine->generateManual($groupId, '2026-01-01', '2026-12-31', 1, false);

        // Simulate an accident: Delete runs 5 and 6 (Cycle 5 & 6)
        $this->_db->delete('main_payroll_run', [
            'pay_group_id = ?' => $groupId,
            'cycle_sequence IN (?)' => [5, 6]
        ]);

        // Run Engine again
        $gapMetrics = $this->_engine->generateManual($groupId, '2026-01-01', '2026-12-31', 1, false);

        $this->assertEquals(2, $gapMetrics['created'], "Engine must heal the calendar by generating exactly the 2 missing gaps.");
        $this->assertEquals(24, $gapMetrics['untouched'], "Engine must preserve the other 24 intact runs untouched.");
    }

    /* =================================================================================
     * TEST 3: IMMUTABILITY LOCK ON PROCESSING RUNS (TC-04)
     * ================================================================================= */

    public function testImmutabilityLockOnProcessingRuns()
    {
        $freqId = $this->_createPayFrequency([
            'code'                  => 'TEST_LOCK',
            'name'                  => 'Test Lock Cadence',
            'frequency_type'        => 'FIXED_INTERVAL',
            'interval_count'        => 2,
            'interval_unit'         => 'WEEK',
            'weekly_anchor_day'     => 'Monday',
            'global_reference_date' => '2026-01-05'
        ]);

        $groupId = $this->_createPayGroup([
            'code'             => 'TEST_PG_3',
            'name'             => 'Test Group 3',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 2
        ]);

        // Generate initial runs
        $this->_engine->generateManual($groupId, '2026-01-01', '2026-12-31', 1, false);

        // Lock Run 1 and put Run 2 in Review
        $this->_db->update('main_payroll_run', ['run_status' => 'Locked'], ['pay_group_id = ?' => $groupId, 'cycle_sequence = 1']);
        $this->_db->update('main_payroll_run', ['run_status' => 'Review'], ['pay_group_id = ?' => $groupId, 'cycle_sequence = 2']);

        // Now change the Pay Group lag from 2 days to 7 days (forcing date shifts)
        $this->_db->update('main_pay_group', ['payment_lag_days' => 7], ['id = ?' => $groupId]);

        // Run Engine Sync
        $metrics = $this->_engine->generateManual($groupId, '2026-01-01', '2026-12-31', 1, false);

        $this->assertEquals(2, $metrics['locked'], "Runs in 'Locked' and 'Review' status must be strictly protected from alteration.");
        $this->assertEquals(24, $metrics['updated'], "The remaining 24 Scheduled runs must receive the new shifted pay date.");
    }

    /* =================================================================================
     * TEST 4: FEBRUARY & END-OF-MONTH RESOLUTION (TC-05)
     * ================================================================================= */

    public function testFebruaryEomResolution()
    {
        // Setup: Semi-Monthly on 15th and 31st (EOM) with PREVIOUS_VALID_DAY fallback
        $freqId = $this->_createPayFrequency([
            'code'                => 'TEST_SEMI',
            'name'                => 'Test Semi-Monthly',
            'frequency_type'      => 'CALENDAR_BASED',
            'missing_date_policy' => 'PREVIOUS_VALID_DAY'
        ]);

        // Insert anchors: 15 and 31
        $this->_db->insert('main_payfrequency_anchor', ['payfrequency_id' => $freqId, 'anchor_day' => 15]);
        $this->_db->insert('main_payfrequency_anchor', ['payfrequency_id' => $freqId, 'anchor_day' => 31]);

        $groupId = $this->_createPayGroup([
            'code'             => 'TEST_PG_4',
            'name'             => 'Test Group 4',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 0
        ]);

        // Generate for Q1 2026 (Jan, Feb, Mar)
        $this->_engine->generateManual($groupId, '2026-01-01', '2026-03-31', 1, false);

        // Fetch February cycles
        $select = $this->_db->select()
            ->from('main_payroll_run')
            ->where('pay_group_id = ?', $groupId)
            ->where("cycle_start_date >= '2026-02-01' AND cycle_start_date <= '2026-02-28'")
            ->order('cycle_start_date ASC');

        $febRuns = $this->_db->fetchAll($select);

        $this->assertCount(2, $febRuns, "February must have exactly two semi-monthly runs.");

        // Cycle 1: Feb 15 to Feb 27
        $this->assertEquals('2026-02-15', $febRuns[0]['cycle_start_date']);
        $this->assertEquals('2026-02-27', $febRuns[0]['cycle_end_date']);

        // Cycle 2: Feb 28 (Resolved EOM) to March 14
        $this->assertEquals('2026-02-28', $febRuns[1]['cycle_start_date'], "Anchor 31 must dynamically resolve to Feb 28th.");
        $this->assertEquals('2026-03-14', $febRuns[1]['cycle_end_date']);
    }

    /* =================================================================================
     * TEST 5: HOLIDAY FORWARD-SHIFT RULE (TC-03)
     * ================================================================================= */

    public function testHolidayForwardShiftRule()
    {
        $freqId = $this->_createPayFrequency([
            'code'                  => 'TEST_HOL',
            'name'                  => 'Test Holiday Shift',
            'frequency_type'        => 'FIXED_INTERVAL',
            'interval_count'        => 1,
            'interval_unit'         => 'WEEK',
            'weekly_anchor_day'     => 'Monday',
            'global_reference_date' => '2026-01-05'
        ]);

        $groupId = $this->_createPayGroup([
            'code'             => 'TEST_PG_5',
            'name'             => 'Test Group 5',
            'payfrequency_id'  => $freqId,
            'payment_lag_days' => 0 // Pay date equals cycle_end_date (Sunday)
        ]);

        // Inject a Holiday on Sunday, Jan 11, 2026 marked as OFF_DAY
        $holId = $this->_createHolidayFixture('2026-01-11');

        // Generate Run for Jan 5 - Jan 11
        $this->_engine->generateManual($groupId, '2026-01-05', '2026-01-11', 1, false);

        $run = $this->_db->fetchRow($this->_db->select()->from('main_payroll_run')->where('pay_group_id = ?', $groupId));

        $this->assertEquals('2026-01-05', $run['cycle_start_date']);
        $this->assertEquals('2026-01-11', $run['cycle_end_date']);

        // Assertion: Tentative pay date was Jan 11 (Holiday). Forward shift must roll to Monday Jan 12!
        $this->assertEquals('2026-01-12', $run['pay_date'], "Pay date landing on a Holiday OFF_DAY must roll forward to the next working day.");
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
        // 1. Master Holiday
        $this->_db->insert('main_holiday', [
            'name' => 'Test Day Off',
            'category_id' => 1,
            'tag_id' => 1,
            'work_policy' => 'OFF_DAY',
            'isactive' => 1
        ]);
        $holId = (int) $this->_db->lastInsertId('main_holiday');

        // 2. Holiday Date Header
        $this->_db->insert('main_holiday_date', [
            'holiday_id' => $holId,
            'start_date' => $date,
            'end_date' => $date,
            'duration_days' => 1.0,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'is_paid' => 1,
            'is_mandatory' => 1,
            'work_policy' => 'OFF_DAY',
            'payroll_multiplier' => 1.0,
            'weekend_policy' => 'NO_SUBSTITUTION',
            'isactive' => 1
        ]);
        $dateId = (int) $this->_db->lastInsertId('main_holiday_date');

        // 3. Holiday Line (Queried by the engine)
        $this->_db->insert('main_holiday_date_line', [
            'holiday_date_id' => $dateId,
            'sequence_no' => 1,
            'effective_date' => $date,
            'isactive' => 1
        ]);

        return $holId;
    }
}
