<?php

use PHPUnit\Framework\TestCase;

/**
 * Holiday Engine Test Suite (PHP 8.3 / PHPUnit 9+)
 *
 * Validates recurrence parsers, weekend shifters, block line generation,
 * and boolean DNF intersection logic of the Holiday Engine.
 *
 * Execution: vendor/bin/phpunit tests/library/Sentrifugo/Service/HolidayEngineTest.php
 */
class HolidayEngineTest extends TestCase
{
    /** @var Sentrifugo_Service_HolidayEngine */
    protected $_engine;

    protected function setUp(): void
    {
        parent::setUp();

        $basePath = defined('BASE_PATH') ? BASE_PATH : realpath(__DIR__ . '/../../../../');
        require_once $basePath . '/library/Sentrifugo/Service/HolidayEngine.php';
        $this->_engine = new Sentrifugo_Service_HolidayEngine();
    }

    /**
     * Helper to invoke protected methods for isolated algorithm testing
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }
        return $method->invokeArgs($object, $parameters);
    }

    /* ====================================================================
     * TEST GROUP 1: RECURRENCE ALGORITHMS
     * ==================================================================== */

    public function testCalculateBaseStartDate_FixedDate(): void
    {
        $master = array(
            'recurrence_type' => 'FIXED_DATE',
            'recur_month'     => 12,
            'recur_day'       => 25
        );

        $result = $this->invokeMethod($this->_engine, '_calculateBaseStartDate', array($master, 2026));
        $this->assertEquals('2026-12-25', $result);
    }

    public function testCalculateBaseStartDate_RelativeDate_FirstMonday(): void
    {
        $master = array(
            'recurrence_type'   => 'RELATIVE_DATE',
            'recur_month'       => 9,
            'recur_week_num'    => 1,
            'recur_day_of_week' => 1
        );

        $result = $this->invokeMethod($this->_engine, '_calculateBaseStartDate', array($master, 2026));
        $this->assertEquals('2026-09-07', $result);
    }

    public function testCalculateBaseStartDate_RelativeDate_LastFriday(): void
    {
        $master = array(
            'recurrence_type'   => 'RELATIVE_DATE',
            'recur_month'       => 11,
            'recur_week_num'    => -1,
            'recur_day_of_week' => 5
        );

        $result = $this->invokeMethod($this->_engine, '_calculateBaseStartDate', array($master, 2026));
        $this->assertEquals('2026-11-27', $result);
    }

    /* ====================================================================
     * TEST GROUP 2: WEEKEND SHIFT ALGORITHMS
     * ==================================================================== */

    public function testApplyWeekendShift_SaturdayToNextWorkingDay(): void
    {
        $result = $this->invokeMethod($this->_engine, '_applyWeekendShift', array('2026-07-04', 'NEXT_WORKING_DAY', array(6, 7)));
        $this->assertEquals('2026-07-06', $result);
    }

    public function testApplyWeekendShift_SundayToPreviousWorkingDay(): void
    {
        $result = $this->invokeMethod($this->_engine, '_applyWeekendShift', array('2026-11-01', 'PREVIOUS_WORKING_DAY', array(6, 7)));
        $this->assertEquals('2026-10-30', $result);
    }

    public function testApplyWeekendShift_MiddleEastWeekend(): void
    {
        $result = $this->invokeMethod($this->_engine, '_applyWeekendShift', array('2026-07-03', 'NEXT_WORKING_DAY', array(5, 6)));
        $this->assertEquals('2026-07-05', $result);
    }

    /* ====================================================================
     * TEST GROUP 3: CONTINUOUS BLOCK & LINE GENERATION
     * ==================================================================== */

    public function testCalculateBlockAndLines_FractionalDay(): void
    {
        $result = $this->invokeMethod($this->_engine, '_calculateBlockAndLines', array('2026-12-24', 0.5, array(6, 7)));

        $this->assertEquals('2026-12-24', $result['start_date']);
        $this->assertEquals('2026-12-24', $result['end_date']);
        $this->assertCount(1, $result['lines']);
        $this->assertEquals(1, $result['lines'][0]['sequence_no']);
    }

    public function testCalculateBlockAndLines_SpanningWeekends(): void
    {
        $result = $this->invokeMethod($this->_engine, '_calculateBlockAndLines', array('2026-10-22', 3.0, array(6, 7)));

        $this->assertEquals('2026-10-22', $result['start_date']);
        $this->assertEquals('2026-10-26', $result['end_date']);
        $this->assertCount(3, $result['lines']);
        $this->assertEquals('2026-10-22', $result['lines'][0]['effective_date']);
        $this->assertEquals('2026-10-23', $result['lines'][1]['effective_date']);
        $this->assertEquals('2026-10-26', $result['lines'][2]['effective_date']);
    }

    /* ====================================================================
     * TEST GROUP 4: DNF CRITERIA INTERSECTION
     * ==================================================================== */

    public function testDoCriteriaIntersect_True_OverlappingAttributes(): void
    {
        $criteriaA = array(
            array('criteria_type' => 'Department', 'criteria_value' => 'Sales'),
            array('criteria_type' => 'Department', 'criteria_value' => 'Marketing')
        );
        $criteriaB = array(
            array('criteria_type' => 'Department', 'criteria_value' => 'Sales')
        );

        $result = $this->invokeMethod($this->_engine, '_doCriteriaIntersect', array($criteriaA, $criteriaB));
        $this->assertTrue($result);
    }

    public function testDoCriteriaIntersect_False_MutuallyExclusive(): void
    {
        $criteriaA = array(
            array('criteria_type' => 'Department', 'criteria_value' => 'Sales'),
            array('criteria_type' => 'Gender', 'criteria_value' => 'Female')
        );
        $criteriaB = array(
            array('criteria_type' => 'Department', 'criteria_value' => 'Engineering'),
            array('criteria_type' => 'Gender', 'criteria_value' => 'Female')
        );

        $result = $this->invokeMethod($this->_engine, '_doCriteriaIntersect', array($criteriaA, $criteriaB));
        $this->assertFalse($result);
    }
}
