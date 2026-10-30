<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * Report Catalog Controller
 * 
 * Manages the CRUD, Grid, and ACL logic for the Report Catalog.
 * Implements the "Merged Form" architecture for mapping Groups, Roles, and Employees.
 * Strictly adheres to SSOT v6.7.4.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_ReportcatalogController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Reportcatalog();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            // SSOT V.2: View-level ACL assignment
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

        // SSOT V.2: Strict AjaxContext registration
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

            // SSOT V.3: Default sort ASC by primary descriptor (name)
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
            error_log("ReportCatalog Index Error: " . $e->getMessage());
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'reportcatalog';
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
        $form = new Default_Form_Reportcatalog();
        $request = $this->getRequest();

        if ($request->isPost()) {

            // Populate dropdowns to satisfy Zend_Validate_InArray before checking isValid
            $this->_fetchDropdownOptionsForView($form);

            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V.4

                try {
                    $data = array(
                        'menugroup_id' => (int)$request->getParam('menugroup_id'),
                        'category_id' => (int)$request->getParam('category_id'),
                        'code'        => trim($request->getParam('code')),
                        'name'        => trim($request->getParam('name')),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive', 1)
                    );

                    $actionFlag = ($id > 0) ? 2 : 1;
                    $reportId = $this->_model->saveRecord($data, $id);

                    // =========================================================
                    // SSOT III.2 (Group 6): Polymorphic Junction Sync 
                    // Process JSON payloads from the Merged Form Tabs
                    // =========================================================
                    $groupsRaw    = json_decode((string)$request->getParam('groups_payload'), true);
                    $rolesRaw     = json_decode((string)$request->getParam('roles_payload'), true);
                    $employeesRaw = json_decode((string)$request->getParam('employees_payload'), true);

                    $groups    = is_array($groupsRaw) ? $groupsRaw : array();
                    $roles     = is_array($rolesRaw) ? $rolesRaw : array();
                    $employees = is_array($employeesRaw) ? $employeesRaw : array();

                    $this->_model->syncAclMappings($reportId, $groups, $roles, $employees);

                    // SSOT V.5: Dynamic Menu ID logging
                    $menuId = defined('REPORTCATALOG');
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $reportId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(array('success' => "Report Definition $msg successfully."));

                    // SSOT V.6: Strict JSON Response
                    $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("ReportCatalog Save Error: " . $e->getMessage());
                    $this->_helper->json(array('result' => 'exception', 'message' => 'System error occurred while saving.'));
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

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                $this->_model->deleteRecord($id);

                $menuId = defined('REPORTCATALOG');
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);

                $this->_helper->json(array('msgtype' => 'success', 'message' => 'Report definition deleted successfully.'));
            } catch (\Throwable $e) {
                // Dependency exception triggered by Model
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

            $menuId = defined('REPORTCATALOG');
            sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);

            $this->_helper->json(array('result' => 'yes'));
        } catch (\Throwable $e) {
            $this->_helper->json(array('result' => 'no'));
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * SSOT VIII: The "On-The-Fly" Popup Pattern
     * Handles adding a new Report Definition from an iframe modal.
     */
    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $form = new Default_Form_Reportcatalog();
        $form->setAction(BASE_URL . 'reportcatalog/addpopup');

        $request = $this->getRequest();
        $msgarray = array();
        $this->view->popup = '';

        if ($request->isPost()) {

            $this->_fetchDropdownOptionsForView($form);

            if ($form->isValid($request->getPost())) {
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $data = array(
                        'menugroup_id' => (int)$request->getParam('menugroup_id'),
                        'category_id' => (int)$request->getParam('category_id'),
                        'code'        => trim($request->getParam('code')),
                        'name'        => trim($request->getParam('name')),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive', 1)
                    );

                    $newId = $this->_model->saveRecord($data);

                    $menuId = defined('REPORTCATALOG');
                    sapp_Global::logManager($menuId, 1, $this->_loginUserId, $newId);

                    $db->commit();

                    // Generate new dropdown options string
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
        } else {
            $this->_fetchDropdownOptionsForView($form);
        }

        $this->view->controllername = 'reportcatalog';
        $this->view->msgarray       = $msgarray;
        $this->view->form           = $form;
    }

    /**
     * Prepares the form, hydrates data for Edit mode, and passes JSON arrays for the View Tabs.
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Reportcatalog();

        $this->_fetchDropdownOptionsForView($form);

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);

                // Hydrate Merged Form Tabs
                $mappings = $this->_model->getExistingAclMappings($id);
                $this->view->existingGroups    = json_encode($mappings['Group']);
                $this->view->existingRoles     = json_encode($mappings['Role']);
                $this->view->existingEmployees = json_encode($mappings['Employee']);
            }
        } else {
            // Default empty states
            $this->view->existingGroups    = '[]';
            $this->view->existingRoles     = '[]';
            $this->view->existingEmployees = '[]';
        }

        // SSOT V.7: Read-only View compliance
        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit_btn');
        }

        return $form;
    }

    /**
     * Populates form selects and provides raw arrays to the View for Custom Tabs.
     */
    private function _fetchDropdownOptionsForView(Zend_Form $form)
    {        
         // 1. Fetch Visual Menu Groups
        $menuGroupModel = new Default_Model_Menugroup();
        $form->menugroup_id->addMultiOption('', 'Select Dashboard Group')->addMultiOptions($menuGroupModel->getPairs(true));

        // 2. Category Form Element        
        $catModel = new Default_Model_Reportcategory();
        $form->category_id->addMultiOption('', 'Select Category')->addMultiOptions($catModel->getPairs(true));

        // 3. UI Tab Options (Groups, Roles, Employees)
        $groupModel = new Default_Model_Groups();
        $this->view->groupOptions = $groupModel->getPairs(true);

        $roleModel = new Default_Model_Roles();
        $this->view->roleOptions = $roleModel->getPairs(true);

        $empModel = new Default_Model_Employee();
        $this->view->employeeOptions = $empModel->getPairs(true);
    }
}
