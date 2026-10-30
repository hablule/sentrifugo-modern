<?php



class Schedule_TimetableController extends Zend_Controller_Action
{
	private $actionadd = 1;
	private $actionupdate = 2;
	private $actiondelete = 3;
	public function preDispatch()
	{
		/*$userModel = new Timemanagement_Model_Users();
		$checkTmEnable = $userModel->checkTmEnable();

		if(!$checkTmEnable){
			$this->_redirect('error');
		}*/

		//check Time management module enable
		if (!sapp_Helper::checkTmEnable())
			$this->_redirect('error');
	}

	public function init()
	{
		$this->options = $this->getInvokeArg('bootstrap')->getOptions();

		require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
		require_once 'application/modules/default/library/utilClasses/Util.php';
	}

	public function indexAction()
	{
		try {


			$clientsModel = new Schedule_Model_Timetable();
			$call = $this->_getParam('call');

			if ($call == 'ajaxcall') {
				$this->_helper->layout->disableLayout();
			}

			$view = Zend_Layout::getMvcInstance()->getView();
			$objname = $this->_getParam('objname');
			$refresh = $this->_getParam('refresh');
			$dashboardcall = $this->_getParam('dashboardcall');

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

			$dataTmp = $clientsModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);

			array_push($data, $dataTmp);
			$this->view->dataArray = $data;
			$this->view->call = $call;

			$this->view->messages = $this->_helper->flashMessenger->getMessages();
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function isavalibleAction()
	{
		try {

			$model = new Schedule_Model_Timetable();
			$shifts = $this->_getParam('shiftid');
			$data = $model->getschshifts($shifts);

			$this->_helper->json($data);
		
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function addtimeAction()
	{
		try {

			$timetableform = new Schedule_Form_Timetable();
			$timetablemodel = new Schedule_Model_Timetable();
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$login = $auth->getStorage()->read()->id;
			}

			if ($this->_request->isPost()) {
				if ($timetableform->isValid($this->getRequest()->getPost())) {
					$name = $this->_request->getParam('name');
					$desc = $this->_request->getParam('description');
					$time_in = $this->_request->getParam('time_in');
					$time_out = $this->_request->getParam('time_out');
					$data = [
						'name' => $name,
						'description' => $desc,
						'code' => $this->_request->getParam('code'),
						'starttime' => sapp_Global::change_time($time_in, 'database'),
						'endtime' => sapp_Global::change_time($time_out, 'database'),
						'starttolerence' => (int)$this->_request->getParam("start_tolerance"),
						'endtolerance' => (int)$this->_request->getParam('end_tolerance'),
						'timecategory_id' => $this->_request->getParam('timecategory'),
						'minimumduration' => $this->_request->getParam('minimumduration'),
						'isflexible' => utlis::getcheckboxvalue($this->_request->getParam('isflexible')),
						'allowautocheckin' => utlis::getcheckboxvalue($this->_request->getParam('allowautocheckin')),
						'allowautocheckout' => utlis::getcheckboxvalue($this->_request->getParam('allowautocheckout')),
						'createdby' => $login,
						'modifiedby' => $login,
						'createddate' => date('Y-m-d H:i:s'),
						'modifieddate' => date('Y-m-d H:i:s')
					];
					$menuID = TIMETABLE;

					$tableid = $timetablemodel->saveandupdate('', $data);
					sapp_Global::logManager($menuID, $this->actionadd, $login, $tableid);
					$this->_helper->getHelper("FlashMessenger")->addMessage(['success' => 'Added Successfully']);
					$this->_redirect('schedule/timetable');
				}
			}
			$messages = $timetableform->getMessages();

			foreach ($messages as $key => $val) {
				foreach ($val as $key2 => $val2) {
					$msgarray[$key] = $val2;
					break;
				}
			}
			$this->view->msgarray = $msgarray;
			$this->view->form = $timetableform;
			$this->view->messages = $this->_helper->flashMessenger->getMessages();
			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function addpopupAction()
	{

		try {
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
			$timetableform = new Schedule_Form_Timetable();
			$timetablemodel = new Schedule_Model_Timetable();
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$login = $auth->getStorage()->read()->id;
			}

			if ($this->_request->isPost()) {
				if ($timetableform->isValid($this->getRequest()->getPost())) {
					$desc = $this->_request->getParam('description');
					$name = $this->_request->getParam('name');
					$time_in = $this->_request->getParam('time_in');
					$time_out = $this->_request->getParam('time_out');
					$data = [
						'name'=>$name,
						'description' => $desc,
						'code' => $this->_request->getParam('code'),
						'starttime' => sapp_Global::change_time($time_in, 'database'),
						'endtime' => sapp_Global::change_time($time_out, 'database'),
						'starttolerence' => (int)$this->_request->getParam("start_tolerance"),
						'endtolerance' => (int)$this->_request->getParam('end_tolerance'),
						'timecategory_id' => $this->_request->getParam('timecategory'),
						'minimumduration' => $this->_request->getParam('minimumduration'),
						'isflexible' => utlis::getcheckboxvalue($this->_request->getParam('isflexible')),
						'allowautocheckin' => utlis::getcheckboxvalue($this->_request->getParam('allowautocheckin')),
						'allowautocheckout' => utlis::getcheckboxvalue($this->_request->getParam('allowautocheckout')),
						'createdby' => $login,
						'modifiedby' => $login,
						'createddate' => date('Y-m-d H:i:s'),
						'modifieddate' => date('Y-m-d H:i:s')
					];
					$menuID = TIMETABLE;

					$tableid = $timetablemodel->saveandupdate('', $data);
					sapp_Global::logManager($menuID, $this->actionadd, $login, $tableid);
					$this->_helper->getHelper("FlashMessenger")->addMessage(['success' => 'Added Successfully']);
				}
			}
			$messages = $timetableform->getMessages();

			foreach ($messages as $key => $val) {
				foreach ($val as $key2 => $val2) {
					$msgarray[$key] = $val2;
					break;
				}
			}
			$this->view->msgarray = $msgarray;
			$this->view->form = $timetableform;
			$this->view->messages = $this->_helper->flashMessenger->getMessages();
			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function editAction()
	{
		try {
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$login = $auth->getStorage()->read()->id;
			}
			$newelement = new addformelement();
			$timetableform = new Schedule_Form_Timetable();
			$id = $this->_request->getParam('id');
			$timetableform->setAttrib('action', BASE_URL . 'schedule/timetable/edit/id/' . $id);
			$timetablemodel = new Schedule_Model_Timetable();
			$shiftdata = $timetablemodel->getschshifts($id);
			
			if (!empty($shiftdata))
				$this->_redirect('schedule/timetable');
				
			$data = $timetablemodel->getTimetableData($id);
			$timetableform->submit->setLabel('update');

			foreach ($data as $value) {
				$timetableform->addElement($newelement->addelement('checkbox', 'isactive', $value, 'isactive'));
				$timetableform->name->setValue($value['name']);
				$timetableform->description->setValue($value['description']);
				$timetableform->code->setValue($value['code']);
				$timetableform->timecategory->setValue($value['timecategory_id']);
				$timetableform->minimumduration->setValue($value['minimumduration']);
				$timetableform->time_in->setValue(date("g:i a", strtotime($value['starttime'])));
				$timetableform->time_out->setValue(date("g:i a", strtotime($value['endtime'])));
				$timetableform->time_in_tolerance->setValue("" . $value['starttolerence'] . "");
				$timetableform->time_out_tolerance->setValue("" . $value['endtolerance'] . "");
				$timetableform->allowautocheckin->setValue(utlis::getcheckboxvalue($value['allowautocheckin'], 'form'));
				$timetableform->allowautocheckout->setValue(utlis::getcheckboxvalue($value['allowautocheckout'], 'form'));
				$timetableform->isflexible->setValue(utlis::getcheckboxvalue($value['isflexible'], 'form'));
				$timetableform->id->setValue($id);
			}

			// Zend_Debug::dump($data);
			//$timetableform->description->setValue();
			if ($this->_request->isPost()) {
				if($timetableform->isValid($this->getRequest()->getPost())){
				$where = 'shift_id=' . $this->_request->getParam('id');
				$data = [
					'name' => $this->_request->getParam('name'),
					'description' => $this->_request->getParam('description'),
					'code' => $this->_request->getParam('code'),
					'starttime' => date("H:i", strtotime($this->_request->getParam('time_in'))),
					'endtime' => date("H:i", strtotime($this->_request->getParam('time_out'))),
					'starttolerence' => (int)$this->_request->getParam("time_in_tolerance"),
					'endtolerance' => (int)$this->_request->getParam('time_out_tolerance'),
					'timecategory_id' => $this->_request->getParam('timecategory'),
					'minimumduration' => $this->_request->getParam('minimumduration'),
					'isflexible' => utlis::getcheckboxvalue($this->_request->getParam('isflexible')),
					'allowautocheckin' => utlis::getcheckboxvalue($this->_request->getParam('allowautocheckin')),
					'allowautocheckout' => utlis::getcheckboxvalue($this->_request->getParam('allowautocheckout')),
					'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive')),
					'createdby' => $login,
					'modifiedby' => $login,
					'createddate' => date('Y-m-d H:i:s'),
					'modifieddate' => date('Y-m-d H:i:s')
				];

				/* $data = array(
				'name' => $this->_request->getParam('description'),
				'start_time' => date("H:i", strtotime($this->_request->getParam('time_in'))),
				'end_time' => date("H:i", strtotime($this->_request->getParam('time_out'))),
				'start_tolerance' => (int)$this->_request->getParam("time_in_tolerance"),
				'end_tolerance' => (int)$this->_request->getParam('time_out_tolerance'),
				'modified' => $login,
				'modifieddate'=>date('Y-m-d H:i:s'),
				'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive'))
			); */
				//die(json_encode($data));
				$menuID = TIMETABLE;
				$tableid = $this->_getParam('id');
				$timetablemodel->saveandupdate($where, $data);
				sapp_Global::logManager($menuID, $this->actionupdate, $login, $tableid);
				$this->_helper->getHelper("FlashMessenger")->addMessage(['success' => 'Updated Successfully']);

				$this->_redirect('schedule/timetable');
			}
			}
			$this->view->form = $timetableform;
			$this->view->messages = $this->_helper->flashMessenger->getMessages();
			//
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
		$this->view->form = $timetableform;
	}

	public function viewAction()
	{
		$form = new Schedule_Form_Timetable();
		$id = $this->_request->getParam('id');
		$form->setAttrib('action', BASE_URL . 'timetable');
		$form->submit->setLabel('Back');
		$timetablemodel = new Schedule_Model_Timetable();
		$data = $timetablemodel->getTimetableData($id);
		foreach ($data as $value) {
			$form->name->setValue($value['name']);
			$form->time_in->setValue($value['start_time']);
			$form->time_out->setValue($value['end_time']);
		}
		
		$form->description->setValue($value['description']);
		$form->time_in->setAttrib('readonly', 'readonly');
		$form->time_out->setAttrib('readonly', 'readonly');
		$this->view->schedules = $data;
		$this->view->form = $form;
	}

	public function viewallAction()
	{

		$timetablemodel = new Schedule_Model_Timetable();
		$timetables = $timetablemodel->getallshifts();
		$this->view->timetables = $timetables;
	}

	public function deleteAction()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$login = $auth->getStorage()->read()->id;
		}
		$timetablemodel = new Schedule_Model_Timeschedule();
		$id = $this->_request->getParam('objid');
		$where = 'shift_id=' . (int)$id;
		$data = [
			'isactive' => 0
		];
		//die(json_encode($where));
		$tableid = $id;
		$menuID = TIMETABLE;
		$res = $timetablemodel->deleteData($where, 'main_shift', 'shift_id');
		sapp_Global::logManager($menuID, $this->actiondelete, $login, $tableid);
		if ($res > 0)
			return $this->_helper->json(['flagtype' => 'process', 'message' => 'Deleted']);
		else
			$this->_helper->json(['flagtype' => 'timetable', 'message' => 'Deleted']);
	}

	public function getshiftnameAction()
	{
		$shiftModel = new Schedule_Model_Timetable();
		$id = $this->_getParam('id');
		if ($id != null)
			$this->_helper->json($shiftModel->getShiftName($id));

		$this->_helper->json(null);
	}
	
}
