<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * My Incident Controller
 * 
 * Allows employees to view cases raised against them and submit appeals.
 * Strictly adheres to SSOT v6.2.
 */
class Default_MyincidentController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    protected $_options;

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Myincident();

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
        $ajaxContext->addActionContext('save', 'json')->initContext();
    }

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
            $perPage = ($dashboardcall == 'Yes') ? $request->getParam('per_page', DASHBOARD_PERPAGE) : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'DESC';
                $by = 'date_of_occurrence';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            // SECURITY: Employees cannot Delete or Add cases from this view. Override grid actions.
            $dataTmp['add'] = '';

            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) {
            error_log("MyIncident Index Error: " . $e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->caseData = $this->_model->getRecordById($id);
        $this->view->id = $id;
        $this->view->controllername = 'myincident';
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');

        // Security check: Is case closed? If so, block editing.
        $caseData = $this->_model->getRecordById($id);
        if ($caseData && $caseData['case_status'] === 'Closed') {
            $this->_helper->flashMessenger->addMessage(array('error' => "This case is closed and cannot be modified."));
            $this->_redirect('myincident/index');
        }

        $this->view->form = $this->_prepareForm($id, false);
        $this->view->caseData = $caseData;
        $this->render('save');
    }

    public function saveAction()
    {
        $form = new Default_Form_Myincident();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $appealValue = (int)$request->getParam('employee_appeal');
                    $data = array(
                        'employee_appeal'    => $appealValue,
                        'employee_statement' => trim($request->getParam('employee_statement'))
                    );

                    // If they select "Yes" to appeal, automatically update case status
                    if ($appealValue === 1) {
                        $data['case_status'] = 'Appealed';
                    }

                    $this->_model->saveRecord($data, $id);

                    // Log the employee's action to the timeline
                    $logMsg = ($appealValue === 1) ? "Employee submitted an Appeal and Statement." : "Employee submitted a Statement (No Appeal).";
                    $this->_model->addCaseLog($id, $logMsg, $this->_loginUserId);

                    $menuId = defined('MY_INCIDENT') ? MY_INCIDENT : 204;
                    sapp_Global::logManager($menuId, 2, $this->_loginUserId, $id);

                    $db->commit();

                    $this->_helper->flashMessenger->addMessage(array('success' => "Statement submitted successfully."));
                    $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("MyIncident Save Error: " . $e->getMessage());
                    $this->_helper->json(array('result' => 'exception', 'message' => 'System error occurred while submitting statement.'));
                }
            } else {
                $msgarray = array();
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $this->_helper->json($msgarray);
            }
        }
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Myincident();

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);
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
