<?php 

class Default_EmprecordaccessController extends Zend_Controller_Action
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

    // public function editAction()
    // {
       
      
    //     try {
            
    //     if (defined('EMPTABCONFIGS')) {
	// 		$empOrganizationTabs = explode(",", EMPTABCONFIGS);
           
    //       // die(Zend_Debug::dump(in_array('empphoneno', $empOrganizationTabs)));
	// 		if (in_array('emppresence', $empOrganizationTabs)) {
    //            // die();
	// 			$userID = "";
	// 			$auth = Zend_Auth::getInstance();
	// 			if ($auth->hasIdentity()) {
	// 				$loginUserId = $auth->getStorage()->read()->id;
	// 				$loginUserRole = $auth->getStorage()->read()->emprole;
	// 				$loginUserGroup = $auth->getStorage()->read()->group_id;
	// 			}
	// 			$id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
	// 			$conText = '';
	// 			$call = $this->_getParam('call');
                
	// 			if ($call == 'ajaxcall') {
	// 				$this->_helper->layout->disableLayout();
	// 				$id = ($this->getRequest()->getParam('unitId') != '') ? $this->getRequest()->getParam('unitId') : $this->getRequest()->getParam('unitId');
	// 				$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
	// 				$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
	// 			}
	// 			if ($id == '')		$id = $userID;
	// 			$Uid = ($id) ? $id : $userID;
	// 			Zend_Debug::dump($Uid);
	// 			$employeeModal = new Default_Model_Employee();
	// 			try {
	// 				if ($Uid && is_numeric($Uid) && $Uid > 0) {
	// 					$usersModel = new Default_Model_Users();
	// 					$empdata = $employeeModal->getActiveEmployeeData($Uid);
                  
	// 					$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
                       
	// 					if ($empdata == 'norows') {
	// 						$this->view->rowexist = "norows";
	// 						$this->view->empdata = "";
	// 					} else {
	// 						$this->view->rowexist = "rows";
	// 						if (!empty($empdata)) {
	// 							$empaddressmodel = new Default_Model_Digitalpresence();
	// 							$view = Zend_Layout::getMvcInstance()->getView();
	// 							$objname = $this->_getParam('objname');
	// 							$refresh = $this->_getParam('refresh');
	// 							$dashboardcall = $this->_getParam('dashboardcall', null);
	// 							$data = array();
	// 							$searchQuery = '';
	// 							$searchArray = array();
	// 							$tablecontent = '';
	// 							if ($refresh == 'refresh') {
	// 								if ($dashboardcall == 'Yes')
	// 									$perPage = DASHBOARD_PERPAGE;
	// 								else
	// 									$perPage = PERPAGE;

	// 								$sort = 'DESC';
	// 								$by = 'digitaladdress';
	// 								$pageNo = 1;
	// 								$searchData = '';
	// 								$searchQuery = '';
	// 								$searchArray = array();
	// 							} else {
	// 								$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
	// 								$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'digitaladdress';
	// 								if ($dashboardcall == 'Yes')
	// 									$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
	// 								else
	// 									$perPage = $this->_getParam('per_page', PERPAGE);

	// 								$pageNo = $this->_getParam('page', 1);
	// 								$searchData = $this->_getParam('searchData');
	// 								$searchData = rtrim($searchData, ',');
	// 							}
	// 							$dataTmp = $empaddressmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText,'edit');

	// 							array_push($data, $dataTmp);
	// 							$this->view->dataArray = $data;
	// 							$this->view->call = $call;
	// 							$this->view->employeedata = $employeeData[0];
	// 							$this->view->id = $id;
	// 							$this->view->messages = $this->_helper->flashMessenger->getMessages();
	// 						}
	// 						$this->view->empdata = $empdata;
	// 					}
	// 				} else {
	// 					$this->view->rowexist = "norows";
	// 				}
	// 			} catch (\Throwable $e) {
	// 				$this->view->rowexist = "norows";
    //                 Zend_Debug::dump($e->getFile);
	// 			}
	// 		} else {
               
	// 			$this->_redirect('error');
	// 		}
	// 	} else {
	// 		$this->_redirect('error');
	// 	}
    // } catch (\Throwable $th) {
    //    Zend_Debug::dump($th->getMessage());
    // }
    // }
	public function editAction()
	{
        try {
            //code...
		if (defined('EMPTABCONFIGS')) {
			$empOrganizationTabs = explode(",", EMPTABCONFIGS);

			if (in_array('emp_recordaccess', $empOrganizationTabs)) {

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
				$employeeleavesModel = new Default_Model_emprecordaccessdetail();
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
						$by = 'ad.emp_id';
						$pageNo = 1;
						$searchData = '';
						$searchQuery = '';
						$searchArray = [];
					} else {
						$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
						$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ad.emp_id';
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
			} else {
				$this->_redirect('error');
			}
		} else {
			$this->_redirect('error');
		}
    } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());
    }
	}
    public function addpopupAction()
    {
		try {
			//code...
	
            //code...

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
       
            $auth = Zend_Auth::getInstance();
            if($auth->hasIdentity())
            {
                $loginUserid = $auth->getStorage()->read()->id;
            }
        $util = new utlis();
        $form = new Default_Form_emprecordaccessdetail();
        $model = new Default_Model_emprecordaccessdetail();
     
        $recordmodel = new Default_Model_recordaccess();
        $unitid = $this->_getParam('unitId');
     
        $recorddata=$recordmodel->getrecordlistListforemp();
		//die(json_encode($posdata));

        // $form->setAction(BASE_URL . 'emprecoraccess/addpopup/unitId/'.$unitid.'/popup/1 ');
$form->removeElement('emp_id');
$form->removeElement('isactive');
$form->removeElement('emprecordaccess_id2');

        $util = new utlis();
        $form->emprecordaccess_id = $util->populate_select($form->emprecordaccess_id,$recorddata,'id','name','Select');
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
		if ($this->getRequest()->isPost()) {
      
			if ($form->isValid($this->_request->getPost())) {
				try{

				$emprecordaccess_id = $this->_request->getParam('emprecordaccess_id');

				for($i=0;$i<sizeof($emprecordaccess_id);$i++)
				{
                $data = [
                    'emprecordaccess_id'=>$emprecordaccess_id[$i],
                    'emp_id'=>$unitid,
                    'createdby'=>$loginUserid,
                    'description'=>$this->_getParam('description'),
                    'createddate'=>gmdate('Y-m-d H:i:s'),
					'isactive'=>'Y'
                ];

				$where = '';
				$actionflag = 1;
				

				
				$Id = $model->SaveorUpdateData($data, $where);
			}
				$this->view->controllername = 'emprecordaccess';
				$this->view->successmessage = 'Record Access Assigned added successfully.';
					$tableid = $Id;
					$this->view->popup = '';
					 $opt ='';

					} catch (\Throwable $th) {
						Zend_Debug::dump($th->getMessage());
						$code=$th->getCode();
						if($code=2300){
							$msgarray['emprecordaccess_id']='Access already exist ';
							$this->view->msgarray = $msgarray;
						}	else{
							$tableid = $Id;
							$this->view->controllername = 'emprecordaccess';
							$this->view->successmessage = 'Record Access Assigned added successfully.';
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
        $this->view->msgarray = $msgarray;

            //code...
		} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage())	;
	}
    }

   
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

        $form = new Default_Form_emprecordaccessdetail();
        $model = new Default_Model_emprecordaccessdetail();
      
        $recordmodel = new Default_Model_recordaccess();
        $unitid = $this->_getParam('unitId');
       
        // $form->setAction(BASE_URL . 'emprole/editpopup/unitId/'.$unitid.'/popup/1 ');
           $form->removeElement('emp_id');
		   $form->removeElement('emprecordaccess_id');
		   $form->emprecordaccess_id2->setAttrib('readonly',true);
		   $form->emprecordaccess_id2->setRequired(false);
        $util = new utlis();
            $id=$this->_getParam('id');
			
              $recorddata=$recordmodel->getrecordlistListforemp();

        $form->emprecordaccess_id2 = $util->populate_select($form->emprecordaccess_id2,$recorddata,'id','name','Select');
	
        $datas=$model->getdetails($id);

    $form->populate($datas);
	$form->isactive->setValue($util::getcheckboxvalue($datas['isactive'],'form'));
	$form->emprecordaccess_id2->setValue($datas['emprecordaccess_id']);

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
		if ($this->getRequest()->isPost()) {
           

				if ($form->isValid($this->_request->getPost())) {
					
                $data = [
                    // 'emprecordaccess_id'=>$this->_request->getParam('emprecordaccess_id2'),
                    'emp_id'=>$unitid,
                 'description'=>$this->_request->getParam('description'),

					'isactive'=>$util::getcheckboxvalue($this->_getParam('isactive'))

                  
                ];
                $data['modifiedby'] = $loginUserId;
                		$data['modifieddate'] = gmdate("Y-m-d H:i:s");
            		$where = 'emprecordaccessdetail_id='.$id;
				$actionflag = 2;
				try {
					//code...
				
				$Id = $model->SaveorUpdateData($data, $where);
                $this->view->controllername = 'emprecordaccess';
			$this->view->successmessage = 'Record Access Assigned updated successfully.';
				$tableid = $Id;
                $this->view->popup = '';
                 $opt ='';
				} catch (\Throwable $th) {
					
						$tableid = $Id;
						$this->view->controllername = 'emprecordaccess';
						$this->view->successmessage = 'Record Access Assigned updated successfully.';
							$tableid = $Id;
							$this->view->popup = '';
							 $opt ='';
												
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
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function viewpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        try {
        $model = new Default_Model_emprecordaccessdetail();
        $id=$this->_getParam('id');
        $posdata=$model->getdetailsforview($id);
        
        $this->view->data = $posdata;
        $this->view->controllername = 'emprecordaccess';
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
        die();
    }


    }

	public function viewAction()
	{    
        try {
        
		if (defined('EMPTABCONFIGS')) {
			$empOrganizationTabs = explode(",", EMPTABCONFIGS);
	
			if (in_array('emp_recordaccess',$empOrganizationTabs)) {
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
								$empaddressmodel = new Default_Model_emprecordaccessdetail();
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
									$by = 'ad.emp_id';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = [];
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ad.emp_id';
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
			} else {
				//die();
				$this->_redirect('error');
			}
		} else {
			//die();
			$this->_redirect('error');
		}
            //code...
        } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());        }
	}


}


?>