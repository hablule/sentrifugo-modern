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

class Default_EmpskillsController extends Zend_Controller_Action
{

	private $options;
	public function preDispatch()
	{
	}

	public function init()
	{
		$employeeModel = new Default_Model_Employee();
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';
	}
	public function indexAction()
	{
		
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

				$employeeModal = new Default_Model_Employee();
				try {
					if ($Uid && is_numeric($Uid) && $Uid > 0) {
						$usersModel = new Default_Model_Users();
						$empdata = $employeeModal->getActiveEmployeeData($Uid);
						$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
						if ($empdata == 'norows') {
							$this->view->rowexist = "norows";
							$this->view->empdata = "";
						} else {
							$this->view->rowexist = "rows";
							if (!empty($empdata)) {
								$empskillsModel = new Default_Model_Empskills();
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
									$by = 'e.modifieddate';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = '';
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'e.modifieddate';
									if ($dashboardcall == 'Yes')
										$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
									else
										$perPage = $this->_getParam('per_page', PERPAGE);

									$pageNo = $this->_getParam('page', 1);
									$searchData = $this->_getParam('searchData');
									$searchData = rtrim($searchData, ',');
								}
								$dataTmp = $empskillsModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);
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
					$this->view->rowexist = "norows";
				}
			
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
									$empskillsModel = new Default_Model_Empskills();
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
										$by = 'es.modifieddate';
										$pageNo = 1;
										$searchData = '';
										$searchQuery = '';
										$searchArray = '';
									} else {
										$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
										$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'es.modifieddate';
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

	
				$userID = "";
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginUserRole = $auth->getStorage()->read()->emprole;
					$loginUserGroup = $auth->getStorage()->read()->group_id;
				}
				$id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
				$conText = '';
				$call = $this->_getParam('call');
				if ($call == 'ajaxcall') {
					$this->_helper->layout->disableLayout();
					$userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
					$conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
				}
				if ($id == '')		$id = $userID;
				$Uid = ($id) ? $id : $userID;

				$employeeModal = new Default_Model_Employee();
				try {
					if ($Uid && is_numeric($Uid) && $Uid > 0) {
						$usersModel = new Default_Model_Users();
						$empdata = $employeeModal->getActiveEmployeeData($Uid);
						$employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
						if ($empdata == 'norows') {
							$this->view->rowexist = "norows";
							$this->view->empdata = "";
						} else {
							$this->view->rowexist = "rows";
							if (!empty($empdata)) {
								$empaddressmodel = new Default_Model_Empskills();
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
									$by = 'isactive';
									$pageNo = 1;
									$searchData = '';
									$searchQuery = '';
									$searchArray = array();
								} else {
									$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
									$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'isactive';
									if ($dashboardcall == 'Yes')
										$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
									else
										$perPage = $this->_getParam('per_page', PERPAGE);

									$pageNo = $this->_getParam('page', 1);
									$searchData = $this->_getParam('searchData');
									$searchData = rtrim($searchData, ',');
								}
								$dataTmp = $empaddressmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText);

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
						die();
						$this->view->rowexist = "norows";
					}
				} catch (Exception $e) {
					die();
					$this->view->rowexist = "norows";
				}
			
	}
	public function uploadpreviewAction()
	{
		try {
			//code...

			$result = $this->imageupload();

			$this->_helper->json($result);
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function imageupload()
	{
		$savefolder = SKILL_PREVIEW_UPLOAD_PATH;		// folder for upload



		$max_size = 1024;			// maxim size for image file, in KiloBytes

		// Allowed image types
		//$allowtype = array('gif', 'jpg', 'jpeg', 'png');
		$allowtype = array('gif', 'jpg', 'jpeg', 'png', 'GIF', 'JPG', 'JPEG', 'PNG');

		/** Uploading the image **/

		$rezultat = '';
		$result_status = '';
		$result_msg = '';
		// if is received a valid file
		if (isset($_FILES['profile_photo'])) {
			$type = explode(".", strtolower($_FILES['profile_photo']['name']));
			//echo in_array($type, $allowtype);exit;
			if (in_array($type[1], $allowtype)) {
				// check its size
				if ($_FILES['profile_photo']['size'] != 0 && $_FILES['profile_photo']['size'] <= $max_size * 1024) {
					// if no errors

					if ($_FILES['profile_photo']['error'] == 0) {
						//$newname = 'preview_'.date("His").'.'.$type[1];
						$newname = 'attestation_' . time() . '.' . $type[1];
						$thefile = $savefolder . "/" . $_FILES['profile_photo']['name'];
						$newfilename = $savefolder . "/" . $newname;
						$filename = $newname;

						if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $newfilename)) {
							$rezultat = '';
							$result_status = 'error';
							$result_msg = 'The file cannot be uploaded, try again.';
						} else {
							$rezultat = $filename;
							$setWidth = 265;
							$setHeight = 40;
							list($imgwidth, $imgheight) = getimagesize($newfilename);
							if ($imgwidth < 265) {
								$setWidth = $imgwidth;
							}
							if ($imgheight < 40) {
								$setHeight = $imgheight;
							}

							if ($imgwidth > 265 || $imgheight > 40) {
								sapp_Global::smartresizeimage($newfilename, $setWidth, $setHeight, false, 'file', false, false);
							}
							$result_status = 'success';
							$result_msg = '';
						}
					}
				} else {
					$rezultat = '';
					$result_status = 'error';
					$result_msg = 'Image size must not exceed ' . $max_size . ' KB.';
				}
			} else {
				$rezultat = '';
				$result_status = 'error';
				$result_msg = 'Please upload only .gif, .jpg, .jpeg, .png images.';
			}
		} else {
			$rezultat = '';
			$result_status = 'error';
			$result_msg = 'Please upload only .gif, .jpg, .jpeg, .png images.';
		}

		$result = array(
			'result' => $result_status,
			'img' => $rezultat,
			'msg' => $result_msg
		);
		return $result;
	}
	public function addpopupAction()
	{
		try {
			//code...
			$auth = Zend_Auth::getInstance();
			if($auth->hasIdentity())
			{
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$skillsourceaddpermission = sapp_Global::_checkprivileges(SKILLSOURCE,$loginuserGroup,$loginuserRole,'add');
			$measurmentindexaddpermission = sapp_Global::_checkprivileges(MEASURMENTINDEX,$loginuserGroup,$loginuserRole,'add');
			$skilladdpermission = sapp_Global::_checkprivileges(SKILL,$loginuserGroup,$loginuserRole,'add');
			$this->view->skillsourceaddpermission = $skillsourceaddpermission;
			$this->view->measurmentaddpermission = $measurmentindexaddpermission;
			$this->view->skilladdpermission = $skilladdpermission;
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$id = $this->getRequest()->getParam('unitId');
			if ($id == '')
				$id = $loginUserId;
			// For open the form in popup...

			$emptyFlag = 0;
			$empskillsform = new Default_Form_empskills();
			$skillModel = new Default_Model_skill();
			$skillsourceModel = new Default_Model_skillsource();
			$measurementindexModel = new Default_Model_measurementindex();
			//---------------
			$skilllevelArr = $skillModel->getskilllistnonsummary();
			$msgarray = array();
			$empskillsform->skill_id->addMultiOption('', 'Select Skill');
			if (!empty($skilllevelArr)) {

				foreach ($skilllevelArr as $skilllevelres) {
					$empskillsform->skill_id->addMultiOption($skilllevelres['skill_id'], $skilllevelres['name']);
				}
			} else {
				$msgarray['skill_id'] = 'Skill  are not configured yet.';
				$emptyFlag++;
			}


			//-------------------------------------//
			//---------------
			$skilllsourcelevelArr = $skillsourceModel->getskillsourcelist();
			$msgarray = array();
			$empskillsform->skillsource_id->addMultiOption('', 'Select Skill source');
			if (!empty($skilllsourcelevelArr)) {

				foreach ($skilllsourcelevelArr as $skilllsourcelevelres) {
					$empskillsform->skillsource_id->addMultiOption($skilllsourcelevelres['skillsource_id'], $skilllsourcelevelres['name']);
				}
			} else {
				$msgarray['skillsource_id'] = 'Skill source  are not configured yet.';
				$emptyFlag++;
			}


			//-------------------------------------//
			//---------------
			$measurementlevelArr = $measurementindexModel->getmeasurementlist();
			$msgarray = array();
			$empskillsform->measurementindex_id->addMultiOption('', 'Select measurement Index');
			if (!empty($measurementlevelArr)) {

				foreach ($measurementlevelArr as $measurementlevelres) {
					$empskillsform->measurementindex_id->addMultiOption($measurementlevelres['measurementindex_id'], $measurementlevelres['name']);
				}
			} else {
				$msgarray['measurementindex_id'] = 'Measurment Index are not configured yet.';
				$emptyFlag++;
			}


			//-------------------------------------//
			$empskillsform->setAttrib('action', BASE_URL . 'empskills/addpopup/unitId/' . $id);
			$this->view->form = $empskillsform;
			$this->view->msgarray = $msgarray;
			$this->view->emptyFlag = $emptyFlag;
			$this->view->controllername = 'empskills';
			if ($this->getRequest()->getPost()) {
				$result = $this->save($empskillsform, $id);
				$this->view->msgarray = $result;
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
		$objName = 'empskills';
		$empskillsform = new Default_Form_empskills();
		$empskillsModel = new Default_Model_Empskills();
		$empskillsform->removeElement("submit");
		$elements = $empskillsform->getElements();

		if (count($elements) > 0) {
			foreach ($elements as $key => $element) {
				if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
					$element->setAttrib("disabled", "disabled");
				}
			}
		}
		$data = $empskillsModel->empskilldetailview($id);
		$this->view->controllername = $objName;
		$this->view->id = $id;
		$this->view->data = $data[0];
		$this->view->form = $empskillsform;
	}
	public function editpopupAction()
	{
		try {
			//code...
			$util = new utlis();
			$auth = Zend_Auth::getInstance();
			if($auth->hasIdentity())
			{
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$skillsourceaddpermission = sapp_Global::_checkprivileges(SKILLSOURCE,$loginuserGroup,$loginuserRole,'add');
			$measurmentindexaddpermission = sapp_Global::_checkprivileges(MEASURMENTINDEX,$loginuserGroup,$loginuserRole,'add');
			$skilladdpermission = sapp_Global::_checkprivileges(SKILL,$loginuserGroup,$loginuserRole,'add');
			$this->view->skillsourceaddpermission = $skillsourceaddpermission;
			$this->view->measurmentaddpermission = $measurmentindexaddpermission;
			$this->view->skilladdpermission = $skilladdpermission;
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
				$loginuserRole = $auth->getStorage()->read()->emprole;
				$loginuserGroup = $auth->getStorage()->read()->group_id;
			}
			$id = $this->_getParam('id');
			$unitid = $this->_getParam('unitId');

			// For open the form in popup...

			$emptyFlag = 0;
			$empskillsform = new Default_Form_empskills();
			$skillModel = new Default_Model_Empskills();
			$skillModelb = new Default_Model_skill();

			$skillsourceModel = new Default_Model_skillsource();
			$measurementindexModel = new Default_Model_measurementindex();
			//---------------
			$skilllevelArr = $skillModelb->getskilllistnonsummary();
			$msgarray = array();
			$empskillsform->skill_id->addMultiOption('', 'Select Skill');
			if (!empty($skilllevelArr)) {

				foreach ($skilllevelArr as $skilllevelres) {
					$empskillsform->skill_id->addMultiOption($skilllevelres['skill_id'], $skilllevelres['name']);
				}
			} else {
				$msgarray['skill_id'] = 'Skill  are not configured yet.';
				$emptyFlag++;
			}


			//-------------------------------------//
			//---------------
			$skilllsourcelevelArr = $skillsourceModel->getskillsourcelist();
			$msgarray = array();
			$empskillsform->skillsource_id->addMultiOption('', 'Select Skill source');
			if (!empty($skilllsourcelevelArr)) {

				foreach ($skilllsourcelevelArr as $skilllsourcelevelres) {
					$empskillsform->skillsource_id->addMultiOption($skilllsourcelevelres['skillsource_id'], $skilllsourcelevelres['name']);
				}
			} else {
				$msgarray['skillsource_id'] = 'Skill source  are not configured yet.';
				$emptyFlag++;
			}


			//-------------------------------------//
			//---------------
			$measurementlevelArr = $measurementindexModel->getmeasurementlist();
			$msgarray = array();
			$empskillsform->measurementindex_id->addMultiOption('', 'Select measurement Index');
			if (!empty($measurementlevelArr)) {

				foreach ($measurementlevelArr as $measurementlevelres) {
					$empskillsform->measurementindex_id->addMultiOption($measurementlevelres['measurementindex_id'], $measurementlevelres['name']);
				}
			} else {
				$msgarray['measurementindex_id'] = 'Measurment Index are not configured yet.';
				$emptyFlag++;
			}


			//-------------------------------------//
			if ($id) {

				$data = $skillModel->getsingleEmpSkillsData($id);
				if (!empty($data)) {
					$empskillsform->populate($data[0]);
					// $empskillsform->setDefault('competencylevelid',$data[0]['competencylevelid']);
					$acquiredsince1 = sapp_Global::change_date($data[0]['acquiredsince'], 'view');
					$empskillsform->acquiredsince->setValue($acquiredsince1);
					$empskillsform->isactive->setValue($util::getcheckboxvalue($data[0]['isactive'], 'form'));
					$this->view->org_image = $data[0]['attestation'];
				}
			}
			$empskillsform->setAction(BASE_URL . 'empskills/editpopup/id/' . $id . '/unitId/' . $unitid . '/popup/1 ');

			// $empskillsform->setAttrib('action',BASE_URL.'empskills/editpopup/unitId/'.$id);
			$this->view->form = $empskillsform;
			$this->view->msgarray = $msgarray;
			$this->view->emptyFlag = $emptyFlag;
			$this->view->controllername = 'empskills';
			if ($this->getRequest()->getPost()) {
				$result = $this->save($empskillsform);
				$this->view->msgarray = $result;
			}
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	// public function editpopupAction()
	// {
	// 	Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
	// 	$auth = Zend_Auth::getInstance();
	// 	if($auth->hasIdentity())
	// 	{
	// 		$loginUserId = $auth->getStorage()->read()->id;
	// 	}
	// 	$id = $this->getRequest()->getParam('id');
	// 	$userid = $this->getRequest()->getParam('unitId');
	// 	if($id == '')
	// 	$id = $loginUserId;
	// 	// For open the form in popup...

	// 	$empskillsform = new Default_Form_empskills();
	// 	$empskillsModel = new Default_Model_Empskills();
	// 	$competencylevelModel = new Default_Model_Competencylevel();
	// 	$competencylevelArr = $competencylevelModel->getCompetencylevelList();

	// 	if(sizeof($competencylevelArr)>0)
	// 	{
	// 		$empskillsform->competencylevelid->addMultiOption('','Select Competency Level');
	// 		foreach ($competencylevelArr as $competencylevelres){
	// 			$empskillsform->competencylevelid->addMultiOption($competencylevelres['id'],$competencylevelres['competencylevel']);

	// 		}
	// 	}
	// 	if($id)
	// 	{
	// 		$data = $empskillsModel->getsingleEmpSkillsData($id);
	// 		if(!empty($data))
	// 		{
	// 			$empskillsform->populate($data[0]);
	// 			$empskillsform->setDefault('competencylevelid',$data[0]['competencylevelid']);
	// 			$year_skill_last_used = sapp_Global::change_date($data[0]['year_skill_last_used'], 'view');
	// 			$empskillsform->year_skill_last_used->setValue($year_skill_last_used);
	// 		}
	// 	}
	// 	$empskillsform->setAttrib('action',BASE_URL.'empskills/editpopup/unitId/'.$userid);
	// 	$this->view->form = $empskillsform;
	// 	$this->view->controllername = 'empskills';

	// 	if($this->getRequest()->getPost())
	// 	{	
	// 		$result = $this->save($empskillsform,$userid);
	// 		$this->view->msgarray = $result;
	// 	}

	// }


	public function save($empskillsform)
	{
		try {
			//code...
			$id = $this->_getParam('id');
			$unitid = $this->_getParam('unitId');

			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
			}
			$imagerror = $this->_request->getParam('imgerr');
			$imagepath = $this->_request->getParam('attestation_image_value');
			$imgerrmsg = $this->_request->getParam('imgerrmsg');

			$flag = 'true';
			if (isset($imagepath) && $imagepath != '') {

				$imageArr = explode('.', $imagepath);
				if (sizeof($imageArr) > 1) {
					$imagename = $imageArr[0];
					$imageext = $imageArr[1];
					$extArr = array('gif', 'jpg', 'jpeg', 'png');
					if (!in_array($imageext, $extArr)) {
						$msgarray['attestation_image_value'] = 'Please upload an appropriate image file.';
						$flag = 'false';
					}
				} else {
					$msgarray['attestation_image_value'] = 'Please upload an appropriate image file.';
					$flag = 'false';
				}
			}
			if ($imagerror == 'error') {
				if ($imgerrmsg != '' && $imgerrmsg != 'undefined')
					$msgarray['attestation_image_value'] = $imgerrmsg;
				else
					$msgarray['attestation_image_value'] = 'Please upload an appropriate image file.';
				$flag = 'false';
			}
			$empskillsModal = new Default_Model_Empskills();



			$date = new Zend_Date();
			$actionflag = '';
			$tableid  = '';



			if ($empskillsform->isValid($this->_request->getPost()) && $flag != 'false') {
				$util = new utlis();
				$skillname = $this->_request->getParam('skillname');
				$skill_id = $this->_request->getParam('skill_id');
				$skillsource_id = $this->_request->getParam('skillsource_id');
				$measurementindex_id = $this->_request->getParam('measurementindex_id');
				$measurementvalue = $this->_request->getParam('measurementvalue');
				$description = $this->_request->getParam('description');

				$acquiredsince1 = $this->_request->getParam('acquiredsince');
				$acquiredsince = sapp_Global::change_date($acquiredsince1, 'database');
				if (($skill_id  != '' &&  $skillname  != '') or (($skill_id  == '' &&  $skillname  == ''))) {
					$msgarray['general'] = 'Please fill either skill or skill name';

					return $msgarray;
				} else if (($measurementindex_id  != '' &&  $measurementvalue  != '') or (($measurementindex_id  == '' &&  $measurementvalue  == ''))) {
					$msgarray['general'] = 'Please fill either Matrix index or  Matrix Value';

					return $msgarray;
				} else {
					try {

						$db = Zend_Db_Table::getDefaultAdapter();
						$path = IMAGE_UPLOAD_PATH_SKILL;
						$imagepath = $this->_request->getParam('attestation_image_value');
						$filecopy = 'success';
						if ($imagepath != '') {
							$filecopy = 'error';
							if (file_exists(SKILL_PREVIEW_UPLOAD_PATH . '//' . $imagepath)) {
								try {
									if (copy(SKILL_PREVIEW_UPLOAD_PATH . '//' . $imagepath, $path . '//' . $imagepath))
										$filecopy = 'success';
									unlink(SKILL_PREVIEW_UPLOAD_PATH . '//' . $imagepath);
								} catch (Exception $e) {
									echo $msgarray['attestation_image_value'] = $e->getFile();
									exit;
								}
							}
						}
						$data = array(
							'skillname' => $skillname,
							'skill_id' => $skill_id,
							'skillsource_id' => $skillsource_id,
							'measurementindex_id' => $measurementindex_id,
							'measurementvalue' => $measurementvalue,
							'acquiredsince' => $acquiredsince,
							'modifiedby' => $loginUserId,
							'modifieddate' => gmdate("Y-m-d H:i:s"),
							'emp_id' => $unitid,
							'attestation' => $imagepath,
							'description' => $description,
						);
						if ($id != '') {

							$where = 'empskill_id= ' . $id;
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

						$Id = $empskillsModal->SaveorUpdateEmpSkills($data, $where);
					} catch (Exception $e) {
						$code = $e->getCode();
						if ($code = 2300) {
							$msgarray['skill_id'] = 'Skill already exist ';
							return $msgarray;
						}
					}
					if ($Id == 'update') {
						$tableid = $id;
						$this->view->successmessage = 'Employee skills updated successfully.';
					} else {
						$tableid = $Id;
						$this->view->successmessage = 'Employee skills added successfully.';
					}
					$menuID = EMPLOYEE;
					$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $unitid);
					$this->view->controllername = 'empskills';
					Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
				}
			} else {
				$messages = $empskillsform->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgarray[$key] = $val2;
						break;
					}
				}
				return $msgarray;
			}

			return $msgarray;
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}

	public function deleteAction()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->_request->getParam('objid');
		$messages['message'] = '';
		$messages['msgtype'] = '';
		$messages['flagtype'] = '';
		$actionflag = 3;
		if ($id) {
			$empskillsModal = new Default_Model_Empskills();
			$data = array('isactive' => 0, 'modifieddate' => gmdate("Y-m-d H:i:s"));
			$where = array('id=?' => $id);
			$Id = $empskillsModal->SaveorUpdateEmpSkills($data, $where);
			if ($Id == 'update') {
				$menuID = EMPLOYEE;
				$result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $id);
				$messages['message'] = 'Employee skills deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Employee skills cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Employee skills cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		$this->_helper->json($messages);
	}
}
