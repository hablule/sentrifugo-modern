<?php

class Default_healthseveritylevelController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_healthseveritylevel();
            $call = $this->_getParam('call');

            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $refresh = $this->_getParam('refresh');
            $dashboradcall = $this->_getParam('dashboradcall');

            $data = [];

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
                $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'value';
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

    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
        $healthseveritylevel_Form = new Default_Form_healthseveritylevel();
        require 'application/modules/default/library/utilClasses/Util.php';
        try {
        $healthseverity_model = new Default_Model_healthseverity();
        $healthseverity_data = $healthseverity_model->GetHealthSeverity();
        $healthseveritylevel_Form->healthseverity_id->addMultiOptions([''=>'Select severity']); 
        if (!empty($healthseverity_data)) {
            foreach ($healthseverity_data as  $value) {
              
               $healthseveritylevel_Form->healthseverity_id->addMultiOptions([$value['healthseverity_id']=>$value['name']]);
            }
            

        }
        if($this->getRequest()->isPost())
        {
            $result = $this->save($healthseveritylevel_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
        }
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        
        $this->view->form = $healthseveritylevel_Form; 
    }

    public function addAction()
    {
        $healthseveritylevel_Form = new Default_Form_healthseveritylevel();
        $this->view->form = $healthseveritylevel_Form; 
    }
    public function editAction()
    {
        $healthseveritylevel_Form = new Default_Form_healthseveritylevel();
        require 'application/modules/default/library/utilClasses/Util.php';
        try {
        $healthseverity_model = new Default_Model_healthseverity();
        $healthseverity_data = $healthseverity_model->GetHealthSeverity();
        $healthseveritylevel_Form->healthseverity_id->addMultiOptions([''=>'Select severity']); 
        if (!empty($healthseverity_data)) {
            foreach ($healthseverity_data as  $value) {
              
               $healthseveritylevel_Form->healthseverity_id->addMultiOptions([$value['healthseverity_id']=>$value['name']]);
            }
            

        }
        $id = $this->_getParam('id');
        if ($id!=null or $id!='') {
            $healthseveritylevel_Form->value->removeValidator('Db_NoRecordExists');
            $healthseveritylevel_Model = new Default_Model_healthseveritylevel();
            $healthseverity_data = $healthseveritylevel_Model->GetHealthSeverityLevel($id);
            $healthseveritylevel_Form->populate($healthseverity_data[0]);
            $healthseveritylevel_Form->isactive->setValue(utlis::getcheckboxvalue($healthseverity_data[0]['isactive'],'form'));
        }

        if($this->getRequest()->isPost())
        {
            
            $result = $this->save($healthseveritylevel_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/healthseveritylevel');
            }
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        
        $this->view->form = $healthseveritylevel_Form; 
    }

    public function save($healthseveritylevel_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
        
		if($healthseveritylevel_Form->isValid($this->_request->getPost())){
           
			$healthseveritylevel_model = new Default_Model_healthseveritylevel();
			$id = $this->getRequest()->getParam('id');
			$user_id = $loginUserId;
			$severity = $this->_request->getParam('healthseverity_id');
			$value = $this->_request->getParam('value');
			$description = $this->_request->getParam('description');
            $isactive = $this->_getParam('isactive');
			$actionflag = '';
			$tableid  = '';
        
			$data = [
				                 'healthseverity_id'=>$severity,
				                 'value'=>$value,
								 'description'=>$description,
                                 'isactive'=>utlis::getcheckboxvalue($isactive)
			];
			if($id!=''){
				$where = ['severitylevel_id=?'=>$id];
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
           
			$Id = $healthseveritylevel_model->SaveorUpdateData($data, $where);
			if($Id == 'update')
			{
				$tableid = $id;
				$this->view->successmessage = ' Health severity level updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Health severity level added successfully.';
                
			}
			$menuID = EMPLOYEE;
            $currencyData = $healthseveritylevel_model->fetchAll('isactive = "Y"','value')->toArray();
				$opt ='';
				foreach($currencyData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['severitylevel_id'], $record['value']);
				}
                $this->view->healthseveritylevel_data = $opt;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'educationdetails';
		}else
		{
            
			$messages = $healthseveritylevel_Form->getMessages();
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
        $healthseveritylevel_Form = new Default_Form_healthseveritylevel();
        $this->view->form = $healthseveritylevel_Form; 
    }
}
