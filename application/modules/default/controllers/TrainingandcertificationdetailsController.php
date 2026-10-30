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

class Default_TrainingandcertificationdetailsController extends Zend_Controller_Action
{
	private $options;

	public function preDispatch()
	{

	}

	public function init()
	{
		$this->_options= $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';

	}
	public function indexAction()
	{
		
		 	$userID='';$empdata=[]; $conText = "";$objName = 'trainingandcertificationdetails';
		 	$auth = Zend_Auth::getInstance();
		 	if($auth->hasIdentity()){
		 		$loginUserId = $auth->getStorage()->read()->id;
		 		$loginUserRole = $auth->getStorage()->read()->emprole;
		 		$loginUserGroup = $auth->getStorage()->read()->group_id;
		 	}
		 	$userid = $this->getRequest()->getParam('userid');	//This is User_id taking from URL
		 	$call = $this->_getParam('call');
		 	if($call == 'ajaxcall')
		 	{
		 		$this->_helper->layout->disableLayout();
		 		$userID = ($this->_getParam('unitId') !='')? $this->_getParam('unitId'):$this->_getParam('userid');
		 		$conText = ($this->_getParam('context') !='')? $this->_getParam('context'):$this->getRequest()->getParam('context');
		 	}
		 	if($userid == '') $userid =$userID;
		 	$Uid = $userid ?: $userID;
		 	$employeeModal = new Default_Model_Employee();
		 	try
		 	{
			    if($userid && is_numeric($userid) && $userid>0)
				{
					$usersModel = new Default_Model_Users();
					$empdata = $employeeModal->getActiveEmployeeData($userid);
					$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);
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
							$TandCdetailsModel = new Default_Model_Trainingandcertificationdetails();
							$view = Zend_Layout::getMvcInstance()->getView();

							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = [];	$searchQuery = '';	$searchArray = [];		$tablecontent = '';

							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';	$searchArray = [];
							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'modifieddate';
								if($dashboardcall == 'Yes')
								$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
								else
								$perPage = $this->_getParam('per_page',PERPAGE);
								$pageNo = $this->_getParam('page', 1);
								$searchData = $this->_getParam('searchData');
								$searchData = rtrim($searchData,',');
							}
							$dataTmp = $TandCdetailsModel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$Uid,$conText);

