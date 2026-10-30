<?php

/**
 * User Model
 * Manages System Users.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_User extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_users';
    protected $_primary = 'id';

    /* --------------------------------------------------------------
     * 1. Persistence & CRUD
     * -------------------------------------------------------------- */

    public function saveRecord($data, $id = null)
    {
        if (!empty($id)) {
            $this->update($data, array('id = ?' => (int)$id));
            return 'update';
        } else {
            $this->insert($data);
            return $this->getAdapter()->lastInsertId($this->_name);
        }
    }

    public function getRecordById($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('u' => $this->_name)
            )
            ->joinLeft(
                array('e' => 'main_employees'),
                'u.empid = e.id',
                array('employee_id' => 'id', 'userfullname', 'profileimg' => 'e.profileimg')
            )->where('u.id = ?', (int)$id);

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /**
     * Soft Delete (isactive = 5 for Users)
     */
    public function deleteRecord($id)
    {
        $data = array(
            'isactive'     => 5,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /**
     * hard Delete
     */
    public function distroyRecord($id)
    {
        return $this->delete(array('id = ?' => (int)$id));
    }

    /* --------------------------------------------------------------
     * 2. Data Display & Grid Retrieval
     * -------------------------------------------------------------- */

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (!empty($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key == 'status_text') {
                        // Map UI 'Active' to DB 1/0
                        $dbVal = ($val == 'Active') ? 1 : (($val == 'Inactive') ? 0 : $val);
                        $searchQuery .= " u.isactive LIKE '%" . $dbVal . "%' AND ";
                    } else {
                        $searchQuery .= " u." . $key . " LIKE '%" . $val . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }

        $tableHeaders = array(
            'action'        => 'Action',
            'username'      => 'User Name',
            'userfullname'  => 'Employee Name',
            'emailaddress'  => 'Email',
            'status_text'   => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent, // Returns Zend_Db_Select
            'objectname'     => 'user',
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'System Users',
            'dashboardcall'  => $dashboardcall,
            'call'           => $call,
            'search_filters' => array(
                'status_text' => array(
                    'type'        => 'select',
                    'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive')
                )
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        // Users table usually uses status 5 for deleted
        $where = "u.isactive != 5";

        if (!empty($searchQuery)) {
            $where .= " AND " . $searchQuery;
        }

        if ($by == 'status_text') $by = 'isactive';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('u' => $this->_name),
                array(
                    'id',
                    'username',
                    'emailaddress',
                    'isactive',
                    // Logic to show Active/Inactive based on 1/0
                    'status_text' => new Zend_Db_Expr("CASE WHEN u.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            ->joinLeft(
                array('e' => 'main_employees'),
                'u.empid = e.id',
                array('userfullname', 'profileimg' => 'e.profileimg')
            )
            ->where($where)
            ->order("$by $sort")
            ->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    public function getAllData($sort, $by, $limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        // Users table usually uses status 5 for deleted
        $where = "u.isactive != 5";

        $cardSearchQuery = '';
        if ($search_val && $search_str) {
            $cardSearchQuery = "$search_val LIKE '%$search_str%'";
        }

        if (!empty($cardSearchQuery)) {
            $where .= " AND " . $cardSearchQuery;
        }

        if ($by == 'status_text') $by = 'isactive';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('u' => $this->_name),
                array(
                    'id',
                    'username',
                    'emailaddress',
                    'isactive',
                    // Logic to show Active/Inactive based on 1/0
                    'status_text' => new Zend_Db_Expr("CASE WHEN u.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            ->joinLeft(
                array('e' => 'main_employees'),
                'u.empid = e.id',
                array('userfullname', 'profileimg' => 'e.profileimg')
            )
            ->where($where)
            ->order("$by $sort");

        if ($limit !== null && $limit !== '') {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /* --------------------------------------------------------------
     * 3. Helper Methods
     * -------------------------------------------------------------- */

    public function getActiveUsersForSelect()
    {
        return $this->getPairs(true);
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'username'));
        if ($activeOnly) {
            $select->where('isactive = ?', 1);
        }
        $select->order('username ASC');

        $results = $this->fetchAll($select);
        $data = array();
        foreach ($results as $row) {
            $data[$row['id']] = $row['username'];
        }
        return $data;
    }

    /**
     * Check duplicates for unique fields (username, code, email)
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

    /* --------------------------------------------------------------
     * 4: Specific Record Retrieval
     * -------------------------------------------------------------- */

    /**
     * Get Data by username
     * @param int $id
     * @return array
     */
    public function getUserByUserName($username, $activeOnly = true)
    {
        $select = $this->select()
            ->where('username = ?', (int)$username);

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : array();
    }

    /**
     * Get Data by Code
     * @param string $code
     * @return array
     */
    public function getUserByCode($code, $activeOnly = true)
    {
        $select = $this->select()
            ->where('code = ?', $code);

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : array();
    }

    /**
     * Get Data by ldapUserName
     * @param string $code
     * @return array
     */
    public function getUserByldapUserName($ldapUserName, $activeOnly = true)
    {
        $select = $this->select()
            ->where('ldapUserName = ?', $ldapUserName);

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : array();
    }


    /**
     * Get Data by Employee ID
     * @param string $code
     * @return array
     */
    public function getUserByEmployee($empid, $activeOnly = true)
    {
        $select = $this->select()
            ->where('empid = ?', $empid);

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : array();
    }

    /* --------------------------------------------------------------
     * 5: Integrity & Helper Logic
     * -------------------------------------------------------------- */

    public function checkUserName($username, $excludeId = null,  $activeOnly = true)
    {
        $select = $this->select()
            ->where('username = ?', trim($username));

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    public function checkldapUserName($ldapUserName, $excludeId = null,  $activeOnly = true)
    {
        $select = $this->select()
            ->where('ldapUserName = ?', trim($ldapUserName));

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    public function getTotalGroupCount($activeOnly = true)
    {
        $select = $this->select()
            ->from(
                $this->_name,
                array('cnt' => new Zend_Db_Expr('COUNT(id)'))
            );

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        $row = $this->fetchRow($select);
        return ($row) ? (int)$row['cnt'] : 0;
    }
}
