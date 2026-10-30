<?php

/**
 * Geography Group Mapping Model
 * 
 * Manages the polymorphic Many-to-Many junction between Geography Groups 
 * and specific Geographical Entities (Countries, States, Cities, Postal Codes).
 * Strictly adheres to SSOT v6.1: Dynamic Zend_Db_Select joins, composite duplicate checks, 
 * standardized grid formatting, and grouped method architecture.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_GeographygroupMap extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT Singular Naming Convention) */
    protected $_name = 'map_geographygroup_entity';

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
        $select = $this->select()->where('isactive != 5');

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
     * @param int|null $groupId Required to filter grid to a specific Group
     * @return array
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $groupId = null)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                // Safely translate UI Status Text to DB Integers
                if ($key == 'status_text') {
                    if ($val === 'Active') {
                        $searchQuery .= " mge.isactive = 1 AND ";
                    } elseif ($val === 'Inactive') {
                        $searchQuery .= " mge.isactive = 0 AND ";
                    }
                } else {
                    $searchQuery .= " mge.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // Must match exact column aliases defined in getGridData()
        $tableHeaders = array(
            'action'                => 'Action',
            'geographygroup_name'   => 'Group Name',
            'entity_type'           => 'Entity Level',
            'entity_name'           => 'Mapped Entity Name',
            'description'           => 'Description',
            'status_text'           => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery, $groupId);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'geographygroupmap',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            // SSOT IV: No 'add' flag injected here. Handled by core ACL.
            'menuName'       => 'Mapped Geographies',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive')),
                'entity_type' => array('type' => 'select', 'filter_data' => array('' => 'All Levels', 'Country' => 'Country', 'State' => 'State', 'City' => 'City', 'PostalCode' => 'Postal Code'))
            )
        );
    }

    /**
     * SSOT Standard Grid Data Fetcher.
     * Polymorphic logic to fetch the correct human-readable name based on the entity type.
     * 
     * @param string $sort
     * @param string $by
     * @param int    $pageNo
     * @param int    $perPage
     * @param string $searchQuery
     * @param int|null $groupId Required to filter grid to a specific Group
     * @return Zend_Db_Select
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '', $groupId = null)
    {
        if ($by == 'status_text') {
            $by = 'mge.isactive';
        } elseif ($by == 'entity_name') {
            $by = 'entity_name'; // Derived column alias
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('mge' => $this->_name), array(
                'id' => 'mge.id',
                'entity_type',
                'description',
                'status_text' => new Zend_Db_Expr("CASE WHEN mge.isactive = 1 THEN 'Active' ELSE 'Inactive' END"),
                // Polymorphic Name Resolution (SQL CASE statement resolving entity_id to physical table name)
                'entity_name' => new Zend_Db_Expr("
                    CASE 
                        WHEN mge.entity_type = 'Country' THEN (SELECT name FROM main_country c WHERE c.id = mge.entity_id)
                        WHEN mge.entity_type = 'State' THEN (SELECT name FROM main_state s WHERE s.id = mge.entity_id)
                        WHEN mge.entity_type = 'City' THEN (SELECT name FROM main_city ci WHERE ci.id = mge.entity_id)
                        WHEN mge.entity_type = 'PostalCode' THEN (SELECT code FROM main_postal_code pc WHERE pc.id = mge.entity_id)
                        ELSE 'Unknown Entity'
                    END
                ")
            ))
            ->joinInner(array('g' => 'main_geographygroup'), 'mge.geographygroup_id = g.id', array('geographygroup_name' => 'g.name'))
            ->where('mge.isactive != 5');

        if (!empty($groupId)) {
            $select->where('mge.geographygroup_id = ?', (int)$groupId);
        }

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /**
     * Standard Pairs Retrieval.
     * Junction tables rarely use getPairs, but it satisfies the SSOT IV interface.
     * 
     * @param bool $activeOnly
     * @return array [id => entity_name]
     */
    public function getPairs($activeOnly = true)
    {
        // Not typically used for polymorphic tables, returning empty to prevent misuse.
        return array();
    }

    /**
     * Standard ID Retrieval.
     * 
     * @param int $id
     * @return array|null
     */
    public function getRecordById($id)
    {
        $select = $this->select()
            ->from($this->_name, array('*'))
            ->where('id = ?', (int)$id)
            ->where('isactive != 5');

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
            $this->update($data, array('id = ?' => (int)$id));
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
            throw new Exception("Cannot delete this Mapping as it is currently serving as an active dependency.");
        }

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

        $data = array(
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 4: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * @deprecated Use checkDuplicateMapping for polymorphic junction tables.
     * Kept to satisfy SSOT IV interface mandates.
     */
    public function checkDuplicate($field, $value, $excludeId = null)
    {
        return null;
    }

    /**
     * SSOT IV: Polymorphic Junction Table Duplicate Checker.
     * Verifies if a specific entity is already mapped to a specific Group.
     * 
     * @param int    $groupId
     * @param string $entityType
     * @param int    $entityId
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicateMapping($groupId, $entityType, $entityId, $excludeId = null)
    {
        $select = $this->select()
            ->where('geographygroup_id = ?', (int)$groupId)
            ->where('entity_type = ?', trim($entityType))
            ->where('entity_id = ?', (int)$entityId)
            ->where('isactive != 5');

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * Dependency Checker (SSOT IV Data Integrity)
     * Verifies if this specific mapping is actively assigned to an employee profile or policy.
     * 
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        // Placeholder: Will return true once we link this junction table to active Policies/Holidays.
        return false;
    }
}
