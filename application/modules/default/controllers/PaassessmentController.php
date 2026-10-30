<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Assessment Controller (Execution & Response)
 * 
 * Orchestrates the dual-routed assessment views (Mine vs. Team) and handles 
 * the transactional saving of the dynamic assessment lines.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_PaassessmentController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginEmpId;
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
        $this->_model   = new Default_Model_Paassessment();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            // Resolve the actual HR Employee ID
            $userModel = new Default_Model_Users();
            $empIdResult = $userModel->getEmpFromUser($this->_loginUserId);
            // getEmpFromUser returns an array of arrays: [['empid' => 204]]
            $this->_loginEmpId = (isset($empIdResult[0]['empid'])) ? (int)$empIdResult[0]['empid'] : 0;

            $this->view->loginuserRole  = $this->_loginuserRole;
            $this->view->loginuserGroup = $this->_loginuserGroup;
        }

        require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
    }

    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) die(Zend_Json::encode(['login' => 'failed']));
            else $this->_redirect('');
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
            ->addActionContext('delete', 'json')
            ->addActionContext('makeactiveinactive', 'json')
            ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL (Dual-Routed)
     * ================================================================================= */

    /**
     * SSOT Deviation Note: indexAction is not used directly here. 
     * Routing relies on `mineAction` and `teamAction` to provide explicit context.
     */
    public function indexAction()
    {
        $this->_redirect('paassessment/mine');
    }

    /**
     * Employee's personal To-Do list (Self, Peer, Subordinate reviews assigned TO them).
     */
    public function tasksAction()
    {
        $this->_processGrid('tasks');
        $this->render('index');
    }

    /**
     * Employee's own Review (Review that need to be acknowledged).
     */
    public function mineAction()
    {
        $this->_processGrid('mine');
        $this->render('index'); // Renders the standard index.phtml grid
    }

    /**
     * Manager's oversight dashboard (Reviews ABOUT their team members).
     */
    public function teamAction()
    {
        $this->_processGrid('team');
        $this->render('index');
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $context = $this->getRequest()->getParam('context', 'mine');

        $record = $this->_model->getRecordById($id);
        if (!$record) {
            $this->_redirect('paassessment/' . $context);
            return;
        }

        $this->view->form = $this->_prepareForm($id, true);
        $this->view->form->getElement('context')->setValue($context);
        $this->view->id = $id;        

        $menuMapUrls = [
            'mine'  => rtrim(BASE_URL, '/') . '/paassessment/mine',
            'tasks' => rtrim(BASE_URL, '/') . '/paassessment/tasks',
            'team'  => rtrim(BASE_URL, '/') . '/paassessment/team'
        ];
        $this->view->activeMenuUrl = $menuMapUrls[$context] ?? $menuMapUrls['mine'];
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        // Assessments are spawned automatically by the Campaign. Manual addition is blocked.
        $this->_helper->flashMessenger->addMessage(['error' => "Assessments cannot be created manually. They are generated by an Appraisal Campaign."]);
        $this->_redirect('paassessment/mine');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $context = $this->getRequest()->getParam('context', 'mine');

        $record = $this->_model->getRecordById($id);
        if (!$record) {
            $this->_redirect('paassessment/' . $context);
            return;
        }

        // =====================================================================
        // 3-PRONGED PERMISSION LOGIC
        // =====================================================================
        if ($context === 'team' && $record['status'] !== 'Submitted') {
            $this->_helper->flashMessenger->addMessage(['error' => "Managers can only action assessments that are in 'Submitted' status."]);
            $this->_redirect('paassessment/team');
            return;
        }
        if ($context === 'mine' && $record['status'] !== 'Completed') {
            $this->_helper->flashMessenger->addMessage(['error' => "You can only edit/acknowledge your report card after the evaluator has marked it 'Completed'."]);
            $this->_redirect('paassessment/mine');
            return;
        }
        if ($context === 'tasks' && in_array($record['status'], ['Completed', 'Acknowledged'])) {
            $this->_helper->flashMessenger->addMessage(['error' => "This task has been finalized and cannot be modified further."]);
            $this->_redirect('paassessment/tasks');
            return;
        }

        $this->view->form = $this->_prepareForm($id, false);
        $this->view->form->getElement('context')->setValue($context);
        $this->view->id = $id;

        // SSOT v6.7: EXPLICIT MENU ID INJECTION (Solves Sidebar Bug)
        // $menuMap = array('mine' => 392, 'tasks' => 402, 'team' => 393);
        // $this->view->menu_id        = isset($menuMap[$context]) ? $menuMap[$context] : 392;
        // $this->view->controllername = 'paassessment';
        // $this->view->actionname     = $context;

        $menuMapUrls = [
            'mine'  => rtrim(BASE_URL, '/') . '/paassessment/mine',
            'tasks' => rtrim(BASE_URL, '/') . '/paassessment/tasks',
            'team'  => rtrim(BASE_URL, '/') . '/paassessment/team'
        ];
        $this->view->activeMenuUrl = $menuMapUrls[$context] ?? $menuMapUrls['mine'];

        $this->render('save');
    }

    /**
     * Atomic transaction handler for the Form Envelope and Dynamic Form Array.
     */
    public function saveAction()
    {
        $form = new Default_Form_Paassessment();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');
                $context = trim($request->getParam('context', 'mine'));

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V.4

                try {
                    // 1. Process the Static Envelope (Header)
                    $data = [
                        'status'             => trim($request->getParam('status')),
                        'description'        => trim($request->getParam('description')),
                        'isactive'           => (int)$request->getParam('isactive', 1)
                    ];

                    // Float cast safely
                    $scoreRaw = $request->getParam('consolidated_score');
                    if ($scoreRaw !== '') $data['consolidated_score'] = (float)$scoreRaw;

                    // Fetch Old State for Timeline Comparison
                    $oldState = $this->_model->getRecordById($id);

                    // Save Header
                    $this->_model->saveRecord($data, $id);

                    // 2. Process the Dynamic Answers Array
                    // Safely grab the raw PHP array generated by the dynamic HTML inputs
                    $answers = $request->getParam('answers');
                    if (is_array($answers) && !empty($answers)) {
                        // DELEGATE to Model (SSOT IV.1)
                        $this->_model->saveAssessmentAnswers($id, $answers);
                    }

                    // 3. Process Timeline / Audit Log
                    // Only log if the workflow status actually changed
                    if ($oldState['status'] !== $data['status']) {
                        $this->_model->logAudit($id, "Status changed from {$oldState['status']} to {$data['status']}.");
                    }

                    // System Audit Log (Menu ID depends on Context)
                    $menuId = ($context === 'team') ? 393 : 392;
                    sapp_Global::logManager($menuId, 2, $this->_loginUserId, $id);

                    $db->commit();

                    $this->_helper->flashMessenger->addMessage(['success' => "Assessment updated successfully."]);

                    // Route back to the specific context they came from
                    $redirectUrl = BASE_URL . "paassessment/$context";
                    $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes', 'redirect_url' => $redirectUrl]);
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("PaAssessment Save Error: " . $e->getMessage());
                    $this->_helper->json(['result' => 'exception', 'message' => 'System error occurred while saving.']);
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
        $context = $this->getRequest()->getParam('context', 'mine');

        if ($id) {
            try {
                $this->_model->deleteRecord($id);
                $menuId = ($context === 'team') ? 393 : 392;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);
                $this->_helper->json(['msgtype' => 'success', 'message' => 'Assessment deleted successfully.']);
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
            $context = $this->getRequest()->getParam('context', 'mine');

            $this->_model->toggleStatus($id, $status);

            $menuId = ($context === 'team') ? 393 : 392;
            sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);
            $this->_helper->json(['result' => 'yes']);
        } catch (\Throwable $e) {
            $this->_helper->json(['result' => 'no']);
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Private helper to extract common Grid logic for dual-routing.
     */
    private function _processGrid($context)
    {
        try {
            $request = $this->getRequest();
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') $this->_helper->layout->disableLayout();

            $searchData = (string)$request->getParam('searchData', '');
            $refresh    = $request->getParam('refresh');
            $page       = $request->getParam('page', 1);
            $perPage    = $request->getParam('per_page', PERPAGE);

            $sort = $request->getParam('sort', 'DESC');
            $by   = $request->getParam('by', 'id');

            if ($refresh == 'refresh') {
                $sort = 'DESC';
                $by = 'id';
                $page = 1;
                $searchData = '';
            }

            // Pass context and user ID down to model to enforce security boundaries
            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, null, $context, $this->_loginEmpId);

            // SSOT V: Enforce structural restriction
            $dataTmp['add'] = ''; // Ensure Add button never appears

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->context   = $context; // Pass to view to append to action buttons
            $this->view->perspective   = $context;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();

            // =====================================================================
            // EXPLICIT MENU INJECTION FOR GRID VIEWS
            // =====================================================================
            $menuMapUrls = [
                'mine'  => rtrim(BASE_URL, '/') . '/paassessment/mine',
                'tasks' => rtrim(BASE_URL, '/') . '/paassessment/tasks',
                'team'  => rtrim(BASE_URL, '/') . '/paassessment/team'
            ];
            $this->view->activeMenuUrl = $menuMapUrls[$context] ?? $menuMapUrls['mine'];
        } catch (\Throwable $e) {
            error_log("PaAssessment Grid Error: " . $e->getMessage());
        }
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Paassessment();

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {

                $currentStatus = $data['status'];
                $context = $this->getRequest()->getParam('context', 'mine');
                $statusElement = $form->getElement('status');
                $statusElement->clearMultiOptions(); // Wipe default options

                // Dynamically adjust status options based on current workflow state                
                // LOGIC BRANCHING FOR STATUS DROPDOWN
                if ($context === 'tasks') {
                    // Evaluator's View (To-Do List)
                    if (in_array($currentStatus, ['Pending', 'In Progress'])) {
                        $statusElement->addMultiOptions([
                            'In Progress' => 'Save as Draft (In Progress)',
                            'Submitted'   => 'Submit as Final'
                        ]);
                    }
                } elseif ($context === 'team') {
                    // Manager's View (Oversight)
                    if ($currentStatus === 'Submitted' && !$readonly) {
                        $statusElement->addMultiOptions([
                            'Submitted' => 'Keep as Submitted',
                            'Completed' => 'Mark as Completed (Ready for Employee)'
                        ]);
                    }
                    elseif ($readonly) {
                        $statusElement->addMultiOptions([
                            'In Progress' => 'Save as Draft (In Progress)', 
                            'Submitted' => 'Keep as Submitted', 
                            'Completed' => 'Mark as Completed (Ready for Employee)'
                        ]);
                    }
                } elseif ($context === 'mine') {
                    // Evaluatee's View (Final Report Card)
                    if ($currentStatus === 'Completed') {
                        $statusElement->addMultiOptions([
                            'Acknowledged' => 'Acknowledge (Final Sign-off)'
                        ]);
                    }
                }

                $form->populate($data);

                // Expose header details to View
                $this->view->headerData = $data;

                // Load dynamic questions and previous answers
                $this->view->dynamicQuestions = $this->_model->getDynamicQuestionsForAssessment($id, $data['appraisal_id']);

                // Load Timeline
                $this->view->logs = $this->_model->getAuditLogs($id);

                if (!$readonly) {
                    $form->getElement('submit')->setLabel('Save');
                }
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
