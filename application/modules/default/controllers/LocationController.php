<?php
session_start();
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

class Default_LocationController extends Zend_Controller_Action
{
	private $_options;
	public function preDispatch()
	{
		$auth = Zend_Auth::getInstance();
		$session = sapp_Global::_readSession();
		if (!isset($session)) {
			if ($this->getRequest()->isXmlHttpRequest()) {
				echo Zend_Json::encode(array('login' => 'failed'));
				die();
			} else {
				$this->_redirect('');
			}
		}
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('chkcandidate', 'json')->initContext();
		$ajaxContext->addActionContext('getvendors', 'json')->initContext();
	}

	public function init()
	{
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';

	}

	public function indexAction()
	{

		$model = new Default_Model_Location();

		$call = $this->_getParam('call');
		if ($call == 'ajaxcall')
			$this->_helper->layout->disableLayout();

		$view = Zend_Layout::getMvcInstance()->getView();
		$objname = $this->_getParam('objname');
		$refresh = $this->_getParam('refresh');
		$dashboardcall = $this->_getParam('dashboardcall');

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
			$by = 'location_id';
			$pageNo = 1;
			$searchData = '';
			$searchQuery = '';
			$searchArray = '';
		} else {
			$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
			$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'l.location_id';
			if ($dashboardcall == 'Yes')
				$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
			else
				$perPage = $this->_getParam('per_page', PERPAGE);
			$pageNo = $this->_getParam('page', 1);
			/** search from grid - START **/
			$searchData = $this->_getParam('searchData');
			$searchData = rtrim($searchData, ',');
			/** search from grid - END **/
		}
		$dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);

		array_push($data, $dataTmp);
		$this->view->dataArray = $data;
		$this->view->call = $call;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}
	public function mapAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
	}
	public function addAction()
	{
		try {
			//code...
		
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
			$countryaddpermission = sapp_Global::_checkprivileges(COUNTRIES,$loginuserGroup,$loginuserRole,'add');
			$stateaddpermission = sapp_Global::_checkprivileges(STATES,$loginuserGroup,$loginuserRole,'add');
			$citypermission = sapp_Global::_checkprivileges(CITIES,$loginuserGroup,$loginuserRole,'add');
			$this->view->countryaddpermission = $countryaddpermission;
			$this->view->stateaddpermission = $stateaddpermission;
			$this->view->cityaddpermission = $citypermission;
		$this->_setParam('selected_option','fill-up-form');
		$msgarray = array();
		$controllername = 'location';
		$form = new Default_Form_location();
		$model = new Default_Model_Location();
		$form->setAction(BASE_URL . 'location/add');
		$countriesModel = new Default_Model_Countries();
		$countrieslistArr = $countriesModel->getTotalCountriesList();
		if (sizeof($countrieslistArr) > 0) {
			$form->country->addMultiOption('', 'Select Country');
			foreach ($countrieslistArr as $countrieslistres) {
				$form->country->addMultiOption($countrieslistres['id'], utf8_encode($countrieslistres['country_name']));
			}
		} else {
			$msgarray['country'] = 'Countries are not configured yet.';
		}
		if (isset($_POST['country']) && $_POST['country'] != '') {
			$statesmodel = new Default_Model_States();
			$statesmodeldata = $statesmodel->getStatesList(intval($_POST['country']));
			$st_opt = array();
			if (count($statesmodeldata) > 0) {
				foreach ($statesmodeldata as $dstate) {
					$st_opt[$dstate['id'] . '!@#' . $dstate['state_name']] = $dstate['state_name'];
				}
			}
			$form->state->addMultiOptions(array('' => 'Select State') + $st_opt);
		}
		if (isset($_POST['state']) && $_POST['state'] != '') {
			$citiesmodel = new Default_Model_Cities();
			$citiesmodeldata = $citiesmodel->getCitiesList(intval($_POST['state']));
			$ct_opt = array();
			if (count($citiesmodeldata) > 0) {
				foreach ($citiesmodeldata as $dcity) {
					$ct_opt[$dcity['id'] . '!@#' . $dcity['city_name']] = $dcity['city_name'];
				}
			}
			$form->city->addMultiOptions(array('' => 'Select City') + $ct_opt);
		}

		if ($this->getRequest()->getPost()) {
			if ($form->isValid($this->_request->getPost())) {
				$address1 = $this->_request->getParam('address1');
				$address2 = $this->_request->getParam('address2');
				$address3 = $this->_request->getParam('address3');
				$address4 = $this->_request->getParam('address4');
				$postal = $this->_request->getParam('postal');
				$country = $this->_request->getParam('country');
				$state = $this->_request->getParam('state');
				$city = $this->_request->getParam('city');
				$map_coordinate = $this->_request->getParam('map_coordinate');



				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = array(
					'address1' => $address1,
					'address2' => $address2,
					'address3' => $address3,
					'address4' => $address4,
					'country_id' => $country,
					'region_id' => $state,
					'city_id' => $city,
					'map_coordinate' => $map_coordinate

				);

				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$where = '';
				$actionflag = 1;
				//die(json_encode($data));
				$Id = $model->SaveorUpdatelocationData($data, $where);
				$menuID = LOCATION;
                
				
				$tableid = $Id;
				sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Location  added successfully."));


				$this->_redirect('location');
			} else {
				$messages = $form->getMessages();
				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
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
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
	}
	}
	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$form = new Default_Form_location();
		$form->setAttrib('action', BASE_URL . 'location/addpopup');
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}

		$msgarray = array();
		$controllername = 'location';
		$model = new Default_Model_Location();
		$countriesModel = new Default_Model_Countries();
		$countrieslistArr = $countriesModel->getTotalCountriesList();
		if (sizeof($countrieslistArr) > 0) {
			$form->country->addMultiOption('', 'Select Country');
			foreach ($countrieslistArr as $countrieslistres) {
				$form->country->addMultiOption($countrieslistres['id'], utf8_encode($countrieslistres['country_name']));
			}
		} else {
			$msgarray['country'] = 'Countries are not configured yet.';
		}
		if (isset($_POST['country']) && $_POST['country'] != '') {
			$statesmodel = new Default_Model_States();
			$statesmodeldata = $statesmodel->getStatesList(intval($_POST['country']));
			$st_opt = array();
			if (count($statesmodeldata) > 0) {
				foreach ($statesmodeldata as $dstate) {
					$st_opt[$dstate['id'] . '!@#' . $dstate['state_name']] = $dstate['state_name'];
				}
			}
			$form->state->addMultiOptions(array('' => 'Select State') + $st_opt);
		}
		if (isset($_POST['state']) && $_POST['state'] != '') {
			$citiesmodel = new Default_Model_Cities();
			$citiesmodeldata = $citiesmodel->getCitiesList(intval($_POST['state']));
			$ct_opt = array();
			if (count($citiesmodeldata) > 0) {
				foreach ($citiesmodeldata as $dcity) {
					$ct_opt[$dcity['id'] . '!@#' . $dcity['city_name']] = $dcity['city_name'];
				}
			}
			$form->city->addMultiOptions(array('' => 'Select City') + $ct_opt);
		}
		if ($this->getRequest()->getPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {
				$address1 = $this->_request->getParam('address1');
				$address2 = $this->_request->getParam('address2');
				$address3 = $this->_request->getParam('address3');
				$address4 = $this->_request->getParam('address4');
				$postal = $this->_request->getParam('postal');
				$country = $this->_request->getParam('country');
				$state = $this->_request->getParam('state');
				$city = $this->_request->getParam('city');
				$map_coordinate = $this->_request->getParam('map_coordinate');



				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = array(
					'address1' => $address1,
					'address2' => $address2,
					'address3' => $address3,
					'address4' => $address4,
					'country_id' => $country,
					'region_id' => $state,
					'city_id' => $city,
					'map_coordinate' => $map_coordinate

				);

				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$where = '';
				$actionflag = 1;
				//die(json_encode($data));
				$Id = $model->SaveorUpdatelocationData($data, $where);
				session_start();
				$_SESSION["location"] = $Id;
			
				$allopt = "";
				//die("hiiii".$Id);
				$locationData = $model->getLocationsbyid($Id);
				//die(json_encode($locationData));
				$opt1 = $locationData[0]['address1'];
				$opt2 = $locationData[0]['address2'];
				$opt3 = $locationData[0]['address3'];
				$opt4 = $locationData[0]['address4'];
				$allopt = $opt1 . ',' . $opt2 . ',' . $opt3 . ',' . $opt4;
				$this->view->locationData = $allopt;

				$tableid = $Id;$menuID=LOCATION;
				sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
				$this->view->eventact = 'added';
				$close = 'close';
				$this->view->popup = $close;
			}
			$messages = $form->getMessages();
			foreach ($messages as $key1 => $value) {
				foreach ($value as $key2 => $value2) {
					$msgArray[$key1] = $value2;
					break;
				}
			}
		}
		$this->view->form = $form;
		$this->view->msgarray = $msgArray;
		$this->view->controllername = $controllername;
		$this->view->ermsg = '';
	}
	public function addpopupnestedAction()
	{



		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
		$form = new Default_Form_location();
		$location_model = new Default_Model_Location();
		$form->setAttrib('action', BASE_URL . 'location/addpoupupnested');
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$location_id = $this->getRequest()->getParam('id');
		$msgarray = array();
		$controllername = 'location';
		$model = new Default_Model_Location();
		$countriesModel = new Default_Model_Countries();
		$countrieslistArr = $countriesModel->getTotalCountriesList();
		if (sizeof($countrieslistArr) > 0) {
			$form->country->addMultiOption('', 'Select Country');
			foreach ($countrieslistArr as $countrieslistres) {
				$form->country->addMultiOption($countrieslistres['id'], utf8_encode($countrieslistres['country_name']));
			}
		} else {
			$msgarray['country'] = 'Countries are not configured yet.';
		}
		$location_data = [];
		if ($location_id) {

			$location_data = $location_model->getLocationsbyid($location_id);
		
			foreach ($location_data as $data) {
				$form->address1->setValue($data['address1']);
				$form->address2->setValue($data['address2']);
				$form->address3->setValue($data['address3']);
				$form->address4->setValue($data['address4']);
			
				$form->country->setValue($data['country_id']);
				$_POST['country'] = $data['country_id'];

				$form->map_coordinate->setValue($data['map_coordinate']);
			}
			// die("This is edit");

		}

		if (isset($_POST['country']) && $_POST['country'] != '') {

			$statesmodel = new Default_Model_States();
			$statesmodeldata = $statesmodel->getStatesList(intval($_POST['country']));
			$st_opt = array();
			if (count($statesmodeldata) > 0) {
				foreach ($statesmodeldata as $dstate) {
					$st_opt[$dstate['id'] . '!@#' . $dstate['state_name']] = $dstate['state_name'];
				}
			}
			$form->state->addMultiOptions(array('' => 'Select State') + $st_opt);
		}
		if ($location_id) {
			foreach ($location_data as $data) {
				$form->state->setValue($data['region_id']);
				$_POST['state'] = $data['region_id'];
			}
		}
		if (isset($_POST['state']) && $_POST['state'] != '') {
			$citiesmodel = new Default_Model_Cities();
			$citiesmodeldata = $citiesmodel->getCitiesList(intval($_POST['state']));
			$ct_opt = array();
			if (count($citiesmodeldata) > 0) {
				foreach ($citiesmodeldata as $dcity) {
					$ct_opt[$dcity['id'] . '!@#' . $dcity['city_name']] = $dcity['city_name'];
				}
			}
			$form->city->addMultiOptions(array('' => 'Select City') + $ct_opt);
		}
		if ($location_id) {
			foreach ($location_data as $data) {

				$form->city->setValue($data['city_id']);
			}
		}

		if ($this->getRequest()->getPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {
				$address1 = $this->_request->getParam('address1');
				$address2 = $this->_request->getParam('address2');
				$address3 = $this->_request->getParam('address3');
				$address4 = $this->_request->getParam('address4');
				$postal = $this->_request->getParam('postal');
				$country = $this->_request->getParam('country');
				$state = $this->_request->getParam('state');
				$city = $this->_request->getParam('city');
				$map_coordinate = $this->_request->getParam('map_coordinate');



				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = array(
					'address1' => $address1,
					'address2' => $address2,
					'address3' => $address3,
					'address4' => $address4,
					'country_id' => $country,
					'region_id' => $state,
					'city_id' => $city,
					'map_coordinate' => $map_coordinate

				);

				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$where = '';
				$actionflag = 1;
				//die(json_encode($data));
				$Id = $model->SaveorUpdatelocationData($data, $where);
				$_SESSION["location"] = $Id;
				$allopt = "";
				//die("hiiii".$Id);
				$locationData = $model->getLocationsbyid($Id);
				//die(json_encode($locationData));
				$opt1 = $locationData[0]['address1'];
				$opt2 = $locationData[0]['address2'];
				$opt3 = $locationData[0]['address3'];
				$opt4 = $locationData[0]['address4'];
				$allopt = $opt1 . ',' . $opt2 . ',' . $opt3 . ',' . $opt4;
				$this->view->locationData = $allopt;

				$tableid = $Id;
				$menuID=LOCATION;
				sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
				$this->view->eventact = 'added';
				$close = 'close';
				$this->view->popup = $close;
			}
			$messages = $form->getMessages();
			foreach ($messages as $key1 => $value) {
				foreach ($value as $key2 => $value2) {
					$msgArray[$key1] = $value2;
					break;
				}
			}
		}
		$this->view->form = $form;
		$this->view->msgarray = $msgArray;
		$this->view->controllername = $controllername;
		$this->view->ermsg = '';
	}
	public function editAction()
	{
		try {
			//code...
		
$util=new utlis();
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
			$countryaddpermission = sapp_Global::_checkprivileges(COUNTRIES,$loginuserGroup,$loginuserRole,'add');
			$stateaddpermission = sapp_Global::_checkprivileges(STATES,$loginuserGroup,$loginuserRole,'add');
			$citypermission = sapp_Global::_checkprivileges(CITIES,$loginuserGroup,$loginuserRole,'add');
			$this->view->countryaddpermission = $countryaddpermission;
			$this->view->stateaddpermission = $stateaddpermission;
			$this->view->cityaddpermission = $citypermission;
			
		
		$this->_setParam('selected_option','fill-up-form');
		$id = (int)$this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if ($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$form = new Default_Form_location();
		$model = new Default_Model_Location();
		$country_model = new Default_Model_Countries();

		$form->setAttrib('action', BASE_URL + 'location/edit/id/' . $id . '');
		try {

			if ($id) {
				if (is_numeric($id) && $id > 0) {
					$data = $model->getsinglelocationData($id);
					$data[0]['isactive']=utlis::getcheckboxvalue($data[0]['isactive'],'form');


					$countryId = $data[0]['country_id'];
					if (isset($_POST['country'])) {
						$countryId = $_POST['country'];
					}
					$stateId = $data[0]['region_id'];
					if (isset($_POST['state'])) {
						$stateId = $_POST['state'];
					}
					$cityId = $data[0]['city_id'];
					if (isset($_POST['city'])) {
						$cityId = $_POST['city'];
					}
					if ($countryId != '') {
						$statesmodel = new Default_Model_States();
						$statesData = $statesmodel->getStatesList($countryId);
						foreach ($statesData as $res)
							$form->state->addMultiOption($res['id'], utf8_encode($res['state_name']));
						$form->setDefault('country', $countryId);
					}
					if ($stateId != '') {
						$citiesmodel = new Default_Model_Cities();
						$citiesData = $citiesmodel->getCitiesList($stateId);
						foreach ($citiesData as $res)
							$form->city->addMultiOption($res['id'], utf8_encode($res['city_name']));
						$form->setDefault('state', $stateId);
					}

					$form->setDefault('city', $cityId);

					$countrieslistArr = $country_model->getTotalCountriesList();
					if (sizeof($countrieslistArr) > 0) {
						$form->country->addMultiOption('', 'Select Country');
						foreach ($countrieslistArr as $countrieslistres) {
							$form->country->addMultiOption($countrieslistres['id'], utf8_encode($countrieslistres['country_name']));
						}
					} else {
						$msgarray['country'] = 'Countries are not configured yet.';
					}

					if (!empty($data) && $data != "norows") {
						$form->populate($data[0]);
						$form->submit->setLabel('Update');
						$this->view->data = $data;
						$this->view->ermsg = '';
					} else {
						$this->view->ermsg = 'nodata';
					}
				} else {
					$this->view->ermsg = 'nodata';
				}
			} else {
				$this->view->ermsg = '';
			}
		} catch (Exception $e) {
			$this->view->ermsg = 'nodata';
		}

		$this->view->form = $form;
		if ($this->getRequest()->getPost()) {

			$address1 = $this->_request->getParam('address1');
			$address2 = $this->_request->getParam('address2');
			$address3 = $this->_request->getParam('address3');
			$address4 = $this->_request->getParam('address4');
			$postal = $this->_request->getParam('postal');
			$country = $this->_request->getParam('country');
			$state = $this->_request->getParam('state');
			$city = $this->_request->getParam('city');
			$map_coordinate = $this->_request->getParam('map_coordinate');
			$isactive=utlis::getcheckboxvalue($this->_request->getParam('isactive'),'database');

			$description = $this->_request->getParam('description');
			$date = new Zend_Date();
			$actionflag = '';
			$tableid  = '';
			$data = array(
				'address1' => $address1,
				'address2' => $address2,
				'address3' => $address3,
				'address4' => $address4,
			
				'country_id' => $country,
				'region_id' => $state,
				'city_id' => $city,
				'map_coordinate' => $map_coordinate,
				'isactive' => $isactive



			);
			$where = 'location_id=' . $id;

			$data['modifiedby'] = $loginUserId;
			$data['modifieddate'] = gmdate("Y-m-d H:i:s");
			$actionflag = 2;
			$Id = $model->SaveorUpdatelocationData($data, $where);
			if ($Id == 'update') {
				$tableid = $id;
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Location updated successfully."));
			} else {
				$tableid = $Id;
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Location  added successfully."));
			}
			$menuID=LOCATION;
			sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
			$this->_redirect('location');
		} else {
			$messages = $form->getMessages();
			foreach ($messages as $key => $val) {
				foreach ($val as $key2 => $val2) {
					$msgarray[$key] = $val2;
					break;
				}
			}
			$this->view->msgarray = $msgarray;
		}
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
		$deleteflag = $this->_request->getParam('deleteflag');
		$messages['message'] = '';
		$messages['msgtype'] = "";
		$actionflag = 3;
		if ($id) {
			$model = new Default_Model_Location();

			
			$where = array('location_id=?' => $id);
			$Id = $model->DeleteData($where);
			if ($Id == 'delete') {
				$messages['message'] = 'Location deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Location cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Location cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		if ($deleteflag == 1) {
			if ($messages['msgtype'] == 'error') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("error" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
			}
			if ($messages['msgtype'] == 'success') {
				$menuID=LOCATION;
				sapp_Global::logManager($menuID,$actionflag,$loginUserId,$id);
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
			}
		}
		$this->_helper->json($messages);
	} // end of delete action

	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if ($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$objName = 'location';

		$form = new Default_Form_location();

		$form->removeElement("submit");


		$model = new Default_Model_Location();
		try {
			if ($id) {
				if (is_numeric($id) && $id > 0) {

					$data = $model->getsinglelocationviewData($id);

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
			$this->view->controller='location';
		} catch (Exception $e) {
			$this->view->ermsg = 'nodata';
		}
	}
}
