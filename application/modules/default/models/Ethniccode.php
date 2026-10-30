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
 * Ethnic Code Model
 * 
 * Manages ethnic code lookup data used in employee profiles.
 * Strictly adheres to SSOT v5.1: Zend_Db_Select, dependency checks, 
 * and standardized grid formatting.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Ethniccode extends Zend_Db_Table_Abstract
{
    /** @var string Table name */
    protected $_name = 'main_ethniccode';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 1: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Standard retrieval of all records.
     */
    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');

        if ($activeStatus !== null) {
            $select->where('isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        }

        $select->order('ethnicname ASC');

        if ($limit !== null) {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * SSOT Standard Grid Configuration.
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'status_text') {
                    if ($val === 'Active') {
                        $searchQuery .= " e.isactive = 1 AND ";
                    } elseif ($val === 'Inactive') {
                        $searchQuery .= " e.isactive = 0 AND ";
                    }
                } else {
                    $searchQuery .= " e.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // Must match exact column names from the Database
        $tableHeaders = [
            'action'      => 'Action',
            'ethnicname'  => 'Ethnic Code',
            'ethniccode'  => 'Ethnic Short Code',
            'description' => 'Description',
            'status_text' => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tableContent,
            'objectname' => 'ethniccode',
            'tableheader' => $tableHeaders,
            'extra' => [],
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'menuName' => 'Ethnic Codes',
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
            ]
        ];
    }

    /**
     * SSOT Standard Grid Data Fetcher.
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'e.isactive';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => $this->_name], [
                '*',
                'status_text' => new Zend_Db_Expr("CASE WHEN e.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->where('e.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    /**
     * Standard Pairs Retrieval (Used for Dropdowns in Popups).
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'ethnicname'])->order('ethnicname ASC');
        if ($activeOnly) {
            $select->where("isactive = 1");
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Standard ID Retrieval.
     */
    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /* =================================================================================
     * GROUP 2: PERSISTENCE
     * ================================================================================= */

    /**
     * Standard Save/Update.
     */
    public function saveRecord($data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if ($id) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, ['id = ?' => (int)$id]);
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        $this->insert($data);
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 3: REMOVAL
     * ================================================================================= */

    /**
     * Soft delete standard execution. Checks dependencies first per SSOT IV.
     * 
     * @param int $id
     * @return int
     * @throws Exception if record is in use
     */
    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this Ethnic Code as it is currently assigned to an employee profile.");
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /**
     * Standard active status toggle.
     */
    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 4: INTEGRITY HELPERS
     * ================================================================================= */

    /**
     * Standard duplicate field checker.
     */
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

    /**
     * Dependency Checker (SSOT IV Data Integrity)
     * Verifies if the ethnic code is assigned to an employee in `main_employees`.
     * Updated to reflect schema changes where ethniccode_id was moved to core employee table.
     * 
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from('main_employees', 'id')
            ->where('ethniccode_id = ?', (int)$id) // Updated column name
            ->where('isactive != 5') // Only care if active/inactive employees are using it
            ->limit(1);
            
        return (bool)$db->fetchOne($select);
    }
}
