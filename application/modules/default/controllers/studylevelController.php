<?php

class Default_studylevelController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_studylevel();
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
        $studylevel_Form = new Default_Form_studylevel();
        $this->view->form = $studylevel_Form; 
    }
    public function editAction()
    {
        require 'application/modules/default/library/utilClasses/Util.php';
        
        $studylevel_Form = new Default_Form_studylevel();
        try {
        
            
            $id = $this->_getParam('id');
            if ($id!=null or $id!='') {
                $studylevel_Form->code->removeValidator('Db_NoRecordExists');
                $studylevel_model = new Default_Model_studylevel();
                $studylevel_data = $studylevel_model->GetStudylevelById($id);
                $studylevel_Form->populate($studylevel_data);
                $studylevel_Form->isactive->setValue(utlis::getcheckboxvalue($studylevel_data['isactive'],'form'));
            }
    
            if($this->getRequest()->isPost())
            {
                $result = $this->save($studylevel_Form);
                if($result!=null)
                {
                    $this->view->msgarray = $result;
                }
                else{
                    $this->_redirect('/studylevel');
                }
                
            }
            $this->view->form = $studylevel_Form; 
              
            } catch (\Throwable $th) {
                Zend_Debug::dump($th->getMessage());
            } 
    }
    public function save($institution_Form)
    {
       
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if($institution_Form->isValid($this->_request->getPost())){
			$educationDetailsModel = new Default_Model_studylevel();
			$id = $this->getRequest()->getParam('id');
			$user_id = $loginUserId;
			$name = $this->_request->getParam('name');
			$code = $this->_request->getParam('code');
			$description = $this->_request->getParam('description');
            $isactive = $this->_getParam('isactive');
			$actionflag = '';
			$tableid  = '';

			$data = [
				                 'name'=>$name,
				                 'code'=>$code,
								 'description'=>$description,
                                 'isactive'=>utlis::getcheckboxvalue($isactive)
			];
			if($id!=''){
				$where = ['studylevel_id=?'=>$id];
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
			$Id = $educationDetailsModel->SaveorUpdateData($data, $where);
			if($Id == 'update')
			{
				$tableid = $id;
				$this->view->successmessage = 'Study Field details updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Study Field details added successfully.';
                
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
            $linetypeData = $educationDetailsModel->fetchAll('isactive = "Y"','name')->toArray();
				$opt =''.sapp_Global::selectOptionBuilder('', 'Select study level');
				foreach($linetypeData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['studylevel_id'], $record['name']);
				}
			$close = 'close';
			$this->view->popup=$close;
            $this->view->studylevelData = $opt; 
            $this->view->eventact = 'added';
			$this->view->controllername = 'educationdetails';
		}else
		{
			$messages = $institution_Form->getMessages();
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
        $studylevel_model = new Default_Model_studylevel();
        $id = $this->_getParam('id');
        $studylevel_data = $studylevel_model->GetStudylevelById($id);
        $this->view->data = $studylevel_data;
    }

    public function addpopupAction()
    {
        require 'application/modules/default/library/utilClasses/Util.php';
        
        $studylevel_Form = new Default_Form_studylevel();
        try {
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
            if($this->getRequest()->isPost())
            {
                $result = $this->save($studylevel_Form);
                
                if($result!=null)
                {
                    $this->view->msgarray = $result;
                }
                
                
            }
            
            $this->view->form = $studylevel_Form;
            
            $this->view->controllername = "studylevel";
            } catch (\Throwable $th) {
                Zend_Debug::dump($th->getMessage());
            } 
    }
}
