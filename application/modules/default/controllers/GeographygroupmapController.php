<?php

/**
 * Geography Group Mapping Controller
 * 
 * Manages the CRUD, Grid, and Cascading Dropdown logic for mapping specific 
 * geographical entities (Countries, States, Cities, Postal Codes) to a parent 
 * Geography Group.
 * 
 * Strictly adheres to SSOT v6.1: PHP 7.4/8.0+ safe, transactional, 
 * strictly formatted JSON responses, decoupled form logic, and polymorphic routing.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_GeographygroupMapController extends Zend_Controller_Action
{
    /** @var int Logged in user ID */
    protected $_loginUserId;

    /** @var int Logged in user Role ID */
    protected $_loginuserRole;

    /** @var int Logged in user Group ID */
    protected $_loginuserGroup;

    /** @var Default_Model_GeographygroupMap Instance of the Mapping Model */
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
        $this->_model   = new Default_Model_GeographygroupMap();

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
                die(Zend_Json::encode(array('login' => 'failed')));
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
            ->addActionContext('delete', 'json')
            ->addActionContext('makeactiveinactive', 'json')
            ->addActionContext('getstates', 'json')
            ->addActionContext('getcities', 'json')
            ->addActionContext('getpostalcodes', 'json')
            ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Index Action (Grid View)
     * 
     * Note: This grid is typically rendered as a sub-grid inside the 
     * Geographygroup/view page, filtered by geographygroup_id.
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
            $groupId       = (int)$request->getParam('geographygroup_id');

            // SSOT VIII: Strict string casting to prevent PHP 8 rtrim() Fatal TypeError
            $searchData    = (string)$request->getParam('searchData', '');

            // SSOT V: Default sort is ASC by entity type
            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'entity_type');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'ASC';
                $by   = 'entity_type';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall, $groupId);

            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) { // SSOT VIII: Catch \Throwable
            error_log("Geography Group Map Index Error: " . $e->getMessage());
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
        $this->view->controllername = 'geographygroupmap';
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
        $form = new Default_Form_GeographygroupMap();
        $request = $this->getRequest();

        // Repopulate for validator
        $this->_populateDropdowns($form, (int)$request->getParam('country_id'), (int)$request->getParam('state_id'), (int)$request->getParam('city_id'));

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id         = (int)$request->getParam('id');
                $groupId    = (int)$request->getParam('geographygroup_id');
                $entityType = trim($request->getParam('entity_type'));
                $entityId   = (int)$request->getParam('entity_id');

                // Validations
                if (empty($entityId)) {
                    $this->_helper->json(array(
                        'result'  => 'error',
                        'message' => array('entity_id' => "Please select the specific $entityType from the dropdowns above.")
                    ));
                    return;
                }

                // SSOT IV: Custom Composite Uniqueness Check
                if ($this->_model->checkDuplicateMapping($groupId, $entityType, $entityId, $id)) {
                    $this->_helper->json(array(
                        'result'  => 'error',
                        'message' => array('entity_type' => "This exact $entityType mapping already exists in this group.")
                    ));
                    return;
                }

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V: Transactions Mandatory

                try {
                    $data = array(
                        'geographygroup_id' => $groupId,
                        'entity_type'       => $entityType,
                        'entity_id'         => $entityId,
                        'description'       => trim($request->getParam('description')),
                        'isactive'          => (int)$request->getParam('isactive')
                    );

                    $actionFlag = ($id > 0) ? 2 : 1; // 2=Edit, 1=Add
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    // SSOT I: Placeholder Menu ID for logging the Mapping action
                    $menuId = defined('GEOGRAPHY_GROUP_MENU_ID') ? GEOGRAPHY_GROUP_MENU_ID : 103;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(array('success' => "Mapping $msg successfully."));

                    $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("Geo Group Map Save Error: " . $e->getMessage());
                    $this->_helper->json(array('result' => 'exception', 'message' => 'System error occurred.'));
                }
            } else {
                // SSOT V: Strict JSON Response for Global Validation Helper
                $msgarray = array();
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $this->_helper->json($msgarray);
            }
        }
    }

    /**
     * Note: addpopupAction is intentionally omitted. 
     * This is a mapping table managed inside the parent Geography Group UI, 
     * so it doesn't require "on-the-fly" addition during other processes.
     */

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    /**
     * Delete Action (AJAX POST Processor)
     */
    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                $this->_model->deleteRecord($id);
                $menuId = defined('GEOGRAPHY_GROUP_MENU_ID') ? GEOGRAPHY_GROUP_MENU_ID : 103;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);
                $this->_helper->json(array('msgtype' => 'success', 'message' => 'Mapping deleted successfully.'));
            } catch (\Throwable $e) {
                $this->_helper->json(array('msgtype' => 'error', 'message' => $e->getMessage()));
            }
        } else {
            $this->_helper->json(array('msgtype' => 'error', 'message' => 'Invalid Request.'));
        }
    }

    /**
     * Make Active/Inactive Action (AJAX POST Processor)
     */
    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');
            $this->_model->toggleStatus($id, $status);

            $menuId = defined('GEOGRAPHY_GROUP_MENU_ID') ? GEOGRAPHY_GROUP_MENU_ID : 103;
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
     * AJAX Endpoint: Get States by Country ID
     * 
     * @return void JSON array of states
     */
    public function getstatesAction()
    {
        $countryId = (int)$this->getRequest()->getParam('country_id');
        $states = array();

        if ($countryId) {
            $stateModel = new Default_Model_State();
            $states = $stateModel->getPairsByCountry($countryId, true);
        }

        $this->_helper->json($states);
    }

    /**
     * AJAX Endpoint: Get Cities by State ID
     * 
     * @return void JSON array of cities
     */
    public function getcitiesAction()
    {
        $stateId = (int)$this->getRequest()->getParam('state_id');
        $cities = array();

        if ($stateId) {
            $cityModel = new Default_Model_City();
            $cities = $cityModel->getPairsByState($stateId, true);
        }

        $this->_helper->json($cities);
    }

    /**
     * AJAX Endpoint: Get Postal Codes by City ID
     * 
     * @return void JSON array of postal codes
     */
    public function getpostalcodesAction()
    {
        $cityId = (int)$this->getRequest()->getParam('city_id');
        $postalCodes = array();

        if ($cityId) {
            $postalCodeModel = new Default_Model_Postalcode();
            $postalCodes = $postalCodeModel->getPairsByCity($cityId, true);
        }

        $this->_helper->json($postalCodes);
    }

    /**
     * Form Preparation Helper
     * 
     * Resolves the polymorphic hierarchy to populate the correct transient dropdowns
     * when editing an existing mapping record.
     * 
     * @param int|null $id       Optional ID for populating data.
     * @param bool     $readonly If true, disables all elements.
     * @return Default_Form_GeographygroupMap
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_GeographygroupMap();
        $countryId = null;
        $stateId   = null;
        $cityId    = null;

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                // Determine the parent hierarchy based on the Entity Type saved in the DB
                if ($data['entity_type'] == 'State') {
                    $stateModel = new Default_Model_State();
                    $stateRec = $stateModel->getRecordById($data['entity_id']);
                    $countryId = $stateRec['country_id'];
                    $data['country_id'] = $countryId;
                    $data['state_id'] = $data['entity_id'];
                } elseif ($data['entity_type'] == 'City') {
                    $cityModel = new Default_Model_City();
                    $cityRec = $cityModel->getRecordById($data['entity_id']);
                    $stateModel = new Default_Model_State();
                    $stateRec = $stateModel->getRecordById($cityRec['state_id']);

                    $countryId = $stateRec['country_id'];
                    $stateId   = $cityRec['state_id'];
                    $data['country_id'] = $countryId;
                    $data['state_id']   = $stateId;
                    $data['city_id']    = $data['entity_id'];
                } elseif ($data['entity_type'] == 'PostalCode') {
                    $postalModel = new Default_Model_Postalcode();
                    $postalRec = $postalModel->getRecordById($data['entity_id']);
                    $cityModel = new Default_Model_City();
                    $cityRec = $cityModel->getRecordById($postalRec['city_id']);
                    $stateModel = new Default_Model_State();
                    $stateRec = $stateModel->getRecordById($cityRec['state_id']);

                    $countryId = $stateRec['country_id'];
                    $stateId   = $cityRec['state_id'];
                    $cityId    = $postalRec['city_id'];

                    $data['country_id'] = $countryId;
                    $data['state_id']   = $stateId;
                    $data['city_id']    = $cityId;
                    $data['postalcode_id'] = $data['entity_id'];
                } elseif ($data['entity_type'] == 'Country') {
                    $data['country_id'] = $data['entity_id'];
                }

                // Form populated with resolved hierarchy data
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
            }
        }

        // Run the populator after data binding to ensure correct options are loaded
        $this->_populateDropdowns($form, $countryId, $stateId, $cityId);

        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }

    /**
     * Populates the cascading dropdowns safely based on context.
     * 
     * @param Zend_Form $form
     * @param int|null  $countryId
     * @param int|null  $stateId
     * @param int|null  $cityId
     * @return void
     */
    private function _populateDropdowns(Zend_Form $form, $countryId = null, $stateId = null, $cityId = null)
    {
        
        // NEW: Populate Geography Groups
        $groupModel = new Default_Model_Geographygroup();
        $groups = $groupModel->getPairs(true);
        $form->geographygroup_id->addMultiOption('', 'Select Target Group...');
        $form->geographygroup_id->addMultiOptions($groups);

        // Populate Countries
        $countryModel = new Default_Model_Country();
        $countries = $countryModel->getPairs(true);
        $form->country_id->addMultiOption('', 'Select Country...');
        $form->country_id->addMultiOptions($countries);

        // Populate States
        if ($countryId) {
            $stateModel = new Default_Model_State();
            $states = $stateModel->getPairsByCountry($countryId, true);
            $form->state_id->addMultiOption('', 'Select State/Province...');
            $form->state_id->addMultiOptions($states);
        } else {
            $form->state_id->addMultiOption('', 'Select Country First');
        }

        // Populate Cities
        if ($stateId) {
            $cityModel = new Default_Model_City();
            $cities = $cityModel->getPairsByState($stateId, true);
            $form->city_id->addMultiOption('', 'Select City...');
            $form->city_id->addMultiOptions($cities);
        } else {
            $form->city_id->addMultiOption('', 'Select State First');
        }

        // Populate Postal Codes
        if ($cityId) {
            $postalModel = new Default_Model_Postalcode();
            $postalCodes = $postalModel->getPairsByCity($cityId, true);
            $form->postalcode_id->addMultiOption('', 'Select Postal/ZIP Code...');
            $form->postalcode_id->addMultiOptions($postalCodes);
        } else {
            $form->postalcode_id->addMultiOption('', 'Select City First');
        }
    }
}