							array_push($data,$dataTmp);
							$this->view->id=$Uid;	
							$this->view->controllername = $objName;
							$this->view->dataArray = $data;
							$this->view->employeedata = $employeeData[0];
							$this->view->call = $call ;
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
			//code...
	
		
		 	$userID='';$empdata=[]; $conText = "";$objName = 'trainingandcertificationdetails';
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
		 	if($userid == '') $userid =$userID;
		 	$Uid = $userid ?: $userID;
		 	$employeeModal = new Default_Model_Employee();
		 	try
		 	{
			    if($userid && is_numeric($userid) && $userid>0 )
				{
					$usersModel = new Default_Model_Users();
					$empdata = $employeeModal->getActiveEmployeeData($userid);
					$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);

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
							$TandCdetailsModel = new Default_Model_Trainingandcertificationdetails();
							$view = Zend_Layout::getMvcInstance()->getView();

							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = [];	$searchQuery = '';	$searchArray = [];		$tablecontent = '';

							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'ec.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';	$searchArray = [];
							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'ec.modifieddate';
								if($dashboardcall == 'Yes')
								$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
								else
								$perPage = $this->_getParam('per_page',PERPAGE);
								$pageNo = $this->_getParam('page', 1);
								$searchData = $this->_getParam('searchData');
								$searchData = rtrim($searchData,',');
							}
							$dataTmp = $TandCdetailsModel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$Uid,$conText,'edit');

							array_push($data,$dataTmp);
							$this->view->id=$Uid;	
							$this->view->controllername = $objName;
							$this->view->dataArray = $data;
							$this->view->employeedata = $employeeData[0];
							$this->view->call = $call ;
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
		
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
	}

	public function viewAction()
	{
		try {
			//code...
	
	
		 	$userID='';$empdata=[]; $conText = "";$objName = 'trainingandcertificationdetails';
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
		 	if($userid == '') $userid =$userID;
		 	$Uid = $userid ?: $userID;
		 	$employeeModal = new Default_Model_Employee();
		 	try
		 	{
			    if($userid && is_numeric($userid) && $userid>0 )
				{
					$usersModel = new Default_Model_Users();
					$empdata = $employeeModal->getActiveEmployeeData($userid);
					$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);

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
							$TandCdetailsModel = new Default_Model_Trainingandcertificationdetails();
							$view = Zend_Layout::getMvcInstance()->getView();

							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = [];	$searchQuery = '';	$searchArray = [];		$tablecontent = '';

							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'ec.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';	$searchArray = [];
							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'ec.modifieddate';
								if($dashboardcall == 'Yes')
								$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
								else
								$perPage = $this->_getParam('per_page',PERPAGE);
								$pageNo = $this->_getParam('page', 1);
								$searchData = $this->_getParam('searchData');
								$searchData = rtrim($searchData,',');
							}
							$dataTmp = $TandCdetailsModel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$Uid,$conText,'view');

							array_push($data,$dataTmp);
							$this->view->id=$Uid;	
							$this->view->controllername = $objName;
							$this->view->dataArray = $data;
							$this->view->employeedata = $employeeData[0];
							$this->view->call = $call ;
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
		
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
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
		$savefolder = CERTIFICATE_PREVIEW_UPLOAD_PATH;		// folder for upload



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
						$newname = 'cert_' . time() . '.' . $type[1];
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
	public function addpopupAction()
	{
		try {
			//code...
	
		$msgarray = [];
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserid = $auth->getStorage()->read()->id;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginUserRole = $auth->getStorage()->read()->emprole;   
		}
		$provideaddpermission = '';
		$provideaddpermission = sapp_Global::_checkprivileges(CERTPROVIDER,$loginuserGroup,$loginUserRole,'add');
		$this->view->provideaddpermission = $provideaddpermission;

		$emptyFlag = 0;

		$TandCDetailsform = new Default_Form_Trainingandcertificationdetails();

		$userId = $this->getRequest()->getParam('unitId');

		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");

		$modela = new Default_Model_certificate();
		$modelb = new Default_Model_Certprovider();
//---------------
$level1Arr = $modela->getallcertificate();
$msgarray = [];
$optionClass=[];
$TandCDetailsform->certificate_id->addMultiOption('', 'Select certificate');
if (!empty($level1Arr)) {

	foreach ($level1Arr as $level1Arrres) {
		$TandCDetailsform->certificate_id->addMultiOption($level1Arrres['certificate_id'], $level1Arrres['name']);
		$optionClass = 'xoffset='.$level1Arrres['provider'].'';
		$optionClasses[$level1Arrres['certificate_id']] = $optionClass;
		$TandCDetailsform->certificate_id->setAttrib('optionClasses', $optionClasses);

	}
} else {
	$msgarray['certificate_id'] = 'Certificate  are not configured yet.';
	$emptyFlag++;
}


//-------------------------------------//
//---------------
$level2Arr = $modelb->getallcategory();
$msgarray = [];
$TandCDetailsform->certprovider_id->addMultiOption('', 'Select Provider');
if (!empty($level2Arr)) {

	foreach ($level2Arr as $level2Arrres) {
		$TandCDetailsform->certprovider_id->addMultiOption($level2Arrres['certprovider_id'], $level2Arrres['name']);
	}
} else {
	$msgarray['certprovider_id'] = 'Provider  are not configured yet.';
	$emptyFlag++;
}


//-------------------------------------//

		$TandCDetailsform->setAttrib('action',BASE_URL.'trainingandcertificationdetails/addpopup/unitId/'.$userId);
		$this->view->form = $TandCDetailsform;
		$this->view->msgarray = $msgarray;
		$this->view->form = $TandCDetailsform;
		$this->view->msgarray = $msgarray;
		$this->view->emptyFlag = $emptyFlag;
		$this->view->controllername = 'trainingandcertificationdetails';
		if ($this->getRequest()->getPost()) {
			$result = $this->save($TandCDetailsform);
			$this->view->msgarray = $result;
		}


	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}

	}
	public function editpopupAction()
	{
		try {
			//code...
	
		$msgarray = [];
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserid = $auth->getStorage()->read()->id;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginUserRole = $auth->getStorage()->read()->emprole;   
		}
		$provideaddpermission = '';
		$provideaddpermission = sapp_Global::_checkprivileges(CERTPROVIDER,$loginuserGroup,$loginUserRole,'add');
		$this->view->provideaddpermission = $provideaddpermission;
		$emptyFlag = 0;

		$TandCDetailsform = new Default_Form_Trainingandcertificationdetails();
		$model = new Default_Model_Trainingandcertificationdetails();

		$id = $this->_getParam('id');
		$unitid = $this->_getParam('unitId');
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");

		$modela = new Default_Model_certificate();
		$modelb = new Default_Model_Certprovider();
//---------------
$level1Arr = $modela->getallcertificate();
$msgarray = [];
$TandCDetailsform->certificate_id->addMultiOption('', 'Select certificate');
if (!empty($level1Arr)) {

	foreach ($level1Arr as $level1Arrres) {
		$TandCDetailsform->certificate_id->addMultiOption($level1Arrres['certificate_id'], $level1Arrres['name']);
	}
} else {
	$msgarray['certificate_id'] = 'Certificate  are not configured yet.';
	$emptyFlag++;
}


//-------------------------------------//
//---------------
$level2Arr = $modelb->getallcategory();
$msgarray = [];
$TandCDetailsform->certprovider_id->addMultiOption('', 'Select Provider');
if (!empty($level2Arr)) {

	foreach ($level2Arr as $level2Arrres) {
		$TandCDetailsform->certprovider_id->addMultiOption($level2Arrres['certprovider_id'], $level2Arrres['name']);
	}
} else {
	$msgarray['certprovider_id'] = 'Provider  are not configured yet.';
	$emptyFlag++;
}


//-------------------------------------//
$data = $model->getTandCdetailsRecord($id);
if (!empty($data)) {
	$TandCDetailsform->populate($data[0]);
	// $empskillsform->setDefault('competencylevelid',$data[0]['competencylevelid']);
	$issueddate = sapp_Global::change_date($data[0]['issueddate'], 'view');
	$expirydate = sapp_Global::change_date($data[0]['expirydate'], 'view');
	$TandCDetailsform->issueddate->setValue($issueddate);
	$TandCDetailsform->expirydate->setValue($expirydate);
	$TandCDetailsform->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'],'form'));

	$this->view->org_image = $data[0]['attestation'];
}
$TandCDetailsform->setAction(BASE_URL . 'trainingandcertificationdetails/editpopup/id/' . $id . '/unitId/' . $unitid . '/popup/1 ');
$this->view->form = $TandCDetailsform;
		$this->view->msgarray = $msgarray;
		$this->view->form = $TandCDetailsform;
		$this->view->msgarray = $msgarray;
		$this->view->emptyFlag = $emptyFlag;
		$this->view->controllername = 'trainingandcertificationdetails';
		if ($this->getRequest()->getPost()) {
			$result = $this->save($TandCDetailsform);
			$this->view->msgarray = $result;
		}


	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}

	}
	// public function editpopupAction()
	// {
	// 	$auth = Zend_Auth::getInstance();$issueddate ="";
	// 	if($auth->hasIdentity())
	// 	{
	// 		$loginUserId = $auth->getStorage()->read()->id;
	// 	}
	// 	Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
	// 	$id = $this->_request->getParam('id');	
	// 	$user_id = $this->getRequest()->getParam('unitId');	

	// 	$TandCDetailsform = new Default_Form_Trainingandcertificationdetails();
	// 	$TandCdetailsModel = new Default_Model_Trainingandcertificationdetails();

	// 	if($id)
	// 	{
	// 		$data = $TandCdetailsModel->getTandCdetailsRecord($id);
	// 		if(!empty($data))
	// 		{
	// 			$TandCDetailsform->setDefault("id",$data[0]["id"]);
	// 			$TandCDetailsform->setDefault("user_id",$user_id);

	// 			$TandCDetailsform->setDefault("course_name",$data[0]["course_name"]);
	// 			$TandCDetailsform->setDefault("description",$data[0]["description"]);
	// 			$TandCDetailsform->setDefault("course_level",$data[0]["course_level"]);

	// 			$TandCDetailsform->setDefault("course_offered_by",$data[0]["course_offered_by"]);
	// 			$TandCDetailsform->setDefault("certification_name",$data[0]["certification_name"]);
					
	// 			if($data[0]["issued_date"] != "" && $data[0]["issued_date"] != "0000-00-00")
	// 			{
	// 				$issueddate = sapp_Global::change_date($data[0]["issued_date"], 'view');
	// 				$TandCDetailsform->setDefault('issued_date', $issueddate);
	// 			}
	// 			$this->view->data=$data;
	// 		}
	// 		$TandCDetailsform->setAttrib('action',BASE_URL.'trainingandcertificationdetails/editpopup/unitId/'.$user_id);
	// 		$this->view->id=$user_id;
	// 	}
	// 	$TandCDetailsform->setAttrib('action',BASE_URL.'trainingandcertificationdetails/editpopup/id/'.$id.'/unitId/'.$user_id);
	// 	$this->view->form = $TandCDetailsform;
	// 	if($this->getRequest()->getPost())
	// 	{
	// 		$result = $this->save($TandCDetailsform);
	// 		$this->view->msgarray = $result;
	// 	}
	// }
	public function save($TandCDetailsform)
	{
		try {
			//code...
			$id = $this->_getParam('id');
			$unitid = $this->_getParam('unitId');

			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
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
			$empskillsModal = new Default_Model_Trainingandcertificationdetails();



			$date = new Zend_Date();
			$actionflag = '';
			$tableid  = '';



			if ($TandCDetailsform->isValid($this->_request->getPost()) && $flag != 'false') {
				$util = new utlis();


			$id = $this->getRequest()->getParam('id');	

			$certificate_id = $this->_request->getParam('certificate_id');
			$description = $this->_request->getParam('description');
			$tainingname = $this->_request->getParam('tainingname');
			$certprovider_id = $this->_request->getParam('certprovider_id');
			$offeredby = $this->_request->getParam('offeredby');
			$summaryofcourse = $this->_request->getParam('summaryofcourse');
			$issuedDate =sapp_Global::change_date($this->_request->getParam('issueddate',null), 'database');;
		
			$expirydate = $this->_request->getParam('expirydate',null);
			if($expirydate != "")
			{
				$expirydate = sapp_Global::change_date($expirydate, 'database');
			}
			if (($certificate_id  != '' &&  $tainingname  != '') or (($certificate_id  == '' &&  $tainingname  == ''))) {
				$msgarray['general'] = 'Please fill either certificate or traing name';

				return $msgarray;
			} else if (($certprovider_id  != '' &&  $offeredby  != '') or (($certprovider_id  == '' &&  $offeredby  == ''))) {
				$msgarray['general'] = 'Please fill either provider  or  offerd By';

				return $msgarray;
				} else {
					try {

						$db = Zend_Db_Table::getDefaultAdapter();
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
						$data = ['certificate_id'=>$certificate_id,
														'description'=>$description,
														'trainingname'=>$tainingname,
														'certprovider_id'=>$certprovider_id,
														'offeredby'=>$offeredby,
														'issueddate'=>$issuedDate,
														'expirydate'=>$expirydate,
														'summaryofcourse'=>$summaryofcourse,
														'emp_id' => $unitid,
														'attestation' => $imagepath,
														'modifiedby'=>$loginUserId,
									                    'modifieddate'=>gmdate("Y-m-d H:i:s")
									];
						if ($id != '') {

							$where = 'empcertification_id= ' . $id;
							$data['isactive'] = $util::getcheckboxvalue($this->_getParam('isactive'));

							$actionflag = 2;
						} else {

							$data['createdby'] = $loginUserId;
							$data['createddate'] = gmdate("Y-m-d H:i:s");
							$data['isactive'] = "Y";
							$where = '';
							$actionflag = 1;
						}
						//	die($where);
						$Id = $empskillsModal->SaveorUpdateEmployeeTandCData($data, $where);
					} catch (Exception $e) {
						Zend_Debug::dump($e->getMessage());
					}
					if ($Id == 'update') {
						$tableid = $id;
								$this->view->successmessage = 'Employee certification details updated successfully.';
					} else {
						$tableid = $Id;
		$this->view->successmessage = 'Employee certification details added successfully.';
}
					$menuID = EMPLOYEE;
					$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $unitid);
					$this->view->controllername = 'trainingandcertificationdetails';
					Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
				}
			} else {
				$messages = $TandCDetailsform->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgarray[$key] = $val2;
						break;
					}
				}
				return $msgarray;
			}

			return $msgarray;
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

