<?php

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

class Default_EmpleavesController extends Zend_Controller_Action
{

	private $options;
	public function preDispatch()
	{
	}

	public function init()
	{
		$employeeModel = new Default_Model_Employee();
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
					$id = ($this->getRequest()->getParam('unitId') != '') ? $this->getRequest()->getParam('unitId') : $this->getRequest()->getParam('unitId');
					$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
					$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
					$this->_helper->layout->disableLayout();
				
				}
				if ($id == '')		$id = $userID;
				$Uid = $id ?: $userID;
				if ($Uid != "") {
					$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
				}
				$employeeleavesModel = new Default_Model_Employeeleaves();
				$employeeModal = new Default_Model_Employee();
				$phoneno = new Default_Model_Phoneno();
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
						$by = 'ls.modifieddate';
						$pageNo = 1;
						$searchData = '';
						$searchQuery = '';
						$searchArray = [];
					} else {
						$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
						$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ls.modifieddate';
						if ($dashboardcall == 'Yes')
							$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
						else
							$perPage = $this->_getParam('per_page', PERPAGE);

						$pageNo = $this->_getParam('page', 1);
						$searchData = $this->_getParam('searchData');
						$searchData = rtrim($searchData, ',');
					}
					$dataTmp = $employeeleavesModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);
					
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
	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

		
				$userID = "";
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
				}
				// $id = $this->getRequest()->getParam('id');
				$id = $this->getRequest()->getParam('unitId');
				$calendarModel = new Default_Model_Calendar();
				$leavemanagementModel = new Default_Model_Leavemanagement();
				$employeeModal = new Default_Model_Employee();
				$employeeleavesModel = new Default_Model_Employeeleaves();
				
				try {
					if ($id && is_numeric($id) && $id > 0 ) {
						// && $id != $loginUserId
						$isrowexist = $employeeModal->getsingleEmployeeData($id);
						if ($isrowexist == 'norows')
							$this->view->rowexist = "norows";
						else
							$this->view->rowexist = "rows";
						$empdata = $employeeModal->getActiveEmployeeData($id);

						if (!empty($empdata)) {
							$call = $this->_getParam('call');
							if ($id == '')
								$id = $loginUserId;
							if ($call == 'ajaxcall') {
								$this->_helper->layout->disableLayout();
								$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
							}

							if ($id) {
								$empleavesform = new Default_Form_empleaves();
								$joiningdate = $empdata[0]['date_of_joining'];
								$empjoiningdate	= strtotime($joiningdate);
								$empjoiningyear = date("Y", $empjoiningdate);

								$currentyear = date("Y");
								$leaves = $employeeleavesModel->getAllIndividualLeaves();
								
								if (!empty($leaves) ) {
									$empleavesform->leave->addMultiOption('', 'Select...');
									foreach ($leaves as $leave) {
										$empleavesform->leave->addMultiOption($leave['leave_id'], $leave['name']);
									}
								} else {
									$msgarray['leave'] = 'Leaves are not configured yet!';
								}

								$years = $calendarModel->getAllYears();
								if (!empty($years)) {
									$empleavesform->year->addMultiOption('', 'Select...');
									foreach ($years as $year) {
										$empleavesform->year->addMultiOption($year['year_id'], $year['name']);
									}
								} else {
									$msgarray['year'] = 'Years are not configured yet!';
								}

								if ($empjoiningyear <= $currentyear) {
									$employeeleavesModal = new Default_Model_Employeeleaves();
									$currentdata = '';
									$leavetransferArr = $leavemanagementModel->getWeekendDetails($empdata[0]['department_id']);
									$prevyeardata = $employeeleavesModal->getPreviousYearEmployeeleaveData($id);
									$currentyeardata = $employeeleavesModal->getsingleEmployeeleaveData($id);
									if (empty($currentyeardata)) {
										$currentdata = "empty";
										$currentyearleavecount = '';
									} else {
										$currentdata = "notempty";
										$currentyearleavecount = $currentyeardata[0]['emp_leave_limit'];
									}

									$this->view->currentdata = $currentdata;
									$leavetransfercount = '';
									$previousyear = '';
									$isleavetrasnferset = '';
									$data = $employeeleavesModal->getsingleEmployeeleaveData($id);
									$used_leaves = 0;
									$date = date('Y');


									if (!empty($data)) {
										$used_leaves = $data[0]['used_leaves'];
									}
									// $empleavesform->alloted_year->setValue($date);
									if (!empty($leavetransferArr) && $leavetransferArr[0]['is_leavetransfer'] == 1 && !empty($prevyeardata) && is_numeric($prevyeardata[0]['remainingleaves']) && (int)$prevyeardata[0]['remainingleaves'] > 0 && $prevyeardata[0]['alloted_year'] != '' && empty($currentyeardata)) {
										$leavetransfercount = $prevyeardata[0]['remainingleaves'];
										$previousyear = $prevyeardata[0]['alloted_year'];
										$isleavetrasnferset = 1;
										$empleavesform->submitbutton->setAttrib('onClick', 'return showleavealert(' . $leavetransfercount . ',' . $previousyear . ')');
										$empleavesform->setAttrib('action', BASE_URL . 'empleaves/addpopup/unitId/' . $id);
									} else {
										$empleavesform->setAttrib('action', BASE_URL . 'empleaves/addpopup/unitId/' . $id);
									}
									$this->view->form = $empleavesform;
									$empleaveids='';
									$requestmodel=new Default_Model_Leaverequest();
									$request=$requestmodel->getsingleLeavessummaryDatabyemp($id);
									$leaveset=$requestmodel->leavefromsetting($id);
									foreach ($request as $requests) {
										$empleaveids .= $requests['leave_id'].',';
									//	array_push($perm,array($group['employee_id']=>'GROUP','Permission'=>$value['readwrite']));
									}
									foreach ($leaveset as $leavesets) {
										$empleaveids .= $leavesets['leave_id'].',';
									//	array_push($perm,array($group['employee_id']=>'GROUP','Permission'=>$value['readwrite']));
									}
									$empleaveids = trim($empleaveids,',');

									$expected = $requestmodel->getexpectedLeavessummary($empleaveids);
									$this->view->expected = $expected;
									$this->view->request = $request;

									$this->view->data = $data;
									$this->view->id = $id;
									$this->view->leavetransfercount = $leavetransfercount;
									$this->view->formflag = 'show';
									$this->view->leaves = $leaves;
								} else {
									$this->view->form = $empleavesform;
									$this->view->formflag = 'hide';
								}
							}
							if ($this->getRequest()->getPost()) {
								$result = $this->save($empleavesform, $id, $used_leaves, $leavetransfercount, $isleavetrasnferset, $currentyearleavecount);
								$this->view->msgarray = $result;
							}
						}
						$this->view->msgarray = $msgarray;
						$this->view->empdata = $empdata;
					}
				} catch (Exception $e) {
				}
		
	}

	public function editpopupAction()
	{
	
			//code...
		
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		try {
		$objName='empleaves';
	
				$userID = "";
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
				}

				// $id = $this->getRequest()->getParam('id');
				$id = $this->getRequest()->getParam('unitId');
				$leave_setting_id = $this->getRequest()->getParam('id');
				

				$calendarModel = new Default_Model_Calendar();
				$leavemanagementModel = new Default_Model_Leavemanagement();
				$employeeModal = new Default_Model_Employee();
				$employeeleavesModel = new Default_Model_Employeeleaves();
				try {
					if ($id && is_numeric($id) && $id >0) {
						$isrowexist = $employeeModal->getsingleEmployeeData($id);
						if ($isrowexist == 'norows')
							$this->view->rowexist = "norows";
						else
							$this->view->rowexist = "rows";
						$empdata = $employeeModal->getActiveEmployeeData($id);
						
						if (!empty($empdata)) {
							$call = $this->_getParam('call');
							if ($id == '')
								$id = $loginUserId;
							if ($call == 'ajaxcall') {
								$this->_helper->layout->disableLayout();
								$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
							}

							if ($id) {
								$empleavesform = new Default_Form_empleaves();
								$joiningdate = $empdata[0]['date_of_joining'];
								$empjoiningdate	= strtotime($joiningdate);
								$empjoiningyear = date("Y", $empjoiningdate);

								$currentyear = date("Y");
								$leaves = $employeeleavesModel->getAllIndividualLeaves();
								//die(json_encode($leaves));
							
								if (!empty($leaves)) {
									$empleavesform->leave->addMultiOption('', 'Select...');
									foreach ($leaves as $leave) {
										$empleavesform->leave->addMultiOption($leave['leave_id'], $leave['name']);
									}
								} else {

									$msgarray['leave'] = 'Leaves are not configured yet!';
								}
								$selectedLeave = $employeeleavesModel->getSingleEmpLeave($leave_setting_id);
								
								$this->view->leaveData = $selectedLeave;
								foreach ($selectedLeave as $data) {
									$empleavesform->leave->setValue($data['leave_id']);
									$empleavesform->isactive->setValue(utlis::getcheckboxvalue($data['isactive'],'form'));
									if ($data['year_id'] != null) {
										$empleavesform->year->setValue($data['year_id']);
									}
								}

								$years = $calendarModel->getAllYears();
								
								if (!empty($years)) {
									$empleavesform->year->addMultiOption('', 'Select...');
									foreach ($years as $year) {
										$empleavesform->year->addMultiOption($year['year_id'], $year['name']);
									}
								} else {
									$msgarray['year'] = 'Years are not configured yet!';
								}

								if ($empjoiningyear <= $currentyear) {
									$employeeleavesModal = new Default_Model_Employeeleaves();
									$currentdata = '';
									$leavetransferArr = $leavemanagementModel->getWeekendDetails($empdata[0]['department_id']);
									$prevyeardata = $employeeleavesModal->getPreviousYearEmployeeleaveData($id);
									$currentyeardata = $employeeleavesModal->getsingleEmployeeleaveData($id);
									
									if (empty($currentyeardata)) {
										$currentdata = "empty";
										$currentyearleavecount = '';
									} else {
										$currentdata = "notempty";
										$currentyearleavecount = $currentyeardata[0]['emp_leave_limit'];
									}

									$this->view->currentdata = $currentdata;
									$leavetransfercount = '';
									$previousyear = '';
									$isleavetrasnferset = '';
									$data = $employeeleavesModal->getsingleEmployeeleaveData($id);
									$used_leaves = 0;
									$date = date('Y');


									if (!empty($data)) {
										$used_leaves = $data[0]['used_leaves'];
									}
									// $empleavesform->alloted_year->setValue($date);

									if (!empty($leavetransferArr) && $leavetransferArr[0]['is_leavetransfer'] == 1 && !empty($prevyeardata) && is_numeric($prevyeardata[0]['remainingleaves']) && (int)$prevyeardata[0]['remainingleaves'] > 0 && $prevyeardata[0]['alloted_year'] != '' && empty($currentyeardata)) {
										$leavetransfercount = $prevyeardata[0]['remainingleaves'];
										$previousyear = $prevyeardata[0]['alloted_year'];
										$isleavetrasnferset = 1;
										$empleavesform->submitbutton->setAttrib('onClick', 'return showleavealert(' . $leavetransfercount . ',' . $previousyear . ')');
										$empleavesform->setAttrib('action', BASE_URL . 'empleaves/editpopup/id/' . $leave_setting_id . '/unitId/' . $id);
									} else {
										$empleavesform->setAttrib('action', BASE_URL . 'empleaves/editpopup/id/' . $leave_setting_id . '/unitId/' . $id);
									}
									$empleaveids='';
									$requestmodel=new Default_Model_Leaverequest();
									$request=$requestmodel->getsingleLeavessummaryDatabyemp($id);
									$leaveset=$requestmodel->leavefromsetting($id);
									foreach ($request as $requests) {
										$empleaveids .= $requests['leave_id'].',';
									//	array_push($perm,array($group['employee_id']=>'GROUP','Permission'=>$value['readwrite']));
									}
									foreach ($leaveset as $leavesets) {
										$empleaveids .= $leavesets['leave_id'].',';
									//	array_push($perm,array($group['employee_id']=>'GROUP','Permission'=>$value['readwrite']));
									}
									if($empleaveids!='' || $empleaveids!=null)
									{
										$empleaveids = trim($empleaveids,',');
										$expected=$requestmodel->getexpectedLeavessummary($empleaveids);

									}


									$this->view->form = $empleavesform;
									
									$this->view->data = $data;
									$this->view->request = $request;
									$this->view->expected = $expected;

									$this->view->id = $id;
									$this->view->leavetransfercount = $leavetransfercount;
									$this->view->formflag = 'show';
									$this->view->leaves = $leaves;
									$this->view->controllername = $objName;
									
								} else {
									$this->view->form = $empleavesform;
									$this->view->formflag = 'hide';
								}
							}
							if ($this->getRequest()->getPost()) {
							
								$result = $this->save($empleavesform, $id, $used_leaves, $leavetransfercount, $isleavetrasnferset, $currentyearleavecount);
								
								$this->view->msgarray = $result;
							}
							
						}
						$this->view->empdata = $empdata;
						
					}
				} catch (Exception $e) {
					Zend_Debug::dump($e);
				}
		
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
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
				$Uid = $id ?: $userID;
				if ($Uid != "") {
					$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
				}
				$employeeleavesModel = new Default_Model_Employeeleaves();
				$employeeModal = new Default_Model_Employee();
				$phoneno = new Default_Model_Phoneno();
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
						$by = 'ls.modifieddate';
						$pageNo = 1;
						$searchData = '';
						$searchQuery = '';
						$searchArray = [];
					} else {
						$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
						$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ls.modifieddate';
						if ($dashboardcall == 'Yes')
							$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
						else
							$perPage = $this->_getParam('per_page', PERPAGE);

						$pageNo = $this->_getParam('page', 1);
						$searchData = $this->_getParam('searchData');
						$searchData = rtrim($searchData, ',');
					}
					$dataTmp = $employeeleavesModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit');
					$phoneData  = $phoneno->getPhonenobyempid($Uid);
					$employeeData[0]['contactnumber'] = $phoneData[0]['phoneno'];
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
				$employeeleavesModel = new Default_Model_Employeeleaves();
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
						$by = 'ls.modifieddate';
						$pageNo = 1;
						$searchData = '';
						$searchQuery = '';
						$searchArray = [];
					} else {
						$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
						$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ls.modifieddate';
						if ($dashboardcall == 'Yes')
							$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
						else
							$perPage = $this->_getParam('per_page', PERPAGE);

						$pageNo = $this->_getParam('page', 1);
						$searchData = $this->_getParam('searchData');
						$searchData = rtrim($searchData, ',');
					}
					$dataTmp = $employeeleavesModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);

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

	public function save($empleavesform, $userid, $used_leaves, $leavetransfercount, $isleavetrasnferset, $currentyearleavecount)
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if ($empleavesform->isValid($this->_request->getPost())) {

			$employeeleavesModel = new Default_Model_Employeeleaves();
			$id = $this->_request->getParam('id');
			
			$user_id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
			// $emp_leave_limit = $this->_request->getParam('leave_limit');
			// if ($leavetransfercount != '' && $currentyearleavecount == '')
			// 	$emp_leave_limit = ($emp_leave_limit + $leavetransfercount);
			// else
			// 	$emp_leave_limit = ($emp_leave_limit + $currentyearleavecount);

			$isleavetrasnfer = 0;
			if ($isleavetrasnferset == 1)
				$isleavetrasnfer = 1;

			$date = new Zend_Date();
			$actionflag = '';
			$tableid  = '';
			/* Save employee leaves in allotted leaves log */
			$postedArr = [];
			$postedArr = $_POST;
			// $saveID = $employeeleavesModel->saveallotedleaves($postedArr, $emp_leave_limit, $user_id, $loginUserId);
			/* END */
			// die("Leave id ".$this->_request->getParam('period'));
			$data = [];
			$data['emp_id'] = $user_id;
			$data['leave_id'] = $this->_request->getParam('leave');
			$data['year_id'] = $this->_request->getParam('year');
			$data['period_id'] = $this->_request->getParam('period');
			$data['alloteddays'] = $this->_request->getParam('alloted_days');
			$where = '';
			if ($id) {
				$data['modifieddate'] = gmdate("Y-m-d H:i:s");
				$data['modifiedby'] = $loginUserId;
				$data['isactive'] = utlis::getcheckboxvalue($this->getRequest()->getParam('isactive'));
				$where = 'leave_setting_id = ' . $id;

			} else {
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$data['createdby'] = $loginUserId;
			}


			// $Id = $employeeleavesModel->SaveorUpdateEmployeeLeaves($user_id, $emp_leave_limit, $isleavetrasnfer, $loginUserId);
			// 
			$Id = $employeeleavesModel->SaveorUpdateEmpLeaves($data, $where);
			if ($id) {
				// $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Employee Leave details updated successfully."));
				$this->view->successmessage = "Employee Leave details updated successfully.";
				$actionflag = 2;
				$tableid = $id;
			} else {
				// $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Employee Leave details added successfully."));
				$this->view->successmessage = "Employee Leave details added successfully.";
				$actionflag = 1;
				$tableid = $Id;
			}
			$this->view->controllername = "empleaves";
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $user_id);
			// $this->_redirect('empleaves/edit/userid/' . $user_id);
		} else {
			$messages = $empleavesform->getMessages();
			// die(json_encode($messages));
			foreach ($messages as $key => $val) {
				foreach ($val as $key2 => $val2) {
					$msgarray[$key] = $val2;
					break;
				}
			}
			return $msgarray;
		}
	}


	public function viewpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$id = $this->getRequest()->getParam('id');
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$callval = $this->getRequest()->getParam('call');
		if ($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$objName = 'empleaves';

		$empleavesform = new Default_Form_empleaves();
		$employeeleavesModal = new Default_Model_Employeeleaves();
		$empleavesform->removeElement("submit");
		$elements = $empleavesform->getElements();

		if (count($elements) > 0) {
			foreach ($elements as $key => $element) {
				if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
					$element->setAttrib("disabled", "disabled");
				}
			}
		}
		$data = $employeeleavesModal->getsingleempleaveDataforindividual($id);
//die(json_encode($data));
		

		$this->view->controllername = $objName;
		$this->view->id = $id;
		$this->view->data = $data;
		$this->view->form = $empleavesform;
	}

	public function deleteAction()
	{
		$employeeleavesModel = new Default_Model_Employeeleaves();
		$id = $this->getRequest()->getParam('objid');

		$data = [
			'isactive' => 'N'
		];
		$where = 'leave_setting_id = ' . $id;
		// $employeeleavesModel->SaveorUpdateEmpLeaves($data, $where);
		$employeeleavesModel->deleteEmpLeave($where);
		return $this->_helper->json(['flagtype' => 'process', 'message' => 'Deleted']);
	}
}
