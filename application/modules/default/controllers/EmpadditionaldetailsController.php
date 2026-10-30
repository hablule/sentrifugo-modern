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

class Default_EmpadditionaldetailsController extends Zend_Controller_Action
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
		
	}
	public function editAction()
	{
		try {
		
	
		 	$userID="";
			$auth = Zend_Auth::getInstance();
		 	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
					$loginUserGroup = $auth->getStorage()->read()->group_id;
		 	}
		 	$id = $this->getRequest()->getParam('userid');
		 	$conText ='';
		 	$call = $this->_getParam('call');
		 	if($call == 'ajaxcall')
		 	{
		 		$this->_helper->layout->disableLayout();
		 		$userID = ($this->_getParam('unitId') !='')? $this->_getParam('unitId'):$this->_getParam('userid');
		 		$conText = ($this->_getParam('context') !='')? $this->_getParam('context'):$this->getRequest()->getParam('context');
		 	}
		 	if($id == '')		
			  $id = $userID;
			  
		 	$Uid = ($id)?$id:$userID;
		 	 
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
							$empadditionaldetailsModal = new Default_Model_Empadditionaldetails();
							$view = Zend_Layout::getMvcInstance()->getView();
							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = array();	$searchQuery = '';	$searchArray = array();		$tablecontent = '';
							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'e.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
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
							
							$dataTmp = $empadditionaldetailsModal->getGrid($sort, $by, $perPage, $pageNo,$searchData,$call,$dashboardcall,$Uid,$conText,'edit');
							
							array_push($data,$dataTmp);
							$this->view->dataArray = $data;
							$this->view->call = $call ;
							$this->view->employeedata = $employeeData[0];
							$this->view->id = $id;
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
		
		 	$userID="";
			$auth = Zend_Auth::getInstance();
		 	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
					$loginUserGroup = $auth->getStorage()->read()->group_id;
		 	}
		 	$id = $this->getRequest()->getParam('userid');
		 	$conText ='';
		 	$call = $this->_getParam('call');
		 	if($call == 'ajaxcall')
		 	{
		 		$this->_helper->layout->disableLayout();
		 		$userID = ($this->_getParam('unitId') !='')? $this->_getParam('unitId'):$this->_getParam('userid');
		 		$conText = ($this->_getParam('context') !='')? $this->_getParam('context'):$this->getRequest()->getParam('context');
		 	}
		 	if($id == '')		
			  $id = $userID;
			  
		 	$Uid = ($id)?$id:$userID;
		 	 
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
							$empadditionaldetailsModal = new Default_Model_Empadditionaldetails();
							$view = Zend_Layout::getMvcInstance()->getView();
							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = array();	$searchQuery = '';	$searchArray = array();		$tablecontent = '';
							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'e.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
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
							$dataTmp = $empadditionaldetailsModal->getGrid($sort, $by, $perPage, $pageNo,$searchData,$call,$dashboardcall,$Uid,$conText);
							array_push($data,$dataTmp);
							$this->view->dataArray = $data;
							$this->view->call = $call ;
							$this->view->employeedata = $employeeData[0];
							$this->view->id = $id;
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
			
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
			$servicebranchaddpermission = sapp_Global::_checkprivileges(SERVICEBRANCH,$loginuserGroup,$loginuserRole,'add');
			$rankaddpermission = sapp_Global::_checkprivileges(MILITARYRANK,$loginuserGroup,$loginuserRole,'add');
			$typeaddpermission = sapp_Global::_checkprivileges(SERVICETYPE,$loginuserGroup,$loginuserRole,'add');
			$dischargeaddpermission = sapp_Global::_checkprivileges(MILITARYDISCHARGE,$loginuserGroup,$loginuserRole,'add');
			$statusaddpermission = sapp_Global::_checkprivileges(VETERANSTATUS,$loginuserGroup,$loginuserRole,'add');
			$this->view->servicebranchaddpermission = $servicebranchaddpermission;
			$this->view->rankaddpermission = $rankaddpermission;
			$this->view->typeaddpermission = $typeaddpermission;
			$this->view->dischargeaddpermission = $dischargeaddpermission;
			$this->view->statusaddpermission = $statusaddpermission;
		$id = $this->getRequest()->getParam('unitId');
		if($id == '')
		$id = $loginUserId;
		// For open the form in popup...
		$emptyFlag=0;
	    $empadditionaldetailsform = new Default_Form_empadditionaldetails();
		$countriesModel = new Default_Model_Countries();
		$veteranstatusmodel = new Default_Model_Veteranstatus();
		$militaryservicemodel = new Default_Model_Militaryservice();
		$militaryBranchModel = new Default_Model_militaryservicebranch();
		$militaryRankModel = new Default_Model_militaryrank();
		$militaryDischargeModel = new Default_Model_militarydischargetype();
		$militaryservicetype_model = new Default_Model_militaryservicetype();
		$servicetypedata = $militaryservicetype_model->GetMilitaryServiceType();
		$militaryDischargedata = $militaryDischargeModel->GetMilitaryDischargeType();
		$militarybranchArra = $militaryBranchModel->GetMilitaryBranch();
		$militaryrankArr = $militaryRankModel->GetMilitaryRank();
		$msgarray = array();
	
		$countrieslistArr = $countriesModel->getTotalCountriesList();
        $empadditionaldetailsform->countries_served->addMultiOption('','Select Country');
		if(!empty($countrieslistArr))
		{
			
			foreach ($countrieslistArr as $countrieslistres)
			{
				$empadditionaldetailsform->countries_served->addMultiOption($countrieslistres['id'],utf8_encode($countrieslistres['country_name']) );
			}
		}else
		{
			$msgarray['countries_served'] = 'Countries are not configured yet.';
			$emptyFlag++;
		}
		$empadditionaldetailsform->branch_service->addMultiOption('','Select Branch');
		if(!empty($militarybranchArra))
		{
			foreach($militarybranchArra as $militarybranchres)
			{
				$empadditionaldetailsform->branch_service->addMultiOption($militarybranchres['servicebranch_id'],$militarybranchres['name']);

			}
		}
		$empadditionaldetailsform->rank_achieved->addMultiOption('','Select Branch');
		if(!empty($militaryrankArr))
		{
			foreach($militaryrankArr as $militaryrankres)
			{
				$empadditionaldetailsform->rank_achieved->addMultiOption($militaryrankres['militaryrank_id'],$militaryrankres['name']);

			}
		}
		$militaryserviceArr = $militaryservicemodel->getTotalMilitaryServiceData();
        $empadditionaldetailsform->military_servicetype->addMultiOption('','Select Military Rank');
		if(!empty($militaryserviceArr))
		{
			
			foreach ($militaryserviceArr as $militaryserviceres){
				$empadditionaldetailsform->military_servicetype->addMultiOption($militaryserviceres['id'],$militaryserviceres['militaryservicetype']);

			}
		}else
		{
			$msgarray['military_servicetype'] = 'Military service type not configured yet.';
			$emptyFlag++;
		}

		$veteranstatusArr = $veteranstatusmodel->getTotalVeteranStatusData();
        $empadditionaldetailsform->veteran_status->addMultiOption('','Select Veteran Status');
		if(!empty($veteranstatusArr))
		{
			foreach ($veteranstatusArr as $veteranstatusres){
				$empadditionaldetailsform->veteran_status->addMultiOption($veteranstatusres['id'],$veteranstatusres['veteranstatus']);

			}
		}else
		{
			$msgarray['veteran_status'] = 'Veteran status not configured yet.';
			$emptyFlag++;
		}

		$empadditionaldetailsform->discharge_status->addMultiOption('','Select Discharge Type');
		if(!empty($militaryDischargedata))
		{
			foreach($militaryDischargedata as $data)
			{
				$empadditionaldetailsform->discharge_status->addMultiOption($data['dischargetype_id'],$data['name']);
			}

		}
		$empadditionaldetailsform->military_servicetype->addMultiOption('','Select Military Service Type');
		if(!empty($servicetypedata))
		{
			foreach ($servicetypedata as  $value) {
				$empadditionaldetailsform->military_servicetype->addMultiOption($value['servicetype_id'],$value['name']);
			}
		}
		$empadditionaldetailsform->setAttrib('action',BASE_URL.'empadditionaldetails/addpopup/unitId/'.$id);
		$this->view->form = $empadditionaldetailsform;
		$this->view->msgarray = $msgarray;
		$this->view->emptyFlag = $emptyFlag;
		$this->view->controllername = 'empadditionaldetails';
		
		if($this->getRequest()->getPost())
		{	
			$result = $this->save($empadditionaldetailsform,$id);
			$this->view->msgarray = $result;
		}
		//code...
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
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
		$savefolder = DISCHARGE_PREVIEW_UPLOAD_PATH;		// folder for upload



		$max_size = 1024;			// maxim size for image file, in KiloBytes

		// Allowed image types
		//$allowtype = array('pdf', 'PDF', 'doc', 'docx', 'xls');
		$allowtype = array('pdf', 'PDF', 'doc', 'docx', 'xls');

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
				if ($_FILES['profile_photo']['size'] != 0 && $_FILES['profile_photo']['size'] <= $max_size * 3072) {
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
							list($imgwidth, $imgheight) = getimagesize($newfilename);
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

		$result = array(
			'result' => $result_status,
			'img' => $rezultat,
			'filename'=>$_FILES['profile_photo']['name'],
			'msg' => $result_msg
		);
		return $result;
	}

	public function viewpopupAction()
	{
		try {
			
		
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$id = $this->getRequest()->getParam('id');
		if($id == '')
		$id = $loginUserId;
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();
		$objName = 'empadditionaldetails';
		$empadditionaldetailsform = new Default_Form_empadditionaldetails();
		$empadditionaldetailsModal = new Default_Model_Empadditionaldetails();
		$countriesModel = new Default_Model_Countries();
		$veteranstatusmodel = new Default_Model_Veteranstatus();
		$militaryservicemodel = new Default_Model_Militaryservice();
		$empadditionaldetailsform->removeElement("submit");
		$elements = $empadditionaldetailsform->getElements();
			
		if(count($elements)>0)
		{
			foreach($elements as $key=>$element)
			{
				if(($key!="Cancel")&&($key!="Edit")&&($key!="Delete")&&($key!="Attachments")){
					$element->setAttrib("disabled", "disabled");
				}
			}
		}
		$data = $empadditionaldetailsModal->getEmpAdditionalDetailsData($id);
		if(!empty($data))
		{
			$singleCountryArr = $countriesModel->getCountryCode($data[0]['countries_served']);
			if(!empty($data[0]['military_servicetype']))
			{
				$singleMilitaryServiceArr = $militaryservicemodel->getMilitaryServiceDataByID($data[0]['military_servicetype']);
			}
			if(!empty($data[0]['veteran_status']))
			{
				$singleVeteranStatusArr = $veteranstatusmodel->getVeteranStatusDataByID($data[0]['veteran_status']);
			}
			if(!empty($singleCountryArr))
			 $empadditionaldetailsform->countries_served->addMultiOption($singleCountryArr[0]['id'],$singleCountryArr[0]['country_name']);
			if(!empty($singleMilitaryServiceArr))
			 $empadditionaldetailsform->military_servicetype->addMultiOption($singleMilitaryServiceArr[0]['id'],$singleMilitaryServiceArr[0]['militaryservicetype']);  
			if(!empty($singleVeteranStatusArr))
		     $empadditionaldetailsform->veteran_status->addMultiOption($singleVeteranStatusArr[0]['id'],$singleVeteranStatusArr[0]['veteranstatus']); 
			$empadditionaldetailsform->populate($data[0]);
			
			$empadditionaldetailsform->setDefault('countries_served',$data[0]['countries_served']);
			$empadditionaldetailsform->setDefault('military_servicetype',$data[0]['military_servicetype']);
			$empadditionaldetailsform->setDefault('veteran_status',$data[0]['veteranstatus']);
			$empadditionaldetailsform->setDefault('rank_achieved',$data[0]['rankname']);
			$file = json_decode($data[0]['verification_report'],true);
			$from_date = sapp_Global::change_date($data[0]['from_date'], 'view');
			$to_date = sapp_Global::change_date($data[0]['to_date'], 'view');
			$empadditionaldetailsform->from_date->setValue($from_date);
			$empadditionaldetailsform->to_date->setValue($to_date);

		}
	                if($data[0]['military_status']==2)
					{
						$data[0]['military_status']="no";
					}
					else 
					{
						$data[0]['military_status']="yes";
					}
	                if(!empty($data[0]['countries_served'])) {
						$countryname = $countriesModel->getCountryCode($data[0]['countries_served']);
						if(!empty($countryname)){
							$data[0]['countries_served'] = $countryname[0]['country_name'];
						}
						else
						{
							$data[0]['countries_served'] = "";
						}
					}
	                if($data[0]['discharge_status']==2)
					{
						$data[0]['discharge_status']="Medical";
					}
					else if ($data[0]['discharge_status']==1)
					{
						$data[0]['discharge_status']="Honorable";
					}
					else 
					{ 
						$data[0]['discharge_status']="";
					}
					
	                if(!empty($data[0]['military_servicetype'])) {
						$milservicename = $militaryservicemodel->getsingleMilitaryServiceData($data[0]['military_servicetype']);
						if(!empty($milservicename)){
							$data[0]['military_servicetype'] = $milservicename['militaryservicetype'];
						}
						else
						{
							$data[0]['military_servicetype'] = "";
						}
					}
	                if(!empty($data[0]['veteran_status'])) {
						$veteranstatusname = $veteranstatusmodel->getsingleVeteranStatusData($data[0]['veteran_status']);
						if(!empty($veteranstatusname)){
							$data[0]['veteran_status'] = $veteranstatusname['veteranstatus'];
						}
						else
						{
							$data[0]['veteran_status'] = "";
						}
					}
		$this->view->controllername = $objName;
		$this->view->id = $id;

		$this->view->filename = $file['originalName'];
		$this->view->data= $data[0];
		
		$this->view->form = $empadditionaldetailsform;
		//code...
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
	}
	}


	function downloadfilesAction()
	{
		try {
			
		if($_POST){
			
			$doc_id = $_POST['doc_id'];
			$file_name = isset($_POST['file_name'])? $_POST['file_name'] : '';
			$empDocuModel = new Default_Model_Employeedocs();
			$empDocuments = $_POST['doc'];
			if (!empty($empDocuments)) {
				if($empDocuments){
					$attachments = json_decode($empDocuments,true);
					//Downloading single file
					
					if($file_name)
					{	$new = array();$ori = array();

							foreach ($attachments as $k => $v){
							 if($file_name == $v["new_name"])
							 {
							   $new[] = $v["new_name"];
							   $ori[] = $v["original_name"];
							 }
						}
					}
					else 
					{
					  if(count($attachments)>0){
				    	$new = array();$ori = array();
				       
							$new[] = $attachments["newName"];
							$ori[] = $attachments["originalName"];
					
				     }
					}
				}
				$file_name = $attachments['newName'];
				$file_names = $new;
				$originalNames = $ori;
				$originalNamesString = implode(",", $originalNames);
				if(isset($file_name) && empty($_POST['user_id']))
				{
					$fileNameArr = explode('.',$file_name);
					$download_file_name = $empDocuments[0]['name'].'_'.$ori[0]; //to appand original name for single file download
				 	$archive_file_name = preg_replace('/[^a-zA-Z0-9\']/', '_', $attachments['newName']);
				}
				else {
				$archive_file_name = preg_replace('/[^a-zA-Z0-9\']/', '_', $attachments['newName']);
				}
				$file_path = DISCHARGE_PREVIEW_UPLOAD_PATH;
	
				$temp = '.zip';
	
				$zip = new ZipArchive();
				//$zip->open($file_path.'/'.$archive_file_name.$temp, ZIPARCHIVE::CREATE );
				//create the file and throw the error if unsuccessful
				if ($zip->open($file_path.'/'.$archive_file_name.$temp, ZIPARCHIVE::CREATE)!==TRUE) {
					exit("cannot open <$archive_file_name>\n");
				}
				//add each files of $file_name array to archive
				for($i=0; $i<sizeof($file_names); $i++)
				{
					
					$name = '';
					$count = substr_count($originalNamesString, $originalNames[$i]);
					if($count > 1)
					$name = $i.$originalNames[$i];
					else
					$name = $originalNames[$i];
					$zip->addFile($file_path."/".$file_names[$i],$name);
				}
				
				$zip->close();
				//then send the headers to foce download the zip file
				
				header("Content-type: application/zip");
				header("Content-Disposition: attachment; filename=".$archive_file_name.$temp);
				header("Pragma: no-cache");
				header("Expires: 10");
				readfile($file_path.'/'.$archive_file_name.$temp);
				unlink($file_path.'/'.$archive_file_name.$temp);
			} 
		}
		else{
			$this->_redirect('error');
		}
		//code...
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
	}
	exit;
	}    

	public function editpopupAction()
	{
		try {
			
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
			$servicebranchaddpermission = sapp_Global::_checkprivileges(SERVICEBRANCH,$loginuserGroup,$loginuserRole,'add');
			$rankaddpermission = sapp_Global::_checkprivileges(MILITARYRANK,$loginuserGroup,$loginuserRole,'add');
			$typeaddpermission = sapp_Global::_checkprivileges(SERVICETYPE,$loginuserGroup,$loginuserRole,'add');
			$dischargeaddpermission = sapp_Global::_checkprivileges(MILITARYDISCHARGE,$loginuserGroup,$loginuserRole,'add');
			$statusaddpermission = sapp_Global::_checkprivileges(VETERANSTATUS,$loginuserGroup,$loginuserRole,'add');
			$this->view->servicebranchaddpermission = $servicebranchaddpermission;
			$this->view->rankaddpermission = $rankaddpermission;
			$this->view->typeaddpermission = $typeaddpermission;
			$this->view->dischargeaddpermission = $dischargeaddpermission;
			$this->view->statusaddpermission = $statusaddpermission;
		$id = $this->getRequest()->getParam('id');
		$userid = $this->getRequest()->getParam('unitId');
		if($id == '')
		$id = $loginUserId;
		// For open the form in popup...

		$empadditionaldetailsform = new Default_Form_empadditionaldetails();
		$empadditionaldetailsModal = new Default_Model_Empadditionaldetails();
		$militaryBranchModel = new Default_Model_militaryservicebranch();
		$militaryRankModel = new Default_Model_militaryrank();
		$militaryDischargeModel = new Default_Model_militarydischargetype();
		$militaryservicetype_model = new Default_Model_militaryservicetype();
		$countriesModel = new Default_Model_Countries();
		$veteranstatusmodel = new Default_Model_Veteranstatus();
		$militaryservicemodel = new Default_Model_Militaryservice();
		$servicetypedata = $militaryservicetype_model->GetMilitaryServiceType();
		$militaryDischargedata = $militaryDischargeModel->GetMilitaryDischargeType();
		$militarybranchArra = $militaryBranchModel->GetMilitaryBranch();
		$militaryrankArr = $militaryRankModel->GetMilitaryRank();
		$countrieslistArr = $countriesModel->getTotalCountriesList();
                $empadditionaldetailsform->countries_served->addMultiOption('','Select Country');
		if(!empty($countrieslistArr))
		{
			
			foreach ($countrieslistArr as $countrieslistres)
			{
				$empadditionaldetailsform->countries_served->addMultiOption($countrieslistres['id'],utf8_encode($countrieslistres['country_name']) );
			}
		}

		$militaryserviceArr = $militaryservicemodel->getTotalMilitaryServiceData();
                $empadditionaldetailsform->military_servicetype->addMultiOption('','Select Service Type');
		if(!empty($militaryserviceArr))
		{
			
			foreach ($militaryserviceArr as $militaryserviceres){
				$empadditionaldetailsform->military_servicetype->addMultiOption($militaryserviceres['id'],$militaryserviceres['militaryservicetype']);

			}
		}

		$empadditionaldetailsform->rank_achieved->addMultiOption('','Select Rank');
		if(!empty($militaryrankArr))
		{
			foreach($militaryrankArr as $militaryrankres)
			{
				$empadditionaldetailsform->rank_achieved->addMultiOption($militaryrankres['militaryrank_id'],$militaryrankres['name']);

			}
		}
		$empadditionaldetailsform->discharge_status->addMultiOption('','Select Discharge Type');
		if(!empty($militaryDischargedata))
		{
			foreach($militaryDischargedata as $data)
			{
				$empadditionaldetailsform->discharge_status->addMultiOption($data['dischargetype_id'],$data['name']);
			}

		}
		$empadditionaldetailsform->branch_service->addMultiOption('','Select Branch');
		if(!empty($militarybranchArra))
		{
			foreach($militarybranchArra as $militarybranchres)
			{
				$empadditionaldetailsform->branch_service->addMultiOption($militarybranchres['servicebranch_id'],$militarybranchres['name']);

			}
		}
		$empadditionaldetailsform->rank_achieved->addMultiOption('','Select Branch');
		if(!empty($militaryrankArr))
		{
			foreach($militaryrankArr as $militaryrankres)
			{
				$empadditionaldetailsform->rank_achieved->addMultiOption($militaryrankres['militaryrank_id'],$militaryrankres['name']);

			}
		}

		$veteranstatusArr = $veteranstatusmodel->getTotalVeteranStatusData();
                $empadditionaldetailsform->veteran_status->addMultiOption('','Select Veteran Status');
		if(!empty($veteranstatusArr))
		{
			
			foreach ($veteranstatusArr as $veteranstatusres){
				$empadditionaldetailsform->veteran_status->addMultiOption($veteranstatusres['id'],$veteranstatusres['veteranstatus']);

			}
		}
		$empadditionaldetailsform->branch_service->addMultiOption('','Select Branch');
		if(!empty($militarybranchArra))
		{
			foreach($militarybranchArra as $militarybranchres)
			{
				$empadditionaldetailsform->branch_service->addMultiOption($militarybranchres['servicebranch_id'],$militarybranchres['name']);

			}
		}
		$empadditionaldetailsform->rank_achieved->addMultiOption('','Select Branch');
		if(!empty($militaryrankArr))
		{
			foreach($militaryrankArr as $militaryrankres)
			{
				$empadditionaldetailsform->rank_achieved->addMultiOption($militaryrankres['militaryrank_id'],$militaryrankres['name']);

			}
		}
		if($id)
		{
			$data = $empadditionaldetailsModal->getEmpAdditionalDetailsData($id);
			if(!empty($data))
			{
				$empadditionaldetailsform->populate($data[0]);
				$empadditionaldetailsform->setDefault('countries_served',$data[0]['countries_served']);
				$empadditionaldetailsform->setDefault('military_servicetype',$data[0]['military_servicetype']);
				$empadditionaldetailsform->setDefault('veteran_status',$data[0]['veteranstatus']);
				$empadditionaldetailsform->setDefault('service_number',$data[0]['servicenumber']);
				$from_date = sapp_Global::change_date($data[0]['from_date'], 'view');
				$to_date = sapp_Global::change_date($data[0]['to_date'], 'view');
				$file = json_decode($data[0]['verification_report'],true)['originalName'];
				$this->view->org_image = $file;
				$empadditionaldetailsform->from_date->setValue($from_date);
				$empadditionaldetailsform->to_date->setValue($to_date);
				$empadditionaldetailsform->submit->setLabel('Update');
				$this->view->data = $data;
			}
		}
		$empadditionaldetailsform->setAttrib('action',BASE_URL.'empadditionaldetails/editpopup/unitId/'.$userid);
		$this->view->form = $empadditionaldetailsform;
		$this->view->controllername = 'empadditionaldetails';
		if($this->getRequest()->getPost())
		{	
			$result = $this->save($empadditionaldetailsform,$userid);
			$this->view->msgarray = $result;
		}
		//code...
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
	}
	}

	public function save($empadditionaldetailsform,$userid)
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$imagerror = $this->_request->getParam('imgerr');
			$imagepath = $this->_request->getParam('attestation_image_value');
			$imgerrmsg = $this->_request->getParam('imgerrmsg');
			$orginalfilename = $this->_request->getParam('filename');
			$flag = 'true';
			if (isset($imagepath) && $imagepath != '') {

				$imageArr = explode('.', $imagepath);
				if (sizeof($imageArr) > 1) {
					$imagename = $imageArr[0];
					$imageext = $imageArr[1];
					$extArr = array('gif', 'jpg', 'jpeg', 'png');
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
		if($empadditionaldetailsform->isValid($this->_request->getPost())){
			$empadditionaldetailsModal = new Default_Model_Empadditionaldetails();
			$id = $this->_request->getParam('id');
			$user_id = $userid;
		
			$countries_served = $this->_request->getParam('countries_served');
			$branch_service = trim($this->_request->getParam('branch_service'));
			$rank_achieved = trim($this->_request->getParam('rank_achieved'));
			$special_training = trim($this->_request->getParam('special_training'));
			$awards = trim($this->_request->getParam('awards'));
			$from_date = $this->_request->getParam('from_date');
			$from_date = sapp_Global::change_date($from_date, 'database');
			$to_date = $this->_request->getParam('to_date');
			$to_date = sapp_Global::change_date($to_date, 'database');
			$discharge_status = $this->_request->getParam('discharge_status');
			$service_number = trim($this->_request->getParam('service_number'));
			$rank = trim($this->_request->getParam('rank'));
			$military_servicetype = $this->_request->getParam('military_servicetype');
			$veteran_status = $this->_request->getParam('veteran_status');
			$date = new Zend_Date();
			$actionflag = '';
			$tableid  = '';
			$data = array('emp_id'=>$user_id,
								 'countries_served'=>$countries_served,
                                 'branch_service'=>($branch_service!=''?$branch_service:NULL),
                                 'rank_achieved'=>($rank_achieved!=''?$rank_achieved:NULL),
                                 'special_training'=>($special_training!=''?$special_training:NULL),
                                 'awards'=>($awards!=''?$awards:NULL),    								 
				      			 'from_date'=>($from_date!=''?$from_date:NULL),
								 'to_date'=>($to_date!=''?$to_date:NULL),
								 'discharge_status'=>$discharge_status,
								 'servicenumber'=>($service_number!=''?$service_number:NULL),
                                 'rank'=>($rank!=''?$rank:NULL), 
                                 'verification_report'=>json_encode(array('newName'=>$imagepath,'originalName'=>$orginalfilename)), 								 
				      			 'military_servicetype'=> $military_servicetype,
								 'veteranstatus'=> $veteran_status,
								 'modifiedby'=>$loginUserId,
								 'modifieddate'=>gmdate("Y-m-d H:i:s")
			);
			if($id!=''){
				$where = array('id=?'=>$id);
				$actionflag = 2;
			}
			else
			{
				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$data['isactive'] = 1;
				$where = '';
				$actionflag = 1;
			}
			$Id = $empadditionaldetailsModal->SaveorUpdateEmpAdditionalData($data, $where);
			if($Id == 'update')
			{
				$tableid = $id;
				$this->view->successmessage = 'Employee additional details updated successfully.';
					
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Employee additional details added successfully.';
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			$this->view->controllername = 'empadditionaldetails';
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		}else
		{
			$messages = $empadditionaldetailsform->getMessages();
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

	public function deleteAction()
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->_request->getParam('objid');
		$messages['message'] = '';$messages['msgtype'] = '';$messages['flagtype'] = '';
		$actionflag = 3;
		if($id)
		{
			$empadditionaldetailsModal = new Default_Model_Empadditionaldetails();
			$data = array('isactive'=>0,'modifieddate'=>gmdate("Y-m-d H:i:s"));
			$where = array('id=?'=>$id);
			$Id = $empadditionaldetailsModal->SaveorUpdateEmpAdditionalData($data, $where);
			if($Id == 'update')
			{
				$menuID = EMPLOYEE;
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$id);
				$messages['message'] = 'Employee additional details deleted successfully.';
				$messages['msgtype'] = 'success';
			}
			else
			{
				$messages['message'] = 'Employee additional details cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		}
		else
		{
			$messages['message'] = 'Employee additional details cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		$this->_helper->json($messages);

	}
}