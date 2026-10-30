<?php

class Default_relationshipController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        $reject_model = new Default_Model_relationship();
        $call = $this->_getParam('call');
        if($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();
		$dashboardcall = $this->_getParam('dashboardcall');
		$auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;			
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		} 
		
        $refresh = $this->_getParam('refresh');
        $data = array();
        $searchQuery = '';
        $searchArray = array();
        $tablecontent='';

        if($refresh == 'refresh')
        {
			if($dashboardcall == 'Yes')
				$perPage = DASHBOARD_PERPAGE;
			else	
				$perPage = PERPAGE;
            $sort = 'DESC';$by = 'modifiedby';$pageNo = 1;$searchData = '';$searchQuery = '';
            $searchArray = array();
        }
        else 
        {
            $sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
            $by = ($this->_getParam('by')!='')? $this->_getParam('by'):'modifiedby';
            if($dashboardcall == 'Yes')
				$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
			else $perPage = $this->_getParam('per_page',PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            /** search from grid - START **/
            $searchData = $this->_getParam('searchData');	
           
            /** search from grid - END **/
        }

        $dataTmp = $reject_model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
        array_push($data,$dataTmp);
        $this->view->dataArray = $dataTmp;
        $this->view->call = $call ;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();	
    }
    public function addAction()
    {
        $relationship_Form = new Default_Form_relationship();
        $this->view->form = $relationship_Form; 
    }

    public function addpopupAction()
    {
        try {
            
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

            require('application/modules/default/library/utilClasses/Util.php');
            $relationship_Form = new Default_Form_relationship();
            if($this->getRequest()->isPost()){
                $result =$this->save($relationship_Form);
                if ($result!='') {
                    $this->view->msgarray = $result;
                }
        }
            $this->view->form = $relationship_Form; 
              //code...
            } catch (\Throwable $th) {
               Zend_Debug::dump($th->getMessage());
            }
    }
    public function editAction()
    {
        try {
          
        require('application/modules/default/library/utilClasses/Util.php');
        $relationship_Form = new Default_Form_relationship();
        $relation_model = new Default_Model_relationship();
        $id = $this->_getParam('id');
        if($id != '')
        {
            $relation_data = $relation_model->GetRelationType($id);
            $relationship_Form->populate($relation_data[0]);
            $relationship_Form->isactive->setValue(utlis::getcheckboxvalue($relation_data[0]['isactive'],'form'));
        }
        if($this->getRequest()->isPost()){
            $result =$this->save($relationship_Form);
            if ($result!='') {
                $this->view->msgarray = $result;
            }
            else{
                $this->_redirect('/relationship');
            }
           
    }
        $this->view->form = $relationship_Form; 
          //code...
        } catch (\Throwable $th) {
           Zend_Debug::dump($th->getMessage());
        }
    }

    public function save($relationship_Form)
    {
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}


       

		if($relationship_Form->isValid($this->_request->getPost())){
           
			$relationship_Model = new Default_Model_relationship();
			$id = $this->getRequest()->getParam('id');
            $empid = $this->getRequest()->getParam('unitId');
			$user_id = $loginUserId;
			$healthissue_id = $this->_request->getParam('healthissue_id');
			$description = $this->_request->getParam('description');
            $code = $this->_request->getParam('code');
            $name = $this->_request->getParam('name');
            $relation_type = $this->_request->getParam('relationtype');
            $isactive = $this->_getParam('isactive');
			$actionflag = '';
			$tableid  = '';

			$data = array(
				                 'name'=>$name,
				                 'code'=>$code,
								 'description'=>$description,
                                 'isactive'=>utlis::getcheckboxvalue($isactive),
                                 'relationtype'=>$relation_type
			);
			if($id!=''){
				$where = array('relationship_id=?'=>$id);
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
			$Id = $relationship_Model->SaveorUpdateData($data, $where);
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
			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
            $relationshipData = $relationship_Model->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($relationshipData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['relationship_id'], $record['name']);
				}
			$close = 'close';
            $this->view->relationshipData = $opt;
			$this->view->popup=$close;
            $this->view->eventact = 'added';
			$this->view->controllername = 'relationship';
		}else
		{
			$messages = $relationship_Form->getMessages();
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
        $relationship_Form = new Default_Form_relationship();
        $this->view->form = $relationship_Form; 
    }
}
