<?php

/**
 * Action Registry Controller
 * 
 * Manages the read-only view of controller actions (methods) discovered by the Scanner.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 */
class Default_PermissionactionController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    protected $_options;

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Action();

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
                echo Zend_Json::encode(['login' => 'failed']);
                die();
            } else {
                $this->_redirect('');
            }
        }
    }

    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') $this->_helper->layout->disableLayout();

            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);
            $searchData    = $request->getParam('searchData');

            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'name');
            $perPage = ($dashboardcall == 'Yes') ? $request->getParam('per_page', DASHBOARD_PERPAGE) : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'ASC'; $by = 'name'; $page = 1; $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);
            $dataTmp['tableheader']['action'] = 'View Details'; 

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (Exception $e) {
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        
        $select = $this->_model->select()
            ->setIntegrityCheck(false)
            ->from(['a' => 'main_action'])
            ->joinLeft(['c' => 'main_controller'], 'a.controller_id = c.id', ['controller_name' => 'c.controller', 'module_id' => 'c.module_id'])
            ->joinLeft(['m' => 'main_module'], 'c.module_id = m.id', ['module_name' => 'm.name'])
            ->where('a.id = ?', $id);
            
        $data = $this->_model->fetchRow($select);

        if (!$data) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->data = $data->toArray();
            
            // Fetch mapped permissions for this action
            $permModel = new Default_Model_ActionPermission();
            $this->view->permissions = $permModel->getRecordsByActionId($id, false);
        }

        $this->view->controllername = 'permissionaction';
        $this->view->id = $id;
    }
}