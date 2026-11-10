<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Digital Media Controller
 * 
 * Manages the CRUD, Grid, and Popup logic for Digital Media.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_DigitalmediaController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Digitalmedia();

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

            // SSOT IX.1: Strict string casting
            $searchData    = (string)$request->getParam('searchData', '');

            // SSOT V.3: Default sort ASC by name
            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'name');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'ASC';
                $by = 'name';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) { // SSOT IX.2
            error_log("DigitalMedia Index Error: " . $e->getMessage());
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'digitalmedia';
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

    public function saveAction()
    {
        $form = new Default_Form_Digitalmedia();
        $request = $this->getRequest();

        if ($request->isPost()) {
            // Re-populate dropdowns before validation so 'InArray' checks pass
            $catModel  = new Default_Model_Digitalmediacategory();
            $typeModel = new Default_Model_Digitalmediatype();
            $form->digitalmediacategory_id->addMultiOption('', 'Select Category')->addMultiOptions($catModel->getPairs(true));
            $form->digitalmediatype_id->addMultiOption('', 'Select Type')->addMultiOptions($typeModel->getPairs(true));

            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V.4: Transactions Mandatory

                try {
                    $data = [
                        'digitalmediacategory_id' => (int)$request->getParam('digitalmediacategory_id'),
                        'digitalmediatype_id'     => (int)$request->getParam('digitalmediatype_id'),
                        'name'                    => trim($request->getParam('name')),
                        'code'                    => trim($request->getParam('code')),
                        'description'             => trim($request->getParam('description')),
                        'isactive'                => (int)$request->getParam('isactive', 1)
                    ];

                    $actionFlag = ($id > 0) ? 2 : 1; // 2=Edit, 1=Add
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    // SSOT V.5: Dynamic Menu ID logging (286 = Digital Media)
                    $menuId = defined('DIGITALMEDIA') ? DIGITALMEDIA : 286;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(['success' => "Digital Media $msg successfully."]);

                    // SSOT V.6: Strict JSON Response
                    $this->_helper->json([
                        'result'    => 'saved',
                        'nomessage' => 'yes'
                    ]);
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("DigitalMedia Save Error: " . $e->getMessage());
                    $this->_helper->json(['result' => 'exception', 'message' => 'System error occurred while saving the record.']);
                }
            } else {
                // SSOT V.8: Extract and flatten Zend Form error messages
                $msgarray = [];
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $this->_helper->json($msgarray);
            }
        }
    }

    /**
     * SSOT VIII: The "On-The-Fly" Popup Pattern
     */
    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $form = new Default_Form_Digitalmedia();
        $form->setAction(BASE_URL . 'digitalmedia/addpopup');
        $form->getElement('submit')->setAttrib('onclick', null);

        $catModel  = new Default_Model_Digitalmediacategory();
        $typeModel = new Default_Model_Digitalmediatype();
        $form->digitalmediacategory_id->addMultiOption('', 'Select Category')->addMultiOptions($catModel->getPairs(true));
        $form->digitalmediatype_id->addMultiOption('', 'Select Type')->addMultiOptions($typeModel->getPairs(true));

        $request = $this->getRequest();
        $msgarray = [];
        $this->view->popup = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $data = [
                        'digitalmediacategory_id' => (int)$request->getParam('digitalmediacategory_id'),
                        'digitalmediatype_id'     => (int)$request->getParam('digitalmediatype_id'),
                        'name'                    => trim($request->getParam('name')),
                        'code'                    => trim($request->getParam('code')),
                        'description'             => trim($request->getParam('description')),
                        'isactive'                => (int)$request->getParam('isactive', 1)
                    ];

                    $newId = $this->_model->saveRecord($data);

                    $menuId = defined('DIGITALMEDIA') ? DIGITALMEDIA : 286;
                    sapp_Global::logManager($menuId, 1, $this->_loginUserId, $newId);

                    $db->commit();

                    $activeRecords = $this->_model->getPairs(true);
                    $optionsHtml = '';
                    foreach ($activeRecords as $valId => $valName) {
                        $optionsHtml .= sapp_Global::selectOptionBuilder($valId, $valName);
                    }

                    $this->view->optionsHtml = $optionsHtml;
                    $this->view->eventact    = 'added';
                    $this->view->popup       = 'close';
                } catch (\Throwable $dbError) {
                    $db->rollBack();
                    $msgarray['error'] = 'Database Error: ' . $dbError->getMessage();
                }
            } else {
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
            }
        }

        $this->view->controllername = 'digitalmedia';
        $this->view->msgarray       = $msgarray;
        $this->view->form           = $form;
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

                $menuId = defined('DIGITALMEDIA') ? DIGITALMEDIA : 286;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);

                $this->_helper->json(['msgtype' => 'success', 'message' => 'Media deleted successfully.']);
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

            $menuId = defined('DIGITALMEDIA') ? DIGITALMEDIA : 286;
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
     * Form Preparation Helper
     * 
     * @param int|null $id
     * @param bool     $readonly
     * @return Default_Form_Digitalmedia
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Digitalmedia();

        // SSOT VI.12: Strict Native Dropdown Population
        $catModel  = new Default_Model_Digitalmediacategory();
        $typeModel = new Default_Model_Digitalmediatype();

        $form->digitalmediacategory_id->addMultiOption('', 'Select Category')
            ->addMultiOptions($catModel->getPairs(true));

        $form->digitalmediatype_id->addMultiOption('', 'Select Type')
            ->addMultiOptions($typeModel->getPairs(true));

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
            }
        }

        // SSOT V.7: Do not manipulate onclick here, only handle readonly state
        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }
}
