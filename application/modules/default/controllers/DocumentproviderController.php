<?php

class Default_DocumentproviderController extends Zend_Controller_Action
{
    public function init()
    {
        require_once 'application/modules/default/library/utilClasses/Util.php';

        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
      
      $dmtmodel = new Default_Model_Documentprovider();
      $call = $this->_getParam('call');

      if ($call == 'ajaxcall') {
          $this->_helper->layout->disableLayout();
      }

      $refresh = $this->_getParam('refresh');
      $dashboradcall = $this->_getParam('dashboradcall');

      $data = [];

      if ($refresh == 'refresh') {
          if ($dashboradcall == 'Yes') {
              $perPage = DASHBOARD_PERPAGE;
          }
          else{
              $perPage = PERPAGE;
          }
          $sort = 'DESC';
          $by = 'l.name';
          $pageNo = 1;
          $searchData = '';
      }
      else{
          $sort = ($this->_getParam('sort')!='') ? $this->_getParam('sort'):'DESC';
          $by = ($this->_getParam('by')!='') ? $this->_getParam('by'):'l.name';
          if($dashboradcall == 'Yes')
          $perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
          else
          $perPage = $this->_getParam('per_page',PERPAGE);

          $pageNo = $this->_getParam('page',1);
          $searchData = $this->_getParam('searchData');
          $searchData = rtrim($searchData,',');
      }
      $dataTmp = $dmtmodel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboradcall);