// 	public function save($TandCDetailsform,$user_id)
// 	{
// 		try {
// 			//code...

// 		$util = new utlis();

// 		$result ="";$issuedDateStr = "";$issuedDate ='';
// 		$auth = Zend_Auth::getInstance();
// 		if($auth->hasIdentity())
// 		{
// 			$loginUserId = $auth->getStorage()->read()->id;
// 		}
// 		$imagerror = $this->_request->getParam('imgerr');
// 		$imagepath = $this->_request->getParam('attestation_image_value');
// 		$imgerrmsg = $this->_request->getParam('imgerrmsg');
// 		$flag = 'true';
// 		if (isset($imagepath) && $imagepath != '') {

// 			$imageArr = explode('.', $imagepath);
// 			if (sizeof($imageArr) > 1) {
// 				$imagename = $imageArr[0];
// 				$imageext = $imageArr[1];
// 				$extArr = array('gif', 'jpg', 'jpeg', 'png');
// 				if (!in_array($imageext, $extArr)) {
// 					$msgarray['attestation_image_value'] = 'Please upload an appropriate image file.';
// 					$flag = 'false';
// 				}
// 			} else {
// 				$msgarray['attestation_image_value'] = 'Please upload an appropriate image file.';
// 				$flag = 'false';
// 			}
// 		}
// 		if ($imagerror == 'error') {
// 			if ($imgerrmsg != '' && $imgerrmsg != 'undefined')
// 				$msgarray['attestation_image_value'] = $imgerrmsg;
// 			else
// 				$msgarray['attestation_image_value'] = 'Please upload an appropriate image file.';
// 			$flag = 'false';
// 		}
// 		$date = new Zend_Date();

