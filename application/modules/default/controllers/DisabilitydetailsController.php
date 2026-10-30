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

class Default_DisabilitydetailsController extends Zend_Controller_Action
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

	
	public function addpopupAction()
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
			$disabilitypermission = '';
			$disabilitypermission = sapp_Global::_checkprivileges(DISABILITY,$loginuserGroup,$loginuserRole,'add');
			$this->view->disabilitypermission = $disabilitypermission;
			$id = $this->getRequest()->getParam('unitId');
			if ($id == '')
				$id = $loginUserId;
			// For open the form in popup...

			$emptyFlag = 0;
			$form = new Default_Form_Disabilitydetails();


			$disabilityModel = new Default_Model_Disability();
			
			//---------------
			$disabilitylevelArr = $disabilityModel->getalldisability();
			$msgarray = [];
			$form->disability_id->addMultiOption('', 'Select Disablity');
			if (!empty($disabilitylevelArr)) {

				foreach ($disabilitylevelArr as $disabilitylevelres) {
					$form->disability_id->addMultiOption($disabilitylevelres['disability_id'], $disabilitylevelres['name']);
				}
			} else {
				$msgarray['disablity_id'] = 'Disablity are not configured yet.';
				$emptyFlag++;
			}



			//-------------------------------------//
			$form->setAttrib('action', BASE_URL . 'disabilitydetails/addpopup/unitId/' . $id);
			$this->view->form = $form;
			$this->view->msgarray = $msgarray;
			$this->view->emptyFlag = $emptyFlag;
			$this->view->controllername = 'disabilitydetails';
			if ($this->getRequest()->getPost()) {
				$result = $this->save($form);
				$this->view->msgarray = $result;
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function editpopupAction()
	{
		try {
			//code...
			$util = new utlis();

			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$disabilitypermission = '';
			$disabilitypermission = sapp_Global::_checkprivileges(DISABILITY,$loginuserGroup,$loginuserRole,'add');
			$this->view->disabilitypermission = $disabilitypermission;
			$id = $this->_getParam('id');
			$unitid = $this->_getParam('unitId');
			// For open the form in popup...

			$emptyFlag = 0;
			$form = new Default_Form_Disabilitydetails();
			$empDisabilitydetailsModel = new Default_Model_Disabilitydetails();


			$disabilityModel = new Default_Model_Disability();
			
			//---------------
			$disabilitylevelArr = $disabilityModel->getalldisability();
			$msgarray = [];
			$form->disability_id->addMultiOption('', 'Select Disablity');
			if (!empty($disabilitylevelArr)) {

				foreach ($disabilitylevelArr as $disabilitylevelres) {
					$form->disability_id->addMultiOption($disabilitylevelres['disability_id'], $disabilitylevelres['name']);
				}
			} else {
				$msgarray['disablity_id'] = 'Disablity are not configured yet.';
				$emptyFlag++;
			}
			if ($id) {
				$data = $empDisabilitydetailsModel->getdisabilitydetails($id);
				if (!empty($data)) {
					$form->populate($data[0]);
					// $empskillsform->setDefault('competencylevelid',$data[0]['competencylevelid']);
					$onsetdate1 = sapp_Global::change_date($data[0]['onsetdate'], 'view');
					$form->onsetdate->setValue($onsetdate1);
					$form->isactive->setValue($util::getcheckboxvalue($data[0]['isactive'], 'form'));
				}
			}
			$form->setAction(BASE_URL . 'disabilitydetails/editpopup/id/' . $id . '/unitId/' . $unitid . '/popup/1 ');

			//-------------------------------------//
			$this->view->form = $form;
			$this->view->msgarray = $msgarray;
			$this->view->emptyFlag = $emptyFlag;
			$this->view->controllername = 'disabilitydetails';
			if ($this->getRequest()->getPost()) {
				$result = $this->save($form);
				$this->view->msgarray = $result;
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function editAction()
	{

		try {
			//code...
				$empDisabilitydetailsModel = new Default_Model_Disabilitydetails();

			
				$employeeModal = new Default_Model_Employee();
				$usersModel = new Default_Model_Users();

					$userID = "";
					$levelsArr = [];
					$empcompetencyLevelsArr = [];
					$auth = Zend_Auth::getInstance();
					if ($auth->hasIdentity()) {
						$loginUserId = $auth->getStorage()->read()->id;
						$loginUserRole = $auth->getStorage()->read()->emprole;
						$loginUserGroup = $auth->getStorage()->read()->group_id;
					}
					$id = $this->getRequest()->getParam('userid');
					$conText = '';
					$call = $this->_getParam('call');
					if ($call == 'ajaxcall') {
						$this->_helper->layout->disableLayout();
						$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
						$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
					}

					if ($id == '')		$id = $userID;
					$Uid = $id ?: $userID;
					if ($Uid != "") {
						$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
					}
					try {
						if ($Uid && is_numeric($Uid) && $Uid > 0) {
							$empdata = $employeeModal->getActiveEmployeeData($Uid);

							$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
							if ($empdata == 'norows') {

								$this->view->rowexist = "norows";
								$this->view->empdata = "";
							} else {

								$this->view->rowexist = "rows";
								if (!empty($empdata)) {
									$view = Zend_Layout::getMvcInstance()->getView();
									$objname = $this->_getParam('objname');
									$refresh = $this->_getParam('refresh');
									$dashboardcall = $this->_getParam('dashboardcall', null);
									$data = [];
									$searchQuery = '';
									$searchArray = [];
									$tablecontent = '';
									if ($refresh == 'refresh') {
										if ($dashboardcall == 'Yes')
											$perPage = DASHBOARD_PERPAGE;
										else
											$perPage = PERPAGE;

										$sort = 'DESC';
										$by = 'ed.modifieddate';
										$pageNo = 1;
										$searchData = '';
										$searchQuery = '';
										$searchArray = '';
									} else {
										$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
										$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ed.modifieddate';
										if ($dashboardcall == 'Yes')
											$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
										else
											$perPage = $this->_getParam('per_page', PERPAGE);

										$pageNo = $this->_getParam('page', 1);
										$searchData = $this->_getParam('searchData');
										$searchData = rtrim($searchData, ',');
									}

									$dataTmp = $empDisabilitydetailsModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'edit');

									array_push($data, $dataTmp);
									$this->view->dataArray = $data;
									$this->view->call = $call;
									$this->view->employeedata = $employeeData[0];
									$this->view->id = $id;
									$this->view->messages = $this->_helper->flashMessenger->getMessages();
								}

								$this->view->empdata = $empdata;
							}
						} else {

							$this->view->rowexist = "norows";
						}
					} catch (Exception $e) {
						Zend_Debug::dump($e->getMessage());

						$this->view->rowexist = "norows";
					}
				
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function save($empDisabilitydetailsform)
	{
		$result ="";
		$util = new utlis();

		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$date = new Zend_Date();
		$empDisabilitydetailsModel = new Default_Model_Disabilitydetails();

		if($empDisabilitydetailsform->isValid($this->_request->getPost()))
		{

			$id = $this->_getParam('id');
			$unitid = $this->_getParam('unitId');			
			$disability_id = $this->_request->getParam('disability_id');
			$description =$this->_request->getParam('description');
			$onsetdate1 = $this->_request->getParam('onsetdate');
			$onsetdate = sapp_Global::change_date($onsetdate1, 'database');
			$data = [  'disability_id'=>$disability_id,
								'onsetdate'=>$onsetdate,
								'description'=>$description,
								'emp_id'=>$unitid,
								'modifiedby'=>$loginUserId,
			                    'modifieddate'=>gmdate("Y-m-d H:i:s"),

			];
			if($id!='')
			{
				$data['isactive'] = $util::getcheckboxvalue($this->_getParam('isactive'));
				$where = ['empdisability_id=?'=>$id];
				$actionflag = 2;
			}
			else
			{		

				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$where = '';
				$actionflag = 1;
			}
			try {
				//code...
			
			$Id = $empDisabilitydetailsModel->SaveorUpdateEmpdisabilityDetails($data, $where);
		} catch (\Throwable $th) {
die(Zend_Debug::dump($th->getMessage()));		}
			if($Id == 'update')
			{
				$tableid = $id;
				$this->view->successmessage = 'Employee Disability updated successfully.';
			}
			else
			{
				$tableid = $Id;
				$this->view->successmessage = 'Employee Disability Added successfully.';
			}
			$menuID = EMPLOYEE;
			$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $unitid);
			$this->view->controllername = 'disabilitydetails';
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		}
		else
		{
			$messages = $empDisabilitydetailsform->getMessages();

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
	public function viewpopupAction()
	{
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
		$objName = 'disabilitydetails';
		$form = new Default_Form_Disabilitydetails();
		$model = new Default_Model_Disabilitydetails();
		$form->removeElement("submit");
		$elements = $form->getElements();

		if (count($elements) > 0) {
			foreach ($elements as $key => $element) {
				if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
					$element->setAttrib("disabled", "disabled");
				}
			}
		}
		$data = $model->getdisabilitydetailsview($id);
		$this->view->controllername = $objName;
		$this->view->id = $id;
		$this->view->data = $data[0];
		$this->view->form = $form;
	}
	public function viewAction()
	{

		try {
			//code...
				$empDisabilitydetailsModel = new Default_Model_Disabilitydetails();

				$employeeModal = new Default_Model_Employee();
				$usersModel = new Default_Model_Users();

					$userID = "";
					$levelsArr = [];
					$empcompetencyLevelsArr = [];
					$auth = Zend_Auth::getInstance();
					if ($auth->hasIdentity()) {
						$loginUserId = $auth->getStorage()->read()->id;
						$loginUserRole = $auth->getStorage()->read()->emprole;
						$loginUserGroup = $auth->getStorage()->read()->group_id;
					}
					$id = $this->getRequest()->getParam('userid');
					$conText = '';
					$call = $this->_getParam('call');
					if ($call == 'ajaxcall') {
						$this->_helper->layout->disableLayout();
						$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
						$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
					}

					if ($id == '')		$id = $userID;
					$Uid = $id ?: $userID;
					if ($Uid != "") {
						$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
					}
					try {
						if ($Uid && is_numeric($Uid) && $Uid > 0) {
							$empdata = $employeeModal->getActiveEmployeeData($Uid);

							$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
							if ($empdata == 'norows') {

								$this->view->rowexist = "norows";
								$this->view->empdata = "";
							} else {

								$this->view->rowexist = "rows";
								if (!empty($empdata)) {
									$view = Zend_Layout::getMvcInstance()->getView();
									$objname = $this->_getParam('objname');
									$refresh = $this->_getParam('refresh');
									$dashboardcall = $this->_getParam('dashboardcall', null);
									$data = [];
									$searchQuery = '';
									$searchArray = [];
									$tablecontent = '';
									if ($refresh == 'refresh') {
										if ($dashboardcall == 'Yes')
											$perPage = DASHBOARD_PERPAGE;
										else
											$perPage = PERPAGE;

										$sort = 'DESC';
										$by = 'ed.modifieddate';
										$pageNo = 1;
										$searchData = '';
										$searchQuery = '';
										$searchArray = '';
									} else {
										$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
										$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ed.modifieddate';
										if ($dashboardcall == 'Yes')
											$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
										else
											$perPage = $this->_getParam('per_page', PERPAGE);

										$pageNo = $this->_getParam('page', 1);
										$searchData = $this->_getParam('searchData');
										$searchData = rtrim($searchData, ',');
									}

									$dataTmp = $empDisabilitydetailsModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'view');

									array_push($data, $dataTmp);
									$this->view->dataArray = $data;
									$this->view->call = $call;
									$this->view->employeedata = $employeeData[0];
									$this->view->id = $id;
									$this->view->messages = $this->_helper->flashMessenger->getMessages();
								}

								$this->view->empdata = $empdata;
							}
						} else {

							$this->view->rowexist = "norows";
						}
					} catch (Exception $e) {
						Zend_Debug::dump($e->getMessage());

						$this->view->rowexist = "norows";
					}
				
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

}

