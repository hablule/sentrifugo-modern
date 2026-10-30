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

class Default_CreditcarddetailsController extends Zend_Controller_Action
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
	}

	public function addAction()
	{
		
		 	$msgarray = array();
		 	$auth = Zend_Auth::getInstance();
		 	if($auth->hasIdentity())
		 	{
		 		$loginUserId = $auth->getStorage()->read()->id;
		 	}
		 	$creditcardDetailsform = new Default_Form_Creditcarddetails();
		 	$this->view->form = $creditcardDetailsform;
		 	$this->view->msgarray = $msgarray;
		 	$creditcardDetailsform->setAttrib('action',BASE_URL.'creditcarddetails/edit/add');
		 	if($this->getRequest()->getPost())
		 	{
		 		$result = $this->save($creditcardDetailsform);
		 		$this->view->form = $creditcardDetailsform;
		 		$this->view->msgarray = $result;
		 	}
		

	}
	public function editAction()
	{
		try {
			//code...
	
		 	$userID='';$empdata=array(); $conText = "";$objName = 'creditcarddetails';
		 	$auth = Zend_Auth::getInstance();
		 	if($auth->hasIdentity()){
		 		$loginUserId = $auth->getStorage()->read()->id;
		 		$loginUserRole = $auth->getStorage()->read()->emprole;
		 		$loginUserGroup = $auth->getStorage()->read()->group_id;
		 	}
		 	$userid = $this->getRequest()->getParam('userid');	
		 	$call = $this->_getParam('call');
		 	if($call == 'ajaxcall')
		 	{
		 		$this->_helper->layout->disableLayout();
		 		$userID = ($this->_getParam('unitId') !='')? $this->_getParam('unitId'):$this->_getParam('userid');
		 		$conText = ($this->_getParam('context') !='')? $this->_getParam('context'):$this->getRequest()->getParam('context');
		 	}
		 	if($userid == '') $userid =$userID;
		 	$Uid = ($userid)?$userid:$userID;
		 	$employeeModal = new Default_Model_Employee();
		 	try
		 	{
			    if($userid && is_numeric($userid) && $userid>0 )
				{
					$usersModel = new Default_Model_Users();
					$empdata = $employeeModal->getActiveEmployeeData($userid);
					$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);

					if($empdata == 'norows')
					{
						$this->view->rowexist = "norows";
						$this->view->empdata = "";
					}
					else
					{
						$this->view->rowexist = "rows";
						if(!empty($empdata))
						{
							$TandCdetailsModel = new Default_Model_Creditcarddetails();
							$view = Zend_Layout::getMvcInstance()->getView();

							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = array();	$searchQuery = '';	$searchArray = array();		$tablecontent = '';

							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'epc.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';	$searchArray = array();
							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'epc.modifieddate';
								if($dashboardcall == 'Yes')
								$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
								else
								$perPage = $this->_getParam('per_page',PERPAGE);
								$pageNo = $this->_getParam('page', 1);
								$searchData = $this->_getParam('searchData');
								$searchData = rtrim($searchData,',');
							}
							$dataTmp = $TandCdetailsModel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$Uid,$conText,'edit');

							array_push($data,$dataTmp);
							$this->view->id=$Uid;	
							$this->view->controllername = $objName;
							$this->view->dataArray = $data;
							$this->view->employeedata = $employeeData[0];
							$this->view->call = $call ;
							$this->view->messages = $this->_helper->flashMessenger->getMessages();
						}
						$this->view->empdata = $empdata;
					}
				}
				else
				{
				  $this->view->rowexist = "norows";
				}
		 	}
		 	catch(Exception $e)
		 	{
		 		$this->view->rowexist = "norows";
		 	}
		
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
	}

	public function viewAction()
	{
		try {
			//code...
	
	
		 	$userID='';$empdata=array(); $conText = "";$objName = 'creditcarddetails';
		 	$auth = Zend_Auth::getInstance();
		 	if($auth->hasIdentity()){
		 		$loginUserId = $auth->getStorage()->read()->id;
		 		$loginUserRole = $auth->getStorage()->read()->emprole;
		 		$loginUserGroup = $auth->getStorage()->read()->group_id;
		 	}
		 	$userid = $this->getRequest()->getParam('userid');	
		 	$call = $this->_getParam('call');
		 	if($call == 'ajaxcall')
		 	{
		 		$this->_helper->layout->disableLayout();
		 		$userID = ($this->_getParam('unitId') !='')? $this->_getParam('unitId'):$this->_getParam('userid');
		 		$conText = ($this->_getParam('context') !='')? $this->_getParam('context'):$this->getRequest()->getParam('context');
		 	}
		 	if($userid == '') $userid =$userID;
		 	$Uid = ($userid)?$userid:$userID;
		 	$employeeModal = new Default_Model_Employee();
		 	try
		 	{
			    if($userid && is_numeric($userid) && $userid>0 )
				{
					$usersModel = new Default_Model_Users();
					$empdata = $employeeModal->getActiveEmployeeData($userid);
					$employeeData = $usersModel->getUserDetailsByIDandFlag($userid);

					if($empdata == 'norows')
					{
						$this->view->rowexist = "norows";
						$this->view->empdata = "";
					}
					else
					{
						$this->view->rowexist = "rows";
						if(!empty($empdata))
						{
							$TandCdetailsModel = new Default_Model_Creditcarddetails();
							$view = Zend_Layout::getMvcInstance()->getView();

							$objname = $this->_getParam('objname');
							$refresh = $this->_getParam('refresh');
							$dashboardcall = $this->_getParam('dashboardcall',null);
							$data = array();	$searchQuery = '';	$searchArray = array();		$tablecontent = '';

							if($refresh == 'refresh')
							{
								if($dashboardcall == 'Yes')
								$perPage = DASHBOARD_PERPAGE;
								else
								$perPage = PERPAGE;

								$sort = 'DESC';$by = 'epc.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';	$searchArray = array();
							}
							else
							{
								$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
								$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'epc.modifieddate';
								if($dashboardcall == 'Yes')
								$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
								else
								$perPage = $this->_getParam('per_page',PERPAGE);
								$pageNo = $this->_getParam('page', 1);
								$searchData = $this->_getParam('searchData');
								$searchData = rtrim($searchData,',');
							}
							$dataTmp = $TandCdetailsModel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$Uid,$conText,'view');

							array_push($data,$dataTmp);
							$this->view->id=$Uid;	
							$this->view->controllername = $objName;
							$this->view->dataArray = $data;
							$this->view->employeedata = $employeeData[0];
							$this->view->call = $call ;
							$this->view->messages = $this->_helper->flashMessenger->getMessages();
						}
						$this->view->empdata = $empdata;
					}
				}
				else
				{
				  $this->view->rowexist = "norows";
				}
		 	}
		 	catch(Exception $e)
		 	{
		 		$this->view->rowexist = "norows";
		 	}
		
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
	}

	public function addpopupAction()
	{
		try {
			//code...
	
		$msgarray = array();
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$cardpermission = '';
		$cardpermission = sapp_Global::_checkprivileges(PAYMENTCARD,$loginuserGroup,$loginuserRole,'add');
		$this->view->cardpermission = $cardpermission;
		$emptyFlag = 0;

		$TandCDetailsform = new Default_Form_Creditcarddetails();

		$userId = $this->getRequest()->getParam('unitId');

		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");

		$modela = new Default_Model_Paymentcard();
//---------------
$level1Arr = $modela->getallpaymentcard();
$msgarray = array();
$optionClass=array();
$TandCDetailsform->paymentcard_id->addMultiOption('', 'Select Paymentcard');
if (!empty($level1Arr)) {

	foreach ($level1Arr as $level1Arrres) {
		$TandCDetailsform->paymentcard_id->addMultiOption($level1Arrres['paymentcard_id'], $level1Arrres['name']);

	}
} else {
	$msgarray['paymentcard_id'] = 'Paymentcard   are not configured yet.';
	$emptyFlag++;
}

		$TandCDetailsform->setAttrib('action',BASE_URL.'creditcarddetails/addpopup/unitId/'.$userId);
		$this->view->form = $TandCDetailsform;
		$this->view->msgarray = $msgarray;
		
		$this->view->controllername = 'creditcarddetails';
		if ($this->getRequest()->getPost()) {
			$result = $this->save($TandCDetailsform);
			$this->view->msgarray = $result;
			$this->view->form = $TandCDetailsform;
		}


	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}

	}
	public function editpopupAction()
	{
		
		try {
			//code...
	
		$msgarray = array();
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$cardpermission = '';
		$cardpermission = sapp_Global::_checkprivileges(PAYMENTCARD,$loginuserGroup,$loginuserRole,'add');
		$this->view->cardpermission = $cardpermission;
		$emptyFlag = 0;

		$TandCDetailsform = new Default_Form_Creditcarddetails();
		$model = new Default_Model_Creditcarddetails();

		$userId = $this->getRequest()->getParam('unitId');

		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");

		$modela = new Default_Model_Paymentcard();
//---------------
$level1Arr = $modela->getallpaymentcard();
$msgarray = array();
$optionClass=array();
$TandCDetailsform->paymentcard_id->addMultiOption('', 'Select Paymentcard');
if (!empty($level1Arr)) {

	foreach ($level1Arr as $level1Arrres) {
		$TandCDetailsform->paymentcard_id->addMultiOption($level1Arrres['paymentcard_id'], $level1Arrres['name']);

	}
} else {
	$msgarray['paymentcard_id'] = 'Paymentcard   are not configured yet.';
	$emptyFlag++;
}

$id = $this->_getParam('id');
$unitid = $this->_getParam('unitId');
$data = $model->getcreditcarddetailsRecord($id);

if (!empty($data)) {
	$TandCDetailsform->populate($data[0]);
	// $empskillsform->setDefault('competencylevelid',$data[0]['competencylevelid']);
	$issueddate = sapp_Global::change_date($data[0]['issueddate'], 'view');
	$expirydate = sapp_Global::change_date($data[0]['expirydate'], 'view');
	$TandCDetailsform->issueddate->setValue($issueddate);
	$TandCDetailsform->expirydate->setValue($expirydate);
	$TandCDetailsform->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'],'form'));

}
$TandCDetailsform->setAction(BASE_URL . 'creditcarddetails/editpopup/id/' . $id . '/unitId/' . $unitid . '/popup/1 ');
$this->view->form = $TandCDetailsform;
		$this->view->msgarray = $msgarray;
		$this->view->form = $TandCDetailsform;
		$this->view->msgarray = $msgarray;
		$this->view->emptyFlag = $emptyFlag;
		$this->view->controllername = 'creditcarddetails';
		if ($this->getRequest()->getPost()) {
			$result = $this->save($TandCDetailsform);
			$this->view->msgarray = $result;
		}


	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}

	}

	public function save($TandCDetailsform)
	{
		try {
			//code...
			$id = $this->_getParam('id');
			$unitid = $this->_getParam('unitId');

			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
			}
		
			$empskillsModal = new Default_Model_Creditcarddetails();
			$date = new Zend_Date();
			$actionflag = '';
			$tableid  = '';
			if ($TandCDetailsform->isValid($this->_request->getPost())) {
				$util = new utlis();

			$id = $this->getRequest()->getParam('id');	

			$paymentcard_id = $this->_request->getParam('paymentcard_id');
			$nameoncard = $this->_request->getParam('nameoncard');
			$cardnumber = $this->_request->getParam('cardnumber');
			$verficationcode = $this->_request->getParam('verficationcode');
			$description = $this->_request->getParam('description');
			$issuedDate =sapp_Global::change_date($this->_request->getParam('issueddate',null), 'database');;
			$expirydate =sapp_Global::change_date($this->_request->getParam('expirydate',null), 'database');;

		
					try {

						$data = array('paymentcard_id'=>$paymentcard_id,
														'description'=>$description,
														'nameoncard'=>$nameoncard,
														'cardnumber'=>$cardnumber,
														'verficationcode'=>$verficationcode,
														'issueddate'=>$issuedDate,
														'expirydate'=>$expirydate,
														'emp_id' => $unitid,
														'modifiedby'=>$loginUserId,
									                    'modifieddate'=>gmdate("Y-m-d H:i:s")
									);
						if ($id != '') {

							$where = 'emppaymentcard_id= ' . $id;
							$data['isactive'] = $util::getcheckboxvalue($this->_getParam('isactive'));

							$actionflag = 2;
						} else {

							$data['createdby'] = $loginUserId;
							$data['createddate'] = gmdate("Y-m-d H:i:s");
							$data['isactive'] = "Y";
							$where = '';
							$actionflag = 1;
						}
						//	die($where);
						$Id = $empskillsModal->SaveorUpdateCreditcardDetails($data, $where);
					} catch (Exception $e) {
						Zend_Debug::dump($e->getMessage());
					}
					if ($Id == 'update') {
						$tableid = $id;
								$this->view->successmessage = 'Employee Paymentcard details updated successfully.';
					} else {
						$tableid = $Id;
		$this->view->successmessage = 'Employee Paymentcard details added successfully.';
}
					$menuID = EMPLOYEE;
					$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $unitid);
					$this->view->controllername = 'creditcarddetails';
					Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
				} else{
				$messages = $TandCDetailsform->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgarray[$key] = $val2;
						break;
					}
				}
				return $msgarray;
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function viewpopupAction()
	{
		try {
			//code...
		
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$id = $this->getRequest()->getParam('id');
		if ($id == '')
			$id = $loginUserId;
		$callval = $this->getRequest()->getParam('call');
		if ($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$objName = 'creditcarddetails';
		$empskillsform = new Default_Form_Creditcarddetails();
		$empskillsModel = new Default_Model_Creditcarddetails();
		$empskillsform->removeElement("submit");
		$elements = $empskillsform->getElements();
	
		if (count($elements) > 0) {
			foreach ($elements as $key => $element) {
				if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
					$element->setAttrib("disabled", "disabled");
				}
			}
		}
		$data = $empskillsModel->paymentcarddetailview($id);
		$this->view->controllername = $objName;
		$this->view->id = $id;
		$this->view->data = $data[0];
		$this->view->form = $empskillsform;
	} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());	}
	}
}
