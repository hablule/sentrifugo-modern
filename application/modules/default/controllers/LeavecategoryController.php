<?php

class Default_LeavecategoryController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';

    }
  
    public function indexAction()
    {
		

        $model = new Default_Model_Leavecategory();
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
			$sort = 'DESC';$by = 'leavecategory_id';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
		}
		else
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'lc.leavecategory_id';
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
		$controllername = 'leavecategory';
		$form = new Default_Form_Leavecategory();
		$form->name->addValidator(new Zend_Validate_Db_NoRecordExists(
			[
				'table'=>'main_leavecategory',
				'field'=>'name'
			]
			));
	  $form->name->getValidator('Db_NoRecordExists')->setMessage('Name already exists.');
	 	
		$model = new Default_Model_Leavecategory();
		$form->setAction(BASE_URL.'leavecategory/add');
		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){
				$name = $this->_request->getParam('name');
				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = ['name'=>$name,
				 				'description'=>$description,	       
				];

					$data['createdby'] = $loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");
					$data['isactive'] = "Y";
					$where = '';
					$actionflag = 1;

				$Id = $model->SaveorUpdateData($data, $where);
				
				$tableid = $Id;
				$result = sapp_Global::logManager(LEAVECATEGORY,1,$loginUserId,$tableid);
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Leave Category  added successfully."]);

				
				$this->_redirect('leavecategory');

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
	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->getRequest()->getParam('id');

		$msgarray = [];
		$controllername = 'leavecategory';
		$form = new Default_Form_Leavecategory();
		$model = new Default_Model_Leavecategory();
		$form->setAction(BASE_URL.'leavecategory/addpopup');
		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){
				$id = $this->_request->getParam('id');
				$name = $this->_request->getParam('name');
				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = ['name'=>$name,
				 				'description'=>$description,
						        'modifiedby'=>$loginUserId,
								'modifieddate'=>gmdate("Y-m-d H:i:s")
				];

				if($id!=''){
					$where = ['leavecategory_id=?'=>$id];
					$actionflag = 2;
				}
				else
				{
					$data['createdby'] = $loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");
					$data['isactive'] = "Y";
					$where = '';
					$actionflag = 1;
				}
				$Id = $model->SaveorUpdateData($data, $where);
				$tableid = $Id;
				$result = sapp_Global::logManager(LEAVECATEGORY,$actionflag,$loginUserId,$tableid);
				$leavecategoryData = $model->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($leavecategoryData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['leavecategory_id'], $record['name']);
				}
				$this->view->leavecategoryData = $opt;
				$this->view->eventact = 'added';
				$close = 'close';
				$this->view->popup=$close;
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
		$form = new Default_Form_Leavecategory();
		$model = new Default_Model_Leavecategory();
		$form->setAttrib('action', BASE_URL +'leavecategory/edit/id/'.$id.'');
		try
		{
		
			if($id)
			{
				if(is_numeric($id) && $id>0)
				{
					$data = $model->getsingleData($id);
				$form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactivecheck'],'form'));

					if(!empty($data) && $data != "norows")
					{
						$form->populate($data[0]);
						$form->submit->setLabel('Update');
						$this->view->data = $data;
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
				$description = $this->_request->getParam('description');
				$isactive = (utlis::getcheckboxvalue($this->_request->getParam('isactive'),'database'));

				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = ['name'=>$name,
				 				'description'=>$description,
						        'modifiedby'=>$loginUserId,
								'isactive'=>$isactive,

								'modifieddate'=>gmdate("Y-m-d H:i:s")
				];
				
					$where = 'leavecategory_id='.$id;
			$Id = $model->SaveorUpdateData($data, $where);
			
				if($Id == 'update')
				{
					$tableid = $id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Leave Category updated successfully."]);
				}
				else
				{
					$tableid = $Id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Leave Category  added successfully."]);
				}
				$result = sapp_Global::logManager(LEAVECATEGORY,2,$loginUserId,$tableid);
				$this->_redirect('leavecategory');
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
			$model = new Default_Model_Leavecategory();

			$data = [
				'isactive' => 'N',
				'modifiedby' => $loginUserId,
				'modifieddate' => gmdate("Y-m-d H:i:s"),
			];
			$where = ['leavecategory_id=?' => $id];
			$Id = $model->SaveorUpdateData($data, $where);
			$tableid=$id;	
			$result = sapp_Global::logManager(LEAVECATEGORY,3,$loginUserId,$tableid);
			if ($Id == 'update') {
				$messages['message'] = 'Leave Category deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Leave Category cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Leave Category cannot be deleted.';
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
		$this->_helper->json($messages);
	} // end of delete action
}