// 		if(($TandCDetailsform->isValid($this->_request->getPost())) && $flag != 'false')
// 		{
// 			$TandCdetailsModel = new Default_Model_Trainingandcertificationdetails();

// 			$id = $this->getRequest()->getParam('id');	

// 			$certificate_id = $this->_request->getParam('certificate_id');
// 			$description = $this->_request->getParam('description');
// 			$tainingname = $this->_request->getParam('tainingname');
// 			$certprovider_id = $this->_request->getParam('certprovider_id');
// 			$offeredby = $this->_request->getParam('offeredby');
// 			$summaryofcourse = $this->_request->getParam('summaryofcourse');
// 			$issuedDate =sapp_Global::change_date($this->_request->getParam('issueddate',null), 'database');;
		
// 			$expirydate = $this->_request->getParam('expirydate',null);
// 			if($expirydate != "")
// 			{
// 				$expirydate = sapp_Global::change_date($expirydate, 'database');
// 			}
// 			if (($certificate_id  != '' &&  $tainingname  != '') or (($certificate_id  == '' &&  $tainingname  == ''))) {
// 				$msgarray['general'] = 'Please fill either certificate or traing name';

// 				return $msgarray;
// 			} else if (($certprovider_id  != '' &&  $offeredby  != '') or (($certprovider_id  == '' &&  $offeredby  == ''))) {
// 				$msgarray['general'] = 'Please fill either provider  or  offerd By';

