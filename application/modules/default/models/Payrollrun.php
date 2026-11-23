<?php

/**
 * Payroll Run Model (The Master Control Ticket)
 *
 * Manages the state machine and calendar boundaries of payroll processing.
 * Enforces strict transactional uniqueness and state transition safeguards.
 * Complies with SSOT v6.7.7.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Payrollrun extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_payroll_run';
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if (!empty($searchData) && $searchData !== 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key === 'paygroup_name') {
                        $searchQuery .= " (pg.name LIKE '%" . trim((string)$val) . "%' OR pg.code LIKE '%" . trim((string)$val) . "%') AND ";
                    } elseif (in_array($key, ['cycle_start_date', 'cycle_end_date', 'pay_date'])) {
                        $searchQuery .= " pr." . $key . " = '" . sapp_Global::change_date(urldecode($val), 'database') . "' AND ";
                    } else {
                        $searchQuery .= " pr." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        $tableHeaders = [
            'action'           => 'Action',
            'paygroup_name'    => 'Pay Group',
            'cycle_sequence'   => 'Sequence',
            'cycle_start_date' => 'Cycle Start',
            'cycle_end_date'   => 'Cycle End',
            'pay_date'         => 'Pay Date',
            'run_status'       => 'Run Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'payrollrun',
            'extra'          => [],
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Payroll Runs',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'run_status' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Scheduled' => 'Scheduled', 'Initiated' => 'Initiated', 'Review' => 'Review', 'Locked' => 'Locked', 'Closed' => 'Closed', 'Cancelled' => 'Cancelled']
                ],
                'cycle_start_date' => ['type' => 'datepicker'],
                'cycle_end_date'   => ['type' => 'datepicker'],
                'pay_date'         => ['type' => 'datepicker']
            ]
        ];
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'paygroup_name') {
            $by = 'pg.name';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['pr' => $this->_name],
                [
                    'id'               => 'pr.id',
                    'cycle_sequence',
                    'run_status',
                    'cycle_start_date' => new Zend_Db_Expr("DATE_FORMAT(pr.cycle_start_date, '" . DATEFORMAT_MYSQL . "')"),
                    'cycle_end_date'   => new Zend_Db_Expr("DATE_FORMAT(pr.cycle_end_date, '" . DATEFORMAT_MYSQL . "')"),
                    'pay_date'         => new Zend_Db_Expr("DATE_FORMAT(pr.pay_date, '" . DATEFORMAT_MYSQL . "')")
                ]
            )
            ->joinInner(
                ['pg' => 'main_pay_group'],
                'pr.pay_group_id = pg.id',
                ['paygroup_name' => new Zend_Db_Expr("CONCAT(pg.code, ' - ', pg.name)")]
            )
            ->where('pr.isactive != ?', 5);

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    public function getRecordById($id)
    {
        $select = $this->select()
            ->where('id = ?', (int)$id)
            ->where('isactive != ?', 5);

        $row = $this->fetchRow($select);
        return $row ? $row->toArray() : null;
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & STATE MACHINE SAFEGUARDS
     * ================================================================================= */

    /**
     * BUSINESS RULE 1: Cancellation Lock
     * Ensures 'Locked' or 'Closed' runs cannot be transitioned to 'Cancelled'.
     */
    public function canBeCancelled(int $runId): bool
    {
        $record = $this->getRecordById($runId);
        if (!$record) return false;

        $forbiddenStates = ['Locked', 'Closed'];
        return !in_array($record['run_status'], $forbiddenStates, true);
    }

    /**
     * BUSINESS RULE 2: Soft-Delete Lock
     * Ensures isactive=5 can only be applied to un-initiated 'Scheduled' runs.
     */
    public function canBeSoftDeleted(int $runId): bool
    {
        $record = $this->getRecordById($runId);
        if (!$record) return false;

        return $record['run_status'] === 'Scheduled';
    }

    /**
     * BUSINESS RULE 3: Strict Application-Level Uniqueness
     * Ensures we don't insert a duplicate run (Group + Start + End) that is active.
     */
    public function isDuplicateRun(int $payGroupId, string $startDate, string $endDate): bool
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from($this->_name, ['id'])
            ->where('pay_group_id = ?', $payGroupId)
            ->where('cycle_start_date = ?', $startDate)
            ->where('cycle_end_date = ?', $endDate)
            ->where('isactive != ?', 5);

        return (bool)$db->fetchRow($select);
    }

    /* =================================================================================
     * GROUP 6: ENGINE CALCULATORS & RECONCILIATION
     * ================================================================================= */

    /**
     * Counts how many active runs exist in the given year strictly BEFORE the target start date.
     * Ensures gap-filled earlier runs receive their true chronological sequence (e.g., 1 for July).
     *
     * @param int $payGroupId
     * @param int $startYear
     * @param string $beforeStartDate (Y-m-d)
     * @return int
     */
    public function countPriorRunsInYear(int $payGroupId, int $startYear, string $beforeStartDate): int
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from($this->_name, array('cnt' => new Zend_Db_Expr('COUNT(id)')))
            ->where('pay_group_id = ?', $payGroupId)
            ->where('YEAR(cycle_start_date) = ?', $startYear)
            ->where('cycle_start_date < ?', $beforeStartDate)
            ->where('isactive != ?', 5);

        return (int) $db->fetchOne($select);
    }


    /**
     * Fetches all existing active runs within a specific date window for diffing.
     * 
     * @param int $payGroupId
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @return array
     */
    public function getRunsInWindow(int $payGroupId, string $startDate, string $endDate): array
    {
        $select = $this->select()
            ->where('pay_group_id = ?', $payGroupId)
            ->where('cycle_start_date <= ?', $endDate)
            ->where('cycle_end_date >= ?', $startDate)
            ->where('isactive != ?', 5)
            ->order('cycle_start_date ASC');

        return $this->fetchAll($select)->toArray();
    }


    /**
     * Reconciles calculated dates against the database using the Smart Gap-Filling Diff Algorithm.
     * Implements the "Dry-Run / Metric Return Contract".
     */
    public function reconcileAndCommit(int $payGroupId, array $calculatedRuns, int $userId, bool $isDryRun = false): array
    {
        $metrics = array(
            'created'   => 0,
            'updated'   => 0,
            'untouched' => 0,
            'locked'    => 0,
            'details'   => array()
        );

        if (empty($calculatedRuns)) {
            return $metrics;
        }

        $minDate = $calculatedRuns[0]['cycle_start_date'];
        $maxDate = end($calculatedRuns)['cycle_end_date'];

        $existingRuns = $this->getRunsInWindow($payGroupId, $minDate, $maxDate);

        $existingMap = array();
        foreach ($existingRuns as $ex) {
            $existingMap[$ex['cycle_start_date']] = $ex;
        }

        $toInsert = array();
        $toUpdate = array();

        foreach ($calculatedRuns as $calc) {
            $startDate  = $calc['cycle_start_date'];
            $endDate    = $calc['cycle_end_date'];
            $payDate    = $calc['pay_date'];
            $sequenceNo = (int)$calc['cycle_sequence'];

            if (isset($existingMap[$startDate])) {
                $current = $existingMap[$startDate];

                // Check 1: Is the run past 'Scheduled'? (Protected Boundary)
                if ($current['run_status'] !== 'Scheduled') {
                    $metrics['locked']++;
                    $metrics['details'][] = "Protected run #{$current['id']} ({$current['run_status']}) covering {$startDate} to {$endDate}.";
                    continue;
                }

                // Check 2: Are the dates and sequence completely identical? (Exact Match)
                if ($current['cycle_end_date'] === $endDate && $current['pay_date'] === $payDate && (int)$current['cycle_sequence'] === $sequenceNo) {
                    $metrics['untouched']++;
                    continue;
                }

                // Check 3: Configuration shifted dates or sequence -> Update existing Scheduled run
                $toUpdate[] = array(
                    'id'               => (int) $current['id'],
                    'cycle_end_date'   => $endDate,
                    'pay_date'         => $payDate,
                    'cycle_sequence'   => $sequenceNo,
                    'modifiedby'       => $userId,
                    'modifieddate'     => gmdate('Y-m-d H:i:s')
                );
                $metrics['updated']++;
                $metrics['details'][] = "Adjusted Scheduled run #{$current['id']} (Seq #{$sequenceNo}) to end {$endDate}, pay {$payDate}.";
            } else {
                // Gap Detected: Prepare Insert with exact mathematical sequence
                $toInsert[] = array(
                    'pay_group_id'     => $payGroupId,
                    'cycle_start_date' => $startDate,
                    'cycle_end_date'   => $endDate,
                    'pay_date'         => $payDate,
                    'cycle_sequence'   => $sequenceNo,
                    'run_status'       => 'Scheduled',
                    'isactive'         => 1,
                    'createdby'        => $userId,
                    'createddate'      => gmdate('Y-m-d H:i:s')
                );
                $metrics['created']++;
                $metrics['details'][] = "Created new Scheduled run (Seq #{$sequenceNo}) for {$startDate} to {$endDate}.";
            }
        }

        if ($isDryRun) {
            return $metrics;
        }

        $db = $this->getAdapter();
        $db->beginTransaction();

        try {
            foreach ($toUpdate as $upRow) {
                $runId = $upRow['id'];
                unset($upRow['id']);
                $this->update($upRow, array('id = ?' => $runId));
            }

            foreach ($toInsert as $inRow) {
                $this->insert($inRow);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return $metrics;
    }
}