      array_push($data,$dataTmp);
      $this->view->dataArray = $data;
      $this->view->call = $call;
      $this->view->messages = $this->_helper->flashMessenger->getMessages();
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }
  

    public function addpopupAction()
    {
        $this->_setParam('selected_option','fill-up-form');

try {
    //code...

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $form = new Default_Form_Documentprovider();
        $form->setAttrib('action', BASE_URL . 'documentprovider/addpopup');
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
            $st_opt = [];
            if (count($statesmodeldata) > 0) {
                foreach ($statesmodeldata as $dstate) {
                    $st_opt[$dstate['id'] . '!@#' . $dstate['state_name']] = $dstate['state_name'];
                }
            }
            $form->state->addMultiOptions(['' => 'Select State'] + $st_opt);
        }
        if (isset($_POST['state']) && $_POST['state'] != '') {
            $citiesmodel = new Default_Model_Cities();
            $citiesmodeldata = $citiesmodel->getCitiesList(intval($_POST['state']));
            $ct_opt = [];
            if (count($citiesmodeldata) > 0) {
                foreach ($citiesmodeldata as $dcity) {
                    $ct_opt[$dcity['id'] . '!@#' . $dcity['city_name']] = $dcity['city_name'];
                }
            }
            $form->city->addMultiOptions(['' => 'Select City'] + $ct_opt);
        }
        if ($this->getRequest()->getPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $model = new Default_Model_Documentprovider();

              
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'country_id'=>$this->_request->getParam('country'),
                    'state_id'=>$this->_request->getParam('state'),
                    'city_id'=>$this->_request->getParam('city'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];

                $tableid=$model->SaveorUpdateData($data, '');
                sapp_Global::logManager(350,1,$loginUserId,$tableid);
                $currencyData = $model->fetchAll('isactive = "Y"', 'name')->toArray();
                $opt = '';
                foreach ($currencyData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['iddocumentprovider_id'], $record['name']);
                }
                $this->view->provider = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->view->controllername = "documentprovider";
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
    } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());    }
    }
    public function editAction(){
		try {
            //code...
            $this->_setParam('selected_option','fill-up-form');

            $auth = Zend_Auth::getInstance();

            if($auth->hasIdentity()){
                $loginUserId = $auth->getStorage()->read()->id;
                $loginuserRole = $auth->getStorage()->read()->emprole;
                $loginuserGroup = $auth->getStorage()->read()->group_id;            }
       
		$objName = 'documentprovider';
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();

		$Form = new Default_Form_Documentprovider();
		$Model = new Default_Model_Documentprovider();
        $countriesModel = new Default_Model_Countries();
		$countrieslistArr = $countriesModel->getTotalCountriesList();
		if (sizeof($countrieslistArr) > 0) {
			$Form->country->addMultiOption('', 'Select Country');
			foreach ($countrieslistArr as $countrieslistres) {
				$Form->country->addMultiOption($countrieslistres['id'], utf8_encode($countrieslistres['country_name']));
			}
		} else {
			$msgarray['country'] = 'Countries are not configured yet.';
		}
		if (isset($_POST['country']) && $_POST['country'] != '') {
			$statesmodel = new Default_Model_States();
			$statesmodeldata = $statesmodel->getStatesList(intval($_POST['country']));
			$st_opt = [];
			if (count($statesmodeldata) > 0) {
				foreach ($statesmodeldata as $dstate) {
					$st_opt[$dstate['id'] . '!@#' . $dstate['state_name']] = $dstate['state_name'];
				}
			}
			$Form->state->addMultiOptions(['' => 'Select State'] + $st_opt);
		}
		if (isset($_POST['state']) && $_POST['state'] != '') {
			$citiesmodel = new Default_Model_Cities();
			$citiesmodeldata = $citiesmodel->getCitiesList(intval($_POST['state']));
			$ct_opt = [];
			if (count($citiesmodeldata) > 0) {
				foreach ($citiesmodeldata as $dcity) {
					$ct_opt[$dcity['id'] . '!@#' . $dcity['city_name']] = $dcity['city_name'];
				}
			}
			$Form->city->addMultiOptions(['' => 'Select City'] + $ct_opt);
		}
		$country_model = new Default_Model_Countries();

		try{
			
			if($id)
			{	//Edit Record...
				
				if(is_numeric($id) && $id>0)
				{
					$data = $Model->getprovider($id);
			

					if(!empty($data) && $data != "norows")
					{


                        $countryId = $data[0]['country_id'];
                        if (isset($_POST['country'])) {
                            $countryId = $_POST['country'];
                        }
                        $stateId = $data[0]['state_id'];
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
                                $Form->state->addMultiOption($res['id'], utf8_encode($res['state_name']));
                            $Form->setDefault('country', $countryId);
                        }
                        if ($stateId != '') {
                            $citiesmodel = new Default_Model_Cities();
                            $citiesData = $citiesmodel->getCitiesList($stateId);
                            foreach ($citiesData as $res)
                                $Form->city->addMultiOption($res['id'], utf8_encode($res['city_name']));
                            $Form->setDefault('state', $stateId);
                        }
    
                        $Form->setDefault('city', $cityId);
    
                        $countrieslistArr = $country_model->getTotalCountriesList();
                        if (sizeof($countrieslistArr) > 0) {
                            $Form->country->addMultiOption('', 'Select Country');
                            foreach ($countrieslistArr as $countrieslistres) {
                                $Form->country->addMultiOption($countrieslistres['id'], utf8_encode($countrieslistres['country_name']));
                            }
                        } else {
                            $msgarray['country'] = 'Countries are not configured yet.';
                        }
						$Form->populate($data[0]);

						$Form->submit->setLabel('Update');
                        $Form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'],'form'));

						$this->view->form = $Form;
						$this->view->controllername = $objName;
						$this->view->id = $id;
						$this->view->ermsg = '';
						$this->view->inpage = 'Edit';
						
					}
					else
					{
						$this->view->ermsg = 'norecord';
					}
				}
				else
				{
					$this->view->ermsg = 'nodata';
				}
			}
			else
			{	
                //Add Record...
				$this->view->ermsg = '';
				$this->view->form = $Form;
				$this->view->inpage = 'Add';
			}
		}
		catch(Exception $e)
		{
			$this->view->ermsg = 'nodata';
		}
		if($this->getRequest()->getPost()){

    	if($Form->isValid($this->_request->getPost())){
				$id = $this->_request->getParam('id');
                
				$name = $this->_request->getParam('name');
                $code = $this->_request->getParam('code');
				$country = $this->_request->getParam('country');
				$state = $this->_request->getParam('state');
				$city = $this->_request->getParam('city');

                $description = $this->_request->getParam('description');

				$date = gmdate("Y-m-d H:i:s");

				$data = [
							'name'  => $name,
                            'code'  => $code,
                            'country_id'  => $country,
                            'state_id'  => $state,
                            'city_id'  => $city,
                            'description'  => $description,
                            'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),

							];
				if($id!=''){

					$data['modifiedby'] = $loginUserId;
					$data['modifieddate'] = $date;
					$where = ['iddocumentprovider_id=?'=>$id];
				}
				else
				{
					$data['createdby'] = $loginUserId;
					$data['createddate'] = $date;
					$data['isactive'] = 'Y';
					$where = '';
				}

			$Id = $Model->SaveorUpdateData($data, $where);
				
				if($Id == 'update')
				{
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Document Provider updated successfully."]);
				}
				else
				{
			     $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Document Provider added successfully."]);
				}
					
				$this->_redirect('documentprovider');
			
				
					
			}else{
                $messages = $Form->getMessages();
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
Zend_Debug::dump($th->getMessage());    }
	}
 
    // public function viewAction()
    // {
    //     $Linecate_Form = new Default_Form_Linecategory();
    //     $dmtmodel = new Default_Model_Linecategory();
    //     $auth = Zend_Auth::getInstance();
    //     $id = $this->_getParam('id');
    //     $dmtdata=$dmtmodel->getlinecategory($id);
    //     if ($auth->hasIdentity()) {
    //         $loginUserId = $auth->getStorage()->read()->id;
    //     }
    //     $Linecate_Form->populate($dmtdata);
       
     
    //     $Linecate_Form->isactive->setValue($dmtdata['isactive']);
    //     $this->view->form = $Linecate_Form; 
    //     $this->view->isactive = $dmtdata['isactive'];
    //     $this->view->form = $Linecate_Form;
    //     $this->view->id = $id;

    //     $this->view->controllername='linecategory';
    // }
}
