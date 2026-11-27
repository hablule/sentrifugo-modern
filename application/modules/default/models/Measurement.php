<?php

/*********************************************************************************
 *  This file is part of Sentrifugo-Modern.
 *  Copyright (C) 2026 Hablul.
 ********************************************************************************/

/**
 * Measurement Model
 *
 * Manages skill measurement scales (LIST and SPECIFIC modes) and orchestrates
 * atomic synchronization of child measurement index rows.
 * Refactored to comply with SSOT v7.0.0.
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution <https://hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo-Modern v4.0
 */
class Default_Model_Measurement extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_measurement';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Retrieves all non-deleted measurement records.
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
            $allowedCols = ['code', 'name', 'value_type', 'description'];
            if (in_array($search_val, $allowedCols, true)) {
                $select->where("$search_val LIKE ?", '%' . $search_str . '%');
            }
        }

        $select->order('name ASC');

        if ($limit !== null) {
            $select->limit((int)$limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Builds the DataGrid configuration and query for Measurements.
     *
     * @param string      $sort
     * @param string      $by
     * @param int         $perPage
     * @param int         $pageNo
     * @param string      $searchData
     * @param string|null $call
     * @param string|null $dashboardcall
     * @return array
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                $adapter     = $this->getAdapter();
                $allowedCols = ['code', 'name', 'description'];
                foreach ($searchValues as $key => $val) {
                    if ($key == 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " m.isactive = " . $dbVal . " AND ";
                    } elseif ($key == 'value_type') {
                        $searchQuery .= $adapter->quoteInto(" m.value_type = ?", $val) . " AND ";
                    } elseif (in_array($key, $allowedCols, true)) {
                        $searchQuery .= $adapter->quoteInto(" m.$key LIKE ?", '%' . $val . '%') . " AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        $tableHeaders = [
            'action'      => 'Action',
            'code'        => 'Scale Code',
            'name'        => 'Measurement Name',
            'value_type'  => 'Value Type',
            'description' => 'Description',
            'status_text' => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'measurement',
            'tableheader'    => $tableHeaders,
            'extra'          => [],
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Measurements',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'value_type' => [
                    'type'        => 'select',
                    'filter_data' => ['' => 'All', 'LIST' => 'LIST', 'SPECIFIC' => 'SPECIFIC']
                ],
                'status_text' => [
                    'type'        => 'select',
                    'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']
                ]
            ]
        ];
    }

    /**
     * Constructs the Zend_Db_Select query for the Measurement grid.
     *
     * @param string $sort
     * @param string $by
     * @param int    $pageNo
     * @param int    $perPage
     * @param string $searchQuery
     * @return Zend_Db_Table_Select
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') {
            $by = 'm.isactive';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['m' => $this->_name], [
                'id'          => 'm.id',
                '*',
                'status_text' => new Zend_Db_Expr("CASE WHEN m.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->where('m.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    /**
     * Returns an associative [id => name] pair array for dropdowns.
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'name'])->order('name ASC');
        if ($activeOnly) {
            $select->where('isactive = 1');
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Retrieves a single non-deleted measurement by ID.
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
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    /**
     * Inserts or updates a measurement record with audit metadata.
     *
     * @param array    $data
     * @param int|null $id
     * @return int
     */
    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate('Y-m-d H:i:s');
            $this->update($data, ['id = ?' => (int)$id]);
            return (int)$id;
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate('Y-m-d H:i:s');
        $this->insert($data);
        return (int)$this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    /**
     * Soft-deletes a measurement and cascades soft-deletion to unused child index rows.
     *
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception('Cannot delete this Measurement. It is currently linked to one or more active Skills.');
        }

        $indexModel = new Default_Model_Measurementindex();
        $children   = $indexModel->getIndexesByMeasurementId($id, true);
        foreach ($children as $child) {
            if ($indexModel->checkInUse((int)$child['id'])) {
                throw new Exception('Cannot delete this Measurement because one of its index values is referenced by employee skill records.');
            }
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate('Y-m-d H:i:s')
        ];

        $this->getAdapter()->update('main_measurement_index', $data, ['measurement_id = ?' => (int)$id]);
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /**
     * Toggles active/inactive status.
     *
     * @param int $id
     * @param int $status
     * @return int
     */
    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate('Y-m-d H:i:s')
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 5: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Checks whether a given field value already exists on a non-deleted record.
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
            ->where('isactive != 5');

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * Verifies if the measurement scale is referenced by any active skills.
     *
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $select = $db->select()
                ->from('main_skill', 'id')
                ->where('measurement_id = ?', (int)$id)
                ->where('isactive != 5')
                ->limit(1);
            if ((bool)$db->fetchOne($select)) {
                return true;
            }
        } catch (\Throwable $e) {
            error_log('Measurement Dependency Check Error: ' . $e->getMessage());
        }
        return false;
    }

    /* =================================================================================
     * GROUP 6: CHILD INDEX SYNCHRONIZATION
     * ================================================================================= */

    /**
     * Atomically synchronizes child rows in main_measurement_index for a parent measurement.
     * Handles LIST upserts, SPECIFIC cleanup, unique index safety, and downstream FK checks.
     *
     * @param int    $measurementId
     * @param string $valueType
     * @param array  $indexesPayload
     * @return void
     * @throws Exception
     */
    public function syncIndexes($measurementId, $valueType, array $indexesPayload)
    {
        $db         = $this->getAdapter();
        $userId     = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $date       = gmdate('Y-m-d H:i:s');
        $indexModel = new Default_Model_Measurementindex();

        $existingSelect = $db->select()
            ->from('main_measurement_index', ['id', 'value'])
            ->where('measurement_id = ?', (int)$measurementId)
            ->where('isactive != 5');
        $existingRows = $db->fetchPairs($existingSelect); // [id => value]

        if ($valueType === 'SPECIFIC') {
            foreach ($existingRows as $idxId => $idxVal) {
                if ($indexModel->checkInUse((int)$idxId)) {
                    throw new Exception("Cannot switch to SPECIFIC mode because index value '$idxVal' is referenced by employee skill records.");
                }
                $db->delete('main_measurement_index', ['id = ?' => (int)$idxId]);
            }
            return;
        }

        $payloadIds = [];
        $seenValues = [];

        foreach ($indexesPayload as $position => $item) {
            $val       = trim((string)($item['value'] ?? ''));
            $sortOrder = isset($item['sort_order']) && is_numeric($item['sort_order']) && (int)$item['sort_order'] > 0
                ? (int)$item['sort_order']
                : ($position + 1);
            $desc      = isset($item['description']) && trim((string)$item['description']) !== ''
                ? trim((string)$item['description'])
                : null;

            if ($val === '') {
                continue;
            }

            $lowerVal = strtolower($val);
            if (isset($seenValues[$lowerVal])) {
                throw new Exception("Duplicate index value '$val' is not allowed within the same measurement.");
            }
            $seenValues[$lowerVal] = true;

            if (!empty($item['id']) && isset($existingRows[(int)$item['id']])) {
                $idxId        = (int)$item['id'];
                $payloadIds[] = $idxId;
                $db->update('main_measurement_index', [
                    'value'        => $val,
                    'sort_order'   => $sortOrder,
                    'description'  => $desc,
                    'isactive'     => 1,
                    'modifiedby'   => $userId,
                    'modifieddate' => $date
                ], ['id = ?' => $idxId]);
            } else {
                // Check if a soft-deleted row with the same (measurement_id, value) exists to satisfy UNIQUE key
                $softDeletedId = $db->fetchOne(
                    $db->select()
                        ->from('main_measurement_index', 'id')
                        ->where('measurement_id = ?', (int)$measurementId)
                        ->where('value = ?', $val)
                        ->limit(1)
                );

                if ($softDeletedId) {
                    $payloadIds[] = (int)$softDeletedId;
                    $db->update('main_measurement_index', [
                        'sort_order'   => $sortOrder,
                        'description'  => $desc,
                        'isactive'     => 1,
                        'modifiedby'   => $userId,
                        'modifieddate' => $date
                    ], ['id = ?' => (int)$softDeletedId]);
                } else {
                    $db->insert('main_measurement_index', [
                        'measurement_id' => (int)$measurementId,
                        'value'          => $val,
                        'sort_order'     => $sortOrder,
                        'description'    => $desc,
                        'isactive'       => 1,
                        'createdby'      => $userId,
                        'createddate'    => $date
                    ]);
                    $payloadIds[] = (int)$db->lastInsertId('main_measurement_index');
                }
            }
        }

        $toRemove = array_diff(array_keys($existingRows), $payloadIds);
        foreach ($toRemove as $removeId) {
            if ($indexModel->checkInUse((int)$removeId)) {
                $usedLabel = $existingRows[$removeId];
                throw new Exception("Cannot remove index value '$usedLabel'. It is currently referenced by employee skill records.");
            }
            $db->delete('main_measurement_index', ['id = ?' => (int)$removeId]);
        }
    }
}
