<?php

class Default_empiddocumentController extends Zend_Controller_Action
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
							$empadditionaldetailsModal = new Default_Model_empiddocument();
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

								$sort = 'DESC';$by = 'empidd.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
							}
							else
							{
								
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'empidd.modifieddate';
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
    public function addAction()
    {
        $empiddocument_Form = new Default_Form_empiddocument();
        $this->view->form = $empiddocument_Form; 
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
          $id = str_replace('#mainDiv','',$id);
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
							$empadditionaldetailsModal = new Default_Model_empiddocument();
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
								$sort = 'DESC';$by = 'empidd.modifieddate';
								$pageNo = 1;
								$searchData = '';
								$searchQuery = '';
								$searchArray='';
							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'empidd.modifieddate';
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
		$savefolder = DOCUMENT_IMAGE_PATH;		// folder for upload



		$max_size = 1024;			// maxim size for image file, in KiloBytes

		// Allowed image types
		$allowtype = array('gif', 'jpg', 'jpeg', 'png');
		//$allowtype = array('docx', 'doc','PDF','pdf');

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
			'msg' => $result_msg
		);
		return $result;
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
			$iddocumentaddpermission = sapp_Global::_checkprivileges(DOCUMENT,$loginuserGroup,$loginuserRole,'add');
			$iddocumentprovideraddpermission = sapp_Global::_checkprivileges(DOCUMENTPROVIDER,$loginuserGroup,$loginuserRole,'add');
			$iddocumentstatuslistaddpermission = sapp_Global::_checkprivileges(DOCUMENTSTATUSLIST,$loginuserGroup,$loginuserRole,'add');
        $empiddocument_Form = new Default_Form_empiddocument();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
        $id = $this->_getParam('id');
        Zend_Debug::dump($id);
        $healthissuetype_Model = new Default_Model_empiddocument();

        if($id!=null)
        {
            $data=$healthissuetype_Model->getempiddocumentbyid($id);
            $empiddocument_Form->populate($data[0]);
			$empiddocument_Form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'],'form'));
			$this->view->org_image = $data[0]['documentimg'];
			
			

        }
        if ($this->getRequest()->isPost()) {
            $this->save($empiddocument_Form);
        }
        $this->view->form = $empiddocument_Form;
		$this->view->iddpremission = $iddocumentaddpermission;
		$this->view->iddproviderpremission = $iddocumentprovideraddpermission;
		$this->view->iddstatuslistpremission = $iddocumentstatuslistaddpermission;
          //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        } 
    }

	public function viewpopupAction()
    {  $empiddocument_Form = new Default_Form_empiddocument();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
         try {
			
        $healthissuetype_Model = new Default_Model_empiddocument();
		$id = $this->_getParam('id');
        
        $data=$healthissuetype_Model->getempiddocumentbyid($id);
        $this->view->data = $data;
		$this->form=$empiddocument_Form;
		$this->view->org_image = $data[0]['documentimg'];
			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		 } 
    }
    public function checkisnumberedAction()
    {
        $id = $this->_getParam('id');
		if($id!=null){
		$model = new Default_Model_iddocument();
		$iddocumentdata = $model->getIddocumentById($id);
		if ($iddocumentdata[0]['isnumbered'] == 'Y') 
			$this->_helper->json(array('result'=>true));
		}
		$this->_helper->json(array('result'=>false));

        
    }
    public function save($healthissuetype_Form)
    {
        try {
            
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
					$extArr = array('pdf', 'doc', 'docx');
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
			$healthissuetype_Model = new Default_Model_empiddocument();
			$id = $this->getRequest()->getParam('id');
            $empid = $this->getRequest()->getParam('unitId');
			$user_id = $loginUserId;
            $isactive = $this->_getParam('isactive');
			$actionflag = '';
			$tableid  = '';

			$data = array(
				                 'empid'=>$empid,
				                 'iddocument_id'=>$this->_getParam('iddocument_id'),
								 'idnumber'=>$this->_getParam('description'),
                                 'description'=>$this->_getParam('description'),
                                 'isactive'=>utlis::getcheckboxvalue($isactive),
                                 'iddocumentprovider_id'=>$this->_getParam('iddocumentprovider_id'),
                                 'iddocumentstatuslist_id'=>$this->_getParam('iddocumentstatuslist_id'),
                                 'iddocumentstatus'=>$this->_getParam('iddocumentstatus'),
                                 'issuedate'=>sapp_Global::change_date($this->_getParam('issuedate'),'database'),
                                 'expirydate'=>sapp_Global::change_date($this->_getParam('expirydate'),'database'),
                                 'documentimg'=>$imagepath
			);
           
			if($id!=''){
				$where = array('empiddocument_id=?'=>$id);
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
			$this->view->controllername = 'empiddocument';
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
							$empadditionaldetailsModal = new Default_Model_empiddocument();
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

								$sort = 'DESC';$by = 'empidd.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'empidd.modifieddate';
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
}
