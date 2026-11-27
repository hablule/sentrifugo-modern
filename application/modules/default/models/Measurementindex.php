<?php

/*********************************************************************************
 *  This file is part of Sentrifugo-Modern.
 *  Copyright (C) 2026 Hablul.
 ********************************************************************************/

/**
 * Measurement Index Model
 *
 * Manages discrete measurement scale index values belonging to a parent
 * Measurement record (`main_measurement`).
 * Refactored to comply with SSOT v7.0.0.
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution <https://hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo-Modern v4.0
 */
class Default_Model_Measurementindex extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_measurement_index';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Retrieves all non-deleted measurement index records.
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
            $allowedCols = ['value', 'description'];
            if (in_array($search_val, $allowedCols, true)) {
                $select->where("$search_val LIKE ?", '%' . $search_str . '%');
            }
        }

        $select->order(['measurement_id ASC', 'sort_order ASC', 'id ASC']);

        if ($limit !== null) {
            $select->limit((int)$limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Retrieves all child index rows for a specific parent Measurement.
     *
     * @param int  $measurementId
     * @param bool $excludeDeleted
     * @return array
     */
    public function getIndexesByMeasurementId($measurementId, $excludeDeleted = true)
    {
        $select = $this->select()
            ->where('measurement_id = ?', (int)$measurementId)
            ->order(['sort_order ASC', 'id ASC']);

        if ($excludeDeleted) {
            $select->where('isactive != 5');
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Returns an associative [id => value] pair array for all index rows.
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, ['id', 'value'])
            ->order(['sort_order ASC', 'value ASC']);
        if ($activeOnly) {
            $select->where('isactive = 1');
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Returns an associative [id => value] pair array filtered by parent Measurement (SSOT IV.6).
     *
     * @param int  $measurementId
     * @param bool $activeOnly
     * @return array
     */
    public function getPairsByMeasurement($measurementId, $activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, ['id', 'value'])
            ->where('measurement_id = ?', (int)$measurementId)
            ->order(['sort_order ASC', 'id ASC']);
        if ($activeOnly) {
            $select->where('isactive = 1');
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Retrieves a single non-deleted measurement index by ID.
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
     * Inserts or updates a measurement index record.
     *
     * @param array    $data
     * @param int|null $id
     * @return int|string
     */
    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate('Y-m-d H:i:s');
            $this->update($data, ['id = ?' => (int)$id]);
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate('Y-m-d H:i:s');
        $this->insert($data);
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    /**
     * Soft-deletes a measurement index after verifying no employee skills reference it.
     *
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception('Cannot delete this Measurement Index. It is currently referenced by employee skill records.');
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate('Y-m-d H:i:s')
        ];
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
     * Checks if a value already exists under the same parent measurement.
     *
     * @param string   $field
     * @param string   $value
     * @param int      $measurementId
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicate($field, $value, $measurementId, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim($value))
            ->where('measurement_id = ?', (int)$measurementId)
            ->where('isactive != 5');

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * Verifies if the measurement index is referenced by employee skill records.
     *
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $select = $db->select()
                ->from('main_empskill', 'empskill_id')
                ->where('measurementindex_id = ?', (int)$id)
                ->limit(1);
            if ((bool)$db->fetchOne($select)) {
                return true;
            }
        } catch (\Throwable $e) {
            error_log('Measurementindex Dependency Check Error: ' . $e->getMessage());
        }
        return false;
    }
}