// 				return $msgarray;
// 			} else{

// 				try {

// 					$db = Zend_Db_Table::getDefaultAdapter();
// 					$path = IMAGE_UPLOAD_PATH_CERTIFICATE;
// 					$imagepath = $this->_request->getParam('attestation_image_value');
// 					$filecopy = 'success';
// 					if ($imagepath != '') {
// 						$filecopy = 'error';
// 						if (file_exists(CERTIFICATE_PREVIEW_UPLOAD_PATH . '//' . $imagepath)) {
// 							try {
// 								if (copy(CERTIFICATE_PREVIEW_UPLOAD_PATH . '//' . $imagepath, $path . '//' . $imagepath))
// 									$filecopy = 'success';
// 								unlink(CERTIFICATE_PREVIEW_UPLOAD_PATH . '//' . $imagepath);
// 							} catch (Exception $e) {
// 								echo $msgarray['attestation_image_value'] = $e->getFile();
// 								exit;
// 							}
// 						}
// 					}
// 					die("boom");
// 			$data = array(  'certificate_id'=>$certificate_id,
// 								'description'=>$description,
// 								'tainingname'=>$tainingname,
// 								'certprovider_id'=>$certprovider_id,
// 								'offeredby'=>$offeredby,
// 								'issueddate'=>$issuedDate,
// 								'expirydate'=>$expirydate,
// 								'summaryofcourse'=>$summaryofcourse,
// 								'emp_id'=>$user_id,
// 								'attestation' => $imagepath,
// 								'modifiedby'=>$loginUserId,
// 			                    'modifieddate'=>gmdate("Y-m-d H:i:s")
// 			);
// 			if($id!='')
// 			{
// 				$data['isactive'] = $util::getcheckboxvalue($this->_getParam('isactive'));

