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

class Default_EmpbenefitsController extends Zend_Controller_Action
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
		$Uid = $id ?: $userID;
		if ($Uid != "") {
			$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
		}
		$empbenefitsModel = new Default_Model_Empbenefits();
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
			$data = [];
			$searchQuery = '';
			$searchArray = [];
			$tablecontent = '';
			if ($refresh == 'refresh') {
				if ($dashboardcall == 'Yes')
					$perPage = DASHBOARD_PERPAGE;
				else
					$perPage = PERPAGE;

				$sort = 'DESC';
				$by = 'ep.modifieddate';
				$pageNo = 1;
				$searchData = '';
				$searchQuery = '';
				$searchArray = [];
			} else {
				$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
				$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ep.modifieddate';
				if ($dashboardcall == 'Yes')
					$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
				else
					$perPage = $this->_getParam('per_page', PERPAGE);

				$pageNo = $this->_getParam('page', 1);
				$searchData = $this->_getParam('searchData');
				$searchData = rtrim($searchData, ',');
			}
			$dataTmp = $empbenefitsModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'edit');

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
		try {



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
			$Uid = $id ?: $userID;
			if ($Uid != "") {
				$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
			}
			$empbenefitsModel = new Default_Model_Empbenefits();
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
				$data = [];
				$searchQuery = '';
				$searchArray = [];
				$tablecontent = '';
				if ($refresh == 'refresh') {
					if ($dashboardcall == 'Yes')
						$perPage = DASHBOARD_PERPAGE;
					else
						$perPage = PERPAGE;

					$sort = 'DESC';
					$by = 'ep.modifieddate';
					$pageNo = 1;
					$searchData = '';
					$searchQuery = '';
					$searchArray = [];
				} else {
					$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
					$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ep.modifieddate';
					if ($dashboardcall == 'Yes')
						$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
					else
						$perPage = $this->_getParam('per_page', PERPAGE);

					$pageNo = $this->_getParam('page', 1);
					$searchData = $this->_getParam('searchData');
					$searchData = rtrim($searchData, ',');
				}
				$dataTmp = $empbenefitsModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'edit');

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

			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
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
		$Uid = $id ?: $userID;
		if ($Uid != "") {
			$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
		}
		$empbenefitsModel = new Default_Model_Empbenefits();
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
			$data = [];
			$searchQuery = '';
			$searchArray = [];
			$tablecontent = '';
			if ($refresh == 'refresh') {
				if ($dashboardcall == 'Yes')
					$perPage = DASHBOARD_PERPAGE;
				else
					$perPage = PERPAGE;

				$sort = 'DESC';
				$by = 'ep.modifieddate';
				$pageNo = 1;
				$searchData = '';
				$searchQuery = '';
				$searchArray = [];
			} else {
				$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
				$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ep.modifieddate';
				if ($dashboardcall == 'Yes')
					$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
				else
					$perPage = $this->_getParam('per_page', PERPAGE);

				$pageNo = $this->_getParam('page', 1);
				$searchData = $this->_getParam('searchData');
				$searchData = rtrim($searchData, ',');
			}
			$dataTmp = $empbenefitsModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);
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
	public function editempperkAction()
	{

		if (isset($_POST['data'])) {
			$empbenefitsModel = new Default_Model_Empbenefits();
			if ($_POST['data'] == 'delete') {
				$empbenefitsModel->deleteEmpPerk($_POST['user_id']);
				die("Employee benefit successfully removed!");
			} else {
				$temp_data = explode('&', $_POST['data']);
				$data = [];
				foreach ($temp_data as $temp) {
					$temp = explode('=', $temp);
					if ($temp[0] != 'amount') {
						$data[$temp[0]] = $temp[1];
					} else {
						$data[$temp[0]] = doubleval($temp[1]);
					}
				}
				$empbenefitsModel->AddEmpPerkData($data);
				die("Employee benefit successfully added!");
			}
		} else {
			die('error');
		}
	}



	public function perktypeAction()
	{
		$empbenefitsModel = new Default_Model_Empbenefits();
		$id = (int)$_POST['data'];
		$perkType = $empbenefitsModel->getPerkType($id);
		die(json_encode($perkType));
	}
	public function save($empbenefitsForm, $userid, $where)
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if ($empbenefitsForm->isValid($this->getRequest()->getPost())) {

			$id = $this->getRequest()->getParam('id');
			$empbenefitsModel = new Default_Model_Empbenefits();
			$data['amount'] = $this->getRequest()->getParam('amount');
			$data['modifiedby'] = $loginUserId;
			$data['modifieddate'] = gmdate("Y-m-d H:i:s");
			$data['isactive'] = utlis::getcheckboxvalue($this->getRequest()->getParam('isactive'));

			$actionflag = '';
			if ($where != '') {
				$actionflag = 2;
			} else {
				$data['perk_id'] = $this->getRequest()->getParam('perk');
				$data['emp_id'] = $userid;
				$data['approved'] = 'N';

				$actionflag = 1;
			}
			$Id = $empbenefitsModel->SaveorUpdatePerkData($data, $where);

			if ($Id == 'update') {
				// $tableid = $id;
				$this->view->successmessage = 'Employee Benfits Assign updated successfully.';
			} else {
				$_SESSION["location"] = "";
				$tableid = $Id;
				$this->view->successmessage = 'Employee Benfit Assign  added successfully.';
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $userid);
			$this->view->controllername = 'empbenefits';
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


	public function addpopupAction()
	{

		try {
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}


			$emptyFlag = 0;

			$userid = $this->getRequest()->getParam('unitId');
			$id = $this->getRequest()->getParam('id');

			if ($userid == '')
				$userid = $loginUserId;



			$msgarray = [];

			$employeeModal = new Default_Model_Employee();
			$admodel = new Default_Model_Addundum();
			$usrmodel = new Default_Model_Users();
			$empbenefitsForm = new Default_Form_empbenefits();
			$isrowexist = $employeeModal->getsingleEmployeeData($userid);

			$incurrdata = $admodel->benfincurr($userid);
			if ($isrowexist == 'norows')
				$this->view->rowexist = "norows";
			else
				$this->view->rowexist = "rows";


			$empbenefitsForm->id->setValue($userid);

			$empdata = $employeeModal->getActiveEmployeeData($userid);
			if (!empty($empdata)) {

				$formModel = new Default_Model_Perk();
				$perks = $formModel->getIndividualPerk();
				$empbenefitsForm->perk->addMultiOption(' ', 'Select...');
				foreach ($perks as $perk) {
					$empbenefitsForm->perk->addMultiOption($perk['perk_id'], $perk['name']);
				}

				$empPerk = $employeeModal->getEmpPerk($userid);


				if ($this->getRequest()->getPost()) {
					$this->save($empbenefitsForm, $userid, '');
					$msgarray = $this->view->msgarray;
				}
				$this->view->perks = $perks;
				$this->view->empperks = $empPerk;
				$this->view->form = $empbenefitsForm;
			}
			$this->view->empdata = $empdata;

			$this->view->msgarray = $msgarray;
			$this->view->incurrdata = $incurrdata;
			$this->view->msgarray = $msgarray;
			$controllername = 'empbenefits';
			$this->view->controllername = $controllername;

			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function editpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		try {
			//code...

			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}

			$empbenefitsForm = new Default_Form_empbenefits();

			$emptyFlag = 0;
			$empbenefitsForm->perk->setAttrib('readonly', true);
			$empbenefitsForm->perk->setRequired(false);
			$userid = $this->getRequest()->getParam('unitId');
			$id = $this->getRequest()->getParam('id');

			if ($userid == '')
				$userid = $loginUserId;



			$msgarray = [];

			$employeeModal = new Default_Model_Employee();
			$admodel = new Default_Model_Addundum();
			$usrmodel = new Default_Model_Users();

			$isrowexist = $employeeModal->getsingleEmployeeData($userid);
			$edata = $usrmodel->getEmpFromUser($userid);

			$incurrdata = $admodel->benfincurr($userid);

			if ($isrowexist == 'norows')
				$this->view->rowexist = "norows";
			else
				$this->view->rowexist = "rows";



			$empdata = $employeeModal->getActiveEmployeeData($userid);
			if (!empty($empdata)) {
				$formModelperk = new Default_Model_Perk();
				$allperks = $formModelperk->getIndividualPerk();

				$formModel = new Default_Model_Perkapprove();

				$perks = $formModel->employeeperkdetail($id);
				$empbenefitsForm->perk->addMultiOption($perks[0]['perk_id'], $perks[0]['name']);
				$empbenefitsForm->perk->setValue($perks[0]['perk_id'], $perks[0]['name']);
				$empbenefitsForm->amount->setValue($perks[0]['amount']);
				$empbenefitsForm->isactive->setValue(utlis::getcheckboxvalue($perks[0]['isactive'], 'form'));

				$empPerk = $employeeModal->getEmpPerk($userid);


				if ($this->getRequest()->isPost()) {
					$this->save($empbenefitsForm, $userid, $id);
					$msgarray = $this->view->msgarray;
				}
				$this->view->perks = $perks;
				$this->view->empperks = $empPerk;
				$this->view->form = $empbenefitsForm;
			}
			$this->view->empdata = $empdata;

			$this->view->msgarray = $msgarray;

			$this->view->msgarray = $msgarray;
			$controllername = 'empbenefits';
			$this->view->incurrdata = $incurrdata;
			$this->view->controllername = $controllername;
		} catch (\Throwable $th) {
			//throw $th;
			Zend_Debug::dump($th->getMessage());
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

		$empbenefitsform = new Default_Form_empbenefits();
		$empbenefitsmodel = new Default_Model_Perkapprove();

		$data = $empbenefitsmodel->employeeperkdetail($id);

		// die(json_encode($data));
		$this->view->data = $data;
		$this->view->form = $empbenefitsform;
	}
}
