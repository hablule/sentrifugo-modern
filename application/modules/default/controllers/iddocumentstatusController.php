<?php

class Default_iddocumentstatusController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_iddocumentstatus();
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
        $iddocumentstatus_Form = new Default_Form_iddocumentstatus();
        $this->view->form = $iddocumentstatus_Form; 
    }

    public function addpopupAction()
    {
        require 'application/modules/default/library/utilClasses/Util.php';
        try {
            
        $iddocumentstatus_Form = new Default_Form_iddocumentstatus();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
            
            
    
            if ($this->getRequest()->isPost()) {
                $this->save($iddocumentstatus_Form);
            }

            $this->view->form = $iddocumentstatus_Form;
    
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
       }
        $this->view->form = $iddocumentstatus_Form; 
    }
    public function editAction()
    {
        $iddocumentstatus_Form = new Default_Form_iddocumentstatus();
        require 'application/modules/default/library/utilClasses/Util.php';
        try {

        $id = $this->_getParam('id');
        if ($id!=null or $id!='') {
            $iddocumentstatus_Form->code->removeValidator('Db_NoRecordExists');
            $iddocumentstatus_Model = new Default_Model_iddocumentstatus();
            $iddocumentstatus_data = $iddocumentstatus_Model->GetIdDocumentById($id);
            $iddocumentstatus_Form->populate($iddocumentstatus_data[0]);
            $iddocumentstatus_Form->isactive->setValue(utlis::getcheckboxvalue($iddocumentstatus_data[0]['isactive'],'form'));
        }

        if($this->getRequest()->isPost())
        {
            $result = $this->save($iddocumentstatus_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/iddocumentstatus');
            }
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $iddocumentstatus_Form; 
    }

    public function save($iddocumentstatus_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if($iddocumentstatus_Form->isValid($this->_request->getPost())){
			$educationDetailsModel = new Default_Model_iddocumentstatus();
			$id = $this->getRequest()->getParam('id');
			$user_id = $loginUserId;
			$name = $this->_request->getParam('name');
			$code = $this->_request->getParam('code');
			$description = $this->_request->getParam('description');
            $valuetype = $this->_request->getParam('valuetype');
            $isactive = $this->_getParam('isactive');
			$actionflag = '';
			$tableid  = '';

			$data = array(
				                 'name'=>$name,
				                 'code'=>$code,
								 'description'=>$description,
                                 'valuetype'=>$valuetype,
                                 'isactive'=>utlis::getcheckboxvalue($isactive)
			);
			if($id!=''){
				$where = array('iddocumentstatus_id=?'=>$id);
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
				$this->view->successmessage = 'Employee education details updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Employee education details added successfully.';
                
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'iddocumentstatus';
		}else
		{
			$messages = $iddocumentstatus_Form->getMessages();
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
        $iddocumentstatus_Form = new Default_Form_iddocumentstatus();
        $this->view->form = $iddocumentstatus_Form; 
    }
}
