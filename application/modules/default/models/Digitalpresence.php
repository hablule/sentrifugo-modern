<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Digital Presence Model
 * 
 * Manages polymorphic digital presence mappings.
 * Strictly adheres to SSOT v6.2: Zend_Db_Select exclusively, singular table naming, 
 * and advanced COALESCE queries for grid rendering.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Digitalpresence extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT Singular Naming Convention) */
    protected $_name = 'main_digitalpresence';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 1: DATA DISPLAY & RETRIEVAL
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

        $select->order('id DESC');

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
                    $dbVal = ($val === 'Active') ? 1 : 0;
                    $searchQuery .= " dp.isactive = " . $dbVal . " AND ";
                } elseif ($key == 'media_name') {
                    $searchQuery .= " dm.name LIKE '%$val%' AND ";
                } elseif ($key == 'owner_name') {
                    // Search across all possible owners
                    $searchQuery .= " (c.name LIKE '%$val%' OR bu.name LIKE '%$val%' OR di.name LIKE '%$val%' OR de.name LIKE '%$val%' OR se.name LIKE '%$val%' OR pr.name LIKE '%$val%' OR tm.name LIKE '%$val%' OR em.userfullname LIKE '%$val%') AND ";
                } else {
                    $searchQuery .= " dp.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        $tableHeaders = [
            'action'         => 'Action',
            'media_name'     => 'Media Platform',
            'digitaladdress' => 'URL / Handle',
            'owner_type'     => 'Entity Type',
            'owner_name'     => 'Assigned To',
            'status_text'    => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'digitalpresence',
            'tableheader'    => $tableHeaders,
            'extra'          => [],
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Digital Presence',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
            ]
        ];
    }

    /**
     * SSOT Standard Grid Data Fetcher.
     * Uses COALESCE to resolve the polymorphic relationship into a single display column.
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'dp.isactive';
        if ($by == 'media_name') $by = 'dm.name';
        if ($by == 'digitaladdress') $by = 'dp.digitaladdress';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['dp' => $this->_name], [
                'id'             => 'dp.id', // SSOT IV.2
                'digitaladdress' => 'dp.digitaladdress',
                'status_text'    => new Zend_Db_Expr("CASE WHEN dp.isactive = 1 THEN 'Active' ELSE 'Inactive' END"),
                // Resolve Entity Type dynamically
                'owner_type'     => new Zend_Db_Expr("
                    CASE 
                        WHEN dp.company_id IS NOT NULL THEN 'Company'
                        WHEN dp.sbunit_id IS NOT NULL THEN 'Business Unit'
                        WHEN dp.division_id IS NOT NULL THEN 'Division'
                        WHEN dp.department_id IS NOT NULL THEN 'Department'
                        WHEN dp.section_id IS NOT NULL THEN 'Section'
                        WHEN dp.project_id IS NOT NULL THEN 'Project'
                        WHEN dp.team_id IS NOT NULL THEN 'Team'
                        WHEN dp.employee_id IS NOT NULL THEN 'Employee'
                        ELSE 'Unknown'
                    END
                "),
                // Resolve Owner Name dynamically
                'owner_name'     => new Zend_Db_Expr("COALESCE(c.name, bu.name, di.name, de.name, se.name, pr.name, tm.name, em.userfullname)")
            ])
            ->joinInner(['dm' => 'main_digitalmedia'], 'dp.digitalmedia_id = dm.id', ['media_name' => 'dm.name'])
            ->joinLeft(['c'  => 'main_company'], 'dp.company_id = c.id', [])
            ->joinLeft(['bu' => 'main_businessunit'], 'dp.sbunit_id = bu.id', [])
            ->joinLeft(['di' => 'main_division'], 'dp.division_id = di.id', [])
            ->joinLeft(['de' => 'main_department'], 'dp.department_id = de.id', [])
            ->joinLeft(['se' => 'main_section'], 'dp.section_id = se.id', [])
            ->joinLeft(['pr' => 'main_project'], 'dp.project_id = pr.id', [])
            ->joinLeft(['tm' => 'main_team'], 'dp.team_id = tm.id', [])
            ->joinLeft(['em' => 'main_employee'], 'dp.employee_id = em.id', [])
            ->where('dp.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        // Handle complex sorting for polymorphic column
        if ($by == 'owner_name') {
            $select->order(new Zend_Db_Expr("COALESCE(c.name, bu.name, di.name, de.name, se.name, pr.name, tm.name, em.userfullname) $sort"));
        } else {
            $select->order("$by $sort");
        }

        $select->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'digitaladdress'])->order('digitaladdress ASC');
        if ($activeOnly) $select->where("isactive = 1");
        return $this->getAdapter()->fetchPairs($select);
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

    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this record. It is currently in use.");
        }

        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = ['isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s")];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = ['isactive' => (int)$status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s")];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 4: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()->where("$field = ?", trim($value))->where("isactive != 5");
        if ($excludeId) $select->where('id != ?', (int)$excludeId);
        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        // Transactional leaf node; inherently not a parent to other tables.
        return false;
    }
}
