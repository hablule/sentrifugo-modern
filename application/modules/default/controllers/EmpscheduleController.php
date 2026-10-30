<?php

class Default_EmpscheduleController extends Zend_Controller_Action
{

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

			$id = $this->getRequest()->getParam('unitId');

			$Uid = ($id) ? $id : $userID;
			if ($Uid != "") {
				$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
			}

			$empscheduleModel = new Default_Model_Empschedule();
			$employeeModal = new Default_Model_Employee();
			//die($this->getRequest()->getParam('userid'));

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
					$by = 'l.emp_id';
					$pageNo = 1;
					$searchData = '';
					$searchQuery = '';
					$searchArray = array();
				} else {
					$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
					$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'l.emp_id';
					if ($dashboardcall == 'Yes')
						$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
					else
						$perPage = $this->_getParam('per_page', PERPAGE);

					$pageNo = $this->_getParam('page', 1);
					$searchData = $this->_getParam('searchData');
					$searchData = rtrim($searchData, ',');
				}
				$dataTmp = $empscheduleModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'edit');

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

	public function getschedulesAction()
	{
		try {

			$ids = $this->_getParam('ids');
			$model = new Default_Model_Empschedule();
			$empschData = $model->getEmpSchedule($ids);
			$this->_helper->json($empschData);
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
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
			$Uid = ($id) ? $id : $userID;

			if ($Uid != "") {
				$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
			}
			$empscheduleModel = new Default_Model_Empschedule();
			$employeeModal = new Default_Model_Employee();
			$isrowexist = $employeeModal->getsingleEmployeeData($Uid);

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
					$by = 'l.emp_id';
					$pageNo = 1;
					$searchData = '';
					$searchQuery = '';
					$searchArray = array();
				} else {
					$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
					$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'l.emp_id';
					if ($dashboardcall == 'Yes')
						$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
					else
						$perPage = $this->_getParam('per_page', PERPAGE);

					$pageNo = $this->_getParam('page', 1);
					$searchData = $this->_getParam('searchData');
					$searchData = rtrim($searchData, ',');
				}
				$dataTmp = $empscheduleModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'edit');

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
		$Uid = ($id) ? $id : $userID;
		if ($Uid != "") {
			$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
		}
		$empscheduleModel = new Default_Model_Empschedule();
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

				$sort = 'ASC';
				$by = 'l.emp_id';
				$pageNo = 1;
				$searchData = '';
				$searchQuery = '';
				$searchArray = array();
			} else {
				$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
				$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'l.emp_id';
				if ($dashboardcall == 'Yes')
					$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
				else
					$perPage = $this->_getParam('per_page', PERPAGE);

				$pageNo = $this->_getParam('page', 1);
				$searchData = $this->_getParam('searchData');
				$searchData = rtrim($searchData, ',');
			}
			$dataTmp = $empscheduleModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, '');

			array_push($data, $dataTmp);
			$permission = sapp_Global::_checkprivileges(EMPLOYEE, $loginuserGroup, $loginUserRole, 'view');
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
/* 	public function getAllShifts()
	{
		$new_data = array();
		if ($this->_request->getParam('mon_check') == 1) {
			$temp_arr = [];
			$shifts = $this->_request->getParam('mon_shift');
			foreach ($shifts as $shift) {
				array_push($temp_arr, $shift);
			}
			$new_data['Monday'] = $temp_arr;
		}
		if ($this->_request->getParam('tus_check') == 1) {
			$temp_arr = [];
			$shifts = $this->_request->getParam('tus_shift');
			foreach ($shifts as $shift) {
				array_push($temp_arr, $shift);
			}
			$new_data['Tuesday'] = $temp_arr;
		}
		if ($this->_request->getParam('wen_check') == 1) {
			$temp_arr = [];
			$shifts = $this->_request->getParam('wen_shift');
			foreach ($shifts as $shift) {
				array_push($temp_arr, $shift);
			}
			$new_data['Wednesday'] = $temp_arr;
		}
		if ($this->_request->getParam('thu_check') == 1) {
			$temp_arr = [];
			$shifts = $this->_request->getParam('thu_shift');
			foreach ($shifts as $shift) {
				array_push($temp_arr, $shift);
			}
			$new_data['Thursday'] = $temp_arr;
		}
		if ($this->_request->getParam('fri_check') == 1) {
			$temp_arr = [];
			$shifts = $this->_request->getParam('fri_shift');
			foreach ($shifts as $shift) {
				array_push($temp_arr, $shift);
			}
			$new_data['Friday'] = $temp_arr;
		}
		if ($this->_request->getParam('sat_check') == 1) {
			$temp_arr = [];
			$shifts = $this->_request->getParam('sat_shift');
			foreach ($shifts as $shift) {
				array_push($temp_arr, $shift);
			}
			$new_data['Saturday'] = $temp_arr;
		}
		if ($this->_request->getParam('sun_check') == 1) {
			$temp_arr = [];
			$shifts = $this->_request->getParam('sun_shift');
			foreach ($shifts as $shift) {
				array_push($temp_arr, $shift);
			}
			$new_data['Sunday'] = $temp_arr;
		}

		return $new_data;
	}
 */
	public function addpopupAction()
	{
		try {
			//code...

			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");



			$auth = Zend_Auth::getInstance();


			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
			}

			$userid = $this->_request->getParam('unitId');
			//Zend_Debug::dump($this->getRequest()->getParam('unitId'));
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
			}
			// $userid = $loginUserId;
			$employeeModal = new Default_Model_Employee();
			$isrowexist = $employeeModal->getsingleEmployeeData($userid);

			if ($isrowexist == 'norows')
				$this->view->rowexist = "norows";
			else
				$this->view->rowexist = "rows";



			if ($userid) {
				//To display Employee Profile information......
				$usersModel = new Default_Model_Users();
			}
			$this->view->id = $userid;



			//Zend_debug::dump(Zend_Controller_Front::getInstance()->getRequest()->getParam('id'));

			$empschedulemodel = new Default_Model_Empschedule();
			$pes = $userid;
			$form = new Default_Form_empschedule();
			// die($pes);
			$form->id->setValue($userid);
			$data3 = $empschedulemodel->getSingleSchedule($pes);

			//$empschedulemodel = new Default_Model_Empschedule();
			/* $schedule = $empschedulemodel->getSchedule();
			foreach ($schedule as $value) {
				$form->sch_name->addMultiOptions(array($value['sch_id'] => $value['schedule_name']));
			} */
			$flag = '';
			if (empty($data3)) {
				$flag = '';
			} else {
				$old_sch = [];
				foreach ($data3 as $data) {
					array_push($old_sch, (int)$data['sch_id']);
				}
				$form->sch_name->setValue($old_sch);
				$form->submit->setLabel('Save');

				$flag = $pes;
			}
			// die($flag);
			// $temp_time_id = explode(',', $data3[0]['schedule']);
			// $time_id =  array();
			// foreach ($temp_time_id as $id) {
			//     if ($id != '') {

			//         array_push($time_id, $id);
			//     }
			// }

			// $id_temp = array();
			// foreach ($time_id as $time) {
			//     $data2 = $empschedulemodel->getSchedulebyid($time);
			//     array_push($id_temp, $data2[0]['id']);
			// }
			//  die(json_encode($data2[0]['schedule_name']));

			// if ($id_temp != 'NULL') {
			//     $form->sch_name->setValue($id_temp);
			// }
			if ($this->getRequest()->isPost()) {
				$emp_id = $this->_request->getParam('id');
				if (!utlis::isoverlappingschedule($this->_request->getParam('sch_name'), $emp_id)) {

					if ($form->isValid($this->_request->getPost())) {

						$schedules = $this->_request->getParam('sch_name');

						$shift = '';

						$emp_sch_data = array(
							'schedule_id' => $schedules,
							'emp_id' => $emp_id,
							'description' => $this->_getParam('description'),
							'start_date' => $this->_getParam('start_date'),
							'end_date' => $this->_getParam('end_date'),
							'createddate' => gmdate("Y-m-d H:i:s"),
							'createdby' => $loginUserId

						);
						try {
							$empschedulemodel->saveOrUpdate($emp_sch_data, '');
							$this->view->successmessage = 'Employee Schedule Assign updated successfully.';
						} catch (\Throwable $th) {
							Zend_Debug::dump($th->getMessage());
							$code = $th->getCode();
							if ($code = 2300) {
								$msgarray['sch_name'] = 'Schedule already exist ';
								$this->view->msgarray = $msgarray;
							} else {
								$this->view->successmessage = 'Employee Schedule Assign updated successfully.';
								$this->_helper->getHelper("FlashMessenger")->addMessage(array('success' => 'Assign Successfuly'));
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
						$this->view->msgarray = $msgarray;
					}
				}
			} else {
				//$msgarray['sch_name'] = 'This Schedule already assigned to this employee';

			}
			$this->view->controllername = 'empschedule';
			$this->view->form = $form;
			$this->view->msgarray = $msgarray;

			$controllername = 'empschedule';

			//$this->view->msgarray = $msgArray;
			// $this->view->form = $timeschform;
			$this->view->controllername = $controllername;
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function checkschid($schid, $userid)
	{
		Zend_Debug::dump($schid);
		$model = new Default_Model_Empschedule();
		$empschdata = $model->getSchedulebyemp($userid);
		foreach ($empschdata as  $value) {
			$exists = array_search($schid . '', $value);
			if ($exists != false) {
				return true;
			}
		}
		return false;
	}


	public function editpopupAction()
	{

		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		require_once 'application/modules/default/library/utilClasses/Util.php';
		try {
			//code...



			$auth = Zend_Auth::getInstance();


			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
			}
			$userid = $this->_request->getParam('unitId');

			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
			}
			// $userid = $loginUserId;
			$employeeModal = new Default_Model_Employee();
			
			$isrowexist = $employeeModal->getsingleEmployeeData($userid);
			
			if ($isrowexist == 'norows')
				$this->view->rowexist = "norows";
			else
				$this->view->rowexist = "rows";



			if ($userid) {
				//To display Employee Profile information......
				$usersModel = new Default_Model_Users();
			}
			$this->view->id = $userid;




			$empschedulemodel = new Default_Model_Empschedule();
			$pes = $this->_request->getParam('id');
			$ids = $this->getRequest()->getParam('unitId');

			$form = new Default_Form_empschedule();

			// die($pes);

			$form->id->setValue($userid);
			$data3 = $empschedulemodel->getSingleSchedule($userid);
			
			//$empschedulemodel = new Default_Model_Empschedule();

			$empsch = $empschedulemodel->getempsch($pes);
			
			$schedule = $empschedulemodel->getSchedule();
			
			/* foreach ($schedule as $value) {
				$form->sch_name->addMultiOptions(array($value['sch_id'] => $value['schedule_name']));
			} */
			$flag = '';
			if (empty($data3)) {
				$flag = '';
			} else {
				$old_sch = [];
				/* foreach ($data3 as $data) {
                        array_push($old_sch, (int)$data['sch_id']);
                    } */

				//$form->sch_name->setAttrib('readonly', 'readonly');
				//die(Zend_Debug::dump($empsch));
				$form->populate($empsch[0]);
				$form->sch_name->setValue($empsch[0]['schedule_id']);
				$form->isactive->setValue(utlis::getcheckboxvalue($empsch[0]['isactive'], 'form'));
				$form->submit->setLabel('Update');

				$flag = $pes;
			}

			if ($this->getRequest()->isPost()) {
			
				$emp_id = $this->_request->getParam('id');
				if (!utlis::isoverlappingschedule($this->_request->getParam('sch_name'), $emp_id)) {
					if ($form->isValid($this->getRequest()->getPost())) {
						
						// $empschedulemodel->delete($emp_id);
						$schedules = $this->_request->getParam('sch_name');
						$isactive = utlis::getcheckboxvalue($this->_request->getParam('isactive'), 'database');

						$shift = '';

						$emp_sch_data = array(
							'schedule_id' => $schedules,
							'description' => $this->_request->getParam('description'),
							'start_date' => $this->_request->getParam('start_date'),
							'end_date' => $this->_request->getParam('end_date'),
							'isactive' => $isactive,
							'modifiedby' => $loginUserId,
							'modifieddate' => gmdate("Y-m-d H:i:s")
						);
						
						$empschedulemodel->saveOrUpdate($emp_sch_data, $pes);
						$this->view->successmessage = 'Employee Schedule Assign updated successfully.';
					}
				}
				else{
					$msgarray['sch_name'] = 'schedule overlap';
				}
	
			}
			$this->view->form = $form;
			$controllername = 'empschedule';

			//$this->view->msgarray = $msgArray;
			// $this->view->form = $timeschform;
			$this->view->controllername = $controllername;
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
			Zend_Debug::dump($th->getLine());
			Zend_Debug::dump($th->getFile());
		}
	}

	public function viewpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$pes = $this->_request->getParam('id');
		$empschedulemodel = new Default_Model_Empschedule();
		$empsch = $empschedulemodel->getempschbyid($pes);
		//die(Zend_Debug::dump($empsch));
		$this->view->empsch = $empsch;
	}
}

