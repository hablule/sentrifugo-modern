<?php

/**
 * State Controller
 * 
 * Manages the CRUD, Grid, and On-the-Fly Popup logic for States/Provinces.
 * Strictly adheres to SSOT v6.1.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_StateController extends Zend_Controller_Action
{
    /** @var int Logged in user ID */
    protected $_loginUserId;

    /** @var int Logged in user Role ID */
    protected $_loginuserRole;

    /** @var int Logged in user Group ID */
    protected $_loginuserGroup;

    /** @var Default_Model_State Instance of the State Model */
    protected $_model;

    /** @var array Application configuration options */
    protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initialize Controller, Model, and Auth properties.
     * 
     * @return void
     */
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_State();

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

    /**
     * Pre-dispatch routines for Session validation and AJAX context switching.
     * 
     * @return void
     */
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

    /**
     * Index Action (Grid View)
     * 
     * @return void
     */
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

            // SSOT V: Default sort is ASC by country, then state name
            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'country_name');
            $perPage = ($dashboardcall == 'Yes') ? $request->getParam('per_page', DASHBOARD_PERPAGE) : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'ASC';
                $by = 'country_name';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) {
            error_log("State Index Error: " . $e->getMessage());
        }
    }

    /**
     * View Action (Read-Only Detail)
     * 
     * @return void
     */
    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'state';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    /**
     * Add Action (Renders Save View)
     * 
     * @return void
     */
    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    /**
     * Edit Action (Renders Save View)
     * 
     * @return void
     */
    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, false);
        $this->render('save');
    }

    /**
     * Save Action (AJAX POST Processor)
     * 
     * @return void Outputs strict JSON contract
     */
    public function saveAction()
    {
        $form = new Default_Form_State();
        $request = $this->getRequest();

        // Repopulate for validator so it knows the valid options
        $this->_populateDropdowns($form);

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $data = array(
                        'country_id'  => (int)$request->getParam('country_id'),
                        'name'        => trim($request->getParam('name')),
                        'code'        => trim($request->getParam('code')),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive')
                    );

                    $actionFlag = ($id > 0) ? 2 : 1;
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    // Sync Polymorphic Geography Groups
                    $selectedGroups = $request->getParam('geographygroup_id');
                    if (!is_array($selectedGroups)) {
                        $selectedGroups = empty($selectedGroups) ? array() : array($selectedGroups);
                    }
                    $this->_model->syncGeographyGroupMappings($recordId, $selectedGroups);

                    // Menu ID 101 = States
                    $menuId = defined('STATES') ? STATES : 101;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(array('success' => "State/Province $msg successfully."));

                    $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("State Save Error: " . $e->getMessage());
                    $this->_helper->json(array('result' => 'exception', 'message' => 'System error occurred.'));
                }
            } else {
                $this->_helper->json($form->getMessages());
            }
        }
    }

    /**
     * Phase 4 Feature: On-The-Fly Popup Addition
     * 
     * @return void
     */
    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $form = new Default_Form_State();
        $this->_populateDropdowns($form);
        $form->setAction(BASE_URL . 'state/addpopup');
        $form->getElement('submit')->setAttrib('onclick', null);

        $request = $this->getRequest();
        $msgarray = array();
        $this->view->popup = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $data = array(
                        'country_id'  => (int)$request->getParam('country_id'),
                        'name'        => trim($request->getParam('name')),
                        'code'        => trim($request->getParam('code')),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive', 1)
                    );

                    $newId = $this->_model->saveRecord($data);

                    $menuId = defined('STATES') ? STATES : 101;
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

        $this->view->controllername = 'state';
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
                $menuId = defined('STATES') ? STATES : 101;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);
                $this->_helper->json(array('msgtype' => 'success', 'message' => 'State deleted successfully.'));
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

            $menuId = defined('STATES') ? STATES : 101;
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
     * AJAX Endpoint: Retrieves a flat JSON array of States [id => name] for a given Country.
     * Used for cascading dropdowns (e.g., Country -> State).
     * 
     * @return void Outputs JSON and exits.
     */
    public function getstatepairsAction()
    {
        // 1. Disable View Rendering (Crucial for clean JSON output in ZF1)
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();

        // 2. Validate Request Type (Strictness)
        if (!$request->isPost()) {
            echo Zend_Json::encode(array('error' => 'Invalid request method.'));
            exit;
        }

        try {
            // 3. Extract and Sanitize Parameters
            $countryId = (int)$request->getParam('country_id', 0);

            if ($countryId <= 0) {
                // Return empty JSON object if no valid country is provided
                echo Zend_Json::encode(new stdClass());
                exit;
            }

            // 4. Fetch Data using the SSOT Model Standard
            $stateModel = new Default_Model_State();
            
            // Returns a flat associative array: [1 => 'California', 2 => 'Texas']
            $statePairs = $stateModel->getPairsByCountry($countryId, true);

            // 5. Output Clean JSON
            // If array is empty, output {} instead of [] so JS $.each iterates correctly
            $output = empty($statePairs) ? new stdClass() : $statePairs;
            
            echo Zend_Json::encode($output);
            exit;

        } catch (\Throwable $e) {
            // 6. Safe Error Handling (PHP 7.4/8+ Compatible)
            error_log("StatePairs AJAX Error: " . $e->getMessage());
            
            // Return an empty object so the UI dropdown simply shows "Select State" 
            // without crashing the Javascript execution thread.
            echo Zend_Json::encode(new stdClass());
            exit;
        }
    }

    /**
     * Internal Helper: Prepares the State Form for Add/Edit/View actions.
     * - If $id is provided, populates the form with existing data.
     * - If $readonly is true, disables all form elements and removes the submit button.
     * 
     * @param int|null $id Optional State ID for Edit/View. Null for Add.
     * @param bool $readonly If true, form is rendered in read-only mode (for View action).
     * @return Default_Form_State The prepared form instance ready for rendering.
     * 
     * Note: This method is strictly for internal use within this controller and assumes the form class and model adhere to SSOT standards.
     * 
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_State();
        $this->_populateDropdowns($form);

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {

                $data['geographygroup_id'] = $this->_model->getMappedGeographyGroupIds($id);

                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
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

    /**
     * Fetches relational data to populate the form Select fields.
     */
    private function _populateDropdowns(Zend_Form $form)
    {
        $util = new utlis();
        $countryModel = new Default_Model_Country();
        $countries = $countryModel->getPairs(true);
        $form->country_id->addMultiOption('', 'Select Country...');
        $form->country_id->addMultiOptions($countries);

        // Populate Geography Groups
        $groupModel = new Default_Model_Geographygroup();
        $geoGroups = $groupModel->getPairs(true);
        $form->geographygroup_id->addMultiOptions($geoGroups);
    }
}
