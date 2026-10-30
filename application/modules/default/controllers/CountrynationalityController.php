<?php

/**
 * Country Nationality Controller
 * 
 * Manages the CRUD and Grid logic for mapping Countries to Nationalities.
 * Strictly adheres to SSOT v6.1: PHP 7.4/8.0+ safe, transactional, 
 * strictly formatted JSON responses, and decoupled form logic.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_CountrynationalityController extends Zend_Controller_Action
{
    /** @var int Logged in user ID */
    protected $_loginUserId;
    
    /** @var int Logged in user Role ID */
    protected $_loginuserRole;
    
    /** @var int Logged in user Group ID */
    protected $_loginuserGroup;
    
    /** @var Default_Model_CountryNationality Instance of the Mapping Model */
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
        $this->_model   = new Default_Model_CountryNationality();

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

            // SSOT V: Default sort is ASC by the primary joined column (Country Name)
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
            error_log("Country-Nationality Index Error: " . $e->getMessage());
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
        $this->view->controllername = 'countrynationality';
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
        $form = new Default_Form_CountryNationality();
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            
            // Re-populate dropdowns so form validation works 
            // (Zend_Form requires options to exist before validating them)
            $this->_populateDropdowns($form);

            if ($form->isValid($request->getPost())) {
                $id            = (int)$request->getParam('id');
                $countryId     = (int)$request->getParam('country_id');
                $nationalityId = (int)$request->getParam('nationality_id');
                
                // Composite Unique Check: Ensure this specific Country-to-Nationality mapping doesn't already exist
                $isDuplicate = $this->_model->checkDuplicateMapping($countryId, $nationalityId, $id);
                
                if ($isDuplicate) {
                    $this->_helper->json([
                        'result'  => 'error', 
                        'message' => ['nationality_id' => 'This exact Legal Status is already mapped to this Country.']
                    ]);
                    return;
                }

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V: Transactions Mandatory
                
                try {
                    $data = [
                        'country_id'     => $countryId,
                        'nationality_id' => $nationalityId,
                        'is_default'     => (int)$request->getParam('is_default'),
                        'description'    => trim($request->getParam('description')),
                        'isactive'       => (int)$request->getParam('isactive')
                    ];
                    
                    $actionFlag = ($id > 0) ? 2 : 1; // 2=Edit, 1=Add
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;
                    
                    // SSOT I: Dynamic Menu ID logging
                    // NOTE: Assign a new Menu ID for this mapping module in your database and replace 999
                    $menuId = defined('COUNTRY_NAT_MAP') ? COUNTRY_NAT_MAP : 378;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);
                    
                    $db->commit();
                    
                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(['success' => "Mapping $msg successfully."]);
                    
                    $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes']);
                    
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("CountryNationality Save Error: " . $e->getMessage());
                    $this->_helper->json(['result' => 'exception', 'message' => 'System error occurred.']);
                }
            } else {
                // SSOT V: Validation Error Extraction
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
                
                $menuId = defined('COUNTRY_NAT_MAP') ? COUNTRY_NAT_MAP : 378;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);
                
                $this->_helper->json(['msgtype' => 'success', 'message' => 'Mapping deleted successfully.']);
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

            $menuId = defined('COUNTRY_NAT_MAP') ? COUNTRY_NAT_MAP : 378;
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
     * Populates form dropdowns, loads data if editing, and locks fields if viewing.
     * 
     * @param int|null $id       Optional ID for populating data.
     * @param bool     $readonly If true, disables all elements.
     * @return Default_Form_CountryNationality
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_CountryNationality();
        
        // Populate the relational dropdowns
        $this->_populateDropdowns($form);

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
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

        // Populate Country (Geography)
        $countryModel = new Default_Model_Country();
        $countries = $countryModel->getPairs(true);
        $form->country_id->addMultiOption('', 'Select Country');
        $form->country_id->addMultiOptions($countries);        

        // Populate Nationality (Jurisprudence)
        $nationalityModel = new Default_Model_Nationality();
        $nationalities = $nationalityModel->getPairs(true);
        $form->nationality_id->addMultiOption('', 'Select Legal Status');
        $form->nationality_id->addMultiOptions($nationalities);

    }
}