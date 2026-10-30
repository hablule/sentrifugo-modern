<?php 

class Default_empuserController extends Zend_Controller_Action
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
        die();
	}

    
	public function editAction()
	{
        try {
            //code...
       
		

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
				$Uid = $id ?: $userID;
				if ($Uid != "") {
					$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
				}
				$model = new Default_Model_Users();
				$employeeModal = new Default_Model_Employee();
				$isrowexist = $employeeModal->getsingleEmployeeData($id);

				if ($isrowexist == 'norows')
					$this->view->rowexist = "norows";
				else
					$this->view->rowexist = "rows";
				$empdata = $employeeModal->getActiveEmployeeData($id);
				
				$this->view->empdata = $empdata;

				try {
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
						$by = 'username';
						$pageNo = 1;
						$searchData = '';
						$searchQuery = '';
						$searchArray = [];
					} else {
						$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
						$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'username';
						if ($dashboardcall == 'Yes')
							$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
						else
							$perPage = $this->_getParam('per_page', PERPAGE);

						$pageNo = $this->_getParam('page', 1);
						$searchData = $this->_getParam('searchData');
						$searchData = rtrim($searchData, ',');
					}
				$dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit');

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
		
    } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());  
  }
	}
    public function addpopupAction()
    {
		
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
       
		$form = new Default_Form_User();
        $model = new Default_Model_User();
        $auth = Zend_Auth::getInstance();
		$unitid = $this->_getParam('unitId');

        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $modelemp=new Default_Model_Employee();
        $empdatas=$modelemp->getEmplist();
        
        $sitepreferencemodel = new Default_Model_Sitepreference();
		$sitepreferenceArr = $sitepreferencemodel->SitePreferanceData();
		
		if(!empty($sitepreferenceArr))
		{
				if($sitepreferenceArr[0]['passwordid'] == 1)
				{ 
				
					$form->password->addValidator("regex",true,[
									'pattern'=> '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/',
								   'messages'=>[
									   'regexNotMatch'=>'Please enter only alphanumeric characters.'
								   ]
					]); 
					
					$form->confirmpassword->addValidator("regex",true,[
									'pattern'=> '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/',
								   'messages'=>[
									   'regexNotMatch'=>'Please enter only alphanumeric characters.'
								   ]
					]);			
				} 
				
				else if($sitepreferenceArr[0]['passwordid'] == 2)
				{
					$form->password->addValidator("regex",true,[
								  'pattern'=> '/^(?=.*[0-9])(?=.*[a-zA-Z])(?=.*[.\-#$@&\_*])([a-zA-Z0-9.\-#$@&\_*]+)$/',
								   'messages'=>[
									   'regexNotMatch'=>'Please enter only characters,numbers and special characters.'
								   ]
					]);
					
					$form->confirmpassword->addValidator("regex",true,[
								  'pattern'=> '/^(?=.*[0-9])(?=.*[a-zA-Z])(?=.*[.\-#$@&\_*])([a-zA-Z0-9.\-#$@&\_*]+)$/',
								   'messages'=>[
									   'regexNotMatch'=>'Please enter only characters,numbers and special characters.'
								   ]
					]);
				}
				else if($sitepreferenceArr[0]['passwordid'] == 3)
				{
					$form->password->addValidator("regex",true,[
								   'pattern'=>'/^[0-9]+$/', 
								   'messages'=>[
									   'regexNotMatch'=>'Please enter numbers only.'
								   ]
					]);
					
					$form->confirmpassword->addValidator("regex",true,[
								   'pattern'=>'/^[0-9]+$/', 
								   'messages'=>[
									   'regexNotMatch'=>'Please enter numbers only.'
								   ]
					]);
				}
				else
				{
					$form->password->addValidator("regex",true,[
									'pattern'=> '/^(?=.*[0-9])(?=.*[.\-#$@&\_*])([0-9.\-#$@&\_*]+)$/',
								   'messages'=>[
									   'regexNotMatch'=>'Please enter only numbers and special characters.'
								   ]
					]); 
					
					$form->confirmpassword->addValidator("regex",true,[
									'pattern'=> '/^(?=.*[0-9])(?=.*[.\-#$@&\_*])([0-9.\-#$@&\_*]+)$/',
								   'messages'=>[
									   'regexNotMatch'=>'Please enter only numbers and special characters.'
								   ]
					]);			
				}
		}
		else
        {
		    $form->password->addValidator("regex",true,[
									'pattern'=> '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/', 
								   'messages'=>[
									   'regexNotMatch'=>'Please enter only alphanumeric characters.'
								   ]
					]); 
					
			$form->confirmpassword->addValidator("regex",true,[
							'pattern'=> '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/', 
						   'messages'=>[
							   'regexNotMatch'=>'Please enter only alphanumeric characters.'
						   ]
			]);	
        } 		


                $util = new utlis();
                $form->employee_id = $util->populate_select($form->employee_id,$empdatas,'id','userfullname','Select');
				$form->removeElement('employee_id');
				if ($this->getRequest()->isPost()) {
					if ($form->isValid($this->getRequest()->getPost())) {
						if($this->_request->getParam('password')!=$this->_request->getParam('confirmpassword')){
							$msgarray['allfk'] ='Password and confirm password in not matched';
						} else{
							
					   // $salt = $this->_getParam('username').uniqid();
						$hashpassword = password_hash($this->_request->getParam('password'),PASSWORD_DEFAULT);
						
						$emailaddress=$this->_request->getParam('emailaddress');
						$data = [
							'username'=>$this->_request->getParam('username'),
							'emailaddress'=>$this->_request->getParam('emailaddress'),
							'code'=>$this->_request->getParam('code'),
							'description'=>$this->_request->getParam('description'),
							'emppassword'=>$hashpassword,
							'empid'=>$unitid,

							'createdby'=>$loginUserId,
							'createddate'=>gmdate('Y-m-d H:i:s')
						];
						//die(json_encode($data));

						$Id = $model->SaveorUpdateData($data,'');
		
						$base_url = 'http://'.$this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
							$view = $this->getHelper('ViewRenderer')->view;
							$this->view->emp_name = $this->_request->getParam('username');
							$this->view->password = $this->_request->getParam('password');
							$this->view->emp_id = $this->_getParam('username');
							$this->view->base_url=$base_url;
							$text = $view->render('mailtemplates/newpassword.phtml');
							$options['subject'] = APPLICATION_NAME.': Login Credentials';
							$options['header'] = 'Greetings from Sentrifugo';
							$options['toEmail'] = $emailaddress;
							$options['toName'] = $this->view->emp_name;
							$options['message'] = $text;
							$result = sapp_Global::_sendEmail($options);
							
							//end of mailing
							$this->view->controllername = 'empuser';
							$this->view->successmessage = 'User added successfully.';
								   $tableid = $Id;
							   $this->view->popup = '';
							$opt ='';
					}
					
				  
				}
					
					$messages = $form->getMessages();
					//die(json_encode($messages));
		
					foreach ($messages as $key => $val) {
						foreach ($val as $key2 => $val2) {
							$msgArray[$key] = $val2;
							break;
						}
					}
				
			}
	
        $this->view->form = $form; 
		$this->view->msgarray = $msgArray;
        $this->view->form = $form; 
		
    }

   
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

		$form = new Default_Form_User();
		$model = new Default_Model_User();

		$data = $model->getsingleuser($id);
		$data=$data[0];
		$this->view->data = $data;
		$this->view->form = $form;
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
						$isrowexist = $employeeModal->getsingleEmployeeData($id);

						if ($isrowexist == 'norows')
							$this->view->rowexist = "norows";
						else
							$this->view->rowexist = "rows";
						$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
						if ($empdata == 'norows') {
							$this->view->rowexist = "norows";
							$this->view->empdata = "";
						} else {
							$this->view->rowexist = "rows";
							if (!empty($empdata)) {
								$empaddressmodel = new Default_Model_Users();
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
									$by = 'code';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = [];
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'code';
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


}


?>