// 				$where = array('empcertification_id=?'=>$id);
// 				$actionflag = 2;
// 			}
// 			else
// 			{
// 				$data['createdby'] = $loginUserId;
// 				$data['createddate'] = gmdate("Y-m-d H:i:s");
// 				$where = '';
// 				$actionflag = 1;
// 			}
// 			$Id = $TandCdetailsModel->SaveorUpdateEmployeeTandCData($data,$where);
// 		} catch (Exception $e) {
// 			Zend_Debug::dump($e->getMessage());
// 			// $code = $e->getCode();
// 			// if ($code = 2300) {
// 			// 	$msgarray['skill_id'] = 'Skill already exist ';
// 			// 	return $msgarray;
// 			// }
// 		}
// 			if($Id == 'update')
// 			{
// 				$tableid = $id;
// 				$this->view->successmessage = 'Employee certification details updated successfully.';
// 			}
// 			else
// 			{
// 				$tableid = $Id;
// 				$this->view->successmessage = 'Employee certification details added successfully.';
// 			}
// 			$menuID = EMPLOYEE;
// 			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
// 			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");

// 			$close = 'close';
// 			$this->view->popup=$close;
// 			$this->view->controllername = 'trainingandcertificationdetails';
// 		}
// 	}
// 		else
// 		{
// 			$messages = $TandCDetailsform->getMessages();

// 			foreach ($messages as $key => $val)
// 			{
// 				foreach($val as $key2 => $val2)
// 				{
// 					$msgarray[$key] = $val2;
// 					break;
// 				}
// 			}
// 			return $msgarray;
// 		}
// 	} catch (\Throwable $th) {
// Zend_Debug::dump($th->getMessage());
// 	}
// 	}
public function viewpopupAction()
{
	Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
	$auth = Zend_Auth::getInstance();
	if ($auth->hasIdentity()) {
		$loginUserId = $auth->getStorage()->read()->id;
		$loginuserRole = $auth->getStorage()->read()->emprole;
		$loginuserGroup = $auth->getStorage()->read()->group_id;
	}
	$id = $this->getRequest()->getParam('id');
	if ($id == '')
		$id = $loginUserId;
	$callval = $this->getRequest()->getParam('call');
	if ($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();
	$objName = 'trainingandcertificationdetails';
	$empskillsform = new Default_Form_Trainingandcertificationdetails();
	$empskillsModel = new Default_Model_Trainingandcertificationdetails();
	$empskillsform->removeElement("submit");
	$elements = $empskillsform->getElements();

	if (count($elements) > 0) {
		foreach ($elements as $key => $element) {
			if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
				$element->setAttrib("disabled", "disabled");
			}
		}
	}
	$data = $empskillsModel->certdetailview($id);
	$this->view->controllername = $objName;
	$this->view->id = $id;
	$this->view->data = $data[0];
	$this->view->form = $empskillsform;
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
			$TandCdetailsModel = new Default_Model_Trainingandcertificationdetails();
			$data = ['isactive'=>0,'modifieddate'=>gmdate("Y-m-d H:i:s")];
			$where = ['id=?'=>$id];
			$Id = $TandCdetailsModel->SaveorUpdateEmployeeTandCData($data,$where);
			if($Id == 'update')
			{
				$menuID = EMPLOYEE;
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$id);
				$messages['message'] = 'Employee certification details deleted successfully.';
				$messages['msgtype'] = 'success';
			}
			else{
				$messages['message'] = 'Employee certification details  cannot be deleted.';
				$messages['msgtype'] = 'error';	
			}
		}
		else
		{
			$messages['message'] = 'Employee certification details cannot be deleted.';
			$messages['msgtype'] = 'error';	
		}
		$this->_helper->json($messages);
	}

}

