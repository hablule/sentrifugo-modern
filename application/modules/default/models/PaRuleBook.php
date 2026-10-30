<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Rulebook Model (Appraisal Templates)
 * 
 * Manages the core blueprints for performance reviews, including polymorphic 
 * targets, evaluators, and assigned questions.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Parulebook extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_pa_rulebook';

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
                    $searchQuery .= " rb.isactive = " . $dbVal . " AND ";
                } else {
                    $searchQuery .= " rb.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        $tableHeaders = array(
            'action'       => 'Action',
            'code'         => 'Rulebook Code',
            'name'         => 'Rulebook Name',
            'frequency'    => 'Frequency',
            'trigger_type' => 'Trigger Type',
            'status_text'  => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'parulebook',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Rulebooks (Templates)',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'rb.isactive';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('rb' => $this->_name), array(
                'id' => 'rb.id',
                '*',
                'status_text' => new Zend_Db_Expr("CASE WHEN rb.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ))
            ->where('rb.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
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
     * Retrieves only Rulebooks designated for Continuous Feedback (Ad-hoc).
     * Formats as Pairs for dropdown population.
     * 
     * @param bool $activeOnly
     * @return array [id => name]
     */
    public function getAdHocPairs($activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, array('id', 'name'))
            ->where("frequency = 'Ad-hoc'")
            ->order('name ASC');
            
        if ($activeOnly) {
            $select->where("isactive = 1");
        }
        
        return $this->getAdapter()->fetchPairs($select);
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
            return (int)$id;
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
            throw new Exception("Cannot delete this Rulebook. It is currently locked to one or more active Appraisal Campaigns.");
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

        if ($excludeId) $select->where('id != ?', (int)$excludeId);
        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $select = $db->select()->from('main_pa_appraisal', 'id')->where('rulebook_id = ?', (int)$id)->where('isactive != 5')->limit(1);
            if ((bool)$db->fetchOne($select)) return true;
        } catch (Exception $e) {
            error_log("PaRulebook Dependency Check: " . $e->getMessage());
        }
        return false;
    }

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION SYNC METHODS
     * ================================================================================= */

    /**
     * Executes the atomic "One-Shot" synchronization of all mapping tables for the Rulebook.
     * 
     * @param int   $rulebookId
     * @param array $targets
     * @param array $evaluators
     * @param array $questions
     * @return void
     */
    public function syncRulebookMappings($rulebookId, array $targets, array $evaluators, array $questions)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $date = gmdate("Y-m-d H:i:s");

        // 1. Hard purge existing mappings to prevent composite key collisions
        $db->delete('map_pa_rulebook_target', array('rulebook_id = ?' => (int)$rulebookId));
        $db->delete('map_pa_rulebook_evaluator', array('rulebook_id = ?' => (int)$rulebookId));
        $db->delete('map_pa_rulebook_question', array('rulebook_id = ?' => (int)$rulebookId));

        // 2. Insert Targets
        foreach ($targets as $t) {
            if (!empty($t['target_type'])) {
                $db->insert('map_pa_rulebook_target', array(
                    'rulebook_id' => (int)$rulebookId,
                    'target_type' => $t['target_type'],
                    'target_id'   => (int)$t['target_id'],
                    'isactive'    => 1,
                    'createdby'   => $userId,
                    'createddate' => $date
                ));
            }
        }

        // 3. Insert Evaluators
        foreach ($evaluators as $e) {
            if (!empty($e['evaluator_type'])) {
                $db->insert('map_pa_rulebook_evaluator', array(
                    'rulebook_id'    => (int)$rulebookId,
                    'evaluator_type' => $e['evaluator_type'],
                    'evaluator_id'   => (int)($e['evaluator_id'] ?? 0),
                    'is_mandatory'   => (int)($e['is_mandatory'] ?? 1),
                    'isactive'       => 1,
                    'createdby'      => $userId,
                    'createddate'    => $date
                ));
            }
        }

        // 4. Insert Questions
        foreach ($questions as $q) {
            if (!empty($q['question_id'])) {
                $db->insert('map_pa_rulebook_question', array(
                    'rulebook_id' => (int)$rulebookId,
                    'question_id' => (int)$q['question_id'],
                    'rating_id'   => isset($q['rating_id']) ? (int)$q['rating_id'] : null,
                    'weight'      => isset($q['weight']) ? (float)$q['weight'] : 1.00,
                    'isactive'    => 1,
                    'createdby'   => $userId,
                    'createddate' => $date
                ));
            }
        }
    }

    /**
     * Retrieves existing mappings formatted cleanly for the View JS parser.
     * Resolves polymorphic Target IDs into human-readable names via LEFT JOINs and COALESCE.
     * 
     * @param int $rulebookId
     * @return array
     */
    public function getExistingMappings($rulebookId)
    {
        $db = $this->getAdapter();
        $mappings = array('targets' => array(), 'evaluators' => array(), 'questions' => array());

        // 1. Fetch Targets with resolved names
        $selT = $db->select()
            ->from(array('rt' => 'map_pa_rulebook_target'), array(
                'target_type',
                'target_id',
                // Resolve the human-readable name based on the target type
                'target_name' => new Zend_Db_Expr("
                    CASE rt.target_type
                        WHEN 'All' THEN 'All Employees'
                        ELSE COALESCE(c.name, bu.name, di.name, de.name, se.name, pr.name, tm.name, loc.address1, r.name, em.userfullname, CONCAT('ID: ', rt.target_id))
                    END
                ")
            ))
            ->joinLeft(array('c'  => 'main_company'),      "rt.target_type = 'Company' AND rt.target_id = c.id", array())
            ->joinLeft(array('bu' => 'main_businessunit'), "rt.target_type = 'BusinessUnit' AND rt.target_id = bu.id", array())
            ->joinLeft(array('di' => 'main_division'),     "rt.target_type = 'Division' AND rt.target_id = di.id", array())
            ->joinLeft(array('de' => 'main_department'),   "rt.target_type = 'Department' AND rt.target_id = de.id", array())
            ->joinLeft(array('se' => 'main_section'),      "rt.target_type = 'Section' AND rt.target_id = se.id", array())
            ->joinLeft(array('pr' => 'main_project'),      "rt.target_type = 'Project' AND rt.target_id = pr.id", array())
            ->joinLeft(array('tm' => 'main_team'),         "rt.target_type = 'Team' AND rt.target_id = tm.id", array())
            ->joinLeft(array('loc' => 'main_location'),     "rt.target_type = 'Location' AND rt.target_id = loc.location_id", array())
            ->joinLeft(array('r'  => 'main_role'),         "rt.target_type = 'Role' AND rt.target_id = r.id", array())
            ->joinLeft(array('em' => 'main_employee'),     "rt.target_type = 'Employee' AND rt.target_id = em.id", array())
            ->where('rt.rulebook_id = ?', (int)$rulebookId);

        $mappings['targets'] = $db->fetchAll($selT);

        // 2. Fetch Evaluators
        $selE = $db->select()
            ->from('map_pa_rulebook_evaluator', array('evaluator_type', 'evaluator_id', 'is_mandatory'))
            ->where('rulebook_id = ?', (int)$rulebookId);
        $mappings['evaluators'] = $db->fetchAll($selE);

        // 3. Fetch Questions with prompt text
        $selQ = $db->select()
            ->from(array('mq' => 'map_pa_rulebook_question'), array('question_id', 'rating_id', 'weight'))
            ->joinInner(array('q' => 'main_pa_question'), 'mq.question_id = q.id', array('question_name' => 'name'))
            ->where('mq.rulebook_id = ?', (int)$rulebookId);
        $mappings['questions'] = $db->fetchAll($selQ);

        return $mappings;
    }
}
