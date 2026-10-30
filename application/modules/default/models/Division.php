<?php

/**
 * Division Model
 *
 * Manages organizational Divisions.
 * Complies with SSOT v6.5 (PHP 7.4 safe, Zend_Db_Select, Singular Naming, Dynamic Parent Resolution).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Division extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_division';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getRecordById($id)
    {
        $select = $this->select()
            ->where('id = ?', (int)$id)
            ->where('isactive != ?', 5);

        $row = $this->fetchRow($select);
        return $row ? $row->toArray() : null;
    }

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
     * Includes $excludeId to prevent self-parenting loops in Poly-Hierarchies.
     * 
     * @param bool $activeOnly
     * @param int|null $excludeId ID to exclude from results
     * @return array
     */
    public function getPairs($activeOnly = true, $excludeId = null)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
        if ($activeOnly) {
            $select->where('isactive = ?', 1);
        }
        if ($excludeId !== null) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->getAdapter()->fetchPairs($select);
    }

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
                    } elseif ($key === 'representative_name') {
                        $searchQuery .= " e.userfullname LIKE '%" . trim($val) . "%' AND ";
                    } elseif ($key === 'parent_name') {
                        // Search across all possible resolved parents
                        $searchQuery .= " (comp.name LIKE '%" . trim($val) . "%' OR sbu.name LIKE '%" . trim($val) . "%' OR pdiv.name LIKE '%" . trim($val) . "%') AND ";
                    } else {
                        $searchQuery .= " d." . $key . " LIKE '%" . trim($val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // SSOT Mandatory: 'action' => 'Action' must be the first element
        $tableHeaders = array(
            'action'              => 'Action',
            'code'                => 'Code',
            'name'                => 'Division Name',
            'parent_type'         => 'Parent Level',
            'parent_name'         => 'Parent Name',
            'representative_name' => 'Representative',
            'status_text'         => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'division',
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Divisions',
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
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'status_text') {
            $by = 'd.isactive';
        } elseif ($by === 'representative_name') {
            $by = 'e.userfullname';
        } elseif ($by === 'parent_name') {
            $by = 'COALESCE(comp.name, sbu.name, pdiv.name)';
        } elseif ($by === 'parent_type') {
            $by = 'd.company_id'; // Proxy sorting
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('d' => $this->_name),
                array(
                    'id' => 'd.id',
                    'code',
                    'name',
                    'parent_type' => new Zend_Db_Expr("
                        CASE 
                            WHEN d.company_id IS NOT NULL THEN 'Company'
                            WHEN d.sbunit_id IS NOT NULL THEN 'Business Unit'
                            WHEN d.parent_id IS NOT NULL THEN 'Division'
                            ELSE 'Orphaned' 
                        END
                    "),
                    'parent_name' => new Zend_Db_Expr("COALESCE(comp.name, sbu.name, pdiv.name, '—')"),
                    'status_text' => new Zend_Db_Expr("CASE WHEN d.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            // Representative
            ->joinLeft(
                array('e' => 'main_employee'),
                'd.representative_id = e.id',
                array('representative_name' => 'e.userfullname')
            )
            // Poly-Hierarchical Parents
            ->joinLeft(array('comp' => 'main_company'), 'd.company_id = comp.id', array())
            ->joinLeft(array('sbu' => 'main_businessunit'), 'd.sbunit_id = sbu.id', array())
            ->joinLeft(array('pdiv' => $this->_name), 'd.parent_id = pdiv.id', array()) // Self-join
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

    public function checkInUse($id)
    {
        $db = $this->getAdapter();

        // 1. Check if used as a parent by another Division
        $divSelect = $this->select()
            ->where('parent_id = ?', (int)$id)
            ->where('isactive != ?', 5);
        if ($this->fetchRow($divSelect)) return true;

        // 2. Check if used as a parent by a Department
        $deptSelect = $db->select()
            ->from('main_department', array('id'))
            ->where('division_id = ?', (int)$id)
            ->where('isactive != ?', 5);
        if ($db->fetchRow($deptSelect)) return true;

        // 3. Check if mapped to a Distribution List
        $distSelect = $db->select()
            ->from('main_distribution_list', array('id'))
            ->where('division_id = ?', (int)$id)
            ->where('isactive = ?', 1);
        if ($db->fetchRow($distSelect)) return true;

        // 4. Check if mapped to Employee Routing
        $empSelect = $db->select()
            ->from('main_employee', array('id'))
            ->where('division_id = ?', (int)$id) // Adjust if employee-division link differs
            ->where('isactive != ?', 5);
        if ($db->fetchRow($empSelect)) return true;

        return false;
    }
}
