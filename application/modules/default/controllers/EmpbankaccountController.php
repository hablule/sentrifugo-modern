<?php
session_start();
class Default_EmpbankaccountController extends Zend_Controller_Action
{

	public function init()
	{
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';
	}

	public function indexAction()
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
								$empbankaccount_model = new Default_Model_Empbankaccount();
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
								$dataTmp = $empbankaccount_model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);

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
				$phoneno = new Default_Model_Phoneno();
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
								$empbankaccount_model = new Default_Model_Empbankaccount();
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
								$dataTmp = $empbankaccount_model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit');
								
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
		try {
		
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$bankpermission = '';
		$bankpermission = sapp_Global::_checkprivileges(BANK,$loginuserGroup,$loginuserRole,'add');
$this->view->bankpermission = $bankpermission;


			$emptyFlag = 0;

			$userid = $this->getRequest()->getParam('unitId');


			if ($userid == '')
				$userid = $loginUserId;

			$empbankaccountForm = new Default_Form_Empbankaccount();


			// $empbankaccountForm->id->setValue($userid);
			$msgarray = [];


				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
				}
				$userid = $this->getRequest()->getParam('unitId');

				$employeeModal = new Default_Model_Employee();
				$location_model = new Default_Model_Location();

				$isrowexist = $employeeModal->getsingleEmployeeData($userid);
				if ($isrowexist == 'norows')
					$this->view->rowexist = "norows";
				else
					$this->view->rowexist = "rows";




				$empdata = $employeeModal->getActiveEmployeeData($userid);

				if (!empty($empdata)) {
					// $accountclasstypemodel = new Default_Model_Accountclasstype();
					// $bankaccounttypemodel = new Default_Model_Bankaccounttype();
					$bank_model = new Default_Model_Bank();

					$banks = $bank_model->getBanks();

					$empbankaccountForm->bankname->addMultiOption('', 'Select Bank');
					// $locations = $location_model->getLocations();
					// if(!empty($locations)){
					// 	$empbankaccountForm->location->addMultiOption('empty','Select...');
					// 	foreach($locations as $location){
					// 		$empbankaccountForm->location->addMultiOption($location['id'],$location['location']);
					// 	}
					// }else{
					// 	$msgarray['location'] = 'Locations are not configured yet.';
					// }
					if (!empty($banks)) {
						foreach ($banks as $bank) {
							$empbankaccountForm->bankname->addMultiOption($bank['bank_id'], $bank['name']);
						}
					} else {
						$msgarray['bankname'] = 'Banks are not configured yet.';
						$emptyFlag++;
					}

					if ($userid) {
						//To display Employee Profile information......
						$usersModel = new Default_Model_Users();
						$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);
					}

					// $accountclasstypeArr = $accountclasstypemodel->getAccountClassTypeList();
					$empbankaccountForm->accountclasstypeid->addMultiOption('', 'Select Account Type');
					$empbankaccountForm->accountclasstypeid->addMultiOption('Saving', 'Saving');
					$empbankaccountForm->accountclasstypeid->addMultiOption('Checking', 'Checking');

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
					$empbankaccountForm->setAttrib('action', BASE_URL . 'empbankaccount/addpopup/unitId/' . $userid);
					// $this->view->id = $userid;
					$this->view->employeedata = $employeeData[0];

					if ($this->getRequest()->getPost()) {


						$this->save($empbankaccountForm);
						$msgarray = $this->view->msgarray;
					}
				}
				$this->view->empdata = $empdata;
				
				
		
			//code...
		} catch (\Throwable $th) {
			if ($th->getCode()==45000) {
				$msgarray['isdefault'] = 'Default account already exists';
				$this->view->msgarray = $msgarray;
			
				
			}
		}
		$this->view->msgarray = $msgarray;
		$this->view->form = $empbankaccountForm;
	}

	public function editpopupAction()
	{
		try {
		
		
		$loc = $_SESSION['location'];
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$bankpermission = '';
		$bankpermission = sapp_Global::_checkprivileges(BANK,$loginuserGroup,$loginuserRole,'add');
$this->view->bankpermission = $bankpermission;


			$emptyFlag = 0;

			$userid = $this->getRequest()->getParam('unitId');
			$id = $this->getRequest()->getParam('id');


			if ($userid == '')
				$userid = $loginUserId;

			$empbankaccountForm = new Default_Form_Empbankaccount();


			$empbankaccountForm->id->setValue($id);
			$msgarray = [];


				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
				}
				$userid = $this->getRequest()->getParam('unitId');

				$employeeModal = new Default_Model_Employee();
				$location_model = new Default_Model_Address();

				$isrowexist = $employeeModal->getsingleEmployeeData($userid);
				if ($isrowexist == 'norows')
					$this->view->rowexist = "norows";
				else
					$this->view->rowexist = "rows";




				$empdata = $employeeModal->getActiveEmployeeData($userid);

				if (!empty($empdata)) {

					$bank_model = new Default_Model_Bank();
					$empbankaccount_model = new Default_Model_Empbankaccount();

					$banks = $bank_model->getBanks();
					$empbankaccount = $empbankaccount_model->getEmpSingleBankAccount($id);
					// $locations = $location_model->getLocations();
					// if(!empty($locations)){
					// 	$empbankaccountForm->location->addMultiOption('empty','Select...');
					// 	foreach($locations as $location){
					// 		$empbankaccountForm->location->addMultiOption($location['id'],$location['location']);
					// 	}
					// }else{
					// 	$msgarray['location'] = 'Locations are not configured yet.';
					// }
					$empbankaccountForm->bankname->addMultiOption('', 'Select Bank');
					if (!empty($banks)) {
						foreach ($banks as $bank) {
							$empbankaccountForm->bankname->addMultiOption($bank['bank_id'], $bank['name']);
						}
					} else {
						$msgarray['bankname'] = 'Banks are not configured yet.';
						$emptyFlag++;
					}

					if ($userid) {
						//To display Employee Profile information......
						$usersModel = new Default_Model_Users();
						$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);
					}

					// $accountclasstypeArr = $accountclasstypemodel->getAccountClassTypeList();
					$empbankaccountForm->accountclasstypeid->addMultiOption('', 'Select Account Type');
					$empbankaccountForm->accountclasstypeid->addMultiOption('Saving', 'Saving');
					$empbankaccountForm->accountclasstypeid->addMultiOption('Checking', 'Checking');

					// $empbankaccountForm->isdefault->addMultiOption('', 'Select...');
					// $empbankaccountForm->isdefault->addMultiOption('Y', 'Yes');
					// $empbankaccountForm->isdefault->addMultiOption('N', 'No');
//empbankaccount/editpopup/id/7/unitId/272/popup/1
					 $empbankaccountForm->setAttrib('action', BASE_URL . 'empbankaccount/editpopup/id/' . $id.'/unitId/'.$userid);
					//$empbankaccountForm->setAttrib('action', BASE_URL . 'empbankaccount/editpopup/unitId/' . $userid);

					foreach ($empbankaccount as $data) {

						$empbankaccountForm->bankname->setValue($data['bank_id']);
						$empbankaccountForm->accountclasstypeid->setValue($data['bank_account_type']);
						$empbankaccountForm->accountnumber->setValue($data['accountno']);
						$empbankaccountForm->location->setValue($data['address1'] . "," . $data['address2'] . "," . $data['address2']);
						$empbankaccountForm->bban->setValue($data['BBAN']);
						$empbankaccountForm->iban->setValue($data['IBAN']);
						
						$empbankaccountForm->isdefault->setValue(utlis::getcheckboxvalue($data['isdefault'],'form'));
						$empbankaccountForm->isactive->setValue(utlis::getcheckboxvalue($data['isactive'],'form'));
						$empbankaccountForm->description->setValue($data['description']);
						$this->view->location_id = $data['location_id'];
					}
					
					$this->view->employeedata = $employeeData[0];

					if ($this->getRequest()->getPost()) {

					
						$this->save($empbankaccountForm);
						$msgarray = $this->view->msgarray;
					}
				}
			
				
	
			//code...
		} catch (\Throwable $th) {
			if ($th->getCode()==45000) {
				$msgarray['isdefault'] = 'Default account already exists';
				$this->view->msgarray = $msgarray;
			
				
			}
		}
		$this->view->form = $empbankaccountForm;
		$this->view->msgarray = $msgarray;
		$this->view->empdata = $empdata;
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

		$empbankaccountForm = new Default_Form_Empbankaccount();
		$empbankaccount_model = new Default_Model_Empbankaccount();

		$data = $empbankaccount_model->getEmpSingleBankAccount($id);

		// die(json_encode($data));
		$this->view->data = $data;
		$this->view->form = $empbankaccountForm;
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
								$empbankaccount_model = new Default_Model_Empbankaccount();
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
								$dataTmp = $empbankaccount_model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);

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

	public function save($empbankaccountForm)
	{

		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$user_id = $this->getRequest()->getParam('unitId');

		$id = $this->getRequest()->getParam('id');
		$empbankaccount_model = new Default_Model_Empbankaccount();
		if ($empbankaccountForm->isValid($this->getRequest()->getPost())) {
			$data['bank_id'] = $this->getRequest()->getParam('bankname');
			$data['emp_id'] = $user_id;
			$data['accountno'] = $this->getRequest()->getParam('accountnumber');
			$data['bank_account_type'] = $this->getRequest()->getParam('accountclasstypeid');
			$data['description'] = $this->getRequest()->getParam('description');
			$data['BBAN'] = $this->getRequest()->getParam('bban');
			$data['IBAN'] = $this->getRequest()->getParam('iban');
			$temp_isDefault = $this->getRequest()->getParam('isdefault');
			$isDefault = 'N';
			if ($temp_isDefault == 1) {
				$isDefault = 'Y';
			}
			$data['isdefault'] = $isDefault;
			$data['location_id'] = 	$_SESSION["location"];
			$data['isactive'] = 'Y';

			// $empbankaccount_model->SaveorUpdateBankAccount($data, $where);

			$actionflag = '';
			if ($id != '') {
				$where = ['empbankaccount_id=?' => $id];
				$actionflag = 2;
				$data['modifiedby'] = $loginUserId;
				$data['modifieddate'] = gmdate("Y-m-d H:i:s");
				$data['isactive'] = utlis::getcheckboxvalue($this->getRequest()->getParam('isactive'));
			} else {
				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$data['isactive'] = 'Y';
				$where = '';
				$actionflag = 1;
			}

		
			$Id = $empbankaccount_model->SaveorUpdateBankAccount($data, $where);

			if ($Id == 'update') {
				$_SESSION["location"] = "";
				// $tableid = $id;
				$this->view->successmessage = 'Employee bank account updated successfully.';
			} else {
				$_SESSION["location"] = "";
				$tableid = $Id;
				$this->view->successmessage = 'Employee bank account added successfully.';
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $user_id);
			$this->view->controllername = 'empbankaccount';
			//Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		} else {
			$msgarray = [];
			$messages = $empbankaccountForm->getMessages();
			foreach ($messages as $key1 => $value) {
				foreach ($value as $key2 => $value2) {
					$msgarray[$key1] = $value2;
					break;
				}
			}
			$this->view->msgarray = $msgarray;
		}
	}

	public function deleteAction(){
		$empbankaccount_model = new Default_Model_Empbankaccount();
		$id = $this->getRequest()->getParam('objid');
		$where = 'empbankaccount_id = ' . $id;
		// $employeeleavesModel->SaveorUpdateEmpLeaves($data, $where);
		$empbankaccount_model->deleteEmpBankAccount($where);
		return $this->_helper->json(['flagtype' => 'process', 'message' => 'Deleted']);
	}
}
