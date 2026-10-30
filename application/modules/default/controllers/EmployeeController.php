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

class Default_EmployeeController extends Zend_Controller_Action
{

	private $options;
	public function preDispatch()
	{
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('makeactiveinactive', 'json')->initContext();
		$ajaxContext->addActionContext('getemprequi', 'json')->initContext();
		$ajaxContext->addActionContext('getempreportingmanagers', 'html')->initContext();
		$ajaxContext->addActionContext('getindividualempdetails', 'html')->initContext();
		$ajaxContext->addActionContext('getmoreemployees', 'html')->initContext();
	}

	public function init()
	{
		$employeeModel = new Default_Model_Employee();
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';
	}

	public function getemprequiAction()
	{
		$cand_id = $this->_getParam('cand_id', null);
		$cand_model = new Default_Model_Candidatedetails();
		$cand_data = $cand_model->getCandidateById($cand_id);
		$this->_helper->json(array('requi_code' => $cand_data['requisition_code']));
	}


	public function listviewAction()
	{
		try {

			$limit = LIMIT;
			$offset = 0;
			$auth = Zend_Auth::getInstance();
			$popConfigPermission = array();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$auth = Zend_Auth::getInstance();

			$search_val = $this->_request->getParam('search_val', null);

			$search_str = $this->_request->getParam('search_str', null);
			$role_id = $this->_request->getParam('role_id', null);
			$model = new Default_Model_Employeeh();

			$call = $this->_getParam('call');
			if ($call == 'ajaxcall')
				$this->_helper->layout->disableLayout();
			$view = Zend_Layout::getMvcInstance()->getView();
			$objname = $this->_getParam('objname');
			$refresh = $this->_getParam('refresh');
			$data = array();
			$searchQuery = '';
			$searchArray = array();
			$tablecontent = '';
			$dashboardcall = $this->_getParam('dashboardcall');

			if ($refresh == 'refresh') {
				if ($dashboardcall == 'Yes')
					$perPage = DASHBOARD_PERPAGE;
				else
					$perPage = PERPAGE;
				$sort = 'ASC';
				$by = 'e.id';
				$pageNo = 1;
				$searchData = '';
				$searchQuery = '';
				$searchArray = array();
			} else {
				$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'ASC';
				$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'e.id';
				if ($dashboardcall == 'Yes')
					$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
				else $perPage = $this->_getParam('per_page', PERPAGE);
				$pageNo = $this->_getParam('page', 1);
				/** search from grid - START **/
				$searchData = $this->_getParam('searchData');

				/** search from grid - END **/
			}
			$employeeModel = new Default_Model_Employee();






			$access = Zend_Session::namespaceGet('access_control');
			$empids = $access['empids'];
			$perm = $access['perm'];
			$dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '', '', $loginUserId, $limit, $offset, $search_val, $search_str, $role_id, $empids, $perm);
			array_push($data, $dataTmp);
			//
			$this->view->dataArray = $dataTmp;
			$this->view->call = $call;

			$this->view->perm = $perm;

			$this->view->dashboardcall = $dashboardcall;

			$this->view->messages = $this->_helper->flashMessenger->getMessages();
		} catch (\Throwable $th) {
			$this->view->rowexist = "norows";

			Zend_Debug::dump($th->getMessage());
			die(22);
		}
	}

	public function indexAction()
	{

		try {

			$limit = LIMIT;
			$offset = 0;
			$auth = Zend_Auth::getInstance();
			$popConfigPermission = array();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$access = Zend_Session::namespaceGet('access_control');
			$empids = $access['empids'];

			$perm = $access['perm'];

			//Zend_Debug::dump($perm);
			$employeeModel = new Default_Model_Employee();
			//$employeeModel->getempgroup(1);
			// $employeeModel->getsingleEmployeeData(272);

			$currentOrgHead = $employeeModel->getCurrentOrgHead();
			$role_model = new Default_Model_Roles();

			if (!empty($currentOrgHead)) {
				$call = $this->_getParam('call');
				if ($call == 'ajaxcall')
					$this->_helper->layout->disableLayout();

				if (sapp_Global::_checkprivileges(EMPLOYEE, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'employee');
				}

				$this->view->popConfigPermission = $popConfigPermission;

				$data = array();

				$search_val = $this->_request->getParam('search_val', null);
				$search_str = $this->_request->getParam('search_str', null);
				$role_id = $this->_request->getParam('role_id', null);
			} else {

				$this->addorganisationhead($loginUserId);
			}




			// get active roles
			$roles_arr = $role_model->getRolesList('', false, false);
			//die(json_encode($roles_arr));
			$this->view->roles_arr  = $roles_arr;
			//echo "<prE>";print_r($roles_arr);


			/* $dataTmp = $employeeModel->getGrid($sort, $by, $perPage, $pageNo, $searchData,$call,$dashboardcall,$loginUserId); */
			$dataTmp = $employeeModel->getEmployees('', $loginUserId, $limit, $offset, $search_val, $search_str, $role_id, $empids);

			$totalemployees = $employeeModel->getEmployees('', $loginUserId, '', '', $search_val, $search_str, $role_id, $empids);



			$totalcount = count($totalemployees);
			$empcount = count($dataTmp);
			array_push($data, $dataTmp);
			$this->view->perm = $perm;
			$this->view->dataArray = $data;
			$this->view->empcount = $empcount;
			$this->view->totalcount = $totalcount;
			$this->view->limit = $limit;
			$this->view->offset = $offset + $limit;
			$this->view->call = $call;
			$this->view->remainingcount = $totalcount -  $empcount;




			$this->view->messages = $this->_helper->flashMessenger->getMessages();
			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
			die(33);
		}
	}

	//function for view more employees
	public function getmoreemployeesAction()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$access = Zend_Session::namespaceGet('access_control');
		$empids = $access['empids'];
		$perm = $access['perm'];
		$call = $this->_getParam('call');
		$popConfigPermission = array();
		$data = array();
		$limit = $this->_getParam('limit');
		$offset = $this->_getParam('offset');
		$count_remaining = $this->_getParam('count_remaining');
		$flag = $this->_getParam('flag');

		$search_val = $this->_request->getParam('search_val', null);

		$search_str = $this->_request->getParam('search_str', null);

		$role_id = $this->_request->getParam('role_id', null);
		$empflag = $this->_request->getParam('empflag', null);
		$clearflag = $this->_request->getParam('clearflag', null);

		if ($call == 'ajaxcall')
			$this->_helper->layout->disableLayout();

		if (sapp_Global::_checkprivileges(EMPLOYEE, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
			array_push($popConfigPermission, 'employee');
		}
		$this->view->popConfigPermission = $popConfigPermission;

		$employeeModel = new Default_Model_Employee();
		if ($empflag == 'myemployees') {
			$dataTmp = $employeeModel->getEmployees($loginUserId, $loginUserId, $limit, $offset, $search_val, $search_str, $role_id);
			$totalemployees = $employeeModel->getEmployees($loginUserId, $loginUserId, '', '', $search_val, $search_str, $role_id);
			$totalcount = count($totalemployees);
			$empcount = count($dataTmp);
		} else {
			$dataTmp = $employeeModel->getEmployees('', $loginUserId, $limit, $offset, $search_val, $search_str, $role_id, $empids);
			$totalemployees = $employeeModel->getEmployees('', $loginUserId, '', '', $search_val, $search_str, $role_id, $empids);
			$totalcount = count($totalemployees);
			$empcount = count($dataTmp);
		}
		if ($search_val != '' && isset($search_val) && $flag == 0) {
			$this->view->remainingcount = $totalcount -  $empcount;
		} else {
			$this->view->remainingcount = $count_remaining -  $empcount;
		}
		if ($clearflag == 1) {
			$this->view->remainingcount = $totalcount -  $empcount;
		}
		array_push($data, $dataTmp);
		$this->view->call = $call;
		$this->view->dataArray = $data;
		$this->view->empcount = $empcount;
		$this->view->totalcount = $totalcount;
		$this->view->limit = $limit;
		$this->view->empflag = $empflag;
		$this->view->inc_offset = $offset + $limit;
	}

	public function addorganisationhead($loginUserId)
	{
		$user_model = new Default_Model_Usermanagement();
		$role_model = new Default_Model_Roles();
		$prefixModel = new Default_Model_Prefix();
		$locationModel = new Default_Model_Location();
		$titleModel = new Default_Model_Title();


		$identity_code_model = new Default_Model_Identitycodes();
		$jobtitlesModel = new Default_Model_Jobtitles();
		$employeeModal = new Default_Model_Employee();
		$positionsmodel = new Default_Model_Positions();
		$form = new Default_Form_Organisationhead();
		$form->setAttrib('action', BASE_URL . 'employee');
		$msgarray = array();
		$role_data = $role_model->getRolesList_orginfo();
		$flag = 'true';

		//employee id
		$identity_code_model = new Default_Model_Identitycodes();
		$identity_codes = $identity_code_model->getIdentitycodesRecord();
		$emp_identity_code = isset($identity_codes[0]) ? $identity_codes[0]['employee_code'] : "";
		if ($emp_identity_code != '') {
			// $emp_id = $emp_identity_code.str_pad($user_model->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
			//sending identity code instead of employee id
			$emp_id = $emp_identity_code;
		} else {
			$emp_id = '';
			$msgarray['employeeId'] = 'Identity codes are not configured yet.';
		}


		$form->employeeId->setValue($emp_id);

		$form->emprole->addMultiOptions(array('' => 'Select Role') + $role_data);
		if (empty($role_data)) {
			$msgarray['emprole'] = 'Roles are not added yet.';
			$flag = 'false';
		}
		$prefixData = $prefixModel->getPrefixList();

		$form->prefix_id->addMultiOption('', 'Select Prefix');
		if (!empty($prefixData)) {
			foreach ($prefixData as $prefixres) {
				$form->prefix_id->addMultiOption($prefixres['id'], $prefixres['prefix']);
			}
		} else {
			$msgarray['prefix_id'] = 'Prefixes are not configured yet.';
		}

		$locationData = $locationModel->getlocationList();

		$form->location->addMultiOption('', 'Select Location');
		if (!empty($locationData)) {
			foreach ($locationData as $prefixres) {
				$form->location->addMultiOption($prefixres['location_id'], $prefixres['address1'], $prefixres['address2'], $prefixres['address3']);
			}
		} else {
			$msgarray['location'] = 'location are not configured yet.';
		}


		$titleData = $titleModel->gettitleList();
		$form->title_id->addMultiOption('', 'Select Title');
		if (!empty($titleData)) {
			foreach ($titleData as $title) {
				$form->title_id->addMultiOption($title['id'], $title['title']);
			}
		} else {
			$msgarray['title_id'] = 'title are not configured yet.';
		}

		$jobtitleData = $jobtitlesModel->getJobTitleList();
		if (!empty($jobtitleData)) {
			foreach ($jobtitleData as $jobtitleres) {
				$form->jobtitle_id->addMultiOption($jobtitleres['id'], $jobtitleres['jobtitlename']);
			}
		} else {
			$msgarray['jobtitle_id'] = 'Job titles are not configured yet.';
			$msgarray['position_id'] = 'Positions are not configured yet.';
		}

		$form->position_id->addMultiOption('', 'Select a Position');
		if (isset($_POST['jobtitle_id']) && $_POST['jobtitle_id'] != '') {
			$jobtitle_id =  $_POST['jobtitle_id'];
			$positionlistArr = $positionsmodel->getPositionList($jobtitle_id);

			if (sizeof($positionlistArr) > 0) {
				foreach ($positionlistArr as $positionlistres) {
					$form->position_id->addMultiOption($positionlistres['id'], $positionlistres['positionname']);
				}
			}
		}

		if ($this->getRequest()->getPost()) {
			if ($form->isValid($this->_request->getPost())) {
				$emp_id = '';
				$employeeId = $this->_request->getParam('employeeId', null);
				$employeeNumId = trim($this->_getParam('employeeNumId', null));
				//employee id
				$emp_identity_code = isset($identity_codes[0]) ? $identity_codes[0]['employee_code'] : "";
				if ($emp_identity_code != '') {
					// $emp_id = $emp_identity_code.str_pad($usersModel->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
					$emp_id = $emp_identity_code . $employeeNumId;
				} else {
					$msgarray['employeeId'] = 'Identity codes are not configured yet.';
					$flag = 'false';
				}
				//duplicate emp id check
				$where_condition = "";
				if (!empty($id)) {
					$where_condition = " and id != $id";
				}
				$isemployeeidexist = $employeeModal->checkemployeeidexist($emp_id, $where_condition);
				if ($isemployeeidexist) {
					$msgarray['employeeNumId'] = "Employee ID already exists. Please try again.";
					$flag = 'false';
				}

				if ($flag != 'false') {
					$jobtitle_id = $this->_request->getParam('jobtitle_id', null);
					$position_id = $this->_request->getParam('position_id', null);
					$date_of_joining = sapp_Global::change_date($this->_request->getParam('date_of_joining_head', null), 'database');
					$emprole = $this->_request->getParam('emprole', null);
					$emailaddress = $this->_request->getParam('emailaddress', null);
					$emppassword = sapp_Global::generatePassword();
					$first_name = trim($this->_request->getParam('firstname_orghead', null));
					$last_name = trim($this->_request->getParam('lastname_orghead', null));
					$middlename = trim($this->_getParam('middlename_orghead', null));
					//$userfullname = trim($this->_request->getParam('orghead',null));
					$userfullname = $first_name . ' ' . $middlename . ' ' . $last_name;
					$prefix_id = $this->_request->getParam('prefix_id', null);
					$user_id = $this->_request->getParam('user_id', null);

					$user_data = array(
						'emprole' => $emprole,
						'firstname' => $first_name,
						'middlename' => $middlename,
						'lastname' => $last_name,
						'userfullname' => $userfullname,
						'emailaddress' => $emailaddress,
						'jobtitle_id' => $jobtitle_id,
						'emppassword' => md5($emppassword),
						'employeeId' => $emp_id,
						'selecteddate' => $date_of_joining,
						'userstatus' => 'old',
						'modeofentry' => 'Direct',
						'createdby'	=> $loginUserId,
						'createddate' => gmdate("Y-m-d H:i:s"),
						'modifiedby' => $loginUserId,
						'modifieddate' => gmdate("Y-m-d H:i:s"),
						'isactive' => 1,
					);
					$emp_data = array(
						'jobtitle_id' => $jobtitle_id,
						'position_id' => $position_id,
						'prefix_id' => $prefix_id,
						'reporting_manager' => 0,
						'date_of_joining' => $date_of_joining,
						'createdby'	=> $loginUserId,
						'createddate' => gmdate("Y-m-d H:i:s"),
						'modifiedby' => $loginUserId,
						'modifieddate' => gmdate("Y-m-d H:i:s"),
						'isactive' => 1,
						'is_orghead' => 1,
					);
					$org_data = array(
						'modifiedby' => $loginUserId,
						'modifieddate' => gmdate("Y-m-d H:i:s"),
					);

					$user_data['employeeId'] = $emp_id;

					$db = Zend_Db_Table::getDefaultAdapter();
					$db->beginTransaction();
					try {

						$user_id = $user_model->SaveorUpdateUserData($user_data, '');

						$emp_data['user_id'] = $user_id;
						$employeeModal->SaveorUpdateEmployeeData($emp_data, '');

						$tableid = $user_id;
						$actionflag = 1;
						$menuID = EMPLOYEE;
						$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $tableid);
						$db->commit();
						//Start sending mails to Management Employee
						$base_url = 'http://' . $this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
						$view = $this->getHelper('ViewRenderer')->view;
						$this->view->emp_name = $userfullname;
						$this->view->password = $emppassword;
						$this->view->emp_id = $emp_id;
						$this->view->base_url = $base_url;
						$text = $view->render('mailtemplates/newpassword.phtml');
						$options['subject'] = APPLICATION_NAME . ': Login Credentials';
						$options['header'] = 'Greetings from Sentrifugo';
						$options['toEmail'] = $emailaddress;
						$options['toName'] = $userfullname;
						$options['message'] = $text;
						$result = sapp_Global::_sendEmail($options);

						$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Organization head added successfully."));
						$this->_redirect('employee');
					} catch (Exception $e) {
						$db->rollBack();
					}
				} else {
					$messages = $form->getMessages();
					foreach ($messages as $key => $val) {
						foreach ($val as $key2 => $val2) {
							$msgarray[$key] = $val2;
							break;
						}
					}
				}
			} else {
				$messages = $form->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgarray[$key] = $val2;
						break;
					}
				}
			}
		}

		$this->view->form = $form;
		$this->view->msgarray = $msgarray;
		$this->render('addorghead', true);
	}

	public function changeorgheadAction()
	{
		try {
			//code...

			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			if ($loginuserRole == SUPERADMIN || $loginuserGroup == MANAGEMENT_GROUP || $loginuserGroup == HR_GROUP) {
				$org_user_id = (int)$this->getRequest()->getParam('orgid');
				$form = new Default_Form_Organisationheadchange();
				$user_model = new Default_Model_Usermanagement();
				$role_model = new Default_Model_Roles();
				$prefixModel = new Default_Model_Prefix();
				$identity_code_model = new Default_Model_Identitycodes();
				$jobtitlesModel = new Default_Model_Jobtitles();
				$employeeModal = new Default_Model_Employee();
				$positionsmodel = new Default_Model_Positions();
				$msgarray = array();
				$orgheadsData = array();

				$form->setAttrib('action', BASE_URL . 'employee/changeorghead/orgid/' . $org_user_id);
				if ($org_user_id)
					$orgheadsData = $employeeModal->getEmployeesForOrgHead($org_user_id);
				if (empty($orgheadsData)) {
					$msgarray['currentorghead'] = 'There are no employees added to assign to the current organization head.';
				}
				$identity_codes = $identity_code_model->getIdentitycodesRecord();
				$role_data = $role_model->getRolesList_orginfo();

				$flag = 'true';
				$emp_identity_code = isset($identity_codes[0]) ? $identity_codes[0]['employee_code'] : "";
				if ($emp_identity_code != '') {
					// $emp_id = $emp_identity_code.str_pad($user_model->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
					$emp_id = $emp_identity_code;
				} else {
					$emp_id = '';
					$msgarray['employeeId'] = 'Identity codes are not configured yet.';
					$flag = 'false';
				}
				$form->employeeId->setValue($emp_id);

				$form->emprole->addMultiOptions(array('' => 'Select Role') + $role_data);
				if (empty($role_data)) {
					$msgarray['emprole'] = 'Roles are not added yet.';
					$flag = 'false';
				}
				$prefixData = $prefixModel->getPrefixList();

				$form->prefix_id->addMultiOption('', 'Select Prefix');
				if (!empty($prefixData)) {
					foreach ($prefixData as $prefixres) {
						$form->prefix_id->addMultiOption($prefixres['id'], $prefixres['prefix']);
					}
				} else {
					$msgarray['prefix_id'] = 'Prefixes are not configured yet.';
				}

				$jobtitleData = $jobtitlesModel->getJobTitleList();
				if (!empty($jobtitleData)) {
					foreach ($jobtitleData as $jobtitleres) {
						$form->jobtitle_id->addMultiOption($jobtitleres['id'], $jobtitleres['jobtitlename']);
					}
				} else {
					$msgarray['jobtitle_id'] = 'Job titles are not configured yet.';
					$msgarray['position_id'] = 'Positions are not configured yet.';
				}

				$form->position_id->addMultiOption('', 'Select a Position');
				if (isset($_POST['jobtitle_id']) && $_POST['jobtitle_id'] != '') {
					$jobtitle_id =  $_POST['jobtitle_id'];
					$positionlistArr = $positionsmodel->getPositionList($jobtitle_id);

					if (sizeof($positionlistArr) > 0) {
						foreach ($positionlistArr as $positionlistres) {
							$form->position_id->addMultiOption($positionlistres['id'], $positionlistres['positionname']);
						}
					}
				}

				if ($this->getRequest()->getPost()) {
					$orghead_repmangr = $this->_request->getParam('orghead_rep_manger', null);
					$selectedtab = $this->_request->getParam('selectedtab');
					$currentorghead = $this->_request->getParam('currentorghead', null);
					$first_name = trim($this->_request->getParam('firstname_orghead', null));
					$middlename = trim($this->_request->getParam('middle_orghead', null));
					$last_name = trim($this->_request->getParam('lastname_orghead', null));
					$date_of_joining = sapp_Global::change_date($this->_request->getParam('date_of_joining_head', null), 'database');
					$employeeId = $this->_request->getParam('employeeId', null);
					$emprole = $this->_request->getParam('emprole', null);
					$emailaddress = $this->_request->getParam('emailaddress', null);
					$employeeNumId = trim($this->_getParam('employeeNumId', null));
					//employee id
					$emp_identity_code = isset($identity_codes[0]) ? $identity_codes[0]['employee_code'] : "";
					$emp_id = '';
					if ($emp_identity_code != '') {
						// $emp_id = $emp_identity_code.str_pad($usersModel->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
						$emp_id = $emp_identity_code . $employeeNumId;
					} else {
						$msgarray['employeeId'] = 'Identity codes are not configured yet.';
						$flag = 'false';
					}

					if ($orghead_repmangr == '') {
						$msgarray['orghead_rep_manger'] = 'Please select reporting manager to current organisation head.';
						$flag = 'false';
					}
					if ($selectedtab == 1) {
						if ($currentorghead == '') {
							$msgarray['currentorghead'] = 'Please select current organisation head.';
							$flag = 'false';
						}
					} else {
						if ($first_name == '') {
							$msgarray['firstname_orghead'] = 'Please enter first name of organization head.';
							$flag = 'false';
						}
						if ($middlename == '') {
							$msgarray['middlename_orghead'] = 'Please enter middle name of organization head.';
							$flag = 'false';
						}
						if ($last_name == '') {
							$msgarray['lastname_orghead'] = 'Please enter last name of organization head.';
							$flag = 'false';
						}
						if ($employeeId == '') {
							$msgarray['employeeId'] = 'Please enter the Employee Id.';
							$flag = 'false';
						}
						if ($employeeNumId == '') {
							$msgarray['employeeNumId'] = 'Please enter the Employee Id.';
							$flag = 'false';
						}
						if ($emprole == '') {
							$msgarray['emprole'] = 'Please select role.';
							$flag = 'false';
						}
						if ($emailaddress == '') {
							$msgarray['emailaddress'] = 'Please enter email.';
							$flag = 'false';
						}
						if ($date_of_joining == '') {
							$msgarray['date_of_joining_head'] = 'Please select date of joining.';
							$flag = 'false';
						}
						//duplicate emp id check
						$where_condition = "";
						if (!empty($id)) {
							$where_condition = " and id != $id";
						}
						$isemployeeidexist = $employeeModal->checkemployeeidexist($emp_id, $where_condition);
						if ($isemployeeidexist) {
							$msgarray['employeeNumId'] = "Employee ID already exists. Please try again.";
							$flag = 'false';
						}
					}

					if ($form->isValid($this->_request->getPost()) && $flag != 'false') {
						$emppassword = sapp_Global::generatePassword();
						$jobtitle_id = $this->_request->getParam('jobtitle_id', null);
						$position_id = $this->_request->getParam('position_id', null);
						$userfullname = $first_name . ' ' . $middlename . ' ' . $last_name;
						$prefix_id = $this->_request->getParam('prefix_id', null);
						$user_id = $this->_request->getParam('user_id', null);

						$old_org_head = array(
							'reporting_manager' => $orghead_repmangr,
							'modifiedby' => $loginUserId,
							'modifieddate' => gmdate("Y-m-d H:i:s"),
							'is_orghead' => 0,
						);
						$old_org_where = array('user_id=?' => $org_user_id);

						if ($selectedtab == 1) {
							$new_org_head = array(
								'reporting_manager' => 0,
								'modifiedby' => $loginUserId,
								'modifieddate' => gmdate("Y-m-d H:i:s"),
								'is_orghead' => 1,
							);
							$new_org_where = array('user_id=?' => $currentorghead);
						} else {

							$user_data = array(
								'emprole' => $emprole,
								'firstname' => $first_name,
								'middlename' => $middlename,
								'lastname' => $last_name,
								'userfullname' => $userfullname,
								'emailaddress' => $emailaddress,
								'jobtitle_id' => $jobtitle_id,
								'emppassword' => md5($emppassword),
								'employeeId' => $emp_id,
								'selecteddate' => $date_of_joining,
								'userstatus' => 'old',
								'modeofentry' => 'Direct',
								'createdby'	=> $loginUserId,
								'createddate' => gmdate("Y-m-d H:i:s"),
								'modifiedby' => $loginUserId,
								'modifieddate' => gmdate("Y-m-d H:i:s"),
								'isactive' => 1,
							);
							$emp_data = array(
								'location' => $location,
								'jobtitle_id' => $jobtitle_id,
								'position_id' => $position_id,
								'prefix_id' => $prefix_id,
								'reporting_manager' => 0,
								'date_of_joining' => $date_of_joining,
								'createdby'	=> $loginUserId,
								'createddate' => gmdate("Y-m-d H:i:s"),
								'modifiedby' => $loginUserId,
								'modifieddate' => gmdate("Y-m-d H:i:s"),
								'isactive' => 1,
								'is_orghead' => 1,
							);
						}

						/*if($emp_identity_code!='')
						$emp_id = $emp_identity_code.str_pad($user_model->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
					else
					$emp_id = '';*/
						$user_data['employeeId'] = $emp_id;


						$db = Zend_Db_Table::getDefaultAdapter();
						$db->beginTransaction();
						try {
							$employeeModal->SaveorUpdateEmployeeData($old_org_head, $old_org_where);
							if ($selectedtab == 1) {
								$employeeModal->SaveorUpdateEmployeeData($new_org_head, $new_org_where);
							} else {
								$user_id = $user_model->SaveorUpdateUserData($user_data, '');
								$emp_data['user_id'] = $user_id;
								$employeeModal->SaveorUpdateEmployeeData($emp_data, '');
							}

							$tableid = $user_id;
							$actionflag = 1;
							$menuID = EMPLOYEE;
							$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $tableid);
							$db->commit();
							//start of mailing to send username and password for new employee
							if ($selectedtab == 2) {
								$base_url = 'http://' . $this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
								$view = $this->getHelper('ViewRenderer')->view;
								$this->view->emp_name = $userfullname;
								$this->view->password = $emppassword;
								$this->view->emp_id = $emp_id;
								$this->view->base_url = $base_url;
								$text = $view->render('mailtemplates/newpassword.phtml');
								$options['subject'] = APPLICATION_NAME . ': Login Credentials';
								$options['header'] = 'Greetings from Sentrifugo';
								$options['toEmail'] = $emailaddress;
								$options['toName'] = $this->view->emp_name;
								$options['message'] = $text;
								$result = sapp_Global::_sendEmail($options);
							}

							//Sending mails to SuperAdmin,Management Employees
							$mangmntEmployees = $employeeModal->getMngmntEmployees();
							//Preparing Employee array for Bcc
							$empArr = array();
							if (!empty($mangmntEmployees)) {
								$empArrList = '';
								$org_head_name = '';
								foreach ($mangmntEmployees as $emp) {
									array_push($empArr, $emp['emailaddress']);
									if ($selectedtab == 1 && $currentorghead == $emp['user_id']) {
										$org_head_name = $emp['userfullname'];
									}
								}
							}
							$new_org_head_name = ($org_head_name == '') ? $userfullname : $org_head_name;
							$options['subject'] = APPLICATION_NAME . ': New Organizaion Head';
							$options['header'] = 'New organization head';
							$options['toEmail'] = SUPERADMIN_EMAIL;
							$options['toName'] = 'Super Admin';
							$options['bcc'] 	= $empArr;
							$options['message'] =  "<div style='padding: 0; text-align: left; font-size:14px; font-family:Arial, Helvetica, sans-serif;'>				
												<span style='color:#3b3b3b;'>Hi,</span><br />
												<div style='padding:20px 0 0 0;color:#3b3b3b;'><b>$new_org_head_name</b> is our new organization head. </div>
												<div style='padding:20px 0 10px 0;'>Please <a href=" . BASE_URL . " target='_blank' style='color:#b3512f;'>click here</a> to login  to your <b>" . APPLICATION_NAME . "</b> account to check the details.</div>
												</div> ";
							$mail_id =  sapp_Global::_sendEmail($options);
							$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Organization head changed succesfully."));
							$this->_redirect('employee');
						} catch (Exception $e) {
							$db->rollBack();
						}
					} else {
						$messages = $form->getMessages();
						foreach ($messages as $key => $val) {
							foreach ($val as $key2 => $val2) {
								$msgarray[$key] = $val2;
								break;
							}
						}
					}
				}

				$this->view->form = $form;
				$this->view->orgheadsData = $orgheadsData;
				$this->view->msgarray = $msgarray;
			} else {
				$this->_redirect('error');
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
			die(44);
		}
	}

	public function addAction()
	{
		try {
			//code...

			$emptyFlag = 0;
			$report_opt = array();
			$popConfigPermission = array();
			$auth = Zend_Auth::getInstance();
			$candidateid = $this->_request->getParam('cid');
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$employeeModel = new Default_Model_Employee();
			$currentOrgHead = $employeeModel->getCurrentOrgHead();

			if (empty($currentOrgHead)) {
				$this->addorganisationhead($loginUserId);
			} else {

				if (sapp_Global::_checkprivileges(TITLE, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'title');
				}
				if (sapp_Global::_checkprivileges(ETHNICCODE, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'ethniccode');
				}
				if (sapp_Global::_checkprivileges(BRANCH, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'branch');
				}
				if (sapp_Global::_checkprivileges(BRANCH, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'branch');
				}

				if (sapp_Global::_checkprivileges(IDENTITYCODES, $loginuserGroup, $loginuserRole, 'edit') == 'Yes') {
					array_push($popConfigPermission, 'identitycodes');
				}
				if (sapp_Global::_checkprivileges(EMPLOYMENTSTATUS, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'empstatus');
				}
				if (sapp_Global::_checkprivileges(JOBTITLES, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'jobtitles');
				}
				if (sapp_Global::_checkprivileges(POSITIONS, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
					array_push($popConfigPermission, 'position');
				}
				$this->view->popConfigPermission = $popConfigPermission;

				$employeeform = new Default_Form_employee();

				$usersModel = new Default_Model_Users();
				$employmentstatusModel = new Default_Model_Employmentstatus();
				$busineesUnitModel = new Default_Model_Businessunits();
				$user_model = new Default_Model_Usermanagement();
				$candidate_model = new Default_Model_Candidatedetails();
				$role_model = new Default_Model_Roles();
				$entrymodel = new Default_Model_entrymode();
				$jobtitlesModel = new Default_Model_Jobtitles();
				$branchModel = new Default_Model_Branch();
				$titleModel = new Default_Model_Title();
				$util = new utlis();
				$msgarray = array();
				$identity_code_model = new Default_Model_Identitycodes();
				$identity_codes = $identity_code_model->getIdentitycodesRecord();


				$emp_identity_code = isset($identity_codes[0]) ? $identity_codes[0]['employee_code'] : "";

				if ($emp_identity_code != '') {
					// $emp_id = $emp_identity_code.str_pad($user_model->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
					//sending identity code instead of employee id
					$emp_id = $emp_identity_code;
				} else {
					$emp_id = '';
					$msgarray['employeeId'] = 'Identity codes are not configured yet.';
				}
				$lcatdata = $entrymodel->getMode();


				$employeeform->employeeId->setValue($emp_id);
				$employeeform->modeofentry->setValue('Direct');
				$employeeform->modeofentry = $util->populate_select($employeeform->modeofentry, $lcatdata, 'entryid', 'mode', 'select');
				$roles_arr = $role_model->getRolesList();

				if (sizeof($roles_arr) > 0) {
					$employeeform->emprole->addMultiOptions(array('' => 'Select Role') + $roles_arr);
				} else {
					$employeeform->emprole->addMultiOptions(array('' => 'Select Role'));
					$msgarray['emprole'] = 'Roles are not configured yet.';
				}
				$candidate_options = $candidate_model->getCandidatesNamesForUsers();
				if (sizeof($candidate_options) > 0) {
					$employeeform->rccandidatename->addMultiOptions(array('' => 'Select Candidate') + $candidate_options);
				} else {
					$msgarray['rccandidatename'] = 'No candidates.';
				}
				if (!empty($candidateid)) {
					$employeeform->modeofentry->setValue('Interview');
					$cand_data = $candidate_model->getCandidateById($candidateid);

					$employeeform->disp_requi->setValue($cand_data['requisition_code']);
					$employeeform->rccandidatename->setValue($candidateid);
					$this->view->candidateid = $candidateid;
				}
				$referedby_options = $user_model->getRefferedByForUsers();

				if (sizeof($referedby_options) > 0) {
					$employeeform->candidatereferredby->addMultiOptions(array('' => 'Select referred by') + $referedby_options);
				} else {
					$msgarray['candidatereferredby'] = 'Employees are not added yet.';
				}

				$employmentStatusData = $employmentstatusModel->getempstatusActivelist();
				$employeeform->emp_status_id->addMultiOption('', 'Select Employment Status');
				if (!empty($employmentStatusData)) {

					foreach ($employmentStatusData as $employmentStatusres) {
						$employeeform->emp_status_id->addMultiOption($employmentStatusres['workcodename'], $employmentStatusres['statusname']);
					}
				} else {
					$msgarray['emp_status_id'] = 'Employment status is not configured yet.';
					$emptyFlag++;
				}

				$businessunitData = $busineesUnitModel->getDeparmentList();
				if (!empty($businessunitData)) {
					$employeeform->businessunit_id->addMultiOption('0', 'No Business Unit');
					foreach ($businessunitData as $businessunitres) {
						$employeeform->businessunit_id->addMultiOption($businessunitres['id'], $businessunitres['unitname']);
					}

					$departmentsmodel = new Default_Model_Departments();
					$departmentlistArr = $departmentsmodel->getDepartmentList('0');
					$totalDeptList = $departmentsmodel->getTotalDepartmentList();
					$employeeform->department_id->clearMultiOptions();
					$employeeform->department_id->addMultiOption('', 'Select Department');
					if (count($departmentlistArr) > 0) {
						foreach ($departmentlistArr as $departmentlistresult) {
							$employeeform->department_id->addMultiOption($departmentlistresult['id'], utf8_encode($departmentlistresult['deptname']));
						}
					}
					if (empty($totalDeptList)) {
						$msgarray['department_id'] = 'Departments are not added yet.';
					}
					if (isset($department_id) && $department_id != 0 && $department_id != '')
						$employeeform->setDefault('department_id', $department_id);
				} else {
					$msgarray['businessunit_id'] = 'Business units are not added yet.';
					$emptyFlag++;
				}

				$jobtitleData = $jobtitlesModel->getJobTitleList();
				$employeeform->jobtitle_id->addMultiOption('', 'Select Job Title');
				if (!empty($jobtitleData)) {
					foreach ($jobtitleData as $jobtitleres) {
						$employeeform->jobtitle_id->addMultiOption($jobtitleres['id'], $jobtitleres['jobtitlename']);
					}
				} else {
					$msgarray['jobtitle_id'] = 'Job titles are not configured yet.';
					$msgarray['position_id'] = 'Positions are not configured yet.';
					$emptyFlag++;
				}


				//

				$branchData = $branchModel->getbranchList();
				$employeeform->branch_id->addMultiOption('', 'Select barnch');
				if (!empty($branchData)) {
					foreach ($branchData as $branches) {
						$employeeform->branch_id->addMultiOption($branches['emp_branch_id'], $branches['branch']);
					}
				} else {
					$msgarray['branch_id'] = 'location are not configured yet.';
					$emptyFlag++;
				}
				$titleData = $titleModel->gettitleList();
				//die(json_encode($titleData));
				$employeeform->title_id->addMultiOption('', 'Select Title');
				if (!empty($titleData)) {
					foreach ($titleData as $title) {
						$employeeform->title_id->addMultiOption($title['id'], $title['title']);
					}
				} else {
					$msgarray['title_id'] = 'Title are not configured yet.';
					$emptyFlag++;
				}

				//
				if (isset($_POST['emprole']) && isset($_POST['department_id']) && $_POST['emprole'] != '') {
					$role_split = preg_split('/_/', $_POST['emprole']);
					$reportingManagerData = $usersModel->getReportingManagerList_employees($_POST['department_id'], '', $role_split[1]);

					if (!empty($reportingManagerData)) {
						$report_opt = $reportingManagerData;

						if (isset($_POST['reporting_manager']) && $_POST['reporting_manager'] != '')
							$employeeform->setDefault('reporting_manager', $_POST['reporting_manager']);
					}
				}

				$employeeform->setAttrib('action', BASE_URL . 'employee/add');
				$this->view->form = $employeeform;
				$this->view->msgarray = $msgarray;
				$this->view->report_opt = $report_opt;
				$this->view->emptyFlag = $emptyFlag++;
				if ($this->getRequest()->getPost()) {
					$result = $this->save($employeeform);
					$this->view->msgarray = $result;
					$this->view->messages = $result;
				}
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
			die(55);
		}
	}


	public function editAction()
	{

		try {
			//code...
			$popConfigPermission = array();
			$auth = Zend_Auth::getInstance();
			$report_opt = array();
			$role_datap = array();
			$empGroup = "";
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}


			if (sapp_Global::_checkprivileges(BRANCH, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
				array_push($popConfigPermission, 'branch');
			}
			if (sapp_Global::_checkprivileges(TITLE, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
				array_push($popConfigPermission, 'title');
			}
			if (sapp_Global::_checkprivileges(ETHNICCODE, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
				array_push($popConfigPermission, 'ethniccode');
			}
			if (sapp_Global::_checkprivileges(EMPLOYMENTSTATUS, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
				array_push($popConfigPermission, 'empstatus');
			}
			if (sapp_Global::_checkprivileges(JOBTITLES, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
				array_push($popConfigPermission, 'jobtitles');
			}
			if (sapp_Global::_checkprivileges(POSITIONS, $loginuserGroup, $loginuserRole, 'add') == 'Yes') {
				array_push($popConfigPermission, 'position');
			}

			$this->view->popConfigPermission = $popConfigPermission;

			$id = (int)$this->getRequest()->getParam('id');

			$id = abs($id);
			if ($id == '')
				$callval = $this->getRequest()->getParam('call');
			if ($callval == 'ajaxcall')
				$this->_helper->layout->disableLayout();

			$employeeform = new Default_Form_employee();
			$employeeform->branch_id->setAttrib("readonly", "readonly");
			$appEmpRatingsModel = new Default_Model_Appraisalemployeeratings();
			$performanceflag = 'true';
			$ff_flag = 'true';
			$employeeform->date_of_joining->setAttrib("readonly", "readonly");

			try {

				if ($id != '' && is_numeric($id) && $id > 0) {

					//&& $id!=$loginUserId

					$appEmpRatingsData = $appEmpRatingsModel->getSelfAppraisalDataByEmpID($id);

					if (sizeof($appEmpRatingsData) > 0 && $appEmpRatingsData[0]['status'] == 1)
						$performanceflag = 'false';
					$ffEmpRatingsData = $appEmpRatingsModel->getFeedforwardDataByEmpID($id);

					if (sizeof($ffEmpRatingsData) > 0 && $ffEmpRatingsData[0]['status'] == 1)
						$ff_flag = 'false';

					if ($performanceflag == 'true' && $ff_flag == 'true') {

						$employeeModal = new Default_Model_Employee();
						$usersModel = new Default_Model_Users();

						$employmentstatusModel = new Default_Model_Employmentstatus();

						$busineesUnitModel = new Default_Model_Businessunits();

						$deptModel = new Default_Model_Departments();

						$role_model = new Default_Model_Roles();

						$user_model = new Default_Model_Usermanagement();

						$candidate_model = new Default_Model_Candidatedetails();
						$jobtitlesModel = new Default_Model_Jobtitles();

						$positionsmodel = new Default_Model_Positions();
						$branchModel = new Default_Model_Branch();
						$titleModel = new Default_Model_Title();
						$ethniccodeModel = new Default_Model_Ethniccode();
						$phoneno = new Default_Model_Phoneno();
						$employeeposModel = new Default_Model_empposition();

						$data = array();
						$empDeptId = "";
						$empRoleId = "";
						$data = $employeeModal->getsingleEmployeeData($id);
						//die(json_encode($data));
						if ($data == 'norows') {

							$this->view->rowexist = "norows";
						} else if (!empty($data)) {
							$this->view->rowexist = "rows";
							$employeeform->submit->setLabel('Update');
							$data = $data[0];

							/* Earlier code to fetch employee details */
							$employeeData = $employeeModal->getsingleEmployeeData($id);
							$phonenodata = $phoneno->getPhonenobyempid($id);
							$employeeData[0]['contactnumber'] = $phonenodata[0]['phoneno'];
							//die(json_encode($employeeData));	

							if (empty($data['modeofentry'])) $data['modeofentry'] = 'Direct';

							if ($data['is_orghead'] == 1) {

								$roles_arr = $role_model->getRolesList('orghead');
								if (sizeof($roles_arr) > 0) {
									// $employeeform->emprole->addMultiOptions(array(''=>'Select Role')+$roles_arr);							
								}
							} else {

								$roles_arr = $role_model->getRolesList();
								if (sizeof($roles_arr) > 0) {
									// $employeeform->emprole->addMultiOptions(array(''=>'Select Role')+$roles_arr);
								}
							}

							$referedby_options = $user_model->getRefferedByForUsers();

							$employmentStatusData = $employmentstatusModel->getempstatuslist();

							if (sizeof($employmentStatusData) > 0) {
								$employeeform->emp_status_id->addMultiOption('', 'Select Employment Status');
								foreach ($employmentStatusData as $employmentStatusres) {
									$employeeform->emp_status_id->addMultiOption($employmentStatusres['workcodename'], $employmentStatusres['statusname']);
								}
							}


							$jobtitleData = $jobtitlesModel->getJobTitleList();
							if (sizeof($jobtitleData) > 0) {
								$employeeform->jobtitle_id->addMultiOption('', 'Select Job Title');
								foreach ($jobtitleData as $jobtitleres) {
									$employeeform->jobtitle_id->addMultiOption($jobtitleres['id'], $jobtitleres['jobtitlename']);
								}
							}

							$positionlistArr = $positionsmodel->getPositionList($data['jobtitle_id']);

							if (sizeof($positionlistArr) > 0) {

								$employeeform->position_id->addMultiOption('', 'Select Position');
								foreach ($positionlistArr as $positionlistres) {
									$employeeform->position_id->addMultiOption($positionlistres['id'], $positionlistres['positionname']);
								}
							}


							$branchData = $branchModel->getbranchList();
							if (!empty($branchData)) {
								foreach ($branchData as $branches) {
									$employeeform->branch_id->addMultiOption($branches['emp_branch_id'], $branches['branch']);
								}
							}

							$titleData = $titleModel->gettitleList();

							if (!empty($titleData)) {
								foreach ($titleData as $title) {
									$employeeform->title_id->addMultiOption($title['title_id'], $title['title']);
								}
							}

							$ethniccodeData = $ethniccodeModel->getPairs(true);

							if (!empty($ethniccodeData)) {
								$employeeform->ethniccode_id->addMultiOption('', 'Select Ethnic Code');
								foreach ($ethniccodeData as $id => $ethnicname) {
									$employeeform->ethniccode_id->addMultiOption($id, $ethnicname);
								}
							}

							$util = new utlis();

							$entrymodel = new Default_Model_entrymode();

							$lcatdata = $entrymodel->getMode();

							$employeeform->modeofentry = $util->populate_select($employeeform->modeofentry, $lcatdata, 'entryid', 'mode', 'select');
							//die(json_encode($data));
							$employeeform->populate($data);
							//splitting employee id
							$employeeId = !empty($data['employeeId']) ? $data['employeeId'] : '';

							$identity_code_model = new Default_Model_Identitycodes();
							$identity_codes = $identity_code_model->getIdentitycodesRecord();
							//getting the identity code

							$emp_identity_code = isset($identity_codes[0]) ? $identity_codes[0]['employee_code'] : "";
							//getting the numeric id
							$employeeNumId = str_replace($emp_identity_code, '', $employeeId);

							$employeeform->setDefault('employeeId', $emp_identity_code);
							$employeeform->setDefault('employeeNumId', $employeeNumId);
							$employeeform->setDefault('final_emp_id', $employeeId);
							$employeeform->setDefault('user_id', $data['id']);
							$employeeform->setDefault('emp_status_id', $data['emp_status_id']);
							$employeeform->setDefault('businessunit_id', $data['businessunit_id']);
							$employeeform->setDefault('jobtitle_id', $data['jobtitle_id']);
							$employeeform->setDefault('branch_id', $data['branch']);
							$employeeform->setDefault('code', $data['code']);

							$employeeform->setDefault('department_id', $data['department_id']);
							$employeeform->setDefault('position_id', $data['position_id']);

							//$employeeform->setDefault('emprole',$data['emprole']."_".MANAGEMENT_GROUP);
							$employeeform->setDefault('emprole', $data['emprole']);

							$date_of_joining = sapp_Global::change_date($data['date_of_joining'], 'view');
							$employeeform->date_of_joining->setValue($date_of_joining);

							if ($data['date_of_leaving'] != '' && $data['date_of_leaving'] != '0000-00-00') {
								$date_of_leaving = sapp_Global::change_date($data['date_of_leaving'], 'view');
								$employeeform->date_of_leaving->setValue($date_of_leaving);
							}

							if ($data['modeofentry'] != 'Direct') {
								//$employeeform->rccandidatename->setValue($data['userfullname']);
								$employeeform->rccandidatename->addMultiOption($data['rccandidatename'], $data['userfullname']);
							}

							if (sizeof($referedby_options) > 0 && $data['candidatereferredby'] != '' && $data['candidatereferredby'] != 0) {
								$employeeform->candidatereferredby->addMultiOption($referedby_options[$data['candidatereferredby']], $referedby_options[$data['candidatereferredby']]);
								//$employeeform->candidatereferredby->setValue($referedby_options[$data['candidatereferredby']]);
							}

							if ($data['rccandidatename'] != '' && $data['rccandidatename'] != 0) {
								$cand_data = $candidate_model->getCandidateById($data['rccandidatename']);
								$data['requisition_code'] = $cand_data['requisition_code'];
							}



							$employeeform->setAttrib('action', BASE_URL . 'employee/edit/id/' . $id);


							$empDeptId = (isset($_POST['department_id'])) ? $_POST['department_id'] : $data['department_id'];
							if (isset($_POST['emprole']) && $_POST['emprole'] != '') {
								$role_split = preg_split('/_/', $_POST['emprole']);
								$empRoleId = $role_split[0];
							} else {
								$empRoleId = $data['emprole'];
							}

							if (isset($empDeptId) && $empDeptId != '' && isset($empRoleId) && $empRoleId != '') {
								try {
									$role_datap = $role_model->getRoleDataById($empRoleId);
									$reportingManagerData = $usersModel->getReportingManagerList_employees($empDeptId, $data['id'], $role_datap['group_id']);
								} catch (Exception $e) {
									$reportingManagerData = array();
								}
							}

							if (!empty($reportingManagerData)) {
								$report_opt = $reportingManagerData;
							}
							$employeeform->setDefault('reporting_manager', $data['reporting_manager']);
							$this->view->id = $id;
							$this->view->form = $employeeform;
							$this->view->employeedata = (!empty($employeeData)) ? $employeeData[0] : "";
							$this->view->messages = $this->_helper->flashMessenger->getMessages();
							$this->view->data = $data;

							if ($data['is_orghead'] == 1) {
								$employeeform->removeElement('businessunit_id');
								$employeeform->removeElement('reporting_manager');
								$employeeform->removeElement('department_id');
								$employeeform->removeElement('emp_status_id');
								$employeeform->removeElement('date_of_leaving');
								//$employeeform->removeElement('emprole');
							}
						}
						$reporting = $employeeposModel->getReportingManager($id);
						$pos = $employeeposModel->getpos_jobtitle($id);

						//die;
						$this->view->pos = $pos;
						$this->view->reporting = $reporting;
						$this->view->report_opt = $report_opt;
					} else {
						$this->editappraisal($id, $performanceflag, $ff_flag);
					}
				} else {

					$this->view->rowexist = "norows";
				}

				if ($this->getRequest()->getPost()) {

					$result = $this->save($employeeform);
					$this->view->msgarray = $result;
					$employeeform->modeofentry->setValue($data['modeofentry']);
					$employeeform->other_modeofentry->setValue($data['other_modeofentry']);
					if ($data['modeofentry'] != 'Direct') {
						$employeeform->rccandidatename->setValue($data['userfullname']);
					}
					if (sizeof($referedby_options) > 0 && $data['candidatereferredby'] != '' && $data['candidatereferredby'] != 0) {
						$employeeform->candidatereferredby->setValue($referedby_options[$data['candidatereferredby']]);
					}
				}
			} catch (Exception $e) {
				Zend_Debug::dump($e->getMessage());
				$this->view->rowexist = "norows";
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
			die;
		}
		$previlage = sapp_Global::permemp($id);

		$this->view->previlage = $previlage;
	}

	public function editappraisal($id, $performanceflag, $ff_flag)
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$employeeform = new Default_Form_employee();
		try {
			if ($id != '' && $id > 0) {

				$employeeModal = new Default_Model_Employee();
				$usersModel = new Default_Model_Users();
				$employmentstatusModel = new Default_Model_Employmentstatus();
				$busineesUnitModel = new Default_Model_Businessunits();
				$deptModel = new Default_Model_Departments();
				$role_model = new Default_Model_Roles();
				$user_model = new Default_Model_Usermanagement();
				$candidate_model = new Default_Model_Candidatedetails();
				$jobtitlesModel = new Default_Model_Jobtitles();
				$positionsmodel = new Default_Model_Positions();
				$prefix_model = new Default_Model_Prefix();
				$data = $employeeModal->getsingleEmployeeData($id);
				if ($data == 'norows') {
					$this->view->rowexist = "norows";
					$this->view->empdata = "";
				} else if (!empty($data)) {

					$this->view->rowexist = "rows";
					$this->view->empdata = $data;
					$elements = $employeeform->getElements();
					if (count($elements) > 0) {
						foreach ($elements as $key => $element) {
							if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
								$element->setAttrib("disabled", "disabled");
							}
						}
					}
					$employeeform->removeElement("submit");
					$data = $data[0];

					//die();
					$roles_arr = $role_model->getRolesDataByID($data['emprole']);
					if (sizeof($roles_arr) > 0) {
						$employeeform->emprole->addMultiOption($roles_arr[0]['id'] . '_' . $roles_arr[0]['group_id'], utf8_encode($roles_arr[0]['rolename']));
					}
					$prefix_data = array();
					if ($data['prefix_id'] != '' && $data['prefix_id'] != 'null')
						$prefix_data = $prefix_model->getsinglePrefixData($data['prefix_id']);
					if (!empty($prefix_data) && $prefix_data != 'norows') {
						$prefix_data = $prefix_data[0];
						$employeeform->prefix_id->addMultiOption($prefix_data['id'], $prefix_data['prefix']);
					}
					$referedby_options = $user_model->getRefferedByForUsers();

					/* Code for reporting manager dropdown */

					$reportingManagerData = $usersModel->getUserDetailsByID($data['reporting_manager']);
					if (!empty($reportingManagerData)) {
						$employeeform->reporting_manager->addMultiOption($reportingManagerData[0]['id'], $reportingManagerData[0]['userfullname']);
					}

					$employmentStatusData = $employmentstatusModel->getempstatuslist();
					if (sizeof($employmentStatusData) > 0) {
						$employeeform->emp_status_id->addMultiOption('', 'Select a Employment Status');
						foreach ($employmentStatusData as $employmentStatusres) {
							$employeeform->emp_status_id->addMultiOption($employmentStatusres['workcodename'], $employmentStatusres['statusname']);
						}
					}

					$businessunitData = $busineesUnitModel->getDeparmentList();
					if (sizeof($businessunitData) > 0) {
						$employeeform->businessunit_id->addMultiOption('0', 'No Business Unit');
						foreach ($businessunitData as $businessunitres) {
							$employeeform->businessunit_id->addMultiOption($businessunitres['id'], $businessunitres['unitname']);
						}
					}

					$departmentsData = $deptModel->getDepartmentList($data['businessunit_id']);
					if (sizeof($departmentsData) > 0) {
						$employeeform->department_id->addMultiOption('', 'Select a Department');
						foreach ($departmentsData as $departmentsres) {
							$employeeform->department_id->addMultiOption($departmentsres['id'], $departmentsres['deptname']);
						}
					}

					$jobtitleData = $jobtitlesModel->getJobTitleList();
					if (sizeof($jobtitleData) > 0) {
						$employeeform->jobtitle_id->addMultiOption('', 'Select a Job Title');
						foreach ($jobtitleData as $jobtitleres) {
							$employeeform->jobtitle_id->addMultiOption($jobtitleres['id'], $jobtitleres['jobtitlename']);
						}
					}

					$positionlistArr = $positionsmodel->getPositionList($data['jobtitle_id']);
					if (sizeof($positionlistArr) > 0) {
						$employeeform->position_id->addMultiOption('', 'Select a Position');
						foreach ($positionlistArr as $positionlistres) {
							$employeeform->position_id->addMultiOption($positionlistres['id'], $positionlistres['positionname']);
						}
					}

					$employeeform->populate($data);
					$employeeform->setDefault('user_id', $data['user_id']);
					$employeeform->setDefault('emp_status_id', $data['emp_status_id']);
					$employeeform->setDefault('businessunit_id', $data['businessunit_id']);
					$employeeform->setDefault('jobtitle_id', $data['jobtitle_id']);
					$employeeform->setDefault('department_id', $data['department_id']);
					$employeeform->setDefault('position_id', $data['position_id']);
					if ($data['date_of_joining'] != '' && $data['date_of_joining'] != '0000-00-00') {
						$date_of_joining = sapp_Global::change_date($data['date_of_joining'], 'view');
						$employeeform->date_of_joining->setValue($date_of_joining);
					}

					if ($data['date_of_leaving'] != '' && $data['date_of_leaving'] != '0000-00-00') {
						$date_of_leaving = sapp_Global::change_date($data['date_of_leaving'], 'view');
						$employeeform->date_of_leaving->setValue($date_of_leaving);
					}
					if ($data['modeofentry'] != 'Direct') {
						$employeeform->rccandidatename->setValue($data['userfullname']);
					}
					if (sizeof($referedby_options) > 0 && $data['candidatereferredby'] != '' && $data['candidatereferredby'] != 0) {
						$employeeform->candidatereferredby->setValue($referedby_options[$data['candidatereferredby']]);
					}

					$this->view->id = $id;
					$this->view->form = $employeeform;
					$this->view->employeedata = (!empty($data)) ? $data : "";
					$this->view->messages = $this->_helper->flashMessenger->getMessages();
					$this->view->empdata = $data;
					$this->view->performanceflag = $performanceflag;
					$this->view->ff_flag = $ff_flag;
				}
			} else {
				$this->view->rowexist = "norows";
			}
		} catch (Exception $e) {
			$this->view->rowexist = "norows";
		}
		$this->render('editappraisal', true);
	}

	public function viewAction()
	{

		try {
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
			}
			$id = $this->getRequest()->getParam('id');
			$callval = $this->getRequest()->getParam('call');
			if ($callval == 'ajaxcall')
				$this->_helper->layout->disableLayout();
			$objName = 'employee';
			$employeeform = new Default_Form_employee();
			$employeeform->removeElement("submit");
			$elements = $employeeform->getElements();

			if (count($elements) > 0) {
				foreach ($elements as $key => $element) {
					if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
						$element->setAttrib("disabled", "disabled");
					}
				}
			}
			try {

				if ($id && is_numeric($id) && $id > 0) {
					$employeeModal = new Default_Model_Employee();
					$usersModel = new Default_Model_Users();
					$employmentstatusModel = new Default_Model_Employmentstatus();
					$busineesUnitModel = new Default_Model_Businessunits();
					$deptModel = new Default_Model_Departments();
					$role_model = new Default_Model_Roles();
					$user_model = new Default_Model_Usermanagement();
					$candidate_model = new Default_Model_Candidatedetails();
					$jobtitlesModel = new Default_Model_Jobtitles();
					$positionsmodel = new Default_Model_Positions();
					$branchModel = new Default_Model_Branch();
					$titleModel = new Default_Model_Title();
					$phoneno = new Default_Model_Phoneno();
					$data = array();

					$data = $employeeModal->getsingleEmployeeData($id);
					$empdata = $employeeModal->getActiveEmployeeData($id);


					if ($data == 'norows') {
						$this->view->rowexist = "norows";
					} else if (!empty($data)) {
						$this->view->rowexist = "rows";

						$data = $data[0];

						$employeeData = $usersModel->getUserDetailsByIDandFlag($data['id']);
						$phonenodata = $phoneno->getPhonenobyempid($id);
						$employeeData[0]['contactnumber'] = $phonenodata[0]['phoneno'];

						//$data['emprole'] = 1;
						//$roles_arr = $role_model->getRolesDataByID($data['emprole']);

						/* if(sizeof($roles_arr) > 0)
					{
						$employeeform->emprole->addMultiOption($roles_arr[0]['id'].'_'.$roles_arr[0]['group_id'],utf8_encode($roles_arr[0]['rolename']));
						$data['emprole']=$roles_arr[0]['rolename'];
						
						//for reporting managers
					  $reportingManagerData = $usersModel->getReportingManagerList_employees($data['department_id'],$data['id'],$roles_arr[0]['group_id']);
						if(!empty($reportingManagerData))
						{
							$employeeform->reporting_manager->addMultiOption('','Select Reporting Manager');						

							foreach ($reportingManagerData as $reportingManagerres){
								$employeeform->reporting_manager->addMultiOption($reportingManagerres['id'],$reportingManagerres['name']);
							}
						}
						$employeeform->setDefault('reporting_manager',$data['reporting_manager']);
						
					}
					else
					{
						$data['emprole']="";
						
					} */

						$referedby_options = $user_model->getRefferedByForUsers();

						$employmentStatusData = $employmentstatusModel->getempstatuslist();
						if (sizeof($employmentStatusData) > 0) {
							$employeeform->emp_status_id->addMultiOption('', 'Select Employment Status');
							foreach ($employmentStatusData as $employmentStatusres) {
								$employeeform->emp_status_id->addMultiOption($employmentStatusres['workcodename'], $employmentStatusres['statusname']);
							}
						}


						if (isset($data['branch']) && $data['branch'] != '') {


							$singlelbranchArr = $branchModel->getsinglebranchData($data['branch_id']);

							if ($singlelbranchArr != 'norows') {
								$employeeform->branch_id->addMultiOption($singlelbranchArr[0]['emp_branch_id'], $singlelbranchArr[0]['branch']);

								$data['branch'] = $singlelbranchArr[0]['branch'];
							} else {
								$data['branch'] = "";
							}
						}
						if (isset($data['title']) && $data['title'] != '') {


							$singletitleArr = $titleModel->getsingletitleData($data['title']);

							if ($singletitleArr != 'norows') {
								$employeeform->title_id->addMultiOption($singletitleArr[0]['id'], $singletitleArr[0]['title']);

								$data['title'] = $singletitleArr[0]['title'];
							} else {
								$data['title'] = "";
							}
						}




						$employeeform->populate($data);

						//splitting employee id
						$employeeId = !empty($data['employeeId']) ? $data['employeeId'] : '';
						$identity_code_model = new Default_Model_Identitycodes();
						$identity_codes = $identity_code_model->getIdentitycodesRecord();
						//getting the identity code
						$emp_identity_code = isset($identity_codes[0]) ? $identity_codes[0]['employee_code'] : "";
						//getting the numeric id
						$employeeNumId = str_replace($emp_identity_code, '', $employeeId);

						$employeeform->setDefault('employeeId', $emp_identity_code);
						$employeeform->setDefault('employeeNumId', $employeeNumId);
						$employeeform->setDefault('user_id', $data['user_id']);
						$employeeform->setDefault('emp_status_id', $data['emp_status_id']);
						$employeeform->setDefault('businessunit_id', $data['businessunit_id']);
						$employeeform->setDefault('jobtitle_id', $data['jobtitle_id']);
						$employeeform->setDefault('department_id', $data['department_id']);
						$employeeform->setDefault('position_id', $data['position_id']);

						$date_of_joining = sapp_Global::change_date($data['date_of_joining'], 'view');
						$employeeform->date_of_joining->setValue($date_of_joining);

						if ($data['date_of_leaving'] != '' && $data['date_of_leaving'] != '0000-00-00') {

							$date_of_leaving = sapp_Global::change_date($data['date_of_leaving'], 'view');
							$employeeform->date_of_leaving->setValue($date_of_leaving);
						}
						if ($data['modeofentry'] != 'Direct') {
							$employeeform->rccandidatename->setValue($data['userfullname']);
						}
						if (sizeof($referedby_options) > 0 && $data['candidatereferredby'] != '' && $data['candidatereferredby'] != 0) {
							$employeeform->candidatereferredby->setValue($referedby_options[$data['candidatereferredby']]);
						}
						if ($data['rccandidatename'] != '' && $data['rccandidatename'] != 0) {
							$cand_data = $candidate_model->getCandidateById($data['rccandidatename']);
							if (!empty($cand_data)) {
								$data['requisition_code'] = $cand_data['requisition_code'];
							} else {
								$data['requisition_code'] = "";
							}
						}

						/* overwriting business unitname */
						if (!empty($data['businessunit_id'])) {
							$buname = $busineesUnitModel->getSingleUnitData($data['businessunit_id']);

							if (!empty($buname)) {
								$data['businessunit_id'] = $buname['unitname'];
							} else {
								$data['businessunit_id'] = "";
							}
						}
						if (!empty($data['department_id'])) {
							$depname = $deptModel->getSingleDepartmentData($data['department_id']);

							if (!empty($depname)) {
								$data['department_id'] = $depname['deptname'];
							} else {
								$data['department_id']  = "";
							}
						}
						if (!empty($data['jobtitle_id'])) {

							$jobname = $jobtitlesModel->getsingleJobTitleData($data['jobtitle_id']);

							if (!empty($jobname) && $jobname != 'norows') {

								$data['jobtitle_id'] = $jobname[0]['jobtitlename'];
							} else {
								$data['jobtitle_id'] = "";
							}
						} else {
							$data['jobtitle_id'] = "";
						}

						if (!empty($data['position_id'])) {

							$nameofposition = $positionsmodel->getsinglePositionData($data['position_id']);
							if (!empty($nameofposition) && $nameofposition != 'norows') {

								$data['position_id'] = $nameofposition[0]['positionname'];
							} else {
								$data['position_id'] = "";
							}
						} else {
							$data['position_id'] = "";
						}

						if (!empty($data['emp_status_id'])) {
							$employmentStatusValue = $employmentstatusModel->getParticularStatusName($data['emp_status_id']);

							if (!empty($employmentStatusValue)) {

								$data['emp_status_id'] = $employmentStatusValue[0]['employemnt_status'];
							} else {
								$data['emp_status_id'] = "";
							}
						}



						//die(json_encode($empdata));
						$employeeform->setAttrib('action', BASE_URL . 'employee/edit/id/' . $id);
						$this->view->id = $id;
						$this->view->form = $employeeform;
						$this->view->employeedata = (!empty($employeeData)) ? $employeeData[0] : "";
						$this->view->messages = $this->_helper->flashMessenger->getMessages();
						$this->view->data = $data;
						$this->view->empdata = $empdata;
						$this->view->controllername = $objName;
						$this->view->id = $id;
					}
				} else {
					$this->view->rowexist = "norows";
				}
			} catch (Exception $e) {
				Zend_Debug::dump($e->getMessage());
				$this->view->rowexist = "norows";
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}


	/**
	 * Saves or updates employee details based on the request parameters.
	 * @param Zend_Form $employeeform The form instance for validation
	 * @return void|array Redirects on success, returns error array on failure
	 */
	public function save($employeeform)
	{
		$request = $this->getRequest();

		// If not POST or if the form fails validation
		if (!$request->isPost() || !$employeeform->isValid($request->getPost())) {
			return $this->_handleFormErrors($employeeform);
		}

		// 1. Initialize Models and Basic Data
		$models = $this->_initModels();
		$auth = Zend_Auth::getInstance();
		$loginUserId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : null;

		$params = $request->getParams();
		$id = $params['id'] ?? null;
		$dateOfJoining = sapp_Global::change_date($params['date_of_joining'], 'database');

		// 2. Pre-save Validations
		$validationErrors = $this->_validateEmployeeData($params, $dateOfJoining, $models);
		if (!empty($validationErrors)) {
			return $validationErrors;
		}

		$db = Zend_Db_Table::getDefaultAdapter();
		$db->beginTransaction();

		try {
			// 3. Prepare Data Arrays
			$isUpdate = !empty($id);
			$userData = $this->_prepareUserData($params, $loginUserId, $isUpdate);
			$employeeData = $this->_prepareEmployeeData($params, $dateOfJoining, $loginUserId, $isUpdate);

			// 4. Persistence logic
			if ($isUpdate) {
				$this->_updateExistingEmployee($id, $params['user_id'], $userData, $employeeData, $models);
				$tableId = $id;
				$msg = "Employee details updated successfully.";
				$actionFlag = 2;
			} else {
				$tableId = $this->_createNewEmployee($userData, $employeeData, $models);
				$msg = "Employee details added successfully.";
				$actionFlag = 1;

				// Send Welcome Email for new employees
				$this->_sendWelcomeEmail($params, $userData['emppassword_plain'], $params['employeeNumId']);
			}

			// 5. Post-save logic (Requisitions & Candidates)
			$this->_handleRecruitmentLogic($params, $models, $loginUserId);

			// 6. Finalize
			sapp_Global::logManager(EMPLOYEE, $actionFlag, $loginUserId, $tableId);
			$db->commit();

			$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => $msg));

			if ($isUpdate) {
				$this->_sendUpdateNotification($params);
				$this->_redirect('employee/edit/id/' . $id);
			}
			$this->_redirect('employee/');
		} catch (Exception $e) {
			$db->rollBack();
			return $this->_handleException($e, $params['employeeNumId'] ?? '');
		}
	}

	/** 
	 * Helper to initialize all required models
	 * 
	 * @return object Collection of model instances 
	 */
	protected function _initModels()
	{
		return (object)[
			'users'         => new Default_Model_Usermanagement(),
			'employee'      => new Default_Model_Employee(),
			'requisition'   => new Default_Model_Requisition(),
			'candidate'     => new Default_Model_Candidatedetails(),
			'orgInfo'       => new Default_Model_Organisationinfo(),
			'identity'      => new Default_Model_Identitycodes(),
			'reqHistory'    => new Default_Model_Requesthistory()
		];
	}

	/**
	 * Validates business logic before DB transaction
	 * 
	 * @param array $params Request parameters
	 * @param string $dateOfJoining Date of Joining in 'Y-m-d' format
	 * @param object $models Collection of model instances
	 * @return array Associative array of validation errors
	 */
	protected function _validateEmployeeData($params, $dateOfJoining, $models)
	{
		$errors = [];
		$id = $params['id'] ?? null;

		// Check Identity Codes
		$identityCodes = $models->identity->getIdentitycodesRecord();
		$empCodePrefix = $identityCodes[0]['employee_code'] ?? "";

		if (empty($empCodePrefix)) {
			$errors['employeeId'] = 'Identity codes are not configured yet.';
			return $errors;
		}

		// Duplicate Check
		$fullEmpId = $empCodePrefix . $params['employeeNumId'];
		$where = $id ? " and id != $id" : "";
		if ($models->employee->checkemployeeidexist($fullEmpId, $where)) {
			$errors['employeeNumId'] = "Employee ID already exists. Please try again.";
		}

		// Date Validation
		if ($models->orgInfo->validateEmployeeJoiningDate($dateOfJoining, '', '')) {
			$errors['date_of_joining'] = 'Employee joining date should be greater than organization start date.';
		}

		return $errors;
	}


	/**
	 * Processes form validation errors and re-populates dependent multi-options 
	 * (Departments and Positions) so they are available when the form re-renders.
	 *
	 * @param Zend_Form $employeeform
	 * @return array The list of error messages
	 */
	protected function _handleFormErrors($employeeform)
	{
		$msgarray = [];
		$request = $this->getRequest();

		// 1. Extract the first error message for each invalid field
		$messages = $employeeform->getMessages();
		foreach ($messages as $field => $errors) {
			// We only take the first error for each field to keep the UI clean
			$msgarray[$field] = current($errors);
		}

		// 2. Re-populate Dependent Dropdown: Departments
		// If a Business Unit was selected, we must fetch its Departments
		$businessunit_id = $request->getParam('businessunit_id');
		if (!empty($businessunit_id)) {
			$deptModel = new Default_Model_Departments();
			$departments = $deptModel->getDepartmentList($businessunit_id);

			$employeeform->department_id->clearMultiOptions();
			$employeeform->reporting_manager->clearMultiOptions(); // Clear reporting mgr if applicable
			$employeeform->department_id->addMultiOption('', 'Select Department');

			foreach ($departments as $dept) {
				$employeeform->department_id->addMultiOption(
					$dept['id'],
					utf8_encode($dept['deptname'])
				);
			}

			// Keep the user's previous selection
			$dept_id = $request->getParam('department_id');
			if (!empty($dept_id)) {
				$employeeform->setDefault('department_id', $dept_id);
			}
		}

		// 3. Re-populate Dependent Dropdown: Positions
		// If a Job Title was selected, we must fetch its Positions
		$jobtitle_id = $request->getParam('jobtitle_id');
		if (!empty($jobtitle_id)) {
			$posModel = new Default_Model_Positions();
			$positions = $posModel->getPositionList($jobtitle_id);

			$employeeform->position_id->clearMultiOptions();
			$employeeform->position_id->addMultiOption('', 'Select Position');

			foreach ($positions as $pos) {
				$employeeform->position_id->addMultiOption(
					$pos['id'],
					utf8_encode($pos['positionname'])
				);
			}

			// Keep the user's previous selection
			$pos_id = $request->getParam('position_id');
			if (!empty($pos_id)) {
				$employeeform->setDefault('position_id', $pos_id);
			}
		}

		return $msgarray;
	}

	/**
	 * Prepares the data array for the User Management (users) table
	 *
	 * @param array $params The request parameters
	 * @param int $loginUserId Currently logged in user ID
	 * @param bool $isUpdate Whether we are updating an existing record
	 * @return array Prepared data array for the users table
	 */
	protected function _prepareUserData($params, $loginUserId, $isUpdate)
	{
		// 1. Parse Role (Incoming format: "roleid_groupid")
		$emproleStr = '';
		if (!empty($params['emprole'])) {
			$roleParts = explode('_', $params['emprole']);
			$emproleStr = $roleParts[0];
		}

		// 2. Handle Name and Candidate Logic
		$firstname  = trim($params['firstname'] ?? '');
		$middlename = trim($params['middlename'] ?? '');
		$lastname   = trim($params['lastname'] ?? '');
		$fullName   = trim("$firstname $middlename $lastname");

		$modeOfEntry = $params['modeofentry'] ?? ($params['hid_modeofentry'] ?? '');
		$isDirect    = ($modeOfEntry === 'Direct');

		// The original code uses dynamic keys for candidate mapping
		$candidateKey   = $isDirect ? 'userfullname' : 'rccandidatename';
		$candidateValue = $isDirect ? $fullName : ($params['rccandidatename'] ?? '');

		// 3. Base Data (Common to Update and Insert)
		$userData = [
			'emprole'      => $emproleStr,
			'firstname'    => !empty($firstname) ? $firstname : null,
			'middlename'   => !empty($middlename) ? $middlename : null,
			'lastname'     => !empty($lastname) ? $lastname : null,
			'emailaddress' => $params['emailaddress'] ?? null,
			'jobtitle_id'  => $params['jobtitle_id'] ?? null,
			'branch'       => $params['branch_id'] ?? null,
			'modifiedby'   => $loginUserId,
			'modifieddate' => gmdate("Y-m-d H:i:s"),
			$candidateKey  => $candidateValue,
		];

		if ($isUpdate) {
			// For updates, we target the specific ID
			$userData['empid'] = $params['id'];
			// Note: password, modeofentry, etc., are excluded from updates in original logic
		} else {
			// 4. Logic for New Employees Only
			$plainPassword = sapp_Global::generatePassword();

			$userData['emppassword']       = password_hash($plainPassword, PASSWORD_DEFAULT);
			$userData['emppassword_plain'] = $plainPassword; // Passed back so save() can send the email
			$userData['modeofentry']       = $modeOfEntry;
			$userData['other_modeofentry'] = $params['other_modeofentry'] ?? null;
			$userData['candidatereferredby'] = $params['candidatereferredby'] ?? null;
			$userData['userstatus']        = 'old';
			$userData['createdby']         = $loginUserId;
			$userData['createddate']       = gmdate("Y-m-d H:i:s");
			$userData['isactive']          = 1;

			// If hired through candidate module, use the temporary name provided in the request
			if (!$isDirect) {
				$userData['userfullname'] = $params['tmp_emp_name'] ?? $fullName;
			}
		}

		return $userData;
	}

	/**
	 * Prepares the massive data array for the Employee Model
	 * 
	 * @param array $params The request parameters
	 * @param string $dateOfJoining Date of Joining in 'Y-m-d' format
	 * @param int $loginUserId Currently logged in user ID
	 * @param bool $isUpdate Whether we are updating an existing record
	 * @return array Prepared data array for the employee table
	 */
	protected function _prepareEmployeeData($params, $dateOfJoining, $loginUserId, $isUpdate)
	{
		$data = [
			'reporting_manager'   => $params['reporting_manager'] ?? null,
			'emp_status_id'       => $params['emp_status_id'] ?? null,
			'branch'              => $params['branch_id'] ?? null,
			'firstname'           => !empty($params['firstname']) ? trim($params['firstname']) : null,
			'middlename'          => !empty($params['middlename']) ? trim($params['middlename']) : null,
			'lastname'            => !empty($params['lastname']) ? trim($params['lastname']) : null,
			'emailaddress'        => $params['emailaddress'] ?? null,
			'userfullname'        => trim(($params['firstname'] ?? '') . ' ' . ($params['middlename'] ?? '') . ' ' . ($params['lastname'] ?? '')),
			'extension_number'    => $params['extension_number'] ?: null,
			'office_number'       => $params['office_number'] ?: null,
			'office_faxnumber'    => $params['office_faxnumber'] ?: null,
			'date_of_joining'     => $dateOfJoining,
			'date_of_leaving'     => !empty($params['date_of_leaving']) ? sapp_Global::change_date($params['date_of_leaving'], 'database') : null,
			'years_exp'           => $params['years_exp'] ?: null,
			'modifiedby'          => $loginUserId,
			'modifieddate'        => gmdate("Y-m-d H:i:s")
		];

		if (!$isUpdate) {
			$data['createdby'] = $loginUserId;
			$data['createddate'] = gmdate("Y-m-d H:i:s");
			$data['isactive'] = 1;
			$data['modeofentry'] = $params['modeofentry'] ?? '';
			$data['other_modeofentry'] = $params['other_modeofentry'] ?? '';
			$data['candidatereferredby'] = $params['candidatereferredby'] ?? null;
		}

		return $data;
	}

	/**
	 * Handles the update logic for an existing employee across multiple (User, Employee, and Request History) tables.
	 *
	 * @param int $id The Employee ID
	 * @param int $userId The User/Login ID
	 * @param array $userData Data for the users table
	 * @param array $employeeData Data for the employee table
	 * @param object $models Collection of model instances
	 * @return void
	 */
	protected function _updateExistingEmployee($id, $userId, $userData, $employeeData, $models)
	{
		// 1. Business Logic: Org Heads cannot have a reporting manager in this context
		$existingRecord = $models->employee->getsingleEmployeeData($id);
		if (!empty($existingRecord) && $existingRecord[0]['is_orghead'] == 1) {
			$employeeData['reporting_manager'] = 0;
		}

		// 2. Update the main Employee record
		$empWhere = array('id = ?' => $id);
		$models->employee->SaveorUpdateEmployeeData($employeeData, $empWhere);

		// 3. Update the User/Login record
		$userWhere = array('id = ?' => $userId);
		$models->users->SaveorUpdateUserData($userData, $userWhere);

		// 4. Synchronize status and "isactive" flags across tables
		$this->_handleStatusChange($userId, $employeeData['emp_status_id'], $models);

		// 5. Update Request History with the updated full name
		// Fetching the fresh user data to ensure we have the correct concatenated name
		$updatedUser = $models->users->getUserDataById($id);
		if ($updatedUser) {
			$historyData = ['emp_name' => $updatedUser['userfullname']];
			$historyWhere = "emp_id = " . (int)$userId;
			$models->reqHistory->SaveorUpdateRhistory($historyData, $historyWhere);
		}
	}

	/**
	 * Handles the logic for the 'isactive' status based on the employee status ID.
	 * Maps specific employment statuses (Resigned, Terminated, etc.) to system access states.
	 * 
	 * @param int $userId The User/Login ID
	 * @param int $empStatusId The Employee Status ID
	 * @param object $models Collection of model instances
	 * @return void
	 */
	protected function _handleStatusChange($userId, $empStatusId, $models)
	{
		// Sentrifugo specific status mapping (8, 9, 10 are usually Resigned/Terminated/Retired)
		$statusMapping = [
			8  => 2,
			9  => 3,
			10 => 4
		];

		$userStatusData = [];
		$userIdInt = (int)$userId;

		if (isset($statusMapping[$empStatusId])) {
			// Map to the specific inactive state
			$userStatusData = ['isactive' => $statusMapping[$empStatusId]];
		} else {
			// If not a termination status, check if the user account is locked
			$userData = $models->users->getUserDataById($userIdInt);
			if ($userData && $userData['isactive'] != 0) {
				// If the temporary lock is on, set isactive to 0, otherwise 1 (Active)
				$isActive = ($userData['emptemplock'] == 1) ? 0 : 1;
				$userStatusData = ['isactive' => $isActive];
			}
		}

		if (!empty($userStatusData)) {
			$where = array('id = ?' => $userIdInt);
			$models->users->SaveorUpdateUserData($userStatusData, $where);

			// Ensure the employee table is in sync with the user table state
			$models->employee->SaveorUpdateEmployeeData($userStatusData, "id = " . $userIdInt);
		}
	}

	/**
	 * Handles the creation of a new user and employee record.
	 * 
	 * @param array $userData Prepared data for the users table
	 * @param array $employeeData Prepared data for the employee table
	 * @param object $models Model collection
	 * @return int The ID of the newly created employee
	 */
	protected function _createNewEmployee($userData, $employeeData, $models)
	{

		// 1. Create the Employee record first to get the shared ID
		// Passing an empty string/null for the second parameter triggers an INSERT in these models
		$employeeId = $models->employee->SaveorUpdateEmployeeData($employeeData, "");

		if (!$employeeId) {
			throw new Exception("Failed to create employee record.");
		}

		// 2. Synchronize the employee ID to the user data
		$userData['empid'] = $employeeId;

		// 3. Create the User record.
		// In Sentrifugo, the 'employee' table usually acts as the primary identity
		$userId = $models->users->SaveorUpdateUserData($userData);

		if (!$userId) {
			throw new Exception("Failed to create user record.");
		}

		// If the model returns the ID or 'success', ensure we return the numeric ID
		return is_numeric($employeeId) ? $employeeId : null;
	}

	/**
	 * Sends the welcome email with login credentials to the new employee.
	 * 
	 * @param array $params Request parameters
	 * @param string $plainPassword The unhashed password generated for the user
	 * @param string $employeeNumId The numeric part of the Employee ID
	 * @return bool True on success, false on failure
	 */
	protected function _sendWelcomeEmail($params, $plainPassword, $employeeNumId)
	{
		$request = $this->getRequest();
		$view = $this->getHelper('ViewRenderer')->view;

		// Get Identity Code Prefix (e.g., 'EMP')
		$identityCodes = new Default_Model_Identitycodes();
		$codes = $identityCodes->getIdentitycodesRecord();
		$prefix = isset($codes[0]) ? $codes[0]['employee_code'] : "";

		// Prepare view variables for the email template
		$view->emp_name = trim(($params['firstname'] ?? '') . ' ' . ($params['lastname'] ?? ''));
		$view->password = $plainPassword;
		$view->emp_id   = $prefix . $employeeNumId;
		$view->base_url = 'http://' . $request->getHttpHost() . $request->getBaseUrl();

		// Render the specific phtml template
		$emailBody = $view->render('mailtemplates/newpassword.phtml');

		$options = [
			'subject'  => APPLICATION_NAME . ': Login Credentials',
			'header'   => 'Greetings from Sentrifugo',
			'toEmail'  => $params['emailaddress'],
			'toName'   => $view->emp_name,
			'message'  => $emailBody
		];

		// Dispatch via Sentrifugo's global mail helper
		return sapp_Global::_sendEmail($options);
	}

	/**
	 * Logic to update recruitment records if the employee was hired via a Requisition
	 * 
	 * @param array $params The request parameters containing email and name
	 * @param object $models Collection of model instances
	 * @param int $loginUserId Currently logged in user ID
	 * @return void
	 */
	protected function _handleRecruitmentLogic($params, $models, $loginUserId)
	{
		$modeOfEntry = $params['modeofentry'] ?? ($params['hid_modeofentry'] ?? '');
		$candidateId = $params['rccandidatename'] ?? null;

		if ($modeOfEntry !== 'Direct' && !empty($candidateId)) {
			// 1. Mark candidate as Recruited
			$models->candidate->SaveorUpdateCandidateData(
				['cand_status' => 'Recruited', 'modifieddate' => gmdate("Y-m-d H:i:s")],
				"id = " . (int)$candidateId
			);

			// 2. Increment filled positions in Requisition
			$candData = $models->candidate->getCandidateById($candidateId);
			if ($candData && !empty($candData['requisition_id'])) {
				$reqId = $candData['requisition_id'];
				$reqData = $models->requisition->incrementfilledpositions($reqId);

				// 3. If requisition is now full, close it
				if ($reqData['req_no_positions'] == $reqData['filled_positions']) {
					$closeData = [
						'req_status' => '6', // Status 6 = Closed
						'modifiedby' => $loginUserId,
						'modifiedon' => gmdate("Y-m-d H:i:s")
					];
					$models->requisition->SaveorUpdateRequisitionData($closeData, "id = " . (int)$reqId);
					$models->requisition->change_to_requisition_closed($reqId);

					// Helper method within controller to notify stakeholders
					$this->send_requi_mail($reqId, $models->requisition, $models->users, $loginUserId);
				}
			}
		}
	}

	/**
	 * Sends a notification email to the employee after their details are updated.
	 * Based on the logic at the end of the original save() function.
	 *
	 * @param array $params The request parameters containing email and name
	 * @return void
	 */
	protected function _sendUpdateNotification($params)
	{
		$userfullname = trim(($params['firstname'] ?? '') . ' ' . ($params['lastname'] ?? ''));
		$emailaddress = $params['emailaddress'] ?? null;

		if (empty($emailaddress)) {
			return;
		}

		$options = [
			'subject' => APPLICATION_NAME . ': Employee details updated',
			'header'  => 'Employee details updated',
			'toEmail' => $emailaddress,
			'toName'  => $userfullname,
			'message' => 'Dear ' . $userfullname . ', <br/> Your details have been updated.',
			'cron'    => 'yes' // Sentrifugo uses this to queue the mail for the cron job
		];

		sapp_Global::_sendEmail($options);
	}

	/**
	 * Standardizes error messaging when a database exception occurs.
	 * 
	 * @param Exception $e The caught exception
	 * @param string $empNumId The numeric part of the Employee ID
	 * @return array Associative array of error messages
	 */
	protected function _handleException(Exception $e, $empNumId)
	{
		$msgarray = [];
		$exceptionMsg = $e->getMessage();

		// Check for duplicate entry errors (common in Sentrifugo if IDs overlap)
		if (!empty($exceptionMsg) && strpos($exceptionMsg, "Duplicate entry '" . $empNumId . "'") !== false) {
			$msgarray['employeeNumId'] = "Employee ID already exists. Please try again.";
		} else {
			$msgarray['employeeId'] = "Something went wrong, please try again later. Error: " . $exceptionMsg;
		}

		return $msgarray;
	}

	public function send_requi_mail($id, $requi_model, $user_model, $loginUserId)
	{
		$requi_model->change_to_requisition_closed($id);
		// end of changing candidate status
		$requisition_data = $requi_model->getReqDataForView($id);
		$requisition_data = $requisition_data[0];
		$report_person_data = $user_model->getUserDataById($requisition_data['createdby']);
		$closed_person_data = $user_model->getUserDataById($loginUserId);
		$mail_arr = array();
		$mail_arr[0]['name'] = 'HR';
		$mail_arr[0]['email'] = defined('REQ_HR_' . $requisition_data['businessunit_id']) ? constant('REQ_HR_' . $requisition_data['businessunit_id']) : "";
		$mail_arr[0]['type'] = 'HR';
		$mail_arr[1]['name'] = 'Management';
		$mail_arr[1]['email'] = defined('REQ_MGMT_' . $requisition_data['businessunit_id']) ? constant('REQ_MGMT_' . $requisition_data['businessunit_id']) : "";
		$mail_arr[1]['type'] = 'Management';
		$mail_arr[2]['name'] = $report_person_data['userfullname'];
		$mail_arr[2]['email'] = $report_person_data['emailaddress'];
		$mail_arr[2]['type'] = 'Raise';
		for ($ii = 0; $ii < count($mail_arr); $ii++) {
			$base_url = 'http://' . $this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
			$view = $this->getHelper('ViewRenderer')->view;
			$this->view->emp_name = $mail_arr[$ii]['name'];
			$this->view->base_url = $base_url;
			$this->view->type = $mail_arr[$ii]['type'];
			$this->view->requisition_code = $requisition_data['requisition_code'];
			$this->view->req_status = "Completed";
			$this->view->raised_name = $report_person_data['userfullname'];
			$this->view->approver_str = $closed_person_data['userfullname'];
			$text = $view->render('mailtemplates/changedrequisition.phtml');
			$options['subject'] = APPLICATION_NAME . ': Requisition for approval';
			$options['header'] = 'Requisition for approval';
			$options['toEmail'] = $mail_arr[$ii]['email'];
			$options['toName'] = $mail_arr[$ii]['name'];
			$options['message'] = $text;
			$options['cron'] = 'yes';
			sapp_Global::_sendEmail($options);
		}
	}

	public function getdepartmentsAction()
	{
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('getdepartments', 'html')->initContext();


		$businessunit_id = $this->_request->getParam('business_id');
		$con = $this->_request->getParam('con');
		$employeeform = new Default_Form_employee();
		$leavemanagementform = new Default_Form_leavemanagement();
		$flag = '';
		$departmentsmodel = new Default_Model_Departments();
		if ($con == 'leavemanagement') {
			$leavemanagementmodel = new Default_Model_Leavemanagement();
			$departmentidsArr = $leavemanagementmodel->getActiveDepartmentIds();
			$depatrmentidstr = '';
			$newarr = array();
			if (!empty($departmentidsArr)) {
				$where = '';
				for ($i = 0; $i < sizeof($departmentidsArr); $i++) {
					$newarr1[] = array_push($newarr, $departmentidsArr[$i]['deptid']);
				}
				$depatrmentidstr = implode(",", $newarr);
				foreach ($newarr as $deparr) {
					$where .= " id!= $deparr AND ";
				}
				$where = trim($where, " AND");
				$querystring = "Select d.id,d.deptname from main_departments as d where d.unitid=$businessunit_id and d.isactive=1 and $where  ";

				$uniquedepartmentids = $departmentsmodel->getUniqueDepartments($querystring);
				if (empty($uniquedepartmentids))
					$flag = 'true';

				$this->view->uniquedepartmentids = $uniquedepartmentids;
			} else {
				$departmentlistArr = $departmentsmodel->getDepartmentList($businessunit_id);
				if (empty($departmentlistArr))
					$flag = 'true';
				$this->view->departmentlistArr = $departmentlistArr;
			}
		} else {
			$departmentlistArr = $departmentsmodel->getDepartmentList($businessunit_id);
			if (empty($departmentlistArr))
				$flag = 'true';
			$this->view->departmentlistArr = $departmentlistArr;
		}

		$this->view->employeeform = $employeeform;
		$this->view->leavemanagementform = $leavemanagementform;
		$this->view->flag = $flag;
		if ($con != '')
			$this->view->con = $con;
	}

	public function getpositionsAction()
	{
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('getpositions', 'html')->initContext();


		$jobtitle_id = $this->_request->getParam('jobtitle_id');
		$con = $this->_request->getParam('con');
		$employeeform = new Default_Form_employee();
		$positionsmodel = new Default_Model_Positions();
		$flag = '';
		$positionlistArr = $positionsmodel->getPositionList($jobtitle_id);
		if (empty($positionlistArr))
			$flag = 'true';

		$this->view->positionlistArr = $positionlistArr;
		$this->view->employeeform = $employeeform;
		$this->view->flag = $flag;
		if ($con != '')
			$this->view->con = $con;
	}

	public function deleteAction()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->_request->getParam('objid');
		$messages['message'] = '';
		$messages['msgtype'] = '';
		$actionflag = 3;
		if ($id) {
			$employeemodel = new Default_Model_Employee();
			$usersModel = new Default_Model_Users();
			$empdata = array('isactive' => 5);
			$empwhere = array('user_id=?' => $id);
			$userdata = array('isactive' => 5);
			$userwhere = array('id=?' => $id);
			$empId = $employeemodel->SaveorUpdateEmployeeData($empdata, $empwhere);
			$userId = $usersModel->addOrUpdateUserModel($userdata, $userwhere);

			if ($empId == 'update' && $userId == 'update') {
				$menuID = EMPLOYEE;
				$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $id);
				$messages['message'] = 'Employee deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Employee cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Employee cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		$this->_helper->json($messages);
	}

	/**
	 * This function is used for ajax call to get reporting managers based on  department
	 * @param integer $id  = department id.
	 * @return Array Array of managers in json format.
	 */
	public function getempreportingmanagersAction()
	{
		$dept_id = $this->_getParam('id', null);
		$employee_id = $this->_getParam('empId', null);
		$employee_group = $this->_getParam('empgroup', null);
		$usersModel = new Default_Model_Users();
		$reportingManagerData = array();
		$flag = '';
		if ($dept_id == 'undefined')
			$dept_id = '';
		if ($employee_group != '') {
			$reportingManagerData = $usersModel->getReportingManagerList_employees($dept_id, $employee_id, $employee_group);

			if (empty($reportingManagerData)) {
				$flag = 'true';
			} else {
				$flag = 'false';
			}
		}

		$this->view->RMdata = $reportingManagerData;

		$this->view->flag = $flag;
	}

	/**
	 * This function is used to active/inactive employees.
	 */
	public function makeactiveinactiveAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$emp_id = $this->_getParam('emp_id', null);
		$status = trim($this->_getParam('status', null));
		$hasteam = trim($this->_getParam('hasteam', null));
		$employeeModal = new Default_Model_Employee();
		$user_model = new Default_Model_Usermanagement();
		$usermodel = new Default_Model_Users();
		$role_model = new Default_Model_Roles();
		$logmanagermodel = new Default_Model_Logmanager();
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$empData = $employeeModal->getsingleEmployeeData($emp_id);

		if ($hasteam == 'true') {
			$employessunderEmpId = array();
			$reportingmanagersList = array();
			$employessunderEmpId = $employeeModal->getEmployeesUnderRM($emp_id);

			if ($empData[0]['is_orghead'] == 1) {
				$reportingmanagersList = $usermodel->getReportingManagerList_employees('', '', MANAGEMENT_GROUP);
			} else {
				$role_data = $role_model->getRoleDataById($empData[0]['emprole']);
				$reportingmanagersList = $usermodel->getReportingManagerList_employees($empData[0]['department_id'], $emp_id, $role_data['group_id']);
			}

			$reportingmanagersList = sapp_Global::removeElementWithValue($reportingmanagersList, 'id', $emp_id);
			$this->view->emp_id = $emp_id;
			$this->view->status = $status;
			$this->view->ishead = $empData[0]['is_orghead'];
			$this->view->empName = $empData[0]['userfullname'];
			$this->view->employessunderEmpId = $employessunderEmpId;
			$this->view->reportingmanagersList = $reportingmanagersList;
		} else {
			$db = Zend_Db_Table::getDefaultAdapter();
			$db->beginTransaction();
			try {
				if ($status == 'active') {
					$data = array(
						'isactive' => 1,
						'emptemplock' => 0,
					);
					$empdata = array('isactive' => 1);
					$logarr = array(
						'userid' => $loginUserId,
						'recordid' => $emp_id,
						'date' => gmdate("Y-m-d H:i:s"),
						'isactive' => 1
					);
					$jsonlogarr = json_encode($logarr);
				} else if ($status == 'inactive') {
					$data = array(
						'isactive' => 0,
						'emptemplock' => 1,
					);
					$empdata = array('isactive' => 0);
					$logarr = array(
						'userid' => $loginUserId,
						'recordid' => $emp_id,
						'date' => gmdate("Y-m-d H:i:s"),
						'isactive' => 0
					);
					$jsonlogarr = json_encode($logarr);
				}
				$where = "id = " . $emp_id;
				$user_model->SaveorUpdateUserData($data, $where);
				$employeeModal->SaveorUpdateEmployeeData($empdata, "user_id =" . $emp_id);
				if ($empData[0]['is_orghead'] == '1') {
					$headData = array(
						'is_orghead' => 0
					);
					$headWhere = "user_id = " . $emp_id;
					$employeeModal->SaveorUpdateEmployeeData($headData, $headWhere);
				}
				$menuID = EMPLOYEE;
				$id = $logmanagermodel->addOrUpdateLogManager($menuID, 4, $jsonlogarr, $loginUserId, $emp_id);

				$db->commit();
				$result = 'update';
			} catch (Exception $e) {
				$db->rollBack();
				$result = 'failed';
			}
			$this->_helper->json(array('result' => ($result == 'update') ? "yes" : "no"));
		}
	}

	public function changereportingmanagerAction()
	{
		$oldRM = $this->_getParam('empid', null);
		$newRM = $this->_getParam('newrmanager', null);
		$status = trim($this->_getParam('status', null));
		$ishead = trim($this->_getParam('ishead', null));
		$baseUrl = BASE_URL;
		$employeeModal = new Default_Model_Employee();
		$employessunderEmpId = $employeeModal->getEmployeesUnderRM($oldRM);
		$updateTable = $employeeModal->changeRM($oldRM, $newRM, $status, $ishead);
		/* Send Mails to the employees whose reporting manager is changed */
		$oldRMData = $employeeModal->getsingleEmployeeData($oldRM);
		$newRMData = $employeeModal->getsingleEmployeeData($newRM);
		foreach ($employessunderEmpId as $employee) {
			$options['subject'] = APPLICATION_NAME . ' : Change of reporting manager';
			$options['header'] = 'Change of reporting manager';
			$options['toEmail'] = $employee['emailaddress'];
			$options['toName'] = $employee['userfullname'];
			$options['message'] = '<div>Hello ' . ucfirst($employee['userfullname']) . ',
										<div>' . ucfirst($newRMData[0]['userfullname']) . ' is your new reporting manager.</div>
										<div style="padding:20px 0 10px 0;">Please <a href="' . $baseUrl . '/index/popup" target="_blank" style="color:#b3512f;">click here</a> to login </div>
									</div>';
			$result = sapp_Global::_sendEmail($options);
		}
		$this->_helper->json(array('result' => $updateTable));
	}

	public function addemppopupAction()
	{
		$flag = 'true';
		$controllername = 'employee';
		$msgarray = array();
		$emptyFlag = 0;
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$deptidforhead = $this->_getParam('deptidforhead', null);

		$report_opt = array();
		$emp_form = new Default_Form_employee();
		$user_model = new Default_Model_Usermanagement();
		$role_model = new Default_Model_Roles();
		$prefixModel = new Default_Model_Prefix();
		$identity_code_model = new Default_Model_Identitycodes();
		$jobtitlesModel = new Default_Model_Jobtitles();
		$deptModel = new Default_Model_Departments();
		$positionsmodel = new Default_Model_Positions();
		$employeeModal = new Default_Model_Employee();
		$usersModel2 = new Default_Model_Users();
		$employmentstatusModel = new Default_Model_Employmentstatus();

		$emp_form->setAction(BASE_URL . 'employee/addemppopup/deptidforhead/' . $deptidforhead);
		$emp_form->removeElement('department_id');
		$emp_form->removeElement('modeofentry');

		$identity_codes = $identity_code_model->getIdentitycodesRecord();
		$emp_identity_code = isset($identity_codes[0]) ? $identity_codes[0]['employee_code'] : "";
		if ($emp_identity_code != '') {
			// $emp_id = $emp_identity_code.str_pad($user_model->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
			$emp_id = $emp_identity_code;
		} else {
			$emp_id = '';
			$msgarray['employeeId'] = 'Identity codes are not configured yet.';
			$flag = 'false';
		}
		$emp_form->employeeId->setValue($emp_id);

		$role_data = $role_model->getRolesList_Dept($deptidforhead);   //in add screen $deptidforhead is null and we can get only Management role,In edit screen getting all roles
		$emp_form->emprole->addMultiOptions(array('' => 'Select Role') + $role_data);
		if (empty($role_data)) {
			$msgarray['emprole'] = 'Roles are not configured yet.';
			$flag = 'false';
		}

		$prefixData = $prefixModel->getPrefixList();

		$emp_form->prefix_id->addMultiOption('', 'Select Prefix');
		if (!empty($prefixData)) {
			foreach ($prefixData as $prefixres) {
				$emp_form->prefix_id->addMultiOption($prefixres['id'], $prefixres['prefix']);
			}
		} else {
			$msgarray['prefix_id'] = 'Prefixes are not configured yet.';
			//$flag = 'false';
		}

		$jobtitleData = $jobtitlesModel->getJobTitleList();
		if (!empty($jobtitleData)) {
			foreach ($jobtitleData as $jobtitleres) {
				$emp_form->jobtitle_id->addMultiOption($jobtitleres['id'], $jobtitleres['jobtitlename']);
			}
		} else {
			$msgarray['jobtitle_id'] = 'Job titles are not configured yet.';
			$msgarray['position_id'] = 'Positions are not configured yet.';
			//$flag = 'false';
		}

		if (isset($_POST['jobtitle_id']) && $_POST['jobtitle_id'] != '') {
			$positionlistArr = $positionsmodel->getPositionList($_POST['jobtitle_id']);
			if (sizeof($positionlistArr) > 0) {
				$emp_form->position_id->addMultiOption('', 'Select Position');
				foreach ($positionlistArr as $positionlistres) {
					$emp_form->position_id->addMultiOption($positionlistres['id'], $positionlistres['positionname']);
				}
			}
		}

		$employmentStatusData = $employmentstatusModel->getempstatusActivelist();
		$emp_form->emp_status_id->addMultiOption('', 'Select Employment Status');
		if (!empty($employmentStatusData)) {
			foreach ($employmentStatusData as $employmentStatusres) {
				$emp_form->emp_status_id->addMultiOption($employmentStatusres['workcodename'], $employmentStatusres['statusname']);
			}
		} else {
			$msgarray['emp_status_id'] = 'Employment status is not configured yet.';
			$emptyFlag++;
		}
		$reportingManagerData = array();
		$emprole = $this->_getParam('emprole', null);
		$businessunit_id = null;
		//if department id and role are not empty(for edit department screen)
		if (!empty($deptidforhead) && is_numeric($deptidforhead) && !empty($emprole)) {
			//get business unit id based on department id
			$departments_model = new Default_Model_Departments();
			$department_details = $departments_model->getEmpdepartmentdetails($deptidforhead);
			if (!empty($department_details)) {
				$businessunit_id = !empty($department_details[0]['unitid']) ? $department_details[0]['unitid'] : null;
			}

			$employee_role = explode('_', $emprole);
			if (!empty($employee_role[0]) && !empty($employee_role[1]) && is_numeric($employee_role[0]) && is_numeric($employee_role[1])) {
				$reportingManagerData = $usersModel2->getReportingManagerList_employees($deptidforhead, '', $employee_role[1]);
			}
		} else //for add department screen
		{
			$reportingManagerData = $usersModel2->getReportingManagerList_employees('', '', MANAGEMENT_GROUP);
		}

		if (!empty($reportingManagerData)) {
			$report_opt = $reportingManagerData;
			if (isset($_POST['reporting_manager']) && $_POST['reporting_manager'] != '')
				$emp_form->setDefault('reporting_manager', $_POST['reporting_manager']);
		} else {
			$msgarray['reporting_manager'] = 'Reporting managers are not added yet.';
			$flag = 'false';
		}
		if ($this->getRequest()->getPost()) {
			if ($emp_form->isValid($this->_request->getPost())) {
				$emp_id = '';
				$employeeId = $this->_request->getParam('employeeId', null);
				$employeeNumId = trim($this->_getParam('employeeNumId', null));
				//employee id
				$emp_identity_code = isset($identity_codes[0]) ? $identity_codes[0]['employee_code'] : "";
				if ($emp_identity_code != '') {
					// $emp_id = $emp_identity_code.str_pad($usersModel->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
					$emp_id = $emp_identity_code . $employeeNumId;
				} else {
					$msgarray['employeeId'] = 'Identity codes are not configured yet.';
					$flag = 'false';
				}
				//duplicate emp id check
				$where_condition = "";
				if (!empty($id)) {
					$where_condition = " and id != $id";
				}
				$isemployeeidexist = $employeeModal->checkemployeeidexist($emp_id, $where_condition);
				if ($isemployeeidexist) {
					$msgarray['employeeNumId'] = "Employee ID already exists. Please try again.";
					$flag = 'false';
				}
				if ($flag == 'true') {
					$jobtitle_id = $this->_request->getParam('jobtitle_id', null);
					$location = $this->_request->getParam('location', null);

					$position_id = $this->_request->getParam('position_id', null);
					$date_of_joining = sapp_Global::change_date($this->_request->getParam('date_of_joining', null), 'database');
					$date_of_leaving = $this->_request->getParam('date_of_leaving', null);
					$date_of_leaving = sapp_Global::change_date($date_of_leaving, 'database');
					$employeeId = $this->_getParam('employeeId', null);
					$emprole = explode('_', $this->_getParam('emprole', null));
					$reporting_manager = $this->_getParam('reporting_manager', null);
					$emailaddress = $this->_getParam('emailaddress', null);
					$emppassword = sapp_Global::generatePassword();
					$firstname = trim($this->_getParam('firstname', null));
					$lastname = trim($this->_getParam('lastname', null));
					$businessunitid = trim($this->_getParam('businessunitid', null));
					$userfullname = $firstname . ' ' . $lastname;
					//$userfullname = trim($this->_request->getParam('userfullname',null));
					$prefix_id = $this->_getParam('prefix_id', null);
					$user_id = $this->_getParam('user_id', null);
					$emp_status_id = $this->_getParam('emp_status_id', null);

					$user_data = array(
						'emprole' => !empty($emprole[0]) ? $emprole[0] : null,
						'firstname' => ($firstname != '') ? $firstname : NULL,
						'lastname' => ($lastname != '') ? $lastname : NULL,
						'userfullname' => $userfullname,
						'emailaddress' => $emailaddress,
						'jobtitle_id' => $jobtitle_id,
						'modifiedby' => $loginUserId,
						'modifieddate' => gmdate("Y-m-d H:i:s"),
						'emppassword' => md5($emppassword),
						'employeeId' => $emp_id,
						'modeofentry' => 'Direct',
						'selecteddate' => $date_of_joining,
						'userstatus' => 'old',
					);
					$emp_data = array(
						'user_id' => $user_id,
						'jobtitle_id' => $jobtitle_id,
						'position_id' => $position_id,
						'location' => $location,

						'prefix_id' => $prefix_id,
						'department_id' => $deptidforhead,
						'businessunit_id' => !empty($businessunitid) ? $businessunitid : '0',
						'reporting_manager' => $reporting_manager,
						'date_of_joining' => $date_of_joining,
						'date_of_leaving' => ($date_of_leaving != '' ? $date_of_leaving : NULL),
						'emp_status_id' => $emp_status_id,
						'modifiedby' => $loginUserId,
						'modifieddate' => gmdate("Y-m-d H:i:s")
					);
					$user_data['createdby'] = $loginUserId;
					$user_data['createddate'] = gmdate("Y-m-d H:i:s");
					$user_data['isactive'] = 1;
					/*if($emp_identity_code!='')
						$emp_id = $emp_identity_code.str_pad($user_model->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
					else
					$emp_id = '';*/
					$user_data['employeeId'] = $emp_id;
					$user_id = $user_model->SaveorUpdateUserData($user_data, '');

					$emp_data['user_id'] = $user_id;
					$emp_data['createdby'] = $loginUserId;
					$emp_data['createddate'] = gmdate("Y-m-d H:i:s");;
					$emp_data['isactive'] = 1;
					$employeeModal->SaveorUpdateEmployeeData($emp_data, '');
					//end of saving into employee table.
					$tableid = $user_id;
					$actionflag = 1;
					$menuID = ORGANISATIONINFO;
					try {
						$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $tableid);
					} catch (Exception $e) {
					}

					//$managementUsersData = $deptModel->getDeptHeads();
					$managementUsersData = $deptModel->getDepartmenttHead('');
					$opt = '';
					foreach ($managementUsersData as $record) {
						$opt .= sapp_Global::selectOptionBuilder($record['user_id'], $record['userfullname']);
					}

					$this->view->managementUsersData = $opt;

					/* Send Mail to the user */
					$base_url = 'http://' . $this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
					$view = $this->getHelper('ViewRenderer')->view;
					$this->view->emp_name = $userfullname;
					$this->view->password = $emppassword;
					$this->view->emp_id = $emp_id;
					$this->view->base_url = $base_url;
					$text = $view->render('mailtemplates/newpassword.phtml');
					$options['subject'] = APPLICATION_NAME . ' login Credentials';
					$options['header'] = 'Greetings from Sentrifugo';
					$options['toEmail'] = $emailaddress;
					$options['toName'] = $this->view->emp_name;
					$options['message'] = $text;
					$result = sapp_Global::_sendEmail($options);
					/* END */

					$this->view->eventact = 'added';
					$close = 'close';
					$this->view->popup = $close;
				} else {
					$messages = $emp_form->getMessages();
					foreach ($messages as $key => $val) {
						foreach ($val as $key2 => $val2) {
							$msgarray[$key] = $val2;
							break;
						}
					}
					$this->view->msgarray = $msgarray;
				}
			} else {
				$messages = $emp_form->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgarray[$key] = $val2;
						break;
					}
				}
				$this->view->msgarray = $msgarray;
			}
		}
		$this->view->msgarray = $msgarray;
		$this->view->report_opt = $report_opt;
		$this->view->controllername = $controllername;
		$this->view->departmentid = $deptidforhead;
		$this->view->businessunitid = $businessunit_id;
		$this->view->emp_form = $emp_form;
	}

	public function uploadexcelAction()
	{
		$savefolder = EMP_EXCEL_UPLOAD_PATH;		// folder for upload									
		$max_size = 1048576;			// maxim size for image file, in KiloBytes
		// Allowed file types		
		$allowtype = array('xls', 'xlsx');

		$rezultat = '';
		$result_status = '';
		$result_msg = '';
		// if is received a valid file
		if (isset($_FILES['emp_excel'])) {

			// checks to have the allowed extension
			$type = explode(".", strtolower($_FILES['emp_excel']['name']));
			$ext = $type[count($type) - 1];
			if (in_array($ext, $allowtype)) {
				// check its size
				if ($_FILES['emp_excel']['size'] <= $max_size * 1024) {
					// if no errors
					if ($_FILES['emp_excel']['error'] == 0) {
						$newname = 'ex_pre_' . date("His") . '.' . $ext;
						$thefile = $savefolder . "/" . $_FILES['emp_excel']['name'];
						$newfilename = $savefolder . "/" . $newname;
						$filename = $newname;

						if (!move_uploaded_file($_FILES['emp_excel']['tmp_name'], $newfilename)) {
							$rezultat = '';
							$result_status = 'error';
							$result_msg = 'The file cannot be uploaded, try again.';
						} else {

							$rezultat = $filename;

							$stat = sapp_Helper::process_emp_excel($newfilename);
							$result_msg = $stat['msg'];
							$result_status = $stat['status'];
						}
					}
				} else {
					$rezultat = '';
					$result_status = 'error';
					$result_msg = 'The file exceeds the maximum permitted size ' . $max_size . ' KB.';
				}
			} else {
				$rezultat = '';
				$result_status = 'error';
				$result_msg = 'Please upload only .xls,.xlsx files.';
			}
		} else {
			$rezultat = '';
			$result_status = 'error';
			$result_msg = 'Please upload only .gif, .jpg, .jpeg, .png images.';
		}

		$result = array(
			'result' => $result_status,
			'img' => $rezultat,
			'msg' => $result_msg
		);

		$this->_helper->json($result);
	} //end of upload excel action

	public function getindividualempdetailsAction()
	{
		$employeeModel = new Default_Model_Employee();
		$userid = $this->_request->getParam('userid', null);

		$userdetails = $employeeModel->getEmp_from_summary($userid);
		$this->view->userdetails = $userdetails;
	}
}//end of class