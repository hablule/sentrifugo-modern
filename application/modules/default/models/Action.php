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
 * Sentrifugo - Open Source HRMS
 *
 * @category   Sentrifugo
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @modified  Sentrifugo v3.2
 * @date	  June 2024
 */
class Default_Model_Action extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_action';
    protected $_primary = 'id';

    /* --------------------------------------------------------------
     * 1. Standard SSOT Methods (CRUD & Lists)
     * -------------------------------------------------------------- */

    public function saveRecord($data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        
        if ($id) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, array('id = ?' => (int)$id));
            return 'update';
        }
        
        $data['createdby']   = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        $this->insert($data);
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /**
     * Standard retrieval. 
     * $activeStatus filters the standard `isactive` column (1 or 0), NOT the scanner status.
     * Always excludes 'Obsolete' scanner status by default.
     */
    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()
            ->where('isactive != 5')
            ->where("status != 'Obsolete'"); // Base rule for this specific table
        
        if ($activeStatus !== null) {
            $select->where('isactive = ?', (int)$activeStatus);
        }
        
        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        }

        if ($limit !== null) {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
        
        if ($activeOnly) {
            $select->where("isactive = 1");
        }
        $select->where("status != 'Obsolete'");
        
        return $this->getAdapter()->fetchPairs($select);
    }

    public function deleteRecord($id)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array(
            'isactive'     => 5, 
            'modifiedby'   => $userId, 
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array(
            'isactive'     => (int)$status, 
            'modifiedby'   => $userId, 
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* --------------------------------------------------------------
     * 2. Grid Logic (SSOT Two-Method Pattern)
     * -------------------------------------------------------------- */

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (!empty($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key == 'status_text') {
                        $dbVal = ($val == 'Active') ? 1 : 0;
                        $searchQuery .= " a.isactive = " . $dbVal . " AND ";
                    } elseif ($key == 'scanner_status') {
                        // Explicit filter for the ENUM status column
                        $searchQuery .= " a.status = '" . $val . "' AND ";
                    } else {
                        $searchQuery .= " a." . $key . " LIKE '%" . $val . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }

        $tableHeaders = array(
            'action'         => 'Action',
            'name'           => 'Method Name',
            'scanner_status' => 'Scanner Status',
            'status_text'    => 'Active Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort, 'by' => $by, 'pageNo' => $pageNo, 'perPage' => $perPage,
            'tablecontent'   => $tableContent, 'objectname' => 'permissionaction', 'tableheader' => $tableHeaders,
            'extra'          => array(), 'jsGridFnName' => 'getAjaxgridData', 'jsFillFnName' => '',
            'searchArray'    => $searchArray, 'menuName' => 'Actions', 'call' => $call, 
            'dashboardcall'  => $dashboardcall, 
            'search_filters' => array(
                'status_text'    => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive')),
                'scanner_status' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Mapped (Active)', 'New' => 'New', 'Obsolete' => 'Obsolete'))
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'isactive';
        if ($by == 'scanner_status') $by = 'status';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('a' => $this->_name), 
                array(
                    '*', 
                    'status_text'    => new Zend_Db_Expr("CASE WHEN a.isactive = 1 THEN 'Active' ELSE 'Inactive' END"),
                    'scanner_status' => 'a.status'
                )
            )
            ->where("a.isactive != 5");

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")
               ->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /* --------------------------------------------------------------
     * 3. Validation & Specific Logic
     * -------------------------------------------------------------- */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim($value))
            ->where("isactive != 5");

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    public function getRecordsByControllerId($controllerId, $activeOnly = true)
    {
        $select = $this->select()
            ->where('controller_id = ?', (int)$controllerId)
            ->where("isactive != 5");
            
        if ($activeOnly) {
            $select->where("isactive = 1");
            $select->where("status != 'Obsolete'");
        }
        return $this->fetchAll($select)->toArray();
    }

    /* --- Scanner & Mapper Specific Logic --- */

    /**
     * Dedicated fetcher for the Permission Mapper UI.
     * Retrieves all logically active actions that are either 'New' or 'Active' (Mapped).
     */
    public function getActionsForMapping()
    {
        $select = $this->select()
            ->where('isactive = 1')
            ->where("status IN ('New', 'Active')")
            ->order(array('controller_id ASC', 'name ASC'));
            
        return $this->fetchAll($select)->toArray();
    }

    public function getActionNamesByControllerId($controllerId)
    {
        $select = $this->select()
            ->from($this->_name, array('name'))
            ->where('controller_id = ?', (int)$controllerId)
            ->where("status != 'Obsolete'")
            ->where("isactive != 5");

        return $this->getAdapter()->fetchCol($select);
    }

    public function markAsObsolete(array $actionNames, $controllerId)
    {
        if (empty($actionNames)) return 0;
        
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array(
            'status'       => 'Obsolete',
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        $where = array(
            'controller_id = ?' => (int)$controllerId,
            'name IN (?)'       => $actionNames
        );
        return $this->update($data, $where);
    }
}