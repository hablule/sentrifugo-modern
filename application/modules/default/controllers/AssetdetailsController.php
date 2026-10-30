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
class Default_AssetdetailsController extends Zend_Controller_Action
{
	private $options;
	public function preDispatch()
	{
		$ajaxContext = $this->_helper->getHelper('AjaxContext');

	}

	public function init()
	{
		$this->_options= $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';

	}

	public function indexAction()
	{

	}
	public function editAction()
	{
		try {
			//code...

				$employeeModal = new Default_Model_Employee();
				$usersModel = new Default_Model_Users();

					$userID = "";
					$levelsArr = array();
					$empcompetencyLevelsArr = array();
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
					$Uid = ($id) ? $id : $userID;
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
									$empskillsModel = new Assets_Model_Assetdetails();
									$view = Zend_Layout::getMvcInstance()->getView();
									$objname = $this->_getParam('objname');
									$refresh = $this->_getParam('refresh');
									$dashboardcall = $this->_getParam('dashboardcall', null);
									$data = array();
									$searchQuery = '';
									$searchArray = array();
									$tablecontent = '';
									if ($refresh == 'refresh') {
										if ($dashboardcall == 'Yes')
											$perPage = DASHBOARD_PERPAGE;
										else
											$perPage = PERPAGE;

										$sort = 'DESC';
										$by = 'ea.modifieddate';
										$pageNo = 1;
										$searchData = '';
										$searchQuery = '';
										$searchArray = '';
									} else {
										$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
										$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ea.modifieddate';
										if ($dashboardcall == 'Yes')
											$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
										else
											$perPage = $this->_getParam('per_page', PERPAGE);

										$pageNo = $this->_getParam('page', 1);
										$searchData = $this->_getParam('searchData');
										$searchData = rtrim($searchData, ',');
									}
									$dataTmp = $empskillsModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'edit');

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
	public function viewAction()
	{
		try {
			//code...

				$employeeModal = new Default_Model_Employee();
				$usersModel = new Default_Model_Users();

					$userID = "";
					$levelsArr = array();
					$empcompetencyLevelsArr = array();
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
					$Uid = ($id) ? $id : $userID;
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
									$empskillsModel = new Assets_Model_Assetdetails();
									$view = Zend_Layout::getMvcInstance()->getView();
									$objname = $this->_getParam('objname');
									$refresh = $this->_getParam('refresh');
									$dashboardcall = $this->_getParam('dashboardcall', null);
									$data = array();
									$searchQuery = '';
									$searchArray = array();
									$tablecontent = '';
									if ($refresh == 'refresh') {
										if ($dashboardcall == 'Yes')
											$perPage = DASHBOARD_PERPAGE;
										else
											$perPage = PERPAGE;

										$sort = 'DESC';
										$by = 'ea.modifieddate';
										$pageNo = 1;
										$searchData = '';
										$searchQuery = '';
										$searchArray = '';
									} else {
										$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
										$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ea.modifieddate';
										if ($dashboardcall == 'Yes')
											$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
										else
											$perPage = $this->_getParam('per_page', PERPAGE);

										$pageNo = $this->_getParam('page', 1);
										$searchData = $this->_getParam('searchData');
										$searchData = rtrim($searchData, ',');
									}
									$dataTmp = $empskillsModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, '');

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
			$assetpermission = '';
			$assetpermission = sapp_Global::_checkprivileges(SUBASSET,$loginuserGroup,$loginuserRole,'add');
			$this->view->assetpermission = $assetpermission;
			

			$id = $this->getRequest()->getParam('unitId');
			if ($id == '')
				$id = $loginUserId;
			// For open the form in popup...

			$emptyFlag = 0;
			$empskillsform = new Assets_Form_Assetdetails();
			
			$msgarray = array();
		
	



			$empskillsform->setAttrib('action', BASE_URL . 'assetdetails/addpopup/unitId/' . $id);
			$this->view->form = $empskillsform;
			$this->view->msgarray = $msgarray;
			$this->view->emptyFlag = $emptyFlag;
			$this->view->controllername = 'assetdetails';
			if ($this->getRequest()->getPost()) {
				$result = $this->save($empskillsform, $id);
				$this->view->msgarray = $result;
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function editpopupAction()
	{
		try {
			$util=new utlis();
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$assetpermission = '';
			$assetpermission = sapp_Global::_checkprivileges(SUBASSET,$loginuserGroup,$loginuserRole,'add');
			$this->view->assetpermission = $assetpermission;
			
			$id = $this->_getParam('id');
			$unitid = $this->_getParam('unitId');
			if ($id == '')
				$id = $loginUserId;
			// For open the form in popup...

			$emptyFlag = 0;
			$empskillsform = new Assets_Form_Assetdetails();
			
			$msgarray = array();
			$skillModel = new Assets_Model_Assetdetails();

	
			if ($id) {

				$data = $skillModel->getsingleassetData($id);
				if (!empty($data)) {
					$instant=$data[0]['assetinstant_id'];
					$empskillsform->populate($data[0]);
					// $empskillsform->setDefault('competencylevelid',$data[0]['competencylevelid']);
					$issueddate1 = sapp_Global::change_date($data[0]['issueddate'], 'view');
					$empskillsform->issueddate->setValue($issueddate1);
					$returndate1 = sapp_Global::change_date($data[0]['returndate'], 'view');
					$empskillsform->returndate->setValue($returndate1);
					$empskillsform->isactive->setValue($util::getcheckboxvalue($data[0]['isactive'], 'form'));
				}
			}
			$empskillsform->setAction(BASE_URL . 'assetdetails/editpopup/id/' . $id . '/unitId/' . $unitid . '/popup/1 ');

			// $empskillsform->setAttrib('action',BASE_URL.'empskills/editpopup/unitId/'.$id);
			$this->view->form = $empskillsform;
			$this->view->instant = $instant;

			$this->view->msgarray = $msgarray;
			$this->view->controllername = 'assetdetails';
			if ($this->getRequest()->getPost()) {
				$result = $this->save($empskillsform);
				$this->view->msgarray = $result;
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
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
		
			$empskillsModal = new Assets_Model_Assetdetails();
			$form = new Assets_Form_Assetdetails();

			$date = new Zend_Date();
			$actionflag = '';
			$tableid  = '';
			// 	 if($this->_request->getParam('isinstant')=='no'){
			//  	// $form->assetinstant_id->setRequired(false);
			// $form->removeElement('assetinstant_id');
			//  	}

			if ($TandCDetailsform->isValid($this->_request->getPost())) {
				$util = new utlis();
			$id = $this->getRequest()->getParam('id');	

			$asset_id = $this->_request->getParam('asset_id');
			$assetinstant_id = $this->_request->getParam('assetinstant_id');
			$quantity = $this->_request->getParam('quantity');
			$uom_id = $this->_request->getParam('uomid');
			$description = $this->_request->getParam('description');
			$issuedDate =sapp_Global::change_date($this->_request->getParam('issueddate',null), 'database');;
			$returndate =sapp_Global::change_date($this->_request->getParam('returndate',null), 'database');;



				try {

						$data = array('asset_id'=>$asset_id,
														'description'=>$description,
														'assetinstant_id'=>$assetinstant_id,
														'uom_id'=>$uom_id,
														'issueddate'=>$issuedDate,
														'returndate'=>$returndate,
														'quantity'=>$quantity,
														'emp_id' => $unitid,
														'modifiedby'=>$loginUserId,
									                    'modifieddate'=>gmdate("Y-m-d H:i:s")
									);
						if ($id != '') {

							$where = 'assetassignment_id= ' . $id;
							$data['isactive'] = $util::getcheckboxvalue($this->_getParam('isactive'));

							$actionflag = 2;
						} else {
							$dataasset=$empskillsModal->getsingleassetDatabyasset($asset_id);
							if(!empty($dataasset)){
								if($dataasset[0]['returndate']>= gmdate('Y-m-d') or $dataasset[0]['returndate']=='')	{
									$msgarray['asset_id']="Asset are in use ";
									return $msgarray;
							
								}
								
							}
							$data['createdby'] = $loginUserId;
							$data['createddate'] = gmdate("Y-m-d H:i:s");
							$data['isactive'] = $util::getcheckboxvalue($this->_getParam('isactive'));
							$where = '';
							$actionflag = 1;
						}
// die(json_encode($data));
						$Id = $empskillsModal->SaveorUpdate($data, $where);
					} catch (Exception $e) {
						Zend_Debug::dump($e->getMessage());
					}
					if ($Id == 'update') {
						$tableid = $id;
								$this->view->successmessage = 'Employee Asset details updated successfully.';
					} else {
						$tableid = $Id;
		$this->view->successmessage = 'Employee Asset details added successfully.';
}
					$menuID = EMPLOYEE;
					$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $unitid);
					$this->view->controllername = 'assetdetails';
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
		$objName = 'assetdetails';
		$empskillsform = new Assets_Form_Assetdetails();
		$empskillsModel = new Assets_Model_Assetdetails();
		$empskillsform->removeElement("submit");
		$elements = $empskillsform->getElements();

		if (count($elements) > 0) {
			foreach ($elements as $key => $element) {
				if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
					$element->setAttrib("disabled", "disabled");
				}
			}
		}
		$data = $empskillsModel->assetdetailview($id);
		$this->view->controllername = $objName;
		$this->view->id = $id;
		$this->view->data = $data[0];
		$this->view->form = $empskillsform;
	}
}