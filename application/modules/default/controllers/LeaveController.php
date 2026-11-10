<?php
require_once 'UserdefinedclassController.php';

class Default_LeaveController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';
        
    }
    public function indexAction()
    {
		
        $model = new Default_Model_Leave();
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
			$sort = 'DESC';$by = 'leave_id';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
		}
		else
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'ml.leave_id';
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

		$dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData,$call,$dashboardcall);
		
		array_push($data,$dataTmp);
		$this->view->dataArray = $data;
		$this->view->call = $call ;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();

    }
    public function addAction()
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
     
		$msgarray = [];
		$controllername = 'leave';
		$util = new utlis();
		$form = new Default_Form_Leave();
		$model = new Default_Model_Leave();
       $form->ispreset->setValue(0);
	   $form->approval->setValue(1);

        $method_form = new Default_Form_userdefinedclass();
		$filter = ['argNo'=>['min'=>0,'max'=>2],'returnType'=>'String'];
        $classes = Default_UserdefinedclassController::getAllClasses($filter);
		$this->view->method_form = $method_form;
        $this->view->classes = $classes;
      
        $method_form->classfun->addMultiOption( 'empty', "Select...");
        foreach($classes as $class){
            foreach($class['methods'] as $method){
                $method_form->classfun->addMultiOption( $method["name"], $method["name"]);
            }
        }
		$form->setAction(BASE_URL.'leave/add');
        $modelb = new Default_Model_Leavecategory();
        $add = $modelb->getleavecategory();
        $form->leavecategory->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['leavecategory'] = 'Leave Category is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->leavecategory->addMultiOptions([$adds['leavecategory_id'] => $adds['name']]);
            }
        }
		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){
				
                $name = $this->_request->getParam('name');
                $allowday = $this->_request->getParam('allowday');
                $leavecategory = $this->_request->getParam('leavecategory');
                $description = $this->_request->getParam('description');
                $paytype = $this->_request->getParam('paytype');
                $duarationtype = $this->_request->getParam('duarationtype');
                $baseperiod = $this->_request->getParam('baseperiod');
                $approval = $util->getcheckboxvalue($this->_request->getParam('approval'));
                $ispreset = $util->getcheckboxvalue($this->_request->getParam('ispreset'));
				$classpath=$this->getRequest()->getParam('classPath');
				$method=$this->getRequest()->getParam('classfun');
				$parameter=trim($this->getRequest()->getParam('parameters'),',');
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = [	
                'name' => $name,
				'leavecategory_id'=>$leavecategory,
				'description' => $description,
				'durationtype' => $duarationtype,
				'paytype' => $paytype,   
                'baseperiod' => $baseperiod, 
                'alloteddays' =>($allowday!=''?$allowday:''),
                'approvedrequired' => $approval,       
                'ispreset' => $ispreset,       
				'classPath'=>($classpath!='empty'?$classpath:''),
				'method' =>($method!='empty'?$method:''),
				'parameters' =>($parameter!=''?$parameter:''),
				];

					$data['createdby'] = $loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");
					$data['isactive'] = "Y";
					$where = '';
					$actionflag = 1;
					$menuID =  LEAVE;
				$Id = $model->SaveorUpdateData($data, $where);
				$tableid = $Id;
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Leave   added successfully."]);
					$this->view->messages = $this->_helper->flashMessenger->getMessages();

				
				$this->_redirect('leave');

			}else
			{
				$messages = $form->getMessages();
				foreach ($messages as $key => $val)
				{
					foreach($val as $key2 => $val2)
					{
						$msgarray[$key] = $val2;
                                                break;
					}
				}
				$this->view->msgarray = $msgarray;
			}
		}
		$this->view->controllername = $controllername;
		$this->view->form = $form;
		$this->view->ermsg = '';

	}
	public function editAction()
	{		
		$util=new utlis();	
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = (int)$this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();
		$method_form = new Default_Form_userdefinedclass();
		$filter = ['argNo'=>['min'=>0,'max'=>2],'returnType'=>'String'];
        $classes = Default_UserdefinedclassController::getAllClasses($filter);
		$this->view->method_form = $method_form;
        $this->view->classes = $classes;
      
        $method_form->classfun->addMultiOption( 'empty', "Select...");
        foreach($classes as $class){
            foreach($class['methods'] as $method){
                $method_form->classfun->addMultiOption( $method["name"], $method["name"]);
            }
        }
		$form = new Default_Form_Leave();
		$form->name->removeValidator('Db_NoRecordExists');
		$model = new Default_Model_Leave();
		$form->setAttrib('action', BASE_URL +'leave/edit/id/'.$id.'');
		$modelb = new Default_Model_Leavecategory();
		$add = $modelb->getleavecategory();
        $form->leavecategory->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['leavecategory'] = 'Leave Category is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->leavecategory->addMultiOptions([$adds['leavecategory_id'] => $adds['name']]);
            }
        }
		try
		{
		
			if($id)
			{				
				if(is_numeric($id) && $id>0)
				{

					$data = $model->getsingleleaveData($id);
					//die(json_encode($data));
					$form->leavecategory->setValue($data[0]['leavecategoryid']);
		

				
					if(!empty($data) && $data != "norows")
					{
						$form->populate($data[0]);
						
		
						$method_form->populate($data[0]);
						$params=$data[0]['parameters'];
						$form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactivecheck'],'form'));
						$form->ispreset->setValue(utlis::getcheckboxvalue($data[0]['ispresetcheck'],'form'));
						$form->approval->setValue(utlis::getcheckboxvalue($data[0]['isapprovecheck'],'form'));

						
						$form->submit->setLabel('Update');
						$this->view->data = $data;
						$this->view->params = $params;

						$this->view->ermsg = '';
					}
					else
					{
						$this->view->ermsg = 'nodata';
					}
				}else
				{

					$this->view->ermsg = 'nodata';
				}
			}else
			{
				$this->view->ermsg = '';
			}
		}
		catch(Exception $e)
		{
			$this->view->ermsg = 'nodata';
		}

		$this->view->form = $form;
		if($this->getRequest()->getPost()){

			if($form->isValid($this->_request->getPost())){

				$name = $this->_request->getParam('name');
                $allowday = $this->_request->getParam('allowday');
                $leavecategory = $this->_request->getParam('leavecategory');
                $description = $this->_request->getParam('description');
                $paytype = $this->_request->getParam('paytype');
                $duarationtype = $this->_request->getParam('duarationtype');
                $baseperiod = $this->_request->getParam('baseperiod');
                $approval = $util->getcheckboxvalue($this->_request->getParam('approval'));
                $ispreset = $util->getcheckboxvalue($this->_request->getParam('ispreset'));
				$classpath=$this->getRequest()->getParam('classPath');
				$method=$this->getRequest()->getParam('classfun');
				$parameter=trim($this->getRequest()->getParam('parameters'),',');
				$isactive = (utlis::getcheckboxvalue($this->_request->getParam('isactive'),'database'));

				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = [	
                'name' => $name,
				'leavecategory_id'=>$leavecategory,
				'description' => $description,
				'durationtype' => $duarationtype,
				'paytype' => $paytype,   
                'baseperiod' => $baseperiod, 
                'alloteddays' =>($allowday!=''?$allowday:''),
                'approvedrequired' => $approval,       
                'ispreset' => $ispreset,       
				'classPath'=>($classpath!='empty'?$classpath:''),
				'method' =>($method!='empty'?$method:''),
				'parameters' =>($parameter!=''?$parameter:''),
				'modifiedby'=>$loginUserId,
				'modifieddate'=>gmdate("Y-m-d H:i:s"),
				'isactive'=>$isactive,

];
				
				
					$where = 'leave_id='.$id;
				//	die(json_encode($data));
			$Id = $model->SaveorUpdateData($data, $where);
				if($Id == 'update')
				{
					$tableid = $id;
					$actionflag = 2;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Leave  updated successfully."]);
				}
				else
				{
					$tableid = $Id;
					$actionflag = 1;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Leave   added successfully."]);
				}
				$menuID = LEAVE;
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
				$this->_redirect('leave');
			}else
			{
				$messages = $form->getMessages();
				foreach ($messages as $key => $val)
				{
					foreach($val as $key2 => $val2)
					{
						$msgarray[$key] = $val2;
                                                break;
					}
				}
				$this->view->msgarray = $msgarray;
					
			}
		}
	}
	public function deleteAction()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$id = $this->_request->getParam('objid');
		$deleteflag = $this->_request->getParam('deleteflag');
		$messages['message'] = '';
		$messages['msgtype'] = "";
		$actionflag = 3;
		if ($id) {
			$model = new Default_Model_Leave();

		
			$where = ['leave_id=?' => $id];
			$Id = $model->DeleteData($where);
			if ($Id == 'delete') {
				$messages['message'] = 'Leave deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Leave Category cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Leave cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		if ($deleteflag == 1) {
			if ($messages['msgtype'] == 'error') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(["error" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag]);
			}
			if ($messages['msgtype'] == 'success') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(["success" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag]);
			}
		}
		$menuID = LEAVE;
		
		$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
		$this->_helper->json($messages);
	} // end of delete action
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if ($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$objName = 'leave';

		$form = new Default_Form_Leave();

		$form->removeElement("submit");


		$model = new Default_Model_Leave();
		try {
			if ($id) {
				if (is_numeric($id) && $id > 0) {

					$data = $model->getsingleleaveData($id);

					//die(json_encode($data));
					if (!empty($data) && $data != "norows") {
						$form->populate($data[0]);
						$this->view->form = $form;
						$this->view->controllername = $objName;
						$this->view->data = $data;
						$this->view->id = $id;
						$this->view->ermsg = '';
					} else {

						$this->view->ermsg = 'nodata';
					}
				} else {
					$this->view->ermsg = 'nodata';
				}
			} else {
				$this->view->ermsg = 'nodata';
			}
			$this->view->controller='leave';
		} catch (Exception $e) {
			$this->view->ermsg = 'nodata';
		}
	}

}
