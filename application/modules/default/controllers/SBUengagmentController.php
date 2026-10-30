<?php

class Default_sbuengagmentController extends Zend_Controller_Action
{

	public function preDispatch()
    {
        $auth = Zend_Auth::getInstance();
        $session = sapp_Global::_readSession();
     
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('chkcandidate', 'json')->initContext();
        $ajaxContext->addActionContext('getvendors', 'json')->initContext();
    }


    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
		

        $model = new Default_Model_sbuengagment();
        $call = $this->_getParam('call');
		if($call == 'ajaxcall')
		$this->_helper->layout->disableLayout();

		$view = Zend_Layout::getMvcInstance()->getView();
		$objname = $this->_getParam('objname');
		$refresh = $this->_getParam('refresh');
		$dashboardcall = $this->_getParam('dashboardcall');

		$data = array();
		$searchQuery = '';
		$searchArray = array();
		$tablecontent='';

		if($refresh == 'refresh')
		{
			if($dashboardcall == 'Yes')
			$perPage = DASHBOARD_PERPAGE;
			else
			$perPage = PERPAGE;
			$sort = 'DESC';$by = 'e.modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
		}
		else
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'e.modifieddate';
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
		
			//code...
		
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$msgarray = array();
		$controllername = 'sbuengagment';
		$form = new Default_Form_sbuengagment();
		$model = new Default_Model_sbuengagment();

		$modelbu = new Default_Model_Businessunits();
		$add = $modelbu->getBusinessUnitsList();
		// die(json_encode($add));
		$modelbd = new Default_Model_businessdomain();
		
		$bdd = $modelbd->getbusinessdomaindata();
		$form->removeElement("businessdomain_id2");

		$form->sbunit_id->addMultiOption('', 'Select...');
		$form->businessdomain_id->addMultiOption('', 'Select...');
	

		if (empty($add)) {
			$msgarray['sbunit_id'] = 'Business Unit is not configured yet.';
		} else {
			foreach($add as $adds){
				$form->sbunit_id->addMultiOptions(array($adds['sbunit_id'] => $adds['name']));

			}

		
		}

		if (empty($bdd)) {
			$msgarray['businessdomain_id'] = 'Business Domain is not configured yet.';
		} else {
			foreach($bdd as $bdds){
				$form->businessdomain_id->addMultiOptions(array($bdds['businessdomain_id'] => $bdds['name']));
			}
		}
		// if(isset($_POST['sbunit_id']) && $_POST['sbunit_id']!='')
        // {
		// 	die("ggf");
        //     $citiesmodel = new Default_Model_sbuengagment();
        //     $citiesmodeldata = $citiesmodel->getajaxdata(intval($_POST['sbunit_id']));
        //     $ct_opt = array();
        //     if(count($citiesmodeldata) > 0)
        //     {
        //         foreach($citiesmodeldata as $dcity)
        //         {
        //             $ct_opt[$dcity['businessdomain_id'].'!@#'.$dcity['name']] = $dcity['name'];
        //         }
        //     }
        //     $form->businessdomain_id->addMultiOptions(array(''=>'Select..')+$ct_opt);
        // }
		if ($this->getRequest()->getPost()) {
			if ($form->isValid($this->_request->getPost())) {
				$sbunit_id = $this->_request->getParam('sbunit_id');
				$businessdomain_id = $this->_request->getParam('businessdomain_id');
				for($i=0;$i<sizeof($businessdomain_id);$i++)
				{
					$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = array(
					'businessdomain_id' =>$businessdomain_id[$i],
					'sbunit_id' => $sbunit_id
				);

				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$where = '';
				$actionflag = 1;
		try{
				$Id = $model->SaveorUpdateData($data, $where);
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Business Unit Engagment   added successfully."));

				$this->_redirect('sbuengagment');

			} catch (\Throwable $th) {
				$code=$th->getCode();
				if($code=2300){
					$msgarray['businessdomain_id']='business domain already exist in the selected business Unit';
					$this->view->msgarray = $msgarray;
				}else{
					$tableid = $Id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Business Unit Engagment   added successfully."));
	
	
					$this->_redirect('sbuengagment');
				}
				}
			}
				}
				
			
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

		$this->view->controllername = $controllername;
		$this->view->form = $form;
		$this->view->ermsg = '';
		}
	public function editAction()
	{
		
			//code...
		
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$msgarray = array();
		$controllername = 'sbuengagment';
		$form = new Default_Form_sbuengagment();
		$model = new Default_Model_sbuengagment();
		$buid = $this->_request->getParam('bu_id');
		$bdid = $this->_request->getParam('bd_id');
	

		$modelbu = new Default_Model_Businessunits();
		$add = $modelbu->getBusinessUnitsList();
		// die(json_encode($add));
		$modelbd = new Default_Model_businessdomain();
		$bdd = $modelbd->getbusinessdomaindata();
		$form->sbunit_id->addMultiOption(' ', 'Select...');
		$form->businessdomain_id2->addMultiOption(' ', 'Select...');
		if (empty($add)) {
			$msgarray['sbunit_id'] = 'Business Unit is not configured yet.';
		} else {
			foreach($add as $adds){
				$form->sbunit_id->addMultiOptions(array($adds['sbunit_id'] => $adds['name']));

			}

		
		}

		if (empty($bdd)) {
			$msgarray['businessdomain_id'] = 'Business Domain is not configured yet.';
		} else {
			foreach($bdd as $bdds){
				$form->businessdomain_id2->addMultiOptions(array($bdds['businessdomain_id'] => $bdds['name']));
			}
		}

		$singledata=$model->getsingledata($buid,$bdid);
		$form->businessdomain_id2->setValue($singledata[0]['bd_id']);
		$form->sbunit_id->setValue($singledata[0]['id']);
		$form->removeElement("businessdomain_id");

		
		
		if ($this->getRequest()->getPost()) {
			if ($form->isValid($this->_request->getPost())) {
				$buid = $this->_request->getParam('bu_id');
				$bdid = $this->_request->getParam('bd_id');
				$sbunit_id = $this->_request->getParam('sbunit_id');
				$businessdomain_id2 = $this->_request->getParam('businessdomain_id2');
				
					$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = array(
					'businessdomain_id' =>$businessdomain_id2,
					'sbunit_id' => $sbunit_id
				);

				$data['createdby'] = $loginUserId;
				$data['createddate'] = gmdate("Y-m-d H:i:s");
				$where = 'sbunit_id= '.$buid.' and businessdomain_id= '.$bdid;
				
				$actionflag = 1;
try{
				$Id = $model->SaveorUpdateData($data, $where);
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Business Unit Engagment   Edit successfully."));

				$this->_redirect('sbuengagment');

					} catch (\Throwable $th) {
						$code=$th->getCode();
						if($code=2300){
							$msgarray['businessdomain_id2']='business domain already exist in the selected business Unit';
							$this->view->msgarray = $msgarray;
						}	else{
							$tableid = $Id;
							$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Business Unit Engagment   added successfully."));
			
			
							$this->_redirect('sbuengagment');
						}					
}
				
				
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
		$this->view->msgarray = $msgarray;


	}
	public function viewAction()
	{

	}
}
