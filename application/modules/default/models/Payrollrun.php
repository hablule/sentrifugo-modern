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
}
