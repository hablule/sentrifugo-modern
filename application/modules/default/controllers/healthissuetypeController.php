<?php

class Default_healthissuetypeController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_healthissuetype();
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
        $healthissuetype_Form = new Default_Form_healthissuetype();
        $this->view->form = $healthissuetype_Form; 
    }
    public function addpopupAction()
    {
        $healthissuetype_Form = new Default_Form_healthissuetype();
        $healthissuetype_Model = new Default_Model_healthissuetype();
        require 'application/modules/default/library/utilClasses/Util.php';
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
        try {

        if($this->getRequest()->isPost())
        {
            $result = $this->save($healthissuetype_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
                $typeData = $healthissuetype_Model->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($typeData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['issuetype_id'], $record['name']);
				}
            $this->view->healthissuetypedata = $opt;
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'healthissuetype';
            }
           
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $healthissuetype_Form; 
    }
    public function editAction()
    {
        $healthissuetype_Form = new Default_Form_healthissuetype();
        require 'application/modules/default/library/utilClasses/Util.php';
        try {

        $id = $this->_getParam('id');
        if ($id!=null or $id!='') {
            $healthissuetype_Form->code->removeValidator('Db_NoRecordExists');
            $healthissuetype_Model = new Default_Model_healthissuetype();
            $healthissuetype_data = $healthissuetype_Model->GetHealthIssueById($id);
            $healthissuetype_Form->populate($healthissuetype_data[0]);
            $healthissuetype_Form->isactive->setValue(utlis::getcheckboxvalue($healthissuetype_data[0]['isactive'],'form'));
        }

        if($this->getRequest()->isPost())
        {
            $result = $this->save($healthissuetype_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/healthissuetype');
            }
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $healthissuetype_Form; 
    }

    public function save($healthissuetype_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if($healthissuetype_Form->isValid($this->_request->getPost())){
			$healthissuetype_Model = new Default_Model_healthissuetype();
			$id = $this->getRequest()->getParam('id');
			$user_id = $loginUserId;
			$name = $this->_request->getParam('name');
			$code = $this->_request->getParam('code');
			$description = $this->_request->getParam('description');
            $isactive = $this->_getParam('isactive');
			$actionflag = '';
			$tableid  = '';

			$data = array(
				                 'name'=>$name,
				                 'code'=>$code,
								 'description'=>$description,
                                 'isactive'=>utlis::getcheckboxvalue($isactive)
			);
			if($id!=''){
				$where = array('issuetype_id=?'=>$id);
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
			$Id = $healthissuetype_Model->SaveorUpdateData($data, $where);
			if($Id == 'update')
			{
				$tableid = $id;
				$this->view->successmessage = 'Health issue type updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Health issue type added successfully.';
                
			}
			$menuID = EMPLOYEE;
            $currencyData = $healthissuetype_Model->fetchAll('isactive = 1','name')->toArray();
				$opt ='';
				foreach($currencyData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['healthissue_id'], $record['name']);
				}
                $this->view->healthissuedata = $opt;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'emphealthissue';
		}else
		{
			$messages = $healthissuetype_Form->getMessages();
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
        $healthissuetype_Form = new Default_Form_healthissuetype();
        $this->view->form = $healthissuetype_Form; 
    }
}
