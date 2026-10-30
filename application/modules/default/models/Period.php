<?php

/**
 * Period Model
 *
 * Manages the specific time boundaries (e.g., Months, Quarters) within a Year.
 * Serves as the State Machine controller for linked Business Processes via map_period_control.
 * Complies with SSOT v6.8 (Zend_Db_Select, Singular Naming, Junction Table Sync).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Period extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_period';

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

    /**
     * Generates a flat ID => Name array for Select dropdowns.
     * 
     * @param bool $activeOnly
     * @param int|null $yearId Filter periods by a specific year
     * @return array
     */
    public function getPairs($activeOnly = true, $yearId = null)
    {
        $select = $this->select()->from($this->_name, ['id', 'name'])->order('start_date ASC');
        if ($activeOnly) {
            $select->where('isactive = ?', 1);
        }
        if ($yearId !== null) {
            $select->where('fiscalyear_id = ?', (int)$yearId);
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Builds the Grid configuration array for the UI.
     * Note: Requires $fiscalyearId to filter the grid to a specific fiscal year.
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $fiscalyearId = null)
    {
        $searchQuery = '';
        $searchArray = [];

        if (!empty($searchData) && $searchData !== 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key === 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " p.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'year_name') {
                        $searchQuery .= " y.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'start_date' || $key === 'end_date') {
                        $searchQuery .= " p." . $key . " = '" . sapp_Global::change_date(urldecode($val), 'database') . "' AND ";
                    } else {
                        $searchQuery .= " p." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        $tableHeaders = [
            'action'          => 'Action',
            'period_sequence' => 'Sequence',
            'name'            => 'Period Name',
            'year_name'       => 'Financial Year',
            'start_date'      => 'Start Date',
            'end_date'        => 'End Date',
            'status_text'     => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery, $fiscalyearId);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'period',
            'extra'          => [],
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Periods & Locks',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']],
                'start_date'  => ['type' => 'datepicker'],
                'end_date'    => ['type' => 'datepicker']
            ]
        ];
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '', $fiscalyearId = null)
    {
        // 1. Determine the sort order array
        $orderArray = [];

        if ($by === 'default_sequence') {
            // The specific requirement: Sort by Fiscal Year (Start Date or Name) first, THEN by Period Sequence
            // We sort year descending (newest year first), then periods ascending (Period 1, 2, 3...)
            $orderArray[] = "y.start_date DESC"; 
            $orderArray[] = "p.period_sequence ASC";
        } elseif ($by === 'status_text') {
            $orderArray[] = "p.isactive $sort";
        } elseif ($by === 'year_name') {
            $orderArray[] = "y.name $sort";
        } else {
            // Standard single-column sort
            $orderArray[] = "$by $sort";
        }        

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['p' => $this->_name],
                [
                    'id' => 'p.id',
                    'name',
                    'period_sequence', 
                    'start_date'  => new Zend_Db_Expr("DATE_FORMAT(p.start_date, '" . DATEFORMAT_MYSQL . "')"),
                    'end_date'    => new Zend_Db_Expr("DATE_FORMAT(p.end_date, '" . DATEFORMAT_MYSQL . "')"),
                    'status_text' => new Zend_Db_Expr("CASE WHEN p.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                ]
            )
            ->joinInner(
                ['y' => 'main_fiscalyear'],
                'p.fiscalyear_id = y.id',
                ['year_name' => 'y.name']
            )
            ->where('p.isactive != ?', 5);

        if (!empty($fiscalyearId)) {
            $select->where('p.fiscalyear_id = ?', (int)$fiscalyearId);
        }

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order($orderArray)->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /**
     * Fetches all periods associated with a specific Fiscal Year.
     * Ordered strictly by sequence.
     * 
     * @param int $fiscalYearId
     * @return array
     */
    public function getPeriodsByFiscalYear($fiscalYearId)
    {
        $select = $this->select()
            ->where('fiscalyear_id = ?', (int)$fiscalYearId)
            ->where('isactive != ?', 5)
            ->order('period_sequence ASC');

        $results = $this->fetchAll($select)->toArray();

        // Format dates for UI consumption
        foreach ($results as &$row) {
            $row['start_date'] = sapp_Global::change_date($row['start_date'], 'view');
            $row['end_date']   = sapp_Global::change_date($row['end_date'], 'view');
            $row['sequence']   = $row['period_sequence'];
        }

        return $results;
    }


    /* =================================================================================
     * GROUP 2: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $currentDate = gmdate("Y-m-d H:i:s");

        // Format dates safely
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
     * GROUP 3: RECORD REMOVAL
     * ================================================================================= */

    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];

        return $this->update($data, ['id = ?' => (int)$id]);
    }

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

        // 1. Check if Period has any active locks/controls
        $lockSelect = $db->select()
            ->from('map_period_control', ['id'])
            ->where('period_id = ?', (int)$id)
            ->where('status != ?', 'Open') // If it's just 'Open', we can delete it. If Locked/Closed, block it.
            ->where('isactive != ?', 5);

        $row = $db->fetchRow($lockSelect);
        return (bool)$row;
    }    

    /**
     * Server-side date validation to prevent overlapping periods.
     * Note: Gaps are permitted, but overlaps throw a fatal exception.
     * 
     * @param array $periodPayload
     * @throws Exception
     */
    private function _validatePeriodDates(array $periodPayload)
    {
        $activePeriods = [];

        // Filter out deleted rows for the overlap check
        foreach ($periodPayload as $p) {
            if (isset($p['action']) && $p['action'] === 'delete') continue;

            $start = strtotime(sapp_Global::change_date($p['start_date'], 'database'));
            $end   = strtotime(sapp_Global::change_date($p['end_date'], 'database'));

            if ($start > $end) {
                throw new Exception("Period '{$p['name']}': Start date cannot be after end date.");
            }

            $activePeriods[] = ['name' => $p['name'], 'start' => $start, 'end' => $end];
        }

        // Check for overlaps (O(N^2) complexity, but N is small (usually 12-52))
        $count = count($activePeriods);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $p1 = $activePeriods[$i];
                $p2 = $activePeriods[$j];

                // Logic: An overlap occurs if Period 1 starts before Period 2 ends AND Period 1 ends after Period 2 starts.
                if ($p1['start'] <= $p2['end'] && $p1['end'] >= $p2['start']) {
                    throw new Exception("Date Overlap Detected: '{$p1['name']}' overlaps with '{$p2['name']}'. Periods must be mutually exclusive.");
                }
            }
        }
    }

    /* =================================================================================
     * GROUP 5: JUNCTION TABLE SYNC (PERIOD CONTROLS / LOCKS)
     * ================================================================================= */

    /**
     * SSOT v6.8: Fetches the State Machine matrix for a specific period.
     * Generates a complete array of ALL active business processes, along with 
     * their current lock status for the specified period.
     *
     * @param int $periodId
     * @return array
     */
    public function getPeriodControlMatrix($periodId)
    {
        $db = $this->getAdapter();

        // 1. Get all Business Processes that require control
        $processSelect = $db->select()
            ->from('main_business_process', ['id', 'name', 'code'])
            ->where('requires_period_control = ?', 1)
            ->where('isactive = ?', 1)
            ->order('name ASC');
        $processes = $db->fetchAll($processSelect);

        $matrix = [];

        if (empty($periodId)) {
            // For a new period, all processes default to Open
            foreach ($processes as $proc) {
                $matrix[$proc['id']] = [
                    'name'   => $proc['name'],
                    'code'   => $proc['code'],
                    'status' => 'Open',
                    'map_id' => 0
                ];
            }
            return $matrix;
        }

        // 2. Fetch existing mappings for this period
        $lockSelect = $db->select()
            ->from('map_period_control', ['id', 'process_id', 'status'])
            ->where('period_id = ?', (int)$periodId)
            ->where('isactive = ?', 1);
        $locks = $db->fetchAll($lockSelect);

        $lockMap = [];
        foreach ($locks as $lock) {
            $lockMap[$lock['process_id']] = ['status' => $lock['status'], 'map_id' => $lock['id']];
        }

        // 3. Merge the lists
        foreach ($processes as $proc) {
            $matrix[$proc['id']] = [
                'name'   => $proc['name'],
                'code'   => $proc['code'],
                'status' => isset($lockMap[$proc['id']]) ? $lockMap[$proc['id']]['status'] : 'Open',
                'map_id' => isset($lockMap[$proc['id']]) ? $lockMap[$proc['id']]['map_id'] : 0
            ];
        }

        return $matrix;
    }

    /**
     * SSOT v6.8: Synchronizes the Period Controls (Locks) for a Period.
     * Uses the Staged JSON Array pattern to surgically insert/update/delete locks.
     *
     * @param int   $periodId The ID of the Period being saved
     * @param array $locks    An array from the UI JSON payload
     * @return void
     */
    public function syncPeriodControls($periodId, array $locks)
    {
        $db = $this->getAdapter();
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        foreach ($locks as $lockData) {
            $action    = $lockData['action'] ?? '';
            $mapId     = isset($lockData['id']) ? (int)$lockData['id'] : 0;
            $processId = isset($lockData['process_id']) ? (int)$lockData['process_id'] : 0;
            $status    = $lockData['status'] ?? 'Open';

            if (empty($processId) || !in_array($status, ['Open', 'Locked', 'Closed'])) continue;

            if ($action === 'delete') {
                if ($mapId > 0) {
                    // Hard delete the mapping record
                    $db->delete('map_period_control', ['id = ?' => $mapId]);
                }
            } 
            elseif ($action === 'update' && $mapId > 0) {
                // Update existing lock state
                $db->update('map_period_control', [
                    'status'       => $status,
                    'modifiedby'   => $userId,
                    'modifieddate' => $currentDate
                ], ['id = ?' => $mapId]);
            } 
            elseif ($action === 'new' || $mapId === 0) {
                // Insert new lock state
                $db->insert('map_period_control', [
                    'period_id'   => (int)$periodId,
                    'process_id'  => $processId,
                    'status'      => $status,
                    'isactive'    => 1,
                    'createdby'   => $userId,
                    'createddate' => $currentDate
                ]);
            }
        }
    }

    /**
     * Surgically synchronizes the Periods array submitted from the UI Grid.
     * Handles Insert, Update, and Delete operations independently based on JSON flags.
     * Performs strict server-side Overlap and Dependency checks.
     *
     * @param int $fiscalYearId The parent ID
     * @param array $periodPayload Decoded JSON array of period objects
     * @return void
     * @throws Exception
     */
    public function syncPeriods($fiscalYearId, array $periodPayload)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        // 1. Server-Side Overlap Validation
        $this->_validatePeriodDates($periodPayload);

        $db = $this->getAdapter();

        foreach ($periodPayload as $period) {
            $action = $period['action'] ?? '';
            $pid    = isset($period['id']) ? (int)$period['id'] : 0;

            // Format dates
            $startDate = sapp_Global::change_date($period['start_date'], 'database');
            $endDate   = sapp_Global::change_date($period['end_date'], 'database');

            $data = [
                'fiscalyear_id'   => (int)$fiscalYearId,
                'name'            => trim((string)$period['name']),
                'period_sequence' => (int)$period['sequence'],
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'description'     => isset($period['description']) ? trim((string)$period['description']) : null,
                'isactive'        => isset($period['isactive']) ? (int)$period['isactive'] : 1
            ];

            // PROCESS ACTION
            if ($action === 'delete') {
                if ($pid > 0) {
                    // Critical Guard: Prevent deleting periods that have locked processes
                    if ($this->checkInUse($pid)) {
                        throw new Exception("Period '{$period['name']}' has active process locks and cannot be deleted.");
                    }

                    // Soft Delete
                    $this->update(
                        ['isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => $currentDate],
                        ['id = ?' => $pid]
                    );
                }
            } elseif ($action === 'update' && $pid > 0) {
                // Update
                $data['modifiedby']   = $userId;
                $data['modifieddate'] = $currentDate;
                $this->update($data, ['id = ?' => $pid]);
            } elseif ($action === 'new' || $pid === 0) {
                // Insert
                $data['createdby']   = $userId;
                $data['createddate'] = $currentDate;
                $this->insert($data);
            }
        }
    }
}
