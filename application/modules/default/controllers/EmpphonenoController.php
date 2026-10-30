<?php

class Default_EmpphonenoController extends Zend_Controller_Action
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

  
	public function editAction(){
		
	
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
					$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('unitId');
					$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
				}
				if ($id == '')		$id = $userID;
				$Uid = ($id) ? $id : $userID;
				if ($Uid != "") {
					$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
				}
			$Model = new Default_Model_Phoneno();
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
	
										$sort = 'DESC';
										$by = 'phoneno';
										$pageNo = 1;
										$searchData = '';
										$searchQuery = '';
										$searchArray = array();
									} else {
										$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
										$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'phoneno';
										if ($dashboardcall == 'Yes')
											$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
										else
											$perPage = $this->_getParam('per_page', PERPAGE);
	
										$pageNo = $this->_getParam('page', 1);
										$searchData = $this->_getParam('searchData');
										$searchData = rtrim($searchData, ',');
									}
									$dataTmp = $Model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit');
	
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
				$Uid = ($id) ? $id : $userID;

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
								$empaddressmodel = new Default_Model_Phoneno();
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
									$by = 'contact';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = array();
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'contact';
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

    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        try {
			$auth = Zend_Auth::getInstance();
			if($auth->hasIdentity())
			{
				$loginUserid = $auth->getStorage()->read()->id;

				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$typepermission = '';
			$typepermission = sapp_Global::_checkprivileges(LINETYPE,$loginuserGroup,$loginuserRole,'add');
	$this->view->typepermission = $typepermission;
	$categorypermission = '';
	$categorypermission = sapp_Global::_checkprivileges(LINECATEGORY,$loginuserGroup,$loginuserRole,'add');
$this->view->categorypermission = $categorypermission;
			
        $util = new utlis();
        $Phoneno_Form = new Default_Form_Phoneno();
        $model = new Default_Model_Phoneno();
        $ltypemodel = new Default_Model_Linetype();
        $lcatmodel = new Default_Model_Linecategory();
        $ltypedata = $ltypemodel->getalltype();
        $lcatdata = $lcatmodel->getallcategory();
        $unitid = $this->_getParam('unitId');
        $Phoneno_Form->removeElement('company_id');
        $Phoneno_Form->removeElement('sbunit_id');
        $Phoneno_Form->removeElement('division_id');
        $Phoneno_Form->removeElement('department_id');
        $Phoneno_Form->removeElement('section_id');
        $Phoneno_Form->removeElement('employee_id');
        $Phoneno_Form->removeElement('project_id');
     
        $Phoneno_Form->setAction(BASE_URL . 'empphoneno/addpopup/unitId/'.$unitid.'/popup/1 ');
        $Phoneno_Form->linecategory_id = $util->populate_select($Phoneno_Form->linecategory_id,$lcatdata,'linecategory_id','name','select');
        $Phoneno_Form->linetype_id = $util->populate_select($Phoneno_Form->linetype_id,$ltypedata,'linetype_id','name','select');
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        if ($this->getRequest()->getPost()) {
			if ($Phoneno_Form->isValid($this->_request->getPost())) {
				$iscurrent = $this->_request->getParam('iscurrent');
				
				if ($iscurrent == 0) {
					$iscurrent = 'N';
				} else {
					$iscurrent = 'Y';
				}
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				session_start();
				
				$data = array(
					'location_id' => isset($_SESSION['location'])?$_SESSION['location']:115,
					'phoneno' => $this->_request->getParam('phone'),
					'contact' =>$this->_request->getParam('contact'),
                    'linetype_id'=>$this->_getParam('linetype_id'),
                    'linecategory_id'=>$this->_getParam('linecategory_id'),
					'description' => $this->_request->getParam('description'),
					'employee_id'=> $unitid,
					
				);

				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$data['isactive'] = "Y";
				$where = '';
				$actionflag = 1;
				$Id = $model->SaveorUpdateData($data, $where);
				$_SESSION['location'] = '';
				$tableid = $Id;
                $this->view->popup = '';
                $linecategoryData = $model->fetchAll('isactive = "Y"','phoneno')->toArray();
                 $opt ='';
                 foreach($linecategoryData as $record){
                     $opt .= sapp_Global::selectOptionBuilder($record['linecategory_id'], $record['name']);
                 }
                 $this->view->empphone = $opt;
                $this->view->controllername = 'linecategory';
                 $this->view->successmessage = 'Phone added successfully';

				
			} else {
				$messages = $Phoneno_Form->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgarray[$key] = $val2;
						break;
					}
				}
				$this->view->msgarray = $msgarray;
			}
		}
        $this->view->form = $Phoneno_Form; 
		$this->view->msgarray = $msgarray;
		$this->view->healthissueaddpermission = $linetypeaddpermission;
		
		$this->view->linecategoryaddpermission = $linecategoryaddpermission;

            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function editpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        try {
       
        $util = new utlis();
        $Phoneno_Form = new Default_Form_Phoneno();
        $model = new Default_Model_Phoneno();
        $lmodel = new Default_Model_Location();
        $ltypemodel = new Default_Model_Linetype();
        $lcatmodel = new Default_Model_Linecategory();
        $ltypedata = $ltypemodel->getalltype();
        $lcatdata = $lcatmodel->getallcategory();
        $unitid = $this->_getParam('unitId');
        $Phoneno_Form->removeElement('company_id');
        $Phoneno_Form->removeElement('sbunit_id');
        $Phoneno_Form->removeElement('division_id');
        $Phoneno_Form->removeElement('department_id');
        $Phoneno_Form->removeElement('section_id');
        $Phoneno_Form->removeElement('employee_id');
        $Phoneno_Form->removeElement('project_id');
        $id=$this->_getParam('id');
        $Phoneno_Form->setAction(BASE_URL . 'empphoneno/editpopup/id/'.$id.'/unitId/'.$unitid.'/popup/1 ');
        $Phoneno_Form->linecategory_id = $util->populate_select($Phoneno_Form->linecategory_id,$lcatdata,'linecategory_id','name','select');
        $Phoneno_Form->linetype_id = $util->populate_select($Phoneno_Form->linetype_id,$ltypedata,'linetype_id','name','select');
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $phonedata=$model->getphonenumber($id);
        $Phoneno_Form->populate($phonedata);
        $ldata=$lmodel->getLocationsbyid($phonedata['location_id']);
        
        $Phoneno_Form->isactive->setValue($util::getcheckboxvalue($phonedata['isactive'],'form'));
  
        $Phoneno_Form->location->setValue((isset($ldata[0]['address1']) ? $ldata[0]['address1'].',':'').(isset($ldata[0]['address2']) ? $ldata[0]['address2'].',':'').(isset($ldata[0]['address3']) ? $ldata[0]['address3'].',':''));
        if ($this->getRequest()->getPost()) {
			if ($Phoneno_Form->isValid($this->_request->getPost())) {
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				//session_start();
			
                
				$data = array(
					'location_id' =>( $_SESSION['location']!='')?$_SESSION['location']:127,
					'phoneno' => $this->_request->getParam('phoneno'),
					'contact' =>$this->_request->getParam('contact'),
                    'linetype_id'=>$this->_getParam('linetype_id'),
                    'linecategory_id'=>$this->_getParam('linecategory_id'),
					'description' => $this->_request->getParam('description'),
					'employee_id'=> $unitid,
                    'isactive'=>$util::getcheckboxvalue($this->_getParam('isactive'))
					
				);
               // die(Zend_Debug::dump($data));
				$data['modifiedby'] = $loginUserId;
				$data['modifieddate'] = gmdate("Y-m-d H:i:s");
				$where = 'phoneno_id='.$id;
				$actionflag = 1;
				$Id = $model->SaveorUpdateData($data, $where);
				$_SESSION['location'] = '';
				$tableid = $Id;
                $this->view->popup = 'close';
                $linecategoryData = $model->fetchAll('isactive = "Y"','phoneno')->toArray();
                 $opt ='';
                 foreach($linecategoryData as $record){
                     $opt .= sapp_Global::selectOptionBuilder($record['linecategory_id'], $record['name']);
                 }
                 $this->view->empphone = $opt;
                $this->view->controllername = 'empphoneno';
                 $this->view->successmessage = 'Phone added successfully';

				
			} else {
				$messages = $Phoneno_Form->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgarray[$key] = $val2;
						break;
					}
				}
				$this->view->msgarray = $msgarray;
			}
		}
        $this->view->form = $Phoneno_Form; 
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function viewpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        try {
            //code...
        
        $util = new utlis();
        
        $model = new Default_Model_Phoneno();
        $lmodel = new Default_Model_Location();
        $id=$this->_getParam('id');
       
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $phonedata=$model->getallphonedata($id);
        
        $this->view->data = $phonedata;
        $this->view->controllername = 'empphoneno';
        $this->view->successmessage = 'Phone added successfully';	
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
        die();
    }


    }
        //code...
    
}
