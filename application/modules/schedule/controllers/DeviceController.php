<?php
class Schedule_DeviceController extends Zend_Controller_Action
{
	public function init()
	{
		//$this->options = $this->getInvokeArg('bootstrap')->getOption();
		require_once 'application/modules/default/library/utilClasses/Util.php';
	}

	public function indexAction()
	{

		try {

			$deviceModel = new Schedule_Model_Device();
			$call = $this->_getParam('call');

			if ($call == 'ajaxcall') {
				$this->_helper->layout->disableLayout();
			}

			$view = Zend_Layout::getMvcInstance()->getView();
			$objname = $this->_getParam('objname');
			$refresh = $this->_getParam('refresh');
			$dashboardcall = $this->_getParam('dashboardcall');

			$data = array();
			$searchQuery = '';
			$searchArray = array();
			$tablecontent = '';

			if ($refresh == 'refresh') {
				if ($dashboardcall == 'Yes')
					$perPage = DASHBOARD_PERPAGE;
				else
					$perPage = PERPAGE;
				$sort = 'DESC';
				$by = 'modifiedby';
				$pageNo = 1;
				$searchData = '';
				$searchQuery = '';
				$searchArray = '';
			} else {
				$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
				$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'modifiedby';
				if ($dashboardcall == 'Yes')
					$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
				else
					$perPage = $this->_getParam('per_page', PERPAGE);

				$pageNo = $this->_getParam('page', 1);

				$searchData = $this->_getParam('searchData');
				$searchData = rtrim($searchData, ',');
			}
			$by = "device_id";
			$dataTmp = $deviceModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);

			array_push($data, $dataTmp);
			$this->view->dataArray = $data;
			$this->view->call = $call;

			$this->view->messages = $this->_helper->flashMessenger->getMessages();
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function editAction()
	{

		$deviceForm = new Schedule_Form_Device();
		$deviceModel = new Schedule_Model_Device();
		$auth = Zend_Auth::getInstance();
		$id = $this->_getParam('id');
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		if ($loginUserId) {
			$deviceModel = new Schedule_Model_Device();
			$deviceData = $deviceModel->getDeviceById($id);

			$deviceForm->populate($deviceData);
			$deviceForm->device_name->setValue($deviceData['device_model']);
			$deviceForm->device_name->setAttrib('readonly', 'readonly');
			
			if($this->getRequest()->isPost()){
				$where = 'id = '.$id;
				Zend_Debug::dump($deviceForm->isValid($this->getRequest()->getPost()));die;
				if($deviceForm->isValid($this->getRequest()->getPost())){
					$data = array(
						"device_model"=>$this->getRequest()->getParam('device_name'),
						"device_ip"=>$this->getRequest()->getParam('device_ip'),
						"device_port"=>$this->getRequest()->getParam('device_port'),
						"connection_mode"=>$this->getRequest()->getParam('connection_mode'),
					);
					Zend_Debug::dump($data);die;
					$deviceModel->saveDevice($data, $where);
					$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Device Updated Successfully."));
					$this->_redirect('schedule/device');
				}
			}
			else{
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("error" => "Cannot Update Device. Please try again."));
			}
		}
		$this->view->form = $deviceForm;
	}

	public function addconnectionAction()
	{
		//Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		$deviceForm = new Schedule_Form_AddConnection();
		$auth = Zend_Auth::getInstance();
		$id = $this->_getParam('id');
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		} else {
			Zend_Debug::dump("Not logged in");
		}
		$this->view->form = $deviceForm;
	}

	public function scandeviceAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();

		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$categorypermission = '';
		$categorypermission = sapp_Global::_checkprivileges(DEVICE, $loginuserGroup, $loginuserRole, 'add');
		$this->view->categorypermission = $categorypermission;
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginuserid = $auth->getStorage()->read()->id;
		}
		$this->view->pop = 'close';
		$this->view->controllername = 'device';
		$this->view->eventact = "added";

		//$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Overtime Added Successfully."));
		//$this->_redirect('schedule/overtime');
	}

	public function deleteAction()
	{
		$deviceModel = new Schedule_Model_Device();
		$objID = $this->_getParam('objid');

		$call = $this->_getParam('call');
		$dashboardcall = $this->_getParam('dashboardcall');
		$deviceModel->deleteDevice($objID);
		$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Device Deleted Successfully."));
		if ($dashboardcall == 'Yes')
			$this->_redirect('schedule/device/dashboardcall');
		else
			$this->_redirect('schedule/device');
	}
}
