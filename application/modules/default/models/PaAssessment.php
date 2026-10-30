<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Assessment Model (The Transaction Envelope)
 * 
 * Manages the core transaction of an evaluator rating an evaluatee.
 * Handles the dynamic loading and saving of the unstructured/structured question lines.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Paassessment extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_pa_assessment';

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

        $select->order('createddate DESC');

        if ($limit !== null) {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * SSOT Standard Grid Configuration.
     * Note: Extends signature safely to accept $context and $userId for dynamic routing.
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $context = 'mine', $employeeID = 0)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'appraisal_name') $searchQuery .= " appr.name LIKE '%$val%' AND ";
                elseif ($key == 'evaluatee_name') $searchQuery .= " ee.userfullname LIKE '%$val%' AND ";
                elseif ($key == 'evaluator_name') $searchQuery .= " er.userfullname LIKE '%$val%' AND ";
                else $searchQuery .= " ass.$key LIKE '%$val%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // 3-PRONGED HEADERS
        if ($context === 'mine') {
            $tableHeaders = array(
                'action' => 'Action',
                'appraisal_name' => 'Campaign',
                'evaluator_name' => 'Evaluated By',
                'evaluator_type' => 'Review Type',
                'status' => 'Status'
            );
            $menuName = 'My Appraisals';
        } elseif ($context === 'tasks') {
            $tableHeaders = array(
                'action' => 'Action',
                'appraisal_name' => 'Campaign',
                'evaluatee_name' => 'Review Target',
                'evaluator_type' => 'Your Role',
                'status' => 'Status'
            );
            $menuName = 'My Tasks';
        } else {
            // team
            $tableHeaders = array(
                'action' => 'Action',
                'appraisal_name' => 'Campaign',
                'evaluatee_name' => 'Team Member',
                'evaluator_name' => 'Evaluator',
                'evaluator_type' => 'Review Type',
                'status' => 'Status'
            );
            $menuName = 'Team Oversight';
        }

        return array(
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery, $context, $employeeID),
            'objectname' => 'paassessment',
            'tableheader' => $tableHeaders,
            'extra' => array(),
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'menuName' => $menuName,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => array(
                'status' => array(
                    'type' => 'select',
                    'filter_data' => array(
                        '' => 'All',
                        'Pending' => 'Pending',
                        'In Progress' => 'In Progress',
                        'Submitted' => 'Submitted',
                        'Completed' => 'Completed',
                        'Acknowledged' => 'Acknowledged'
                    )
                )
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '', $context = 'mine', $employeeID = 0)
    {
        if ($by == 'appraisal_name') $by = 'appr.name';
        if ($by == 'evaluatee_name') $by = 'ee.userfullname';
        if ($by == 'evaluator_name') $by = 'er.userfullname';
        if (in_array($by, array('evaluator_type', 'status'))) $by = 'ass.' . $by;

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ass' => $this->_name), array('id' => 'ass.id', 'evaluator_type' => 'ass.evaluator_type', 'status' => 'ass.status'))
            ->joinInner(array('appr' => 'main_pa_appraisal'), 'ass.appraisal_id = appr.id', array('appraisal_name' => 'appr.name'))
            ->joinInner(array('er' => 'main_employee'), 'ass.evaluator_id = er.id', array('evaluator_name' => 'er.userfullname'))
            ->joinInner(array('ee' => 'main_employee'), 'ass.evaluatee_id = ee.id', array('evaluatee_name' => 'ee.userfullname'))
            ->where('ass.isactive != 5');

        // 3-PRONGED DATA FILTERING
        if ($context === 'mine') {
            $select->where('ass.evaluatee_id = ?', (int)$employeeID)
                // Only show in permanent record if grading is done
                ->where("ass.status IN ('Completed', 'Acknowledged')");
        } elseif ($context === 'tasks') {
            $select->where('ass.evaluator_id = ?', (int)$employeeID)
                // Only show in In progress record for grading to be done
                ->where("ass.status IN ('Pending', 'In Progress')");
        } elseif ($context === 'team') {
            $select->joinInner(array('pos' => 'main_empposition'), 'ee.id = pos.employee_id', array())
                ->where('pos.reportto_id = ?', (int)$employeeID)
                ->where('pos.isactive = ?', 'Y');
        }

        if (!empty($searchQuery)) $select->where($searchQuery);
        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        // Concatenate IDs to satisfy the mandatory method requirement safely
        $select = $this->select()->from($this->_name, array('id', 'name' => new Zend_Db_Expr("CONCAT('Assessment #', id)")));
        if ($activeOnly) $select->where("isactive = 1");
        return $this->getAdapter()->fetchPairs($select);
    }

    public function getRecordById($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ass' => $this->_name))
            ->joinInner(array('appr' => 'main_pa_appraisal'), 'ass.appraisal_id = appr.id', array('appraisal_name' => 'appr.name'))
            ->joinInner(array('ee' => 'main_employee'), 'ass.evaluatee_id = ee.id', array('evaluatee_name' => 'ee.userfullname'))
            ->joinInner(array('er' => 'main_employee'), 'ass.evaluator_id = er.id', array('evaluator_name' => 'er.userfullname'))
            ->where('ass.id = ?', (int)$id)
            ->where('ass.isactive != 5');

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /**
     * SSOT IV.6: Foreign Key Retrieval
     */
    public function getPairsByAppraisal($appraisalId, $activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'name' => new Zend_Db_Expr("CONCAT('Assessment #', id)")))
            ->where('appraisal_id = ?', (int)$appraisalId);
        if ($activeOnly) $select->where("isactive = 1");
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
            throw new Exception("Cannot delete an Assessment once it has been submitted or completed.");
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array('isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array('isactive' => (int)$status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 5: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        // Not strictly applicable to transactional envelopes, but required by SSOT signature
        return null;
    }

    public function checkInUse($id)
    {
        // Protect assessments that have already been worked on
        $record = $this->getRecordById($id);
        if ($record && in_array($record['status'], array('Submitted', 'Completed', 'Acknowledged'))) {
            return true;
        }
        return false;
    }
    

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION SYNC METHODS (Dynamic Forms)
     * ================================================================================= */

    /**
     * Extracts the full question list and current answers for dynamic rendering.
     * Joins across the Mapping table, Question table, and Rating Scales.
     * 
     * @param int $assessmentId
     * @param int $appraisalId
     * @return array
     */
    public function getDynamicQuestionsForAssessment($assessmentId, $appraisalId)
    {
        $db = $this->getAdapter();

        $select = $db->select()
            ->from(array('maq' => 'map_pa_appraisal_question'), array('map_question_id' => 'maq.id', 'weight' => 'maq.weight'))
            ->joinInner(array('q' => 'main_pa_question'), 'maq.question_id = q.id', array('question_name' => 'q.name', 'question_desc' => 'q.description'))
            ->joinInner(array('c' => 'main_pa_category'), 'q.category_id = c.id', array('category_name' => 'c.name'))

            // Read rating_id from the Snapshot (maq), NOT the master question table
            ->joinInner(array('r' => 'main_pa_rating'), 'maq.rating_id = r.id', array('data_type' => 'r.data_type', 'ui_widget' => 'r.ui_widget', 'rating_id' => 'r.id', 'step_value' => 'r.step_value'))

            ->joinLeft(
                array('al' => 'main_pa_assessment_line'),
                $db->quoteInto("al.map_question_id = maq.id AND al.assessment_id = ?", (int)$assessmentId),
                array('answer_rating_id' => 'al.rating_option_id', 'answer_numeric_value' => 'al.numeric_value', 'answer_comments' => 'al.comments')
            )
            ->where('maq.appraisal_id = ?', (int)$appraisalId)
            ->order(array('c.name ASC', 'q.id ASC'));

        $questions = $db->fetchAll($select);

        foreach ($questions as &$q) {
            if ($q['data_type'] === 'Structured') {
                $optSelect = $db->select()
                    ->from('main_pa_rating_option', array('id', 'name', 'value', 'icon_class', 'normalized_value'))
                    ->where('rating_id = ?', (int)$q['rating_id'])
                    ->where('isactive = 1')
                    ->order('value ASC');
                $q['options'] = $db->fetchAll($optSelect);
            } else {
                $q['options'] = array();
            }
        }
        return $questions;
    }

    /**
     * SSOT Mathematical Engine.
     * Relies entirely on the pre-calculated normalized_value 
     * stored in the Immutable Ledger (assessment_line).
     */
    public function calculateConsolidatedScore($assessmentId)
    {
        $db = $this->getAdapter();

        $sql = "SELECT 
                    SUM(al.normalized_value * maq.weight) as total_weighted_score,
                    SUM(maq.weight) as total_weight
                FROM main_pa_assessment_line al
                JOIN map_pa_appraisal_question maq ON al.map_question_id = maq.id
                WHERE al.assessment_id = ? AND al.normalized_value IS NOT NULL";

        $result = $db->fetchRow($sql, array((int)$assessmentId));

        if ($result && $result['total_weight'] > 0) {
            $percentage = $result['total_weighted_score'] / $result['total_weight'];
            $fivePointScale = ($percentage / 100) * 5;
            $db->update('main_pa_assessment', array('consolidated_score' => round($fivePointScale, 2)), array('id = ?' => (int)$assessmentId));
        } else {
            $db->update('main_pa_assessment', array('consolidated_score' => null), array('id = ?' => (int)$assessmentId));
        }
    }

    /**
     * SSOT IV.1 Compliant (No Raw SQL): Saves dynamic form responses and calculates the 
     * Immutable Ledger normalized value.
     * 
     * @param int   $assessmentId
     * @param array $answers [ map_question_id => [ 'rating_option_id' => X, 'comments' => Y ] ]
     * @return void
     */
    public function saveAssessmentAnswers($assessmentId, array $answers)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $date = gmdate("Y-m-d H:i:s");

        foreach ($answers as $mapQuestionId => $data) {

            $comments = isset($data['comments']) ? trim($data['comments']) : null;
            $ratingOptionId = null;
            $numericValue = null;
            $normalizedValue = null;

            // Determine if it's a Slider or a standard Option
            // Use isset() and !== '' so a slider value of "0" is not ignored
            if (isset($data['slider_value']) && $data['slider_value'] !== '') {
                // IT IS A SLIDER
                $numericValue = (float)$data['slider_value'];

                // Fetch the Min and Max bounds from the DB to calculate the percentage
                $boundsSelect = $db->select()
                    ->from(array('ro' => 'main_pa_rating_option'), array(
                        'min_val' => new Zend_Db_Expr('MIN(ro.value)'),
                        'max_val' => new Zend_Db_Expr('MAX(ro.value)')
                    ))
                    ->joinInner(array('maq' => 'map_pa_appraisal_question'), 'maq.rating_id = ro.rating_id', array())
                    ->where('maq.id = ?', (int)$mapQuestionId)
                    ->where('ro.isactive = 1');
                    
                $bounds = $db->fetchRow($boundsSelect);

                if ($bounds && $bounds['min_val'] !== null && $bounds['max_val'] !== null) {
                    $min = (float)$bounds['min_val'];
                    $max = (float)$bounds['max_val'];
                    $range = $max - $min;

                    // Normalize the exact position of the slider into a 1-100% score
                    if ($range > 0) {
                        $percentage = ((($numericValue - $min) / $range) * 99) + 1;
                        $normalizedValue = round(max(1, min(100, $percentage)), 2);
                    } else {
                        $normalizedValue = 100.00; // Failsafe
                    }
                }
            } elseif (!empty($data['rating_option_id'])) {
                // IT IS A STANDARD DROPDOWN/RADIO/STAR
                $ratingOptionId = (int)$data['rating_option_id'];

                // Fetch the pre-calculated percentage from the dictionary
                $normVal = $db->fetchOne(
                    $db->select()->from('main_pa_rating_option', 'normalized_value')->where('id = ?', $ratingOptionId)
                );
                if ($normVal !== false) {
                    $normalizedValue = (float)$normVal;
                    // Also capture the raw numeric value for the ledger
                    $numericValue = (float)$db->fetchOne($db->select()->from('main_pa_rating_option', 'value')->where('id = ?', $ratingOptionId));
                }
            }

            // Write to the Immutable Ledger
            $select = $db->select()->from('main_pa_assessment_line', 'id')->where('assessment_id = ?', (int)$assessmentId)->where('map_question_id = ?', (int)$mapQuestionId)->limit(1);
            $lineId = $db->fetchOne($select);

            $payload = array(
                'rating_option_id' => $ratingOptionId,
                'numeric_value'    => $numericValue,
                'normalized_value' => $normalizedValue,
                'comments'         => $comments,
                'modifiedby'       => $userId,
                'modifieddate'     => $date
            );

            if ($lineId) {
                $db->update('main_pa_assessment_line', $payload, array('id = ?' => $lineId));
            } else {
                $payload['assessment_id']   = (int)$assessmentId;
                $payload['map_question_id'] = (int)$mapQuestionId;
                $payload['isactive']        = 1;
                $payload['createdby']       = $userId;
                $payload['createddate']     = $date;
                $db->insert('main_pa_assessment_line', $payload);
            }
        }

        // TRIGGER MATH ENGINE
        $this->calculateConsolidatedScore($assessmentId);
    }

    /**
     * Writes to the Audit Log table to maintain historical timeline.
     */
    public function logAudit($assessmentId, $actionTaken, $description = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        $this->getAdapter()->insert('main_pa_audit_log', array(
            'assessment_id' => (int)$assessmentId,
            'action_taken'  => $actionTaken,
            'actor_id'      => (int)$userId,
            'description'   => $description,
            'isactive'      => 1,
            'createdby'     => (int)$userId,
            'createddate'   => gmdate("Y-m-d H:i:s")
        ));
    }

    /**
     * Retrieves the audit log timeline for a specific assessment.
     */
    public function getAuditLogs($assessmentId)
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from(array('l' => 'main_pa_audit_log'), array(
                'action_taken',
                'description',
                'createddate' => new Zend_Db_Expr("DATE_FORMAT(l.createddate, '%b %d, %Y %H:%i')")
            ))
            ->joinLeft(array('emp' => 'main_employee'), 'l.actor_id = emp.id', array('actor_name' => 'emp.userfullname'))
            ->where('l.assessment_id = ?', (int)$assessmentId)
            ->order('l.createddate DESC');

        return $db->fetchAll($select);
    }
}
