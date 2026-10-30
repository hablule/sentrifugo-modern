<?php

class Default_emphealthissueController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }
    public function indexAction()
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
			  
		 	$Uid = $id ?: $userID;
		 	 
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
							$empadditionaldetailsModal = new Default_Model_emphealthissue();
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
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$healthissueaddpermission = sapp_Global::_checkprivileges(HEALTHISSUE,$loginuserGroup,$loginuserRole,'add');
			$healthissueserverityaddpermission = sapp_Global::_checkprivileges(HEALTHISSUESEVERITYLEVEL,$loginuserGroup,$loginuserRole,'add');
			
        $emphealthissue_Form = new Default_Form_emphealthissue();
        $healthissue_model = new Default_Model_healthissue();
        $severitylevel_model = new Default_Model_healthseveritylevel();
        $severitylevel_data = $severitylevel_model->GetHealthseveritylevelSelect();
        $healthissue_data = $healthissue_model->GetHealthIssue();
        $emphealthissue_Form->healthissue_id->addMultiOptions([''=>'Select Health Issue']);

        if(!empty($healthissue_data))
        {
            foreach ($healthissue_data as  $value) {
                $emphealthissue_Form->healthissue_id->addMultiOptions([$value['healthissue_id']=>$value['name']]);
            }
        }
        $emphealthissue_Form->severitylevel_id->addMultiOptions([''=>'Select Severity level']);
        if(!empty($severitylevel_data))
        {
            foreach ($severitylevel_data as $value) {
                $emphealthissue_Form->severitylevel_id->addMultiOptions([$value['severitylevel_id']=>$value['value']]);
            }
        }

        if ($this->getRequest()->isPost()) {
            $this->save($emphealthissue_Form);
        }

} catch (\Throwable $th) {
    Zend_Debug::dump($th->getMessage());
   }
        $this->view->form = $emphealthissue_Form;
		$this->view->healthissueaddpermission = $healthissueaddpermission;
		
		$this->view->healthissueserverityaddpermission = $healthissueserverityaddpermission;
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
		$savefolder = HEALTHISSUE_PREIVEW_UPLOAD_PATH;		// folder for upload



		$max_size = 1024;			// maxim size for image file, in KiloBytes

		// Allowed image types
		//$allowtype = array('gif', 'jpg', 'jpeg', 'png');
		$allowtype = ['docx', 'doc','PDF','pdf'];

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
    public function save($healthissuetype_Form)
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
					$extArr = ['pdf', 'doc', 'docx'];
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

		if($healthissuetype_Form->isValid($this->_request->getPost())){
			$healthissuetype_Model = new Default_Model_emphealthissue();
			$id = $this->getRequest()->getParam('id');
            $empid = $this->getRequest()->getParam('unitId');
			$user_id = $loginUserId;
			$healthissue_id = $this->_request->getParam('healthissue_id');
			$description = $this->_request->getParam('description');
            $diagnosedon = $this->_request->getParam('diagnosedon');
            $severitylevel_id = $this->_request->getParam('severitylevel_id');
            $severitylevel = $this->_request->getParam('severitylevel');
            $isactive = $this->_getParam('isactive');
			$actionflag = '';
			$tableid  = '';

			$data = [
				                 'emp_id'=>$empid,
				                 'healthissue_id'=>$healthissue_id,
								 'description'=>$description,
                                 'diagnosedon'=>sapp_Global::change_date($diagnosedon,'database'),
                                 'isactive'=>utlis::getcheckboxvalue($isactive),
                                 'attention'=>$imagepath
			];
            if($severitylevel_id!='')
            {
                $data['severitylevel_id']=$severitylevel_id;
            }
            else{
                $data['severitylevel'] = $severitylevel;
            }
			if($id!=''){
				$where = ['emphealthissue_id=?'=>$id];
                $data['modifiedby'] = $loginUserId;
                $data['modifieddate'] = gmdate("Y-m-d H:i:s");
				$actionflag = 2;
			}
			else
			{
				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$data['isactive'] = 'Y';
				$where = '';
				$actionflag = 1;
			}
			$Id = $healthissuetype_Model->SaveorUpdateData($data, $where);
			if($Id == 'update')
			{
				$tableid = $id;
				$this->view->successmessage = 'Health issue  updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Health issue  added successfully.';
                
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'emphealthissue';
		}else
		{
			$messages = $healthissuetype_Form->getMessages();
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
	public function viewpopupAction()
    {  
		try {
			
		
		$empiddocument_Form = new Default_Form_emphealthissue();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
         try {
			
        $healthissuetype_Model = new Default_Model_emphealthissue();
		$id = $this->_getParam('id');
        
        $data=$healthissuetype_Model->Getemphealthissuebyidview($id);
		(!empty($data[0]['hslname']))?$data[0]['hslname']=$data[0]['hslname']:$data[0]['hslname']=$data[0]['severitylevel'];
        $this->view->data = $data;
		$this->form=$empiddocument_Form;
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
    public function editAction()
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
          $id = str_replace('#mainDiv','',$id);
		 	if($id == '')		
			  $id = $userID;
			  
		 	$Uid = $id ?: $userID;
		 	 
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
							$empadditionaldetailsModal = new Default_Model_emphealthissue();
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

								$sort = 'DESC';$by = 'ehi.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'ehi.modifieddate';
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
				$id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
				$id = str_replace('#mainDiv','',$id);
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
								$empaddressmodel = new Default_Model_emphealthissue();
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
									$by = 'ehi.isactive';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = [];
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ehi.isactive';
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
						die();
						$this->view->rowexist = "norows";
					}
				} catch (Exception $e) {
					die();
					$this->view->rowexist = "norows";
				}
		
    }

    public function editpopupAction()
    {
       
        try {
        
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
            

			$auth = Zend_Auth::getInstance();
			if($auth->hasIdentity())
			{
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$healthissueaddpermission = sapp_Global::_checkprivileges(HEALTHISSUE,$loginuserGroup,$loginuserRole,'add');
			$healthissueserverityaddpermission = sapp_Global::_checkprivileges(HEALTHISSUESEVERITYLEVEL,$loginuserGroup,$loginuserRole,'add');
            $emphealthissue_Form = new Default_Form_emphealthissue();
            $healthissue_model = new Default_Model_healthissue();
            $emphealthissue_model = new Default_Model_emphealthissue();
            $id = $this->_request->getParam('id');
           
            $emphealthissue_data = $emphealthissue_model->Getemphealthissuebyid($id);

            $emphealthissue_Form->populate($emphealthissue_data[0]);
            $emphealthissue_Form->isactive->setValue(utlis::getcheckboxvalue($emphealthissue_data[0]['isactive'],'form'));
            $severitylevel_model = new Default_Model_healthseveritylevel();
            $severitylevel_data = $severitylevel_model->GetHealthseveritylevelSelect();
            $healthissue_data = $healthissue_model->GetHealthIssue();
            $emphealthissue_Form->healthissue_id->addMultiOptions([''=>'Select Health Issue']);
            $this->view->org_image = $emphealthissue_data[0]['attention'];
            if(!empty($healthissue_data))
            {
                foreach ($healthissue_data as  $value) {
                    $emphealthissue_Form->healthissue_id->addMultiOptions([$value['healthissue_id']=>$value['name']]);
                }
            }
            $emphealthissue_Form->severitylevel_id->addMultiOptions([''=>'Select Severity level']);
            if(!empty($severitylevel_data))
            {
                foreach ($severitylevel_data as $value) {
                    $emphealthissue_Form->severitylevel_id->addMultiOptions([$value['severitylevel_id']=>$value['value']]);
                }
            }
    
            if ($this->getRequest()->isPost()) {
                $this->save($emphealthissue_Form);
            }


    
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
       }
       $this->view->healthissueaddpermission = $healthissueaddpermission;
		
		$this->view->healthissueserverityaddpermission = $healthissueserverityaddpermission;
            $this->view->form = $emphealthissue_Form;
    }


	
}

