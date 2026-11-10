<?php

/**
 * Holiday Date Model (Occurrence Header)
 *
 * Manages the Business Truth of generated holiday blocks and immutable policy snapshots.
 * Complies with SSOT v6.7.7 (Singular Naming, Zend_Db_Select, Structured Method Groups).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Holidaydate extends Zend_Db_Table_Abstract
{
    /* --------------------------------------------------------------
     * Attributes (Visibility -> Alphabetical)
     * -------------------------------------------------------------- */

    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_holiday_date';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Fetch all records with optional filtering and pagination.
     * 
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $search_val
     * @param string|null $search_str
     * @param int|string|null $activeStatus
     * @return array
     */
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

    /**
     * Builds the Grid configuration array for the UI.
     * SSOT v6.7.7: 'action' MUST be first. No 'add' flag passed.
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
                        $searchQuery .= " hd.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'holiday_name') {
                        $searchQuery .= " h.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'start_date' || $key === 'end_date') {
                        $searchQuery .= " hd." . $key . " = '" . sapp_Global::change_date(urldecode($val), 'database') . "' AND ";
                    } else {
                        $searchQuery .= " hd." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // SSOT Mandatory: 'action' => 'Action' must be the first element
        $tableHeaders = [
            'action'             => 'Action',
            'holiday_name'       => 'Holiday Policy',
            'start_date'         => 'Start Date',
            'end_date'           => 'End Date',
            'duration_days'      => 'Days',
            'work_policy'        => 'Work Policy',
            'payroll_multiplier' => 'Multiplier',
            'status_text'        => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'holidaydate',
            'extra'          => [],
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Holiday Calendar',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']],
                'start_date'  => ['type' => 'datepicker'],
                'end_date'    => ['type' => 'datepicker']
            ]
        ];
    }

    /**
     * Returns the raw Zend_Db_Select object for the Grid.
     * Joins to main_holiday to pull master policy descriptors.
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
            $by = 'hd.isactive';
        } elseif ($by === 'holiday_name') {
            $by = 'h.name';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['hd' => $this->_name],
                [
                    'id' => 'hd.id',
                    'start_date'  => new Zend_Db_Expr("DATE_FORMAT(hd.start_date, '" . DATEFORMAT_MYSQL . "')"),
                    'end_date'    => new Zend_Db_Expr("DATE_FORMAT(hd.end_date, '" . DATEFORMAT_MYSQL . "')"),
                    'duration_days',
                    'work_policy',
                    'payroll_multiplier',
                    'status_text' => new Zend_Db_Expr("CASE WHEN hd.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                ]
            )
            ->joinInner(
                ['h' => 'main_holiday'],
                'hd.holiday_id = h.id',
                ['holiday_name' => 'h.name']
            )
            ->where('hd.isactive != ?', 5);

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /**
     * Generates a flat ID => Date String array for Select dropdowns.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['hd' => $this->_name],
                ['id' => 'hd.id', 'label' => new Zend_Db_Expr("CONCAT(h.name, ' (', hd.start_date, ')')")]
            )
            ->joinInner(['h' => 'main_holiday'], 'hd.holiday_id = h.id', [])
            ->order('hd.start_date DESC');

        if ($activeOnly) {
            $select->where('hd.isactive = ?', 1);
        }

        return $this->getAdapter()->fetchPairs($select);
    }

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
     * Fetches all discrete lines associated with this holiday occurrence header.
     * 
     * @param int $headerId
     * @return array
     */
    public function getLinesByHeaderId(int $headerId): array
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from('main_holiday_date_line')
            ->where('holiday_date_id = ?', $headerId)
            ->where('isactive != ?', 5)
            ->order('sequence_no ASC');

        return $db->fetchAll($select);
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
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

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
            $this->update($data, ['id = ?' => (int)$id]);
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = $currentDate;
        $this->insert($data);
        return (int) $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL
     * ================================================================================= */

    /**
     * Safely soft-deletes a header and cascades soft-delete to its child lines.
     * 
     * @param int $id
     * @return int Number of rows updated
     */
    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => $currentDate
        ];

        // Soft delete child lines
        $lineModel = new Default_Model_Holidaydateline();
        $lineModel->softDeleteLinesByHeaderId($id, $userId, $currentDate);

        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /**
     * Toggles the Active (1) / Inactive (0) status.
     * 
     * @param int $id
     * @param int|string $status
     * @return int Number of rows updated
     */
    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

        $data = [
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];

        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
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
            ->where("$field = ?", trim((string)$value))
            ->where('isactive != ?', 5);

        if (!empty($excludeId)) {
            $select->where('id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    /**
     * Checks if an occurrence already exists for a specific master policy and start date.
     * Implements the UNIQUE (holiday_id, start_date) constraint guard.
     * 
     * @param int $holidayId
     * @param string $startDate (Y-m-d)
     * @param int|null $excludeId
     * @return array|null
     */
    public function checkDuplicateGeneration(int $holidayId, string $startDate, ?int $excludeId = null)
    {
        $select = $this->select()
            ->where('holiday_id = ?', $holidayId)
            ->where('start_date = ?', $startDate)
            ->where('isactive != ?', 5);

        if (!empty($excludeId)) {
            $select->where('id != ?', $excludeId);
        }

        $row = $this->fetchRow($select);
        return $row ? $row->toArray() : null;
    }

    /**
     * Dependency Check prior to deletion.
     * Checks if this holiday date falls into a locked/closed payroll period.
     * 
     * @param int $id
     * @return bool True if date falls in a closed period and cannot be deleted
     */
    public function checkInUse($id)
    {
        $record = $this->getRecordById($id);
        if (!$record) return false;

        $db = $this->getAdapter();

        // Query if this holiday date intersects with a Closed period in map_period_control
        $select = $db->select()
            ->from(['pc' => 'map_period_control'], ['id'])
            ->joinInner(['p' => 'main_period'], 'pc.period_id = p.id', [])
            ->where('? BETWEEN p.start_date AND p.end_date', $record['start_date'])
            ->where("pc.status = 'Closed'")
            ->where('pc.isactive = ?', 1);

        $row = $db->fetchRow($select);
        return (bool)$row;
    }
}
