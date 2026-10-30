<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Appraisal Controller (Manage Campaigns)
 * 
 * Orchestrates the creation and launching of Performance Review cycles.
 * Implements the "Draft -> Refine -> Launch" workflow.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_PaappraisalController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Paappraisal();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            $this->view->loginuserRole  = $this->_loginuserRole;
            $this->view->loginuserGroup = $this->_loginuserGroup;
        }

        require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
    }

    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) die(Zend_Json::encode(array('login' => 'failed')));
            else $this->_redirect('');
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
            ->addActionContext('delete', 'json')
            ->addActionContext('makeactiveinactive', 'json')
            ->addActionContext('launch', 'json') // For the grid "Rocket" button
            ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') $this->_helper->layout->disableLayout();

            $searchData = (string)$request->getParam('searchData', '');
            $sort       = $request->getParam('sort', 'DESC');
            $by         = $request->getParam('by', 'pa.start_date');
            $page       = $request->getParam('page', 1);
            $perPage    = $request->getParam('per_page', PERPAGE);

            if ($request->getParam('refresh') == 'refresh') {
                $sort = 'DESC';
                $by = 'pa.start_date';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, null);
            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) {
            error_log("PaAppraisal Index Error: " . $e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'paappraisal';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->view->campaignStatus = 'new';
        $this->render('save');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');

        $record = $this->_model->getRecordById($id);

        // Security check: Only Draft campaigns can be modified
        if ($record && $record['status'] !== 'Draft') {
            $this->_helper->flashMessenger->addMessage(array('error' => "Active or Closed campaigns cannot be modified."));
            $this->_redirect('paappraisal/index');
            return;
        }

        $this->view->form = $this->_prepareForm($id, false);
        $this->view->id = $id;
        $this->view->campaignStatus = $record ? $record['status'] : 'new';

        $this->render('save');
    }

    /**
     * DUAL-STATE SAVE & LAUNCH
     * Handles both saving a Draft and launching a campaign in a single transaction.
     */
    public function saveAction()
    {
        $form = new Default_Form_Paappraisal();
        $request = $this->getRequest();
        $id = (int)$request->getParam('id');

        // Check if the launch button was clicked
        $shouldLaunch = (bool)$request->getParam('and_launch', false);

        if ($request->isPost()) {
            $this->_populateDropdowns($form);
            if ($id > 0) $form->getElement('rulebook_id')->setRequired(false);

            if ($form->isValid($request->getPost())) {

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $rulebookId = ($id > 0) ? $this->_model->getRecordById($id)['rulebook_id'] : (int)$request->getParam('rulebook_id');
                    $periodId   = $request->getParam('period_id') ? (int)$request->getParam('period_id') : null;

                    if ($id == 0) {
                        if ($this->_model->checkDuplicateCampaign($rulebookId, $periodId)) {
                            $this->_helper->json(array('period_id' => 'This financial period has already been launched for this Rulebook.'));
                            return;
                        }
                    }

                    $data = array(
                        'period_id'   => $periodId,
                        'name'        => trim($request->getParam('name')),
                        'start_date'  => sapp_Global::change_date(trim($request->getParam('start_date')), 'database'),
                        'end_date'    => sapp_Global::change_date(trim($request->getParam('end_date')), 'database'),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive', 1)
                    );
                    if ($id == 0) $data['rulebook_id'] = $rulebookId;

                    $actionFlag = ($id > 0) ? 2 : 1;
                    $campaignId = $this->_model->saveRecord($data, $id);

                    if ($id == 0) {
                        // State 1: New Campaign. Pull snapshot from Rulebook template.
                        $this->_model->generateInitialSnapshots($campaignId, $rulebookId);
                    } else {
                        // State 2: Refinement. Sync manual UI changes from Merged Form tabs.
                        $audienceRaw  = json_decode((string)$request->getParam('audience_payload'), true);
                        $questionsRaw = json_decode((string)$request->getParam('questions_payload'), true);

                        $employeeIds = is_array($audienceRaw) ? array_column($audienceRaw, 'employee_id') : array();

                        $this->_model->syncAppraisalMappings($campaignId, $employeeIds, $questionsRaw);
                    }

                    // ==============================================================
                    // LAUNCH LOGIC BRANCH
                    // This now calls the Model directly.
                    // ==============================================================
                    if ($shouldLaunch) {
                        $assessmentsGenerated = $this->_model->generateAssessmentEnvelopes($campaignId, $rulebookId, $this->_loginUserId);
                        $this->_model->saveRecord(array('status' => 'Active'), $campaignId);
                        $this->_helper->flashMessenger->addMessage(array('success' => "Campaign launched successfully. Generated $assessmentsGenerated assessments."));
                    } else {
                        $msg = ($actionFlag == 1) ? 'Campaign Draft created' : 'Campaign Updated';
                        $this->_helper->flashMessenger->addMessage(array('success' => "$msg successfully."));
                    }

                    $menuId = 395;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $campaignId);
                    if ($shouldLaunch) sapp_Global::logManager($menuId, 2, $this->_loginUserId, $campaignId);

                    $db->commit();

                    $redirectUrl = ($shouldLaunch)
                        ? BASE_URL . 'paappraisal'
                        : (($actionFlag == 1) ? BASE_URL . "paappraisal/edit/id/$campaignId" : BASE_URL . 'paappraisal');

                    $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes', 'redirect_url' => $redirectUrl));
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("PaAppraisal Save/Launch Error: " . $e->getMessage());
                    $this->_helper->json(array('result' => 'exception', 'message' => $e->getMessage()));
                }
            } else {
                $this->_helper->json($form->getMessages());
            }
        }
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                $this->_model->deleteRecord($id);
                sapp_Global::logManager(395, 3, $this->_loginUserId, $id);
                $this->_helper->json(array('msgtype' => 'success', 'message' => 'Campaign deleted successfully.'));
            } catch (\Throwable $e) {
                $this->_helper->json(array('msgtype' => 'error', 'message' => $e->getMessage()));
            }
        } else {
            $this->_helper->json(array('msgtype' => 'error', 'message' => 'Invalid Request.'));
        }
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');
            $this->_model->toggleStatus($id, $status);
            sapp_Global::logManager(395, 4, $this->_loginUserId, $id);
            $this->_helper->json(array('result' => 'yes'));
        } catch (\Throwable $e) {
            $this->_helper->json(array('result' => 'no'));
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX & INTERNAL HELPERS
     * ================================================================================= */

    private function _populateDropdowns(Zend_Form $form)
    {
        $rulebookModel = new Default_Model_Parulebook();
        $form->rulebook_id->addMultiOption('', 'Select Template')->addMultiOptions($rulebookModel->getPairs(true));

        if (class_exists('Default_Model_Period')) {
            $periodModel = new Default_Model_Period();
            if (method_exists($periodModel, 'getPairs')) {
                $form->period_id->addMultiOption('', 'Ad-Hoc / Out of Cycle')->addMultiOptions($periodModel->getPairs(true));
            }
        }
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Paappraisal();
        $this->_populateDropdowns($form);

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                // Dates UI Transformation
                if (!empty($data['start_date'])) $data['start_date'] = sapp_Global::change_date($data['start_date'], 'view');
                if (!empty($data['end_date']))   $data['end_date']   = sapp_Global::change_date($data['end_date'], 'view');

                $form->populate($data);

                // Rulebook is locked once drafted to preserve snapshot integrity
                $form->getElement('rulebook_id')->setAttrib('disabled', 'disabled');

                if (!$readonly) {
                    $form->getElement('submit')->setLabel('Update Draft');
                }

                // Hydrate Merged Form Tabs
                $mappings = $this->_model->getExistingMappings($id);
                $this->view->existingAudience  = json_encode($mappings['audience']);
                $this->view->existingQuestions = json_encode($mappings['questions']);
            }
        } else {
            // Default empty states for new records
            $this->view->existingAudience  = '[]';
            $this->view->existingQuestions = '[]';
        }

        $questionModel = new Default_Model_Paquestion();
        $this->view->questionOptions = $questionModel->getQuestionOptionsWithMetaData(true);

        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }
}
