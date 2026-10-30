<?php

/**
 * Employee Assignment Ledger Model
 *
 * Manages the temporal, effective-dated ledger of employee seat assignments.
 * Enforces the Highlander Principle (Primary role limits) and FTE concurrency capacity.
 * Complies with SSOT v6.7.7 (Singular Naming, Zend_Db_Select, Structured Method Groups).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Empassignment extends Zend_Db_Table_Abstract
{
    /* --------------------------------------------------------------
     * Attributes (Visibility -> Alphabetical)
     * -------------------------------------------------------------- */

    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_emp_assignment';

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

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if (!empty($searchData) && $searchData !== 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key === 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " a.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'employee_name') {
                        $searchQuery .= " (e.userfullname LIKE '%" . trim((string)$val) . "%' OR e.firstname LIKE '%" . trim((string)$val) . "%' OR e.lastname LIKE '%" . trim((string)$val) . "%') AND ";
                    } elseif ($key === 'position_title') {
                        $searchQuery .= " (p.code LIKE '%" . trim((string)$val) . "%' OR COALESCE(p.name, jt.name) LIKE '%" . trim((string)$val) . "%') AND ";
                    } elseif ($key === 'start_date' || $key === 'end_date') {
                        $searchQuery .= " a." . $key . " = '" . sapp_Global::change_date(urldecode($val), 'database') . "' AND ";
                    } else {
                        $searchQuery .= " a." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        $tableHeaders = [
            'action'               => 'Action',
            'employee_name'        => 'Employee Name',
            'position_title'       => 'Position (Seat)',
            'assignment_type'      => 'Type',
            'employment_type_name' => 'Contract',
            'allocated_fte'        => 'FTE',
            'start_date'           => 'Effective Start',
            'end_date'             => 'Effective End',
            'reason_name'          => 'Reason',
            'status_text'          => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'empassignment',
            'extra'          => [],
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Employee Assignments',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text'     => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']],
                'assignment_type' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Primary' => 'Primary', 'Secondary' => 'Secondary', 'Acting' => 'Acting', 'Secondment' => 'Secondment']],
                'start_date'      => ['type' => 'datepicker'],
                'end_date'        => ['type' => 'datepicker']
            ]
        ];
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'status_text') {
            $by = 'a.isactive';
        } elseif ($by === 'employee_name') {
            $by = 'employee_name';
        } elseif ($by === 'position_title') {
            $by = 'position_title';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['a' => $this->_name],
                [
                    'id' => 'a.id',
                    'assignment_type',
                    'allocated_fte',
                    'start_date'   => new Zend_Db_Expr("DATE_FORMAT(a.start_date, '" . DATEFORMAT_MYSQL . "')"),
                    'end_date'     => new Zend_Db_Expr("CASE WHEN a.end_date IS NULL THEN 'Ongoing / Current' ELSE DATE_FORMAT(a.end_date, '" . DATEFORMAT_MYSQL . "') END"),
                    'status_text'  => new Zend_Db_Expr("CASE WHEN a.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                ]
            )
            ->joinInner(
                ['e' => 'main_employee'],
                'a.employee_id = e.id',
                ['employee_name' => new Zend_Db_Expr("COALESCE(NULLIF(e.userfullname, ''), CONCAT(e.firstname, ' ', e.lastname))")]
            )
            ->joinInner(
                ['p' => 'main_position'],
                'a.position_id = p.id',
                ['position_code' => 'p.code']
            )
            ->joinInner(
                ['jt' => 'main_jobtitle'],
                'p.jobtitle_id = jt.id',
                ['position_title' => new Zend_Db_Expr("CONCAT(p.code, ' - ', COALESCE(p.name, jt.name))")]
            )
            ->joinInner(
                ['et' => 'main_employment_type'],
                'a.employment_type_id = et.id',
                ['employment_type_name' => 'et.name']
            )
            ->joinInner(
                ['ar' => 'main_assignment_reason'],
                'a.assignment_reason_id = ar.id',
                ['reason_name' => 'ar.name']
            )
            ->where('a.isactive != ?', 5);

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['a' => $this->_name],
                ['id' => 'a.id', 'label' => new Zend_Db_Expr("CONCAT(e.userfullname, ' (', a.assignment_type, ')')")]
            )
            ->joinInner(['e' => 'main_employee'], 'a.employee_id = e.id', [])
            ->where('a.end_date IS NULL') // Only current assignments
            ->order('e.userfullname ASC');

        if ($activeOnly) {
            $select->where('a.isactive = ?', 1);
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

    /**
     * Timeline Provider: Fetches full chronological assignment history for an employee profile.
     * 
     * @param int $employeeId
     * @return array
     */
    public function getAssignmentsByEmployee(int $employeeId): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['a' => $this->_name])
            ->joinInner(['p' => 'main_position'], 'a.position_id = p.id', ['position_code' => 'p.code', 'position_custom_name' => 'p.name'])
            ->joinInner(['jt' => 'main_jobtitle'], 'p.jobtitle_id = jt.id', ['jobtitle_name' => 'jt.name'])
            ->joinInner(['et' => 'main_employment_type'], 'a.employment_type_id = et.id', ['employment_type_name' => 'et.name'])
            ->joinInner(['ar' => 'main_assignment_reason'], 'a.assignment_reason_id = ar.id', ['reason_name' => 'ar.name'])
            ->where('a.employee_id = ?', $employeeId)
            ->where('a.isactive != ?', 5)
            ->order('a.start_date DESC');

        return $this->fetchAll($select)->toArray();
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        // Format dates safely
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
            $this->update($data, ['id = ?' => (int)$id]);
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

        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];

        return $this->update($data, ['id = ?' => (int)$id]);
    }

    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

        $data = [
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];

        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 5: BUSINESS RULES & CONCURRENCY CONTROL
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

    public function checkInUse($id)
    {
        // Future check: prevent deleting assignment if payroll was processed against it
        return false;
    }

    /**
     * BUSINESS RULE 1: The Highlander Principle (Strict Primary Position Enforcement)
     * 
     * Verifies that:
     * 1. No other 'Primary' assignment is currently open (end_date IS NULL).
     * 2. The new start date strictly begins AFTER the end date of the previous Primary assignment.
     *
     * @param int $employeeId
     * @param string $newStartDate (Y-m-d)
     * @param int|null $excludeId
     * @return array|null Returns error message array if collision detected, null if safe
     */
    public function checkPrimaryCollision(int $employeeId, string $newStartDate, ?int $excludeId = null): ?array
    {
        $select = $this->select()
            ->where('employee_id = ?', $employeeId)
            ->where("assignment_type = 'Primary'")
            ->where('isactive = ?', 1)
            ->order('start_date DESC');

        if (!empty($excludeId)) {
            $select->where('id != ?', $excludeId);
        }

        $records = $this->fetchAll($select)->toArray();

        foreach ($records as $rec) {
            // Check 1: Open previous primary assignment
            if ($rec['end_date'] === null) {
                return [
                    'code'    => 'OPEN_PRIMARY_EXISTS',
                    'message' => "The employee already has an active Primary assignment (ID #{$rec['id']}). You must explicitly set an End Date on the previous assignment before creating a new Primary role."
                ];
            }

            // Check 2: Date boundary order
            if ($rec['end_date'] >= $newStartDate) {
                $endDateFmt = sapp_Global::change_date($rec['end_date'], 'view');
                return [
                    'code'    => 'START_DATE_COLLISION',
                    'message' => "The new Primary assignment start date must be strictly after the previous assignment's end date ({$endDateFmt})."
                ];
            }
        }

        return null; // Safe
    }

    /**
     * BUSINESS RULE 2: FTE Concurrency Control
     * 
     * Verifies that the new allocated FTE does not exceed the position's max_fte.
     *
     * @param int $positionId
     * @param float $newAllocatedFte
     * @param int|null $excludeId
     * @return array|null Returns error array if over-allocated, null if safe
     */
    public function checkFteCapacity(int $positionId, float $newAllocatedFte, ?int $excludeId = null): ?array
    {
        $db = $this->getAdapter();

        // 1. Fetch Position Max FTE
        $posRow = $db->fetchRow($db->select()->from('main_position', ['max_fte', 'code'])->where('id = ?', $positionId));
        if (!$posRow) {
            return ['code' => 'INVALID_POSITION', 'message' => 'Position does not exist.'];
        }

        $maxFte = (float)$posRow['max_fte'];

        // 2. Sum currently active allocated FTEs for this seat
        $sumSelect = $db->select()
            ->from($this->_name, ['total_allocated' => new Zend_Db_Expr('COALESCE(SUM(allocated_fte), 0)')])
            ->where('position_id = ?', $positionId)
            ->where('end_date IS NULL') // Currently active occupants
            ->where('isactive = ?', 1);

        if (!empty($excludeId)) {
            $sumSelect->where('id != ?', $excludeId);
        }

        $currentTotal = (float)$db->fetchOne($sumSelect);
        $projectedTotal = $currentTotal + $newAllocatedFte;

        if ($projectedTotal > $maxFte) {
            return [
                'code'    => 'FTE_EXCEEDED',
                'message' => "Position {$posRow['code']} capacity exceeded. Max FTE is {$maxFte}, currently allocated is {$currentTotal}. Projected total ({$projectedTotal}) exceeds seat capacity."
            ];
        }

        return null; // Safe
    }
}
