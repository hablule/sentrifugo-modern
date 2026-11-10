<?php

/**
 * Employment Type Model
 * 
 * Manages the employment contracts and global policy switches 
 * (e.g., Full-Time, Contractor, Intern) and their eligibility rules.
 * Strictly adheres to SSOT v6.7.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Sentrifugo AI Co-Developer
 * @version    Sentrifugo v3.2
 */
class Default_Model_Employmenttype extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT Singular Naming Convention) */
    protected $_name = 'main_employment_type';

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
        $searchArray = [];

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                // Safely translate UI Status Text to DB Integers
                if ($key == 'status_text') {
                    if ($val === 'Active') {
                        $searchQuery .= " t.isactive = 1 AND ";
                    } elseif ($val === 'Inactive') {
                        $searchQuery .= " t.isactive = 0 AND ";
                    }
                } elseif (in_array($key, ['benefits_text', 'leave_text', 'overtime_text', 'timesheet_text', 'term_text'])) {
                    // Reverse map UI strings to respective boolean columns
                    $col = str_replace('_text', '', $key);
                    $dbCol = 't.' . ($col == 'benefits' ? 'is_benefits_eligible' : ($col == 'leave' ? 'is_leave_eligible' : ($col == 'overtime' ? 'is_overtime_eligible' : ($col == 'timesheet' ? 'requires_timesheet' : 'has_fixed_term'))));
                    if ($val === 'Yes') {
                        $searchQuery .= " $dbCol = 1 AND ";
                    } elseif ($val === 'No') {
                        $searchQuery .= " $dbCol = 0 AND ";
                    }
                } else {
                    $searchQuery .= " t.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // Exact column aliases defined in getGridData()
        $tableHeaders = [
            'action'         => 'Action',
            'name'           => 'Type Name',
            'code'           => 'Code',
            'benefits_text'  => 'Benefits',
            'leave_text'     => 'Leaves',
            'overtime_text'  => 'Overtime',
            'timesheet_text' => 'Timesheets',
            'term_text'      => 'Fixed Term',
            'status_text'    => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'employmenttype',
            'tableheader'    => $tableHeaders,
            'extra'          => [],
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Employment Status',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
            ]
        ];
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
        // Alias translation for sorting
        if ($by == 'status_text') $by = 't.isactive';
        if ($by == 'benefits_text') $by = 't.is_benefits_eligible';
        if ($by == 'leave_text') $by = 't.is_leave_eligible';
        if ($by == 'overtime_text') $by = 't.is_overtime_eligible';
        if ($by == 'timesheet_text') $by = 't.requires_timesheet';
        if ($by == 'term_text') $by = 't.has_fixed_term';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['t' => $this->_name], [
                'id' => 't.id',
                '*',
                'benefits_text'  => new Zend_Db_Expr("CASE WHEN t.is_benefits_eligible = 1 THEN 'Yes' ELSE 'No' END"),
                'leave_text'     => new Zend_Db_Expr("CASE WHEN t.is_leave_eligible = 1 THEN 'Yes' ELSE 'No' END"),
                'overtime_text'  => new Zend_Db_Expr("CASE WHEN t.is_overtime_eligible = 1 THEN 'Yes' ELSE 'No' END"),
                'timesheet_text' => new Zend_Db_Expr("CASE WHEN t.requires_timesheet = 1 THEN 'Yes' ELSE 'No' END"),
                'term_text'      => new Zend_Db_Expr("CASE WHEN t.has_fixed_term = 1 THEN 'Yes' ELSE 'No' END"),
                'status_text'    => new Zend_Db_Expr("CASE WHEN t.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->where('t.isactive != 5');

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
     * @return array [id => name]
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'name'])->order('name ASC');
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
            $this->update($data, ['id = ?' => (int)$id]);
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
            throw new Exception("Cannot delete this Employment Type as it is currently assigned to an employee position history.");
        }

        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
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

        $data = [
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
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
     * Dependency Checker (SSOT IV)
     * Checks if this Employment Type is actively used in the Position History table.
     * 
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        // Using main_empposition per our agreed structure
        $db = $this->getAdapter();
        $select = $db->select()
            ->from('main_empposition', 'id')
            ->where('employment_type_id = ?', (int)$id) // Based on the new Phase 1 schema mapping
            ->where('isactive = ?', 'Y')
            ->limit(1);

        return (bool)$db->fetchOne($select);
    }
}
