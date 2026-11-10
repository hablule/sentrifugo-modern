<?php

/**
 * User Role Model
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 * 
 **/
class Default_Model_Userrole extends Zend_Db_Table_Abstract
{
    protected $_name    = 'main_userrole';
    protected $_primary = 'userrole_id';

    /* ------------------------------------------------------------------
     * Core CRUD
     * ------------------------------------------------------------------ */

    /**
     * Save or Update User Role record
     *
     * @param array $data
     * @param int|null $id
     * @return int|string
     */
    public function saveRecord($data, $id = null)
    {
        if (empty($id)) {
            return $this->insert($data);
        }

        $this->update(
            $data,
            ['userrole_id = ?' => (int)$id]
        );
        return 'update';
    }

    /**
     * Fetch record by primary key
     *
     * @param int $id
     * @return array|null
     */
    public function getRecordById($id)
    {
        $select = $this->select()
            ->where('userrole_id = ?', (int)$id);

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /**
     * Soft delete record (Sentrifugo standard: isactive = 5)
     *
     * @param int $id
     * @return int
     */
    public function deleteRecord($id)
    {
        return $this->update(
            ['isactive' => '5'],
            ['userrole_id = ?' => (int)$id]
        );
    }

    /* ------------------------------------------------------------------
     * Grid & Listing (Sentrifugo ListView)
     * ------------------------------------------------------------------ */

    /**
     * Grid data provider
     */
    public function getUserRoleGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        $where = "ur.isactive != '5'";
        if (!empty($searchQuery)) {
            $where .= " AND " . $searchQuery;
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['ur' => $this->_name],
                [
                    'id' => 'ur.userrole_id',
                    'role_id' => 'ur.role_id',
                    'user_id' => 'ur.user_id',
                    'description' => 'ur.description',
                    'createdby' => 'ur.createdby',
                    'createddate' => 'ur.createddate',
                    'modifiedby' => 'ur.modifiedby',
                    'modifieddate' => 'ur.modifieddate',
                    'isactive' => 'ur.isactive',
                    'datefrom' => 'DATE_FORMAT(ur.datefrom,"' . DATEFORMAT_MYSQL . '")',
                    'dateto' => 'DATE_FORMAT(ur.dateto,"' . DATEFORMAT_MYSQL . '")',
                    'status_text' => new Zend_Db_Expr(
                        "CASE WHEN ur.isactive = 'Y' THEN 'Active' ELSE 'Inactive' END"
                    )
                ]
            )
            ->joinInner(
                ['r' => 'main_role'],
                'ur.role_id = r.id',
                ['rolename' => 'r.name']
            )
            ->joinInner(
                ['u' => 'main_users'],
                'ur.user_id = u.id',
                []
            )
            ->joinLeft(
                ['e' => 'main_employees'],
                'u.empid = e.id',
                [
                    'profileimg' => 'e.profileimg',
                    // Fallback name logic (COALESCE handles missing employee records)
                    'userfullname' => new Zend_Db_Expr("COALESCE(NULLIF(e.userfullname, ''), u.username)")
                ]
            )
            ->where($where)
            ->order("$by $sort")
            ->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /**
     * Functional Group: DataGrid & Listing Logic
     * Alphabetically: getGrid
     * 
     * Centralized grid data provider for the User Role assignment list view.
     * 
     * @param string $sort          ASC or DESC
     * @param string $by            Column to sort by
     * @param int    $perPage       Records per page
     * @param int    $pageNo        Current page number
     * @param string $searchData    JSON string of filter values
     * @param string $call          Type of call (e.g., 'ajaxcall')
     * @param string $dashboardcall Whether this is a dashboard widget
     * @return array
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        // 1. Process Search Filters from Grid
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (!empty($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key == 'status_text') {
                        // Map UI 'Active'/'Inactive' back to DB 'Y'/'N'
                        $searchQuery .= " ur.isactive like '%" . ($val == 'Active' ? 'Y' : 'N') . "%' AND ";
                    } elseif ($key == 'datefrom') {
                        $searchQuery .= " ur.datefrom >= '" . sapp_Global::change_date(urldecode($val), 'database') . "' AND ";
                    } elseif ($key == 'dateto') {
                        $searchQuery .= " ur.dateto <= '" . sapp_Global::change_date(urldecode($val), 'database') . "' AND ";
                    } else {
                        $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }

        // 2. Define Table Column Mappings [DB Alias => UI Label]
        $tableFields = [
            'action'        => 'Action',
            'userfullname'  => 'Full Name',
            'rolename'      => 'Role Name',
            'datefrom'      => 'Valid From',
            'dateto'        => 'Valid Until',
            'status_text'   => 'Status'
        ];

        // 3. Fetch Data using standard Sentrifugo paginated select
        // Note: getUserRoleGridData must be defined in your model (as generated previously)
        $tablecontent = $this->getUserRoleGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        // 4. Prepare Filters (Dropdowns for the grid header)
        $role_model = new Default_Model_Roles();
        $roles_list = $role_model->getRolesList('', false); // Fetch simple rolenames
        $role_filter = [];
        foreach ($roles_list as $r) {
            $role_filter[$r['name']] = $r['name'];
        }

        $dataTmp = [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tablecontent,
            'objectname'     => 'userrole', // Used for auto-building URLs: userrole/edit/id/X
            'extra'          => [],
            'tableheader'    => $tableFields,
            'jsGridFnName'   => 'getAjaxgridData', // Core Sentrifugo JS function
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'User Role',
            'dashboardcall'  => $dashboardcall,
            'call'           => $call,
            'search_filters' => [
                'datefrom' => [
                    'type' => 'datepicker'
                ],
                'dateto' => [
                    'type' => 'datepicker'
                ],
                'status_text' => [
                    'type'        => 'select',
                    'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']
                ],
                'rolename' => [
                    'type'        => 'select',
                    'filter_data' => ['' => 'All'] + $role_filter
                ]
            ]
        ];

        return $dataTmp;
    }

    /**
     * Get all user roles with pagination, search, and status mapping.
     * 
     * @param int|null    $limit         Number of records to fetch
     * @param int|null    $offset        Starting record index
     * @param string|null $search_val    Search category (user_name/role_name)
     * @param string|null $search_str    Search keyword
     * @param mixed|null  $activeStatus  Filter value from UI (1, 0, 'Y', or 'N')
     * @return array
     */
    public function getAllUserRoles($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        // 1. Input Mapping: Map UI filter values (1/0) to DB storage format (Y/N)
        if ($activeStatus !== null) {
            // If UI sends 1, '1', or 'Y', we query for 'Y'. Otherwise, we query for 'N'.
            $activeStatus = (in_array($activeStatus, ['1', 1, 'Y'], true)) ? 'Y' : 'N';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['ur' => $this->_name],
                [
                    '*', // Select all original columns (userrole_id, role_id, user_id, etc.)
                    // 2. Output Mapping: Transform 'Y'/'N' to 1/0 so Checkboxes are 'checked' automatically
                    'isactive' => new Zend_Db_Expr("IF(ur.isactive = 'Y', 1, 0)")
                ]
            )
            ->joinInner(
                ['r' => 'main_role'],
                'ur.role_id = r.id',
                ['rolename' => 'r.name']
            )
            ->joinInner(
                ['u' => 'main_users'],
                'ur.user_id = u.id',
                []
            )
            ->joinLeft(
                ['e' => 'main_employees'],
                'u.empid = e.id',
                [
                    'profileimg' => 'e.profileimg',
                    // Fallback name logic (COALESCE handles missing employee records)
                    'userfullname' => new Zend_Db_Expr("COALESCE(NULLIF(e.userfullname, ''), u.username)")
                ]
            );

        // Apply status filter based on mapped 'Y'/'N'
        if ($activeStatus !== null) {
            $select->where('ur.isactive = ?', $activeStatus);
        } else {
            // Sentrifugo standard: Exclude soft-deleted records (status 5)
            $select->where("ur.isactive != '5'");
        }

        // 3. Handle Search Filters
        if (!empty($search_val) && !empty($search_str)) {
            $search_str = trim($search_str);
            if ($search_val == 'user_name') {
                $select->where('e.userfullname LIKE ? OR u.username LIKE ?', '%' . $search_str . '%');
            } else if ($search_val == 'role_name') {
                $select->where('r.name LIKE ?', '%' . $search_str . '%');
            }
        }

        // 4. Handle Sorting & Pagination
        $select->order('e.userfullname ASC');

        if ($limit !== null && $limit !== '') {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /* ------------------------------------------------------------------
     * Validation Helpers
     * ------------------------------------------------------------------ */

    /**
     * Check if user already has a role (used during add/edit)
     *
     * @param int $userId
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkUserExists($userId, $excludeId = null)
    {
        $select = $this->select()
            ->where('user_id = ?', (int)$userId)
            ->where('isactive != ?', '5');

        if (!empty($excludeId)) {
            $select->where('userrole_id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    /**
     * Check if a user-role combination exists
     * @param int $userId
     * @param int $roleId
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     * 
     */
    public function checkUserRoleCombination($userId, $roleId, $excludeId = null)
    {
        $select = $this->select()
            ->where('user_id = ?', (int)$userId)
            ->where('role_id = ?', (int)$roleId);

        // If editing, ignore the current record
        if (!empty($excludeId)) {
            $select->where('userrole_id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    /* ------------------------------------------------------------------
     * Role Logic (Date-Aware, ACL-Friendly)
     * ------------------------------------------------------------------ */

    /**
     * Get active roles for a user (date & status aware)
     *
     * @param int $userId
     * @return array
     */
    public function getActiveRolesByUserId($userId)
    {
        $select = $this->select()
            ->where('user_id = ?', (int)$userId)
            ->where('isactive = ?', '1')
            ->where('datefrom <= CURDATE()')
            ->where('(dateto IS NULL OR dateto >= CURDATE())');

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Check if user has a specific active role
     * (ACL helper)
     *
     * @param int $userId
     * @param int $roleId
     * @return bool
     */
    public function userHasRole($userId, $roleId)
    {
        $select = $this->select()
            ->from($this->_name, ['userrole_id'])
            ->where('user_id = ?', (int)$userId)
            ->where('role_id = ?', (int)$roleId)
            ->where('isactive = ?', '1')
            ->where('datefrom <= CURDATE()')
            ->where('(dateto IS NULL OR dateto >= CURDATE())');

        return (bool)$this->fetchRow($select);
    }

    /**
     * Deactivate role assignment without deleting history
     *
     * @param int $userRoleId
     * @param int $loginUserId
     * @return int
     */
    public function deactivateUserRole($userRoleId, $loginUserId)
    {
        return $this->update(
            [
                'isactive'      => '0',
                'modifiedby'    => $loginUserId,
                'modifieddate'  => new Zend_Db_Expr('NOW()')
            ],
            ['userrole_id = ?' => (int)$userRoleId]
        );
    }
}
