<?php

/**
 * Sentrifugo Holiday Engine Service
 *
 * Implements Phase 5 Business Logic:
 * - Recurrence calculations (Fixed & Ordinal relative weekdays)
 * - Weekend policy shifting
 * - Continuous block calculations & Line generation
 * - Immutable snapshot stamping
 *
 * Complies with SSOT v6.7.7 (PHP 7.4 strict, Service Layer Pattern).
 *
 * @category   Library
 * @package    Sentrifugo_Service
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Sentrifugo_Service_HolidayEngine
{
    /**
     * @var Default_Model_Holiday
     */
    protected $_masterModel;

    /**
     * @var Default_Model_Holidaydate
     */
    protected $_dateHeaderModel;

    /**
     * @var Default_Model_Holidaydateline
     */
    protected $_dateLineModel;

    /**
     * Constructor
     */
    public function __construct()
    {
    
    }

    /**
     * Lazy Load Master Model
     */
    protected function _getMasterModel()
    {
        if ($this->_masterModel === null) {
            $this->_masterModel = new Default_Model_Holiday();
        }
        return $this->_masterModel;
    }

    /**
     * Lazy Load Date Header Model
     */
    protected function _getDateHeaderModel()
    {
        if ($this->_dateHeaderModel === null) {
            $this->_dateHeaderModel = new Default_Model_Holidaydate();
        }
        return $this->_dateHeaderModel;
    }

    /**
     * Lazy Load Date Line Model
     */
    protected function _getDateLineModel()
    {
        if ($this->_dateLineModel === null) {
            $this->_dateLineModel = new Default_Model_Holidaydateline();
        }
        return $this->_dateLineModel;
    }

    /* =================================================================================
     * GROUP 1: GENERATION ORCHESTRATION
     * ================================================================================= */

    /**
     * Core Generation Orchestrator.
     * Takes a specific Master Policy and generates the concrete occurrence (Header + Lines) for a given Year.
     *
     * @param int $masterHolidayId
     * @param int $targetYear
     * @param int $userId
     * @return array Result payload: ['status' => 'success'|'skipped'|'error', 'message' => '...', 'header_id' => int]
     * @throws Exception
     */
    public function generateOccurrenceForYear(int $masterHolidayId, int $targetYear, int $userId): array
    {
        $master = $this->_getMasterModel()->getRecordById($masterHolidayId);
        if (!$master) {
            throw new Exception("Master Holiday Policy ID {$masterHolidayId} not found.");
        }

        if ($master['recurrence_type'] === 'MANUAL') {
            return [
                'status'  => 'skipped',
                'message' => "Policy '{$master['name']}' is set to MANUAL recurrence. Skipped automatic generation."
            ];
        }

        $baseStartDate = $this->_calculateBaseStartDate($master, $targetYear);
        if (empty($baseStartDate)) {
            throw new Exception("Failed to resolve recurrence date for '{$master['name']}' in year {$targetYear}.");
        }

        $resolvedStartDate = $this->_applyWeekendShift($baseStartDate, $master['weekend_policy']);

        $existingHeader = $this->_getDateHeaderModel()->checkDuplicateGeneration($masterHolidayId, $resolvedStartDate);
        if ($existingHeader) {
            return [
                'status'    => 'skipped',
                'message'   => "Occurrence for '{$master['name']}' on {$resolvedStartDate} already exists.",
                'header_id' => (int)$existingHeader['id']
            ];
        }

        $blockData = $this->_calculateBlockAndLines($resolvedStartDate, (float)$master['duration_days']);

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->beginTransaction();

        try {
            $currentDate = gmdate("Y-m-d H:i:s");

            $headerData = [
                'holiday_id'         => $masterHolidayId,
                'start_date'         => $blockData['start_date'],
                'end_date'           => $blockData['end_date'],
                'duration_days'      => (float)$master['duration_days'],
                'start_time'         => $master['start_time'],
                'end_time'           => $master['end_time'],
                'is_paid'            => (int)$master['is_paid'],
                'is_mandatory'       => (int)$master['is_mandatory'],
                'work_policy'        => $master['work_policy'],
                'payroll_multiplier' => (float)$master['payroll_multiplier'],
                'weekend_policy'     => $master['weekend_policy'],
                'priority'           => (int)$master['priority'],
                'description'        => "Auto-generated for year {$targetYear} from Master Policy.",
                'isactive'           => 1,
                'createdby'          => $userId,
                'createddate'        => $currentDate
            ];

            $headerId = (int)$this->_getDateHeaderModel()->saveRecord($headerData);

            foreach ($blockData['lines'] as $line) {
                $lineData = [
                    'holiday_date_id' => $headerId,
                    'sequence_no'     => (int)$line['sequence_no'],
                    'effective_date'  => $line['effective_date'],
                    'isactive'        => 1,
                    'createddate'     => $currentDate
                ];
                $this->_getDateLineModel()->saveRecord($lineData);
            }

            $db->commit();

            return [
                'status'      => 'success',
                'message'     => "Successfully generated '{$master['name']}' ({$blockData['start_date']} to {$blockData['end_date']}).",
                'header_id'   => $headerId,
                'lines_count' => count($blockData['lines'])
            ];

        } catch (\Throwable $e) {
            $db->rollBack();
            throw new Exception("Error persisting generated holiday: " . $e->getMessage());
        }
    }

    /**
     * Batch generator for all active auto-generating holidays in a given year.
     * Used by Cron jobs or the "Generate Year" administrative action.
     *
     * @param int $targetYear
     * @param int $userId
     * @return array Summary of generated, skipped, and failed policies
     */
    public function generateAllHolidaysForYear(int $targetYear, int $userId): array
    {
        $select = $this->_getMasterModel()->select()
            ->where('isactive = ?', 1)
            ->where('auto_generate = ?', 1)
            ->where("recurrence_type != 'MANUAL'");

        $policies = $this->_getMasterModel()->fetchAll($select)->toArray();

        $summary = [
            'total'     => count($policies),
            'generated' => 0,
            'skipped'   => 0,
            'errors'    => []
        ];

        foreach ($policies as $policy) {
            try {
                $result = $this->generateOccurrenceForYear((int)$policy['id'], $targetYear, $userId);
                if ($result['status'] === 'success') {
                    $summary['generated']++;
                } else {
                    $summary['skipped']++;
                }
            } catch (\Throwable $e) {
                $summary['errors'][] = "Policy '{$policy['name']}': " . $e->getMessage();
            }
        }

        return $summary;
    }

    /* =================================================================================
     * GROUP 2: RECURRENCE & DATE ALGORITHMS
     * ================================================================================= */

    /**
     * Resolves the unshifted base start date using strict recurrence rules.
     *
     * @param array $master
     * @param int $targetYear
     * @return string (Y-m-d)
     */
    protected function _calculateBaseStartDate(array $master, int $targetYear): string
    {
        $type = $master['recurrence_type'];

        if ($type === 'FIXED_DATE') {
            $month = str_pad((string)$master['recur_month'], 2, '0', STR_PAD_LEFT);
            $day   = str_pad((string)$master['recur_day'], 2, '0', STR_PAD_LEFT);
            return "{$targetYear}-{$month}-{$day}";
        }

        if ($type === 'RELATIVE_DATE') {
            $monthNum = (int)$master['recur_month'];
            $weekNum  = (int)$master['recur_week_num']; // 1, 2, 3, 4, or -1 (Last)
            $dayNum   = (int)$master['recur_day_of_week']; // 1=Mon, ..., 7=Sun

            $dayMap = [
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                7 => 'Sunday'
            ];
            $dayName = $dayMap[$dayNum];

            $monthName = date('F', mktime(0, 0, 0, $monthNum, 10));

            if ($weekNum === -1) {
                // e.g., "last friday of december 2026"
                $dateStr = "last {$dayName} of {$monthName} {$targetYear}";
            } else {
                $ordinalMap = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth'];
                $ordinal = $ordinalMap[$weekNum];
                // e.g., "fourth thursday of november 2026"
                $dateStr = "{$ordinal} {$dayName} of {$monthName} {$targetYear}";
            }

            $timestamp = strtotime($dateStr);
            return date('Y-m-d', $timestamp);
        }

        return '';
    }

    /**
     * Shifts the calculated base date if it falls on a designated rest day, 
     * according to the Master Policy's weekend rule.
     *
     * @param string $baseDate (Y-m-d)
     * @param string $policy ENUM
     * @param array $restDays Array of integer days considered weekends (1=Mon ... 7=Sun). e.g., [6, 7]
     * @return string (Y-m-d)
     */
    protected function _applyWeekendShift(string $baseDate, string $policy, array $restDays = [6, 7]): string
    {
        if ($policy === 'FIXED_DATE' || $policy === 'NO_SUBSTITUTION') {
            return $baseDate;
        }

        $dt = new DateTime($baseDate);

        // Keep shifting until we land on a day that is NOT in the $restDays array
        while (in_array((int)$dt->format('N'), $restDays, true)) {
            if ($policy === 'NEXT_WORKING_DAY') {
                $dt->modify('+1 day');
            } elseif ($policy === 'PREVIOUS_WORKING_DAY') {
                $dt->modify('-1 day');
            } elseif ($policy === 'NEXT_REST_DAY') {
                // Highly specific edge case: Moves deeper into the rest period. Rarely used, but supported.
                $dt->modify('+1 day');
            } else {
                // Failsafe break to prevent infinite loops on malformed policy data
                break;
            }
        }

        return $dt->format('Y-m-d');
    }

    /**
     * Calculates the continuous holiday block and discrete lines.
     * Skips designated rest days while counting the required duration.
     *
     * @param string $startDate (Y-m-d)
     * @param float $durationDays
     * @param array $restDays Array of integer days considered weekends (e.g., [6, 7])
     * @return array ['start_date' => string, 'end_date' => string, 'lines' => array]
     */
    protected function _calculateBlockAndLines(string $startDate, float $durationDays, array $restDays = [6, 7]): array
    {
        $dt = new DateTime($startDate);
        $lines = [];

        // For fractional/single-day holidays (e.g., 0.5 or 1.0 day)
        if ($durationDays <= 1.0) {
            $lines[] = [
                'sequence_no'    => 1,
                'effective_date' => $startDate
            ];
            return [
                'start_date' => $startDate,
                'end_date'   => $startDate,
                'lines'      => $lines
            ];
        }

        // For multi-day blocks (e.g., 3.0 days)
        $totalDaysRequired = (int)ceil($durationDays);
        $currentDate = clone $dt;
        $endDate = $startDate;
        $seq = 1;

        while ($seq <= $totalDaysRequired) {
            $dayOfWeek = (int)$currentDate->format('N');

            // If the current day is a Rest Day, skip it and DO NOT increment the sequence counter
            if (in_array($dayOfWeek, $restDays, true)) {
                $currentDate->modify('+1 day');
                continue;
            }

            // It's a valid working day. Record it.
            $formattedDate = $currentDate->format('Y-m-d');
            $lines[] = [
                'sequence_no'    => $seq,
                'effective_date' => $formattedDate
            ];
            $endDate = $formattedDate;

            // Move to next calendar day and increment our required day counter
            $currentDate->modify('+1 day');
            $seq++;
        }

        return [
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'lines'      => $lines
        ];
    }

    /* =================================================================================
     * GROUP 3: VALIDATION & CONFLICT RESOLUTION (PENDING PHASE 7)
     * ================================================================================= */

    /**
     * Checks if a proposed holiday block overlaps with existing holidays 
     * AND intersects with the same target demographic criteria.
     *
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @param int $masterHolidayId The ID of the holiday policy being saved/generated
     * @param int|null $excludeHeaderId (For editing existing occurrence records)
     * @return array List of conflicting holiday occurrences with priority guidance
     */
    public function checkOverlap(string $startDate, string $endDate, int $masterHolidayId, ?int $excludeHeaderId = null): array
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        // 1. Fetch the master policy
        $currentMaster = $this->_masterModel->getRecordById($masterHolidayId);
        if (!$currentMaster) {
            return [];
        }

        // 2. Fetch Date Collisions
        $select = $db->select()
            ->from(array('hd' => 'main_holiday_date'), array(
                'header_id'          => 'hd.id',
                'start_date'         => 'hd.start_date',
                'end_date'           => 'hd.end_date',
                'payroll_multiplier' => 'hd.payroll_multiplier',
                'priority'           => 'hd.priority',
                'work_policy'        => 'hd.work_policy',
                'holiday_id'         => 'hd.holiday_id'
            ))
            ->joinInner(
                array('h' => 'main_holiday'),
                'hd.holiday_id = h.id',
                array('policy_name' => 'h.name')
            )
            ->where('hd.start_date <= ?', $endDate)
            ->where('hd.end_date >= ?', $startDate)
            ->where('hd.isactive != ?', 5);

        if (!empty($excludeHeaderId)) {
            $select->where('hd.id != ?', $excludeHeaderId);
        }

        $dateCollisions = $db->fetchAll($select);
        if (empty($dateCollisions)) {
            return [];
        }

        // 3. Fetch Targeting Criteria for the Current Master Policy
        $criteriaModel = new Default_Model_Holidaycriteria();
        $currentCriteria = $criteriaModel->getCriteriaByHolidayId($masterHolidayId);

        $conflicts = [];
        $currentPriority = (int)$currentMaster['priority'];
        $currentMultiplier = (float)$currentMaster['payroll_multiplier'];

        foreach ($dateCollisions as $col) {
            // 4. CRITERIA INTERSECTION LOGIC
            $colCriteria = $criteriaModel->getCriteriaByHolidayId((int)$col['holiday_id']);

            // If either policy is Global (no criteria), they automatically intersect.
            // If both have criteria, check for intersection.
            $hasIntersection = true;

            if (!empty($currentCriteria) && !empty($colCriteria)) {
                $hasIntersection = $this->_doCriteriaIntersect($currentCriteria, $colCriteria);
            }

            // Only flag as conflict if targets intersect
            if ($hasIntersection) {
                $colPriority = (int)$col['priority'];
                $colMultiplier = (float)$col['payroll_multiplier'];

                $winner = '';
                if ($currentPriority > $colPriority) {
                    $winner = "'{$currentMaster['name']}' (Priority: {$currentPriority} vs {$colPriority})";
                } elseif ($colPriority > $currentPriority) {
                    $winner = "'{$col['policy_name']}' (Priority: {$colPriority} vs {$currentPriority})";
                } else {
                    if ($currentMultiplier >= $colMultiplier) {
                        $winner = "'{$currentMaster['name']}' (Multiplier: {$currentMultiplier}x vs {$colMultiplier}x)";
                    } else {
                        $winner = "'{$col['policy_name']}' (Multiplier: {$colMultiplier}x vs {$currentMultiplier}x)";
                    }
                }

                $conflicts[] = [
                    'existing_policy'   => $col['policy_name'],
                    'existing_dates'    => "{$col['start_date']} to {$col['end_date']}",
                    'existing_priority' => $colPriority,
                    'current_priority'  => $currentPriority,
                    'runtime_winner'    => $winner,
                    'message'           => "Target Demographic Overlaps with '{$col['policy_name']}' ({$col['start_date']} to {$col['end_date']}). Runtime precedence will favor {$winner}."
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Helper: Determines if two sets of DNF criteria potentially target the same employee.
     * Evaluates Group Homogeneity (MANDATORY ANDs vs OPTIONAL ORs).
     */
    protected function _doCriteriaIntersect(array $criteriaA, array $criteriaB): bool
    {
        // In a true DNF evaluation, if Group A targets "Sales" and Group B targets "Engineering", they do not intersect.
        // If Group A targets "Sales OR Engineering" and Group B targets "Sales", they intersect.
        // For SSOT v6.8, we use a rapid overlapping array check on attributes.

        $attributesA = [];
        foreach ($criteriaA as $rule) {
            $attributesA[$rule['criteria_type']][] = $rule['criteria_value'];
        }

        $attributesB = [];
        foreach ($criteriaB as $rule) {
            $attributesB[$rule['criteria_type']][] = $rule['criteria_value'];
        }

        foreach ($attributesA as $type => $valuesA) {
            if (isset($attributesB[$type])) {
                // If they share an attribute type (e.g. Department), do they share any actual values?
                $sharedValues = array_intersect($valuesA, $attributesB[$type]);
                if (empty($sharedValues)) {
                    // They target completely different Departments. They cannot intersect.
                    return false;
                }
            }
        }

        return true;
    }

    /* =================================================================================
     * GROUP 4: ELIGIBILITY RESOLUTION (CONSUMER HELPER)
     * ================================================================================= */

    /**
     * Determines if an employee is eligible for a specific holiday occurrence.
     * [Reinstated from Skeleton]
     */
    public function evaluateEmployeeEligibility(int $employeeId, int $holidayHeaderId): bool
    {
        // Implementation defers to downstream module requirements
        return false;
    }
}
