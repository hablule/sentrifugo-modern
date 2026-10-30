<?php

class Default_healthissueController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require 'application/modules/default/library/utilClasses/Util.php';
    }
    public function indexAction()
    {
        try {
            $model = new Default_Model_healthissue();
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
                } else {
                    $perPage = PERPAGE;
                }
                $sort = 'DESC';
                $by = 'name';
                $pageNo = 1;
                $searchData = '';
            } else {
                $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
                $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'name';
                if ($dashboradcall == 'Yes')
                    $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
                else
                    $perPage = $this->_getParam('per_page', PERPAGE);

                $pageNo = $this->_getParam('page', 1);
                $searchData = $this->_getParam('searchData');
                $searchData = rtrim($searchData, ',');
            }
            $dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboradcall);

            array_push($data, $dataTmp);
            $this->view->dataArray = $data;
            $this->view->call = $call;
            $this->view->messages = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function addAction()
    {
        $healthissue_Form = new Default_Form_healthissue();
        $this->view->form = $healthissue_Form; 
    }
    public function editAction()
    {
        $healthissue_Form = new Default_Form_healthissue();
        $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
                $loginuserRole = $auth->getStorage()->read()->emprole;
                $loginuserGroup = $auth->getStorage()->read()->group_id;
            }
                $serverityaddpermission = sapp_Global::_checkprivileges(HEALTHSERVERITY,$loginuserGroup,$loginuserRole,'add');
                $typeaddpermission = sapp_Global::_checkprivileges(HEALTHISSUETYPE,$loginuserGroup,$loginuserRole,'add');
                $categoryaddpermission = sapp_Global::_checkprivileges(HEALTHISSUECATEGORY,$loginuserGroup,$loginuserRole,'add');
                $this->view->serverityaddpermission = $serverityaddpermission;
                $this->view->typeaddpermission = $typeaddpermission;
                $this->view->categoryaddpermission = $categoryaddpermission;
        $issuetype_model = new Default_Model_healthissuetype();
        $healthseverity_model = new Default_Model_healthseverity();
        $issuecategory_model = new Default_Model_healthissuecategory();
        $issuecategory_data = $issuecategory_model->GetIssuecategory();
        $healthseverity_data = $healthseverity_model->GetHealthSeverity();
        $issuetype_data = $issuetype_model->GetHealthIssueType();
     
        $healthissue_Form->issuetype_id->addMultiOptions(array(''=>'Select issue type'));
        if(!empty($issuetype_data))
        {
            foreach ($issuetype_data  as $value) {
                $healthissue_Form->issuetype_id->addMultiOptions(array($value['issuetype_id']=>$value['name']));
            }
        }
        $healthissue_Form->healthseverity_id->addMultiOptions(array(''=>'Select health severity'));
        if (!empty($healthseverity_data)) {
           foreach ($healthseverity_data as $value) {
            $healthissue_Form->healthseverity_id->addMultiOptions(array($value['healthseverity_id']=>$value['name']));
           }
        }
        $healthissue_Form->issuecategory_id->addMultiOptions(array(''=>'Select issue category'));
        if (!empty($issuecategory_data)) {
            foreach ($issuecategory_data as  $value) {
                $healthissue_Form->issuecategory_id->addMultiOptions(array($value['issuecategory_id']=>$value['name']));
            }
        }
        try {

        $id = $this->_getParam('id');
        if ($id!=null or $id!='') {
            $healthissue_Form->code->removeValidator('Db_NoRecordExists');
            $healthissue_Model = new Default_Model_healthissue();
            $healthissue_data = $healthissue_Model->GetHealthIssueById($id);
            $healthissue_Form->populate($healthissue_data[0]);
            $healthissue_Form->isactive->setValue(utlis::getcheckboxvalue($healthissue_data[0]['isactive'],'form'));
        }

        if($this->getRequest()->isPost())
        {
            $result = $this->save($healthissue_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/healthissue');
            }
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $healthissue_Form; 
    }


    public function addpopupAction()
    {
        try {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
        $healthissue_Form = new Default_Form_healthissue();
        $issuetype_model = new Default_Model_healthissuetype();
        $healthseverity_model = new Default_Model_healthseverity();
        $issuecategory_model = new Default_Model_healthissuecategory();
        $issuecategory_data = $issuecategory_model->GetIssuecategory();
        $healthseverity_data = $healthseverity_model->GetHealthSeverity();
        $issuetype_data = $issuetype_model->GetHealthIssueType();
     
        $healthissue_Form->issuetype_id->addMultiOptions(array(''=>'Select issue type'));
        if(!empty($issuetype_data))
        {
            foreach ($issuetype_data  as $value) {
                $healthissue_Form->issuetype_id->addMultiOptions(array($value['issuetype_id']=>$value['name']));
            }
        }
        $healthissue_Form->healthseverity_id->addMultiOptions(array(''=>'Select health severity'));
        if (!empty($healthseverity_data)) {
           foreach ($healthseverity_data as $value) {
            $healthissue_Form->healthseverity_id->addMultiOptions(array($value['healthseverity_id']=>$value['name']));
           }
        }
        $healthissue_Form->issuecategory_id->addMultiOptions(array(''=>'Select issue category'));
        if (!empty($issuecategory_data)) {
            foreach ($issuecategory_data as  $value) {
                $healthissue_Form->issuecategory_id->addMultiOptions(array($value['issuecategory_id']=>$value['name']));
            }
        }
        if($this->getRequest()->isPost())
        {
            $this->save($healthissue_Form);
        }
           //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $healthissue_Form;
    }

    public function save($healthissue_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if($healthissue_Form->isValid($this->_request->getPost())){
			$healthissuecategory_Model = new Default_Model_healthissue();
			$id = $this->getRequest()->getParam('id');
			$user_id = $loginUserId;
			$name = $this->_request->getParam('name');
			$code = $this->_request->getParam('code');
            $severity = $this->_request->getParam('healthseverity_id');
            $issue = $this->_request->getParam('issuetype_id');
			$description = $this->_request->getParam('description');
            $issuecategory = $this->_request->getParam('issuecategory_id');
            $isactive = $this->_getParam('isactive');
			$actionflag = '';
			$tableid  = '';

			$data = array(
				                 'name'=>$name,
				                 'code'=>$code,
								 'description'=>$description,
                                 'healthseverity_id'=>$severity,
                                 'issuetype_id'=>$issue,
                                 'issuecategory_id'=> $issuecategory,
                                 'isactive'=>utlis::getcheckboxvalue($isactive)
			);
			if($id!=''){
				$where = array('healthissue_id=?'=>$id);
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
			$Id = $healthissuecategory_Model->SaveorUpdateData($data, $where);
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
            $currentData = $healthissuecategory_Model->fetchAll('isactive="Y"','name')->toArray();
            $opt ='';
				foreach($currentData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['healthissue_id'], $record['name']);
				}
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
			$close = 'close';
            $this->view->healthissuedata = $opt ;
			$this->view->popup=$close;
			$this->view->controllername = 'educationdetails';
		}else
		{
			$messages = $healthissue_Form->getMessages();
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
    public function viewAction()
    {
        $healthissue_Form = new Default_Form_healthissue();
        $this->view->form = $healthissue_Form; 
    }
}
