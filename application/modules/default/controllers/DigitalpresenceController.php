<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Digital Presence Controller
 * 
 * Manages the CRUD and Grid logic for Digital Presence mappings.
 * Strictly adheres to SSOT v6.2, explicitly implementing SSOT V.10 
 * (Poly-Hierarchical Integrity Validation).
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_DigitalpresenceController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Digitalpresence();

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

            // SSOT V.3: Default sort ASC
            $sort    = $request->getParam('sort', 'DESC');
            $by      = $request->getParam('by', 'id');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'DESC';
                $by = 'id';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) { // SSOT IX.2
            error_log("DigitalPresence Index Error: " . $e->getMessage());
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'digitalpresence';
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
        $form = new Default_Form_Digitalpresence();
        $request = $this->getRequest();

        if ($request->isPost()) {
            // Re-populate dropdowns for validation
            $this->_populateDropdowns($form);

            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V.4: Transactions Mandatory

                try {
                    // ===================================================================
                    // SSOT V.10: Poly-Hierarchical Integrity Validation
                    // Enforce exactly one parent organizational unit/owner
                    // ===================================================================
                    $parentIds = [
                        'company_id'    => $request->getParam('company_id'),
                        'sbunit_id'     => $request->getParam('sbunit_id'),
                        'division_id'   => $request->getParam('division_id'),
                        'department_id' => $request->getParam('department_id'),
                        'section_id'    => $request->getParam('section_id'),
                        'project_id'    => $request->getParam('project_id'),
                        'team_id'       => $request->getParam('team_id'),
                        'employee_id'   => $request->getParam('employee_id')
                    ];

                    // Filter out empty values to count how many parents were selected
                    $activeParents = array_filter($parentIds, fn($val) => !empty($val) && (int)$val > 0);

                    if (count($activeParents) !== 1) {
                        throw new Exception("Integrity Error: This Digital Presence record must be assigned to exactly one owner (e.g., Company OR Employee OR Department). You selected " . count($activeParents) . ".");
                    }

                    // Prepare Data Payload
                    $data = [
                        'digitalmedia_id' => (int)$request->getParam('digitalmedia_id'),
                        'company_id'      => !empty($parentIds['company_id']) ? (int)$parentIds['company_id'] : null,
                        'sbunit_id'       => !empty($parentIds['sbunit_id']) ? (int)$parentIds['sbunit_id'] : null,
                        'division_id'     => !empty($parentIds['division_id']) ? (int)$parentIds['division_id'] : null,
                        'department_id'   => !empty($parentIds['department_id']) ? (int)$parentIds['department_id'] : null,
                        'section_id'      => !empty($parentIds['section_id']) ? (int)$parentIds['section_id'] : null,
                        'project_id'      => !empty($parentIds['project_id']) ? (int)$parentIds['project_id'] : null,
                        'team_id'         => !empty($parentIds['team_id']) ? (int)$parentIds['team_id'] : null,
                        'employee_id'     => !empty($parentIds['employee_id']) ? (int)$parentIds['employee_id'] : null,
                        'digitaladdress'  => trim($request->getParam('digitaladdress')),
                        'description'     => trim($request->getParam('description')),
                        'isactive'        => (int)$request->getParam('isactive', 1)
                    ];

                    $actionFlag = ($id > 0) ? 2 : 1; // 2=Edit, 1=Add
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    // SSOT V.5: Dynamic Menu ID logging (295 = Digital Presence)
                    $menuId = defined('DIGITALPRESENCES') ? DIGITALPRESENCES : 295;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(['success' => "Digital Presence $msg successfully."]);

                    // SSOT V.6: Strict JSON Response
                    $this->_helper->json([
                        'result'    => 'saved',
                        'nomessage' => 'yes'
                    ]);
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("DigitalPresence Save Error: " . $e->getMessage());
                    // If it's our custom integrity exception, return it cleanly to the UI
                    if (str_contains($e->getMessage(), 'Integrity Error')) {
                        $this->_helper->json(['error' => $e->getMessage()]);
                    } else {
                        $this->_helper->json(['result' => 'exception', 'message' => 'System error occurred while saving the record.']);
                    }
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

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                $this->_model->deleteRecord($id);

                $menuId = defined('DIGITALPRESENCES') ? DIGITALPRESENCES : 295;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);

                $this->_helper->json(['msgtype' => 'success', 'message' => 'Record deleted successfully.']);
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

            $menuId = defined('DIGITALPRESENCES') ? DIGITALPRESENCES : 295;
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
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Digitalpresence();
        $this->_populateDropdowns($form);

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

    /**
     * Populates all 9 dropdowns using strict addMultiOptions (SSOT VI.12).
     * Relies on the standard getPairs() method implemented across all Sentrifugo models.
     */
    private function _populateDropdowns(Zend_Form $form)
    {
        // Media (Direct Parent)
        $mediaModel = new Default_Model_Digitalmedia();
        $form->digitalmedia_id->addMultiOption('', 'Select Media Platform')->addMultiOptions($mediaModel->getPairs(true));

        // The 8 Polymorphic Owners
        $models = [
            'company_id'    => ['model' => 'Default_Model_Company', 'label' => 'Select Company'],
            'sbunit_id'     => ['model' => 'Default_Model_Businessunit', 'label' => 'Select Business Unit'],
            'division_id'   => ['model' => 'Default_Model_Division', 'label' => 'Select Division'],
            'department_id' => ['model' => 'Default_Model_Department', 'label' => 'Select Department'],
            'section_id'    => ['model' => 'Default_Model_Section', 'label' => 'Select Section'],
            'project_id'    => ['model' => 'Default_Model_Project', 'label' => 'Select Project'],
            'team_id'       => ['model' => 'Default_Model_Team', 'label' => 'Select Team'],
            // Using Usermanagement model as it often contains the unified getPairs for employees in Sentrifugo,
            // or Default_Model_Employee if specifically implemented. Assuming standard Employee model exists:
            'employee_id'   => ['model' => 'Default_Model_Employee', 'label' => 'Select Employee']
        ];

        foreach ($models as $field => $config) {
            if (class_exists($config['model'])) {
                $modelObj = new $config['model']();
                if (method_exists($modelObj, 'getPairs')) {
                    $form->getElement($field)->addMultiOption('', $config['label'])
                        ->addMultiOptions($modelObj->getPairs(true));
                } else {
                    // Fallback for models that might not have getPairs yet
                    $form->getElement($field)->addMultiOption('', $config['label']);
                }
            }
        }
    }
}
