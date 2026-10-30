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
 * Position Model (The Structural Seat)
 *
 * Manages the discrete organizational seats, their FTE values, and polymorphic organization owners.
 * Enforces the Single Org Owner and the Job Title Freeze rule.
 * Complies with SSOT v6.7.7.
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */

class Default_Model_Position extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_position';
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != ?', 5);
        if ($activeStatus !== null) $select->where('isactive = ?', (int)$activeStatus);
        if (!empty($search_val) && !empty($search_str)) $select->where("$search_val LIKE ?", '%' . trim((string)$search_str) . '%');
        if ($limit !== null) $select->limit((int)$limit, (int)$offset);
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
                        $searchQuery .= " p.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'display_name') {
                        $searchQuery .= " COALESCE(p.name, jt.name) LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'jobtitle_name') {
                        $searchQuery .= " jt.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'reports_to_name') {
                        $searchQuery .= " COALESCE(pp.name, pjt.name) LIKE '%" . trim((string)$val) . "%' AND ";
                    } else {
                        $searchQuery .= " p." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        $tableHeaders = [
            'action'          => 'Action',
            'code'            => 'Position Code',
            'display_name'    => 'Position Name',
            'jobtitle_name'   => 'Job Title (Blueprint)',
            'reports_to_name' => 'Reports To',
            'org_owner_name'  => 'Organizational Unit',
            'status'          => 'Status',
            'max_fte'         => 'Max FTE'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'position',
            'extra'          => [],
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Positions',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Proposed' => 'Proposed', 'Active' => 'Active', 'Frozen' => 'Frozen', 'Eliminated' => 'Eliminated']]
            ]
        ];
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'display_name') $by = 'COALESCE(p.name, jt.name)';
        elseif ($by === 'jobtitle_name') $by = 'jt.name';
        elseif ($by === 'reports_to_name') $by = 'COALESCE(pp.name, pjt.name)';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['p' => $this->_name],
                [
                    'id' => 'p.id',
                    'code',
                    'status',
                    'max_fte',
                    'display_name' => new Zend_Db_Expr("COALESCE(p.name, jt.name)"),
                    'status_text' => new Zend_Db_Expr("CASE WHEN p.isactive = 1 THEN 'Active' ELSE 'Inactive' END"),
                    'org_owner_name' => new Zend_Db_Expr("COALESCE(comp.name, bu.name, divn.name, dept.name, sec.name, tm.name, 'Unassigned')")
                ]
            )
            // Blueprint Join
            ->joinInner(['jt' => 'main_jobtitle'], 'p.jobtitle_id = jt.id', ['jobtitle_name' => 'jt.name'])

            // Parent Position (Reports To) Join & its Job Title for COALESCE fallback
            ->joinLeft(['pp' => 'main_position'], 'p.reports_to_position_id = pp.id', ['reports_to_name' => new Zend_Db_Expr("CONCAT(pp.code, ' - ', COALESCE(pp.name, pjt.name))")])
            ->joinLeft(['pjt' => 'main_jobtitle'], 'pp.jobtitle_id = pjt.id', [])

            // Org Unit Joins
            ->joinLeft(['comp' => 'main_company'], 'p.company_id = comp.id', [])
            ->joinLeft(['bu' => 'main_businessunit'], 'p.businessunit_id = bu.id', [])
            ->joinLeft(['divn' => 'main_division'], 'p.division_id = divn.id', [])
            ->joinLeft(['dept' => 'main_department'], 'p.department_id = dept.id', [])
            ->joinLeft(['sec' => 'main_section'], 'p.section_id = sec.id', [])
            ->joinLeft(['tm' => 'main_team'], 'p.team_id = tm.id', [])
            ->where('p.isactive != ?', 5);

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
                ['p' => $this->_name],
                ['id' => 'p.id', 'label' => new Zend_Db_Expr("CONCAT(p.code, ' - ', COALESCE(p.name, jt.name))")]
            )
            ->joinInner(['jt' => 'main_jobtitle'], 'p.jobtitle_id = jt.id', [])
            ->order('p.code ASC');

        if ($activeOnly) {
            $select->where('p.isactive = ?', 1);
            $select->where("p.status = 'Active'");
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != ?', 5);
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

        if (!empty($id)) {
            if ($this->isUtilized((int)$id)) {
                unset($data['jobtitle_id']); // Freeze rule
            }
            $data['modifiedby'] = $userId;
            $data['modifieddate'] = $currentDate;
            $this->update($data, ['id = ?' => (int)$id]);
            return 'update';
        }

        $data['createdby'] = $userId;
        $data['createddate'] = $currentDate;
        $this->insert($data);
        return (int) $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;
        $data = ['isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s")];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;
        $data = ['isactive' => (int)$status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s")];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()->where("$field = ?", trim((string)$value))->where('isactive != ?', 5);
        if (!empty($excludeId)) $select->where('id != ?', (int)$excludeId);
        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        return $this->isUtilized((int)$id);
    }

    public function isUtilized(int $positionId): bool
    {
        $db = $this->getAdapter();
        // Check assignments
        try {
            $modernCheck = $db->select()->from('main_emp_assignment', ['id'])->where('position_id = ?', $positionId);
            if ($db->fetchRow($modernCheck)) return true;
        } catch (\Throwable $e) {
        }

        // Check if other positions report to this one
        $childCheck = $db->select()->from('main_position', ['id'])->where('reports_to_position_id = ?', $positionId)->where('isactive != ?', 5);
        if ($db->fetchRow($childCheck)) return true;

        return false;
    }
}
