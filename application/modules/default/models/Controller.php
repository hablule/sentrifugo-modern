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
class Default_Model_Controller extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_controller';
    protected $_primary = 'id';

    public function saveRecord($data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        if ($id) {
            $data['modifiedby'] = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            return $this->update($data, array('id = ?' => (int)$id));
        }
        $data['createdby'] = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        return $this->insert($data);
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

    
    public function deleteRecord($id)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array('isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }
    
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'controller'));
        if ($activeOnly) $select->where("isactive = 1");
        $results = $this->fetchAll($select);
        $data = array();
        foreach ($results as $row) { $data[$row->id] = $row->controller; }
        return $data;
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (!empty($searchValues)) {
                foreach ($searchValues as $key => $val) { $searchQuery .= " c." . $key . " LIKE '%" . $val . "%' AND "; }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }

        $tableHeaders = array('id' => 'ID', 'module_name' => 'Module', 'controller' => 'Controller', 'status_text' => 'Status');
        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);
        
        return array(
            'sort' => $sort, 'by' => $by, 'pageNo' => $pageNo, 'perPage' => $perPage,
            'tablecontent' => $tableContent, 'objectname' => 'permissioncontroller', 'tableheader'  => $tableHeaders,
            'extra' => array(), 'jsGridFnName' => 'getAjaxgridData', 'jsFillFnName' => '', 'searchArray' => array(),
            'menuName' => 'Controllers', 'call' => $call, 'dashboardcall' => $dashboardcall, 'search_filters' => array()
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('c' => $this->_name), array('*', 'status_text' => new Zend_Db_Expr("CASE WHEN c.isactive = 1 THEN 'Active' ELSE 'Inactive' END")))
            ->joinLeft(array('m' => 'main_module'), 'c.module_id = m.id', array('module_name' => 'm.name'))
            ->where('c.isactive != 5');
        
        if ($searchQuery) $select->where($searchQuery);

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function checkDuplicateController($value, $excludeId = null)
    {
        $select = $this->select()->where('controller = ?', trim($value))->where('isactive != 5');
        if ($excludeId) $select->where('id != ?', (int)$excludeId);
        return $this->fetchRow($select);
    }

    public function getRecordsByModuleId($moduleId, $activeOnly = true)
    {
        $select = $this->select()->where('module_id = ?', (int)$moduleId)->where('isactive != 5');
        if ($activeOnly) $select->where('isactive = 1');
        return $this->fetchAll($select)->toArray();
    }
    
    public function getRecordByModuleIdAndController($moduleId, $controllerName)
    {
        $select = $this->select()
            ->where('module_id = ?', (int)$moduleId)
            ->where('controller = ?', $controllerName)
            ->where('isactive != 5');
            
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }
}