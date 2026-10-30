<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Feedforward Model (Continuous Feedback)
 * 
 * Orchestrates the on-demand generation of Ad-Hoc appraisals.
 * Maps to main_pa_assessment for the grid to show feedback given by the user.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Pafeedforward extends Zend_Db_Table_Abstract
{
    /** @var string Overrides default naming to map to the Assessment transaction table */
    protected $_name = 'main_pa_assessment';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * SSOT Standard Grid Configuration.
     * Custom configured to show the "Continuous Feedback" dashboard.
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $employeeID = 0)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'evaluatee_name') {
                    $searchQuery .= " ee.userfullname LIKE '%$val%' AND ";
                } elseif ($key == 'rulebook_name') {
                    $searchQuery .= " rb.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " ass.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // Action column allows the user to click back into the assessment to view/edit it
        $tableHeaders = array(
            'action'         => 'Action',
            'createddate'    => 'Date Initiated',
            'evaluatee_name' => 'Feedback For',
            'rulebook_name'  => 'Template Used',
            'evaluator_type' => 'Your Relationship',
            'status'         => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery, $employeeID);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'pafeedforward',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            // SSOT IV.4: No 'add' => 'add' flag here. Handled by core Sentrifugo ACL.
            'menuName'       => 'Continuous Feedback',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status' => array(
                        'type' => 'select', 
                        'filter_data' => array('' => 'All', 'In Progress' => 'In Progress', 'Submitted' => 'Submitted', 'Completed' => 'Completed')
                    )
            )
        );
    }

    /**
     * SSOT Standard Grid Data Fetcher.
     * Filters strictly for 'Ad-hoc' rulebooks where the logged in user is the evaluator.
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '', $employeeID = 0)
    {
        // Handle sorting aliases
        if ($by == 'evaluatee_name') $by = 'ee.userfullname';
        if ($by == 'rulebook_name')  $by = 'rb.name';
        if ($by == 'createddate')    $by = 'ass.createddate';
        if (in_array($by, array('evaluator_type', 'status'))) $by = 'ass.' . $by;        

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ass' => $this->_name), array(
                'id'             => 'ass.id', // SSOT IV.2: Mandatory Grid Aliasing
                'createddate'    => new Zend_Db_Expr("DATE_FORMAT(ass.createddate, '" . DATEFORMAT_MYSQL . "')"),
                'evaluator_type' => 'ass.evaluator_type',
                'status'         => 'ass.status'
            ))
            ->joinInner(array('appr' => 'main_pa_appraisal'), 'ass.appraisal_id = appr.id', array())
            ->joinInner(array('rb' => 'main_pa_rulebook'), 'appr.rulebook_id = rb.id', array('rulebook_name' => 'rb.name'))
            ->joinInner(array('ee' => 'main_employee'), 'ass.evaluatee_id = ee.id', array('evaluatee_name' => 'ee.userfullname'))
            ->where('ass.isactive != 5')
            // SECURITY: Only show feedback initiated by this user
            ->where('ass.evaluator_id = ?', (int)$employeeID)
            // FILTER: Only show Ad-hoc templates (hide formal campaigns)
            ->where('rb.frequency = ?', 'Ad-hoc');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    // getPairs(), getRecordById(), saveRecord(), deleteRecord(), toggleStatus(), checkDuplicate(), checkInUse() 
    // are intentionally omitted here. This model acts as a highly specialized factory/viewer. 
    // The actual record manipulation happens in Paassessment_Model.

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION SYNC METHODS (The Ad-Hoc Generator)
     * ================================================================================= */

    /**
     * Instantly generates an ad-hoc 1-to-1 Appraisal and Assessment envelope.
     * 
     * @param int $evaluatorId The logged-in user giving feedback
     * @param int $evaluateeId The target employee receiving feedback
     * @param int $rulebookId  The ad-hoc template to use
     * @return int The ID of the newly generated main_pa_assessment record
     * @throws Exception
     */
    public function generateAdHocFeedback($evaluatorId, $evaluateeId, $rulebookId)
    {
        $db = $this->getAdapter();
        $date = gmdate("Y-m-d H:i:s");

        // 1. Validate Target
        if ($evaluatorId == $evaluateeId) {
            throw new Exception("You cannot use Continuous Feedback on yourself. Please use the Self-Appraisal feature instead.");
        }

        // 2. Fetch Rulebook info for naming the hidden campaign
        $rbName = $db->fetchOne($db->select()->from('main_pa_rulebook', 'name')->where('id = ?', (int)$rulebookId));
        $targetName = $db->fetchOne($db->select()->from('main_employee', 'userfullname')->where('id = ?', (int)$evaluateeId));

        if (!$rbName || !$targetName) {
            throw new Exception("Invalid Template or Colleague selected.");
        }

        // 3. Create the 1-to-1 Appraisal Campaign
        $appraisalId = $this->_createAdHocAppraisal($db, $rulebookId, $evaluatorId, $targetName, $rbName, $date);

        // 4. Freeze Audience & Questions (The Template Snapshot)
        $db->insert('map_pa_appraisal_audience', array(
            'appraisal_id' => $appraisalId,
            'employee_id'  => (int)$evaluateeId,
            'isactive'     => 1,
            'createdby'    => $evaluatorId,
            'createddate'  => $date
        ));

        // Ensure the selected template actually has questions
        $questions = $db->fetchCol($db->select()->from('map_pa_rulebook_question', 'question_id')->where('rulebook_id = ?', (int)$rulebookId));
        if (empty($questions)) {
            throw new Exception("The selected template has no questions assigned to it. Please contact HR.");
        }

        foreach ($questions as $qId) {
            $db->insert('map_pa_appraisal_question', array(
                'appraisal_id' => $appraisalId,
                'question_id'  => $qId,
                'isactive'     => 1,
                'createdby'    => $evaluatorId,
                'createddate'  => $date
            ));
        }

        // 5. Determine the Relationship (Evaluator Type) dynamically
        $relationship = $this->_determineRelationship($evaluatorId, $evaluateeId);

        // 6. Create the actual Assessment Envelope
        $db->insert('main_pa_assessment', array(
            'appraisal_id'   => $appraisalId,
            'evaluator_id'   => $evaluatorId,
            'evaluatee_id'   => $evaluateeId,
            'evaluator_type' => $relationship,
            // Start 'In Progress' so they can edit immediately without it being flagged as 'Pending Launch'
            'status'         => 'In Progress',
            'isactive'       => 1,
            'createdby'      => $evaluatorId,
            'createddate'    => $date
        ));

        // Return the Assessment ID so the Controller can instantly redirect the user to it
        return (int)$db->lastInsertId('main_pa_assessment');
    }

    /**
     * Private helper to build the hidden Campaign shell required by the architecture.
     */
    private function _createAdHocAppraisal($db, $rulebookId, $userId, $targetName, $rbName, $date)
    {
        $campaignName = "Ad-Hoc: $targetName - $rbName (" . date('M j, Y') . ")";
        $db->insert('main_pa_appraisal', array(
            'rulebook_id' => (int)$rulebookId,
            'period_id'   => null, // Ad-hoc bypasses strict periods, allowing infinite duplicates
            'name'        => substr($campaignName, 0, 255), // Prevent column overflow
            'start_date'  => date('Y-m-d'),
            'end_date'    => date('Y-m-d', strtotime('+30 days')), // Arbitrary open window
            'status'      => 'Active', // Instantly active
            'isactive'    => 1,
            'createdby'   => $userId,
            'createddate' => $date
        ));
        return (int)$db->lastInsertId('main_pa_appraisal');
    }

    /**
     * Private helper to calculate the organizational relationship between two employees.
     */
    private function _determineRelationship($evaluatorId, $evaluateeId)
    {
        $empModel = new Default_Model_Employee();

        // Failsafe if the external model is missing expected hierarchy methods
        if (!method_exists($empModel, 'getManagerIdByEmployee')) {
            return 'Peer';
        }

        // If the Target's boss is ME -> I am the Manager
        if ($empModel->getManagerIdByEmployee($evaluateeId) == $evaluatorId) {
            return 'Manager';
        }

        // If MY boss is the Target -> I am the Subordinate
        if ($empModel->getManagerIdByEmployee($evaluatorId) == $evaluateeId) {
            return 'Subordinate';
        }

        // Otherwise -> We are Peers
        return 'Peer';
    }
}
