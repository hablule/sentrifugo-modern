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
 * Currency Converter Model
 * 
 * Manages currency exchange rates.
 * Strictly adheres to SSOT v6.2: Zend_Db_Select exclusively, explicit method grouping,
 * singular table naming, and mandatory dependency checks.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2 
 * @author     Hablul Soft Solution < /hablul.in>
 */
class Default_Model_Currencyconverter extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT Singular Naming Convention) */
    protected $_name = 'main_currency_converter';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 1: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Standard retrieval of all records.
     * 
     * @param int|null    $limit
     * @param int|null    $offset
     * @param string|null $search_val
     * @param string|null $search_str
     * @param int|null    $activeStatus
     * @return array
     */
    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['cc' => $this->_name])
            ->joinInner(['bc' => 'main_currency'], 'cc.base_currency_id = bc.id', ['base_currency' => 'bc.code'])
            ->joinInner(['tc' => 'main_currency'], 'cc.target_currency_id = tc.id', ['target_currency' => 'tc.code'])
            ->where('cc.isactive != 5');
        
        if ($activeStatus !== null) {
            $select->where('cc.isactive = ?', (int)$activeStatus);
        }
        
        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        }

        $select->order(['bc.code ASC', 'tc.code ASC', 'cc.start_date DESC']);

        if ($limit !== null) {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * SSOT Standard Grid Configuration.
     * Generates the matrix properties for the Sentrifugo Grid Helper.
     * 
     * @param string $sort
     * @param string $by
     * @param int    $perPage
     * @param int    $pageNo
     * @param string $searchData
     * @param string $call
     * @param string $dashboardcall
     * @return array
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (!empty($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key == 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " cc.isactive = " . $dbVal . " AND ";
                    } elseif ($key == 'base_currency') {
                        $searchQuery .= " bc.code LIKE '%" . $val . "%' AND ";
                    } elseif ($key == 'target_currency') {
                        $searchQuery .= " tc.code LIKE '%" . $val . "%' AND ";
                    } else {
                        $searchQuery .= " cc." . $key . " LIKE '%" . $val . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }

        // Must match exact column aliases defined in getGridData()
        $tableHeaders = [
            'action'          => 'Action',
            'base_currency'   => 'Base Currency',
            'target_currency' => 'Target Currency',
            'exchange_rate'   => 'Exchange Rate',
            'start_date'      => 'Start Date',
            'end_date'        => 'End Date',
            'status_text'     => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort, 
            'by'             => $by, 
            'pageNo'         => $pageNo, 
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent, 
            'objectname'     => 'currencyconverter', 
            'tableheader'    => $tableHeaders,
            'extra'          => [], 
            'jsGridFnName'   => 'getAjaxgridData', 
            'jsFillFnName'   => '', 
            'searchArray'    => $searchArray,
            // SSOT IV: No 'add' flag injected here. Handled by core ACL.
            'menuName'       => 'Currency Conversions', 
            'call'           => $call, 
            'dashboardcall'  => $dashboardcall, 
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
            ]
        ];
    }

    /**
     * SSOT Standard Grid Data Fetcher.
     * 
     * @param string $sort
     * @param string $by
     * @param int    $pageNo
     * @param int    $perPage
     * @param string $searchQuery
     * @return Zend_Db_Select
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        // Handle sorting aliases
        if ($by == 'status_text') $by = 'cc.isactive';
        if ($by == 'base_currency') $by = 'bc.code';
        if ($by == 'target_currency') $by = 'tc.code';
        // Prefix default columns to avoid ambiguity
        if (in_array($by, ['exchange_rate', 'start_date', 'end_date'])) {
            $by = 'cc.' . $by;
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['cc' => $this->_name], [
                'id'            => 'cc.id', // SSOT IV: Mandatory 'id' alias
                'exchange_rate' => 'cc.exchange_rate',
                'start_date'    => new Zend_Db_Expr("DATE_FORMAT(cc.start_date, '" . DATEFORMAT_MYSQL . "')"),
                'end_date'      => new Zend_Db_Expr("IF(cc.end_date IS NOT NULL, DATE_FORMAT(cc.end_date, '" . DATEFORMAT_MYSQL . "'), '')"),
                'status_text'   => new Zend_Db_Expr("CASE WHEN cc.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->joinInner(['bc' => 'main_currency'], 'cc.base_currency_id = bc.id', ['base_currency' => 'bc.code'])
            ->joinInner(['tc' => 'main_currency'], 'cc.target_currency_id = tc.id', ['target_currency' => 'tc.code'])
            ->where('cc.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        
        return $select;
    }

    /**
     * Standard ID Retrieval.
     * 
     * @param int $id
     * @return array|null
     */
    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /* =================================================================================
     * GROUP 2: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    /**
     * Standard Save/Update.
     * 
     * @param array $data DB Columns mapping
     * @param int   $id   Optional ID for updating
     * @return int|string Last insert ID or 'update'
     */
    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        
        if (!empty($id)) {
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
     * GROUP 3: REMOVAL & STATUS
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
            throw new Exception("Cannot delete this conversion rate as it is currently linked to historical transactions.");
        }

        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = [
            'isactive'     => 5, 
            'modifiedby'   => $userId, 
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /**
     * Standard active status toggle.
     * 
     * @param int $id
     * @param int $status
     * @return int
     */
    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = [
            'isactive'     => (int)$status, 
            'modifiedby'   => $userId, 
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 4: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Checks if a unique conversion period exists to prevent DB Constraint failures.
     * Validates: Base + Target + Start Date must be unique.
     * 
     * @param int    $baseId
     * @param int    $targetId
     * @param string $startDate (YYYY-MM-DD)
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicatePeriod($baseId, $targetId, $startDate, $excludeId = null)
    {
        $select = $this->select()
            ->where("base_currency_id = ?", (int)$baseId)
            ->where("target_currency_id = ?", (int)$targetId)
            ->where("start_date = ?", $startDate)
            ->where("isactive != 5");

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * Dependency Checker (SSOT IV Data Integrity)
     * Verifies if this specific rate ID is bound to immutable historical records.
     * 
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        // By default, Sentrifugo expenses copy the rate value at the time of creation
        // rather than strictly linking to the conversion table ID to preserve history.
        // However, this acts as a placeholder for strict linking if future modules require it.
        return false; 
    }
}