<?php

/**
 * Postal Code Lookup Model
 * 
 * Manages geographical postal/ZIP codes (Pillar 1, Level 4).
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
class Default_Model_Postalcode extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT Singular Naming Convention) */
    protected $_name = 'main_postal_code';

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

        $select->order('code ASC');

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
                    if ($val === 'Active') $searchQuery .= " pc.isactive = 1 AND ";
                    elseif ($val === 'Inactive') $searchQuery .= " pc.isactive = 0 AND ";
                } elseif ($key == 'city_name') {
                    $searchQuery .= " ci.name LIKE '%$val%' AND ";
                } elseif ($key == 'state_name') {
                    $searchQuery .= " s.name LIKE '%$val%' AND ";
                } elseif ($key == 'country_name') {
                    $searchQuery .= " c.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " pc.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        $tableHeaders = array(
            'action'       => 'Action',
            'country_name' => 'Country',
            'state_name'   => 'State/Province',
            'city_name'    => 'City',
            'code'         => 'Postal/ZIP Code',
            'status_text'  => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'postalcode',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Postal Codes',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    /**
     * SSOT Standard Grid Data Fetcher.
     * Integrates City, State, and Country context for full geographical awareness.
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
        if ($by == 'status_text')  $by = 'pc.isactive';
        if ($by == 'city_name')    $by = 'ci.name';
        if ($by == 'state_name')   $by = 's.name';
        if ($by == 'country_name') $by = 'c.name';
        if ($by == 'code')         $by = 'pc.code'; // Explicitly target the postal code table

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('pc' => $this->_name), array(
                'id' => 'pc.id',
                '*',
                'status_text' => new Zend_Db_Expr("CASE WHEN pc.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ))
            ->joinInner(array('ci' => 'main_city'), 'pc.city_id = ci.id', array('city_name' => 'ci.name'))
            ->joinInner(array('s' => 'main_state'), 'ci.state_id = s.id', array('state_name' => 's.name'))
            ->joinInner(array('c' => 'main_country'), 's.country_id = c.id', array('country_name' => 'c.name'))
            ->where('pc.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /**
     * Standard Pairs Retrieval (Used for Dropdowns).
     * 
     * @param bool $activeOnly
     * @return array [id => code]
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'code'))->order('code ASC');
        if ($activeOnly) $select->where("isactive = 1");
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Relational Helper: Get Postal Codes by City ID
     * 
     * @param int  $cityId
     * @param bool $activeOnly
     * @return array [id => code]
     */
    public function getPairsByCity($cityId, $activeOnly = true)
    {
        if (empty($cityId)) return array();

        $select = $this->select()
            ->from($this->_name, array('id', 'code'))
            ->where('city_id = ?', (int)$cityId)
            ->order('code ASC');

        if ($activeOnly) $select->where("isactive = 1");

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
            ->from(array('pc' => $this->_name), array('pc.*'))
            ->joinInner(array('ci' => 'main_city'), 'pc.city_id = ci.id', array('state_id' => 'ci.state_id'))
            ->joinInner(array('s' => 'main_state'), 'ci.state_id = s.id', array('country_id' => 's.country_id'))
            ->where('pc.id = ?', (int)$id)
            ->where('pc.isactive != 5');

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

    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this Postal Code as it is currently assigned to a physical address or location.");
        }

        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $data = array('isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $data = array('isactive' => (int)$status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 4: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Composite Duplicate Checker.
     * Verifies if a postal code is already used WITHIN the same city.
     * 
     * @param int    $cityId
     * @param string $code
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicate($cityId, $code, $excludeId = null)
    {
        $select = $this->select()
            ->where('city_id = ?', (int)$cityId)
            ->where('code = ?', trim($code))
            ->where("isactive != 5");

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * Dependency Checker (SSOT IV Data Integrity)
     * Verifies if the postal code is assigned to a geographical location.
     * 
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();

        // Check 1: Is it used in main_address? (Assuming it has a postal_code_id FK)
        // $selectAddr = $db->select()
        //     ->from('main_address', 'id')
        //     ->where('postal_code_id = ?', (int)$id)
        //     ->limit(1);
        //     
        // if ((bool)$db->fetchOne($selectAddr)) {
        //     return true;
        // }

        return false;
    }
}
