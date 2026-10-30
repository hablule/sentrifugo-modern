<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * Report Category Model
 * 
 * Manages the lookup table for categorizing reports (e.g., Compliance, Analytics).
 * Strictly adheres to SSOT v6.7.4 (Singular Naming, Method Grouping, No Raw SQL).
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Reportcategory extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_report_category';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');

        if ($activeStatus !== null) {
            $select->where('isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        }

        $select->order('name ASC');

        if ($limit !== null) {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'status_text') {
                    $dbVal = ($val === 'Active') ? 1 : 0;
                    $searchQuery .= " rc.isactive = " . $dbVal . " AND ";
                } else {
                    $searchQuery .= " rc.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // SSOT IV.3: Strict headers. Action must be first.
        $tableHeaders = [
            'action'      => 'Action',
            'code'        => 'Category Code',
            'name'        => 'Category Name',
            'description' => 'Description',
            'status_text' => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'reportcategory',
            'tableheader'    => $tableHeaders,
            'extra'          => [],
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            // SSOT IV.4: No 'add' flag injected here. Handled by core ACL.
            'menuName'       => 'Report Categories',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
            ]
        ];
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'rc.isactive';
        if (in_array($by, ['code', 'name', 'description'])) $by = 'rc.' . $by;

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['rc' => $this->_name], [
                'id'          => 'rc.id', // SSOT IV.2: Mandatory Grid Aliasing
                'code'        => 'rc.code',
                'name'        => 'rc.name',
                'description' => 'rc.description',
                'status_text' => new Zend_Db_Expr("CASE WHEN rc.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->where('rc.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'name'])->order('name ASC');
        if ($activeOnly) {
            $select->where("isactive = 1");
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, ['id = ?' => (int)$id]);
            return (int)$id;
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        $this->insert($data);
        return (int)$this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteRecord($id)
    {
        // SSOT IV.5: Mandatory Dependency Check execution
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this Category. It is currently assigned to one or more active Reports in the Catalog.");
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

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
     * GROUP 5: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

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
     * SSOT IV.5 Data Integrity: Verifies if category is assigned to reports.
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $select = $db->select()
                ->from('main_report_catalog', 'id')
                ->where('category_id = ?', (int)$id)
                ->where('isactive != 5')
                ->limit(1);
            if ((bool)$db->fetchOne($select)) return true;
        } catch (Exception $e) {
            error_log("ReportCategory Dependency Check Error: " . $e->getMessage());
        }
        return false;
    }
}
