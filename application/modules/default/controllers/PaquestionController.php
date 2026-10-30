<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Question Controller
 * 
 * Manages the CRUD and Grid logic for Appraisal Questions. Handles the Junction
 * transaction for Associated Skills.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_PaquestionController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Paquestion();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            // SSOT V.2
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
                die(Zend_Json::encode(array('login' => 'failed')));
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

            // SSOT IX.1: Strict string casting
            $searchData    = (string)$request->getParam('searchData', '');

            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);

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

            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) { // SSOT IX.2
            error_log("PaQuestion Index Error: " . $e->getMessage());
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'paquestion';
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
        $form = new Default_Form_Paquestion();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->_populateDropdowns($form);

            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V.4

                try {
                    $data = array(
                        'category_id' => (int)$request->getParam('category_id'),
                        'rating_id'   => (int)$request->getParam('rating_id'),
                        'weight'      => (float)$request->getParam('weight', 1.00),
                        'name'        => trim($request->getParam('name')),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive', 1)
                    );

                    $actionFlag = ($id > 0) ? 2 : 1; // 2=Edit, 1=Add
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    // SSOT III.2 (Group 6): Poly Junction Sync 
                    // Safely cast multiselect to array to prevent empty scalar errors
                    $skillIds = (array)$request->getParam('skill_ids', array());
                    $this->_model->syncSkills($recordId, $skillIds);

                    // SSOT V.5: Dynamic Menu ID logging
                    $menuId = defined('APPRAISALQUESTIONS') ? APPRAISALQUESTIONS : 152;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(array('success' => "Question $msg successfully."));

                    // SSOT V.6: Strict JSON Response
                    $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("PaQuestion Save Error: " . $e->getMessage());
                    $this->_helper->json(array('result' => 'exception', 'message' => 'System error occurred while saving the record.'));
                }
            } else {
                // SSOT V.8: Extract and flatten Zend Form error messages
                $msgarray = array();
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $this->_helper->json($msgarray);
            }
        }
    }

    /**
     * SSOT VIII: The "On-The-Fly" Popup Pattern
     * 
     * Handles adding a new Question from an iframe modal.
     * Integrates the Many-to-Many Skill junction sync.
     * 
     * @return void
     */
    public function addpopupAction()
    {
        // SSOT VIII.2: Switch to popup layout shell
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $form = new Default_Form_Paquestion();
        $form->setAction(BASE_URL . 'paquestion/addpopup');

        // SSOT VIII.3: Strip the global AJAX submit handler; popup requires standard POST
        $form->getElement('submit')->setAttrib('onclick', null);

        // Populate dropdowns required for the form
        $this->_populateDropdowns($form);

        $request = $this->getRequest();
        $msgarray = array();
        $this->view->popup = ''; // Initialize popup state

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $data = array(
                        'category_id' => (int)$request->getParam('category_id'),
                        'rating_id'   => (int)$request->getParam('rating_id'),
                        'weight'      => (float)$request->getParam('weight', 1.00),
                        'name'        => trim($request->getParam('name')),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive', 1)
                    );

                    $newId = $this->_model->saveRecord($data);

                    // SSOT III.2 (Group 6): Poly Junction Sync 
                    // Safely cast multiselect to array to prevent empty scalar errors
                    $skillIds = (array)$request->getParam('skill_ids', array());
                    $this->_model->syncSkills($newId, $skillIds);

                    // Logging Menu ID for Appraisal Questions
                    sapp_Global::logManager(APPRAISALQUESTIONS, 1, $this->_loginUserId, $newId);

                    $db->commit();

                    // SSOT VIII.4: Generate new dropdown options string for the parent window
                    $activeRecords = $this->_model->getPairs(true);
                    $optionsHtml = '';
                    foreach ($activeRecords as $valId => $valName) {
                        $optionsHtml .= sapp_Global::selectOptionBuilder($valId, $valName);
                    }

                    $this->view->newInsertedId = $newId;
                    $this->view->optionsHtml   = $optionsHtml;
                    $this->view->eventact      = 'added';
                    $this->view->popup         = 'close'; // Triggers View success state

                } catch (\Throwable $dbError) {
                    $db->rollBack();
                    $msgarray['error'] = 'Database Error: ' . $dbError->getMessage();
                }
            } else {
                // Flatten validation errors for display in the view
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
            }
        }

        $this->view->controllername = 'paquestion';
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

                $menuId = defined('APPRAISALQUESTIONS') ? APPRAISALQUESTIONS : 152;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);

                $this->_helper->json(array('msgtype' => 'success', 'message' => 'Question deleted successfully.'));
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

            $menuId = defined('APPRAISALQUESTIONS') ? APPRAISALQUESTIONS : 152;
            sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);

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
        $catModel    = new Default_Model_Pacategory();
        $ratingModel = new Default_Model_Parating();

        $form->category_id->addMultiOption('', 'Select Category')->addMultiOptions($catModel->getPairs(true));
        $form->rating_id->addMultiOption('', 'Select Rating Scale')->addMultiOptions($ratingModel->getPairs(true));

        // Populate the Multiselect (Skills)
        if (class_exists('Default_Model_Skill')) {
            $skillModel = new Default_Model_Skill();
            if (method_exists($skillModel, 'getPairs')) {
                $form->skill_ids->addMultiOptions($skillModel->getPairs(true));
            }
        }
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Paquestion();
        $this->_populateDropdowns($form);

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
            }
        }

        // SSOT V.7
        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }
}
