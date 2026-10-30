<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * Report Catalog Model
 * 
 * Manages the Report Catalog and synchronizes the Polymorphic ACL mappings.
 * Strictly adheres to SSOT v6.7.4.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Reportcatalog extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_report_catalog';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');
        if ($activeStatus !== null) $select->where('isactive = ?', (int)$activeStatus);
        if (!empty($search_val) && !empty($search_str)) $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        $select->order('name ASC');
        if ($limit !== null) $select->limit($limit, (int)$offset);
        return $this->fetchAll($select)->toArray();
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'status_text') {
                    $searchQuery .= " r.isactive = " . (($val === 'Active') ? 1 : 0) . " AND ";
                } elseif ($key == 'category_name') {
                    $searchQuery .= " c.name LIKE '%$val%' AND ";
                } elseif ($key == 'menugroup_name') {
                    $searchQuery .= " mg.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " r.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // SSOT IV.3: Strict headers. Action must be first.
        $tableHeaders = array(
            'action'        => 'Action',
            'menugroup_name' => 'Dashboard Group',
            'category_name' => 'Report Category',
            'code'          => 'Report Code',
            'name'          => 'Report Name',
            'status_text'   => 'Status'
        );

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery),
            'objectname'     => 'reportcatalog',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            // SSOT IV.4: No 'add' flag injected here.
            'menuName'       => 'Report Catalog',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text')   $by = 'r.isactive';
        if ($by == 'category_name') $by = 'c.name';
        if ($by == 'menugroup_name') $by = 'mg.name';
        if (in_array($by, array('code', 'name', 'menugroup_id', 'category_id'))) {
            $by = 'r.' . $by;
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('r' => $this->_name), array(
                'id'          => 'r.id', 
                'menugroup_id'   => 'r.menugroup_id',
                'category_id'    => 'r.category_id',
                'code'        => 'r.code',
                'name'        => 'r.name',
                'status_text' => new Zend_Db_Expr("CASE WHEN r.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ))
            ->joinInner(array('c' => 'main_report_category'), 'r.category_id = c.id', array('category_name' => 'c.name'))
            ->joinLeft(array('mg' => 'main_menugroup'), 'r.menugroup_id = mg.id', array('menugroup_name' => 'mg.name'))
            ->where('r.isactive != 5');

        if (!empty($searchQuery)) $select->where($searchQuery);
        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
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
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, array('id = ?' => (int)$id));
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
        // SSOT IV.5: Dependency Check
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this Report. There are active ACL mappings tied to it.");
        }
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array('isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array('isactive' => (int)$status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 5: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()->where("$field = ?", trim($value))->where("isactive != 5");
        if ($excludeId) $select->where('id != ?', (int)$excludeId);
        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $select = $db->select()->from('map_report_acl', 'id')->where('report_id = ?', (int)$id)->where('isactive = 1')->limit(1);
            if ((bool)$db->fetchOne($select)) return true;
        } catch (Exception $e) {
        }
        return false;
    }

    /**
     * THE UNIVERSAL ACL GATEKEEPER
     * 
     * Evaluates a user's Group, Role, and Employee ID against the Polymorphic ACL table.
     * Crucial for Phase 5 Analytics & Reporting engines.
     * 
     * @param int    $menuGroupId The visual dashboard ID (e.g., 4 for Performance)
     * @param int    $empId   The logged-in user's main_employee.id
     * @param int    $roleId  The logged-in user's main_role.id
     * @param int    $groupId The logged-in user's main_group.id
     * @return array          A flat array of authorized report codes (e.g., ['PA_EXEC_SUMMARY', 'PA_360_GAP'])
     */
    public function getAuthorizedReportCodes($menuGroupId, $empId, $roleId, $groupId)
    {
        $db = $this->getAdapter();

        // 1. Base query: Get active reports for the requested module
        $select = $db->select()
            ->from(array('rc' => $this->_name), array('code'))
            ->where('rc.isactive = 1')
            ->where('rc.menugroup_id = ?', (int)$menuGroupId);

        // 2. The Check: Are there ANY ACL rules defined for this specific report?
        // If a report has 0 rules in the map_report_acl table, it is "locked down" and only 
        // Super Admins (Role 1) should see it.
        $select->joinInner(
            array('acl' => 'map_report_acl'),
            'rc.id = acl.report_id AND acl.isactive = 1',
            array()
        );

        // 3. The Polymorphic OR-Gate
        // The user gets access IF:
        // A) Their exact EmpID is mapped
        // OR B) Their exact Role is mapped
        // OR C) Their exact Group is mapped

        $whereGate = array(
            $db->quoteInto("(acl.target_type = 'Employee' AND acl.target_id = ?)", (int)$empId),
            $db->quoteInto("(acl.target_type = 'Role' AND acl.target_id = ?)", (int)$roleId),
            $db->quoteInto("(acl.target_type = 'Group' AND acl.target_id = ?)", (int)$groupId)
        );

        $select->where(implode(' OR ', $whereGate));

        // Group by to avoid duplicates if a user matches multiple rules (e.g. they are in the Group AND explicitly named)
        $select->group('rc.code');

        return $db->fetchCol($select);
    }

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION SYNC METHODS (SSOT IV.9 & IV.10)
     * ================================================================================= */

    public function syncAclMappings($reportId, array $groups, array $roles, array $employees)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $date = gmdate("Y-m-d H:i:s");

        // 1. Atomic Purge
        $db->delete('map_report_acl', array('report_id = ?' => (int)$reportId));

        $insertions = array();
        foreach ($groups as $g) {
            if ((int)$g['target_id'] > 0) $insertions[] = array('type' => 'Group', 'id' => (int)$g['target_id']);
        }
        foreach ($roles as $r) {
            if ((int)$r['target_id'] > 0) $insertions[] = array('type' => 'Role', 'id' => (int)$r['target_id']);
        }
        foreach ($employees as $e) {
            if ((int)$e['target_id'] > 0) $insertions[] = array('type' => 'Employee', 'id' => (int)$e['target_id']);
        }

        foreach ($insertions as $target) {
            try {
                $db->insert('map_report_acl', array(
                    'report_id'   => (int)$reportId,
                    'target_type' => $target['type'],
                    'target_id'   => $target['id'],
                    'isactive'    => 1,
                    'createdby'   => $userId,
                    'createddate' => $date
                ));
            } catch (Exception $dup) {
                // Ignore duplicates if the UI submitted the same ID twice
            }
        }
    }

    public function getExistingAclMappings($reportId)
    {
        $db = $this->getAdapter();
        $mappings = array('Group' => array(), 'Role' => array(), 'Employee' => array());

        $select = $db->select()
            ->from(array('acl' => 'map_report_acl'), array(
                'target_type',
                'target_id',
                'target_name' => new Zend_Db_Expr("
                    CASE acl.target_type
                        WHEN 'Group' THEN g.name
                        WHEN 'Role' THEN r.name
                        WHEN 'Employee' THEN e.userfullname
                        ELSE CONCAT('ID: ', acl.target_id)
                    END
                ")
            ))
            ->joinLeft(array('g' => 'main_group'),    "acl.target_type = 'Group' AND acl.target_id = g.id", array())
            ->joinLeft(array('r' => 'main_role'),     "acl.target_type = 'Role' AND acl.target_id = r.id", array())
            ->joinLeft(array('e' => 'main_employee'), "acl.target_type = 'Employee' AND acl.target_id = e.id", array())
            ->where('acl.report_id = ?', (int)$reportId)
            ->where('acl.isactive = 1');

        $results = $db->fetchAll($select);

        foreach ($results as $row) {
            $type = $row['target_type'];
            if (isset($mappings[$type])) {
                $mappings[$type][] = array('target_id' => $row['target_id'], 'target_name' => $row['target_name']);
            }
        }
        return $mappings;
    }
}
