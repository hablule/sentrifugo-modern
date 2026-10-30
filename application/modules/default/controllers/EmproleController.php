<?php 

class Default_EmproleController extends Zend_Controller_Action
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
				$usrdata = $usersModel->getUserFromEmp($id);
				$tmp_id = $Uid;
				$Uid = $usrdata[0]['id'] ?? -1;
				$employeeleavesModel = new Default_Model_emprole();
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
						$by = 'r.id';
						$pageNo = 1;
						$searchData = '';
						$searchQuery = '';
						$searchArray = [];
					} else {
						$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
						$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'r.id';
						if ($dashboardcall == 'Yes')
							$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
						else
							$perPage = $this->_getParam('per_page', PERPAGE);

						$pageNo = $this->_getParam('page', 1);
						$searchData = $this->_getParam('searchData');
						$searchData = rtrim($searchData, ',');
					}
			$dataTmp = $employeeleavesModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit',$tmp_id);

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
		try {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
       
            $auth = Zend_Auth::getInstance();
            if($auth->hasIdentity())
            {
                $loginUserid = $auth->getStorage()->read()->id;
            }
        $util = new utlis();
        $form = new Default_Form_emprole();
        $model = new Default_Model_emprole();
        $dmmodel = new Default_Model_Digitalmedia();
		$usrmodel = new Default_Model_Users();
        $rolemodel = new Default_Model_Roles();
        $unitid = $this->_getParam('unitId');
		$usrdata = $usrmodel->getUserFromEmp($unitid); 
        $roledata=$rolemodel->getRoles();
		
		
		if (!isset($usrdata[0]['id'])) {
			
			$this->view->rowexist = 'norows';
			
		}else{
		
        $form->setAction(BASE_URL . 'emprole/addpopup/unitId/'.$unitid.'/popup/1 ');
		$form->removeElement('employee_id');
		$form->removeElement('isactive');

// $form->removeElement('company_id');
// $form->removeElement('sbunit_id');
// $form->removeElement('division_id');
// $form->removeElement('department_id');
// $form->removeElement('section_id');
// $form->removeElement('team_id');

        $util = new utlis();
        $form->role_id = $util->populate_select($form->role_id,$roledata,'role_id','name','Select');
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
		if ($this->getRequest()->isPost()) {
      
			if ($form->isValid($this->_request->getPost())) {
		
                $data = [
                    'role_id'=>$this->_getParam('role_id'),
                    'user_id'=>$usrdata[0]['id'],
                    'datefrom' => sapp_Global::change_date($this->_request->getParam('datefrom'), 'database'),
                    'dateto' => sapp_Global::change_date($this->_request->getParam('dateto'), 'database'),
                    'createdby'=>$loginUserid,
                    'description'=>$this->_getParam('description'),
                    'createddate'=>gmdate('Y-m-d H:i:s'),
					'isactive'=>'Y'
                ];

				$where = '';
				$actionflag = 1;
				

				try{
				$Id = $model->SaveorUpdateData($data, $where);
				$this->view->controllername = 'emprole';
				$this->view->successmessage = 'Role Assigned added successfully.';
					$tableid = $Id;
					$this->view->popup = '';
					 $opt ='';

					} catch (\Throwable $th) {
						Zend_Debug::dump($th->getMessage());
						$code=$th->getCode();
						if($code=2300){
							$msgarray['role_id']='Role already exist ';
							$this->view->msgarray = $msgarray;
						}	else{
							$tableid = $Id;
							$this->view->controllername = 'emprole';
							$this->view->successmessage = 'Role Assigned added successfully.';
								$tableid = $Id;
								$this->view->popup = '';
								 $opt ='';
								}					
			}
				
			} else {
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
       
         	
		}   //code...
		$this->view->form = $form; 
        } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());        }
    }

    // public function editpopupAction()
    // {
    //     Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
    //     try {
       
    //     $util = new utlis();
    //     $Dform = new Default_Form_Digitalpresence();
    //     $model = new Default_Model_Digitalpresence();
	// 	$dmmodel = new Default_Model_Digitalmedia();
    //     $unitid = $this->_getParam('unitId');
    //     $Dform->removeElement('company_id');
    //     $Dform->removeElement('sbunit_id');
    //     $Dform->removeElement('division_id');
    //     $Dform->removeElement('department_id');
    //     $Dform->removeElement('section_id');
    //     $Dform->removeElement('employee_id');
    //     $Dform->removeElement('project_id');
    //     $id=$this->_getParam('id');
    //     $Dform->setAction(BASE_URL . 'emppresence/editpopup/id/'.$id.'/unitId/'.$unitid.'/popup/1 ');

    //     $auth = Zend_Auth::getInstance();
    //     if ($auth->hasIdentity()) {
    //         $loginUserId = $auth->getStorage()->read()->id;
    //     }
    //     $phonedata=$model->getDPers($id);
	// 	$ddata = $dmmodel->getmedia();
	// 	$Dform->digitalmedia_id = $util->populate_select($Dform->digitalmedia_id,$ddata,'digitalmedia_id','name','select');
    //     $Dform->populate($phonedata);
    //     $Dform->isactive->setValue($util::getcheckboxvalue($phonedata['isactive'],'form'));
      
    //     if ($this->getRequest()->getPost()) {
	// 		if ($Dform->isValid($this->_request->getPost())) {
	// 			$date = new Zend_Date();
	// 			$actionflag = '';
	// 			$tableid  = '';
	// 			//session_start();
			
                
	// 			$data = array(
    //                 'digitalmedia_id'=>$this->_getParam('digitalmedia_id'),
    //                 'digitaladdress'=>$this->_getParam('digitaladdress'),
    //                 'description'=>$this->_getParam('description'),
    //                 'createdby'=>$loginUserId,
	// 				'isactive'=>$util::getcheckboxvalue($this->_getParam('isactive')),
    //                 'createddate'=>gmdate('Y-m-d H:i:s'),
	// 				'employee_id'=>$unitid
    //             );
    //            // die(Zend_Debug::dump($data));
	// 			$data['modifiedby'] = $loginUserId;
	// 			$data['modifieddate'] = gmdate("Y-m-d H:i:s");
	// 			$where = 'digitalpresence_id='.$id;
	// 			$actionflag = 1;
	// 			$Id = $model->SaveorUpdateData($data, $where);
	// 			$tableid = $Id;
    //             $this->view->popup = 'close';
    //             $linecategoryData = $model->fetchAll('isactive = "Y"','digitalmedia_id')->toArray();
    //              $opt ='';
    //              foreach($linecategoryData as $record){
    //                  $opt .= sapp_Global::selectOptionBuilder($record['digitalmedia_id'], $record['name']);
    //              }
    //              $this->view->empphone = $opt;
    //             $this->view->controllername = 'digitalmedia';
    //              $this->view->successmessage = 'Digital persence added successfully';

				
	// 		} else {
	// 			$messages = $Dform->getMessages();
	// 			foreach ($messages as $key => $val) {
	// 				foreach ($val as $key2 => $val2) {
	// 					$msgarray[$key] = $val2;
	// 					break;
	// 				}
	// 			}
	// 			$this->view->msgarray = $msgarray;
	// 		}
	// 	}
    //     $this->view->form = $Dform; 
    //         //code...
    //     } catch (\Throwable $th) {
    //         Zend_Debug::dump($th->getMessage());
    //     }
    // }
    public function editpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        try {
            $auth = Zend_Auth::getInstance();
            if($auth->hasIdentity())
            {
                $loginUserid = $auth->getStorage()->read()->id;
            }
        $util = new utlis();
        $form = new Default_Form_emprole();
        $model = new Default_Model_emprole();
		$usrmodel = new Default_Model_Users();
        $rolemodel = new Default_Model_Roles();
        $unitid = $this->_getParam('unitId');
		$usrdata = $usrmodel->getUserFromEmp($unitid);
		
		if (!isset($usrdata[0]['id'])) {
			$this->view->rowexist = 'norows';
		}
		else{ 
        $form->setAction(BASE_URL . 'emprole/editpopup/unitId/'.$unitid.'/popup/1 ');
           $form->removeElement('employee_id');
        $util = new utlis();
            $id=$this->_getParam('id');
			// $form->removeElement('employee_id');
			// $form->removeElement('company_id');
			// $form->removeElement('sbunit_id');
			// $form->removeElement('division_id');
			// $form->removeElement('department_id');
			// $form->removeElement('section_id');
			// $form->removeElement('team_id');      
              $roledata=$rolemodel->getRoles();

        $form->role_id = $util->populate_select($form->role_id,$roledata,'role_id','name','Select');
	
        $datas=$model->getalldatas($id);

    $form->populate($datas);
	$form->isactive->setValue($util::getcheckboxvalue($datas['isactive'],'form'));

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
		if ($this->getRequest()->isPost()) {
           

				if ($form->isValid($this->_request->getPost())) {
		
                $data = [
                    'role_id'=>$this->_getParam('role_id'),
                    'user_id'=>$usrdata[0]['id'],
                    'datefrom' => sapp_Global::change_date($this->_request->getParam('datefrom'), 'database'),
                    'dateto' => sapp_Global::change_date($this->_request->getParam('dateto'), 'database'),
                    'description'=>$this->_getParam('description'),
					'isactive'=>$util::getcheckboxvalue($this->_getParam('isactive'))

                  
                ];
                $data['modifiedby'] = $loginUserId;
                		$data['modifieddate'] = gmdate("Y-m-d H:i:s");
            		$where = 'userrole_id='.$id;
				$actionflag = 2;
				try {
					//code...
				
				$Id = $model->SaveorUpdateData($data, $where);
                $this->view->controllername = 'emprole';
			$this->view->successmessage = 'Roll Assigned updated successfully.';
				$tableid = $Id;
                $this->view->popup = '';
                 $opt ='';
				} catch (\Throwable $th) {
					$code=$th->getCode();
					if($code=2300){
						$msgarray['role_id']='Role already exist ';
						$this->view->msgarray = $msgarray;
					}	else{
						$tableid = $Id;
						$this->view->controllername = 'emprole';
						$this->view->successmessage = 'Role Assigned updated successfully.';
							$tableid = $Id;
							$this->view->popup = '';
							 $opt ='';
							}					
}

				
			} else {
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
	  }  //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function viewpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        try {
        $model = new Default_Model_emprole();
        $id=$this->_getParam('id');
        $posdata=$model->getalldatas($id);
        $this->view->data = $posdata;
        $this->view->controllername = 'emprole';
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
								$empaddressmodel = new Default_Model_emprole();
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
									$by = 'isactive';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = [];
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'isactive';
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
						
						$this->view->rowexist = "norows";
					}
				} catch (Exception $e) {

					$this->view->rowexist = "norows";
				}
		
	}


}


?>