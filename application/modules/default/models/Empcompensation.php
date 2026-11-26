<?php

/**
 * Employee Compensation Model
 *
 * Manages the effective-dated ledger of employee compensation packages,
 * linking employees to Pay Groups, Remuneration Basis, Currencies, and Base Rates.
 * Complies with SSOT v6.7.7 (Singular Naming, Zend_Db_Select, Structured Method Groups).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @version    Sentrifugo v3.2
 */
class Default_Model_Empcompensation extends Zend_Db_Table_Abstract
{
    /* --------------------------------------------------------------
     * Attributes (Visibility -> Alphabetical)
     * -------------------------------------------------------------- */

    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_emp_compensation';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != ?', 5);

        if ($activeStatus !== null) {
            $select->where('isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . trim((string)$search_str) . '%');
        }

        if ($limit !== null) {
            $select->limit((int)$limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Fetches the active compensation profile for an employee on a specific target date.
     *
     * @param int $employeeId
     * @param string|null $targetDate (Y-m-d, defaults to today)
     * @return array|null
     */
    public function getActiveCompensationForDate(int $employeeId, ?string $targetDate = null): ?array
    {
        $date = $targetDate ? $targetDate : gmdate('Y-m-d');

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('c' => $this->_name))
            ->joinInner(array('pg' => 'main_pay_group'), 'c.pay_group_id = pg.id', array('paygroup_code' => 'pg.code', 'paygroup_name' => 'pg.name'))
            ->joinInner(array('rb' => 'main_remunerationbasis'), 'c.remunerationbasis_id = rb.id', array('calculation_method' => 'rb.calculation_method', 'remuneration_name' => 'rb.name'))
            ->joinInner(array('cur' => 'main_currency'), 'c.currency_id = cur.id', array('currency_code' => 'cur.code', 'currency_symbol' => 'cur.symbol'))
            ->where('c.employee_id = ?', $employeeId)
            ->where('c.isactive = ?', 1)
            ->where('c.start_date <= ?', $date)
            ->where('c.end_date IS NULL OR c.end_date >= ?', $date)
            ->order('c.start_date DESC');

        $row = $this->fetchRow($select);
        return $row ? $row->toArray() : null;
    }

    /**
     * Timeline Provider: Fetches full chronological compensation history for an employee profile.
     *
     * @param int $employeeId
     * @return array
     */
    public function getCompensationByEmployee(int $employeeId): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('c' => $this->_name))
            ->joinInner(array('pg' => 'main_pay_group'), 'c.pay_group_id = pg.id', array('paygroup_name' => 'pg.name', 'paygroup_code' => 'pg.code'))
            ->joinInner(array('rb' => 'main_remunerationbasis'), 'c.remunerationbasis_id = rb.id', array('remuneration_name' => 'rb.name', 'calculation_method' => 'rb.calculation_method'))
            ->joinInner(array('cur' => 'main_currency'), 'c.currency_id = cur.id', array('currency_code' => 'cur.code', 'currency_symbol' => 'cur.symbol'))
            ->where('c.employee_id = ?', $employeeId)
            ->where('c.isactive != ?', 5)
            ->order('c.start_date DESC');

        return $this->fetchAll($select)->toArray();
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if (!empty($searchData) && $searchData !== 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key === 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " c.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'employee_name') {
                        $searchQuery .= " (e.userfullname LIKE '%" . trim((string)$val) . "%' OR e.firstname LIKE '%" . trim((string)$val) . "%' OR e.lastname LIKE '%" . trim((string)$val) . "%') AND ";
                    } elseif ($key === 'paygroup_name') {
                        $searchQuery .= " (pg.name LIKE '%" . trim((string)$val) . "%' OR pg.code LIKE '%" . trim((string)$val) . "%') AND ";
                    } elseif ($key === 'remuneration_name') {
                        $searchQuery .= " rb.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'currency_code') {
                        $searchQuery .= " cur.code LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'start_date' || $key === 'end_date') {
                        $searchQuery .= " c." . $key . " = '" . sapp_Global::change_date(urldecode($val), 'database') . "' AND ";
                    } else {
                        $searchQuery .= " c." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        $tableHeaders = array(
            'action'            => 'Action',
            'employee_name'     => 'Employee Name',
            'paygroup_name'     => 'Pay Group',
            'remuneration_name' => 'Remuneration Basis',
            'formatted_amount'  => 'Base Amount',
            'start_date'        => 'Effective Start',
            'end_date'          => 'Effective End',
            'status_text'       => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'empcompensation',
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Employee Compensation',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive')),
                'start_date'  => array('type' => 'datepicker'),
                'end_date'    => array('type' => 'datepicker')
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'status_text') {
            $orderClause = "c.isactive $sort";
        } elseif ($by === 'employee_name') {
            $orderClause = array("employee_name $sort", "c.start_date DESC");
        } elseif ($by === 'paygroup_name') {
            $orderClause = "pg.name $sort";
        } elseif ($by === 'remuneration_name') {
            $orderClause = "rb.name $sort";
        } elseif ($by === 'formatted_amount') {
            $orderClause = "c.base_amount $sort";
        } elseif ($by === 'start_date') {
            $orderClause = "c.start_date $sort";
        } elseif ($by === 'end_date') {
            $orderClause = "c.end_date $sort";
        } else {
            $orderClause = "c.$by $sort";
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('c' => $this->_name),
                array(
                    'id'               => 'c.id',
                    'base_amount',
                    'formatted_amount' => new Zend_Db_Expr("CONCAT(COALESCE(cur.symbol, cur.code), ' ', FORMAT(c.base_amount, 2))"),
                    'start_date'       => new Zend_Db_Expr("DATE_FORMAT(c.start_date, '" . DATEFORMAT_MYSQL . "')"),
                    'end_date'         => new Zend_Db_Expr("CASE WHEN c.end_date IS NULL THEN 'Ongoing / Current' ELSE DATE_FORMAT(c.end_date, '" . DATEFORMAT_MYSQL . "') END"),
                    'status_text'      => new Zend_Db_Expr("CASE WHEN c.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            ->joinInner(
                array('e' => 'main_employee'),
                'c.employee_id = e.id',
                array('employee_name' => new Zend_Db_Expr("COALESCE(NULLIF(e.userfullname, ''), CONCAT(e.firstname, ' ', e.lastname))"))
            )
            ->joinInner(
                array('pg' => 'main_pay_group'),
                'c.pay_group_id = pg.id',
                array('paygroup_name' => new Zend_Db_Expr("CONCAT(pg.code, ' - ', pg.name)"))
            )
            ->joinInner(
                array('rb' => 'main_remunerationbasis'),
                'c.remunerationbasis_id = rb.id',
                array('remuneration_name' => 'rb.name')
            )
            ->joinInner(
                array('cur' => 'main_currency'),
                'c.currency_id = cur.id',
                array('currency_code' => 'cur.code')
            )
            ->where('c.isactive != ?', 5);

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order($orderClause)->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('c' => $this->_name),
                array('id' => 'c.id', 'label' => new Zend_Db_Expr("CONCAT(COALESCE(NULLIF(e.userfullname, ''), e.firstname), ' - ', cur.code, ' ', c.base_amount)"))
            )
            ->joinInner(array('e' => 'main_employee'), 'c.employee_id = e.id', array())
            ->joinInner(array('cur' => 'main_currency'), 'c.currency_id = cur.id', array())
            ->where('c.end_date IS NULL')
            ->order('e.userfullname ASC');

        if ($activeOnly) {
            $select->where('c.isactive = ?', 1);
        }

        return $this->getAdapter()->fetchPairs($select);
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
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        if (!empty($data['start_date'])) {
            $data['start_date'] = sapp_Global::change_date($data['start_date'], 'database');
        }
        if (!empty($data['end_date'])) {
            $data['end_date'] = sapp_Global::change_date($data['end_date'], 'database');
        } else {
            $data['end_date'] = null;
        }

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = $currentDate;
            $this->update($data, array('id = ?' => (int)$id));
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = $currentDate;
        $this->insert($data);
        return (int) $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL
     * ================================================================================= */

    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

        $data = array(
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );

        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

        $data = array(
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );

        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 5: BUSINESS RULES & INTEGRITY CHECKS
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim((string)$value))
            ->where('isactive != ?', 5);

        if (!empty($excludeId)) {
            $select->where('id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    /**
     * Enforces strict non-overlapping effective dates & single open compensation profile per employee.
     *
     * @param int $employeeId
     * @param string $newStartDate (Y-m-d)
     * @param string|null $newEndDate (Y-m-d or null)
     * @param int|null $excludeId
     * @return array|null Error details if overlap/collision exists, null if valid
     */
    public function checkDateOverlap(int $employeeId, string $newStartDate, ?string $newEndDate, ?int $excludeId = null): ?array
    {
        $select = $this->select()
            ->where('employee_id = ?', $employeeId)
            ->where('isactive = ?', 1)
            ->order('start_date DESC');

        if (!empty($excludeId)) {
            $select->where('id != ?', $excludeId);
        }

        $records = $this->fetchAll($select)->toArray();
        $newEndBound = !empty($newEndDate) ? $newEndDate : '9999-12-31';

        foreach ($records as $rec) {
            // Check 1: Cannot have two open (ongoing) compensation profiles simultaneously
            if ($rec['end_date'] === null && $newEndDate === null) {
                return array(
                    'code'    => 'OPEN_COMP_EXISTS',
                    'message' => "The employee already has an active ongoing compensation profile (ID #{$rec['id']}). Explicitly set an End Date on the existing profile before creating a new ongoing one."
                );
            }

            // Check 2: Interval Overlap [S1 <= E2 AND S2 <= E1]
            $recEndBound = !empty($rec['end_date']) ? $rec['end_date'] : '9999-12-31';
            if ($rec['start_date'] <= $newEndBound && $newStartDate <= $recEndBound) {
                $recStartFmt = sapp_Global::change_date($rec['start_date'], 'view');
                $recEndFmt   = !empty($rec['end_date']) ? sapp_Global::change_date($rec['end_date'], 'view') : 'Ongoing';
                return array(
                    'code'    => 'DATE_OVERLAP',
                    'message' => "Effective dates overlap with existing compensation profile #{$rec['id']} ({$recStartFmt} to {$recEndFmt})."
                );
            }
        }

        return null;
    }

    /**
     * Checks if Payroll Runs have already been Initiated/Locked/Closed for this employee's Pay Group
     * overlapping this compensation record's timeframe.
     *
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        $record = $this->getRecordById((int)$id);
        if (!$record) {
            return false;
        }

        $db = $this->getAdapter();
        $endBound = !empty($record['end_date']) ? $record['end_date'] : '9999-12-31';

        $runCheck = $db->select()
            ->from('main_payroll_run', array('id'))
            ->where('pay_group_id = ?', (int)$record['pay_group_id'])
            ->where('run_status != ?', 'Scheduled')
            ->where('cycle_end_date >= ?', $record['start_date'])
            ->where('cycle_start_date <= ?', $endBound)
            ->where('isactive != ?', 5);

        return (bool)$db->fetchRow($runCheck);
    }
}
