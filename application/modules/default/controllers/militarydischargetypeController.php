<?php

class Default_militarydischargetypeController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_militarydischargetype();
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
        $militarydischargetype_Form = new Default_Form_militarydischargetype();
        $this->view->form = $militarydischargetype_Form; 
    }
    public function editAction()
    {
        require 'application/modules/default/library/utilClasses/Util.php';
        $militarydischargetype_Form = new Default_Form_militarydischargetype();
        try{
        $id = $this->_getParam('id');
        if ($id!=null or $id!='') {
            $militarydischargetype_Form->code->removeValidator('Db_NoRecordExists');
            $militarydischargetype_Model = new Default_Model_militarydischargetype();
            $militarydischargetype_data = $militarydischargetype_Model->GetMilitaryDischargeTypeById($id);
            $militarydischargetype_Form->populate($militarydischargetype_data[0]);
            $militarydischargetype_Form->isactive->setValue(utlis::getcheckboxvalue($militarydischargetype_data[0]['isactive'],'form'));
        }

        if($this->getRequest()->isPost())
        {
            $result = $this->save($militarydischargetype_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/militarydischargetype');
            }
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $militarydischargetype_Form; 
    }

    public function addpopupAction()
    {
        require 'application/modules/default/library/utilClasses/Util.php';
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
        $militarydischargetype_Form = new Default_Form_militarydischargetype();
        try{
        
        if($this->getRequest()->isPost())
        {
            $result = $this->save($militarydischargetype_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
           
            
        }
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $militarydischargetype_Form; 
    }
    public function save($militarydischargetype_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if($militarydischargetype_Form->isValid($this->_request->getPost())){
			$educationDetailsModel = new Default_Model_militarydischargetype();
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
				$where = array('dischargetype_id=?'=>$id);
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
				$this->view->successmessage = 'Military Discharge Type  updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Military Discharge Type  added successfully.';
                
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
            $dischargetypeData = $educationDetailsModel->fetchAll('isactive = "Y"','name')->toArray();
				$opt =''.sapp_Global::selectOptionBuilder('', 'Select line tyoe');;
				foreach($dischargetypeData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['dischargetype_id'], $record['name']);
				}
                
				$this->view->dischargetypeData = $opt;
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'militarydischargetype';
		}else
		{
			$messages = $militarydischargetype_Form->getMessages();
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
        $militarydischargetype_Form = new Default_Form_militarydischargetype();
        $this->view->form = $militarydischargetype_Form; 
    }
}
