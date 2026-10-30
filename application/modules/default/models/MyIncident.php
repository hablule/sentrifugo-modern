<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * My Incident Model
 * 
 * Read/Update model for the Employee's view of their own disciplinary cases.
 * Strictly adheres to SSOT v6.2.
 */
class Default_Model_Myincident extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_discipline_case';
    protected $_primary = 'id';

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'violation_name') {
                    $searchQuery .= " v.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " dc.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // SSOT IV.3: Strict headers. 'Action' must be first.
        $tableHeaders = array(
            'action'             => 'Action',
            'date_of_occurrence' => 'Incident Date',
            'violation_name'     => 'Violation',
            'case_status'        => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'myincident',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'My Incidents',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'case_status' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Initiated' => 'Initiated', 'Appealed' => 'Appealed', 'Closed' => 'Closed'))
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'violation_name') $by = 'v.name';

        // Security: Get current logged-in user ID
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id;

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('dc' => $this->_name), array(
                'id'                 => 'dc.id',
                'date_of_occurrence' => new Zend_Db_Expr("DATE_FORMAT(dc.date_of_occurrence, '" . DATEFORMAT_MYSQL . "')"),
                'case_status'        => 'dc.case_status'
            ))
            ->joinInner(array('v' => 'main_discipline_violation'), 'dc.violation_id = v.id', array('violation_name' => 'v.name'))
            ->where('dc.isactive != 5')
            ->where('dc.employee_id = ?', (int)$userId); // STRICT FILTER: Only show cases belonging to this user

        if (!empty($searchQuery)) $select->where($searchQuery);
        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getRecordById($id)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id;

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('dc' => $this->_name))
            ->joinInner(array('v' => 'main_discipline_violation'), 'dc.violation_id = v.id', array('violation_name' => 'v.name'))
            ->joinLeft(array('a' => 'main_discipline_action'), 'dc.action_id = a.id', array('action_name' => 'a.name'))
            ->where('dc.id = ?', (int)$id)
            ->where('dc.employee_id = ?', (int)$userId) // Security Enforcement
            ->where('dc.isactive != 5');

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    public function saveRecord(array $data, $id)
    {
        // Employees can only update existing records (submitting appeals)
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id;
        $data['modifiedby']   = $userId;
        $data['modifieddate'] = gmdate("Y-m-d H:i:s");

        $this->update($data, array('id = ?' => (int)$id, 'employee_id = ?' => (int)$userId));
        return $id;
    }

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
}
