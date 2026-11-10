<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Discipline Case Controller
 * 
 * Orchestrates the Disciplinary workflow, managing the transaction between the Case 
 * and the Audit Log (Timeline). 
 * Strictly adheres to SSOT v6.2 (Method Grouping, Transactional Logic, JSON Contracts).
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_DisciplinecaseController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Disciplinecase();

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
            if ($this->getRequest()->isXmlHttpRequest()) {
                die(Zend_Json::encode(['login' => 'failed']));
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
                    ->addActionContext('delete', 'json')
                    ->addActionContext('makeactiveinactive', 'json')
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

            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);

            $searchData    = (string)$request->getParam('searchData', '');

            $sort    = $request->getParam('sort', 'DESC');
            $by      = $request->getParam('by', 'date_of_occurrence');
            $perPage = ($dashboardcall == 'Yes') 
                       ? $request->getParam('per_page', DASHBOARD_PERPAGE) 
                       : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'DESC'; $by = 'date_of_occurrence'; $page = 1; $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) { 
            error_log("DisciplineCase Index Error: " . $e->getMessage());
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'disciplinecase';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, false);
        $this->render('save');
    }

    /**
     * DUAL-ROUTING TRANSACTIONAL SAVE
     */
    public function saveAction()
    {
        $form = new Default_Form_Disciplinecase();
        $request = $this->getRequest();
        $id = (int)$request->getParam('id');

        if ($request->isPost()) {
            $this->_populateDropdowns($form);

            // If Edit mode, remove requirement for employee_id since it is disabled in the UI
            // and therefore omitted from the POST payload by the browser.
            if ($id > 0) {
                $form->getElement('employee_id')->setRequired(false);
            }

            if ($form->isValid($request->getPost())) {
                
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    // 1. Format Dates
                    $dateOccDb = sapp_Global::change_date(trim($request->getParam('date_of_occurrence')), 'database');
                    $expRaw    = trim($request->getParam('violation_expiry'));
                    $dateExpDb = !empty($expRaw) ? sapp_Global::change_date($expRaw, 'database') : null;

                    // 2. Format Data Payload (Omit employee_id if editing)
                    $data = [
                        'violation_id'       => (int)$request->getParam('violation_id'),
                        'date_of_occurrence' => $dateOccDb,
                        'employer_statement' => trim($request->getParam('employer_statement')),
                        'employee_appeal'    => (int)$request->getParam('employee_appeal'),
                        'employee_statement' => trim($request->getParam('employee_statement')),
                        'case_status'        => trim($request->getParam('case_status')),
                        'isactive'           => (int)$request->getParam('isactive', 1)
                    ];

                    // Add optional Enums/Lookups safely
                    if ($request->getParam('action_id')) $data['action_id'] = (int)$request->getParam('action_id');
                    if ($request->getParam('cao_verdict')) $data['cao_verdict'] = (int)$request->getParam('cao_verdict');
                    if ($request->getParam('action_text')) $data['action_text'] = trim($request->getParam('action_text'));
                    if ($dateExpDb) $data['violation_expiry'] = $dateExpDb;

                    // FIX: Only insert employee_id on new case creation
                    if ($id == 0) {
                        $data['employee_id'] = (int)$request->getParam('employee_id');
                    }

                    // 3. Fetch Old State for Timeline Comparison
                    $oldState = null;
                    if ($id > 0) {
                        $oldState = $this->_model->getRecordById($id);
                    }

                    // 4. Save the Case
                    $caseId = $this->_model->saveRecord($data, $id);

                    // 5. TIMELINE/LOG ENGINE
                    if ($id == 0) {
                        $this->_model->addCaseLog($caseId, "Case Initiated.", $this->_loginUserId);
                    } else {
                        // Auto-Log: Status Change Detection
                        if ($oldState['case_status'] !== $data['case_status']) {
                            $this->_model->addCaseLog($caseId, "Status changed from {$oldState['case_status']} to {$data['case_status']}.", $this->_loginUserId);
                        }
                        // Auto-Log: Verdict Set
                        if ($oldState['cao_verdict'] != $data['cao_verdict'] && !empty($data['cao_verdict'])) {
                            $verdictText = ($data['cao_verdict'] == 1) ? 'Guilty' : 'Not Guilty';
                            $this->_model->addCaseLog($caseId, "Verdict set to: $verdictText.", $this->_loginUserId);
                        }
                    }

                    // 6. User Manual Note
                    $userNote = trim($request->getParam('log_comment'));
                    if (!empty($userNote)) {
                        $this->_model->addCaseLog($caseId, "HR Note: " . $userNote, $this->_loginUserId);
                    }

                    // 7. System Audit Log
                    $menuId = defined('RAISE_INCIDENT') ? RAISE_INCIDENT : 203;
                    sapp_Global::logManager($menuId, ($id > 0 ? 2 : 1), $this->_loginUserId, $caseId);

                    $db->commit();

                    $msg = ($id > 0) ? 'Updated' : 'Initiated';
                    $this->_helper->flashMessenger->addMessage(['success' => "Case $msg successfully."]);
                    $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes']);

                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("DisciplineCase Save Error: " . $e->getMessage());
                    $this->_helper->json(['result' => 'exception', 'message' => 'System error occurred while saving the case.']);
                }
            } else {
                $msgarray = [];
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $this->_helper->json($msgarray);
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
                $menuId = defined('RAISE_INCIDENT') ? RAISE_INCIDENT : 203;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);
                $this->_helper->json(['msgtype' => 'success', 'message' => 'Case deleted successfully.']);
            } catch (\Throwable $e) {
                $this->_helper->json(['msgtype' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            $this->_helper->json(['msgtype' => 'error', 'message' => 'Invalid Request.']);
        }
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');

            $this->_model->toggleStatus($id, $status);

            $menuId = defined('RAISE_INCIDENT') ? RAISE_INCIDENT : 203;
            sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);

            $this->_helper->json(['result' => 'yes']);
        } catch (\Throwable $e) {
            $this->_helper->json(['result' => 'no']);
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX & INTERNAL HELPERS
     * ================================================================================= */

    private function _populateDropdowns(Zend_Form $form)
    {
        $empModel = new Default_Model_Usermanagement(); 
        if (method_exists($empModel, 'getUsersList')) {
            $form->employee_id->addMultiOption('', 'Select Employee')->addMultiOptions($empModel->getUsersList());
        }

        $violationModel = new Default_Model_Disciplineviolation();
        $form->violation_id->addMultiOption('', 'Select Violation')->addMultiOptions($violationModel->getPairs(true));

        $actionModel = new Default_Model_Disciplineaction();
        $form->action_id->addMultiOption('', 'Select Action')->addMultiOptions($actionModel->getPairs(true));
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Disciplinecase();
        $this->_populateDropdowns($form);

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                if (!empty($data['date_of_occurrence'])) $data['date_of_occurrence'] = sapp_Global::change_date($data['date_of_occurrence'], 'view');
                if (!empty($data['violation_expiry']))   $data['violation_expiry']   = sapp_Global::change_date($data['violation_expiry'], 'view');

                $form->populate($data);
                $form->getElement('submit')->setLabel('Update Case');

                $this->view->logs = $this->_model->getCaseLogs($id);
                
                // Disable the dropdown in the UI. 
                // The controller saveAction now handles the backend exemption.
                $form->getElement('employee_id')->setAttrib('disabled', 'disabled');
            }
        }

        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }
}