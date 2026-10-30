<?php

class Default_iddocumentController extends Zend_Controller_Action
{
    public function init()
    {
        require_once 'application/modules/default/library/utilClasses/Util.php';

        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_iddocument();
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
        $iddocument_Form = new Default_Form_iddocument();
        $this->view->form = $iddocument_Form; 
    }
    public function editAction()
    {
       
        try {
            
        $iddocument_Form = new Default_Form_iddocument();
  
       // require 'application/modules/default/library/utilClasses/Util.php';
       $auth = Zend_Auth::getInstance();
       if ($auth->hasIdentity()) {
           $loginUserId = $auth->getStorage()->read()->id;
           $loginuserRole = $auth->getStorage()->read()->emprole;
           $loginuserGroup = $auth->getStorage()->read()->group_id;
       }
           $purposeaddpermission = sapp_Global::_checkprivileges(DOCUMENTPURPOSE,$loginuserGroup,$loginuserRole,'add');
           $typeaddpermission = sapp_Global::_checkprivileges(DOCUMENTTYPE,$loginuserGroup,$loginuserRole,'add');
           $categoryaddpermission = sapp_Global::_checkprivileges(DOCUMENTCATEGORY,$loginuserGroup,$loginuserRole,'add');
           $this->view->purposeaddpermission = $purposeaddpermission;
           $this->view->typeaddpermission = $typeaddpermission;
           $this->view->categoryaddpermission = $categoryaddpermission;

        $id = $this->_getParam('id');
        if ($id!=null or $id!='') {
            $iddocument_Form->code->removeValidator('Db_NoRecordExists');
            $iddocumentstatus_Model = new Default_Model_iddocument();
            $iddocumentstatus_data = $iddocumentstatus_Model->GetIdDocumentById($id);
            $iddocument_Form->populate($iddocumentstatus_data[0]);
            $iddocument_Form->isactive->setValue(utlis::getcheckboxvalue($iddocumentstatus_data[0]['isactive'],'form'));
            $iddocument_Form->isnumberd->setValue(utlis::getcheckboxvalue($iddocumentstatus_data[0]['isnumbered'],'form'));
            $iddocument_Form->isphotoid->setValue(utlis::getcheckboxvalue($iddocumentstatus_data[0]['isphotoid'],'form'));
        }

        if($this->getRequest()->isPost())
        {
            $result = $this->save($iddocument_Form);
            if($result!=null)
            {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/iddocument');
            }
            
        }
      
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $iddocument_Form; 
    }

    public function save($iddocument_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if($iddocument_Form->isValid($this->_request->getPost())){
			$educationDetailsModel = new Default_Model_iddocument();
			$id = $this->getRequest()->getParam('id');
			$user_id = $loginUserId;
			$name = $this->_request->getParam('name');
			$code = $this->_request->getParam('code');
			$description = $this->_request->getParam('description');
            $valuetype = $this->_request->getParam('valuetype');
            $isactive = $this->_getParam('isactive');
            $isnumbered = $this->_getParam('isnumbered');
            $isphotoid = $this->_getParam('isphotoid');
            $doccatid = $this->_getParam('iddocumentcategory_id');
            $doctypid = $this->_getParam('iddocumenttype_id');
            $docpurid = $this->_getParam('iddocumentpurpose_id');
            $status = $this->_getParam('iddocumentstatus');
			$actionflag = '';
			$tableid  = '';

			$data = array(
				                 'name'=>$name,
				                 'code'=>$code,
								 'description'=>$description,
                                 'isactive'=>utlis::getcheckboxvalue($isactive),
                                 'isnumbered'=>utlis::getcheckboxvalue($isnumbered),
                                 'isphotoid'=>utlis::getcheckboxvalue($isphotoid),
                                 'iddocumentpurpose_id'=>$docpurid,
                                 'iddocumentcategory_id'=>$doccatid,
                                 'iddocumenttype_id'=>$doctypid,
                                 'iddocumentstatus_id'=>$status,
			);
			if($id!=''){
				$where = array('iddocument_id=?'=>$id);
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
            $currencyData = $educationDetailsModel->fetchAll('isactive = "Y"', 'name')->toArray();
            $opt = '';
            foreach ($currencyData as $record) {
                $opt .= sapp_Global::selectOptionBuilder($record['iddocument_id'], $record['name']);
            }
            $this->view->document = $opt;
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
			$close = 'close';
			$this->view->popup=$close;
			$this->view->controllername = 'iddocument';
		}else
		{
			$messages = $iddocument_Form->getMessages();
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

    public function addpopupAction()
    {
try {
    //code...

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $form = new Default_Form_iddocument();
        $form->setAttrib('action', BASE_URL . 'iddocument/addpopup');
        if ($this->getRequest()->getPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $model = new Default_Model_iddocument();
                $data = array(
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'description'=>$this->_request->getParam('description'),
                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'isnumbered'=>utlis::getcheckboxvalue($this->_request->getParam('isnumbered')),
                    'isphotoid'=>utlis::getcheckboxvalue($this->_request->getParam('isphotoid')),
                    'iddocumentpurpose_id'=>$this->_request->getParam('iddocumentpurpose_id'),
                    'iddocumentcategory_id'=>$this->_request->getParam('iddocumentcategory_id'),
                    'iddocumenttype_id'=>$this->_request->getParam('iddocumenttype_id'),
                    'iddocumentstatus_id'=>$this->_request->getParam('iddocumentstatus_id'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );

                $tableid=$model->SaveorUpdateData($data, '');
                sapp_Global::logManager(351,1,$loginUserId,$tableid);
                $currencyData = $model->fetchAll('isactive = "Y"', 'name')->toArray();
                $opt = '';
                foreach ($currencyData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['iddocument_id'], $record['name']);
                }
                $this->view->document = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->view->controllername = "iddocument";
            }
            $messages = $form->getMessages();
            foreach ($messages as $key1 => $value) {
                foreach ($value as $key2 => $value2) {
                    $msgArray[$key1] = $value2;
                    break;
                }
            }
        }
        $this->view->form = $form;
        $this->view->msgarray = $msgArray;
    } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());    }
    }
    public function viewAction()
    {
    
        $id = $this->_getParam('id');
        $iddocumentstatus_Model = new Default_Model_iddocument();
        $iddocumentstatus_data = $iddocumentstatus_Model->GetIdDocumentById($id);
        $this->view->data = $iddocumentstatus_data[0]; 
    }
}
