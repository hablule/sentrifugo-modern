<?php

/**
 * SBU Business Domain Mapping Model
 *
 * Manages the many-to-many relationships between Strategic Business Units and Business Domains.
 * Refactored to comply with SSOT v6.2 (PHP 7.4 compatibility, Zend_Db_Select, Composite PK Handling).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_SbuBusinessdomain extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name */
    protected $_name = 'map_sbu_businessdomain';

    /** @var array Composite Primary key */
    protected $_primary = ['sbunit_id', 'businessdomain_id'];

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Fetch a single record by its concatenated composite key.
     * 
     * @param string $id Format: "sbunitId_businessdomainId" (e.g., "1_5")
     * @return array|null
     */
    public function getRecordById($id)
    {
        $keys = explode('_', (string)$id);
        if (count($keys) !== 2) {
            return null;
        }

        $select = $this->select()
            ->where('sbunit_id = ?', (int)$keys[0])
            ->where('businessdomain_id = ?', (int)$keys[1])
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
            $select->where('isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . trim($search_str) . '%');
        }

        if ($limit !== null) {
            $select->limit((int)$limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Generates a flat ID => Name array for Select dropdowns.
     * For a mapping table, we concatenate the names of the two entities.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['m' => $this->_name],
                [
                    'id' => new Zend_Db_Expr("CONCAT(m.sbunit_id, '_', m.businessdomain_id)"),
                    'name' => new Zend_Db_Expr("CONCAT(sbu.name, ' - ', dom.name)")
                ]
            )
            ->joinInner(['sbu' => 'main_businessunit'], 'm.sbunit_id = sbu.id', [])
            ->joinInner(['dom' => 'main_businessdomain'], 'm.businessdomain_id = dom.id', [])
            ->order('sbu.name ASC');

        if ($activeOnly) {
            $select->where('m.isactive = ?', 1);
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
        $searchArray = [];

        if (!empty($searchData) && $searchData !== 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key === 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " m.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'sbu_name') {
                        $searchQuery .= " sbu.name LIKE '%" . trim($val) . "%' AND ";
                    } elseif ($key === 'domain_name') {
                        $searchQuery .= " dom.name LIKE '%" . trim($val) . "%' AND ";
                    } else {
                        $searchQuery .= " m." . $key . " LIKE '%" . trim($val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // SSOT Mandatory: 'action' => 'Action' must be the first element
        $tableHeaders = [
            'action'      => 'Action',
            'sbu_name'    => 'Business Unit',
            'domain_name' => 'Business Domain',
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
            'objectname'     => 'sbubusinessdomain',
            'extra'          => [],
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'SBU Domain Engagements',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
            ]
        ];
    }

    /**
     * Returns the raw Zend_Db_Select object for the Grid.
     * Generates a composite ID alias (`sbunitId_domainId`) for UI routing.
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
            $by = 'm.isactive';
        } elseif ($by === 'sbu_name') {
            $by = 'sbu.name';
        } elseif ($by === 'domain_name') {
            $by = 'dom.name';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['m' => $this->_name],
                [
                    // SSOT Aliasing requirement mapped to composite key
                    'id'          => new Zend_Db_Expr("CONCAT(m.sbunit_id, '_', m.businessdomain_id)"),
                    'description',
                    'status_text' => new Zend_Db_Expr("CASE WHEN m.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                ]
            )
            ->joinInner(['sbu' => 'main_businessunit'], 'm.sbunit_id = sbu.id', ['sbu_name' => 'sbu.name'])
            ->joinInner(['dom' => 'main_businessdomain'], 'm.businessdomain_id = dom.id', ['domain_name' => 'dom.name'])
            ->where('m.isactive != ?', 5);

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
     * Supports composite keys.
     * 
     * @param array $data
     * @param string|null $id Format: "sbunitId_businessdomainId"
     * @return string Last Insert ID (as composite string) or 'update'
     */
    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $currentDate = gmdate("Y-m-d H:i:s");

        if (!empty($id)) {
            $keys = explode('_', (string)$id);
            if (count($keys) === 2) {
                $data['modifiedby']   = $userId;
                $data['modifieddate'] = $currentDate;

                $where = [
                    'sbunit_id = ?'         => (int)$keys[0],
                    'businessdomain_id = ?' => (int)$keys[1]
                ];

                $this->update($data, $where);
                return 'update';
            }
        }

        $data['createdby']   = $userId;
        $data['createddate'] = $currentDate;
        $this->insert($data);

        // Return constructed composite ID for newly added record logging
        return $data['sbunit_id'] . '_' . $data['businessdomain_id'];
    }

    /* =================================================================================
     * GROUP 4: RECORD REMOVAL
     * ================================================================================= */

    /**
     * Safely soft-deletes a record by composite key.
     * 
     * @param string $id Format: "sbunitId_businessdomainId"
     * @return int Number of rows updated
     */
    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $keys = explode('_', (string)$id);
        if (count($keys) !== 2) return 0;

        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];

        $where = [
            'sbunit_id = ?'         => (int)$keys[0],
            'businessdomain_id = ?' => (int)$keys[1]
        ];

        return $this->update($data, $where);
    }

    /**
     * Toggles the Active (1) / Inactive (0) status by composite key.
     * 
     * @param string $id Format: "sbunitId_businessdomainId"
     * @param mixed $status
     * @return int Number of rows updated
     */
    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $keys = explode('_', (string)$id);
        if (count($keys) !== 2) return 0;

        $data = [
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];

        $where = [
            'sbunit_id = ?'         => (int)$keys[0],
            'businessdomain_id = ?' => (int)$keys[1]
        ];

        return $this->update($data, $where);
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS, & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * SSOT Baseline Signature.
     * For composite key tables, prefer checkMappingExists() below.
     * 
     * @param string $field
     * @param string $value
     * @param string|null $excludeId Composite ID
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim($value))
            ->where('isactive != ?', 5);

        if (!empty($excludeId)) {
            $keys = explode('_', (string)$excludeId);
            if (count($keys) === 2) {
                // Exclude this specific mapping pair
                $select->where('NOT (sbunit_id = ? AND businessdomain_id = ?)', (int)$keys[0], (int)$keys[1]);
            }
        }

        return $this->fetchRow($select);
    }

    /**
     * Verifies if a specific SBU to Domain mapping already exists.
     * 
     * @param int $sbunit_id
     * @param int $businessdomain_id
     * @param string|null $excludeId Composite ID to exclude during updates
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkMappingExists($sbunit_id, $businessdomain_id, $excludeId = null)
    {
        $select = $this->select()
            ->where('sbunit_id = ?', (int)$sbunit_id)
            ->where('businessdomain_id = ?', (int)$businessdomain_id)
            ->where('isactive != ?', 5);

        if (!empty($excludeId)) {
            $keys = explode('_', (string)$excludeId);
            if (count($keys) === 2) {
                $select->where('NOT (sbunit_id = ? AND businessdomain_id = ?)', (int)$keys[0], (int)$keys[1]);
            }
        }

        return $this->fetchRow($select);
    }

    /**
     * Dependency Check prior to deletion.
     * 
     * @param string $id Format: "sbunitId_businessdomainId"
     * @return bool True if record is actively used and cannot be deleted
     */
    public function checkInUse($id)
    {
        // Join/Mapping tables are usually safe to delete as they represent relationships,
        // not primary entities. Return false to allow deletion.
        return false;
    }

    /* =================================================================================
     * GROUP 6: JUNCTION TABLE SYNC (MANY-TO-MANY)
     * ================================================================================= */

    /**
     * Fetches a simple array of Business Domain IDs mapped to a specific SBU.
     * Used to pre-populate multiselect/checkbox elements in forms.
     * 
     * @param int $sbunit_id
     * @return array
     */
    public function getDomainIdsBySbu($sbunit_id)
    {
        $select = $this->select()
            ->from($this->_name, 'businessdomain_id')
            ->where('sbunit_id = ?', (int)$sbunit_id);

        return $this->getAdapter()->fetchCol($select);
    }

    /**
     * Fetches a simple array of SBU IDs mapped to a specific Business Domain.
     * Used to pre-populate multiselect/checkbox elements in forms.
     * 
     * @param int $domain_id
     * @return array
     */
    public function getSbuIdsByDomain($domain_id)
    {
        $select = $this->select()
            ->from($this->_name, 'sbunit_id')
            ->where('businessdomain_id = ?', (int)$domain_id);

        return $this->getAdapter()->fetchCol($select);
    }
}
