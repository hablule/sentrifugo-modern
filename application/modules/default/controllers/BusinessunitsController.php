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

class Default_BusinessunitsController extends Zend_Controller_Action
{

    private $options;
    public function preDispatch()
    {		 		
    }
	/**
	 * Init
	 *
	 * @see Zend_Controller_Action::init()
	 */
    public function init()
    {
        $this->_options= $this->getInvokeArg('bootstrap')->getOptions();	
		$this->businessunitsmodel = new Default_Model_Businessunits();
		require_once 'application/modules/default/library/utilClasses/Util.php';
        $util = new utlis();
    }
	
    public function indexAction()
    {
	    $orgInfoModel = new Default_Model_Organisationinfo();
		$getorgData = $orgInfoModel->getorgrecords();
		    if(!empty($getorgData))
			{
			    $orgdata = ''; 
				$businessunitModel = new Default_Model_Businessunits();	
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
					$sort = 'DESC';$by = 'b.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
				}
				else 
				{
					$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
					$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'b.modifieddate';
					if($dashboardcall == 'Yes')
						$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
					else $perPage = $this->_getParam('per_page',PERPAGE);
					$pageNo = $this->_getParam('page', 1);
					$searchData = $this->_getParam('searchData');	
					$searchData = rtrim($searchData,',');					
				}
						
				$dataTmp = $businessunitModel->getGrid($sort, $by, $perPage, $pageNo, $searchData,$call,$dashboardcall);
				array_push($data,$dataTmp);
				$this->view->dataArray = $data;
				$this->view->call = $call ;
				$this->view->messages = $this->_helper->flashMessenger->getMessages();
			}
			else
			{
			    $orgdata = 'noorgdata';
                $this->view->orgdata = $orgdata ;			
			}	
	}
	public function addAction()
	{
		try {
			//code...
	 
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$msgarray = [];

		$controllername = 'businessunits';
	 
		$form = new Default_Form_businessunits();

		$model = new Default_Model_Businessunits();

		// $form->setAction(BASE_URL.'division/add');
		$modelcompany = new Default_Model_Organisationinfo();
		$modelsbunit=new Default_Model_Businessunits();
		$modelemp=new Default_Model_Employee();
		$empdatas=$modelemp->getEmplist();
		$sbunitdatas=$modelsbunit->getsbunit();
		$compdatas=$modelcompany->getSelecteOrgRecords();
		// $add = $modelb->getcompany();
		
		$form->representative_id->addMultiOption('', 'Select...');
		$form->company_id->addMultiOption('', 'Select...');
		$form->parentsbu_id->addMultiOption('', 'Select...');
		
		if (empty($compdatas)) {
			$msgarray['company_id'] = 'Company is not configured yet.';
		} else {
				$form->company_id->addMultiOptions([$compdatas['company_id'] => $compdatas['name']]);
		
		}
		if (empty($sbunitdatas)) {
			$msgarray['parentsbu_id'] = 'Bussiness Unit is not configured yet.';
		} else {
			foreach($sbunitdatas as $sbunitdata){
				$form->parentsbu_id->addMultiOptions([$sbunitdata['sbunit_id'] => $sbunitdata['name']]);

			}
		
		}
		if (empty($empdatas)) {
			$msgarray['representative_id'] = 'Employee is not configured yet.';
		} else {
			foreach($empdatas as $empdata){
				$form->representative_id->addMultiOptions([$empdata['id'] => $empdata['userfullname']]);

			}
		
		}
	
   
		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost()) ){
if($this->_request->getParam('company_id')=='' && $this->_request->getParam('parentsbu_id') ==''){
$msgarray['allfk'] ='choose one from Company, Parent Bussiness Unit ';
$this->view->msgarray = $msgarray;
}    else{                $name = $this->_request->getParam('name');
				$code = $this->_request->getParam('code');
				$representative_id = $this->_request->getParam('representative_id');
				$description = $this->_request->getParam('description');
				$company_id = $this->_request->getParam('company_id');
				$parentsbu_id	 = $this->_request->getParam('parentsbu_id	');
			
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = [	
				'name' => $name,
				'code'=>$code,
				'description' => $description,
				'representative_id' => $representative_id,
				'company_id' => $company_id,   
				'parentsbu_id' => $parentsbu_id, 
				];

					$data['createdby'] = $loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");
					$data['isactive'] = "Y";
					$where = '';
					$actionflag = 1;
					$menuID =  BUSINESSUNITS;
//die(json_encode($data));
				$Id = $model->SaveorUpdateBusinessUnits($data, $where);
				$tableid = $Id;
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Bussieness Unit   added successfully."]);
					$this->view->messages = $this->_helper->flashMessenger->getMessages();

				
				$this->_redirect('businessunits');
			}
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

			}
		}
		$this->view->msgarray = $msgarray;

		$this->view->controllername = $controllername;
		$this->view->form = $form;
		$this->view->ermsg = '';
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());       
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

		$msgarray = [];
		$id = $this->_request->getParam("id");

		$controllername = 'businessunits';
	 
		$form = new Default_Form_businessunits();

		$model = new Default_Model_Businessunits();

		// $form->setAction(BASE_URL.'division/add');
		$modelcompany = new Default_Model_Organisationinfo();
		$modelsbunit=new Default_Model_Businessunits();
		$modelemp=new Default_Model_Employee();
		$empdatas=$modelemp->getEmplist();
		$sbunitdatas=$modelsbunit->getsbunit();
		$compdatas=$modelcompany->getSelecteOrgRecords();
		// $add = $modelb->getcompany();

		$form->representative_id->addMultiOption('', 'Select...');
		$form->company_id->addMultiOption('', 'Select...');
		$form->parentsbu_id->addMultiOption('', 'Select...');
		
		if (empty($compdatas)) {
			$msgarray['company_id'] = 'Company is not configured yet.';
		} else {
				$form->company_id->addMultiOptions([$compdatas['company_id'] => $compdatas['name']]);
		
		}
		if (empty($sbunitdatas)) {
			$msgarray['parentsbu_id'] = 'Bussiness Unit is not configured yet.';
		} else {
			foreach($sbunitdatas as $sbunitdata){
				$form->parentsbu_id->addMultiOptions([$sbunitdata['sbunit_id'] => $sbunitdata['name']]);

			}
		
		}
		if (empty($empdatas)) {
			$msgarray['representative_id'] = 'Employee is not configured yet.';
		} else {
			foreach($empdatas as $empdata){
				$form->representative_id->addMultiOptions([$empdata['id'] => $empdata['userfullname']]);

			}
		
		}
	
   $getparticularbudata=$model->getparticularsbu($id);
  //die(json_encode($getparticularbudata));
   $form->name->setValue($getparticularbudata['name']);
   $form->code->setValue($getparticularbudata['code']);
   if($getparticularbudata['comp_id'] !=NULL){

	$form->company_id->setValue($getparticularbudata['comp_id']);
   }
  
   if($getparticularbudata['parentsbu_id '] !=NULL){
	$form->sbunit->setValue($getparticularbudata['parentsbu_id ']);
   }
   $form->representative_id->setValue($getparticularbudata['representative_id']);
   $form->description->setValue($getparticularbudata['description']);
   $form->isactive->setValue(utlis::getcheckboxvalue($getparticularbudata['isactive'],'form'));

		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost()) ){
if($this->_request->getParam('company_id')=='' && $this->_request->getParam('parentsbu_id') ==''){
$msgarray['allfk'] ='choose one from Company or Parent Bussiness Unit ';
$this->view->msgarray = $msgarray;
}    else{               
$id = $this->_request->getParam("id");

$name = $this->_request->getParam('name');
				$code = $this->_request->getParam('code');
				$representative_id = $this->_request->getParam('representative_id');
				$description = $this->_request->getParam('description');
				$company_id = $this->_request->getParam('company_id');
				$parentsbu_id	 = $this->_request->getParam('parentsbu_id	');
				$isactive = (utlis::getcheckboxvalue($this->_request->getParam('isactive'),'database'));

				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = [	
				'name' => $name,
				'code'=>$code,
				'description' => $description,
				'representative_id' => $representative_id,
				'company_id' => $company_id,   
				'parentsbu_id' => $parentsbu_id, 
				'isactive'=>$isactive
				];

					$data['modifiedby'] = $loginUserId;
					$data['modifieddate'] = gmdate("Y-m-d H:i:s");
					$where ="sbunit_id=".$id;
					$actionflag = 2;
					$menuID =  BUSINESSUNITS;
				$Id = $model->SaveorUpdateBusinessUnits($data, $where);
				$tableid = $Id;
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Business Unit   Updated successfully."]);
					$this->view->messages = $this->_helper->flashMessenger->getMessages();

				
				$this->_redirect('businessunits');
			}
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

			}
		}
		$this->view->msgarray = $msgarray;

		$this->view->controllername = $controllername;
		$this->view->form = $form;
		$this->view->ermsg = '';
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());       
}

	}
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if ($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$objName = 'division';
		$form = new Default_Form_businessunits();

		$form->removeElement("submit");


		$model = new Default_Model_Businessunits();
		try {
			if ($id) {
				if (is_numeric($id) && $id > 0) {

					$data = $model->getparticularsbu($id);

					//die(json_encode($data));
					if (!empty($data) && $data != "norows") {
						$form->populate($data);
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
			$this->view->controllername='businessunits';

		} catch (Exception $e) {
			$this->view->ermsg = 'nodata';
		}
	}
	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		try {
			//code...

		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->_request->getParam('id');
		$this->view->status=$id;
		$msgarray = [];

		$controllername = 'businessunits';
	 
		$form = new Default_Form_businessunits();

		$model = new Default_Model_Businessunits();
		// $form->setAction(BASE_URL.'division/add');
		$modelcompany = new Default_Model_Organisationinfo();
		$modelsbunit=new Default_Model_Businessunits();
		$modelemp=new Default_Model_Employee();
		$empdatas=$modelemp->getEmplist();
		$sbunitdatas=$modelsbunit->getsbunit();
		$compdatas=$modelcompany->getSelecteOrgRecords();
		// $add = $modelb->getcompany();
		
		$form->representative_id->addMultiOption('', 'Select...');
		$form->company_id->addMultiOption('', 'Select...');
		$form->parentsbu_id->addMultiOption('', 'Select...');
		
		if (empty($compdatas)) {
			$msgarray['company_id'] = 'Company is not configured yet.';
		} else {
				$form->company_id->addMultiOptions([$compdatas['company_id'] => $compdatas['name']]);
		
		}
		if (empty($sbunitdatas)) {
			$msgarray['parentsbu_id'] = 'Bussiness Unit is not configured yet.';
		} else {
			foreach($sbunitdatas as $sbunitdata){
				$form->parentsbu_id->addMultiOptions([$sbunitdata['sbunit_id'] => $sbunitdata['name']]);

			}
		
		}
		if (empty($empdatas)) {
			$msgarray['representative_id'] = 'Employee is not configured yet.';
		} else {
			foreach($empdatas as $empdata){
				$form->representative_id->addMultiOptions([$empdata['id'] => $empdata['userfullname']]);

			}
		
		}
	
   
		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost()) ){
if($this->_request->getParam('company_id')=='' && $this->_request->getParam('parentsbu_id') ==''){
$msgarray['allfk'] ='choose one from Company, Parent Bussiness Unit ';
$this->view->msgarray = $msgarray;
}    else{              
	$id = $this->getRequest()->getParam('id');

	$name = $this->_request->getParam('name');
				$code = $this->_request->getParam('code');
				$representative_id = $this->_request->getParam('representative_id');
				$description = $this->_request->getParam('description');
				$company_id = $this->_request->getParam('company_id');
				$parentsbu_id	 = $this->_request->getParam('parentsbu_id	');
			
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = [	
				'name' => $name,
				'code'=>$code,
				'description' => $description,
				'representative_id' => $representative_id,
				'company_id' => $company_id,   
				'parentsbu_id' => $parentsbu_id, 
				];

					$data['createdby'] = $loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");
					$data['isactive'] = "Y";
					$where = '';
					$actionflag = 1;
					$menuID =  BUSINESSUNITS;
//die(json_encode($data));
if($id==1)
{
	$this->view->nested = true;
	
}
else{
	$this->view->nested = false;
}
$Id = $model->SaveorUpdateBusinessUnits($data, $where);
			$tableid = $Id;
			$result = sapp_Global::logManager(BUSINESSUNITS,$actionflag,$loginUserId,$tableid);
			$bData = $model->fetchAll('isactive = "Y"','name')->toArray();
			$opt ='';
			foreach($bData as $record){
				$opt .= sapp_Global::selectOptionBuilder($record['sbunit_id'], $record['name']);
			}
			$this->view->bData = $opt;

			$this->view->eventact = 'added';
			$close = 'close';
			$this->view->popup=$close;
			}
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
		$this->view->msgarray = $msgarray;

		$this->view->controllername = $controllername;
		$this->view->form = $form;
		$this->view->ermsg = '';
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());       
}
	
	}
	
}