<?php

/**
 * City Lookup Model
 * 
 * Manages geographical cities/municipalities (Pillar 1, Level 3).
 * Strictly adheres to SSOT v6.1: Zend_Db_Select, dependency checks, 
 * standardized grid formatting, and grouped method architecture.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_City extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT Singular Naming Convention) */
    protected $_name = 'main_city';

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

        $select->order('name ASC');

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
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                // Safely translate UI Status Text to DB Integers
                if ($key == 'status_text') {
                    if ($val === 'Active') {
                        $searchQuery .= " ci.isactive = 1 AND ";
                    } elseif ($val === 'Inactive') {
                        $searchQuery .= " ci.isactive = 0 AND ";
                    }
                } elseif ($key == 'state_name') {
                    $searchQuery .= " s.name LIKE '%$val%' AND ";
                } elseif ($key == 'country_name') {
                    $searchQuery .= " c.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " ci.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // Must match exact column aliases defined in getGridData()
        $tableHeaders = array(
            'action'       => 'Action',
            'country_name' => 'Country',
            'state_name'   => 'State/Province',
            'name'         => 'City Name',
            'status_text'  => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'city',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            // SSOT IV: No 'add' flag injected here. Handled by core ACL.
            'menuName'       => 'Cities',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    /**
     * SSOT Standard Grid Data Fetcher.
     * Integrates State and Country context for full geographical awareness.
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
        if ($by == 'status_text') {
            $by = 'ci.isactive';
        } elseif ($by == 'state_name') {
            $by = 's.name';
        } elseif ($by == 'country_name') {
            $by = 'c.name';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ci' => $this->_name), array(
                'id' => 'ci.id',
                '*',
                // Alias status text so the grid helper displays words instead of 1/0
                'status_text' => new Zend_Db_Expr("CASE WHEN ci.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ))
            ->joinInner(array('s' => 'main_state'), 'ci.state_id = s.id', array('state_name' => 's.name'))
            ->joinInner(array('c' => 'main_country'), 's.country_id = c.id', array('country_name' => 'c.name'))
            ->where('ci.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /**
     * Standard Pairs Retrieval (Used for Dropdowns).
     * Optional $stateId parameter added to support Cascading AJAX Dropdowns.
     * 
     * @param bool     $activeOnly
     * @param int|null $stateId Filter cities by parent state
     * @return array [id => name]
     */
    public function getPairs($activeOnly = true, $stateId = null)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');

        if ($activeOnly) {
            $select->where("isactive = 1");
        }

        if ($stateId) {
            $select->where("state_id = ?", (int)$stateId);
        }

        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Relational Helper: Get Cities by State ID
     * 
     * @param int  $stateId
     * @param bool $activeOnly
     * @return array [id => name]
     */
    public function getPairsByState($stateId, $activeOnly = true)
    {
        if (empty($stateId)) return array();

        $select = $this->select()
            ->from($this->_name, array('id', 'name'))
            ->where('state_id = ?', (int)$stateId)
            ->order('name ASC');

        if ($activeOnly) {
            $select->where("isactive = 1");
        }

        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Relational Helper: Get Cities by Country ID (Skipping State Level)
     * 
     * @param int  $countryId
     * @param bool $activeOnly
     * @return array [id => name]
     */
    public function getPairsByCountry($countryId, $activeOnly = true)
    {
        if (empty($countryId)) return array();

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('c' => $this->_name), array('id' => 'c.id', 'name' => 'c.name'))
            ->joinInner(array('s' => 'main_state'), 'c.state_id = s.id', array())
            ->where('s.country_id = ?', (int)$countryId)
            ->order('c.name ASC');

        if ($activeOnly) {
            $select->where("c.isactive = 1");
        }

        return $this->getAdapter()->fetchPairs($select);
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
            ->setIntegrityCheck(false)
            ->from(array('ci' => $this->_name), array('ci.*'))
            // We join State to ensure we can populate the 'Country' dropdown contextually
            ->joinInner(array('s' => 'main_state'), 'ci.state_id = s.id', array('country_id' => 's.country_id'))
            ->where('ci.id = ?', (int)$id)
            ->where('ci.isactive != 5');

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
            throw new Exception("Cannot delete this City as it is currently assigned to a physical address or location.");
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
     * Composite Duplicate Checker.
     * Verifies if a city name is already used WITHIN the same state.
     * (e.g., "Springfield" can exist in multiple states, but not twice in the same state).
     * 
     * @param int    $stateId
     * @param string $cityName
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicateCity($stateId, $cityName, $excludeId = null)
    {
        $select = $this->select()
            ->where('state_id = ?', (int)$stateId)
            ->where('name = ?', trim($cityName))
            ->where("isactive != 5");

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * @deprecated Use checkDuplicateCity() for relational context.
     * Kept to satisfy SSOT IV interface mandates.
     */
    public function checkDuplicate($field, $value, $excludeId = null)
    {
        return null;
    }

    /**
     * Dependency Checker (SSOT IV Data Integrity)
     * Verifies if the city is assigned to a geographical location.
     * 
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();

        // Check 1: Is it used in main_location?
        $selectLoc = $db->select()
            ->from('main_location', 'id')
            ->where('city_id = ?', (int)$id)
            ->limit(1);

        if ((bool)$db->fetchOne($selectLoc)) {
            return true;
        }

        // Check 2: Is it used in main_address? (If addresses link to cities)
        // $selectAddr = $db->select()
        //     ->from('main_address', 'id')
        //     ->where('city_id = ?', (int)$id)
        //     ->limit(1);
        //     
        // if ((bool)$db->fetchOne($selectAddr)) {
        //     return true;
        // }

        return false;
    }

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION SYNC (GEOGRAPHY GROUPS)
     * ================================================================================= */

    /**
     * Retrieves an array of Geography Group IDs currently mapped to this City.
     */
    public function getMappedGeographyGroupIds($cityId)
    {
        if (empty($cityId)) return array();

        $db = $this->getAdapter();
        $select = $db->select()
            ->from('map_geographygroup_entity', 'geographygroup_id')
            ->where('entity_type = ?', 'City')
            ->where('entity_id = ?', (int)$cityId)
            ->where('isactive != 5');

        return $db->fetchCol($select);
    }

    /**
     * Synchronizes the Polymorphic Many-to-Many relationship for Cities.
     */
    public function syncGeographyGroupMappings($cityId, array $groupIds)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        $currentMappedIds = $this->getMappedGeographyGroupIds($cityId);
        $toAdd    = array_diff($groupIds, $currentMappedIds);
        $toRemove = array_diff($currentMappedIds, $groupIds);

        if (!empty($toRemove)) {
            $db->delete('map_geographygroup_entity', array(
                'entity_type = ?' => 'City',
                'entity_id = ?'   => (int)$cityId,
                'geographygroup_id IN (?)' => $toRemove
            ));
        }

        if (!empty($toAdd)) {
            foreach ($toAdd as $gId) {
                $db->insert('map_geographygroup_entity', array(
                    'geographygroup_id' => (int)$gId,
                    'entity_type'       => 'City',
                    'entity_id'         => (int)$cityId,
                    'isactive'          => 1,
                    'createdby'         => $userId,
                    'createddate'       => $currentDate
                ));
            }
        }
    }
}
