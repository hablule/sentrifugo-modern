<?php

/**
 * Business Unit Model
 *
 * Manages organizational Business Units (e.g., USA Region, Europe Operations).
 * Refactored to comply with SSOT v6.4 (PHP 7.4 compatibility, Zend_Db_Select).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Businessunit extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_businessunit';

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
     * 
     * @param bool $activeOnly
     * @param int|null $excludeId ID to exclude (prevents self-parenting loops)
     * @return array
     */
    public function getPairs($activeOnly = true, $excludeId = null)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
        if ($activeOnly) {
            $select->where('isactive = ?', 1);
        }
        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
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
                        $searchQuery .= " b.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'head_name') {
                        $searchQuery .= " e.userfullname LIKE '%" . trim($val) . "%' AND ";
                    } else {
                        $searchQuery .= " b." . $key . " LIKE '%" . trim($val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // SSOT Mandatory: 'action' => 'Action' must be the first element
        $tableHeaders = array(
            'action'        => 'Action',
            'code'          => 'Code',
            'name'          => 'Name',
            'parent_type'   => 'Parent Type', // NEW
            'parent_name'   => 'Parent Name', // NEW
            'head_name'     => 'Unit Head',
            'status_text'   => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'businessunit', // Singular
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Business Units',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    /**
     * Returns the raw Zend_Db_Select object for the Grid.
     * Implements Dynamic Parent Resolution via COALESCE and LEFT JOINs.
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
            $by = 'b.isactive';
        } elseif ($by === 'head_name') {
            $by = 'e.userfullname';
        } elseif ($by === 'parent_name') {
            $by = 'COALESCE(comp.name, psbu.name)';
        } elseif ($by === 'parent_type') {
            $by = 'b.company_id'; // Rough proxy for sorting by type
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('b' => $this->_name),
                array(
                    'id' => 'b.id', // SSOT Aliasing requirement
                    'code',
                    'name',
                    // Dynamic Parent Resolution
                    'parent_type' => new Zend_Db_Expr("
                        CASE 
                            WHEN b.company_id IS NOT NULL THEN 'Company'
                            WHEN b.parent_id IS NOT NULL THEN 'Business Unit'
                            ELSE 'None' 
                        END
                    "),
                    'parent_name' => new Zend_Db_Expr("COALESCE(comp.name, psbu.name, '—')"),
                    'status_text' => new Zend_Db_Expr("CASE WHEN b.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            // Join for Unit Head
            ->joinLeft(
                array('e' => 'main_employee'), // Updated to singular SSOT name
                'b.unithead_id = e.id',
                array('head_name' => 'e.userfullname')
            )
            // Left Joins for ALL possible parents in this tier
            ->joinLeft(
                array('comp' => 'main_company'),
                'b.company_id = comp.id',
                array()
            )
            ->joinLeft(
                array('psbu' => $this->_name), // Self-join for recursive parent
                'b.parent_id = psbu.id',
                array()
            )
            ->where('b.isactive != ?', 5);

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
     * Evaluates if the Business Unit is currently referenced by other active entities.
     * 
     * @param int $id
     * @return bool True if record is actively used and cannot be deleted
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();

        // Check Employee routing assignments (main_employees_summary or similar)
        // Adjust table/column names if your employee org assignment structure differs
        $select = $db->select()
            ->from('main_employees_summary', array('id'))
            ->where('businessunit_id = ?', (int)$id)
            ->where('isactive = ?', 1);

        $row = $db->fetchRow($select);
        if ($row) return true;

        // Check Distribution List routing assignments
        $distSelect = $db->select()
            ->from('main_distribution_list', array('id'))
            ->where('businessunit_id = ?', (int)$id)
            ->where('isactive = ?', 1);

        $distRow = $db->fetchRow($distSelect);
        return (bool)$distRow;
    }
}
