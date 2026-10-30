<?php

/**
 * Company Business Domain Mapping Model
 *
 * Manages the many-to-many relationships between top-level Companies and Business Domains.
 * Refactored to comply with SSOT v6.4 (PHP 7.4 compatibility, Zend_Db_Select, Composite PK Handling).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_CompanyBusinessdomain extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name */
    protected $_name = 'map_company_businessdomain';

    /** @var array Composite Primary key */
    protected $_primary = array('company_id', 'businessdomain_id');

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Fetch a single record by its concatenated composite key.
     * 
     * @param string $id Format: "companyId_businessdomainId" (e.g., "1_5")
     * @return array|null
     */
    public function getRecordById($id)
    {
        $keys = explode('_', (string)$id);
        if (count($keys) !== 2) {
            return null;
        }

        $select = $this->select()
            ->where('company_id = ?', (int)$keys[0])
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
                array('m' => $this->_name),
                array(
                    'id' => new Zend_Db_Expr("CONCAT(m.company_id, '_', m.businessdomain_id)"),
                    'name' => new Zend_Db_Expr("CONCAT(comp.name, ' - ', dom.name)")
                )
            )
            ->joinInner(array('comp' => 'main_company'), 'm.company_id = comp.id', array())
            ->joinInner(array('dom' => 'main_businessdomain'), 'm.businessdomain_id = dom.id', array())
            ->order('comp.name ASC');

        if ($activeOnly) {
            $select->where('m.isactive = ?', 1);
        }

        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Builds the Grid configuration array for the UI.
     * SSOT v6.4: 'action' MUST be first. No 'add' flag.
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
                        $searchQuery .= " m.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'company_name') {
                        $searchQuery .= " comp.name LIKE '%" . trim($val) . "%' AND ";
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
        $tableHeaders = array(
            'action'       => 'Action',
            'company_name' => 'Company',
            'domain_name'  => 'Business Domain',
            'description'  => 'Description',
            'status_text'  => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'companybusinessdomain', // Controller namespace target
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Company Domain Engagements',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    /**
     * Returns the raw Zend_Db_Select object for the Grid.
     * Generates a composite ID alias (`companyId_domainId`) for UI routing.
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
        } elseif ($by === 'company_name') {
            $by = 'comp.name';
        } elseif ($by === 'domain_name') {
            $by = 'dom.name';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('m' => $this->_name),
                array(
                    // SSOT Aliasing requirement mapped to composite key
                    'id'          => new Zend_Db_Expr("CONCAT(m.company_id, '_', m.businessdomain_id)"),
                    'description',
                    'status_text' => new Zend_Db_Expr("CASE WHEN m.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            ->joinInner(array('comp' => 'main_company'), 'm.company_id = comp.id', array('company_name' => 'comp.name'))
            ->joinInner(array('dom' => 'main_businessdomain'), 'm.businessdomain_id = dom.id', array('domain_name' => 'dom.name'))
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
     * @param string|null $id Format: "companyId_businessdomainId"
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

                $where = array(
                    'company_id = ?'        => (int)$keys[0],
                    'businessdomain_id = ?' => (int)$keys[1]
                );

                $this->update($data, $where);
                return 'update';
            }
        }

        $data['createdby']   = $userId;
        $data['createddate'] = $currentDate;
        $this->insert($data);

        // Return constructed composite ID for newly added record logging
        return $data['company_id'] . '_' . $data['businessdomain_id'];
    }

    /* =================================================================================
     * GROUP 4: RECORD REMOVAL
     * ================================================================================= */

    /**
     * Safely soft-deletes a record by composite key.
     * 
     * @param string $id Format: "companyId_businessdomainId"
     * @return int Number of rows updated
     */
    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $keys = explode('_', (string)$id);
        if (count($keys) !== 2) return 0;

        $data = array(
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );

        $where = array(
            'company_id = ?'        => (int)$keys[0],
            'businessdomain_id = ?' => (int)$keys[1]
        );

        return $this->update($data, $where);
    }

    /**
     * Toggles the Active (1) / Inactive (0) status by composite key.
     * 
     * @param string $id Format: "companyId_businessdomainId"
     * @param mixed $status
     * @return int Number of rows updated
     */
    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $keys = explode('_', (string)$id);
        if (count($keys) !== 2) return 0;

        $data = array(
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );

        $where = array(
            'company_id = ?'        => (int)$keys[0],
            'businessdomain_id = ?' => (int)$keys[1]
        );

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
                $select->where('NOT (company_id = ? AND businessdomain_id = ?)', (int)$keys[0], (int)$keys[1]);
            }
        }

        return $this->fetchRow($select);
    }

    /**
     * Verifies if a specific Company to Domain mapping already exists.
     * 
     * @param int $company_id
     * @param int $businessdomain_id
     * @param string|null $excludeId Composite ID to exclude during updates
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkMappingExists($company_id, $businessdomain_id, $excludeId = null)
    {
        $select = $this->select()
            ->where('company_id = ?', (int)$company_id)
            ->where('businessdomain_id = ?', (int)$businessdomain_id)
            ->where('isactive != ?', 5);

        if (!empty($excludeId)) {
            $keys = explode('_', (string)$excludeId);
            if (count($keys) === 2) {
                $select->where('NOT (company_id = ? AND businessdomain_id = ?)', (int)$keys[0], (int)$keys[1]);
            }
        }

        return $this->fetchRow($select);
    }

    /**
     * SSOT v6.4 Model Encapsulation: 
     * Fetches a simple array of Business Domain IDs mapped to a specific Company.
     * Used to pre-populate multiselect/checkbox elements in the Company form.
     * 
     * @param int $company_id
     * @return array Array of domain IDs
     */
    public function getDomainIdsByCompany($company_id, $activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, 'businessdomain_id')
            ->where('company_id = ?', (int)$company_id)
            ->where('isactive != ?', 5); // Optional: Include if you want to hide soft-deleted mappings from the UI            
            

        if($activeOnly) {
            $select->where('isactive = ?', 1);
        }
        
        return $this->getAdapter()->fetchCol($select);
    }

    /**
     * Dependency Check prior to deletion.
     * 
     * @param string $id Format: "companyId_businessdomainId"
     * @return bool True if record is actively used and cannot be deleted
     */
    public function checkInUse($id)
    {
        // Join/Mapping tables are usually safe to delete as they represent relationships,
        // not primary entities. Return false to allow deletion.
        return false;
    }
}
