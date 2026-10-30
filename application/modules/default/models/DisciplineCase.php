<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Discipline Case Model
 * 
 * Manages the disciplinary transaction workflow and historical snapshotting.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Disciplinecase extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_discipline_case';
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 1: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');
        if ($activeStatus !== null) $select->where('isactive = ?', (int)$activeStatus);
        if (!empty($search_val) && !empty($search_str)) $select->where("$search_val LIKE ?", '%' . $search_str . '%');

        $select->order('createddate DESC');
        if ($limit !== null) $select->limit($limit, (int)$offset);

        return $this->fetchAll($select)->toArray();
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'status_text') {
                    $dbVal = ($val === 'Active') ? 1 : 0;
                    $searchQuery .= " dc.isactive = " . $dbVal . " AND ";
                } elseif ($key == 'employee_name') {
                    $searchQuery .= " emp.userfullname LIKE '%$val%' AND ";
                } elseif ($key == 'violation_name') {
                    $searchQuery .= " v.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " dc.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        $tableHeaders = array(
            'action'             => 'Action',
            'employee_name'      => 'Employee',
            'date_of_occurrence' => 'Incident Date',
            'violation_name'     => 'Violation',
            'case_status'        => 'Case Status',
            'status_text'        => 'Active'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'disciplinecase',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Disciplinary Cases',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive')),
                'case_status' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Initiated' => 'Initiated', 'Appealed' => 'Appealed', 'Closed' => 'Closed'))
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'dc.isactive';
        if ($by == 'employee_name') $by = 'emp.userfullname';
        if ($by == 'violation_name') $by = 'v.name';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('dc' => $this->_name), array(
                'id'                 => 'dc.id',
                'date_of_occurrence' => new Zend_Db_Expr("DATE_FORMAT(dc.date_of_occurrence, '" . DATEFORMAT_MYSQL . "')"),
                'case_status'        => 'dc.case_status',
                'status_text'        => new Zend_Db_Expr("CASE WHEN dc.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ))
            ->joinInner(array('emp' => 'main_employee'), 'dc.employee_id = emp.id', array('employee_name' => 'emp.userfullname'))
            ->joinInner(array('v' => 'main_discipline_violation'), 'dc.violation_id = v.id', array('violation_name' => 'v.name'))
            ->where('dc.isactive != 5');

        if (!empty($searchQuery)) $select->where($searchQuery);
        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /* =================================================================================
     * GROUP 2: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, array('id = ?' => (int)$id));
            return (int)$id; // Returning ID instead of 'update' for Log linking in controller
        }

        $data['case_raised_by'] = $userId;
        $data['createdby']      = $userId;
        $data['createddate']    = gmdate("Y-m-d H:i:s");

        // Historical Snapshotting Logic: Fetch current state of employee
        $snapshot = $this->_getEmployeeSnapshot((int)$data['employee_id']);
        $data['employee_bu_id']        = $snapshot['sbunit_id'];
        $data['employee_dept_id']      = $snapshot['department_id'];
        $data['employee_job_title_id'] = $snapshot['jobtitle_id'];
        $data['employee_rep_mang_id']  = $snapshot['reportto_id'];

        $this->insert($data);
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 3: LOG/TIMELINE MANAGEMENT
     * ================================================================================= */

    /**
     * Inserts a record into the Audit Log table.
     */
    public function addCaseLog($caseId, $description, $userId)
    {
        $db = $this->getAdapter();
        $db->insert('main_discipline_case_log', array(
            'case_id'       => (int)$caseId,
            'description'   => $description,
            'action_emp_id' => (int)$userId,
            'isactive'      => 1,
            'createdby'     => (int)$userId,
            'createddate'   => gmdate("Y-m-d H:i:s")
        ));
    }

    /**
     * Retrieves the chronologically ordered timeline for a specific case.
     */
    public function getCaseLogs($caseId)
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from(array('l' => 'main_discipline_case_log'), array(
                'description',
                'createddate' => new Zend_Db_Expr("DATE_FORMAT(l.createddate, '%b %d, %Y %H:%i')")
            ))
            ->joinLeft(array('u' => 'main_users'), 'l.action_emp_id = u.id', array())
            ->joinLeft(array('emp' => 'main_employee'), 'u.empid = emp.id', array('userfullname' => new Zend_Db_Expr("COALESCE(emp.userfullname, 'System')")))
            ->where('l.case_id = ?', (int)$caseId)
            ->where('l.isactive = 1')
            ->order('l.createddate DESC'); // Newest first

        return $db->fetchAll($select);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $data = array('isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $data = array('isactive' => (int)$status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function checkInUse($id)
    {
        return false;
    } // End of the line transaction table

    /* =================================================================================
     * GROUP 5: INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Fetches current Org details for an employee to freeze in the case snapshot.
     */
    private function _getEmployeeSnapshot($employeeId)
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from('main_empposition', array('sbunit_id', 'department_id', 'jobtitle_id', 'reportto_id'))
            ->where('employee_id = ?', (int)$employeeId)
            ->where('isactive = ?', 'Y')
            ->order('datefrom DESC')
            ->limit(1);

        $row = $db->fetchRow($select);
        return $row ? $row : array('sbunit_id' => null, 'department_id' => null, 'jobtitle_id' => null, 'reportto_id' => null);
    }
}
