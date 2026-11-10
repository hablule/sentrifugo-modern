<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Feedforward Controller
 * 
 * Orchestrates Continuous Feedback (Ad-Hoc Appraisals).
 * Acts as a rapid generator and routes the user directly into the execution form.
 * Strictly adheres to SSOT v6.2 (Strict MVC Separation).
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_PafeedforwardController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Pafeedforward();

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
        $ajaxContext->addActionContext('save', 'json')->initContext();
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
            $refresh    = $request->getParam('refresh');
            $page       = $request->getParam('page', 1);
            $perPage    = $request->getParam('per_page', PERPAGE);

            $sort = $request->getParam('sort', 'DESC');
            $by   = $request->getParam('by', 'createddate');

            if ($refresh == 'refresh') {
                $sort = 'DESC';
                $by = 'createddate';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, null, $this->_loginEmpId);

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) {
            error_log("PaFeedforward Index Error: " . $e->getMessage());
        }
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    /* =================================================================================
     * SMART ROUTING FAILSAFES
     * Catches Sentrifugo's unpredictable grid URL generation and routes correctly.
     * ================================================================================= */

    /**
     * Failsafe for the Grid "Add" button routing to /edit, 
     * or a row "Edit" icon bypassing the JS rewriter.
     */
    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id', 0);

        // Scenario 1: A row's Edit icon was clicked (bypassing JS).
        // Redirect to the actual execution module to edit the feedback.
        if ($id > 0) {
            $this->_redirect('paassessment/edit/id/' . $id . '/context/mine');
            return;
        }

        // Scenario 2: The Grid's "Add" button was clicked, but routed to /edit.
        // Render the Generator Form.
        $this->view->form = $this->_prepareForm();

        // Inform the view it's acting as the Add form
        $this->view->id = 0;

        $this->render('save');
    }

    /**
     * Failsafe for a row "View" icon bypassing the JS rewriter.
     */
    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id', 0);

        // Redirect to the actual execution module to view the feedback.
        if ($id > 0) {
            $this->_redirect('paassessment/view/id/' . $id . '/context/mine');
            return;
        }

        // If no ID, send them back to the grid safely
        $this->_redirect('pafeedforward/index');
    }

    public function saveAction()
    {
        $form = new Default_Form_Pafeedforward();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->_populateDropdowns($form);

            if ($form->isValid($request->getPost())) {
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $evaluateeId = (int)$request->getParam('evaluatee_id');
                    $rulebookId  = (int)$request->getParam('rulebook_id');

                    // Delegate execution to the Model
                    $assessmentId = $this->_model->generateAdHocFeedback($this->_loginUserId, $evaluateeId, $rulebookId);

                    $menuId = defined('CONTINUOUS_FEEDBACK') ? CONTINUOUS_FEEDBACK : 394;
                    sapp_Global::logManager($menuId, 1, $this->_loginUserId, $assessmentId);

                    $db->commit();

                    $redirectUrl = BASE_URL . "paassessment/edit/id/$assessmentId/context/mine";
                    $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes', 'redirect_url' => $redirectUrl]);
                } catch (\Throwable $e) {
                    $db->rollBack();
                    $this->_helper->json(['result' => 'exception', 'message' => $e->getMessage()]);
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
     * GROUP 5: AJAX & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Populates the dropdowns using strict Model method delegation.
     */
    private function _populateDropdowns(Zend_Form $form)
    {
        // 1. Fetch Employees
        $empModel = new Default_Model_Employee();
        $form->evaluatee_id->addMultiOption('', 'Select Colleague')->addMultiOptions($empModel->getPairs());

        // 2. Fetch Ad-hoc Rulebooks (Delegated to Parulebook Model)
        $rulebookModel = new Default_Model_Parulebook();
        $form->rulebook_id->addMultiOption('', 'Select Template')->addMultiOptions($rulebookModel->getAdHocPairs(true));
    }

    private function _prepareForm()
    {
        $form = new Default_Form_Pafeedforward();
        $this->_populateDropdowns($form);
        return $form;
    }
}
