<?php

class Default_militaryrankController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_militaryrank();
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
        $militaryrank_Form = new Default_Form_militaryrank();
        $this->view->form = $militaryrank_Form; 
    }
    public function editAction()
    {
        $militaryrank_Form = new Default_Form_militaryrank();
        require 'application/modules/default/library/utilClasses/Util.php';
        try {

        $id = $this->_getParam('id');
        if ($id!=null or $id!='') {
            $militaryrank_Form->code->removeValidator('Db_NoRecordExists');
            $militaryrank_Model = new Default_Model_militaryrank();
            $militaryrank_data = $militaryrank_Model->GetMilitaryServiceById($id);
            $militaryrank_Form->populate($militaryrank_data[0]);
            $militaryrank_Form->isactive->setValue(utlis::getcheckboxvalue($militaryrank_data[0]['isactive'],'form'));
        }

        if($this->getRequest()->isPost())
        {
            $result = $this->save($militaryrank_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/militaryrank');
            }
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }

        $this->view->form = $militaryrank_Form; 
    }
    public function addpopupAction()
    {
        $militaryrank_Form = new Default_Form_militaryrank();
        require 'application/modules/default/library/utilClasses/Util.php';
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
        try {

        if($this->getRequest()->isPost())
        {
            $result = $this->save($militaryrank_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
           
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }

        $this->view->form = $militaryrank_Form; 
        
    }
    public function save($militaryrank_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if($militaryrank_Form->isValid($this->_request->getPost())){
			$educationDetailsModel = new Default_Model_militaryrank();
			$id = $this->getRequest()->getParam('id');
			$user_id = $loginUserId;
			$name = $this->_request->getParam('name');
			$code = $this->_request->getParam('code');
			$description = $this->_request->getParam('description');
            $isactive = $this->_getParam('isactive');
            $natocode = $this->_getParam('natocode');
			$actionflag = '';
			$tableid  = '';

			$data = [
				                 'name'=>$name,
				                 'code'=>$code,
								 'description'=>$description,
                                 'natocode'=>$natocode,
                                 'isactive'=>utlis::getcheckboxvalue($isactive)
			];
			if($id!=''){
				$where = ['militaryrank_id=?'=>$id];
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
				$this->view->successmessage = 'Military Rank  updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Military Rank  added successfully.';
                
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
            $rankData = $educationDetailsModel->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($rankData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['militaryrank_id'], $record['name']);
				}
                
				$this->view->rankData = $opt;
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'militaryrank';
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
        $militaryrank_Form = new Default_Form_militaryrank();
        $this->view->form = $militaryrank_Form; 
    }
}
