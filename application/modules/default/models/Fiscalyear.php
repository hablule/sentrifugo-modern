<?php

/**
 * Fiscal Year Model
 *
 * Manages the top-level financial/operational time groupings.
 * Complies with SSOT v6.8 (Zend_Db_Select, Singular Naming, Master-Detail JSON Pattern).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Fiscalyear extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_fiscalyear';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 1: DATA DISPLAY & RETRIEVAL
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
            $select->where("$search_val LIKE ?", '%' . trim((string)$search_str) . '%');
        }

        if ($limit !== null) {
            $select->limit((int)$limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    public function getPairs($activeOnly = true, $calendarId = null)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('start_date DESC');
        if ($activeOnly) {
            $select->where('isactive = ?', 1);
        }
        if ($calendarId !== null) {
            $select->where('calendar_id = ?', (int)$calendarId);
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
                        $searchQuery .= " y.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'calendar_name') {
                        $searchQuery .= " c.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'start_date' || $key === 'end_date') {
                        $searchQuery .= " y." . $key . " = '" . sapp_Global::change_date(urldecode($val), 'database') . "' AND ";
                    } else {
                        $searchQuery .= " y." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        $tableHeaders = array(
            'action'        => 'Action',
            'name'          => 'Fiscal Year',
            'calendar_name' => 'Calendar',
            'start_date'    => 'Start Date',
            'end_date'      => 'End Date',
            'status_text'   => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'fiscalyear',
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Fiscal Years',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive')),
                'start_date'  => array('type' => 'datepicker'),
                'end_date'    => array('type' => 'datepicker')
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'status_text') {
            $by = 'y.isactive';
        } elseif ($by === 'calendar_name') {
            $by = 'c.name';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('y' => $this->_name),
                array(
                    'id' => 'y.id',
                    'name',
                    'start_date'  => new Zend_Db_Expr("DATE_FORMAT(y.start_date, '" . DATEFORMAT_MYSQL . "')"),
                    'end_date'    => new Zend_Db_Expr("DATE_FORMAT(y.end_date, '" . DATEFORMAT_MYSQL . "')"),
                    'status_text' => new Zend_Db_Expr("CASE WHEN y.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            ->joinInner(
                array('c' => 'main_calendar'),
                'y.calendar_id = c.id',
                array('calendar_name' => 'c.name')
            )
            ->where('y.isactive != ?', 5);

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /* =================================================================================
     * GROUP 2: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $currentDate = gmdate("Y-m-d H:i:s");

        // Format dates safely before insertion
        if (isset($data['start_date'])) {
            $data['start_date'] = sapp_Global::change_date($data['start_date'], 'database');
        }
        if (isset($data['end_date'])) {
            $data['end_date'] = sapp_Global::change_date($data['end_date'], 'database');
        }

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
     * GROUP 3: RECORD REMOVAL
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
     * GROUP 4: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim((string)$value))
            ->where('isactive != ?', 5);

        if (!empty($excludeId)) {
            $select->where('id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        $db = $this->getAdapter();

        // Check if the Fiscal Year has any active child Periods
        $periodSelect = $db->select()
            ->from('main_period', array('id'))
            ->where('fiscalyear_id = ?', (int)$id)
            ->where('isactive != ?', 5);

        $row = $db->fetchRow($periodSelect);
        return (bool)$row;
    }
}
