<?php

/**
 * Country-Nationality Mapping Model
 * 
 * Manages the Many-to-Many junction between Geographical Countries (main_country) 
 * and Legal Nationalities (main_nationality).
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
class Default_Model_CountryNationality extends Zend_Db_Table_Abstract
{
    /** @var string Table name */
    protected $_name = 'map_country_nationality';

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
            ->from(array('mcn' => $this->_name), array('mcn.*'))
            ->joinInner(array('c' => 'main_country'), 'mcn.country_id = c.id', array('country_name' => 'c.name'))
            ->joinInner(array('n' => 'main_nationality'), 'mcn.nationality_id = n.id', array('nationality_name' => 'n.name'))
            ->where('mcn.isactive != 5');

        if ($activeStatus !== null) {
            $select->where('mcn.isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            // Map search val to actual joined table columns
            if ($search_val == 'country_name') {
                $select->where("c.name LIKE ?", '%' . $search_str . '%');
            } elseif ($search_val == 'nationality_name') {
                $select->where("n.name LIKE ?", '%' . $search_str . '%');
            } else {
                $select->where("mcn.$search_val LIKE ?", '%' . $search_str . '%');
            }
        }

        $select->order(array('c.name ASC', 'n.name ASC'));

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
                    if ($val === 'Active') {
                        $searchQuery .= " mcn.isactive = 1 AND ";
                    } elseif ($val === 'Inactive') {
                        $searchQuery .= " mcn.isactive = 0 AND ";
                    }
                } elseif ($key == 'default_text') {
                    if ($val === 'Yes') {
                        $searchQuery .= " mcn.is_default = 1 AND ";
                    } elseif ($val === 'No') {
                        $searchQuery .= " mcn.is_default = 0 AND ";
                    }
                } elseif ($key == 'country_name') {
                    $searchQuery .= " c.name LIKE '%$val%' AND ";
                } elseif ($key == 'nationality_name') {
                    $searchQuery .= " n.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " mcn.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // Must match exact column aliases defined in getGridData()
        $tableHeaders = array(
            'action'           => 'Action',
            'country_name'     => 'Country (Geography)',
            'nationality_name' => 'Nationality (Jurisprudence)',
            'default_text'     => 'Is Primary',
            'status_text'      => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'countrynationality',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Country to Nationality Mappings',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text'  => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive')),
                'default_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Yes' => 'Yes', 'No' => 'No'))
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
        // Handle sorting alias translations
        if ($by == 'status_text') {
            $by = 'mcn.isactive';
        } elseif ($by == 'default_text') {
            $by = 'mcn.is_default';
        } elseif ($by == 'country_name') {
            $by = 'c.name';
        } elseif ($by == 'nationality_name') {
            $by = 'n.name';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('mcn' => $this->_name), array(
                'id' => 'mcn.id',
                '*',
                // Alias boolean columns for the UI Grid
                'status_text'  => new Zend_Db_Expr("CASE WHEN mcn.isactive = 1 THEN 'Active' ELSE 'Inactive' END"),
                'default_text' => new Zend_Db_Expr("CASE WHEN mcn.is_default = 1 THEN 'Yes' ELSE 'No' END")
            ))
            ->joinInner(array('c' => 'main_country'), 'mcn.country_id = c.id', array('country_name' => 'c.name'))
            ->joinInner(array('n' => 'main_nationality'), 'mcn.nationality_id = n.id', array('nationality_name' => 'n.name'))
            ->where('mcn.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /**
     * Standard Pairs Retrieval.
     * Note: Junction tables rarely use getPairs, but it satisfies the SSOT IV interface.
     * 
     * @param bool $activeOnly
     * @return array [id => combined name]
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('mcn' => $this->_name), array('id' => 'mcn.id'))
            ->joinInner(array('c' => 'main_country'), 'mcn.country_id = c.id', array())
            ->joinInner(array('n' => 'main_nationality'), 'mcn.nationality_id = n.id', array())
            ->columns(array('mapping_name' => new Zend_Db_Expr("CONCAT(c.name, ' - ', n.name)")))
            ->order(array('c.name ASC', 'n.name ASC'));

        if ($activeOnly) {
            $select->where("mcn.isactive = 1");
        }

        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Standard ID Retrieval with Joined Data.
     * 
     * @param int $id
     * @return array|null
     */
    public function getRecordById($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('mcn' => $this->_name), array('mcn.*'))
            ->joinInner(array('c' => 'main_country'), 'mcn.country_id = c.id', array('country_name' => 'c.name'))
            ->joinInner(array('n' => 'main_nationality'), 'mcn.nationality_id = n.id', array('nationality_name' => 'n.name'))
            ->where('mcn.id = ?', (int)$id)
            ->where('mcn.isactive != 5');

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
            throw new Exception("Cannot delete this mapping. It is currently acting as a dependency for an employee record.");
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
     * SSOT IV: Junction Table Duplicate Checker.
     * Verifies if a Country <-> Nationality combination already exists.
     * 
     * @param int      $countryId
     * @param int      $nationalityId
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicateMapping($countryId, $nationalityId, $excludeId = null)
    {
        $select = $this->select()
            ->where('country_id = ?', (int)$countryId)
            ->where('nationality_id = ?', (int)$nationalityId)
            ->where('isactive != 5');

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * @deprecated Use checkDuplicateMapping for junction tables.
     * Kept to satisfy SSOT IV interface mandates.
     */
    public function checkDuplicate($field, $value, $excludeId = null)
    {
        return null;
    }

    /**
     * Dependency Checker (SSOT IV Data Integrity)
     * Verifies if this specific mapping is actively assigned to an employee profile.
     * 
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        // Placeholder: Will return true once we rebuild the employee details table
        // to link to this specific junction ID instead of just the nationality ID.
        return false;
    }
}
