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

class Default_EmpsalarydetailsController extends Zend_Controller_Action
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
					$loginUserRole = $auth->getStorage()->read()->emprole;
					$loginUserGroup = $auth->getStorage()->read()->group_id;
				}
				$id = $this->getRequest()->getParam('userid');
				$conText = '';
				$call = $this->_getParam('call');
				if ($call == 'ajaxcall') {
					$this->_helper->layout->disableLayout();
					$id = ($this->getRequest()->getParam('unitId') != '') ? $this->getRequest()->getParam('unitId') : $this->getRequest()->getParam('unitId');
					$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
					$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
				}
				if ($id == '')		$id = $userID;
				$Uid = $id ?: $userID;

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
								$empsalarydetailsModal = new Default_Model_Empsalarydetails();
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
									$by = 'e.modifieddate';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = [];
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
								$dataTmp = $empsalarydetailsModal->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);

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
			
	}

	public function editAction()
	{

		
				$userID = "";
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
					$loginUserGroup = $auth->getStorage()->read()->group_id;
				}
				$id = $this->getRequest()->getParam('userid');
				$conText = '';
				$call = $this->_getParam('call');
				if ($call == 'ajaxcall') {
					$this->_helper->layout->disableLayout();
					$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
					$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
				}
				if ($id == '')		$id = $userID;
				$Uid = $id ?: $userID;

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
								$empsalarydetailsModal = new Default_Model_Empsalarydetails();
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
									$by = 'e.modifieddate';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = [];
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
								$dataTmp = $empsalarydetailsModal->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit');

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
		
	}

	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

		
				$auth = Zend_Auth::getInstance();
				$emptyFlag = 0;
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
				}

				$popConfigPermission = [];
				if (sapp_Global::_checkprivileges(CURRENCY, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'currency');
				}
				
				if (sapp_Global::_checkprivileges(PAYFREQUENCY, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'payfrequency');
				}

				$this->view->popConfigPermission = $popConfigPermission;

				$id = $this->getRequest()->getParam('unitId');
				// $userid = $this->getRequest()->getParam('unitId');

				if ($id == '')
					$id = $loginUserId;
				$callval = $this->getRequest()->getParam('call');
				if ($callval == 'ajaxcall')
					$this->_helper->layout->disableLayout();

				$empsalarydetailsform = new Default_Form_empsalarydetails();
				$empbankaccount_model = new Default_Model_Empbankaccount();

				try {
					if ($id && is_numeric($id) && $id > 0) {
						$employeeModal = new Default_Model_Employee();
						$usersModel = new Default_Model_Users();
						$empdata = $employeeModal->getActiveEmployeeData($id);
						$employeeData = $usersModel->getUserDetailsByIDandFlag($id);
						$empbankaccounts = $empbankaccount_model->getEmpBankAccounts();


						if ($empdata == 'norows') {
							$this->view->rowexist = "norows";
							$this->view->empdata = "";
						} else {

							$this->view->rowexist = "rows";
							if (!empty($empdata)) {
								$empsalarydetailsModal = new Default_Model_Empsalarydetails();
								$usersModel = new Default_Model_Users();

								$currencymodel = new Default_Model_Currency();

								$payfrequencyModal = new Default_Model_Payfrequency();

								$msgarray = [];

								$basecurrencymodeldata = $currencymodel->getCurrencyList();
								$empsalarydetailsform->currencyid->addMultiOption('', 'Select Salary Currency');
								if (sizeof($basecurrencymodeldata) > 0) {

									foreach ($basecurrencymodeldata as $basecurrencyres) {
										$empsalarydetailsform->currencyid->addMultiOption($basecurrencyres['id'], utf8_encode($basecurrencyres['currency']));
									}
								} else {
									$msgarray['currencyid'] = 'Salary currencies are not configured yet.';
									$emptyFlag++;
								}
								if (!empty($empbankaccounts)) {
									$empsalarydetailsform->bank_account->addMultiOption('', 'Select...');
									foreach ($empbankaccounts as $empbankaccount) {
										$empsalarydetailsform->bank_account->addMultiOption($empbankaccount['empbankaccount_id'], $empbankaccount['accountno']);
									}
								} else {
									$msgarray['bank_account'] = 'Employee bank account is not configured yet';
									$emptyFlag++;
								}

								// $payfreqData = $payfrequencyModal->getActivePayFreqData();
								$empsalarydetailsform->salarytype->addMultiOption('', 'Select Pay Frequency');
								$empsalarydetailsform->salarytype->addMultiOption('Monthly', 'Monthly');
								$empsalarydetailsform->salarytype->addMultiOption('Semimonthly', 'Semimonthly');
								$empsalarydetailsform->salarytype->addMultiOption('Weekly', 'Weekly');
								$empsalarydetailsform->salarytype->addMultiOption('Daily', 'Daily');
								$empsalarydetailsform->salarytype->addMultiOption('Hourly', 'Hourly');

								$empsalarydetailsform->user_id->setValue($id);
								$empsalarydetailsform->setAttrib('action', BASE_URL . 'empsalarydetails/addpopup/unitId/' . $id);

								$this->view->form = $empsalarydetailsform;

								$this->view->data = $data[0] ?? [];
								$this->view->id = $id;
								$this->view->msgarray = $msgarray;
								$this->view->emptyFlag = $emptyFlag;
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
				if ($this->getRequest()->getPost()) {

					$result = $this->save($empsalarydetailsform);

					$this->view->msgarray = $result;
				}
		
	}

	public function editpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

	
				$auth = Zend_Auth::getInstance();
				$emptyFlag = 0;
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
				}
				$popConfigPermission = [];
				if (sapp_Global::_checkprivileges(CURRENCY, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'currency');
				}
				if (sapp_Global::_checkprivileges(PAYFREQUENCY, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'payfrequency');
				}
				$this->view->popConfigPermission = $popConfigPermission;

				$id = $this->getRequest()->getParam('id');
				$userid = $this->getRequest()->getParam('unitId');

				if ($id == '')
					$id = $loginUserId;
				$callval = $this->getRequest()->getParam('call');
				if ($callval == 'ajaxcall')
					$this->_helper->layout->disableLayout();

				$empsalarydetailsform = new Default_Form_empsalarydetails();
				$empbankaccount_model = new Default_Model_Empbankaccount();
				try {
					$empsalarydetailsform->id->setValue($id);
					$empsalarydetailsform->user_id->setValue($userid);
					if ($userid && is_numeric($userid) && $userid > 0 ) {
						$employeeModal = new Default_Model_Employee();
						$usersModel = new Default_Model_Users();

						$empdata = $employeeModal->getActiveEmployeeData($userid);
						$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);
						$empbankaccounts = $empbankaccount_model->getEmpBankAccounts();

						if ($empdata == 'norows') {
							$this->view->rowexist = "norows";
							$this->view->empdata = "";
						} else {

							$this->view->rowexist = "rows";
							if (!empty($empdata)) {
								$empsalarydetailsModal = new Default_Model_Empsalarydetails();

								$usersModel = new Default_Model_Users();

								$currencymodel = new Default_Model_Currency();



								$msgarray = [];
								
								$empsalaryData = $empsalarydetailsModal->getsingleSalaryDetailsData($id);

								$basecurrencymodeldata = $currencymodel->getCurrencyList();
								$empsalarydetailsform->currencyid->addMultiOption('', 'Select Salary Currency');
								if (sizeof($basecurrencymodeldata) > 0) {

									foreach ($basecurrencymodeldata as $basecurrencyres) {
										$empsalarydetailsform->currencyid->addMultiOption($basecurrencyres['id'], utf8_encode($basecurrencyres['currency']));
									}
								} else {
									$msgarray['currencyid'] = 'Salary currencies are not configured yet.';
									$emptyFlag++;
								}

								if (!empty($empbankaccounts)) {
									$empsalarydetailsform->bank_account->addMultiOption('', 'Select...');
									foreach ($empbankaccounts as $empbankaccount) {
										$empsalarydetailsform->bank_account->addMultiOption($empbankaccount['empbankaccount_id'], $empbankaccount['accountno']);
									}
								} else {
									$msgarray['bank_account'] = 'Employee bank account is not configured yet';
									$emptyFlag++;
								}
								$empsalarydetailsform->salarytype->addMultiOption('', 'Select Pay Frequency');
								$empsalarydetailsform->salarytype->addMultiOption('Monthly', 'Monthly');
								$empsalarydetailsform->salarytype->addMultiOption('Semimonthly', 'Semimonthly');
								$empsalarydetailsform->salarytype->addMultiOption('Weekly', 'Weekly');
								$empsalarydetailsform->salarytype->addMultiOption('Daily', 'Daily');
								$empsalarydetailsform->salarytype->addMultiOption('Hourly', 'Hourly');

								$empsalarydetailsform->user_id->setValue($userid);
								$empsalarydetailsform->setAttrib('action', BASE_URL . 'empsalarydetails/editpopup/unitId/' . $userid);
								// die(json_encode($empsalaryData));
								
								if (!empty($empsalaryData)) {
									
									$empsalarydetailsform->salary->setValue($empsalaryData[0]['amount']);
									$empsalarydetailsform->description->setValue($empsalaryData[0]['description']);
									$empsalarydetailsform->salarytype->setValue($empsalaryData[0]['amount_frequency']);
									$empsalarydetailsform->currencyid->setValue($empsalaryData[0]['currency_id']);
									$empsalarydetailsform->start_date->setValue(sapp_Global::change_date($empsalaryData[0]['from_date'], ''));
									$empsalarydetailsform->end_date->setValue(sapp_Global::change_date($empsalaryData[0]['end_date'], ''));
									$empsalarydetailsform->bank_account->setValue($empsalaryData[0]['empbankaccount_id']);
									$empsalarydetailsform->isactive->setValue(utlis::getcheckboxvalue($empsalaryData[0]['isactive'],'form'));
								}

								$this->view->data = $data[0] ?? [];
								$this->view->id = $id;
								$this->view->msgarray = $msgarray;
								$this->view->employeedata = $employeeData[0];
								$this->view->emptyFlag = $emptyFlag;
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
				$this->view->form = $empsalarydetailsform;
				if ($this->getRequest()->getPost()) {

					$result = $this->save($empsalarydetailsform);

					$this->view->msgarray = $result;
				}
			
	}

	public function viewAction()
	{
		
				$userID = "";
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
					$loginUserGroup = $auth->getStorage()->read()->group_id;
				}
				$id = $this->getRequest()->getParam('userid');
				$conText = '';
				$call = $this->_getParam('call');
				if ($call == 'ajaxcall') {
					$this->_helper->layout->disableLayout();
					$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
					$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
				}
				if ($id == '')		$id = $userID;
				$Uid = $id ?: $userID;

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
								$empsalarydetailsModal = new Default_Model_Empsalarydetails();
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
									$by = 'e.modifieddate';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = [];
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
								$dataTmp = $empsalarydetailsModal->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);

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

		$empsalarydetailsform = new Default_Form_empsalarydetails();
		$empsalarydetailsModel = new Default_Model_Empsalarydetails();

		$data = $empsalarydetailsModel->getsingleSalaryDetailsData($id);
		// die(json_encode($data));
		$this->view->data = $data;
		$this->view->form = $empsalarydetailsform;
	}

	public function save($empsalarydetailsform)
	{
		$userid = $this->getRequest()->getParam('unitId');
		$id = $this->getRequest()->getParam('id');
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		if ($empsalarydetailsform->isValid($this->_request->getPost())) {
			$post_values = $this->_request->getPost();
			if (isset($post_values['id']))
				unset($post_values['id']);
			if (isset($post_values['unitId']))
				unset($post_values['unitId']);
			if (isset($post_values['submit']))
				unset($post_values['submit']);
			$new_post_values = array_filter($post_values);
			if (!empty($new_post_values)) {
				$empsalarydetailsModal = new Default_Model_Empsalarydetails();
				// $id = $this->_request->getParam('id');
				$user_id = $userid;
				$currencyid = $this->_request->getParam('currencyid');
				$salarytype = $this->_request->getParam('salarytype');
				$salary = $this->_request->getParam('salary');
				$description = $this->_request->getParam('description');
				$empbankaccount_id = $this->_request->getParam('bank_account');
				// die($salary);
				$from = $this->getRequest()->getParam('start_date');
				$to = $this->getRequest()->getParam('end_date');


				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				// $salary = sapp_Global::_encrypt($salary);
				$data = [
					'emp_id' => $user_id,
					'currency_id' => $currencyid,
					'amount_frequency' => $salarytype,
					'amount' => ($salary != '' ? $salary : NULL),
					'description' => $description,
					'empbankaccount_id' => $empbankaccount_id,
					'from_date' => sapp_Global::change_date($from, 'database'),
					'end_date' => sapp_Global::change_date($to, 'database'),
					'modifiedby' => $loginUserId,
					'modifieddate' => gmdate("Y-m-d H:i:s")
				];
				if ($id != '') {
					$where = ['empsalary_id=?' => $id];
					$actionflag = 2;
					$data['modifiedby'] = $loginUserId;
					$data['modifieddate'] = gmdate("Y-m-d H:i:s");
					$data['isactive'] =utlis::getcheckboxvalue($this->getRequest()->getParam('isactive'));
				} else {
					$data['createdby'] = $loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");
					$data['isactive'] = 'Y';
					$where = '';
					$actionflag = 1;
				}



				$Id = $empsalarydetailsModal->SaveorUpdateEmpSalaryData($data, $where);
				if ($Id == 'update') {
					$tableid = $id;
					$this->view->successmessage = 'Employee salary updated successfully.';
				} else {
					$tableid = $Id;
					$this->view->successmessage = 'Employee salary added successfully.';
				}
				$menuID = EMPLOYEE;
				$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $user_id);
				$this->view->controllername = 'empsalarydetails';
				Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
			} else {

				$this->_helper->getHelper("FlashMessenger")->addMessage(["error" => FIELDMSG]);
			}
			// $this->_redirect('empsalarydetails/index/userid/' . $userid);
		} else {
			$messages = $empsalarydetailsform->getMessages();
			foreach ($messages as $key => $val) {
				foreach ($val as $key2 => $val2) {
					$msgarray[$key] = $val2;
					break;
				}
			}

			return $msgarray;
		}
	}
}
