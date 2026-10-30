<?php

/**
 * Module Registry Controller
 * 
 * Manages the read-only view of application modules discovered by the Scanner Engine.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_ModuleController extends Zend_Controller_Action
{
    // SSOT Standard Properties
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
        $this->_model   = new Default_Model_Module();

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
                echo Zend_Json::encode(array('login' => 'failed'));
                die();
            } else {
                $this->_redirect('');
            }
        }
        
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('scan', 'json')->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

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
            $searchData    = $request->getParam('searchData');

            $sort    = $request->getParam('sort', 'DESC');
            $by      = $request->getParam('by', 'id');
            $perPage = ($dashboardcall == 'Yes') 
                       ? $request->getParam('per_page', DASHBOARD_PERPAGE) 
                       : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'DESC'; $by = 'id'; $page = 1; $searchData = '';
            }

            // Fetch Grid Data (SSOT Two-Method Pattern)
            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);
            
            // Override default actions (Remove Add/Edit/Delete, keep View only)
            $dataTmp['tableheader']['action'] = 'View Details'; 

            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();

        } catch (Exception $e) {
            if (APPLICATION_ENV == 'development') {
                Zend_Debug::dump($e->getMessage());
            }
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $data = $this->_model->getRecordById($id);

        if (!$data) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->data = $data;
            
            // Fetch associated controllers for this module
            $controllerModel = new Default_Model_Controller();
            $this->view->controllers = $controllerModel->getRecordsByModuleId($id, false);
        }

        $this->view->controllername = 'module';
        $this->view->id = $id;
    }

    /* =================================================================================
     * GROUP 5: AJAX & SYSTEM ACTIONS
     * ================================================================================= */

    /**
     * Triggers the System Scanner to discover new Modules, Controllers, and Actions.
     */
    public function scanAction()
    {
        try {
            require_once APPLICATION_PATH . '/../library/Sentrifugo/System/Scanner.php';
            $scanner = new Sentrifugo_System_Scanner();
            $results = $scanner->scan();
            
            $msg = "Scan Complete. Discovered {$results['new']} new actions, updated {$results['updated']} files.";
            if ($results['obsolete'] > 0) {
                $msg .= " Marked {$results['obsolete']} actions as obsolete.";
            }

            // Log the system scan
            // sapp_Global::logManager(MODULE_REGISTRY_MENU_ID, 2, $this->_loginUserId, 0);

            $this->_helper->json(array('status' => 'success', 'message' => $msg));

        } catch (Exception $e) {
            $this->_helper->json(array('status' => 'error', 'message' => 'Scanner failed: ' . $e->getMessage()));
        }
    }
}