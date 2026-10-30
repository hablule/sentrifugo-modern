<?php 

class Default_EmppresenceController extends Zend_Controller_Action
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
				$employeeleavesModel = new Default_Model_Digitalpresence();
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
						$by = 'digitaladdress';
						$pageNo = 1;
						$searchData = '';
						$searchQuery = '';
						$searchArray = array();
					} else {
						$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
						$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'digitaladdress';
						if ($dashboardcall == 'Yes')
							$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
						else
							$perPage = $this->_getParam('per_page', PERPAGE);

						$pageNo = $this->_getParam('page', 1);
						$searchData = $this->_getParam('searchData');
						$searchData = rtrim($searchData, ',');
					}
					$dataTmp = $employeeleavesModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit');

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
    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        try {
			$auth = Zend_Auth::getInstance();
			if($auth->hasIdentity())
			{
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$digitalmediatypeaddpermission = sapp_Global::_checkprivileges(DIGITALMEDIATYPE,$loginuserGroup,$loginuserRole,'add');
			$this->view->digitalmediatypeaddpermission = $digitalmediatypeaddpermission;
        $util = new utlis();
        $Dform = new Default_Form_Digitalpresence();
        $model = new Default_Model_Digitalpresence();
		$dmmodel = new Default_Model_Digitalmedia();
        $unitid = $this->_getParam('unitId');
        $Dform->removeElement('company_id');
        $Dform->removeElement('sbunit_id');
        $Dform->removeElement('division_id');
        $Dform->removeElement('department_id');
        $Dform->removeElement('section_id');
        $Dform->removeElement('employee_id');
        $Dform->removeElement('project_id');
		$ddata = $dmmodel->getmedia();
        $Dform->setAction(BASE_URL . 'emppresence/addpopup/unitId/'.$unitid.'/popup/1 ');
        $Dform->digitalmedia_id = $util->populate_select($Dform->digitalmedia_id,$ddata,'digitalmedia_id','name','select');
        //$Dform->linetype_id = $util->populate_select($Dform->linetype_id,$ltypedata,'linetype_id','name','select');
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        if ($this->getRequest()->getPost()) {
			if ($Dform->isValid($this->_request->getPost())) {
				$iscurrent = $this->_request->getParam('iscurrent');
				
				if ($iscurrent == 0) {
					$iscurrent = 'N';
				} else {
					$iscurrent = 'Y';
				}
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				
				
				$data = array(
                    'digitalmedia_id'=>$this->_getParam('digitalmedia_id'),
                    'digitaladdress'=>$this->_getParam('digitaladdress'),
                    'description'=>$this->_getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s'),
					'employee_id'=>$unitid
                );

				$where = '';
				$actionflag = 1;
				$Id = $model->SaveorUpdateData($data, $where);
				$tableid = $Id;
                $this->view->popup = '';
                $linecategoryData = $model->fetchAll('isactive = "Y"','digitalmedia_id')->toArray();
                 $opt ='';
                 foreach($linecategoryData as $record){
                     $opt .= sapp_Global::selectOptionBuilder($record['digitalmedia_id'], $record['name']);
                 }
                 $this->view->empphone = $opt;
                $this->view->controllername = 'digitalmedia';
                 $this->view->successmessage = 'Digital presence added successfully';

				
			} else {
				$messages = $Dform->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgarray[$key] = $val2;
						break;
					}
				}
				$this->view->msgarray = $msgarray;
			}
		}
		
        $this->view->form = $Dform; 
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function editpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        try {
			$auth = Zend_Auth::getInstance();
			if($auth->hasIdentity())
			{
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$digitalmediatypeaddpermission = sapp_Global::_checkprivileges(DIGITALMEDIATYPE,$loginuserGroup,$loginuserRole,'add');
			$this->view->digitalmediatypeaddpermission = $digitalmediatypeaddpermission;
        $util = new utlis();
        $Dform = new Default_Form_Digitalpresence();
        $model = new Default_Model_Digitalpresence();
		$dmmodel = new Default_Model_Digitalmedia();
        $unitid = $this->_getParam('unitId');
        $Dform->removeElement('company_id');
        $Dform->removeElement('sbunit_id');
        $Dform->removeElement('division_id');
        $Dform->removeElement('department_id');
        $Dform->removeElement('section_id');
        $Dform->removeElement('employee_id');
        $Dform->removeElement('project_id');
        $id=$this->_getParam('id');
        $Dform->setAction(BASE_URL . 'emppresence/editpopup/id/'.$id.'/unitId/'.$unitid.'/popup/1 ');

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $phonedata=$model->getDPers($id);
		$ddata = $dmmodel->getmedia();
		$Dform->digitalmedia_id = $util->populate_select($Dform->digitalmedia_id,$ddata,'digitalmedia_id','name','select');
        $Dform->populate($phonedata);
        $Dform->isactive->setValue($util::getcheckboxvalue($phonedata['isactive'],'form'));
      
        if ($this->getRequest()->getPost()) {
			if ($Dform->isValid($this->_request->getPost())) {
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				//session_start();
			
                
				$data = array(
                    'digitalmedia_id'=>$this->_getParam('digitalmedia_id'),
                    'digitaladdress'=>$this->_getParam('digitaladdress'),
                    'description'=>$this->_getParam('description'),
                    'createdby'=>$loginUserId,
					'isactive'=>$util::getcheckboxvalue($this->_getParam('isactive')),
                    'createddate'=>gmdate('Y-m-d H:i:s'),
					'employee_id'=>$unitid
                );
               // die(Zend_Debug::dump($data));
				$data['modifiedby'] = $loginUserId;
				$data['modifieddate'] = gmdate("Y-m-d H:i:s");
				$where = 'digitalpresence_id='.$id;
				$actionflag = 1;
				$Id = $model->SaveorUpdateData($data, $where);
				$tableid = $Id;
                $this->view->popup = 'close';
                $linecategoryData = $model->fetchAll('isactive = "Y"','digitalmedia_id')->toArray();
                 $opt ='';
                 foreach($linecategoryData as $record){
                     $opt .= sapp_Global::selectOptionBuilder($record['digitalmedia_id'], $record['name']);
                 }
                 $this->view->empphone = $opt;
                $this->view->controllername = 'digitalmedia';
                 $this->view->successmessage = 'Digital persence added successfully';

				
			} else {
				$messages = $Dform->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgarray[$key] = $val2;
						break;
					}
				}
				$this->view->msgarray = $msgarray;
			}
		}
        $this->view->form = $Dform; 
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function viewpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        try {
        $model = new Default_Model_Digitalpresence();
        $id=$this->_getParam('id');
        $phonedata=$model->getalldp($id);
        $this->view->data = $phonedata;
        $this->view->controllername = 'emppresence';
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
        die();
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
								$empaddressmodel = new Default_Model_Digitalpresence();
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
									$by = 'digitaladdress';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = array();
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'digitaladdress';
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