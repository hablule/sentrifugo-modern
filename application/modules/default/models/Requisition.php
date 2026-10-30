<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Requisition Model (The Master Campaign)
 *
 * Manages the approval workflow for headcount requests.
 * Complies with SSOT v6.7.7.
 */
class Default_Model_Requisition extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_requisition';
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $statusFilter = null)
    {
        $searchQuery = '';
        $searchArray = [];

        if (!empty($searchData) && $searchData !== 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key === 'manager_name') {
                        $searchQuery .= " (emp.userfullname LIKE '%" . trim((string)$val) . "%' OR emp.firstname LIKE '%" . trim((string)$val) . "%') AND ";
                    } elseif ($key === 'status') {
                        $searchQuery .= " r.status = '" . trim((string)$val) . "' AND ";
                    } else {
                        $searchQuery .= " r." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // Apply Hard Filter from Tabs
        if ($statusFilter === 'action_required') {
            $searchQuery .= ($searchQuery ? " AND " : "") . " r.status IN ('Draft', 'Pending Approval') ";
        } elseif ($statusFilter === 'active_pipelines') {
            $searchQuery .= ($searchQuery ? " AND " : "") . " r.status = 'Approved' ";
        } elseif ($statusFilter === 'concluded') {
            $searchQuery .= ($searchQuery ? " AND " : "") . " r.status IN ('Closed', 'Rejected') ";
        }

        $tableHeaders = [
            'action'              => 'Action',
            'code'                => 'Requisition Code',
            'manager_name'        => 'Hiring Manager',
            'requested_fte'       => 'Target FTE',
            'filled_fte'          => 'Filled FTE',
            'status'              => 'Campaign Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'requisition',
            'extra'          => [],
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Requisitions',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => []
        ];
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'manager_name') $by = 'employee_name';
        elseif ($by === 'filled_fte') $by = 'filled_fte';

        $db = $this->getAdapter();

        // Subquery to calculate total filled FTE for this requisition
        $filledSubquery = $db->select()
            ->from('map_requisition_position', ['requisition_id', 'total_filled' => new Zend_Db_Expr('SUM(target_fte)')])
            ->where('status = ?', 'Filled')
            ->where('isactive != ?', 5)
            ->group('requisition_id');

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['r' => $this->_name],
                [
                    'id' => 'r.id',
                    'code',
                    'requested_fte',
                    'status',
                    'filled_fte' => new Zend_Db_Expr('COALESCE(filled_sub.total_filled, 0.00)')
                ]
            )
            ->joinLeft(
                ['emp' => 'main_employee'],
                'r.requested_by_employee_id = emp.id',
                ['manager_name' => new Zend_Db_Expr("COALESCE(NULLIF(emp.userfullname, ''), CONCAT(emp.firstname, ' ', emp.lastname), 'Unassigned')")]
            )
            ->joinLeft(
                ['filled_sub' => clone $filledSubquery], // Subquery alias
                'r.id = filled_sub.requisition_id',
                []
            )
            ->where('r.isactive != ?', 5);

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != ?', 5);
        $row = $this->fetchRow($select);
        return $row ? $row->toArray() : null;
    }

    /**
     * Get aggregate counts for the Dashboard Summary Tabs
     */
    public function getTabCounts()
    {
        $db = $this->getAdapter();

        $sql = "SELECT 
                    SUM(CASE WHEN status IN ('Draft', 'Pending Approval') THEN 1 ELSE 0 END) as action_required,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as active_pipelines,
                    SUM(CASE WHEN status IN ('Closed', 'Rejected') THEN 1 ELSE 0 END) as concluded
                FROM {$this->_name} 
                WHERE isactive != 5";

        $result = $db->fetchRow($sql);

        return [
            'action_required'  => (int)$result['action_required'],
            'active_pipelines' => (int)$result['active_pipelines'],
            'concluded'        => (int)$result['concluded']
        ];
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

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
     * GROUP 4: RECORD REMOVAL
     * ================================================================================= */

    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;
        $data = ['isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s")];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS, & INTERNAL HELPERS
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()->where("$field = ?", trim((string)$value))->where('isactive != ?', 5);
        if (!empty($excludeId)) $select->where('id != ?', (int)$excludeId);
        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        $record = $this->getRecordById((int)$id);
        if (!$record) return false;

        // Cannot delete if it has moved past Draft
        if ($record['status'] !== 'Draft') {
            return true;
        }

        return false;
    }
}
