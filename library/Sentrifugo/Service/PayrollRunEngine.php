<?php

/**
 * Payroll Run Generator Engine Service
 *
 * Mathematical orchestrator for generating, gap-filling, and holiday-shifting payroll runs.
 * Implements the 3 Flat Trigger Interfaces and the Dry-Run Metric Return Contract.
 * Complies with SSOT v6.7.7.
 */
class Sentrifugo_Service_PayrollRunEngine
{
    /** @var Default_Model_Payrollrun */
    protected $_runModel;

    /** @var Default_Model_Paygroup */
    protected $_groupModel;

    public function __construct()
    {
        $this->_runModel   = new Default_Model_Payrollrun();
        $this->_groupModel = new Default_Model_Paygroup();
    }

    /* =================================================================================
     * THE 3 EXPLICIT TRIGGER INTERFACES
     * ================================================================================= */

    /**
     * Trigger 1: Generates/Fills runs for a specific Pay Group over an explicit date window.
     * (Called by PaygroupController after saving).
     */
    public function generateForPayGroup(int $payGroupId, string $startDate, string $endDate, int $userId = 0, bool $isDryRun = false): array
    {
        $group = $this->_groupModel->getGroupWithCadence($payGroupId);
        if (!$group) {
            throw new Exception("Pay Group #{$payGroupId} is invalid or inactive.");
        }

        $calculatedRuns = $this->_calculateCadenceDates($group, $startDate, $endDate);
        return $this->_runModel->reconcileAndCommit($payGroupId, $calculatedRuns, $userId, $isDryRun);
    }

    /**
     * Trigger 2: Generates runs for ALL active Pay Groups spanning a specific Accounting Period.
     * (Called by PeriodController when a new Period/Fiscal Year is opened).
     */
    public function generateForPeriod(int $periodId, int $userId = 0, bool $isDryRun = false): array
    {
        $periodModel = new Default_Model_Period();
        $period = $periodModel->getRecordById($periodId);
        if (!$period) {
            throw new Exception("Period #{$periodId} does not exist.");
        }

        $startDate = $period['start_date'];
        $endDate   = $period['end_date'];

        $activeGroups = $this->_groupModel->getAllRecords(null, null, null, null, 1);
        $aggregateMetrics = array('created' => 0, 'updated' => 0, 'untouched' => 0, 'locked' => 0, 'details' => array());

        foreach ($activeGroups as $grp) {
            $res = $this->generateForPayGroup((int)$grp['id'], $startDate, $endDate, $userId, $isDryRun);
            $aggregateMetrics['created']   += $res['created'];
            $aggregateMetrics['updated']   += $res['updated'];
            $aggregateMetrics['untouched'] += $res['untouched'];
            $aggregateMetrics['locked']    += $res['locked'];
            $aggregateMetrics['details']    = array_merge($aggregateMetrics['details'], $res['details']);
        }

        return $aggregateMetrics;
    }

    /**
     * Trigger 3: Manual Sync / Gap Fill initiated by an HR Admin via the UI.
     */
    public function generateManual(int $payGroupId, string $startDate, string $endDate, int $userId, bool $isDryRun = false): array
    {
        return $this->generateForPayGroup($payGroupId, $startDate, $endDate, $userId, $isDryRun);
    }

    /**
     * Trigger 4: Safety Net Cron (Looks ahead X days from today).
     */
    public function generateNightlyCron(int $lookaheadDays = 90): array
    {
        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime("+{$lookaheadDays} days"));

        $activeGroups = $this->_groupModel->getAllRecords(null, null, null, null, 1);
        $metrics = array('created' => 0, 'updated' => 0, 'untouched' => 0, 'locked' => 0, 'details' => array());

        foreach ($activeGroups as $grp) {
            $res = $this->generateForPayGroup((int)$grp['id'], $startDate, $endDate, 0, false);
            $metrics['created']   += $res['created'];
            $metrics['updated']   += $res['updated'];
            $metrics['untouched'] += $res['untouched'];
            $metrics['locked']    += $res['locked'];
        }

