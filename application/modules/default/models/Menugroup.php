<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
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
class Default_Model_Menugroup extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_menugroup';
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

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');

        if ($activeStatus !== null) {
            $select->where('isactive = ?', $activeStatus);
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

        $results = $this->fetchAll($select);
        $data = array();
        foreach ($results as $row) {
            $data[$row->id] = $row->name;
        }
        return $data;
    }

    public function deleteRecord($id)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array('isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array('isactive' => $status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /**
     * Retrieves all core menu groups used for the top-level radial UI dashboard.
     * Excludes system-hidden utilities (like 'SYS_HIDDEN' or isactive = 0).
     * 
     * @return array An array of associative arrays containing group details.
     */
    public function getTopLevelMenus()
    {
        $select = $this->select()
            ->from(
                $this->_name,
                array(
                    'id',
                    'name',
                    'code',
                    'iconpath',
                    'backgroundcolor'
                )
            )
            ->where('isactive = 1')
            // Exclude the catch-all group for hidden utilities we created earlier
            ->where("code != 'SYS_HIDDEN'")
            ->order('name ASC'); // You can add a 'displayorder' column to this table later if you want manual sorting

        return $this->fetchAll($select)->toArray();
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
                        $dbVal = ($val == 'Active') ? 1 : (($val == 'Inactive') ? 0 : $val);
                        $searchQuery .= " mg.isactive = " . $dbVal . " AND ";
                    } else {
                        $searchQuery .= " mg." . $key . " LIKE '%" . $val . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }

        $tableHeaders = array(
            'action'          => 'Action',
            'name'            => 'Group Name',
            'code'            => 'Code',
            'backgroundcolor' => 'Theme Color',
            'status_text'     => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent'   => $tableContent,
            'objectname' => 'menugroup',
            'tableheader' => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray'    => $searchArray,
            'menuName' => 'Menu Groups',
            'call' => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'isactive';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('mg' => $this->_name),
                array('*', 'status_text' => new Zend_Db_Expr("CASE WHEN mg.isactive = 1 THEN 'Active' ELSE 'Inactive' END"))
            )
            ->where("mg.isactive != 5");

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    /* --------------------------------------------------------------
     * 3. Validation & Specific Logic
     * -------------------------------------------------------------- */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()->where("$field = ?", trim($value))->where("isactive != 5");
        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }
}
