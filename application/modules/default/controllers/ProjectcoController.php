<?php

class Default_ProjectcoController extends Zend_Controller_Action
{
	public function init()
	{
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';
        $util = new utlis();	}
	public function indexAction()
	{

		$model = new Default_Model_Projectco();

		$call = $this->_getParam('call');
		if ($call == 'ajaxcall')
			$this->_helper->layout->disableLayout();

		$view = Zend_Layout::getMvcInstance()->getView();
		$objname = $this->_getParam('objname');
		$refresh = $this->_getParam('refresh');
		$dashboardcall = $this->_getParam('dashboardcall');

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
			$by = 'pco.modifieddate';
			$pageNo = 1;
			$searchData = '';
			$searchQuery = '';
			$searchArray = '';
		} else {
			$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
			$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'pco.modifieddate';
			if ($dashboardcall == 'Yes')
				$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
			else
				$perPage = $this->_getParam('per_page', PERPAGE);
			$pageNo = $this->_getParam('page', 1);
			/** search from grid - START **/
			$searchData = $this->_getParam('searchData');
			$searchData = rtrim($searchData, ',');
			/** search from grid - END **/
		}
		$dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);

		array_push($data, $dataTmp);
		$this->view->dataArray = $data;
		$this->view->call = $call;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}


	public function addAction()
	{
		try {
			//code...

			$auth = Zend_Auth::getInstance();

			if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;            }
			$bupermission = '';
			$bupermission = sapp_Global::_checkprivileges(BUSINESSUNITS,$loginuserGroup,$loginuserRole,'add');
			$this->view->bupermission = $bupermission;
			$divpermission = '';
			$divpermission = sapp_Global::_checkprivileges(DIVISION,$loginuserGroup,$loginuserRole,'add');
			$this->view->divpermission = $divpermission;
			$deppermission = '';
			$deppermission = sapp_Global::_checkprivileges(DEPARTMENTS,$loginuserGroup,$loginuserRole,'add');
			$this->view->deppermission = $deppermission;
			$secpermission = '';
			$secpermission = sapp_Global::_checkprivileges(SECTION,$loginuserGroup,$loginuserRole,'add');
			$this->view->secpermission = $secpermission;
			$propermission = '';
			$propermission = sapp_Global::_checkprivileges(PROJECT,$loginuserGroup,$loginuserRole,'add');
			$this->view->propermission = $propermission;
			$msgarray = array();

			$controllername = 'projectco';

			$form = new Default_Form_Projectco();

			$model = new Default_Model_projectco();
			$modelpro = new Default_Model_Project();
			// $form->setAction(BASE_URL.'division/add');
			$modelcompany = new Default_Model_Organisationinfo();
			$modelsbunit = new Default_Model_Businessunits();
			$modelemp = new Default_Model_Employee();
			$modeldivition = new Default_Model_division();
			$devisiondatas = $modeldivition->getdevisionlist();
			$dptmodel = new Default_Model_Departments();
			$empdatas = $modelemp->getEmplist();
			$sbunitdatas = $modelsbunit->getsbunit();
			$compdatas = $modelcompany->getSelecteOrgRecords();
			$dptdata = $dptmodel->getdepartmentlist();
			$secdata = $model->getsectionlist();
			$projectdata = $modelpro->getprojectlist();



			$form->company_id->addMultiOption('', 'Select...');
			$form->project_id->addMultiOption('', 'Select...');

			$form->sbunit_id->addMultiOption('', 'Select...');

			$form->division_id->addMultiOption('', 'Select...');
			$form->departement_id->addMultiOption('', 'Select...');

			$form->section_id->addMultiOption('', 'Select...');

			$form->associateproject_id->addMultiOption('', 'Select...');




			if (empty($compdatas)) {
				$msgarray['company_id'] = 'Company is not configured yet.';
			} else {
				$form->company_id->addMultiOptions(array($compdatas['company_id'] => $compdatas['name']));
			}
			if (empty($sbunitdatas)) {
				$msgarray['sbunit_id'] = 'Bussiness Unit is not configured yet.';
			} else {
				foreach ($sbunitdatas as $sbunitdata) {
					$form->sbunit_id->addMultiOptions(array($sbunitdata['sbunit_id'] => $sbunitdata['name']));
				}
			}

			if (empty($devisiondatas)) {
				$msgarray['division_id'] = 'Division is not configured yet.';
			} else {
				foreach ($devisiondatas as $devisiondata) {
					$form->division_id->addMultiOptions(array($devisiondata['division_id'] => $devisiondata['name']));
				}
			}
			if (empty($dptdata)) {
				$msgarray['departement_id'] = 'Department is not configured yet.';
			} else {
				foreach ($dptdata as $dptdatas) {
					$form->departement_id->addMultiOptions(array($dptdatas['department_id'] => $dptdatas['name']));
				}
			}
			if (empty($secdata)) {
				$msgarray['section_id'] = 'Section is not configured yet.';
			} else {
				foreach ($secdata as $secdatas) {
					$form->section_id->addMultiOptions(array($secdatas['section_id'] => $secdatas['name']));
				}
			}
			if (empty($projectdata)) {
				$msgarray['project_id'] = 'Project is not configured yet.';
				$msgarray['associateproject_id'] = 'Project is not configured yet.';
			} else {
				foreach ($projectdata as $projectdatas) {
					$form->associateproject_id->addMultiOptions(array($projectdatas['project_id'] => $projectdatas['name']));
					$form->project_id->addMultiOptions(array($projectdatas['project_id'] => $projectdatas['name']));
				}
			}
			if ($this->getRequest()->getPost()) {
				if ($form->isValid($this->_request->getPost())) {
					if ($this->_request->getParam('company_id') == '' && $this->_request->getParam('sbunit_id') == '' && $this->_request->getParam('division_id') == '' && $this->_request->getParam('departement_id') == '' && $this->_request->getParam('section_id') == '' && $this->_request->getParam('associateproject_id') == '') {
						$msgarray['allfk'] = 'choose one from Company,Bussiness Unit,division,Department or Section';
						
					} else {
						$name = $this->_request->getParam('name');
						$code = $this->_request->getParam('code');
						$description = $this->_request->getParam('description');
						$company_id = $this->_request->getParam('company_id');
						$sbunit_id	 = $this->_request->getParam('sbunit_id');
						$division_id = $this->_request->getParam('division_id');
						$departement_id = $this->_request->getParam('departement_id');
						$section_id = $this->_request->getParam('section_id');
						$project_id = $this->_request->getParam('project_id');

						$associateproject_id = $this->_request->getParam('associateproject_id');

						$date = new Zend_Date();
						$actionflag = '';
						$tableid  = '';
						$data = array(
							'project_id' => $project_id,
							'description' => $description,
							'company_id' => $company_id,
							'sbunit_id' => $sbunit_id,
							'division_id' => $division_id,
							'department_id' => $departement_id,
							'section_id' => $section_id,
							'associateproject_id' => $associateproject_id
						);

						$data['createdby'] = $loginUserId;
						$data['createddate'] = gmdate("Y-m-d H:i:s");
						$data['isactive'] = "Y";
						$where = '';
						$actionflag = 1;
						$menuID = PROJECTCO;
						$Id = $model->SaveorUpdateData($data, $where);
						$tableid = $Id;
						$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $tableid);
						$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Project co   added successfully."));
						$this->view->messages = $this->_helper->flashMessenger->getMessages();


						$this->_redirect('projectco');
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
			$this->view->msgarray = $msgarray;

			$this->view->controllername = $controllername;
			$this->view->form = $form;
			$this->view->ermsg = '';
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	/////////////////////////////////////////////////////
	public function editAction()
	{
		try {
			//code...

			$auth = Zend_Auth::getInstance();

			if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;            }
			$bupermission = '';
			$bupermission = sapp_Global::_checkprivileges(BUSINESSUNITS,$loginuserGroup,$loginuserRole,'add');
			$this->view->bupermission = $bupermission;
			$divpermission = '';
			$divpermission = sapp_Global::_checkprivileges(DIVISION,$loginuserGroup,$loginuserRole,'add');
			$this->view->divpermission = $divpermission;
			$deppermission = '';
			$deppermission = sapp_Global::_checkprivileges(DEPARTMENTS,$loginuserGroup,$loginuserRole,'add');
			$this->view->deppermission = $deppermission;
			$secpermission = '';
			$secpermission = sapp_Global::_checkprivileges(SECTION,$loginuserGroup,$loginuserRole,'add');
			$this->view->secpermission = $secpermission;
			$propermission = '';
			$propermission = sapp_Global::_checkprivileges(PROJECT,$loginuserGroup,$loginuserRole,'add');
			$this->view->propermission = $propermission;
			$id = $this->_request->getParam("id");

			$msgarray = array();

			$controllername = 'projectco';

			$form = new Default_Form_Projectco();
			// $form->project_id->removeValidator('Db_NoRecordExists');

			$model = new Default_Model_Projectco();
			$modelpro = new Default_Model_Project();

			// $form->setAction(BASE_URL.'division/add');
			$modelcompany = new Default_Model_Organisationinfo();
			$modelsbunit = new Default_Model_Businessunits();
			$modelemp = new Default_Model_Employee();
			$modeldivition = new Default_Model_division();
			$devisiondatas = $modeldivition->getdevisionlist();
			$dptmodel = new Default_Model_Departments();
			$empdatas = $modelemp->getEmplist();
			$sbunitdatas = $modelsbunit->getsbunit();
			$compdatas = $modelcompany->getSelecteOrgRecords();
			$dptdata = $dptmodel->getdepartmentlist();
			$secdata = $model->getsectionlist();
			$projectdata = $modelpro->getprojectlist();
			$form->company_id->addMultiOption('', 'Select...');
			$form->project_id->addMultiOption('', 'Select...');
			$form->sbunit_id->addMultiOption('', 'Select...');
			$form->division_id->addMultiOption('', 'Select...');
			$form->departement_id->addMultiOption('', 'Select...');
			$form->section_id->addMultiOption('', 'Select...');
			$form->associateproject_id->addMultiOption('', 'Select...');



			if (empty($compdatas)) {
				$msgarray['company_id'] = 'Company is not configured yet.';
			} else {
				$form->company_id->addMultiOptions(array($compdatas['company_id'] => $compdatas['name']));
			}
			if (empty($sbunitdatas)) {
				$msgarray['sbunit_id'] = 'Bussiness Unit is not configured yet.';
			} else {
				foreach ($sbunitdatas as $sbunitdata) {
					$form->sbunit_id->addMultiOptions(array($sbunitdata['sbunit_id'] => $sbunitdata['name']));
				}
			}

			if (empty($devisiondatas)) {
				$msgarray['division_id'] = 'Division is not configured yet.';
			} else {
				foreach ($devisiondatas as $devisiondata) {
					$form->division_id->addMultiOptions(array($devisiondata['division_id'] => $devisiondata['name']));
				}
			}
			if (empty($dptdata)) {
				$msgarray['departement_id'] = 'Department is not configured yet.';
			} else {
				foreach ($dptdata as $dptdatas) {
					$form->departement_id->addMultiOptions(array($dptdatas['department_id'] => $dptdatas['name']));
				}
			}
			if (empty($secdata)) {
				$msgarray['section_id'] = 'Section is not configured yet.';
			} else {
				foreach ($secdata as $secdatas) {
					$form->section_id->addMultiOptions(array($secdatas['section_id'] => $secdatas['name']));
				}
			}
			if (empty($projectdata)) {
				$msgarray['project_id'] = 'Project is not configured yet.';
				$msgarray['associateproject_id'] = 'Project is not configured yet.';
			} else {
				foreach ($projectdata as $projectdatas) {
					$form->associateproject_id->addMultiOptions(array($projectdatas['project_id'] => $projectdatas['name']));
					$form->project_id->addMultiOptions(array($projectdatas['project_id'] => $projectdatas['name']));
				}
			}

			$getparticulardivdata = $model->getParicularoprojectco($id);

			//   die(json_encode($getparticulardivdata));

			if ($getparticulardivdata['comp_id'] != NULL) {

				$form->company_id->setValue($getparticulardivdata['comp_id']);
			}
			if ($getparticulardivdata['division_id'] != NULL) {
				$form->division_id->setValue($getparticulardivdata['division_id']);
			}
			if ($getparticulardivdata['departement_id'] != NULL) {
				$form->departement_id->setValue($getparticulardivdata['departement_id']);
			}
			if ($getparticulardivdata['section_id'] != NULL) {
				$form->section_id->setValue($getparticulardivdata['section_id']);
			}
			if ($getparticulardivdata['sbunit_id'] != NULL) {
				$form->sbunit_id->setValue($getparticulardivdata['sbunit_id']);
			}
			if ($getparticulardivdata['associateproject_id'] != NULL) {
				$form->associateproject_id->setValue($getparticulardivdata['associateproject_id']);
			}
			$form->project_id->setValue($getparticulardivdata['project_id']);
			$form->description->setValue($getparticulardivdata['description']);
			$form->isactive->setValue(utlis::getcheckboxvalue($getparticulardivdata['isactive'], 'form'));

			if ($this->getRequest()->getPost()) {
				if ($form->isValid($this->_request->getPost())) {
					if ($this->_request->getParam('company_id') == '' && $this->_request->getParam('sbunit_id') == '' && $this->_request->getParam('division_id') == ''  && $this->_request->getParam('departement_id') == '' && $this->_request->getParam('section_id') == '' && $this->_request->getParam('associateproject_id') == '') {
						$msgarray['allfk'] = 'choose one from Company,Bussiness Unit,Division, Depaartment or Parent Section';
						$this->view->msgarray = $msgarray;
					} else {
						$id = $this->_request->getParam("id");

						$description = $this->_request->getParam('description');
						$company_id = $this->_request->getParam('company_id');
						$sbunit_id	 = $this->_request->getParam('sbunit_id	');
						$division_id = $this->_request->getParam('division_id');
						$departement_id = $this->_request->getParam('departement_id');
						$section_id = $this->_request->getParam('section_id');
						$project_id = $this->_request->getParam('project_id');
						$associateproject_id = $this->_request->getParam('associateproject_id');

						$isactive = (utlis::getcheckboxvalue($this->_request->getParam('isactive'), 'database'));

						$date = new Zend_Date();
						$actionflag = '';
						$tableid  = '';
						$data = array(
							'description' => $description,
							'project_id' => $project_id,
							'company_id' => $company_id,
							'sbunit_id' => $sbunit_id,
							'division_id' => $division_id,
							'department_id' => $departement_id,
							'section_id' => $section_id,
							'associateproject_id' => $associateproject_id,


							'isactive' => $isactive
						);

						$data['modifiedby'] = $loginUserId;
						$data['modifieddate'] = gmdate("Y-m-d H:i:s");
						$where = "projectco_id=" . $id;
						$actionflag = 2;
						$menuID =  PROJECTCO;
						$Id = $model->SaveorUpdateData($data, $where);
						$tableid = $Id;
						$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $tableid);
						$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Project Co Updated successfully."));
						$this->view->messages = $this->_helper->flashMessenger->getMessages();


						$this->_redirect('projectco');
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
			$this->view->msgarray = $msgarray;

			$this->view->controllername = $controllername;
			$this->view->form = $form;
			$this->view->ermsg = '';
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if ($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$objName = 'departments';

		$form = new Default_Form_departments();

		$form->removeElement("submit");


		$model = new Default_Model_Departments();
		try {
			if ($id) {
				if (is_numeric($id) && $id > 0) {

					$data = $model->getParicularDepartmentId($id);

					//die(json_encode($data));
					if (!empty($data) && $data != "norows") {
						$form->populate($data);
						$this->view->form = $form;
						$this->view->controllername = $objName;
						$this->view->data = $data;
						$this->view->id = $id;
						$this->view->ermsg = '';
					} else {

						$this->view->ermsg = 'nodata';
					}
				} else {
					$this->view->ermsg = 'nodata';
				}
			} else {
				$this->view->ermsg = 'nodata';
			}
		} catch (Exception $e) {
			$this->view->ermsg = 'nodata';
		}
	}

	public function addpopupAction()
	{
		try {
			
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		require_once 'application/modules/default/library/utilClasses/Util.php';
		$util=new utlis();
		$form = new Default_Form_Projectco();
		$model = new Default_Model_Projectco();
		$modelpro = new Default_Model_Project();
		$smodel = new Default_Model_Section();
		// $form->setAction(BASE_URL.'division/add');
		$modelcompany = new Default_Model_Organisationinfo();
		$modelsbunit = new Default_Model_Businessunits();
		
		$modeldivition = new Default_Model_division();
		$devisiondatas = $modeldivition->getdevisionlist();
		$dptmodel = new Default_Model_Departments();
	
		$sbunitdatas = $modelsbunit->getsbunit();
		$compdatas = $modelcompany->getSelecteOrgRecords();
		$dptdata = $dptmodel->getdepartmentlist();
		$secdata = $smodel->getsectionlist();
		$projectdata = $modelpro->getprojectlist();
		$auth = Zend_Auth::getInstance();
		$compdatas = array($compdatas);
		$form->setAttrib('action',BASE_URL.'projectco/addpopup');
		$form->division_id = $util->populate_select($form->division_id,$devisiondatas,'division_id','name','select');
		$form->project_id = $util->populate_select($form->project_id,$projectdata,'project_id','name','select');
		$form->sbunit_id = $util->populate_select($form->sbunit_id,$sbunitdatas,'sbunit_id','name','select');
		$form->company_id = $util->populate_select($form->company_id,$compdatas,'company_id','name','select');
		$form->section_id = $util->populate_select($form->section_id,$secdata,'section_id','name','select');
		$form->departement_id = $util->populate_select($form->departement_id,$dptdata,'department_id','name','select');
		$form->associateproject_id = $util->populate_select($form->associateproject_id,$projectdata,'project_id','name','select');
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if ($this->getRequest()->isPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {
				$data = array(
					'project_id' => $this->_getParam('project_id'),
					'description' => $this->_getParam('description'),
					'company_id' => $this->_getParam('company_id'),
					'sbunit_id' => $this->_getParam('sbunit_id'),
					'division_id' => $this->_getParam('division_id'),
					'department_id' => $this->_getParam('department_id'),
					'section_id' => $this->_getParam('section_id'),
					'associateproject_id' => $this->_getParam('associateproject_id')
				);

				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$data['isactive'] = "Y";
				$model->SaveorUpdateData($data,'');
				$bData = $model->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($bData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['section_id'], $record['name']);
				}
				$this->view->bData = $opt;
				$close = 'close';
				$this->view->controllername='projectco';
				$this->view->popup=$close;
			}
			else{
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
		$this->view->form = $form;
		//code...
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
	}
	}

}
