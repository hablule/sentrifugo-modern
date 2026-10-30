<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_BranchController extends Zend_Controller_Action
{

	private $options;
	public function preDispatch()
	{
			

	}

	public function init()
	{
		$this->_options= $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';

	}

	public function indexAction()
	{
		$model = new Default_Model_Branch();

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
			$sort = 'DESC';$by = 'modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
		}
		else
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'l.modifieddate';
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

	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id');

		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();
		$objName = 'branch';
		
		$form = new Default_Form_branch();

		$form->removeElement("submit");

	
		$model = new Default_Model_branch();
		try
		{
			if($id)
			{
				if(is_numeric($id) && $id>0)
				{
					$data = $model->getsinglebranchData($id);
                  
					if(!empty($data) && $data != "norows")
					{
						$form->populate($data[0]);
						$this->view->form = $form;
						$this->view->controllername = $objName;
						$this->view->data = $data;
						$this->view->id = $id;
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
				$this->view->ermsg = 'nodata';
			}
		}
		catch(Exception $e)
		{
			$this->view->ermsg = 'nodata';
		}

	}


	public function editAction()
	{		
		try {
			//code...
		

		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = (int)$this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();
		$form = new Default_Form_branch();
		$form->branch->removeValidator('Db_NoRecordExists');

		$model = new Default_Model_branch();
		$form->setAttrib('action', BASE_URL +'branch/edit/id/'.$id.'');
		try
		{
		
			if($id)
			{
				if(is_numeric($id) && $id>0)
				{
					$data = $model->getsinglebranchData($id);
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

				$branch = $this->_request->getParam('branch');
				$description = $this->_request->getParam('description');
				$isactive=utlis::getcheckboxvalue($this->_request->getParam('isactive'),'database');
				$date = new Zend_Date();
				$actionflag = 2;
				$tableid  = '';
				$data = ['branch'=>trim($branch),
				 				'description'=>trim($description),
								 'isactive'=>$isactive,
						        'modifiedby'=>$loginUserId,
								'modifieddate'=>gmdate("Y-m-d H:i:s")
				];
				
					$where = 'emp_branch_id='.$id;
//die($where)	;
			$Id = $model->SaveorUpdatebranchData($data, $where);
				if($Id == 'update')
				{
					$tableid = $id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"branch updated successfully."]);
				}
				else
				{
					$tableid = $Id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"branch  added successfully."]);
				}
				
					$menuID = BRANCH;
				
					sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
				
				
				$this->_redirect('branch');
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
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}	
	}
	
	// public function deleteAction()
	// {
	// 	$auth = Zend_Auth::getInstance();
	// 	if($auth->hasIdentity()){
	// 		$loginUserId = $auth->getStorage()->read()->id;
	// 	}
	// 	$locationform = new Default_Form_location();


	// 	$id = $this->_request->getParam('objid');
	// 	die($id."");
	// 	$deleteflag= $this->_request->getParam('deleteflag');
	// 	$locationform->setAttrib('action', BASE_URL +'location/delete/id/'.$id.'');

	// 	$messages['message'] = '';
	// 	$messages['msgtype'] = '';
	// 	$actionflag = 3;
	// 	if($id)
	// 	{
	// 		$locationmodel = new Default_Model_Location();
	// 		$locationdata = $locationmodel->getsinglelocationData($id);

	// 		$data = array('isactive'=>0,'modifieddate'=>gmdate("Y-m-d H:i:s"));
	// 		$where = array('id=?'=>$id);
	// 		$Id = $locationmodel->SaveorUpdatelocationData($data, $where);
	// 		if($Id == 'update')
	// 		{
	// 			$menuID = LOCATION;
	// 			$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$id);
	// 			$configmail = sapp_Global::send_configuration_mail('location',$locationdata[0]['location']);
					
	// 			$messages['message'] = 'location deleted successfully.';
	// 			$messages['msgtype'] = 'success';
	// 		}
	// 		else
	// 		{
	// 			$messages['message'] = 'location cannot be deleted.';
	// 			$messages['msgtype'] = 'error';
	// 		}
	// 	}
	// 	else
	// 	{
	// 		$messages['message'] = 'location cannot be deleted.';
	// 		$messages['msgtype'] = 'error';
	// 	}
	// 	if($deleteflag==1)
	// 	{
	// 		if(	$messages['msgtype'] == 'error')
	// 		{
	// 			$this->_helper->getHelper("FlashMessenger")->addMessage(array("error"=>$messages['message'],"msgtype"=>$messages['msgtype'] ,'deleteflag'=>$deleteflag));
	// 		}
	// 		if(	$messages['msgtype'] == 'success')
	// 		{
	// 			$this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>$messages['message'],"msgtype"=>$messages['msgtype'],'deleteflag'=>$deleteflag));
	// 		}
				
	// 	}
	// 	$this->_helper->json($messages);

	// }

	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->getRequest()->getParam('id');

		$msgarray = [];
		$controllername = 'branch';
		$form = new Default_Form_branch();
		$model = new Default_Model_Branch();
		$form->setAction(BASE_URL.'branch/addpopup');
		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){
				$id = $this->_request->getParam('id');
				$branch = $this->_request->getParam('branch');
				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = ['branch'=>trim($branch),
				 				'description'=>trim($description),
						        'modifiedby'=>$loginUserId,
								'modifieddate'=>gmdate("Y-m-d H:i:s")
				];

				if($id!=''){
					$where = ['emp_branch_id=?'=>$id];
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
				$Id = $model->SaveorUpdatebranchData($data, $where);
				$tableid = $Id;
				$branchData = $model->fetchAll('isactive = "Y"','branch')->toArray();
				$opt ='';
				foreach($branchData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['emp_branch_id'], $record['branch']);
				}
				$menuID = BRANCH;
				sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
				$this->view->branchData = $opt;
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


	public function addAction()
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$msgarray = [];
		$controllername = 'branch';
		$form = new Default_Form_branch();
		$model = new Default_Model_Branch();
		$form->setAction(BASE_URL.'branch/add');
		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){
				$branch = $this->_request->getParam('branch');
				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = ['branch'=>trim($branch),
				 				'description'=>trim($description),	       
				];

					$data['createdby'] = $loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");
					$data['isactive'] = "Y";
					$where = '';
					$actionflag = 1;

				$Id = $model->SaveorUpdatebranchData($data, $where);
				$tableid = $Id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Branch  added successfully."]);

					$menuID = BRANCH;
					sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
				$this->_redirect('branch');

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

	public function deleteAction()
    {
        $formModel = new Default_Model_Branch();

		$id = $this->_request->getParam('objid');
		$where = 'emp_branch_id =' . (int)$id;
		$data = [
			'isactive' => 'N'
		];
		//die(json_encode($where));
        $res=$formModel->SaveorUpdatebranchData($data, $where);

		if ($res > 0)
			return $this->_helper->json(['flagtype' => 'process', 'message' => 'Deleted']);
		die();
    }
}

