<?php
session_start();
class Default_EmpaddressController extends Zend_Controller_Action
{

	public function init()
	{
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';
	}

	public function indexAction()
	{
		if (defined('EMPTABCONFIGS')) {
			$empOrganizationTabs = explode(",", EMPTABCONFIGS);

			if (in_array('emp_address', $empOrganizationTabs)) {
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
								$empaddressmodel = new Default_Model_Empaddress();
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
									$by = 'a.modifieddate';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = array();
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'a.modifieddate';
									if ($dashboardcall == 'Yes')
										$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
									else
										$perPage = $this->_getParam('per_page', PERPAGE);

									$pageNo = $this->_getParam('page', 1);
									$searchData = $this->_getParam('searchData');
									$searchData = rtrim($searchData, ',');
								}
								$dataTmp = $empaddressmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);
							
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
			} else {
				$this->_redirect('error');
			}
		} else {
			$this->_redirect('error');
		}
	}

	public function editAction()
	{

		if (defined('EMPTABCONFIGS')) {
			$empOrganizationTabs = explode(",", EMPTABCONFIGS);

			if (in_array('emp_address', $empOrganizationTabs)) {
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
								$empaddressmodel = new Default_Model_Empaddress();
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
									$by = 'a.modifieddate';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = array();
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'a.modifieddate';
									if ($dashboardcall == 'Yes')
										$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
									else
										$perPage = $this->_getParam('per_page', PERPAGE);

									$pageNo = $this->_getParam('page', 1);
									$searchData = $this->_getParam('searchData');
									$searchData = rtrim($searchData, ',');
								}
								$dataTmp = $empaddressmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit');

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
			} else {
				$this->_redirect('error');
			}
		} else {
			$this->_redirect('error');
		}
	}
	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		try {
			//code...
			$addresstype_model=new Default_Model_Addresstype();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}

		$typepermission = '';
		$typepermission = sapp_Global::_checkprivileges(ADDRESSTYPE,$loginuserGroup,$loginuserRole,'add');
