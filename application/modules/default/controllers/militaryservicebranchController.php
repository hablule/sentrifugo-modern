<?php

class Default_militaryservicebranchController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_militaryservicebranch();
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
        $militaryservicebranch_Form = new Default_Form_militaryservicebranch();
        $this->view->form = $militaryservicebranch_Form; 
    }
    public function editAction()
    {
       /*  $militaryservicebranch_Form = new Default_Form_militaryservicebranch();
        require 'application/modules/default/library/utilClasses/Util.php'; */
        try {
            $militaryservicebranch_Form = new Default_Form_militaryservicebranch();
            require 'application/modules/default/library/utilClasses/Util.php';
        $id = $this->_getParam('id');
        if ($id!=null or $id!='') {
            $militaryservicebranch_Form->code->removeValidator('Db_NoRecordExists');
            $militaryservicebranch_Model = new Default_Model_militaryservicebranch();
            $militaryservicebranch_data = $militaryservicebranch_Model->GetMilitaryServiceBranchById($id);
            $militaryservicebranch_Form->populate($militaryservicebranch_data[0]);
            $militaryservicebranch_Form->isactive->setValue(utlis::getcheckboxvalue($militaryservicebranch_data[0]['isactive'],'form'));
        }

        if($this->getRequest()->isPost())
        {
            $result = $this->save($militaryservicebranch_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/militaryservicebranch');
            }
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $militaryservicebranch_Form; 
    }

    public function addpopupAction()
    {
        try {
            $militaryservicebranch_Form = new Default_Form_militaryservicebranch();
            require 'application/modules/default/library/utilClasses/Util.php';
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");

        if($this->getRequest()->isPost())
        {
            $result = $this->save($militaryservicebranch_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
           
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $militaryservicebranch_Form; 
    }
    public function save($militaryrank_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if($militaryrank_Form->isValid($this->_request->getPost())){
			$educationDetailsModel = new Default_Model_militaryservicebranch();
			$id = $this->getRequest()->getParam('id');
			$user_id = $loginUserId;
			$name = $this->_request->getParam('name');
			$code = $this->_request->getParam('code');
			$description = $this->_request->getParam('description');
            $isactive = $this->_getParam('isactive');
            $natocode = $this->_getParam('natocode');
			$actionflag = '';
			$tableid  = '';

			$data = array(
				                 'name'=>$name,
				                 'code'=>$code,
								 'description'=>$description,
                                 'isactive'=>utlis::getcheckboxvalue($isactive)
			);
			if($id!=''){
				$where = array('servicebranch_id=?'=>$id);
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
                $opt .= sapp_Global::selectOptionBuilder($record['servicebranch_id'], $record['name']);
            }
            
            $this->view->servicebranchData = $opt;
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'militaryservicebranch';
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
        $militaryservicebranch_Form = new Default_Form_militaryservicebranch();
        $this->view->form = $militaryservicebranch_Form; 
    }
}
