<?php

use function PHPSTORM_META\type;

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_EmpdeductiveController extends Zend_Controller_Action
{

	private $options;
	public function preDispatch()
	{
	}

	public function init()
	{
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';
	}

	public function indexAction()
	{


		$userID = "";
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
			$loginUserRole = $auth->getStorage()->read()->emprole;
		}
		$conText = "";
		$id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
		$call = $this->_getParam('call');
		$usersModel = new Default_Model_Users();
		if ($call == 'ajaxcall') {
			$this->_helper->layout->disableLayout();
			$id = ($this->getRequest()->getParam('unitId') != '') ? $this->getRequest()->getParam('unitId') : $this->getRequest()->getParam('unitId');
			$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
			$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
		}
		if ($id == '')		$id = $userID;
		$Uid = ($id) ? $id : $userID;
		if ($Uid != "") {
			$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
		}
		$empdeductiveModel = new Default_Model_Empdeductive();
		$employeeModal = new Default_Model_Employee();
		$isrowexist = $employeeModal->getsingleEmployeeData($id);

		if ($isrowexist == 'norows')
			$this->view->rowexist = "norows";
		else
			$this->view->rowexist = "rows";
		$empdata = $employeeModal->getActiveEmployeeData($id);

		$this->view->empdata = $empdata;

		try {
			$view = Zend_Layout::getMvcInstance()->getView();
			$objname = $this->_getParam('objname');
			$refresh = $this->_getParam('refresh');
			$dashboardcall = $this->_getParam('dashboardcall', null);
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
				$by = 'el.modifieddate';
				$pageNo = 1;
				$searchData = '';
				$searchQuery = '';
				$searchArray = array();
			} else {
				$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
				$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'el.modifieddate';
				if ($dashboardcall == 'Yes')
					$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
				else
					$perPage = $this->_getParam('per_page', PERPAGE);

				$pageNo = $this->_getParam('page', 1);
				$searchData = $this->_getParam('searchData');
				$searchData = rtrim($searchData, ',');
			}
			$dataTmp = $empdeductiveModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'edit');

			array_push($data, $dataTmp);
			$permission = sapp_Global::_checkprivileges(EMPLOYEE, $loginuserGroup, $loginUserRole, 'edit');
			$this->view->dataArray = $data;
			$this->view->call = $call;
			$this->view->id = $id;

			$this->view->messages = $this->_helper->flashMessenger->getMessages();

			if (!empty($employeeData))
				$this->view->employeedata = $employeeData[0];
			$this->view->usergroup = $loginuserGroup;
			$this->view->permission = $permission;
		} catch (Exception $e) {

			$this->view->rowexist = "norows";
		}
	}

	public function editAction()
	{

		$userID = "";
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
			$loginUserRole = $auth->getStorage()->read()->emprole;
		}
		$conText = "";
		$id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
		$call = $this->_getParam('call');
		$usersModel = new Default_Model_Users();
		if ($call == 'ajaxcall') {
			$this->_helper->layout->disableLayout();
			$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
			$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
		}
		if ($id == '')		$id = $userID;
		$Uid = ($id) ? $id : $userID;
		if ($Uid != "") {
			$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
		}
		$empdeductiveModel = new Default_Model_Empdeductive();
		$employeeModal = new Default_Model_Employee();
		$isrowexist = $employeeModal->getsingleEmployeeData($id);

		if ($isrowexist == 'norows')
			$this->view->rowexist = "norows";
		else
			$this->view->rowexist = "rows";
		$empdata = $employeeModal->getActiveEmployeeData($id);

		$this->view->empdata = $empdata;

		try {
			$view = Zend_Layout::getMvcInstance()->getView();
			$objname = $this->_getParam('objname');
			$refresh = $this->_getParam('refresh');
			$dashboardcall = $this->_getParam('dashboardcall', null);
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
				$by = 'el.modifieddate';
				$pageNo = 1;
				$searchData = '';
				$searchQuery = '';
				$searchArray = array();
			} else {
				$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
				$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'el.modifieddate';
				if ($dashboardcall == 'Yes')
					$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
				else
					$perPage = $this->_getParam('per_page', PERPAGE);

				$pageNo = $this->_getParam('page', 1);
				$searchData = $this->_getParam('searchData');
				$searchData = rtrim($searchData, ',');
			}
			$dataTmp = $empdeductiveModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'edit');

			array_push($data, $dataTmp);
			$permission = sapp_Global::_checkprivileges(EMPLOYEE, $loginuserGroup, $loginUserRole, 'edit');
			$this->view->dataArray = $data;
			$this->view->call = $call;
			$this->view->id = $id;

			$this->view->messages = $this->_helper->flashMessenger->getMessages();

			if (!empty($employeeData))
				$this->view->employeedata = $employeeData[0];
			$this->view->usergroup = $loginuserGroup;
			$this->view->permission = $permission;
		} catch (Exception $e) {

			$this->view->rowexist = "norows";
		}
	}
	public function viewAction()
	{


		$userID = "";
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
			$loginUserRole = $auth->getStorage()->read()->emprole;
		}
		$conText = "view";
		$id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
		$call = $this->_getParam('call');
		$usersModel = new Default_Model_Users();
		if ($call == 'ajaxcall') {
			$this->_helper->layout->disableLayout();
			$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
			$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
		}
		if ($id == '')		$id = $userID;
		$Uid = ($id) ? $id : $userID;
		if ($Uid != "") {
			$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
		}
		$empdeductiveModel = new Default_Model_Empdeductive();
		$employeeModal = new Default_Model_Employee();
		$isrowexist = $employeeModal->getsingleEmployeeData($id);

		if ($isrowexist == 'norows')
			$this->view->rowexist = "norows";
		else
			$this->view->rowexist = "rows";
		$empdata = $employeeModal->getActiveEmployeeData($id);

		$this->view->empdata = $empdata;

		try {
			$objname = $this->_getParam('objname');
			$refresh = $this->_getParam('refresh');
			$dashboardcall = $this->_getParam('dashboardcall', null);
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
				$by = 'e.modifieddate';
				$pageNo = 1;
				$searchData = '';
				$searchQuery = '';
				$searchArray = array();
			} else {
				$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
				$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'e.modifieddate';
				if ($dashboardcall == 'Yes')
					$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
				else
					$perPage = $this->_getParam('per_page', PERPAGE);

				$pageNo = $this->_getParam('page', 1);
				$searchData = $this->_getParam('searchData');
				$searchData = rtrim($searchData, ',');
			}
			$dataTmp = $empdeductiveModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);

			array_push($data, $dataTmp);
			$permission = sapp_Global::_checkprivileges(EMPLOYEE, $loginuserGroup, $loginUserRole, 'edit');
			$this->view->dataArray = $data;
			$this->view->call = $call;
			$this->view->id = $id;

			$this->view->messages = $this->_helper->flashMessenger->getMessages();

			if (!empty($employeeData))
				$this->view->employeedata = $employeeData[0];
			$this->view->usergroup = $loginuserGroup;
			$this->view->permission = $permission;
		} catch (Exception $e) {

			$this->view->rowexist = "norows";
		}
	}

	public function editpopupAction()
	{
		//code...

		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		try {

			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}

			$empdeductiveForm = new Default_Form_empdeductive();
			$empdeductiveForm->deductive->setAttrib('readonly', true);
			$empdeductiveForm->deductive->setRequired(false);
			$emptyFlag = 0;
			$usrmodel = new Default_Model_Users();
			$userid = $this->getRequest()->getParam('unitId');
			$id = $this->getRequest()->getParam('id');

			if ($userid == '')
				$userid = $loginUserId;



			$msgarray = [];

			$employeeModal = new Default_Model_Employee();
			$isrowexist = $employeeModal->getsingleEmployeeData($userid);
			$usrdata = $usrmodel->getEmpFromUser($userid);

			if ($isrowexist == 'norows')
				$this->view->rowexist = "norows";
			else
				$this->view->rowexist = "rows";



			$empdata = $employeeModal->getActiveEmployeeData($userid);
			if (!empty($empdata)) {
				$formModeldeductive = new Default_Model_Deductive();

				$alldeductives = $formModeldeductive->getIndividualLoad();

				$admodel = new Default_Model_Addundum();
				$loaddata = $admodel->dedincurr($userid);

				$formModel = new Default_Model_Loadapprove();
				$loads = $formModel->employeeloaddetail($id);
				$empdeductiveForm->deductive->addMultiOption($loads[0]['load_id'], $loads[0]['name']);

				$empdeductiveForm->deductive->setValue($loads[0]['load_id'], $loads[0]['name']);
				$empdeductiveForm->amount->setValue($loads[0]['amount']);
				$empdeductiveForm->isactive->setValue(utlis::getcheckboxvalue($loads[0]['isactive'], 'form'));


				if ($this->getRequest()->isPost()) {

					$this->save($empdeductiveForm, $userid, $id);
					$msgarray = $this->view->msgarray;
				}
				$this->view->perks = $loads;
				$this->view->form = $empdeductiveForm;
			}
			$this->view->empdata = $empdata;

			$this->view->msgarray = $msgarray;

			$this->view->msgarray = $msgarray;
			$controllername = 'empdeductive';
			$this->view->controllername = $controllername;
			$this->view->loaddata = $loaddata;
		} catch (Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function save($empbenefitsForm, $userid, $where)
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if ($empbenefitsForm->isValid($this->getRequest()->getPost())) {

			$id = $this->getRequest()->getParam('id');
			$empdeductiveModel = new Default_Model_Empdeductive();

			$data['amount'] = $this->getRequest()->getParam('amount');
			$data['modifiedby'] = $loginUserId;
			$data['modifieddate'] = gmdate("Y-m-d H:i:s");
			$data['isactive'] = utlis::getcheckboxvalue($this->getRequest()->getParam('isactive'));

			$actionflag = '';
			if ($where != '') {
				$actionflag = 2;
				$where = 'employeeload_id=' . $where;
			} else {
				$data['load_id'] = $this->getRequest()->getParam('deductive');
				$data['emp_id'] = $userid;
				$data['approved'] = 'N';

				$actionflag = 1;
			}

			$Id = $empdeductiveModel->SaveorUpdateLoadkData($data, $where);

			if ($Id == 'update') {
				// $tableid = $id;
				$this->view->successmessage = 'Employee Deductive Assign updated successfully.';
			} else {
				$_SESSION["location"] = "";
				$tableid = $Id;
				$this->view->successmessage = 'Employee Deductive Assign  added successfully.';
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $userid);
			$this->view->controllername = 'empdeductive';
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		} else {
			$msgarray = [];
			$messages = $empbenefitsForm->getMessages();

			foreach ($messages as $key1 => $value) {
				foreach ($value as $key2 => $value2) {
					$msgarray[$key1] = $value2;
					break;
				}
			}
			$this->view->msgarray = $msgarray;
		}
	}
	public function editempdeductiveAction()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if (isset($_POST['data'])) {
			$empdeductiveModel = new Default_Model_Deductive();
			if ($_POST['data'] == 'delete') {
				$empdeductiveModel->deleteEmpDeductiive($_POST['user_id']);
				die("Employee load removed");
			} else {
				$temp_data = explode('&', $_POST['data']);
				$data = array();
				foreach ($temp_data as $temp) {
					$temp = explode('=', $temp);
					if ($temp[0] != 'amount') {
						$data[$temp[0]] = $temp[1];
					} else {
						$data[$temp[0]] = doubleval($temp[1]);
					}
				}
				$data['modifiedby'] = $loginUserId;
				$data['modifieddate'] =  gmdate("Y-m-d H:i:s");

				$empdeductiveModel->AddEmpDeductiveData($data);
				die("Employee load successfully added");
			}
		} else {
			die('error');
		}
	}


	public function deductivetypeAction()
	{
		$empdeductiveModel = new Default_Model_Deductive();
		$id = (int)$_POST['data'];
		$deductiveType = $empdeductiveModel->getDeductiveType($id);
		die(json_encode($deductiveType));
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

		$controllername = 'manageremployeeovertime';
	}

	public function addpopupAction()
	{
		try {
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
			$usrmodel = new Default_Model_Users();
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}

			$empdeductiveForm = new Default_Form_empdeductive();

			$emptyFlag = 0;

			$userid = $this->getRequest()->getParam('unitId');
			$id = $this->getRequest()->getParam('id');
			if ($userid == '')
				$userid = $loginUserId;



			$msgarray = [];

			$employeeModal = new Default_Model_Employee();
			$admodel = new Default_Model_Addundum();
			$isrowexist = $employeeModal->getsingleEmployeeData($userid);

			$loaddata = $admodel->dedincurr($userid);
			if ($isrowexist == 'norows')
				$this->view->rowexist = "norows";
			else
				$this->view->rowexist = "rows";


			$empdeductiveForm->id->setValue($userid);

			$empdata = $employeeModal->getActiveEmployeeData($userid);
			if (!empty($empdata)) {

				$formModel = new Default_Model_Deductive();
				$deductives = $formModel->getIndividualLoad($userid);
				$empdeductiveForm->deductive->addMultiOption(' ', 'Select...');
				foreach ($deductives as $deductive) {
					$empdeductiveForm->deductive->addMultiOption($deductive['load_id'], $deductive['name']);
				}

				// $empPerk = $employeeModal->getEmpPerk($userid);


				if ($this->getRequest()->getPost()) {
					$this->save($empdeductiveForm, $userid, '');
					$msgarray = $this->view->msgarray;
				}
				$this->view->perks = $deductives;
				// $this->view->empperks = $empPerk;
				$this->view->form = $empdeductiveForm;
			}
			$this->view->empdata = $empdata;
			$this->view->loaddata = $loaddata;
			$this->view->msgarray = $msgarray;

			$this->view->msgarray = $msgarray;
			$controllername = 'empbenefits';
			$this->view->controllername = $controllername;
		} catch (Exception $e) {
			Zend_Debug::dump($e->getFile());
		}
	}
	public function viewpopupAction()
	{

		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->getRequest()->getParam('id');

		if ($id == '')
			$id = $loginUserId;
		$callval = $this->getRequest()->getParam('call');
		if ($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();

		$empdeductiveform = new Default_Form_empdeductive();
		$emploadsmodel = new Default_Model_Loadapprove();

		$data = $emploadsmodel->employeeloaddetail($id);

		// die(json_encode($data));
		$this->view->data = $data;
		$this->view->form = $empdeductiveform;
	}
}
