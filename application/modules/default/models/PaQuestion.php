<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Question Model
 * 
 * Manages the actual appraisal prompts, linking them to Categories, Rating Scales, 
 * and a Many-to-Many pool of Skills.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Paquestion extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_pa_question';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');

        if ($activeStatus !== null) {
            $select->where('isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        }

        $select->order('name ASC');

        if ($limit !== null) {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'status_text') {
                    $dbVal = ($val === 'Active') ? 1 : 0;
                    $searchQuery .= " pq.isactive = " . $dbVal . " AND ";
                } elseif ($key == 'category_name') {
                    $searchQuery .= " pc.name LIKE '%$val%' AND ";
                } elseif ($key == 'rating_name') {
                    $searchQuery .= " pr.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " pq.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // SSOT IV.3: Strict headers. Action must be first.
        $tableHeaders = array(
            'action'        => 'Action',
            'name'          => 'Question Prompt',
            'category_name' => 'Category',
            'rating_name'   => 'Rating Scale',
            'weight'        => 'Weight',
            'status_text'   => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'paquestion',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            // SSOT IV.4: No 'add' flag injected here. Handled by core ACL.
            'menuName'       => 'Appraisal Questions',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'pq.isactive';
        if ($by == 'category_name') $by = 'pc.name';
        if ($by == 'rating_name') $by = 'pr.name';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('pq' => $this->_name), array(
                'id'          => 'pq.id', // SSOT IV.2: Mandatory Grid Aliasing
                'name'        => 'pq.name',
                'weight'      => 'pq.weight',
                'status_text' => new Zend_Db_Expr("CASE WHEN pq.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ))
            ->joinInner(array('pc' => 'main_pa_category'), 'pq.category_id = pc.id', array('category_name' => 'pc.name'))
            ->joinInner(array('pr' => 'main_pa_rating'), 'pq.rating_id = pr.id', array('rating_name' => 'pr.name'))
            ->where('pq.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
        if ($activeOnly) {
            $select->where("isactive = 1");
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);

        if ($row) {
            $data = $row->toArray();
            // Fetch associated M:M skills to populate the Zend_Form_Element_Multiselect
            $skillSelect = $this->getAdapter()->select()
                ->from('map_pa_question_skill', 'skill_id')
                ->where('question_id = ?', (int)$id);
            $data['skill_ids'] = $this->getAdapter()->fetchCol($skillSelect);
            return $data;
        }
        return null;
    }

    /**
     * SSOT Compliance: Encapsulated data retrieval for dropdowns.
     * Fetches questions along with their default rating scales and weights 
     * to support the "Rating Snapshot" architecture in Rulebooks/Campaigns.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getQuestionOptionsWithMetaData($activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, array('id', 'name', 'rating_id', 'weight'))
            ->order('name ASC');

        if ($activeOnly) {
            $select->where("isactive = 1");
        }

        return $this->getAdapter()->fetchAll($select);
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, array('id = ?' => (int)$id));
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        $this->insert($data);
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this Question. It is currently locked into an active Appraisal Rulebook or Campaign.");
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array(
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array(
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 5: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

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

    /**
     * SSOT IV.5 Data Integrity: Verifies if question is locked into a Template or Campaign.
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $sel1 = $db->select()->from('map_pa_rulebook_question', 'rulebook_id')->where('question_id = ?', (int)$id)->where('isactive != 5')->limit(1);
            if ((bool)$db->fetchOne($sel1)) return true;

            $sel2 = $db->select()->from('map_pa_appraisal_question', 'appraisal_id')->where('question_id = ?', (int)$id)->where('isactive != 5')->limit(1);
            if ((bool)$db->fetchOne($sel2)) return true;
        } catch (Exception $e) {
            error_log("PaQuestion Dependency Check: " . $e->getMessage());
        }
        return false;
    }

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION SYNC METHODS (SSOT III.2)
     * ================================================================================= */

    /**
     * Syncs the Many-To-Many relationship between Questions and Skills.
     * Uses atomic delete/insert for clean mapping.
     * 
     * @param int   $questionId
     * @param array $skillIds
     * @return void
     */
    public function syncSkills($questionId, array $skillIds)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $date = gmdate("Y-m-d H:i:s");

        // Hard delete existing mappings to prevent composite key collisions
        $db->delete('map_pa_question_skill', array('question_id = ?' => (int)$questionId));

        if (!empty($skillIds)) {
            foreach ($skillIds as $skillId) {
                if ((int)$skillId > 0) {
                    $db->insert('map_pa_question_skill', array(
                        'question_id' => (int)$questionId,
                        'skill_id'    => (int)$skillId,
                        'isactive'    => 1,
                        'createdby'   => $userId,
                        'createddate' => $date
                    ));
                }
            }
        }
    }
}
