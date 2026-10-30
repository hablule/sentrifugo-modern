<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Rating Model (Rating Scales)
 * 
 * Manages the parent scales and orchestrates the synchronization of child options.
 * Strictly adheres to SSOT v6.2 (Method Grouping & Alphabetical Ordering).
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Parating extends Zend_Db_Table_Abstract
{
    /* -----------------------------------------------------------------------------
     * ATTRIBUTES (Protected, Alphabetical)
     * ----------------------------------------------------------------------------- */
    protected $_name = 'main_pa_rating';
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL (Alphabetical)
     * ================================================================================= */

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');
        if ($activeStatus !== null) $select->where('isactive = ?', (int)$activeStatus);
        if (!empty($search_val) && !empty($search_str)) $select->where("$search_val LIKE ?", '%' . $search_str . '%');

        $select->order('name ASC');
        if ($limit !== null) $select->limit($limit, (int)$offset);

        return $this->fetchAll($select)->toArray();
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'status_text') {
                    $dbVal = ($val === 'Active') ? 1 : 0;
                    $searchQuery .= " pr.isactive = " . $dbVal . " AND ";
                } else {
                    $searchQuery .= " pr.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        $tableHeaders = [
            'action'      => 'Action',
            'code'        => 'Scale Code',
            'name'        => 'Scale Name',
            'data_type'   => 'Data Type',
            'ui_widget'   => 'UI Widget',
            'status_text' => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent'   => $tableContent,
            'objectname' => 'parating',
            'tableheader' => $tableHeaders,
            'extra'          => [],
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray'    => $searchArray,
            'menuName' => 'Rating Scales',
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
            ]
        ];
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'pr.isactive';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pr' => $this->_name], [
                'id' => 'pr.id',
                '*',
                'status_text' => new Zend_Db_Expr("CASE WHEN pr.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->where('pr.isactive != 5');

        if (!empty($searchQuery)) $select->where($searchQuery);
        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'name'])->order('name ASC');
        if ($activeOnly) $select->where("isactive = 1");
        return $this->getAdapter()->fetchPairs($select);
    }

    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE (Alphabetical)
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, ['id = ?' => (int)$id]);
            return (int)$id;
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        $this->insert($data);
        return (int)$this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS (Alphabetical)
     * ================================================================================= */

    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this Rating Scale. It is currently linked to one or more Appraisal Questions.");
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = ['isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s")];

        // Cascade soft-delete to child options
        $this->getAdapter()->update('main_pa_rating_option', $data, ['rating_id = ?' => (int)$id]);

        return $this->update($data, ['id = ?' => (int)$id]);
    }

    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = ['isactive' => (int)$status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s")];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 5: INTEGRITY CHECKS & INTERNAL HELPERS (Alphabetical)
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim($value))
            ->where("isactive != 5");

        if ($excludeId) $select->where('id != ?', (int)$excludeId);
        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $select = $db->select()
                ->from('main_pa_question', 'id')
                ->where('rating_id = ?', (int)$id)
                ->where('isactive != 5')
                ->limit(1);
            if ((bool)$db->fetchOne($select)) return true;
        } catch (Exception $e) {
            error_log("PaRating Dependency Check: " . $e->getMessage());
        }
        return false;
    }

    /**
     * IMMUTABLE LEDGER CHECK: Verifies if this Rating Scale has been used to answer
     * an assessment. If true, the scale is locked to protect historical data integrity.
     */
    public function checkIfUsedInAssessment($id)
    {
        if (empty($id)) return false;

        $db = $this->getAdapter();
        try {
            // Trace the path: Rating -> Mapped Question -> Assessment Line
            $select = $db->select()
                ->from(['al' => 'main_pa_assessment_line'], ['id'])
                ->joinInner(['maq' => 'map_pa_appraisal_question'], 'al.map_question_id = maq.id', [])
                ->where('maq.rating_id = ?', (int)$id)
                ->limit(1);

            return (bool)$db->fetchOne($select);
        } catch (\Throwable $e) {
            error_log("PaRating Immutability Check Error: " . $e->getMessage());
            return false;
        }
    }

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION / CHILD SYNC METHODS (Alphabetical)
     * ================================================================================= */

    public function syncOptions($ratingId, $dataType, array $optionsPayload)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $date = gmdate("Y-m-d H:i:s");

        // CONDITION 3: Wipe DB if Unstructured
        if ($dataType === 'Unstructured') {
            $db->update(
                'main_pa_rating_option',
                ['isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => $date],
                ['rating_id = ?' => (int)$ratingId, 'isactive != ?' => 5]
            );
            return;
        }

        // CONDITION 1 & 2: Process Structured Upsert
        $existingSelect = $db->select()->from('main_pa_rating_option', 'id')->where('rating_id = ?', (int)$ratingId)->where('isactive != 5');
        $existingIds = $db->fetchCol($existingSelect);
        $payloadIds = [];

        foreach ($optionsPayload as $opt) {
            $val  = (float)$opt['value'];
            $name = trim($opt['name']);
            $icon = trim($opt['icon_class']);

            if (empty($name)) continue;

            if (!empty($opt['id'])) {
                $optId = (int)$opt['id'];
                $payloadIds[] = $optId;
                $db->update('main_pa_rating_option', [
                    'value'        => $val,
                    'name'         => $name,
                    'icon_class'   => $icon,
                    'modifiedby'   => $userId,
                    'modifieddate' => $date
                ], ['id = ?' => $optId]);
            } else {
                $db->insert('main_pa_rating_option', [
                    'rating_id'   => (int)$ratingId,
                    'value'       => $val,
                    'name'        => $name,
                    'icon_class'  => $icon,
                    'isactive'    => 1,
                    'createdby'   => $userId,
                    'createddate' => $date
                ]);
            }
        }

        $toDelete = array_diff($existingIds, $payloadIds);
        if (!empty($toDelete)) {
            $db->update(
                'main_pa_rating_option',
                ['isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => $date],
                ['id IN (?)' => $toDelete]
            );
        }

        $paratingModel = new Default_Model_Paratingoption();
        $paratingModel->autoCalibrateScale($ratingId);
    }
}
