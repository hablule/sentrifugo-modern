<?php

/**
 * Controller Registry Controller
 * 
 * Manages the read-only view of application controllers discovered by the Scanner.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_PermissioncontrollerController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Controller();

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

            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'controller');
            $perPage = ($dashboardcall == 'Yes') 
                       ? $request->getParam('per_page', DASHBOARD_PERPAGE) 
                       : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'ASC'; $by = 'controller'; $page = 1; $searchData = '';
            }

            // Fetch Grid Data
            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);
            
            // Override grid actions: Remove Add/Edit/Delete, keep View only
            $dataTmp['tableheader']['action'] = 'View Actions'; 

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
        
        // Custom Fetch with Module Name included
        $select = $this->_model->select()
            ->setIntegrityCheck(false)
            ->from(array('c' => 'main_controller'))
            ->joinLeft(array('m' => 'main_module'), 'c.module_id = m.id', array('module_name' => 'm.name'))
            ->where('c.id = ?', $id);
            
        $data = $this->_model->fetchRow($select);

        if (!$data) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->data = $data->toArray();
            
            // Fetch associated Actions for this controller
            $actionModel = new Default_Model_Action();
            $this->view->actions = $actionModel->getRecordsByControllerId($id, false);
        }

        $this->view->controllername = 'permissioncontroller';
        $this->view->id = $id;
    }
}