<?php

class Default_militaryservicetypeController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_militaryservicetype();
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
        $militaryservicetype_Form = new Default_Form_militaryservicetype();
        $this->view->form = $militaryservicetype_Form; 
    }
    public function editAction()
    {
        $militaryservicetype_Form = new Default_Form_militaryservicetype();
        require 'application/modules/default/library/utilClasses/Util.php';
        try{
        $id = $this->_getParam('id');
        if ($id!=null or $id!='') {
            $militaryservicetype_Form->code->removeValidator('Db_NoRecordExists');
            $militaryservicetype_Model = new Default_Model_militaryservicetype();
            $militaryservicetype_data = $militaryservicetype_Model->GetMilitaryServiceTypeById($id);
            $militaryservicetype_Form->populate($militaryservicetype_data[0]);
            $militaryservicetype_Form->isactive->setValue(utlis::getcheckboxvalue($militaryservicetype_data[0]['isactive'],'form'));
        }

        if($this->getRequest()->isPost())
        {
            $result = $this->save($militaryservicetype_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/militaryservicetype');
            }
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $militaryservicetype_Form; 
    }

    public function addpopupAction()
    {
        $militaryservicetype_Form = new Default_Form_militaryservicetype();
        require 'application/modules/default/library/utilClasses/Util.php';
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");

        try{
        
        if($this->getRequest()->isPost())
        {
            $result = $this->save($militaryservicetype_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
         
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $militaryservicetype_Form; 
    }

    public function save($militaryrank_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if($militaryrank_Form->isValid($this->_request->getPost())){
			$educationDetailsModel = new Default_Model_militaryservicetype();
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
				$where = ['servicetype_id=?'=>$id];
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
				$this->view->successmessage = 'Military Service Type  updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Military Service Type  added successfully.';
                
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
            $servicetypeData = $educationDetailsModel->fetchAll('isactive = "Y"','name')->toArray();
            $opt ='';
            foreach($servicetypeData as $record){
                $opt .= sapp_Global::selectOptionBuilder($record['servicetype_id'], $record['name']);
            }
            
            $this->view->servicetypeData = $opt;
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'miltaryservicetype';
		}else
		{
			$messages = $militaryrank_Form->getMessages();
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
        $militaryservicetype_Form = new Default_Form_militaryservicetype();
        $this->view->form = $militaryservicetype_Form; 
    }
}
