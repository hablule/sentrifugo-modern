<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Rating Option Model (Rating Values)
 * 
 * Manages the child values for a parent rating scale.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Paratingoption extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_pa_rating_option';
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');
        if ($activeStatus !== null) $select->where('isactive = ?', (int)$activeStatus);
        if (!empty($search_val) && !empty($search_str)) $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        $select->order('rating_id ASC')->order('value ASC');
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
                    $searchQuery .= " pro.isactive = " . (($val === 'Active') ? 1 : 0) . " AND ";
                } elseif ($key == 'parent_scale') {
                    $searchQuery .= " pr.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " pro.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        $tableHeaders = [
            'action'           => 'Action',
            'parent_scale'     => 'Parent Scale',
            'name'             => 'Text Label',
            'value'            => 'Raw Value',
            'normalized_value' => 'Math Weight (%)',
            'status_text'      => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent'   => $tableContent,
            'objectname' => 'paratingoption',
            'tableheader'    => $tableHeaders,
            'extra'          => [],
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Rating Options',
            'call' => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
            ]
        ];
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'pro.isactive';
        if ($by == 'parent_scale') $by = 'pr.name';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pro' => $this->_name], [
                'id' => 'pro.id',
                'value' => 'pro.value',
                'name' => 'pro.name',
                'icon_class' => 'pro.icon_class',
                'normalized_value' => new Zend_Db_Expr("CONCAT(pro.normalized_value, '%')"), // Format as percentage for grid
                'status_text' => new Zend_Db_Expr("CASE WHEN pro.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->joinInner(['pr' => 'main_pa_rating'], 'pro.rating_id = pr.id', ['parent_scale' => 'pr.name'])
            ->where('pro.isactive != 5');

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

    /**
     * SSOT IV.6: Get child options for a specific parent scale.
     * @param int  $ratingId
     * @param bool $activeOnly
     * @return array
     */
    public function getPairsByRating($ratingId, $activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, ['id', 'name'])
            ->where('rating_id = ?', (int)$ratingId)
            ->order('value ASC');
        if ($activeOnly) $select->where("isactive = 1");
        return $this->getAdapter()->fetchPairs($select);
    }

    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /**
     * Retrieves all child options for a specific parent Rating Scale.
     * Encapsulates the query logic away from the Controller.
     * 
     * @param int  $ratingId
     * @param bool $excludeDeleted
     * @return array
     */
    public function getOptionsByRatingId($ratingId, $excludeDeleted = true)
    {
        $select = $this->select()
            ->where('rating_id = ?', (int)$ratingId)
            ->order('value DESC'); // Highest value first (e.g., 5, 4, 3...)

        if ($excludeDeleted) {
            $select->where('isactive != 5');
        }

        return $this->fetchAll($select)->toArray();
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $ratingId = $data['rating_id']; // We need this to calibrate

        if (!empty($id)) {
            // Fetch rating_id if not in data array during certain updates
            if (empty($ratingId)) {
                $ratingId = $this->getAdapter()->fetchOne($this->select()->from($this->_name, 'rating_id')->where('id = ?', (int)$id));
            }

            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, ['id = ?' => (int)$id]);

            $this->autoCalibrateScale($ratingId); // Trigger auto-math
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        $this->insert($data);

        $this->autoCalibrateScale($ratingId); // Trigger auto-math
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this Rating Option. It is currently linked to one or more submitted employee assessments.");
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $ratingId = $this->getAdapter()->fetchOne($this->select()->from($this->_name, 'rating_id')->where('id = ?', (int)$id));

        $data = ['isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s")];
        $result = $this->update($data, ['id = ?' => (int)$id]);

        $this->autoCalibrateScale($ratingId); // Recalibrate the scale after removing an option
        return $result;
    }

    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = ['isactive' => (int)$status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s")];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 5: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Automatically calculates the 0-100% normalized_value using a
     * Non-Zero Base Counting Technique. 
     * Formula: (100 / Total_Options) * (Index_Position + 1)
     * This ensures the lowest possible score provides a baseline percentage 
     * rather than an absolute 0%, which is standard HR best practice.
     * 
     * @param int $ratingId
     */
    public function autoCalibrateScale($ratingId)
    {
        if (empty($ratingId)) return;

        $db = $this->getAdapter();

        // Check what kind of widget this scale uses
        $uiWidget = $db->fetchOne($db->select()->from('main_pa_rating', 'ui_widget')->where('id = ?', (int)$ratingId));

        $select = $db->select()
            ->from($this->_name, ['id', 'value'])
            ->where('rating_id = ?', (int)$ratingId)
            ->where('isactive != 5')
            ->order('value ASC');

        $options = $db->fetchAll($select);
        $totalOptions = count($options);

        if ($totalOptions === 0) return;

        // --- SLIDER LOGIC ---
        if ($uiWidget === 'Slider') {
            // Force the lowest value to 0% and the highest to 100%
            foreach ($options as $index => $opt) {
                $normalized = ($index === 0) ? 1.00 : 100.00;
                $db->update($this->_name, ['normalized_value' => $normalized], ['id = ?' => (int)$opt['id']]);
            }
            return; // Exit early
        }

        // --- STANDARD LOGIC (Dropdowns/Radios/Stars) ---
        // Formula: (100 / Total_Options) * (Index_Position + 1)
        $stepValue = 100 / $totalOptions;

        // Loop and apply the formula
        foreach ($options as $index => $opt) {

            // Formula: (100 / Total) * (Index + 1)
            $normalized = $stepValue * ($index + 1);

            $db->update(
                $this->_name,
                ['normalized_value' => round($normalized, 2)],
                ['id = ?' => (int)$opt['id']]
            );
        }
    }

    /**
     * Checks if the combination of a parent rating_id and a value already exists.
     */
    public function checkDuplicate($field, $value, $ratingId, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim($value))
            ->where("rating_id = ?", (int)$ratingId)
            ->where("isactive != 5");

        if ($excludeId) $select->where('id != ?', (int)$excludeId);
        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            // Check if linked to main_pa_assessment_line
            $select = $db->select()->from('main_pa_assessment_line', 'id')->where('rating_option_id = ?', (int)$id)->limit(1);
            if ((bool)$db->fetchOne($select)) return true;
        } catch (Exception $e) {
            error_log("PaRatingOption Dependency Check: " . $e->getMessage());
        }
        return false;
    }
}
