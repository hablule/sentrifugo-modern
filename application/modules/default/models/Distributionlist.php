<?php

/**
 * Distribution Lists Model
 *
 * Manages shared inboxes and distribution lists mapped to specific organizational units.
 * Refactored to comply with SSOT v6.2 (PHP 7.4 compatibility, Zend_Db_Select).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_DistributionList extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name */
    protected $_name = 'main_distribution_list';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Fetch a single record by Primary Key.
     * 
     * @param int $id
     * @return array|null
     */
    public function getRecordById($id)
    {
        $select = $this->select()
            ->where('id = ?', (int)$id)
            ->where('isactive != ?', 5);
            
        $row = $this->fetchRow($select);
        return $row ? $row->toArray() : null;
    }

    /**
     * Fetch all records with optional filtering and pagination.
     * 
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $search_val
     * @param string|null $search_str
     * @param mixed $activeStatus
     * @return array
     */
    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != ?', 5);

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

    /**
     * Generates a flat ID => Name array for Select dropdowns.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
        if ($activeOnly) {
            $select->where('isactive = ?', 1);
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Builds the Grid configuration array for the UI.
     * SSOT v6.2: 'action' MUST be first. No 'add' flag.
     * 
     * @param string $sort
     * @param string $by
     * @param int $perPage
     * @param int $pageNo
     * @param string $searchData
     * @param string $call
     * @param string $dashboardcall
     * @return array
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if (!empty($searchData) && $searchData !== 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key === 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " d.isactive = " . $dbVal . " AND ";
                    } else {
                        $searchQuery .= " d." . $key . " LIKE '%" . $val . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // SSOT Mandatory: 'action' => 'Action' must be the first element
        $tableHeaders = array(
            'action'        => 'Action',
            'name'          => 'Inbox Name',
            'email_address' => 'Email Address',
            'unit_mapping'  => 'Mapped Unit',
            'status_text'   => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'distributionlist',
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Distribution Lists',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    /**
     * Returns the raw Zend_Db_Select object for the Grid.
     * Includes complex mapping logic to display the operational unit.
     * 
     * @param string $sort
     * @param string $by
     * @param int $pageNo
     * @param int $perPage
     * @param string $searchQuery
     * @return Zend_Db_Select
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'status_text') {
            $by = 'isactive';
        }

        // We build a CASE statement to determine what operational unit this email belongs to for UI display
        $unitMappingExpr = new Zend_Db_Expr("
            CASE 
                WHEN d.businessunit_id IS NOT NULL THEN CONCAT('BU: ', bu.unitname)
                WHEN d.division_id IS NOT NULL THEN CONCAT('Division: ', divi.name)
                WHEN d.department_id IS NOT NULL THEN CONCAT('Dept: ', dept.name)
                WHEN d.section_id IS NOT NULL THEN CONCAT('Section: ', sec.name)
                WHEN d.team_id IS NOT NULL THEN CONCAT('Team: ', t.name)
                WHEN d.project_id IS NOT NULL THEN CONCAT('Project: ', p.name)
                WHEN d.group_id IS NOT NULL THEN CONCAT('Group: ', g.name)
                ELSE 'Global System Inbox' 
            END
        ");

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('d' => $this->_name),
                array(
                    'id' => 'd.id', // SSOT Aliasing requirement
                    'name',
                    'email_address',
                    'status_text'  => new Zend_Db_Expr("CASE WHEN d.isactive = 1 THEN 'Active' ELSE 'Inactive' END"),
                    'unit_mapping' => $unitMappingExpr
                )
            )
            // Left joins to resolve the mapped operational unit name
            ->joinLeft(array('bu' => 'main_businessunits'), 'd.businessunit_id = bu.id', array())
            ->joinLeft(array('divi' => 'main_division'), 'd.division_id = divi.division_id', array())
            ->joinLeft(array('dept' => 'main_department'), 'd.department_id = dept.department_id', array())
            ->joinLeft(array('sec' => 'main_section'), 'd.section_id = sec.section_id', array())
            ->joinLeft(array('t' => 'main_team'), 'd.team_id = t.team_id', array())
            ->joinLeft(array('p' => 'main_project'), 'd.project_id = p.project_id', array())
            ->joinLeft(array('g' => 'main_group'), 'd.group_id = g.id', array())
            ->where('d.isactive != ?', 5);

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    /**
     * Standard persistence method handling Add and Edit.
     * 
     * @param array $data
     * @param int|null $id
     * @return int|string Last Insert ID or 'update'
     */
    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        
        $currentDate = gmdate("Y-m-d H:i:s");

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = $currentDate;
            $this->update($data, array('id = ?' => (int)$id));
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

    /**
     * Safely soft-deletes a record.
     * 
     * @param int $id
     * @return int Number of rows updated
     */
    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = array(
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /**
     * Toggles the Active (1) / Inactive (0) status.
     * 
     * @param int $id
     * @param mixed $status
     * @return int Number of rows updated
     */
    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = array(
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );

        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS, & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Validate against duplicate entries.
     * 
     * @param string $field
     * @param string $value
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim($value))
            ->where('isactive != ?', 5);

        if (!empty($excludeId)) {
            $select->where('id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    /**
     * Dependency Check prior to deletion.
     * 
     * @param int $id
     * @return bool True if record is actively used and cannot be deleted
     */
    public function checkInUse($id)
    {
        // Distribution lists are routing targets. They are safe to delete as the 
        // routing engine should safely fallback if an inbox is missing.
        return false;
    }

    /**
     * Utility method: Finds the specific shared inbox for a given operational unit context.
     * E.g., pass ('department_id', 5) to find the HR department's shared inbox.
     * 
     * @param string $unitColumn The column to match (e.g., 'businessunit_id')
     * @param int $unitId The ID of the unit
     * @return string|null The email address, or null if none found
     */
    public function getInboxByUnit($unitColumn, $unitId)
    {
        $select = $this->select()
            ->from($this->_name, array('email_address'))
            ->where("$unitColumn = ?", (int)$unitId)
            ->where('isactive = ?', 1);
            
        $row = $this->fetchRow($select);
        return $row ? $row['email_address'] : null;
    }

    /* =================================================================================
     * LEGACY DEPRECATED METHODS (Defanging Pattern Applied)
     * ================================================================================= */

    /**
     * @deprecated Use getAllRecords() instead.
     */
    public function getEmailcontactsRecord()
    {
        $select = $this->select()->from(array('i' => $this->_name), array('i.*'));
        return $this->fetchAll($select)->toArray();
    }

    /**
     * @deprecated Use getRecordById($id) instead.
     */
    public function getEmailcontactsRecord_i($id)
    {
        $record = $this->getRecordById((int)$id);
        return $record ? array($record) : array();
    }
}