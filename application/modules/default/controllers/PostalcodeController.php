<?php

/**
 * Postal Code Controller
 * 
 * Manages the CRUD, Grid, and Popup logic for Postal/ZIP Codes.
 * Strictly adheres to SSOT v6.1: PHP 7.4/8.0+ safe, transactional, 
 * composite validation, and cascading dropdown handlers.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_PostalcodeController extends Zend_Controller_Action
{
    /** @var int Logged in user ID */
    protected $_loginUserId;

    /** @var int Logged in user Role ID */
    protected $_loginuserRole;

    /** @var int Logged in user Group ID */
    protected $_loginuserGroup;

    /** @var Default_Model_Postalcode Instance of the Postal Code Model */
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
        $this->_model   = new Default_Model_Postalcode();

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
                die(Zend_Json::encode(['login' => 'failed']));
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
            $searchData    = (string)$request->getParam('searchData', '');

            // SSOT V: Default sort is hierarchical (Country -> State -> City -> Code)
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
            error_log("Postal Code Index Error: " . $e->getMessage());
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
        $this->view->controllername = 'postalcode';
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
        $form = new Default_Form_Postalcode();
        $request = $this->getRequest();

        // Repopulate for validator
        $this->_populateDropdowns($form, (int)$request->getParam('country_id'), (int)$request->getParam('state_id'));

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id     = (int)$request->getParam('id');
                $cityId = (int)$request->getParam('city_id');
                $code   = trim($request->getParam('code'));

                // SSOT IV: Custom Composite Uniqueness Check
                if ($this->_model->checkDuplicate($cityId, $code, $id)) {
                    $this->_helper->json(['code' => ['duplicate' => 'This Postal Code already exists within the selected City.']]);
                    return;
                }

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $data = [
                        'city_id'     => $cityId,
                        'code'        => $code,
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive')
                    ];

                    $actionFlag = ($id > 0) ? 2 : 1;
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    // Note: No default Menu ID exists for Postal Codes, using a placeholder
                    $menuId = defined('POSTALCODE') ? POSTALCODE : 379;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(['success' => "Postal Code $msg successfully."]);

                    $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes']);
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("Postal Code Save Error: " . $e->getMessage());
                    $this->_helper->json(['result' => 'exception', 'message' => 'System error occurred.']);
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

        $form = new Default_Form_Postalcode();
        $request = $this->getRequest();

        $this->_populateDropdowns($form, (int)$request->getParam('country_id'), (int)$request->getParam('state_id'));
        $form->setAction(BASE_URL . 'postalcode/addpopup');
        $form->getElement('submit')->setAttrib('onclick', null);

        $msgarray = [];
        $this->view->popup = '';

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $cityId = (int)$request->getParam('city_id');
                $code   = trim($request->getParam('code'));

                if ($this->_model->checkDuplicate($cityId, $code)) {
                    $msgarray['code'] = 'This Postal Code already exists for the selected City.';
                } else {
                    $db = Zend_Db_Table::getDefaultAdapter();
                    $db->beginTransaction();

                    try {
                        $data = [
                            'city_id'     => $cityId,
                            'code'        => $code,
                            'description' => trim($request->getParam('description')),
                            'isactive'    => (int)$request->getParam('isactive', 1)
                        ];

                        $newId = $this->_model->saveRecord($data);

                        $menuId = defined('POSTALCODE') ? POSTALCODE : 998;
                        sapp_Global::logManager($menuId, 1, $this->_loginUserId, $newId);

                        $db->commit();

                        $activeRecords = $this->_model->getPairsByCity(true, $cityId);
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
                }
            } else {
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
            }
        }

        $this->view->controllername = 'postalcode';
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
                $menuId = defined('POSTALCODE') ? POSTALCODE : 998;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);
                $this->_helper->json(['msgtype' => 'success', 'message' => 'Postal Code deleted successfully.']);
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

            $menuId = defined('POSTALCODE') ? POSTALCODE : 998;
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
     * AJAX Endpoint: Get States by Country ID
     * 
     * @return void JSON array of states
     */
    public function getstatesAction()
    {
        $countryId = (int)$this->getRequest()->getParam('country_id');
        $states = [];

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
        $cities = [];

        if ($stateId) {
            $cityModel = new Default_Model_City();
            $cities = $cityModel->getPairsByState($stateId, true);
        }

        $this->_helper->json($cities);
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Postalcode();
        $countryId = null;
        $stateId = null;

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                // We need the IDs for the dropdown population logic
                $countryId = (int)$data['country_id'];
                $stateId = (int)$data['state_id'];

                // Populate the form with the full dataset
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
            }
        }

        // Run the populator after data binding to ensure correct options are loaded
        $this->_populateDropdowns($form, $countryId, $stateId);

        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }

    private function _populateDropdowns(Zend_Form $form, $countryId = null, $stateId = null)
    {
        // Populate Countries
        $countryModel = new Default_Model_Country();
        $countries = $countryModel->getPairs(true);
        $form->country_id->addMultiOption('', 'Select Country...');
        $form->country_id->addMultiOptions($countries);

        // Populate States (if a country is selected)
        if ($countryId) {
            $stateModel = new Default_Model_State();
            $states = $stateModel->getPairsByCountry($countryId, true);
            $form->state_id->addMultiOption('', 'Select State/Province...');
            $form->state_id->addMultiOptions($states);
        } else {
            $form->state_id->addMultiOption('', 'Select Country First');
        }

        // Populate Cities (if a state is selected)
        if ($stateId) {
            $cityModel = new Default_Model_City();
            $cities = $cityModel->getPairsByState($stateId, true);
            $form->city_id->addMultiOption('', 'Select City...');
            $form->city_id->addMultiOptions($cities);
        } else {
            $form->city_id->addMultiOption('', 'Select State First');
        }
    }
}
