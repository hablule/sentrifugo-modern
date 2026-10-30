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

class Default_EmpholidaysController extends Zend_Controller_Action
{

	private $options;
	public function preDispatch()
	{
	}

	public function init()
	{
				require_once 'application/modules/default/library/utilClasses/Util.php';

		$employeeModel = new Default_Model_Employee();
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
	}
	public function indexAction()
	{
		
				$emptyFlag = 0;
				$conText = "";
				$userID = "";
				$msgarray = array();
				$empGroupId = '';
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
				}
				$id = $this->getRequest()->getParam('userid');
				$call = $this->_getParam('call');
				$holidaygroupid = $this->getRequest()->getParam('groupid');
				if ($call == 'ajaxcall') {
					if ($holidaygroupid != '')
						$_SESSION['holidaygroupid'] = $holidaygroupid;
					$this->_helper->layout->disableLayout();
					$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
					$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
				} else {
					unset($_SESSION['holidaygroupid']);
				}


				if ($id == '')		$id = $userID;
				$Uid = ($id) ? $id : $userID;
				if ($Uid != "") {
					//TO dispaly EMployee Profile information.....
					$usersModel = new Default_Model_Users();
					$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
				}
				$employeesModel = new Default_Model_Employees();
				$holidaydatesmodel = new Default_Model_Holidaydates();
				$employeeModal = new Default_Model_Employee();
				$phoneno = new Default_Model_Phoneno();
				try {
					if ($id && is_numeric($id) && $id > 0) {
						$isrowexist = $employeeModal->getsingleEmployeeData($id);
						if ($isrowexist == 'norows')
							$this->view->rowexist = "norows";
						else
							$this->view->rowexist = "rows";

						$empdata = $employeeModal->getActiveEmployeeData($id);
						if (!empty($empdata)) {
							if ($id) {
								$empholidaysform = new Default_Form_empholidays();
								$holidaygroupModel = new Default_Model_Holidaygroups();
								$holidaygroupArr = $holidaygroupModel->getAllGroupData();

								if (!empty($holidaygroupArr)) {
									foreach ($holidaygroupArr as $holidaygroupres) {
										$empholidaysform->holiday_group->addMultiOption($holidaygroupres['id'], $holidaygroupres['groupname']);
									}
								} else {
									$msgarray['empholidaysform'] = 'Holiday groups not configured yet';
									$emptyFlag++;
								}
								$data = $employeesModel->getHolidayGroupForEmployee($id);

								if ($data[0]['holiday_group_id'] != '') {
									$singleholidaygroupArr = $holidaygroupModel->getsingleGroupData($data[0]['holiday_group']);
									$empholidaysform->populate($data[0]);
									$empholidaysform->setDefault('holiday_group', $data[0]['holiday_group_id']);
									//$empGroupId = $data[0]['holiday_group'];
									$this->view->data = $data;
								}
								$empholidaysform->setAttrib('action', BASE_URL . 'empholidays/edit/userid/' . $id);
								$this->view->form = $empholidaysform;

								if ($holidaygroupid != '') {
									$empGroupId = $holidaygroupid;
								} else {
									if (isset($_SESSION['holidaygroupid'])) {
										$empGroupId = $_SESSION['holidaygroupid'];
									} else if ($data[0]['holiday_group'] != '')
										$empGroupId = $data[0]['holiday_group'];
									else
										$empGroupId = '';
								}
							}
							if ($this->getRequest()->getPost()) {
								$result = $this->save($empholidaysform, $id);
								$this->view->msgarray = $result;
							}

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
								$by = 'h.modifieddate';
								$pageNo = 1;
								$searchData = '';
								$searchQuery = '';
								$searchArray = array();
							} else {
								$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
								$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'h.modifieddate';
								if ($dashboardcall == 'Yes')
									$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
								else
									$perPage = $this->_getParam('per_page', PERPAGE);

								$pageNo = $this->_getParam('page', 1);
								$searchData = $this->_getParam('searchData');
								$searchData = rtrim($searchData, ',');
							}
							$objName = 'empholidays';

							$dataTmp = $holidaydatesmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $objName, $empGroupId, $Uid, $conText);
							
							$phoneData  = $phoneno->getPhonenobyempid($Uid);
							$employeeData[0]['contactnumber'] = $phoneData[0]['phoneno'];
							
							array_push($data, $dataTmp);
							$this->view->dataArray = $data;
							$this->view->call = $call;
							$this->view->id = $id;
							$this->view->emptyFlag = $emptyFlag;
							$this->view->messages = $this->_helper->flashMessenger->getMessages();
							if (!empty($employeeData))
								$this->view->employeedata = $employeeData[0];
						}
						$this->view->empdata = $empdata;
					} else {
						$this->view->rowexist = "norows";
					}
				} catch (Exception $e) {
					$this->view->rowexist = "norows";
				}
			
	}
	public function editAction()
	{
try {
	//code...
		
				$userID = "";
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
					$loginUserGroup = $auth->getStorage()->read()->group_id;
				}
				$id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
				$conText = '';
				$call = $this->_getParam('call');
				if ($call == 'ajaxcall') {
					$this->_helper->layout->disableLayout();
					$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
					$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
				}
				if ($id == '')		$id = $userID;
				$Uid = ($id) ? $id : $userID;

				$employeeModal = new Default_Model_Employee();
				try {
					if ($Uid && is_numeric($Uid) && $Uid > 0) {
						$usersModel = new Default_Model_Users();
						
						$empdata = $employeeModal->getActiveEmployeeData($Uid);
						$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
						if ($empdata == 'norows') {
							$this->view->rowexist = "norows";
							$this->view->empdata = "";
						} else {
							$this->view->rowexist = "rows";
							if (!empty($empdata)) {
								$model = new Default_Model_Empholidays();
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
									$by = 'eh.modifieddate';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = array();
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'eh.modifieddate';
									if ($dashboardcall == 'Yes')
										$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
									else
										$perPage = $this->_getParam('per_page', PERPAGE);

									$pageNo = $this->_getParam('page', 1);
									$searchData = $this->_getParam('searchData');
									$searchData = rtrim($searchData, ',');
								}

								$dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit');

								array_push($data, $dataTmp);
								$this->view->dataArray = $data;
								$this->view->call = $call;
								$this->view->employeedata = $employeeData[0];
								$this->view->id = $id;
								$this->view->messages = $this->_helper->flashMessenger->getMessages();
							}
							$this->view->empdata = $empdata;
						}
					} else {
						$this->view->rowexist = "norows";
					}
				} catch (Exception $e) {
					$this->view->rowexist = "norows";
				}
		
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
	}




	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		try {
			//code...
			$categorymodel=new Default_Model_Holidaygroups();
			$holidaymodel=new Default_Model_Holidaydates();

		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		// if (sapp_Global::_checkprivileges(BENEFITS, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
		// 	array_push($popConfigPermission, 'empbenefits');
		// }

		
			$emptyFlag = 0;

			$userid = $this->getRequest()->getParam('unitId');
		

			$form = new Default_Form_empholidays();

		
			// $empbankaccountForm->id->setValue($userid);
			$msgarray = [];


				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
				}
				$employeeModal = new Default_Model_Employee();
				$isrowexist = $employeeModal->getsingleEmployeeData($userid);
				if ($isrowexist == 'norows')
				$this->view->rowexist = "norows";
			else
			$this->view->rowexist = "rows";
		
		$empdata = $employeeModal->getActiveEmployeeData($userid);
		
		if (!empty($empdata)) {
			// $accountclasstypemodel = new Default_Model_Accountclasstype();
			// $bankaccounttypemodel = new Default_Model_Bankaccounttype();
			$util = new utlis();
			// $this->setElementDecorators(array('ViewHelper')); 	 
			$type = $categorymodel->getAllGroupData();
			$holiday=$holidaymodel->getAllholiday();
			$form->holidaycategory_id->addMultiOption('', 'Select Holiday Category');
			$form->holiday_id->addMultiOption('', 'Select Holiday');
			
			$empdata = $employeeModal->getEmplist();
			// $locations = $location_model->getLocations();
			// if(!empty($locations)){
				// 	$empbankaccountForm->location->addMultiOption('empty','Select...');
				// 	foreach($locations as $location){
					// 		$empbankaccountForm->location->addMultiOption($location['id'],$location['location']);
					// 	}
					// }else{
						// 	$msgarray['location'] = 'Locations are not configured yet.';
						// }
						if (!empty($type)) {
							foreach ($type as $types) {
								$form->holidaycategory_id->addMultiOption($types['id'], $types['name']);
							}
					} else {
						$msgarray['holidaycategory_id'] = 'Holiday Category are not configured yet.';
						$emptyFlag++;
					}
					if (!empty($holiday)) {
						foreach ($holiday as $holidays) {
							$form->holiday_id->addMultiOption($holidays['id'], $holidays['name']);
						}
					} else {
						$msgarray['holiday_id'] = 'Holiday  are not configured yet.';
						$emptyFlag++;
					}
					
					if ($userid) {
						//To display Employee Profile information......
						$usersModel = new Default_Model_Users();
						$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);
					}
					
					// $accountclasstypeArr = $accountclasstypemodel->getAccountClassTypeList();
					// $empbankaccountForm->accountclasstypeid->addMultiOption('', 'Select Account Type');
					// $empbankaccountForm->accountclasstypeid->addMultiOption('Saving', 'Saving');
					// $empbankaccountForm->accountclasstypeid->addMultiOption('Checking', 'Checking');

					// $empbankaccountForm->isdefault->addMultiOption('', 'Select...');
					// $empbankaccountForm->isdefault->addMultiOption('Y', 'Yes');
					// $empbankaccountForm->isdefault->addMultiOption('N', 'No');
					// if (!empty($accountclasstypeArr)) {

					// 	foreach ($accountclasstypeArr as $accountclasstyperes) {
						// 		$empbankaccountForm->accountclasstypeid->addMultiOption($accountclasstyperes['id'], $accountclasstyperes['accountclasstype']);
						// 	}
					// } else {
					// 	$msgarray['accountclasstypeid'] = 'Account class types are not configured yet.';
					// 	$emptyFlag++;
					// }
					$empholiday='';
					$empcategory='';

					$request=$holidaymodel->getsingleholidaysummaryDatabyemp($userid);
					$holidayset=$holidaymodel->holidayfromemp($userid);
					Zend_Debug::dump($holidayset);
					foreach ($holidayset as $holidaysets) {
						$empholiday .= $holidaysets['holiday_id'].',';
						$empcategory .= $holidaysets['holidaycategory_id'].',';

					//	array_push($perm,array($group['employee_id']=>'GROUP','Permission'=>$value['readwrite']));
					}
					$empcategory = trim($empcategory,',');
					if($empcategory!='' || $empcategory!=null)
					{
						$holidayset2=$holidaymodel->holidayfromcat($empcategory);
					}
					
					foreach ($holidayset2 as $holidaysets2) {
						$empholiday .= $holidaysets2['holiday_id'].',';
						
						//	array_push($perm,array($group['employee_id']=>'GROUP','Permission'=>$value['readwrite']));
					}
					$empholiday = trim($empholiday,',');
					if($empholiday!='' || $empholiday!=null)
					{
						$expected=$holidaymodel->getsingleexpectedholidaysummaryDatabyemp($empholiday);
						$this->view->expected = $expected;
					}

					$this->view->request = $request;
					$form->setAttrib('action', BASE_URL . 'empholidays/addpopup/unitId/' . $userid);
					// $this->view->id = $userid;
					$this->view->employeedata = $employeeData[0];

					if ($this->getRequest()->getPost()) {
						if (
							$this->_request->getParam('holiday_id') == '' && $this->_request->getParam('holidaycategory_id') == ''
					
						) {
							$msgarray['generalerror'] = 'choose one from Company,Bussiness Unit,division,Department,Section, Project or Employee';
							$messages = $form->getMessages();
							foreach ($messages as $key => $val) {
								foreach ($val as $key2 => $val2) {
									$msgarray[$key] = $val2;
									break;
								}
							}
							$msgarray = $this->view->msgarray;

						} else {
						$this->save($form,$userid,'add');
						$msgarray = $this->view->msgarray;
						}
					}
				}
				$this->view->empdata = $empdata;
				$this->view->form = $form;
				$this->view->msgarray = $msgarray;
			
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
	}



	public function editpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		try {
			//code...
			$categorymodel=new Default_Model_Holidaygroups();
			$holidaymodel=new Default_Model_Holidaydates();
			$model=new Default_Model_Empholidays();
			
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		// if (sapp_Global::_checkprivileges(BENEFITS, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
		// 	array_push($popConfigPermission, 'empbenefits');
		// }

	
			$emptyFlag = 0;

			$userid = $this->getRequest()->getParam('unitId');
			$id = $this->getRequest()->getParam('id');


			$form = new Default_Form_empholidays();

		
			// $empbankaccountForm->id->setValue($userid);
			$msgarray = [];


				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
				}
				$employeeModal = new Default_Model_Employee();
				$isrowexist = $employeeModal->getsingleEmployeeData($userid);
				if ($isrowexist == 'norows')
					$this->view->rowexist = "norows";
				else
					$this->view->rowexist = "rows";

				$empdata = $employeeModal->getActiveEmployeeData($userid);

				if (!empty($empdata)) {
					// $accountclasstypemodel = new Default_Model_Accountclasstype();
					// $bankaccounttypemodel = new Default_Model_Bankaccounttype();
					$util = new utlis();
					// $this->setElementDecorators(array('ViewHelper')); 	 
					$type = $categorymodel->getAllGroupData();
					$holiday=$holidaymodel->getAllholiday();
					$form->holidaycategory_id->addMultiOption('', 'Select Holiday Category');
					$form->holiday_id->addMultiOption('', 'Select Holiday');

					$empdata = $employeeModal->getEmplist();
					// $locations = $location_model->getLocations();
					// if(!empty($locations)){
					// 	$empbankaccountForm->location->addMultiOption('empty','Select...');
					// 	foreach($locations as $location){
					// 		$empbankaccountForm->location->addMultiOption($location['id'],$location['location']);
					// 	}
					// }else{
					// 	$msgarray['location'] = 'Locations are not configured yet.';
					// }
					if (!empty($type)) {
						foreach ($type as $types) {
							$form->holidaycategory_id->addMultiOption($types['id'], $types['name']);
						}
					} else {
						$msgarray['holidaycategory_id'] = 'Holiday Category are not configured yet.';
						$emptyFlag++;
					}
					if (!empty($holiday)) {
						foreach ($holiday as $holidays) {
							$form->holiday_id->addMultiOption($holidays['id'], $holidays['name']);
						}
					} else {
						$msgarray['holiday_id'] = 'Holiday  are not configured yet.';
						$emptyFlag++;
					}

					if ($userid) {
						//To display Employee Profile information......
						$usersModel = new Default_Model_Users();
						$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);
					}
					
					$empholiday = $model->getsingleempholidaydatas($id);

					$empholidayids='';
					$empcategory='';

					$request=$holidaymodel->getsingleholidaysummaryDatabyemp($userid);
					$holidayset=$holidaymodel->holidayfromemp($userid);
					foreach ($holidayset as $holidaysets) {
						$empholidayids .= $holidaysets['holiday_id'].',';
						$empcategory .= $holidaysets['holidaycategory_id'].',';

					//	array_push($perm,array($group['employee_id']=>'GROUP','Permission'=>$value['readwrite']));
					}
					$empcategory = trim($empcategory,',');
					
					if($empcategory!='' || $empcategory!=null)
					{
						$holidayset2=$holidaymodel->holidayfromcat($empcategory);
					}
					foreach ($holidayset2 as $holidaysets2) {
						$empholidayids .= $holidaysets2['holiday_id'].',';

					//	array_push($perm,array($group['employee_id']=>'GROUP','Permission'=>$value['readwrite']));
					}
					$empholidayids = trim($empholidayids,',');
					if($empholidayids!='' || $empholidayids!=null)
					{
					$expected=$holidaymodel->getsingleexpectedholidaysummaryDatabyemp($empholidayids);
					$this->view->expected = $expected;
					}
					
					$this->view->request = $request;
					//$this->view->expected = $expected;

					$form->populate($empholiday);
					$form->isactive->setValue($util::getcheckboxvalue($empholiday['isactive'],'form'));
				
					// $form->setAttrib('action', BASE_URL . 'empholidays/edirtpopup/unitId/' . $userid);
					$this->view->employeedata = $employeeData[0];

					if ($this->getRequest()->getPost()) {
						if (
							$this->_request->getParam('holiday_id') == '' && $this->_request->getParam('holidaycategory_id') == ''
					
						) {
							$msgarray['generalerror'] = 'choose one from Company,Bussiness Unit,division,Department,Section, Project or Employee';
							$messages = $form->getMessages();
							foreach ($messages as $key => $val) {
								foreach ($val as $key2 => $val2) {
									$msgarray[$key] = $val2;
									break;
								}
							}
							$msgarray = $this->view->msgarray;

						} else {
						$this->save($form,$userid);
						$msgarray = $this->view->msgarray;
						}
					}
				}
				$this->view->empdata = $empdata;
				$this->view->form = $form;
				$this->view->msgarray = $msgarray;
			
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
	}
	public function viewAction()
	{
try {
	//code...
		
				$userID = "";
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
					$loginUserGroup = $auth->getStorage()->read()->group_id;
				}
				$id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
				$conText = '';
				$call = $this->_getParam('call');
				if ($call == 'ajaxcall') {
					$this->_helper->layout->disableLayout();
					$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
					$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
				}
				if ($id == '')		$id = $userID;
				$Uid = ($id) ? $id : $userID;

				$employeeModal = new Default_Model_Employee();
				try {
					if ($Uid && is_numeric($Uid) && $Uid > 0) {
						$usersModel = new Default_Model_Users();
						$empdata = $employeeModal->getActiveEmployeeData($Uid);
						$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
						if ($empdata == 'norows') {
							$this->view->rowexist = "norows";
							$this->view->empdata = "";
						} else {
							$this->view->rowexist = "rows";
							if (!empty($empdata)) {
								$model = new Default_Model_Empholidays();
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
									$by = 'eh.modifieddate';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = array();
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'eh.modifieddate';
									if ($dashboardcall == 'Yes')
										$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
									else
										$perPage = $this->_getParam('per_page', PERPAGE);

									$pageNo = $this->_getParam('page', 1);
									$searchData = $this->_getParam('searchData');
									$searchData = rtrim($searchData, ',');
								}

								$dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'view');

								array_push($data, $dataTmp);
								$this->view->dataArray = $data;
								$this->view->call = $call;
								$this->view->employeedata = $employeeData[0];
								$this->view->id = $id;
								$this->view->messages = $this->_helper->flashMessenger->getMessages();
							}
							$this->view->empdata = $empdata;
						}
					} else {
						$this->view->rowexist = "norows";
					}
				} catch (Exception $e) {
					$this->view->rowexist = "norows";
				}
			
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
	}

	public function save($empholidaysform, $userid)
	{
		$util = new utlis();

		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if ($empholidaysform->isValid($this->_request->getPost())) {
			$employeesModel = new Default_Model_Employees();
			$holiday_group = $this->_request->getParam('holidaycategory_id');
			$holiday = $this->_request->getParam('holiday_id');
			$description = $this->_request->getParam('description');
			$isactive=$util::getcheckboxvalue($this->_request->getParam('isactive'),'database');
			$user_id = $userid;
			$id = $this->getRequest()->getParam('id');

			$date = new Zend_Date();
			$actionflag = '';
			$tableid  = '';
			if ($user_id != '') {
				$data = array(
					'emp_id'=>(int)$userid,
					'holidaycategory_id' => $holiday_group,
					'holiday_id' => $holiday,
					'description' =>$description,
					'isactive' =>$isactive,

					'modifiedby' => (int)$loginUserId,
					'modifieddate' => gmdate("Y-m-d H:i:s")
				);
				if($id!=''){
					$where = array(
						'empholiday_id=?' => $id,
					);
				}else{
					$where = '';
					$data['createdby'] = (int)$loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");
				}
			$actionflag = 2;
				$Id = $employeesModel->SaveorUpdateEmployees($data, $where);
				if ($Id == 'update') {
					$empdetailsArr = $employeesModel->getLoggedInEmployeeDetails($user_id);
					$tableid = $empdetailsArr[0]['id'];
					$this->view->successmessage = 'Employee Holiday updated successfully.';
				} else {
					$tableid = $Id;
					$this->view->successmessage = 'Employee Holiday Added successfully.';
				}
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $user_id);
			$this->view->controllername = 'empholidays';
		} else {
			$messages = $empholidaysform->getMessages();
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
		$callval = $this->getRequest()->getParam('call');
		if ($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$objName = 'empholidays';
		$holidaydatesform = new Default_Form_empholidays();
		$holidaydatesmodel = new Default_Model_Empholidays();
		$holidaydatesform->removeElement("submit");
		$data = $holidaydatesmodel->getsingleholidayview($id);
		$elements = $holidaydatesform->getElements();
		if (count($elements) > 0) {
			foreach ($elements as $key => $element) {
				if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
					$element->setAttrib("disabled", "disabled");
				}
			}
		}

		// $holidaydatesform->populate($data);

		// $holidaydate = sapp_Global::change_date($data['holidaydate'], 'view');
		// $holidaydatesform->holidaydate->setValue($holidaydate);

		$this->view->controllername = $objName;
		$this->view->id = $id;
		$this->view->data = $data[0];
		$this->view->form = $holidaydatesform;
	}
}
