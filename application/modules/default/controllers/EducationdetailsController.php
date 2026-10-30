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

class Default_EducationdetailsController extends Zend_Controller_Action
{

	private $options;
	public function preDispatch()
	{

	}

	public function init()
	{
		$employeeModel = new Default_Model_Employee();
		$this->_options= $this->getInvokeArg('bootstrap')->getOptions();
	}
	public function indexAction()
	{
		
		

		 	$userID="";$conText = "";$objName = 'educationdetails';
		 	$auth = Zend_Auth::getInstance();
		 	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
					$loginUserGroup = $auth->getStorage()->read()->group_id;
		 	}
		 	$userid = $this->getRequest()->getParam('userid');

		 	$call = $this->_getParam('call');
		 	if($call == 'ajaxcall')
		 	{
		 		$this->_helper->layout->disableLayout();
		 		$userID = ($this->_getParam('unitId') !='')? $this->_getParam('unitId'):$this->_getParam('userid');
		 		$conText = ($this->_getParam('context') !='')? $this->_getParam('context'):$this->getRequest()->getParam('context');
		 	}
		 	if($userid == '') $userid = $userID;
		 	$Uid = $userid ?: $userID;

		 	$educationdetailsModel = new Default_Model_Educationdetails();
		 	$employeeModal = new Default_Model_Employee();
		 	try
		 	{
			    if($Uid && is_numeric($Uid) && $Uid>0)
				{
					$usersModel = new Default_Model_Users();
					$empdata = $employeeModal->getActiveEmployeeData($Uid);
					$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
					if($empdata == 'norows')
					{
						$this->view->rowexist = "norows";
						$this->view->empdata = "";
					}
					else
					{
						$this->view->rowexist = "rows";
						if(!empty($empdata))
						{

							$view = Zend_Layout::getMvcInstance()->getView();
							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = [];$searchQuery = '';$searchArray = [];
							$tablecontent = '';
							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'e.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';	$searchArray = [];

							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'e.modifieddate';
								if($dashboardcall == 'Yes')
								$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
								else
								$perPage = $this->_getParam('per_page',PERPAGE);

								$pageNo = $this->_getParam('page', 1);
								$searchData = $this->_getParam('searchData');
								$searchData = rtrim($searchData,',');
							}
							$dataTmp = $educationdetailsModel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$Uid,$conText);

							array_push($data,$dataTmp);
							$this->view->id=$userid;
							$this->view->controllername = $objName;
							$this->view->dataArray = $data;
							$this->view->employeedata = $employeeData[0];
							$this->view->call = $call;
							$this->view->messages = $this->_helper->flashMessenger->getMessages();
						}
						$this->view->empdata = $empdata;
					}
				}	
				else
				{
				   $this->view->rowexist = "norows";
				}	
		 	}
		 	catch(Exception $e)
		 	{
		 		$this->view->rowexist = "norows";
		 	}
		
	}

	public function editAction()
	{
		try {
		
		
		 	$userID="";$objName = 'educationdetails';
		 	$auth = Zend_Auth::getInstance();
		 	if($auth->hasIdentity()){
		 		$loginUserId = $auth->getStorage()->read()->id;
		 		$loginUserRole = $auth->getStorage()->read()->emprole;
		 		$loginUserGroup = $auth->getStorage()->read()->group_id;
		 	}
		 	$userid = $this->getRequest()->getParam('userid');
		 	$conText = "";
		 	$call = $this->_getParam('call');
		 	if($call == 'ajaxcall')
		 	{
		 		$this->_helper->layout->disableLayout();
		 		$userID = ($this->_getParam('unitId') !='')? $this->_getParam('unitId'):$this->_getParam('userid');
		 		$conText = ($this->_getParam('context') !='')? $this->_getParam('context'):$this->getRequest()->getParam('context');
		 	}
		 	if($userid == '') $userid = $userID;
		 	$Uid = $userid ?: $userID;
		 	$educationdetailsModel = new Default_Model_Educationdetails();
		 	$employeeModal = new Default_Model_Employee();
		 	try
		 	{
			    if($Uid && is_numeric($Uid) && $Uid>0)
				{
					$usersModel = new Default_Model_Users();
					$empdata = $employeeModal->getActiveEmployeeData($Uid);
					$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
					if($empdata == 'norows')
					{
						$this->view->rowexist = "norows";
						$this->view->empdata = "";
					}
					else
					{
						$this->view->rowexist = "rows";
						if(!empty($empdata))
						{

							$view = Zend_Layout::getMvcInstance()->getView();
							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = [];$searchQuery = '';$searchArray = [];
							$tablecontent = '';
							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'e.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';	$searchArray = [];
							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'e.modifieddate';
								if($dashboardcall == 'Yes')
								$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
								else
								$perPage = $this->_getParam('per_page',PERPAGE);
								$pageNo = $this->_getParam('page', 1);
								$searchData = $this->_getParam('searchData');
								$searchData = rtrim($searchData,',');
							}
							
							$dataTmp = $educationdetailsModel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$Uid,$conText,'edit');
						
							array_push($data,$dataTmp);
							$this->view->id=$userid;
							$this->view->controllername = $objName;
							$this->view->dataArray = $data;
							$this->view->employeedata = $employeeData[0];
							$this->view->call = $call;
							$this->view->messages = $this->_helper->flashMessenger->getMessages();
						}
						$this->view->empdata = $empdata;
					}
				}	
				else
				{
				   $this->view->rowexist = "norows";
				}	
		 	}
		 	catch(Exception $e)
		 	{
		 		$this->view->rowexist = "norows";
		 	}
		
			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function viewAction()
	{
		
		    $auth = Zend_Auth::getInstance();
			if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
			$loginUserRole = $auth->getStorage()->read()->emprole;
			$loginUserGroup = $auth->getStorage()->read()->group_id;
			}
		 	$userID = '';
			$empdata=[];
			$conText = "";
			$objName = 'educationdetails';
		 	$userid = $this->getRequest()->getParam('userid');
		 	$call = $this->_getParam('call');
		 	if($call == 'ajaxcall')
		 	{
		 		$this->_helper->layout->disableLayout();
		 		$userID = ($this->_getParam('unitId') !='')? $this->_getParam('unitId'):$this->_getParam('userid');
		 		$conText = ($this->_getParam('context') !='')? $this->_getParam('context'):$this->getRequest()->getParam('context');
		 	}
		 	if($userid == '') $userid = $userID;
             $Uid = $userid ?: $userID;
		 	$educationdetailsModel = new Default_Model_Educationdetails();
		 	$employeeModal = new Default_Model_Employee();
		 	try
		 	{
			    if($Uid && is_numeric($Uid) && $Uid>0 )
				{
					$usersModel = new Default_Model_Users();
					$empdata = $employeeModal->getActiveEmployeeData($Uid);
					$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
					if($empdata == 'norows')
					{
						$this->view->rowexist = "norows";
						$this->view->empdata = "";
					}
					else
					{
						$this->view->rowexist = "rows";
						if(!empty($empdata))
						{
							$view = Zend_Layout::getMvcInstance()->getView();
							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = [];$searchQuery = '';$searchArray = [];
							$tablecontent = '';
							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'e.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';	$searchArray = [];

							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'e.modifieddate';
								if($dashboardcall == 'Yes')
								$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
								else
								$perPage = $this->_getParam('per_page',PERPAGE);

								$pageNo = $this->_getParam('page', 1);
								$searchData = $this->_getParam('searchData');
								$searchData = rtrim($searchData,',');
							}
							$dataTmp = $educationdetailsModel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$userid,$conText);

							array_push($data,$dataTmp);
							$this->view->id=$userid;
							$this->view->controllername = $objName;
							$this->view->dataArray = $data;
							$this->view->employeedata = $employeeData[0];
							$this->view->call = $call;
							$this->view->messages = $this->_helper->flashMessenger->getMessages();
						}
						$this->view->empdata = $empdata;
					}
				}	
				else
				{
				   $this->view->rowexist = "norows";
				}	
		 	}
		 	catch(Exception $e)
		 	{
		 		$this->view->rowexist = "norows";
		 	}
		
	}

	public function addpopupAction()
	{
		try {
			
		$auth = Zend_Auth::getInstance();
		
			if($auth->hasIdentity())
			{
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$studyfieldaddpermission = sapp_Global::_checkprivileges(STUDYFIELD,$loginuserGroup,$loginuserRole,'add');
			$studyleveladdpermission = sapp_Global::_checkprivileges(STUDYLEVEL,$loginuserGroup,$loginuserRole,'add');
			$this->view->studyfieldaddpermission = $studyfieldaddpermission;
			$this->view->studyleveladdpermission = $studyleveladdpermission;
		$userId = $this->getRequest()->getParam('unitId');

		// For open the form in popup...
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$educationDetailsform = new Default_Form_Educationdetails();
		$instituation_model = new Default_Model_institution();
		$studylevel_model = new Default_Model_studylevel();
		$studyfield_model = new Default_Model_studyfield();
		$educationlevelcodemodel = new Default_Model_Educationlevelcode();
		$msgarray = [];
		$emptyFlag = '';
		$educationlevelArr = $educationlevelcodemodel->getEducationlevelData();
		$studylevelArr = $studylevel_model->GetStudylevel();
		$institutionArr = $instituation_model->Getinstitution();
		$studyfieldArr = $studyfield_model->GetStudy();
  
		

		$educationDetailsform->institution->addMultiOption('','Select Institution');

		if (!empty($institutionArr)) {
			foreach ($institutionArr as $value) {
				$educationDetailsform->institution->addMultiOption($value['institution_id'],$value['name']);
			}
		}
		$educationDetailsform->studylevel->addMultiOption('','Select Study Level');
		if (!empty($studylevelArr)) {
			foreach ($studylevelArr as $value) {
				$educationDetailsform->studylevel->addMultiOption($value['studylevel_id'],$value['name']);
			}
		}
		$educationDetailsform->studyfield->addMultiOption('','Select Study Field');
		if (!empty($studyfieldArr)) {
			foreach ($studyfieldArr as $value) {
				$educationDetailsform->studyfield->addMultiOption($value['studyfield_id'],$value['name']);
			}
		}
			
		$educationDetailsform->setAttrib('action',BASE_URL.'educationdetails/addpopup/unitId/'.$userId);
		$this->view->form = $educationDetailsform;
		$this->view->msgarray = $msgarray;
		$this->view->emptyFlag = $emptyFlag;

		if($this->getRequest()->getPost())
		{
			$result = $this->save($educationDetailsform,$userId);
			$this->view->form = $educationDetailsform;
			$this->view->msgarray = $result;
		}
		//code...
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
	}

	}
	public function viewpopupAction()
    {  
		try {
			
		
		$form = new Default_Form_Educationdetails();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
         try {
			
        $model = new Default_Model_Educationdetails();
		$id = $this->_getParam('id');
        
        $data=$model->geteducationdetailsview($id);
        $this->view->data = $data;
		$this->form=$form;
		$this->view->org_image = $data[0]['attention'];
			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		 } 
		 //code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
    }

	public function editpopupAction()
	{
		try {
			$auth = Zend_Auth::getInstance();
			if($auth->hasIdentity())
			{
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$studyfieldaddpermission = sapp_Global::_checkprivileges(STUDYFIELD,$loginuserGroup,$loginuserRole,'add');
			$studyleveladdpermission = sapp_Global::_checkprivileges(STUDYLEVEL,$loginuserGroup,$loginuserRole,'add');
			$this->view->studyfieldaddpermission = $studyfieldaddpermission;
			$this->view->studyleveladdpermission = $studyleveladdpermission;
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$id = $this->getRequest()->getParam('id');	//Id (PK) from form URL
		$user_id = $this->getRequest()->getParam('unitId');	//This is User_id taking from URL set to form...
		$educationDetailsform = new Default_Form_Educationdetails();
		$educationDetailsModel = new Default_Model_Educationdetails();
		$instituation_model = new Default_Model_institution();
		$studylevel_model = new Default_Model_studylevel();
		$studyfield_model = new Default_Model_studyfield();
		
		$studylevelArr = $studylevel_model->GetStudylevel();
		$institutionArr = $instituation_model->Getinstitution();
		$studyfieldArr = $studyfield_model->GetStudy();
		if($id)
		{
			$data = $educationDetailsModel->geteducationdetailsRecord($id);
			
			$educationDetailsform->institution->addMultiOption('','Select Study Level');
			if (!empty($institutionArr)) {
				foreach ($institutionArr as $value) {
					$educationDetailsform->institution->addMultiOption($value['institution_id'],$value['name']);
				}
			}
			$educationDetailsform->studylevel->addMultiOption('','Select Study Level');
			if (!empty($studylevelArr)) {
				foreach ($studylevelArr as $value) {
					$educationDetailsform->studylevel->addMultiOption($value['studylevel_id'],$value['name']);
				}
			}
			$educationDetailsform->studyfield->addMultiOption('','Select Study Field');
			if (!empty($studyfieldArr)) {
				foreach ($studyfieldArr as $value) {
					$educationDetailsform->studyfield->addMultiOption($value['studyfield_id'],$value['name']);
				}
			}
			
			$educationDetailsform->setDefault("id",$data[0]["id"]);
			$educationDetailsform->setDefault("institution_name",$data[0]["institution_name"]);
			$educationDetailsform->setDefault("institution",$data[0]["institution_id"]);
			$from_date = sapp_Global::change_date($data[0]['start_date'], 'view');
			$educationDetailsform->setDefault('from_date', $from_date);
			$to_date = sapp_Global::change_date($data[0]['end_date'], 'view');
			$educationDetailsform->setDefault('to_date', $to_date);
			$educationDetailsform->setDefault("status",$data[0]["status"]);
			$educationDetailsform->setDefault("studylevel",$data[0]["studylevel_id"]);
			$educationDetailsform->setDefault("studyfield_name",$data[0]['studyfield']);
			$educationDetailsform->setDefault("studyfield",$data[0]['studyfield_id']);
			$educationDetailsform->setAttrib('action',BASE_URL.'educationdetails/editpopup/id/'.$id.'/unitId/'.$user_id);
			$educationDetailsform->setDefault("emp_id",$user_id);
			$educationDetailsform->setDefault("totalscore",$data[0]['totalscore']);
			$educationDetailsform->setDefault("summaryofcourse",$data[0]['summaryofcourse']);
			$educationDetailsform->setDefault("description",$data[0]["description"]);
			$educationDetailsform->setDefault("maxachscore",$data[0]['maxachievablescore']);
			$this->view->org_image = $data[0]['attestation'];
			$this->view->id=$user_id;

		}
		$educationDetailsform->setAttrib('action',BASE_URL.'educationdetails/editpopup/id/'.$id.'/unitId/'.$user_id);
		$this->view->form = $educationDetailsform;
		if($this->getRequest()->getPost())
		{
			$result = $this->save($educationDetailsform,$user_id);
			$this->view->msgarray = $result;
		}
		//code...
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
	}
	}

	public function save($educationDetailsform,$userid)
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$imagerror = $this->_request->getParam('imgerr');
		$imagepath = $this->_request->getParam('attestation_image_value');
		$imgerrmsg = $this->_request->getParam('imgerrmsg');

		$flag = 'true';
		if (isset($imagepath) && $imagepath != '') {

			$imageArr = explode('.', $imagepath);
			if (sizeof($imageArr) > 1) {
				$imagename = $imageArr[0];
				$imageext = $imageArr[1];
				$extArr = ['gif', 'jpg', 'jpeg', 'png'];
				if (!in_array($imageext, $extArr)) {
					$msgarray['attestation_image_value'] = 'Please upload an appropriate image file.';
					$flag = 'false';
				}
			} else {
				$msgarray['attestation_image_value'] = 'Please upload an appropriate image file.';
				$flag = 'false';
			}
		}
		if ($imagerror == 'error') {
			if ($imgerrmsg != '' && $imgerrmsg != 'undefined')
				$msgarray['attestation_image_value'] = $imgerrmsg;
			else
				$msgarray['attestation_image_value'] = 'Please upload an appropriate image file.';
			$flag = 'false';
		}
		if($educationDetailsform->isValid($this->_request->getPost())){
			$educationDetailsModel = new Default_Model_Educationdetails();
			$id = $this->getRequest()->getParam('id');
			$user_id = $userid;
		
			$path = IMAGE_UPLOAD_PATH_SKILL;
						$imagepath = $this->_request->getParam('attestation_image_value');
						$filecopy = 'success';
						if ($imagepath != '') {
							$filecopy = 'error';
							if (file_exists(SKILL_PREVIEW_UPLOAD_PATH . '//' . $imagepath)) {
								try {
									if (copy(SKILL_PREVIEW_UPLOAD_PATH . '//' . $imagepath, $path . '//' . $imagepath))
										$filecopy = 'success';
									unlink(SKILL_PREVIEW_UPLOAD_PATH . '//' . $imagepath);
								} catch (Exception $e) {
									echo $msgarray['attestation_image_value'] = $e->getFile();
									exit;
								}
							}
						}
			if($user_id == "")	$user_id = $loginUserId;
			$educationlevel = $this->_request->getParam('educationlevel');
			$institution_name = $this->_request->getParam('institution_name','(NULL)');
			$from_date = $this->_request->getParam('from_date',null);
			$to_date = $this->_request->getParam('to_date',null);
			$status = $this->_request->getParam('status');
			$institution = $this->_request->getParam('institution');
			$studyfield_name = $this->_request->getParam('studyfield_name');
			$studyfield = $this->_request->getParam('studyfield','(NULL)');
			$description = $this->_request->getParam('description');
			$studylevel = $this->_request->getParam('studylevel');
			$summaryofcourse = $this->_request->getParam('summaryofcourse');
			$totalscore = $this->_request->getParam('totalscore');
			$maxachiv = $this->_request->getParam('maxachscore');
			$date = new Zend_Date();
			$actionflag = '';
			$tableid  = '';

			$data = [		 'emp_id'=>$user_id,
				                 'institution_name'=>$institution_name,
								 'studyfield'=>$studyfield_name,
								 'institution_id'=>$institution,
								 'description'=>$description,
								 'studylevel_id'=>$studylevel,
								 'start_date'=>sapp_Global::change_date($from_date, 'database'),					 
				      			 'end_date'=>sapp_Global::change_date($to_date, 'database'),
								 'status'=>$status,
								 'summaryofcourse'=>$summaryofcourse,
								 'totalscore'=>$totalscore,
								 'maxachievablescore'=>$maxachiv,
								 'studyfield_id'=>$studyfield,
								 'modifiedby'=>$loginUserId,
								 'attestation'=>$imagepath,
			                     'modifieddate'=>gmdate("Y-m-d H:i:s")
			];
			
			if($id!=''){
				$where = ['id=?'=>$id];
				$actionflag = 2;
			}
			else
			{
				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$data['isactive'] = "Y";
				$where = '';
				$actionflag = 1;
			}
			
			$Id = $educationDetailsModel->SaveorUpdateEducationDetails($data, $where);
			if($Id == 'update')
			{
				$tableid = $id;
				$this->view->successmessage = 'Employee education details updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Employee education details added successfully.';
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'educationdetails';
		}else
		{
			$messages = $educationDetailsform->getMessages();
			foreach ($messages as $key => $val)
			{
				foreach($val as $key2 => $val2)
				{
					$msgarray[$key] = $val2;
					break;
				}
			}
			return $msgarray;
		}

	}

	public function uploadpreviewAction()
	{
		try {
			//code...

			$result = $this->imageupload();

			$this->_helper->json($result);
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function imageupload()
	{
		$savefolder = EDUCATION_PREVIEW_UPLOAD_PATH;		// folder for upload



		$max_size = 1024;			// maxim size for image file, in KiloBytes

		// Allowed image types
		//$allowtype = array('gif', 'jpg', 'jpeg', 'png');
		$allowtype = ['gif', 'jpg', 'jpeg', 'png', 'GIF', 'JPG', 'JPEG', 'PNG'];

		/** Uploading the image **/

		$rezultat = '';
		$result_status = '';
		$result_msg = '';
		// if is received a valid file
		if (isset($_FILES['profile_photo'])) {
			$type = explode(".", strtolower($_FILES['profile_photo']['name']));
			//echo in_array($type, $allowtype);exit;
			if (in_array($type[1], $allowtype)) {
				// check its size
				if ($_FILES['profile_photo']['size'] != 0 && $_FILES['profile_photo']['size'] <= $max_size * 1024) {
					// if no errors

					if ($_FILES['profile_photo']['error'] == 0) {
						//$newname = 'preview_'.date("His").'.'.$type[1];
						$newname = 'attestation_' . time() . '.' . $type[1];
						$thefile = $savefolder . "/" . $_FILES['profile_photo']['name'];
						$newfilename = $savefolder . "/" . $newname;
						$filename = $newname;

						if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $newfilename)) {
							$rezultat = '';
							$result_status = 'error';
							$result_msg = 'The file cannot be uploaded, try again.';
						} else {
							$rezultat = $filename;
							$setWidth = 265;
							$setHeight = 40;
							[$imgwidth, $imgheight] = getimagesize($newfilename);
							if ($imgwidth < 265) {
								$setWidth = $imgwidth;
							}
							if ($imgheight < 40) {
								$setHeight = $imgheight;
							}

							if ($imgwidth > 265 || $imgheight > 40) {
								sapp_Global::smartresizeimage($newfilename, $setWidth, $setHeight, false, 'file', false, false);
							}
							$result_status = 'success';
							$result_msg = '';
						}
					}
				} else {
					$rezultat = '';
					$result_status = 'error';
					$result_msg = 'Image size must not exceed ' . $max_size . ' KB.';
				}
			} else {
				$rezultat = '';
				$result_status = 'error';
				$result_msg = 'Please upload only .gif, .jpg, .jpeg, .png images.';
			}
		} else {
			$rezultat = '';
			$result_status = 'error';
			$result_msg = 'Please upload only .gif, .jpg, .jpeg, .png images.';
		}

		$result = [
			'result' => $result_status,
			'img' => $rezultat,
			'msg' => $result_msg
		];
		return $result;
	}

	public function deleteAction()
	{

		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->_request->getParam('objid');
		$messages['message'] = '';$messages['msgtype'] = '';
		$actionflag = 3;
		if($id)
		{
			$educationDetailsModel = new Default_Model_Educationdetails();
			$data = ['isactive'=>0,'modifieddate'=>gmdate("Y-m-d H:i:s")];
			$where = ['id=?'=>$id];
			$Id = $educationDetailsModel->SaveorUpdateEducationDetails($data, $where);
			if($Id == 'update')
			{
				$menuID = EMPLOYEE;
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$id);
				$messages['message'] = 'Employee education details deleted successfully.';
				$messages['msgtype'] = 'success';
			}
			else
			{
				$messages['message'] = 'Employee education details  cannot be deleted.';
				$messages['msgtype'] = 'error';	
			}
		}
		else
		{
			$messages['message'] = 'Employee education details cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		$this->_helper->json($messages);
	}
}
