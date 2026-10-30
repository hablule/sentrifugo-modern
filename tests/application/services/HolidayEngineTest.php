<?php

use PHPUnit\Framework\TestCase;

// Load Composer's autoloader so PHPUnit and modern libraries are recognized
require_once __DIR__ . '/../../../vendor/autoload.php';

// Define Application Path for Sentrifugo
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(__DIR__ . '/../../../application'));

/**
 * Holiday Engine Test Suite
 *
 * Validates the core mathematical algorithms, recurrence parsers, 
 * weekend shifters, and boolean DNF intersection logic of the Holiday Engine.
 *
 * @category   Tests
 * @package    Sentrifugo_Service
 */
class HolidayEngineTest extends TestCase
{
    /**
     * @var Sentrifugo_Service_HolidayEngine
     */
    protected $engine;

    protected function setUp(): void
    {
        // Require the service class directly for isolated testing
        require_once APPLICATION_PATH . '/../library/Sentrifugo/Service/HolidayEngine.php';
        $this->engine = new Sentrifugo_Service_HolidayEngine();
    }

    /**
     * Helper to invoke protected methods for isolated algorithm testing
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /* ====================================================================
     * TEST GROUP 1: RECURRENCE ALGORITHMS
     * ==================================================================== */

    public function testCalculateBaseStartDate_FixedDate()
    {
        $master = [
            'recurrence_type' => 'FIXED_DATE',
            'recur_month'     => 12,
            'recur_day'       => 25
        ];

        $result = $this->invokeMethod($this->engine, '_calculateBaseStartDate', [$master, 2026]);
        $this->assertEquals('2026-12-25', $result);
    }

    public function testCalculateBaseStartDate_RelativeDate_FirstMonday()
    {
        $master = [
            'recurrence_type'     => 'RELATIVE_DATE',
            'recur_month'         => 9,
            'recur_week_num'      => 1,
            'recur_day_of_week'   => 1 // Monday
        ];

        $result = $this->invokeMethod($this->engine, '_calculateBaseStartDate', [$master, 2026]);
        $this->assertEquals('2026-09-07', $result);
    }

    public function testCalculateBaseStartDate_RelativeDate_LastFriday()
    {
        $master = [
            'recurrence_type'     => 'RELATIVE_DATE',
            'recur_month'         => 11,
            'recur_week_num'      => -1, // Last
            'recur_day_of_week'   => 5   // Friday
        ];

        $result = $this->invokeMethod($this->engine, '_calculateBaseStartDate', [$master, 2026]);
        $this->assertEquals('2026-11-27', $result);
    }

    /* ====================================================================
     * TEST GROUP 2: WEEKEND SHIFT ALGORITHMS
     * ==================================================================== */

    public function testApplyWeekendShift_SaturdayToNextWorkingDay()
    {
        $baseDate = '2026-07-04'; // Saturday
        
        $result = $this->invokeMethod($this->engine, '_applyWeekendShift', [$baseDate, 'NEXT_WORKING_DAY', [6, 7]]);
        
        $this->assertEquals('2026-07-06', $result);
    }

    public function testApplyWeekendShift_SundayToPreviousWorkingDay()
    {
        $baseDate = '2026-11-01'; // Sunday
        
        $result = $this->invokeMethod($this->engine, '_applyWeekendShift', [$baseDate, 'PREVIOUS_WORKING_DAY', [6, 7]]);
        
        $this->assertEquals('2026-10-30', $result);
    }

    public function testApplyWeekendShift_MiddleEastWeekend()
    {
        // Fri/Sat weekends (Days 5 and 6)
        $baseDate = '2026-07-03'; // Friday
        $restDays = [5, 6]; 
        
        $result = $this->invokeMethod($this->engine, '_applyWeekendShift', [$baseDate, 'NEXT_WORKING_DAY', $restDays]);
        
        $this->assertEquals('2026-07-05', $result);
    }

    /* ====================================================================
     * TEST GROUP 3: CONTINUOUS BLOCK & LINE GENERATION
     * ==================================================================== */

    public function testCalculateBlockAndLines_FractionalDay()
    {
        $result = $this->invokeMethod($this->engine, '_calculateBlockAndLines', ['2026-12-24', 0.5, [6, 7]]);

        $this->assertEquals('2026-12-24', $result['start_date']);
        $this->assertEquals('2026-12-24', $result['end_date']);
        $this->assertCount(1, $result['lines']);
        $this->assertEquals(1, $result['lines'][0]['sequence_no']);
    }

    public function testCalculateBlockAndLines_SpanningWeekends()
    {
        // Thursday start, 3 working days -> Thu (1), Fri (2), [Skip Sat/Sun], Mon (3)
        $result = $this->invokeMethod($this->engine, '_calculateBlockAndLines', ['2026-10-22', 3.0, [6, 7]]);

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

    public function testDoCriteriaIntersect_True_OverlappingAttributes()
    {
        $criteriaA = [
            ['criteria_type' => 'Department', 'criteria_value' => 'Sales'],
            ['criteria_type' => 'Department', 'criteria_value' => 'Marketing']
        ];
        $criteriaB = [
            ['criteria_type' => 'Department', 'criteria_value' => 'Sales']
        ];

        $result = $this->invokeMethod($this->engine, '_doCriteriaIntersect', [$criteriaA, $criteriaB]);
        $this->assertTrue($result);
    }

    public function testDoCriteriaIntersect_False_MutuallyExclusive()
    {
        $criteriaA = [
            ['criteria_type' => 'Department', 'criteria_value' => 'Sales'],
            ['criteria_type' => 'Gender', 'criteria_value' => 'Female']
        ];
        $criteriaB = [
            ['criteria_type' => 'Department', 'criteria_value' => 'Engineering'],
            ['criteria_type' => 'Gender', 'criteria_value' => 'Female']
        ];

        $result = $this->invokeMethod($this->engine, '_doCriteriaIntersect', [$criteriaA, $criteriaB]);
        $this->assertFalse($result);
    }
}