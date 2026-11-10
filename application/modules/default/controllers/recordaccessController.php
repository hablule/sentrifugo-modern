<?php

class Default_recordaccessController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }
    public function indexAction()
    {
        try {
        
        $racecodemodel = new Default_Model_recordaccess();	
        $call = $this->_getParam('call');
		if($call == 'ajaxcall')
				$this->_helper->layout->disableLayout();
		
		$view = Zend_Layout::getMvcInstance()->getView();		
		$objname = $this->_getParam('objname');
		$refresh = $this->_getParam('refresh');
		$dashboardcall = $this->_getParam('dashboardcall');
		
		$data = [];
		$searchQuery = '';
		$searchArray = [];
		$tablecontent='';
		
		if($refresh == 'refresh')
		{
		    if($dashboardcall == 'Yes')
				$perPage = DASHBOARD_PERPAGE;
			else	
				$perPage = PERPAGE;
			$sort = 'DESC';$by = 'ra.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
		}
		else 
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'ra.modifieddate';
			if($dashboardcall == 'Yes')
				$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
			else 
			    $perPage = $this->_getParam('per_page',PERPAGE);
			$pageNo = $this->_getParam('page', 1);
			/** search from grid - START **/
			$searchData = $this->_getParam('searchData');	
			$searchData = rtrim($searchData,',');
			/** search from grid - END **/
		}
		$dataTmp = $racecodemodel->getGrid($sort, $by, $perPage, $pageNo, $searchData,$call,$dashboardcall);     				
		
		array_push($data,$dataTmp);
		$this->view->dataArray = $data;
		$this->view->call = $call ;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function addAction()
    {
        $recordaccess_Form = new Default_Form_recordaccess();
        $this->view->form = $recordaccess_Form; 
    }
    public function editAction()
    {
        
        $recordaccess_Form = new Default_Form_recordaccess();
        $recordaccess_model = new Default_Model_recordaccess();
        $user = new Default_Model_Employees();
        $userdata=$user->getemp();
        $util = new utlis();
        $recordaccess_Form->user_id = $util->populate_select($recordaccess_Form->user_id,$userdata,'id','userfullname','select');
        $auth = Zend_Auth::getInstance();
        $id = $this->_getParam('id',null);
        if ($id!=null) {
            $recorddata = $recordaccess_model->getrecordaccessbyid($id);
            $recordaccess_Form->populate($recorddata);
            $recordaccess_Form->isactive->setValue(utlis::getcheckboxvalue($recorddata['isactive'],'form'));
            $recordaccess_Form->readwrite->setValue(utlis::getcheckboxvalue($recorddata['readwrite']),'form');
        }
        if($auth->hasIdentity())
        {
            $loginuserdId = $auth->getStorage()->read()->id;
        }
        if($this->getRequest()->isPost())
        { 
            if ($recordaccess_Form->isValid($this->_request->getPost())) {
                $data = [
                    'user_id'=>$this->_getParam('user_id'),
                    'description'=>$this->_getParam('description'),
                    'accesslevel'=>$this->_getParam('accesslevel'),
                    'readwrite'=>utlis::getcheckboxvalue($this->_getParam('readwrite')),
                    'isactive'=>utlis::getcheckboxvalue($this->_getParam('isactive'))
                ];
                
                if($id!=null)
                {
                    $data['modifiedby']=$loginuserdId;
                    $data['modifieddate']=gmdate('Y-m-d H:i:s');
                   
                    $Id=$recordaccess_model->SaveorUpdateData($data,'emprecordaccess_id='.$id);
                }
                else{
                    $data['createdby'] = $loginuserdId;
                    $data['createddate'] = gmdate('Y-m-d H:i:s');
                
                  
                   try {
                   // die();
                    $Id = $recordaccess_model->SaveorUpdateData($data,'');
                        //code...
                   } catch (\Throwable $th) {
                       
                       $code = $th->getCode();
                     
                    if($code=23000){
                        $msgArray['accesslevel']='Access level already set ';
                       
                    }
                    else{
                        $this->_redirect('recordaccess');
                    }
                   
                }
                }
               
                if($Id=='update')
                {
                    $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Record access updated successfully."]);
                   
                }
                else{
                    $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Record access added successfully."]);
                   
                }
                $this->_redirect('recordaccess');
               
            } 
            else{
                $messages = $recordaccess_Form->getMessages();
                
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
            }
        }
      
        $this->view->msgarray = $msgArray ;
        $this->view->form = $recordaccess_Form; 
            
      
    }
    public function viewAction()
    {
        try {
         
        $recordaccess_model = new Default_Model_recordaccess();
        $user = new Default_Model_Users();
        
        $id = $this->_getParam('id',null);
        $recorddata = $recordaccess_model->getrecordaccessbyid($id);
        $userfullname=$user->getUserDetailsByID($recorddata['user_id'],'all')[0]['userfullname'];
        $recordaccess_Form = new Default_Form_recordaccess();
        $recorddata['userfullname'] = $userfullname;
        $this->view->data = $recorddata;
        $this->view->form = $recordaccess_Form; 
           //code...
        } catch (\Throwable $th) {
           Zend_Debug::dump($th->getMessage());
        }
    }
}
