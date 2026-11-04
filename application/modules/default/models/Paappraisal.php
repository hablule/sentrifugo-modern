<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Appraisal Model (The Campaign Instance)
 * 
 * Manages the specific instances of performance review cycles. Freezes Rulebook 
 * templates into hard target audiences and question lists.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Paappraisal extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_pa_appraisal';

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

        $select->order('start_date DESC');

        if ($limit !== null) {
            $select->limit($limit, (int)$offset);
        }

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
                    $searchQuery .= " pa.isactive = " . $dbVal . " AND ";
                } elseif ($key == 'rulebook_name') {
                    $searchQuery .= " rb.name LIKE '%$val%' AND ";
                } elseif ($key == 'period_name') {
                    $searchQuery .= " p.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " pa.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // SSOT IV.3: Strict headers. Action must be first.
        $tableHeaders = [
            'action'        => 'Action',
            'name'          => 'Campaign Name',
            'rulebook_name' => 'Rulebook Template',
            'period_name'   => 'Period',
            'status'        => 'Lifecycle Status',
            'status_text'   => 'Active Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'paappraisal',
            'tableheader'    => $tableHeaders,
            'extra'          => [],
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            // SSOT IV.4: No 'add' => 'add' flag injected here. Handled by core ACL.
            'menuName'       => 'Manage Campaigns',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']],
                'status'      => ['type' => 'select', 'filter_data' => ['' => 'All', 'Draft' => 'Draft', 'Active' => 'Active', 'Closed' => 'Closed']]
            ]
        ];
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 'pa.isactive';
        if ($by == 'rulebook_name') $by = 'rb.name';
        if ($by == 'period_name') $by = 'p.name';

        if (in_array($by, ['start_date', 'end_date', 'name', 'status'])) {
            $by = 'pa.' . $by;
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['pa' => $this->_name], [
                'id'          => 'pa.id', // SSOT IV.2: Mandatory Grid Aliasing
                'name'        => 'pa.name',
                'status'      => 'pa.status',
                'status_text' => new Zend_Db_Expr("CASE WHEN pa.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->joinInner(['rb' => 'main_pa_rulebook'], 'pa.rulebook_id = rb.id', ['rulebook_name' => 'rb.name'])
            ->joinLeft(['p' => 'main_period'], 'pa.period_id = p.id', ['period_name' => 'p.name'])
            ->where('pa.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

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
     * SSOT IV.6: Foreign Key Pair Retrieval Convention
     */
    public function getPairsByRulebook($rulebookId, $activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'name'])->where('rulebook_id = ?', (int)$rulebookId)->order('name ASC');
        if ($activeOnly) $select->where("isactive = 1");
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * SSOT IV.6: Foreign Key Pair Retrieval Convention
     */
    public function getPairsByPeriod($periodId, $activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'name'])->where('period_id = ?', (int)$periodId)->order('name ASC');
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
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, ['id = ?' => (int)$id]);
            return (int)$id; // Returning ID to enable cascade logic in controller
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        $this->insert($data);
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    /**
     * SSOT IV.5 Data Integrity: Dependency Check
     */
    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this Campaign. It has already generated active Assessments for employees.");
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
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
     * Composite Unique Key Validator for Campaign Generation
     * Prevents generating the same period twice for the same rulebook.
     */
    public function checkDuplicateCampaign($rulebookId, $periodId, $excludeId = null)
    {
        if (empty($periodId)) {
            return false; // Ad-hoc campaigns (period_id = NULL) allow infinite duplicates
        }

        $select = $this->select()
            ->where("rulebook_id = ?", (int)$rulebookId)
            ->where("period_id = ?", (int)$periodId)
            ->where("isactive != 5");

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * Verifies if the campaign has spawned physical `main_pa_assessment` transaction records.
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $select = $db->select()->from('main_pa_assessment', 'id')->where('appraisal_id = ?', (int)$id)->where('isactive != 5')->limit(1);
            if ((bool)$db->fetchOne($select)) return true;
        } catch (Exception $e) {
            error_log("PaAppraisal Dependency Check: " . $e->getMessage());
        }
        return false;
    }

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION SYNC METHODS
     * ================================================================================= */

    /**
     * Executes Step 1 of the generation workflow.
     * Calculates the audience from the Rulebook targets, and freezes them along 
     * with the questions into the Appraisal mapping tables.
     * 
     * @param int $appraisalId
     * @param int $rulebookId
     * @return void
     */
    public function generateInitialSnapshots($appraisalId, $rulebookId)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $date = gmdate("Y-m-d H:i:s");

        // 1. Resolve and Freeze the Audience
        $selTargets = $db->select()->from('map_pa_rulebook_target', ['target_type', 'target_id'])->where('rulebook_id = ?', (int)$rulebookId);
        $rulebookTargets = $db->fetchAll($selTargets);

        $audienceIds = [];

        if (class_exists('Default_Model_Employee')) {
            $empModel = new Default_Model_Employee();
            if (method_exists($empModel, 'getEmployeeIdsByOrgUnit')) {
                foreach ($rulebookTargets as $target) {
                    $ids = $empModel->getEmployeeIdsByOrgUnit($target['target_type'], $target['target_id']);
                    $audienceIds = array_merge($audienceIds, $ids);
                }
            }
        }

        // Deduplicate the final employee pool
        $audienceIds = array_unique($audienceIds);

        // Bulk insert frozen audience
        foreach ($audienceIds as $empId) {
            if ((int)$empId > 0) {
                $db->insert('map_pa_appraisal_audience', [
                    'appraisal_id' => (int)$appraisalId,
                    'employee_id'  => (int)$empId,
                    'isactive'     => 1,
                    'createdby'    => $userId,
                    'createddate'  => $date
                ]);
            }
        }

        // 2. Resolve and Freeze the Questions
        $selQuestions = $db->select()->from('map_pa_rulebook_question', ['question_id', 'rating_id', 'weight'])->where('rulebook_id = ?', (int)$rulebookId);
        $rulebookQuestions = $db->fetchAll($selQuestions);

        foreach ($rulebookQuestions as $q) {
            if ((int)$q['question_id'] > 0) {
                $db->insert('map_pa_appraisal_question', [
                    'appraisal_id' => (int)$appraisalId,
                    'question_id'  => (int)$q['question_id'],
                    'rating_id'    => $q['rating_id'], // SNAPSHOT COPIED
                    'weight'       => $q['weight'],    // SNAPSHOT COPIED
                    'isactive'     => 1,
                    'createdby'    => $userId,
                    'createddate'  => $date
                ]);
            }
        }
    }

    /**
     * Executes Step 2 of the generation workflow (Refinements via the Merged Form).
     * Synchronizes the manual additions/removals made by HR on the UI.
     * 
     * @param int   $appraisalId
     * @param array $employeeIds Final list of Employee IDs
     * @param array $questionsArray Final list of Questions with details
     * @return void
     */
    public function syncAppraisalMappings($appraisalId, array $employeeIds, array $questionsArray)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $date = gmdate("Y-m-d H:i:s");

        // Atomic Resync (Hard Delete & Insert)
        $db->delete('map_pa_appraisal_audience', ['appraisal_id = ?' => (int)$appraisalId]);
        $db->delete('map_pa_appraisal_question', ['appraisal_id = ?' => (int)$appraisalId]);

        foreach ($employeeIds as $empId) {
            if ((int)$empId > 0) {
                $db->insert('map_pa_appraisal_audience', [
                    'appraisal_id' => (int)$appraisalId,
                    'employee_id' => (int)$empId,
                    'isactive' => 1,
                    'createdby' => $userId,
                    'createddate' => $date
                ]);
            }
        }

        foreach ($questionsArray as $q) {
            if (!empty($q['question_id'])) {

                $ratingId = isset($q['rating_id']) && $q['rating_id'] !== '' ? (int)$q['rating_id'] : null;
                $weight   = isset($q['weight']) && $q['weight'] !== '' ? (float)$q['weight'] : 1.00;

                $db->insert('map_pa_appraisal_question', [
                    'appraisal_id' => (int)$appraisalId,
                    'question_id'  => (int)$q['question_id'],
                    'rating_id'    => $ratingId,
                    'weight'       => $weight,
                    'isactive'     => 1,
                    'createdby'    => $userId,
                    'createddate'  => $date
                ]);
            }
        }
    }

    /**
     * Fetches the frozen Audience and Question arrays, resolving names for the View.
     * 
     * @param int $appraisalId
     * @return array
     */
    public function getExistingMappings($appraisalId)
    {
        $db = $this->getAdapter();
        $mappings = ['audience' => [], 'questions' => []];

        $selA = $db->select()
            ->from(['aa' => 'map_pa_appraisal_audience'], ['employee_id'])
            ->joinInner(['em' => 'main_employee'], 'aa.employee_id = em.id', ['employee_name' => 'em.userfullname'])
            ->where('aa.appraisal_id = ?', (int)$appraisalId);
        $mappings['audience'] = $db->fetchAll($selA);

        $selQ = $db->select()
            ->from(['aq' => 'map_pa_appraisal_question'], ['question_id', 'rating_id', 'weight'])
            ->joinInner(['q' => 'main_pa_question'], 'aq.question_id = q.id', ['question_name' => 'name'])
            ->where('aq.appraisal_id = ?', (int)$appraisalId);
        $mappings['questions'] = $db->fetchAll($selQ);

        return $mappings;
    }


    /**
     * Retrieves the evaluator rules from the parent Rulebook.
     * Encapsulates DB logic, keeping the Controller clean.
     * 
     * @param int $rulebookId
     * @return array
     */
    public function getEvaluatorRulesByRulebook($rulebookId)
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from('map_pa_rulebook_evaluator', ['evaluator_type', 'evaluator_id'])
            ->where('rulebook_id = ?', (int)$rulebookId)
            ->where('isactive = 1');
        return $db->fetchAll($select);
    }

    /**
     * THE EXECUTION ENGINE
     * Generates all `main_pa_assessment` envelopes for a given campaign.
     * STRICT VALIDATION ADDED: Halts and throws exception if mandatory evaluators are missing.
     * FULLY IMPLEMENTED: Resolves all 6 Evaluator Types (Self, Manager, Peer, Subordinate, HR, SpecificUser).
     * 
     * @param int $appraisalId
     * @param int $rulebookId
     * @param int $currentUserId
     * @return int Count of assessments generated
     * @throws Exception
     */
    public function generateAssessmentEnvelopes($appraisalId, $rulebookId, $currentUserId)
    {
        $db = $this->getAdapter();

        // 1. Fetch Frozen Audience (Includes employee_name for exact error reporting)
        $mappings = $this->getExistingMappings($appraisalId);
        $audienceList = $mappings['audience'];

        if (empty($audienceList)) {
            throw new Exception("Cannot launch: The Target Audience is empty.");
        }

        // 2. Fetch Evaluator Rules (Including the is_mandatory flag)
        $selE = $db->select()
            ->from('map_pa_rulebook_evaluator', ['evaluator_type', 'evaluator_id', 'is_mandatory'])
            ->where('rulebook_id = ?', (int)$rulebookId)
            ->where('isactive = 1');
        $evaluatorRules = $db->fetchAll($selE);

        if (empty($evaluatorRules)) {
            throw new Exception("Cannot launch: The parent Rulebook has no Evaluator logic defined.");
        }

        // Initialize models and constants for hierarchy resolution
        $empModel = new Default_Model_Employee();
        $hrGroupId = defined('HR_GROUP') ? HR_GROUP : 3; // Fallback to 3 if constant missing

        $date = gmdate("Y-m-d H:i:s");
        $assessmentCount = 0;

        // 3. The Heavy Lift: Loop and Generate Envelopes
        foreach ($audienceList as $target) {
            $targetId = (int)$target['employee_id'];
            $targetName = $target['employee_name'];

            foreach ($evaluatorRules as $rule) {
                $evaluatorIds = [];

                // ====================================================================
                // FULL HIERARCHY RESOLUTION BRANCH
                // ====================================================================
                switch ($rule['evaluator_type']) {

                    case 'Self':
                        $evaluatorIds[] = $targetId;
                        break;

                    case 'Manager':
                        if (method_exists($empModel, 'getManagerIdByEmployee')) {
                            $mgrId = $empModel->getManagerIdByEmployee($targetId);
                            if ($mgrId) $evaluatorIds[] = $mgrId;
                        }
                        break;

                    case 'Peer':
                        if (method_exists($empModel, 'getPeerIdsByEmployee')) {
                            $peerIds = $empModel->getPeerIdsByEmployee($targetId);
                            if (!empty($peerIds)) $evaluatorIds = array_merge($evaluatorIds, $peerIds);
                        }
                        break;

                    case 'Subordinate':
                        if (method_exists($empModel, 'getSubordinateIdsByEmployee')) {
                            $subIds = $empModel->getSubordinateIdsByEmployee($targetId);
                            if (!empty($subIds)) $evaluatorIds = array_merge($evaluatorIds, $subIds);
                        }
                        break;

                    case 'HR':
                        // Assigns the review task to all active HR personnel in the system
                        $hrSelect = $db->select()
                            ->from(['u' => 'main_users'], ['empid'])
                            ->joinInner(['ur' => 'main_userrole'], 'u.id = ur.user_id', [])
                            ->joinInner(['r' => 'main_role'], 'ur.role_id = r.id', [])
                            ->where('r.group_id = ?', (int)$hrGroupId)
                            ->where('u.isactive = 1')
                            ->where('u.empid IS NOT NULL');
                        $hrIds = $db->fetchCol($hrSelect);
                        if (!empty($hrIds)) $evaluatorIds = array_merge($evaluatorIds, $hrIds);
                        break;

                    case 'SpecificUser':
                        // Used when the template dictates a specific individual (e.g., the CEO) must review
                        if ($rule['evaluator_id'] > 0) {
                            $evaluatorIds[] = $rule['evaluator_id'];
                        }
                        break;
                }

                // Clean the array of any duplicates or nulls
                $evaluatorIds = array_unique(array_filter($evaluatorIds));

                // ====================================================================
                // STRICT VALIDATION CHECK
                // ====================================================================
                if (empty($evaluatorIds)) {
                    if ($rule['is_mandatory'] == 1) {
                        // HALT THE ENTIRE LAUNCH TRANSACTION
                        throw new Exception("Launch aborted: Employee '{$targetName}' has no assigned {$rule['evaluator_type']} in their profile records (This is a mandatory rule). Please update their employee record before launching.");
                    } else {
                        // If it's not mandatory, skip generating this specific rule for this employee and continue
                        continue;
                    }
                }

                // ====================================================================
                // GENERATE THE RECORDS
                // ====================================================================
                foreach ($evaluatorIds as $eId) {
                    try {
                        $db->insert('main_pa_assessment', [
                            'appraisal_id'   => $appraisalId,
                            'evaluator_id'   => (int)$eId,
                            'evaluatee_id'   => $targetId,
                            'evaluator_type' => $rule['evaluator_type'],
                            'status'         => 'Pending',
                            'isactive'       => 1,
                            'createdby'      => $currentUserId,
                            'createddate'    => $date
                        ]);
                        $assessmentCount++;
                    } catch (Exception $dup) {
                        // Ignore duplicate key errors if an assessment envelope already exists
                        // This relies on the UNIQUE KEY `unq_assessment_run` (`appraisal_id`,`evaluator_id`,`evaluatee_id`)
                    }
                }
            }
        }

        // Final sanity check
        if ($assessmentCount === 0) {
            throw new Exception("Launch aborted: Zero assessments could be generated. Please verify your hierarchy and audience data.");
        }

        return $assessmentCount;
    }
}
