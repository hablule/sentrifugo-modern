<?php

/**
 * Country Lookup Model
 * 
 * Manages strictly geographical country data (Pillar 1 of the Geography vs 
 * Jurisprudence architecture) used in addresses, shipping, and phone codes.
 * Strictly adheres to SSOT v6.2: Zend_Db_Select, dependency checks, 
 * standardized grid formatting, singular table naming, and grouped method architecture.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Country extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT Singular Naming Convention) */
    protected $_name = 'main_country';

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
                if ($key == 'status_text') {
                    // Safely translate UI Status Text to DB Integers
                    if ($val === 'Active') {
                        $searchQuery .= " c.isactive = 1 AND ";
                    } elseif ($val === 'Inactive') {
                        $searchQuery .= " c.isactive = 0 AND ";
                    }
                } else {
                    $searchQuery .= " c.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // Must match exact column aliases defined in getGridData()
        $tableHeaders = array(
            'action'      => 'Action',
            'name'        => 'Country',
            'iso_alpha2'  => 'ISO Alpha-2',
            'iso_alpha3'  => 'ISO Alpha-3',
            'phone_code'  => 'Dialing Code',
            'status_text' => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'country',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            // SSOT IV: No 'add' flag injected here. Handled by core ACL.
            'menuName'       => 'Countries',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
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
        if ($by == 'status_text') {
            $by = 'c.isactive';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('c' => $this->_name), array(
                'id' => 'c.id',
                '*',
                // Alias status text so the grid helper displays words instead of 1/0
                'status_text' => new Zend_Db_Expr("CASE WHEN c.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ))
            ->where('c.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /**
     * Standard Pairs Retrieval (Used for Dropdowns in Popups/Forms).
     * 
     * @param bool $activeOnly
     * @return array [id => name]
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
        if ($activeOnly) {
            $select->where("isactive = 1");
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
            throw new Exception("Cannot delete this Country as it is currently assigned to a physical address or location.");
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
     * Standard duplicate field checker.
     * 
     * @param string   $field
     * @param string   $value
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
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
     * Verifies if the country is assigned as a geographical location.
     * Checks multiple potential dependency tables.
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
            ->where('country_id = ?', (int)$id)
            ->limit(1);

        if ((bool)$db->fetchOne($selectLoc)) {
            return true;
        }

        // Check 2: Is it used in main_address? (If addresses link directly to countries instead of locations)
        // Adjust this based on your exact address table schema
        $selectAddr = $db->select()
            ->from('main_address', 'id')
            ->where('country_id = ?', (int)$id)
            ->limit(1);

        if ((bool)$db->fetchOne($selectAddr)) {
            return true;
        }

        return false;
    }

    /* =================================================================================
     * GROUP 5: JUNCTION TABLE SYNC (MANY-TO-MANY)
     * ================================================================================= */

    /**
     * Retrieves an array of Nationality IDs currently mapped to a specific Country.
     * Used to pre-populate the multi-select box on the Edit screen.
     * 
     * @param int $countryId
     * @return array Flat array of Nationality IDs
     */
    public function getMappedNationalityIds($countryId)
    {
        if (empty($countryId)) {
            return array();
        }

        $db = $this->getAdapter();
        $select = $db->select()
            ->from('map_country_nationality', 'nationality_id')
            ->where('country_id = ?', (int)$countryId)
            ->where('isactive != 5')
            // Order by is_default DESC so the primary nationality appears first in the array
            ->order('is_default DESC');

        return $db->fetchCol($select);
    }

    /**
     * Synchronizes the Many-to-Many relationship between Countries and Nationalities.
     * Inserts new mappings, removes unchecked mappings, and handles the 'is_default' logic
     * where the first item in the submitted array is treated as the primary nationality.
     * 
     * @param int   $countryId      The ID of the Country being saved
     * @param array $nationalityIds Array of Nationality IDs selected in the UI
     * @return void
     */
    public function syncNationalityMappings($countryId, array $nationalityIds)
    {
        $db = $this->getAdapter();
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        // 1. Fetch current mappings to determine what to add/remove
        $currentMappedIds = $this->getMappedNationalityIds($countryId);

        // 2. Determine differences
        $toAdd    = array_diff($nationalityIds, $currentMappedIds);
        $toRemove = array_diff($currentMappedIds, $nationalityIds);

        // 3. Remove unselected mappings (Hard delete for junction tables)
        if (!empty($toRemove)) {
            $db->delete('map_country_nationality', array(
                'country_id = ?'      => (int)$countryId,
                'nationality_id IN (?)' => $toRemove
            ));
        }

        // 4. Insert new mappings
        if (!empty($toAdd)) {
            foreach ($toAdd as $index => $nId) {
                // The very first item in the submitted array becomes the default
                $isDefault = ($index === 0) ? 1 : 0;

                $db->insert('map_country_nationality', array(
                    'country_id'     => (int)$countryId,
                    'nationality_id' => (int)$nId,
                    'is_default'     => $isDefault,
                    'isactive'       => 1,
                    'createdby'      => $userId,
                    'createddate'    => $currentDate
                ));
            }
        }

        // 5. Enforce the 'is_default' flag based on the current array order
        // Even if no new items were added, the admin might have re-ordered the selections
        if (!empty($nationalityIds)) {
            $firstNatId = $nationalityIds[0];

            // Strip default from all mappings for this country
            $db->update(
                'map_country_nationality',
                array('is_default' => 0, 'modifiedby' => $userId, 'modifieddate' => $currentDate),
                array('country_id = ?' => (int)$countryId)
            );

            // Apply default to the chosen first nationality
            $db->update(
                'map_country_nationality',
                array('is_default' => 1, 'modifiedby' => $userId, 'modifieddate' => $currentDate),
                array('country_id = ?' => (int)$countryId, 'nationality_id = ?' => (int)$firstNatId)
            );
        }
    }

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION SYNC (GEOGRAPHY GROUPS)
     * ================================================================================= */

    /**
     * Retrieves an array of Geography Group IDs currently mapped to this Country.
     * 
     * @param int $countryId
     * @return array Flat array of Geography Group IDs
     */
    public function getMappedGeographyGroupIds($countryId)
    {
        if (empty($countryId)) return array();

        $db = $this->getAdapter();
        $select = $db->select()
            ->from('map_geographygroup_entity', 'geographygroup_id')
            ->where('entity_type = ?', 'Country')
            ->where('entity_id = ?', (int)$countryId)
            ->where('isactive != 5');

        return $db->fetchCol($select);
    }

    /**
     * Synchronizes the Polymorphic Many-to-Many relationship between Countries and Geography Groups.
     * 
     * @param int   $countryId The ID of the Country being saved
     * @param array $groupIds  Array of Geography Group IDs selected in the UI
     * @return void
     */
    public function syncGeographyGroupMappings($countryId, array $groupIds)
    {
        $db = $this->getAdapter();
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        // 1. Fetch current mappings
        $currentMappedIds = $this->getMappedGeographyGroupIds($countryId);

        // 2. Determine differences
        $toAdd    = array_diff($groupIds, $currentMappedIds);
        $toRemove = array_diff($currentMappedIds, $groupIds);

        // 3. Remove unselected mappings
        if (!empty($toRemove)) {
            $db->delete('map_geographygroup_entity', array(
                'entity_type = ?' => 'Country',
                'entity_id = ?'   => (int)$countryId,
                'geographygroup_id IN (?)' => $toRemove
            ));
        }

        // 4. Insert new mappings
        if (!empty($toAdd)) {
            foreach ($toAdd as $gId) {
                $db->insert('map_geographygroup_entity', array(
                    'geographygroup_id' => (int)$gId,
                    'entity_type'       => 'Country',
                    'entity_id'         => (int)$countryId,
                    'isactive'          => 1,
                    'createdby'         => $userId,
                    'createddate'       => $currentDate
                ));
            }
        }
    }
}