$this->view->typepermission = $typepermission;


		if (defined('EMPTABCONFIGS')) {

			$empOrganizationTabs = explode(",", EMPTABCONFIGS);
			$emptyFlag = 0;

			$userid = $this->getRequest()->getParam('unitId');
		

			$empaddressform = new Default_Form_Empaddress();

			$empaddressform->name->addValidator('NotEmpty', false, array('messages'=>'name is required!'));
			$empaddressform->name->setRequired(true);
			$empaddressform->name->addValidator(new Zend_Validate_Db_NoRecordExists(
				array(
					'table'=>'main_address',
					'field'=>'name'
				)
				));
		  $empaddressform->name->getValidator('Db_NoRecordExists')->setMessage('Name already exists.');
		  $empaddressform->name->addValidator("regex",true,array(
			  'pattern'=>'/^[a-zA-Z.\-?]+$/',
			  'messages'=>array(
				  'regexNotMatch'=>'Name is not valid'
			  )
			  ));	
			// $empbankaccountForm->id->setValue($userid);
			$msgarray = [];

			if (in_array('emp_address', $empOrganizationTabs)) {

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
					$type = $addresstype_model->getaddresstype();

					$empaddressform->address_type_id->addMultiOption('', 'Select Address Type');
					
					$empaddressform->removeElement('company_id');
					$empaddressform->removeElement('project_id');
					$empaddressform->removeElement('section_id');
					$empaddressform->removeElement('sbunit_id');
					$empaddressform->removeElement('department_id');
					$empaddressform->removeElement('division_id');
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
							$empaddressform->address_type_id->addMultiOption($types['address_type_id'], $types['name']);
						}
					} else {
						$msgarray['address_type_id'] = 'Address Type are not configured yet.';
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
					$empaddressform->setAttrib('action', BASE_URL . 'empaddress/addpopup/unitId/' . $userid);
					// $this->view->id = $userid;
					$this->view->employeedata = $employeeData[0];

					if ($this->getRequest()->getPost()) {
						$this->save($empaddressform);
						$msgarray = $this->view->msgarray;
						
					}
				}
				$this->view->empdata = $empdata;
				$this->view->form = $empaddressform;
				$this->view->msgarray = $msgarray;
			} else {
				$this->_redirect('error');
			}
		} else {
			$this->_redirect('error');
		}
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
	}

    public function save($empaddressform)
	{
		$util = new utlis();
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$user_id = $this->getRequest()->getParam('unitId');
	
		$id = $this->getRequest()->getParam('id');
		$empbaddress_model = new Default_Model_Empaddress();
		if ($empaddressform->isValid($this->getRequest()->getPost())) {
			$data = array(
				'location_id' => isset($_SESSION['location'])?$_SESSION['location']:115,
				'ispermanet'=>$util::getcheckboxvalue($this->_getParam('permanent'),'database'),
				'address_type_id' => $this->_request->getParam('address_type_id'),
				'name' =>$this->_request->getParam('name'),
				'description' => $this->_request->getParam('description'),
				'code' =>$this->_request->getParam('code'),
				'iscurrent' => $util::getcheckboxvalue($this->_getParam('iscurrent')),
				'emp_id'=> $user_id = $this->getRequest()->getParam('unitId'),
				'postal'=>$this->_request->getParam('postal'),
				'isactive'=>$util::getcheckboxvalue($this->_getParam('isactive'),'database')
			);
           // $iscuurent=$this->getRequest()->getParam('iscurrent');
//$data['iscurrent'] =$util::getcheckboxvalue($iscuurent);

			// $empbankaccount_model->SaveorUpdateBankAccount($data, $where);

			$actionflag = '';
			if ($id != '') {
				$where = array('address_id=?' => $id);
				$actionflag = 2;
				$data['modifiedby'] = $loginUserId;
				$data['modifieddate'] = gmdate("Y-m-d H:i:s");
			} else {
				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$data['isactive'] = 'Y';
				$where = '';
				$actionflag = 1;
			}
		
			$Id = $empbaddress_model->SaveorUpdateData($data, $where);

			if ($Id == 'update') {
				$_SESSION["location"]="";
				// $tableid = $id;
				$this->view->successmessage = 'Employee Address updated successfully.';
			} else {
				$_SESSION["location"]="";
				$tableid = $Id;
				$this->view->successmessage = 'Employee Address  added successfully.';
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $user_id);
			$this->view->controllername = 'empaddress';

			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		}
		else{
			$msgarray = [];
			$messages = $empaddressform->getMessages();
		
            foreach ($messages as $key1 => $value) {
                foreach ($value as $key2 => $value2) {
                    $msgarray[$key1] = $value2;
                    break;
                }
            }
			$this->view->msgarray = $msgarray;

		}
	}
    public function editpopupAction(){
		try {

		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		
		$typepermission = '';
		$typepermission = sapp_Global::_checkprivileges(ADDRESSTYPE,$loginuserGroup,$loginuserRole,'add');
$this->view->typepermission = $typepermission;


		if (defined('EMPTABCONFIGS')) {

			$empOrganizationTabs = explode(",", EMPTABCONFIGS);
			$emptyFlag = 0;

			$userid = $this->getRequest()->getParam('unitId');
			$id = $this->getRequest()->getParam('id');
		
			if ($userid == '')
				$userid = $loginUserId;

			$empaddressform = new Default_Form_Empaddress();

			$empaddressform->code->removeValidator('Db_NoRecordExists');
			$empaddressform->id->setValue($id);
			$msgarray = [];

			if (in_array('emp_address', $empOrganizationTabs)) {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
				}
				$userid = $this->getRequest()->getParam('unitId');

			
				$location_model = new Default_Model_Address();
				$employeeModal = new Default_Model_Employee();
				$isrowexist = $employeeModal->getsingleEmployeeData($userid);
				if ($isrowexist == 'norows')
					$this->view->rowexist = "norows";
				else
					$this->view->rowexist = "rows";




				$empdata = $employeeModal->getActiveEmployeeData($userid);
				if (!empty($empdata)) {
					$util = new utlis();
					$addresstypemodel = new Default_Model_Addresstype();
					$empaddressmodel = new Default_Model_Empaddress();
					$companymode = new Default_Model_Organisationinfo();
					$busniessunit = new Default_Model_Businessunits();
					$divisionmodel = new Default_Model_division();
					$departmentmodel = new Default_Model_Departments();
					$sectionmodel = new Default_Model_Section();
					$projectmodel = new Default_Model_Project();
					$locationmodel = new Default_Model_Location();
					$prodata = $projectmodel->getprojectlist();
					$secdata = $sectionmodel->getsectionlist();
					$depdata = $departmentmodel->getdepartmentlist();
					$divdata = $divisionmodel->getdevisionlist();
					$budata = $busniessunit->getBusinessUnitsList();
					$companydata =$companymode->getOrganisationInfo(); 
					$type = $addresstypemodel->getaddresstype();
					$empaddress = $empaddressmodel->getsingleempaddressdatas($id);
                    //die(json_encode($empaddress));

					$empaddressform->removeElement('company_id');
					$empaddressform->removeElement('project_id');
					$empaddressform->removeElement('section_id');
					$empaddressform->removeElement('sbunit_id');
					$empaddressform->removeElement('department_id');
					$empaddressform->removeElement('division_id');
					//$empaddressform->addresstype->addMultiOption('', 'Select Address Type');
					
					if (!empty($type)) {

					//	die();
						$empaddressform->address_type_id=$util->populate_select($empaddressform->address_type_id,$type,'address_type_id','name');

					} else {

						$msgarray['address_type_id'] = 'Address Type are not configured yet.';
						$emptyFlag++;
					}

					if ($userid) {
						//To display Employee Profile information......
						$usersModel = new Default_Model_Users();
						$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);

					}


					$empaddressform->setAttrib('action', BASE_URL . 'empaddress/editpopup/unitId/' . $userid);

					$empaddressform->populate($empaddress);

					$locationdata=$locationmodel->getLocationsbyid($empaddress['location_id']);

					$empaddressform->location->setValue(rtrim((isset($locationdata['address1']) ? $locationdata[0]['address1'].',':'').(isset($locationdata[0]['address2']) ? $locationdata[0]['address2'].',':'').(isset($locationdata[0]['address3']) ? $locationdata[0]['address3'].',':''),','));
					$_SESSION['location'] = $empaddress['location_id'];
					$empaddressform->isactive->setValue($util::getcheckboxvalue($empaddress['isactive'],'form'));
					$empaddressform->permanent->setValue($util::getcheckboxvalue($empaddress['ispermanet'],'form'));
					//die(Zend_Debug::dump($locationdata));
					
					/* foreach($empaddress as $data){
						$empaddressform->addresstype->setValue($data['typeid']);
						$empaddressform->name->setValue($data['name']);
						$empaddressform->phone->setValue($data['phone']);
						$empaddressform->location->setValue($data['location']);
						$empaddressform->fax->setValue($data['fax']);
						$empaddressform->iscurrent->setValue($data['iscurrent']);
                        $_SESSION['location']=$data['locationid'];
					} */
					$this->view->employeedata = $employeeData;

					if ($this->getRequest()->getPost()) {
							$this->save($empaddressform);
							$msgarray = $this->view->msgarray;
					}
					
				}
				$this->view->empdata = $empdata;
				$this->view->form = $empaddressform;
				$this->view->msgarray = $msgarray;
			} else {
				$this->_redirect('error');
			}
		} else {
			$this->_redirect('error');
		}
			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function viewAction()
	{
		if (defined('EMPTABCONFIGS')) {
			$empOrganizationTabs = explode(",", EMPTABCONFIGS);

			if (in_array('emp_address', $empOrganizationTabs)) {
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
								$empaddressmodel = new Default_Model_Empaddress();
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
									$by = 'a.modifieddate';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = array();
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'a.modifieddate';
									if ($dashboardcall == 'Yes')
										$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
									else
										$perPage = $this->_getParam('per_page', PERPAGE);

									$pageNo = $this->_getParam('page', 1);
									$searchData = $this->_getParam('searchData');
									$searchData = rtrim($searchData, ',');
								}
								$dataTmp = $empaddressmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);

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
			} else {
				$this->_redirect('error');
			}
		} else {
			$this->_redirect('error');
		}
	}
	public function deleteAction()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$id = $this->_request->getParam('objid');
		$deleteflag = $this->_request->getParam('deleteflag');
		$messages['message'] = '';
		$messages['msgtype'] = "";
		$actionflag = 3;
		if ($id) {
			$model = new Default_Model_Empaddress();

		
			$where = array('address_id=?' => $id);
			$Id = $model->DeleteData($where);
			if ($Id == 'delete') {
				$messages['message'] = 'Address  deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Address  cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Address cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		if ($deleteflag == 1) {
			if ($messages['msgtype'] == 'error') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("error" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
			}
			if ($messages['msgtype'] == 'success') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
			}
		}
		$this->_helper->json($messages);
	} // end of delete action

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

		$empaddressform = new Default_Form_Empaddress();
		$empaddressmodel = new Default_Model_Empaddress();
		
		$data = $empaddressmodel->getparticularaddressData($id);
		
		// die(json_encode($data));
		$this->view->data = $data;
		$this->view->form = $empaddressform;
	}
}