        return $metrics;
    }

    /* =================================================================================
     * MATHEMATICAL CADENCE ENGINES
     * ================================================================================= */

    protected function _calculateCadenceDates(array $group, string $windowStart, string $windowEnd): array
    {
        // 1. Project pure cycle boundaries for the requested window
        $rawRuns = $this->_projectCycleBoundaries($group, $windowStart, $windowEnd);
        if (empty($rawRuns)) {
            return array();
        }

        // 2. Build true calendar-year sequence maps for all start years touched
        $yearMaps = array();
        foreach ($rawRuns as &$run) {
            $startYear = (int) date('Y', strtotime($run['cycle_start_date']));
            if (!isset($yearMaps[$startYear])) {
                $yearMaps[$startYear] = $this->_buildYearSequenceMap($group, $startYear);
            }

            // Assign true mathematical sequence within that calendar year
            $run['cycle_sequence'] = isset($yearMaps[$startYear][$run['cycle_start_date']])
                ? $yearMaps[$startYear][$run['cycle_start_date']]
                : 1;

            // 3. Apply Holiday Forward Shift ONLY to runs inside the actual window
            $run['pay_date'] = $this->_resolveHolidayForwardShift($run['pay_date']);
        }

        return $rawRuns;
    }


    /**
     * FORWARD Holiday Shift Rule:
     * If the pay date lands on a Holiday marked as OFF_DAY, roll forward (+1 day)
     * until a non-holiday working date is found. Protects the payment lag buffer.
     * 
     * @param string $tentativeDate (Y-m-d)
     * @return string (Y-m-d)
     */
    protected function _resolveHolidayForwardShift(string $tentativeDate): string
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $currDate = new DateTime($tentativeDate);

        for ($iteration = 0; $iteration < 14; $iteration++) { // Safety bound
            $dateStr = $currDate->format('Y-m-d');

            // Query immutable Holiday Line records joined to Header work_policy
            $select = $db->select()
                ->from(array('l' => 'main_holiday_date_line'), array('l.id'))
                ->joinInner(array('h' => 'main_holiday_date'), 'l.holiday_date_id = h.id', array())
                ->where('l.effective_date = ?', $dateStr)
                ->where('h.work_policy = ?', 'OFF_DAY')
                ->where('l.isactive = ?', 1);

            $isHolidayOffDay = (bool)$db->fetchOne($select);

            if (!$isHolidayOffDay) {
                return $dateStr;
            }

            // Roll forward to preserve the operational buffer
            $currDate->modify('+1 day');
        }

        return $currDate->format('Y-m-d');
    }


    /**
     * Builds a map of ['YYYY-MM-DD' => sequence_number] for every cycle starting in $year.
     */
    protected function _buildYearSequenceMap(array $group, int $year): array
    {
        $yearRuns = $this->_projectCycleBoundaries($group, "{$year}-01-01", "{$year}-12-31");
        $map = array();
        $seq = 1;

        foreach ($yearRuns as $yrRun) {
            $runStartYear = (int) date('Y', strtotime($yrRun['cycle_start_date']));
            // Strictly assign sequence only to cycles whose start_date falls inside $year
            if ($runStartYear === $year) {
                $map[$yrRun['cycle_start_date']] = $seq++;
            }
        }

        return $map;
    }

    /**
     * Pure date projection router (no DB writes or holiday queries).
     */
    protected function _projectCycleBoundaries(array $group, string $windowStart, string $windowEnd): array
    {
        if ($group['frequency_type'] === 'FIXED_INTERVAL') {
            return $this->_generateFixedIntervalRuns($group, $windowStart, $windowEnd);
        } else {
            return $this->_generateCalendarBasedRuns($group, $windowStart, $windowEnd);
        }
    }

    /**
     * Algorithm A: Fixed Interval Generator (e.g. Bi-Weekly)
     */
    protected function _generateFixedIntervalRuns(array $group, string $windowStart, string $windowEnd): array
    {
        $runs = array();
        $refDateStr = !empty($group['global_reference_date']) ? $group['global_reference_date'] : $windowStart;
        $unit = strtoupper($group['interval_unit']); // DAY, WEEK, MONTH
        $step = (int)$group['interval_count'];
        $lag  = (int)$group['payment_lag_days'];

        $dtRef = new DateTime($refDateStr);
        $dtWinStart = new DateTime($windowStart);
        $dtWinEnd   = new DateTime($windowEnd);

        // Step 1: Align cursor backwards if reference date is in the future
        while ($dtRef > $dtWinStart) {
            $dtRef->modify("-{$step} {$unit}");
        }

        // Step 2: Roll forward until reaching window start
        while (true) {
            $cycleStart = clone $dtRef;
            $cycleEnd   = clone $dtRef;
            $cycleEnd->modify("+{$step} {$unit}")->modify('-1 day');

            if ($cycleEnd >= $dtWinStart) {
                break;
            }
            $dtRef->modify("+{$step} {$unit}");
        }

        // Step 3: Generate cycles spanning the window
        while ($cycleStart <= $dtWinEnd) {
            $cycleEnd = clone $cycleStart;
            $cycleEnd->modify("+{$step} {$unit}")->modify('-1 day');

            $tentativePayDate = clone $cycleEnd;
            if ($lag > 0) {
                $tentativePayDate->modify("+{$lag} days");
            }

            $runs[] = array(
                'cycle_start_date' => $cycleStart->format('Y-m-d'),
                'cycle_end_date'   => $cycleEnd->format('Y-m-d'),
                'pay_date'         => $tentativePayDate->format('Y-m-d')
            );

            $cycleStart->modify("+{$step} {$unit}");
        }

        return $runs;
    }

    /**
     * Algorithm B: Calendar Based Generator (e.g. Monthly, Semi-Monthly)
     */
    protected function _generateCalendarBasedRuns(array $group, string $windowStart, string $windowEnd): array
    {
        $runs = array();
        $anchors = $group['anchors'];
        if (empty($anchors)) {
            $anchors = array(1);
        }
        sort($anchors);

        $dtStart = new DateTime($windowStart);
        $dtEnd   = new DateTime($windowEnd);
        $lag     = (int)$group['payment_lag_days'];
        $policy  = $group['missing_date_policy'];

        $dtCursor = new DateTime($dtStart->format('Y-m-01'));
        while ($dtCursor <= $dtEnd) {
            $year  = (int)$dtCursor->format('Y');
            $month = (int)$dtCursor->format('m');
            $daysInMonth = (int)$dtCursor->format('t');

            $resolvedDates = array();
            foreach ($anchors as $anchor) {
                if ($anchor <= $daysInMonth) {
                    $resolvedDates[] = sprintf('%04d-%02d-%02d', $year, $month, $anchor);
                } else {
                    if ($policy === 'NEXT_VALID_DAY') {
                        $nextMonth = clone $dtCursor;
                        $nextMonth->modify('+1 month');
                        $resolvedDates[] = $nextMonth->format('Y-m-01');
                    } else {
                        $resolvedDates[] = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
                    }
                }
            }
            $resolvedDates = array_unique($resolvedDates);
            sort($resolvedDates);

            for ($i = 0; $i < count($resolvedDates); $i++) {
                $cStart = new DateTime($resolvedDates[$i]);

                if ($i < count($resolvedDates) - 1) {
                    $cEnd = new DateTime($resolvedDates[$i + 1]);
                    $cEnd->modify('-1 day');
                } else {
                    $nextMonthCursor = clone $dtCursor;
                    $nextMonthCursor->modify('+1 month');
                    $nextMonthDays = (int)$nextMonthCursor->format('t');
                    $firstAnchorNextMonth = min($anchors[0], $nextMonthDays);
                    $cEnd = new DateTime(sprintf('%04d-%02d-%02d', $nextMonthCursor->format('Y'), $nextMonthCursor->format('m'), $firstAnchorNextMonth));
                    $cEnd->modify('-1 day');
                }

                if ($cEnd >= $dtStart && $cStart <= $dtEnd) {
                    $tentativePayDate = clone $cEnd;
                    if ($lag > 0) $tentativePayDate->modify("+{$lag} days");

                    $runs[] = array(
                        'cycle_start_date' => $cStart->format('Y-m-d'),
                        'cycle_end_date'   => $cEnd->format('Y-m-d'),
                        'pay_date'         => $tentativePayDate->format('Y-m-d')
                    );
                }
            }

            $dtCursor->modify('+1 month');
        }

        return $runs;
    }
}
