<?php

class Default_emppositionController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }
    public function indexAction()
    {
        try {
            //code...
     
      $dmtmodel = new Default_Model_empposition();
      $call = $this->_getParam('call');

      if ($call == 'ajaxcall') {
          $this->_helper->layout->disableLayout();
      }

      $refresh = $this->_getParam('refresh');
      $dashboradcall = $this->_getParam('dashboradcall');

      $data = array();

      if ($refresh == 'refresh') {
          if ($dashboradcall == 'Yes') {
              $perPage = DASHBOARD_PERPAGE;
          }
          else{
              $perPage = PERPAGE;
          }
          $sort = 'DESC';
          $by = 'position_id';
          $pageNo = 1;
          $searchData = '';
      }
      else{
          $sort = ($this->_getParam('sort')!='') ? $this->_getParam('sort'):'DESC';
          $by = ($this->_getParam('by')!='') ? $this->_getParam('by'):'position_id';
          if($dashboradcall == 'Yes')
          $perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
          else
          $perPage = $this->_getParam('per_page',PERPAGE);

          $pageNo = $this->_getParam('page',1);
          $searchData = $this->_getParam('searchData');
          $searchData = rtrim($searchData,',');
      }
      $dataTmp = $dmtmodel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboradcall);

      array_push($data,$dataTmp);
      $this->view->dataArray = $data;
      $this->view->call = $call;
      $this->view->messages = $this->_helper->flashMessenger->getMessages();
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }

    }
    public function addAction()
    {
        try {
            //code...
        
        $form = new Default_Form_empposition();
        $model = new Default_Model_empposition();
        $dmmodel = new Default_Model_Digitalmedia();
        $companymode = new Default_Model_Organisationinfo();
        $busniessunit = new Default_Model_Businessunits();
		$divisionmodel = new Default_Model_division();
		$departmentmodel = new Default_Model_Departments();
		$sectionmodel = new Default_Model_Section();
		$projectmodel = new Default_Model_Project();
        $teammodel = new Default_Model_Team();
        $positionmodel = new Default_Model_Positions();
        $titlemodel = new Default_Model_Jobtitles();

        $modelemp=new Default_Model_Employee();
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity())
        {
            $loginUserid = $auth->getStorage()->read()->id;
        }

        $prodata = $projectmodel->getprojectlist();
        $secdata = $sectionmodel->getsectionlist();
        $depdata = $departmentmodel->getdepartmentlist();
		$divdata = $divisionmodel->getdevisionlist();
		$budata = $busniessunit->getBusinessUnitsList();
		$companydata =$companymode->getOrganisationInfo(); 
        $teamdata=$teammodel->getteamlist();
        $posdata=$positionmodel->getTotalPositionList();
        $titledata=$titlemodel->getJobTitleList();
    
        $empdatas=$modelemp->getEmplist();
        $util = new utlis();
        $form->position_id = $util->populate_select($form->position_id,$posdata,'position_id','name','Select');
		$form->jobtitle_id=$util->populate_select($form->jobtitle_id,$titledata,'jobtitle_id','name','Select');
        $form->team_id = $util->populate_select($form->team_id,$teamdata,'team_id','name','Select');
		$form->company_id=$util->populate_select($form->company_id,$companydata,'id','organisationname','Select');
		$form->sbunit_id = $util->populate_select($form->sbunitid,$budata,'sbunit_id','name','Select');
		$form->division_id = $util->populate_select($form->division_id,$divdata,'division_id','name','Select');
		$form->department_id = $util->populate_select($form->department_id,$depdata,'department_id','name','Select');
		$form->section_id = $util->populate_select($form->section_id,$secdata,'section_id','name','Select');
		$form->project_id = $util->populate_select($form->project_id,$prodata,'project_id','name','Select');
        $form->employee_id = $util->populate_select($form->employee_id,$empdatas,'id','userfullname','Select');
        if ($this->getRequest()->isPost()) {
            if($this->_request->getParam('company_id')=='' && $this->_request->getParam('sbunitid') =='' 
            && $this->_request->getParam('division_id')=='' && $this->_request->getParam('departement_id')==''
            && $this->_request->getParam('section_id')=='' && $this->_request->getParam('project_id')=='' 
            && $this->_request->getParam('team_id')==''){
                $msgarray['generalerror'] = 'choose one from Company,Bussiness Unit,division,Department,Section, Project or Employee';
            }
            else{
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = array(
                    'position_id'=>$this->_getParam('position_id'),
                    'jobtitle_id'=>$this->_getParam('jobtitle_id'),
                    'employee_id'=>$this->_getParam('employee_id'),
                    'datefrom' => sapp_Global::change_date($this->_request->getParam('datefrom'), 'database'),
                    'dateto' => sapp_Global::change_date($this->_request->getParam('dateto'), 'database'),
                    'company_id'=>$this->_getParam('company_id'),
                    'sbunit_id'=>$this->_getParam('sbunitid'),
                    'division_id'=>$this->_getParam('division_id'),
                    'department_id'=>$this->_getParam('department_id'),
                    'section_id'=>$this->_getParam('section_id'),
                    'project_id'=>$this->_getParam('project_id'),
                    'team_id'=>$this->_getParam('team_id'),
                    'description'=>$this->_getParam('description'),
                    'createdby'=>$loginUserid,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );

                $model->SaveorUpdateData($data,'');
                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Employee Position added successfully."));
                $this->_redirect('empposition');
            }
            else{
                $msgarray = [];
			$messages = $form->getMessages();
		
            foreach ($messages as $key1 => $value) {
                foreach ($value as $key2 => $value2) {
                    $msgarray[$key1] = $value2;
                    break;
                }
            }
			
            }
        }
        }
        $this->view->msgarray = $msgarray;
        $this->view->form = $form;
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    } 
    }
    public function editAction()
    {
        try {
            //code...
          
        $form = new Default_Form_empposition();
        $model = new Default_Model_empposition();
        $dmmodel = new Default_Model_Digitalmedia();
        $companymode = new Default_Model_Organisationinfo();
        $busniessunit = new Default_Model_Businessunits();
		$divisionmodel = new Default_Model_division();
		$departmentmodel = new Default_Model_Departments();
		$sectionmodel = new Default_Model_Section();
		$projectmodel = new Default_Model_Project();
        $teammodel = new Default_Model_Team();
        $positionmodel = new Default_Model_Positions();
        $titlemodel = new Default_Model_Jobtitles();
        $emp_id = $this->_getParam('emp_id');
        $pos_id = $this->_getParam('pos_id');

        $modelemp=new Default_Model_Employee();
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity())
        {
            $loginUserid = $auth->getStorage()->read()->id;
        }

        $prodata = $projectmodel->getprojectlist();
        $secdata = $sectionmodel->getsectionlist();
        $depdata = $departmentmodel->getdepartmentlist();
		$divdata = $divisionmodel->getdevisionlist();
		$budata = $busniessunit->getBusinessUnitsList();
		$companydata =$companymode->getOrganisationInfo(); 
        $teamdata=$teammodel->getteamlist();
        $posdata=$positionmodel->getTotalPositionList();
        $titledata=$titlemodel->getJobTitleList();
        $dpdata = $model->getDPers($emp_id,$pos_id);

        $empdatas=$modelemp->getEmplist();
        $util = new utlis();
        $form->position_id = $util->populate_select($form->position_id,$posdata,'position_id','name','Select');
		$form->jobtitle_id=$util->populate_select($form->jobtitle_id,$titledata,'jobtitle_id','name','Select');
        $form->team_id = $util->populate_select($form->team_id,$teamdata,'team_id','name','Select');
		$form->company_id=$util->populate_select($form->company_id,$companydata,'id','organisationname','Select');
		$form->sbunit_id = $util->populate_select($form->sbunitid,$budata,'sbunit_id','name','Select');
		$form->division_id = $util->populate_select($form->division_id,$divdata,'division_id','name','Select');
		$form->department_id = $util->populate_select($form->department_id,$depdata,'department_id','name','Select');
		$form->section_id = $util->populate_select($form->section_id,$secdata,'section_id','name','Select');
		$form->project_id = $util->populate_select($form->project_id,$prodata,'project_id','name','Select');
        $form->employee_id = $util->populate_select($form->employee_id,$empdatas,'id','userfullname','Select');
        $form->populate($dpdata);
        
        if ($this->getRequest()->isPost()) {
        Zend_debug::dump($this->_request->getParam('departement_id'));
            if($this->_request->getParam('company_id')=='' && $this->_request->getParam('sbunitid') =='' 
            && $this->_request->getParam('division_id')=='' && $this->_request->getParam('department_id')==''
            && $this->_request->getParam('section_id')=='' && $this->_request->getParam('project_id')=='' 
            && $this->_request->getParam('team_id')==''){
                $msgarray['generalerror'] = 'choose one from Company,Bussiness Unit,division,Department,Section, Project or Employee';
            }
            else{
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = array(
                    'position_id'=>$this->_getParam('position_id'),
                    'jobtitle_id'=>$this->_getParam('jobtitle_id'),
                    'employee_id'=>$this->_getParam('employee_id'),
                    'datefrom' => sapp_Global::change_date($this->_request->getParam('datefrom'), 'database'),
                    'dateto' => sapp_Global::change_date($this->_request->getParam('dateto'), 'database'),
                    'company_id'=>$this->_getParam('company_id'),
                    'sbunit_id'=>$this->_getParam('sbunitid'),
                    'division_id'=>$this->_getParam('division_id'),
                    'department_id'=>$this->_getParam('department_id'),
                    'section_id'=>$this->_getParam('section_id'),
                    'project_id'=>$this->_getParam('project_id'),
                    'team_id'=>$this->_getParam('team_id'),
                    'description'=>$this->_getParam('description'),
                    'createdby'=>$loginUserid,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );
                $model->SaveorUpdateData($data,'employee_id = '.$emp_id.' and position_id= '.$pos_id);
                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Employee Position updated successfully."));
                $this->_redirect('empposition');
            }
            else{
                $msgarray = [];
			$messages = $form->getMessages();
		
            foreach ($messages as $key1 => $value) {
                foreach ($value as $key2 => $value2) {
                    $msgarray[$key1] = $value2;
                    break;
                }
            }
			
            }
        }
        }
        $this->view->msgarray = $msgarray;
        $this->view->form = $form;
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    } 
    }
    public function viewAction()
    {
        try {
            //code...
        
        $util = new utlis();
        
        $model = new Default_Model_Digitalpresence();
        $lmodel = new Default_Model_Location();
        $id=$this->_getParam('id');
       
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $phonedata=$model->getparticularpresenceData($id);
        //die(Zend_Debug::dump($phonedata));
        $this->view->data = $phonedata;
        $this->view->controllername = 'emppresence';
      
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
        die();
    }
    }
}
