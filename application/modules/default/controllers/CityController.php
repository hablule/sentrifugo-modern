<?php

/**
 * City Controller
 * 
 * Manages the CRUD, Grid, and On-the-Fly Popup logic for Geographical Cities.
 * Strictly adheres to SSOT v6.1: PHP 7.4/8.0+ safe, transactional, 
 * strictly formatted JSON responses, decoupled form logic, and composite validation.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_CityController extends Zend_Controller_Action
{
    /** @var int Logged in user ID */
    protected $_loginUserId;

    /** @var int Logged in user Role ID */
    protected $_loginuserRole;

    /** @var int Logged in user Group ID */
    protected $_loginuserGroup;

    /** @var Default_Model_City Instance of the City Model */
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
        $this->_model   = new Default_Model_City();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();

            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            // SSOT V: Explicitly assign context to view for UI-level ACL checks
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
                die(Zend_Json::encode(['login' => 'failed']));
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
            ->addActionContext('delete', 'json')
            ->addActionContext('makeactiveinactive', 'json')
            ->addActionContext('getstates', 'json') // For the cascading dropdown
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
            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);

            // SSOT VIII: Strict string casting to prevent PHP 8 rtrim() Fatal TypeError
            $searchData    = (string)$request->getParam('searchData', '');

            // SSOT V: Default sort is ASC by hierarchy (Country)
            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'country_name');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'ASC';
                $by   = 'country_name';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) { // SSOT VIII: Catch \Throwable
            error_log("City Index Error: " . $e->getMessage());
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
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
        $this->view->controllername = 'city';
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
     * @return void Outputs strict JSON contract expected by Sentrifugo hrms.js
     */
    public function saveAction()
    {
        $form = new Default_Form_City();
        $request = $this->getRequest();

        // Re-populate dropdowns before validation so 'InArray' validation passes
        $this->_populateDropdowns($form, (int)$request->getParam('country_id'));

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id       = (int)$request->getParam('id');
                $stateId  = (int)$request->getParam('state_id');
                $cityName = trim($request->getParam('name'));

                // Custom Composite Uniqueness Check: Cannot have duplicate city in the SAME state
                if ($this->_model->checkDuplicateCity($stateId, $cityName, $id)) {
                    $this->_helper->json([
                        'result'  => 'error',
                        'message' => ['name' => 'This city name already exists within the selected state.']
                    ]);
                    return;
                }

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V: Transactions Mandatory

                try {
                    $data = [
                        'state_id'    => $stateId,
                        'name'        => $cityName,
                        'code'        => trim($request->getParam('code')),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive')
                    ];

                    $actionFlag = ($id > 0) ? 2 : 1; // 2=Edit, 1=Add
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    // Sync Polymorphic Geography Groups
                    $selectedGroups = $request->getParam('geographygroup_id');
                    if (!is_array($selectedGroups)) {
                        $selectedGroups = empty($selectedGroups) ? [] : [$selectedGroups];
                    }
                    $this->_model->syncGeographyGroupMappings($recordId, $selectedGroups);

                    // SSOT I: Dynamic Menu ID logging (102 = Cities)
                    $menuId = defined('CITIES') ? CITIES : 102;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

                    $db->commit();

                    // Set Flash message for the NEXT page load
                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(['success' => "City $msg successfully."]);

                    // SSOT V: Strict JSON Response for Global Redirect Helper
                    $this->_helper->json([
                        'result'    => 'saved',
                        'nomessage' => 'yes'
                    ]);
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("City Save Error: " . $e->getMessage());
                    // Legacy Exception Contract
                    $this->_helper->json(['result' => 'exception', 'message' => 'System error occurred.']);
                }
            } else {
                // SSOT V: Strict JSON Response for Global Validation Helper
                $msgarray = [];
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $this->_helper->json($msgarray);
            }
        }
    }

    /**
     * Phase 4 Feature: On-The-Fly Popup Addition
     * Disables layout, handles standard POST (no AJAX), and returns 
     * JavaScript options string to the parent window DOM upon success.
     * 
     * @return void
     */
    public function addpopupAction()
    {
        // SSOT VII: Switch to popup layout shell
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $form = new Default_Form_City();
        $request = $this->getRequest();

        $this->_populateDropdowns($form, (int)$request->getParam('country_id'));
        $form->setAction(BASE_URL . 'city/addpopup');

        // Strip the global AJAX submit handler; popup requires standard POST to trigger JS callback
        $form->getElement('submit')->setAttrib('onclick', null);

        $msgarray = [];

        // Initialize popup state to prevent view rendering notices
        $this->view->popup = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {

                $stateId  = (int)$request->getParam('state_id');
                $cityName = trim($request->getParam('name'));

                if ($this->_model->checkDuplicateCity($stateId, $cityName)) {
                    $msgarray['name'] = 'This city name already exists within the selected state.';
                } else {
                    $db = Zend_Db_Table::getDefaultAdapter();
                    $db->beginTransaction();

                    try {
                        $data = [
                            'state_id'    => $stateId,
                            'name'        => $cityName,
                            'code'        => trim($request->getParam('code')),
                            'description' => trim($request->getParam('description')),
                            'isactive'    => (int)$request->getParam('isactive', 1)
                        ];

                        $newId = $this->_model->saveRecord($data);

                        $menuId = defined('CITIES') ? CITIES : 102;
                        sapp_Global::logManager($menuId, 1, $this->_loginUserId, $newId);

                        $db->commit();

                        // Generate new dropdown options string for the parent window
                        // (Filtered by the specific state that was selected in the popup)
                        $activeRecords = $this->_model->getPairs(true, $stateId);
                        $optionsHtml = '';
                        foreach ($activeRecords as $valId => $valName) {
                            $optionsHtml .= sapp_Global::selectOptionBuilder($valId, $valName);
                        }

                        $this->view->optionsHtml = $optionsHtml;
                        $this->view->eventact    = 'added';
                        $this->view->popup       = 'close'; // Triggers View success state

                    } catch (\Throwable $dbError) {
                        $db->rollBack();
                        $msgarray['error'] = 'Database Error: ' . $dbError->getMessage();
                    }
                }
            } else {
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
            }
        }

        $this->view->controllername = 'city';
        $this->view->msgarray       = $msgarray;
        $this->view->form           = $form;
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    /**
     * Delete Action (AJAX POST Processor)
     * 
     * @return void Outputs strict JSON contract
     */
    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                // Dependency check occurs inside the Model per SSOT IV
                $this->_model->deleteRecord($id);

                $menuId = defined('CITIES') ? CITIES : 102;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);

                $this->_helper->json(['msgtype' => 'success', 'message' => 'City deleted successfully.']);
            } catch (\Throwable $e) {
                // Catch the model exception (e.g., Record is in use)
                $this->_helper->json(['msgtype' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            $this->_helper->json(['msgtype' => 'error', 'message' => 'Invalid Request.']);
        }
    }

    /**
     * Make Active/Inactive Action (AJAX POST Processor)
     * 
     * @return void Outputs strict JSON contract
     */
    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');

            $this->_model->toggleStatus($id, $status);

            $menuId = defined('CITIES') ? CITIES : 102;
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
     * AJAX Endpoint: Retrieves a flat JSON array of States [id => name] for a given Country.
     * Used for cascading dropdowns (e.g., Country -> State).
     * 
     * @return void Outputs JSON and exits.
     */
    public function getcitypairsAction()
    {
        // 1. Disable View Rendering (Crucial for clean JSON output in ZF1)
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();

        // 2. Validate Request Type (Strictness)
        if (!$request->isPost()) {
            echo Zend_Json::encode(['error' => 'Invalid request method.']);
            exit;
        }

        try {
            // 3. Extract and Sanitize Parameters
            $stateId = (int)$request->getParam('state_id', 0);

            if ($stateId <= 0) {
                // Return empty JSON object if no valid state is provided
                echo Zend_Json::encode(new stdClass());
                exit;
            }

            // 4. Fetch Data using the SSOT Model Standard
            $cityModel = new Default_Model_City();
            
            // Returns a flat associative array: [1 => 'California', 2 => 'Texas']
            $cityPairs = $cityModel->getPairsByState($stateId, true);

            // 5. Output Clean JSON
            // If array is empty, output {} instead of [] so JS $.each iterates correctly
            $output = empty($cityPairs) ? new stdClass() : $cityPairs;
            
            echo Zend_Json::encode($output);
            exit;

        } catch (\Throwable $e) {
            // 6. Safe Error Handling (PHP 7.4/8+ Compatible)
            error_log("CityPairs AJAX Error: " . $e->getMessage());
            
            // Return an empty object so the UI dropdown simply shows "Select State" 
            // without crashing the Javascript execution thread.
            echo Zend_Json::encode(new stdClass());
            exit;
        }
    }

    /**
     * Form Preparation Helper
     * 
     * Instantiates the form, populates it if editing, and locks fields if viewing.
     * SSOT V: Does NOT manipulate the onclick attribute.
     * 
     * @param int|null $id       Optional ID for populating data.
     * @param bool     $readonly If true, disables all elements.
     * @return Default_Form_City
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_City();

        // 1. Initial dropdown population (Countries only by default)
        $this->_populateDropdowns($form);

        if ($id) {
            // 2. Fetch data (This includes the joined 'country_id' from the Model)
            $data = $this->_model->getRecordById($id);
            if ($data) {

                // 3. Since we now know the country_id, populate the State dropdown specifically for that country
                $this->_populateDropdowns($form, (int)$data['country_id']);

                $data['geographygroup_id'] = $this->_model->getMappedGeographyGroupIds($id);

                // 4. Bind the data
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
     * Populates the Country and State dropdowns safely.
     * Uses native Zend_Form methods to handle the flat arrays returned by SSOT getPairs().
     * 
     * @param Zend_Form $form
     * @param int|null  $countryId If provided, filters the states array to only those in the country.
     * @return void
     */
    
    /**
     * Populates the Country and State dropdowns safely.
     * Uses native Zend_Form methods and strictly adheres to the SSOT 
     * by utilizing dedicated relational helper methods from the Models.
     * 
     * @param Zend_Form $form
     * @param int|null  $countryId If provided, filters the states array to only those in the country.
     * @return void
     */
    private function _populateDropdowns(Zend_Form $form, $countryId = null)
    {
        // 1. Populate Countries (Top Level)
        $countryModel = new Default_Model_Country();
        $countries = $countryModel->getPairs(true);
        
        $form->country_id->addMultiOption('', 'Select Country');
        $form->country_id->addMultiOptions($countries);

        // 2. Populate States (Relational Level)
        $stateModel = new Default_Model_State();
        
        if ($countryId) {
            // Use the dedicated relational method instead of hacking getPairs()
            $states = $stateModel->getPairsByCountry($countryId, true);
            
            $form->state_id->addMultiOption('', 'Select State/Province');
            $form->state_id->addMultiOptions($states);
        } else {
            // If no country is selected yet (initial load of Add form)
            $form->state_id->addMultiOption('', 'Select Country First');
        }

        // Populate Geography Groups
        $groupModel = new Default_Model_Geographygroup();
        $geoGroups = $groupModel->getPairs(true);
        $form->geographygroup_id->addMultiOptions($geoGroups);

    }

    /**
     * AJAX Endpoint: Get States by Country ID
     * Fired by the 'onchange' event on the Country dropdown in the UI.
     * 
     * @return void JSON array of states
     */
    public function getstatesAction()
    {
        $countryId = (int)$this->getRequest()->getParam('country_id');
        $states = [];

        if ($countryId) {
            $stateModel = new Default_Model_State();
            $stateData = $stateModel->getPairsByCountry($countryId, true);

            // Format as array of objects for easier JS parsing in Sentrifugo
            foreach ($stateData as $id => $name) {
                $states[] = ['id' => $id, 'name' => $name];
            }
        }

        $this->_helper->json($states);
    }
}
