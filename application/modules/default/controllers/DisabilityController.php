<?php

class Default_DisabilityController extends Zend_Controller_Action
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
      
      $dmtmodel = new Default_Model_Disability();
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
          $by = 'md.name';
          $pageNo = 1;
          $searchData = '';
      }
      else{
          $sort = ($this->_getParam('sort')!='') ? $this->_getParam('sort'):'DESC';
          $by = ($this->_getParam('by')!='') ? $this->_getParam('by'):'md.name';
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
try {
    //code...

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $form = new Default_Form_Disability();
        $dmtmodel = new Default_Model_Disability();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
   ///////////////////////////////
   $modelb = new Default_Model_Disabilitytype();
   $add = $modelb->getalltype();
   $form->disabilitytype_id->addMultiOption(' ', 'Select...');
   if (empty($add)) {
       $msgarray['disabilitytype_id'] = 'Disability Type  is not configured yet.';
   } else {
       foreach ($add as $adds) {
           $form->disabilitytype_id->addMultiOptions([$adds['disabilitytype_id'] => $adds['name']]);
       }
   }

   //////////////////////////////
      ///////////////////////////////
      $modelc = new Default_Model_disabilitycategory();
      $addc = $modelc->getallcategory();
      $form->disabilitycategory_id->addMultiOption(' ', 'Select...');
      if (empty($addc)) {
          $msgarray['disabilitycategory_id'] = 'Disability Category  is not configured yet.';
      } else {
          foreach ($addc as $addcs) {
              $form->disabilitycategory_id->addMultiOptions([$addcs['disabilitycategory_id'] => $addcs['name']]);
          }
      }

        $form->setAttrib('action', BASE_URL . 'disability/addpopup');
        if ($this->getRequest()->getPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'disabilitycategory_id'=>$this->_request->getParam('disabilitycategory_id'),
                    'disabilitytype_id'=>$this->_request->getParam('disabilitytype_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];

                $tableid=$dmtmodel->SaveorUpdateData($data, '');
                sapp_Global::logManager(332,1,$loginUserId,$tableid);
                $currencyData = $dmtmodel->fetchAll('isactive = "Y"', 'name')->toArray();
                $opt = '';
                foreach ($currencyData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['disability_id'], $record['name']);
                }
                $this->view->disability = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->view->controllername = "disability";
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
    public function addpopupnestedAction()
    {
try {
    //code...

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $form = new Default_Form_Disability();
        $dmtmodel = new Default_Model_Disability();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
   ///////////////////////////////
   $modelb = new Default_Model_Disabilitytype();
   $add = $modelb->getalltype();
   $form->disabilitytype_id->addMultiOption(' ', 'Select...');
   if (empty($add)) {
       $msgarray['disabilitytype_id'] = 'Disability Type  is not configured yet.';
   } else {
       foreach ($add as $adds) {
           $form->disabilitytype_id->addMultiOptions([$adds['disabilitytype_id'] => $adds['name']]);
       }
   }

   //////////////////////////////
      ///////////////////////////////
      $modelc = new Default_Model_disabilitycategory();
      $addc = $modelc->getallcategory();
      $form->disabilitycategory_id->addMultiOption(' ', 'Select...');
      if (empty($addc)) {
          $msgarray['disabilitycategory_id'] = 'Disability Category  is not configured yet.';
      } else {
          foreach ($addc as $addcs) {
              $form->disabilitycategory_id->addMultiOptions([$addcs['disabilitycategory_id'] => $addcs['name']]);
          }
      }

        $form->setAttrib('action', BASE_URL . 'disability/addpopupnested');
        if ($this->getRequest()->getPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {

                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'disabilitycategory_id'=>$this->_request->getParam('disabilitycategory_id'),
                    'disabilitytype_id'=>$this->_request->getParam('disabilitytype_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];

                $tableid=$dmtmodel->SaveorUpdateData($data, '');
                sapp_Global::logManager(332,1,$loginUserId,$tableid);
                $currencyData = $dmtmodel->fetchAll('isactive = "Y"', 'name')->toArray();
                $opt = '';
                foreach ($currencyData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['disability_id'], $record['name']);
                }
                $this->view->disability = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->view->controllername = "disability";
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

    public function editAction()
    {
        try{
        $form = new Default_Form_Disability();
        $dmtmodel = new Default_Model_Disability();
        $auth = Zend_Auth::getInstance();

        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $categorypermission = '';
        $categorypermission = sapp_Global::_checkprivileges(DISABILITYCATEGORY,$loginuserGroup,$loginuserRole,'add');
        $this->view->categorypermission = $categorypermission;
        $typepermission = '';
        $typepermission = sapp_Global::_checkprivileges(DISABILITYTYPE,$loginuserGroup,$loginuserRole,'add');
        $this->view->typepermission = $typepermission;    
        $id = $this->getRequest()->getParam('id');
		$objName = 'disability';

        if($id)
			{	//Edit Record...
				
				if(is_numeric($id) && $id>0)
				{
					$data = $dmtmodel->getdisability($id);
			

					if(!empty($data) && $data != "norows")
					{
						$form->populate($data);
						$form->submit->setLabel('Update');
                        $form->isactive->setValue(utlis::getcheckboxvalue($data['isactive'],'form'));

						$this->view->form = $form;
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
				$this->view->form = $form;
				$this->view->inpage = 'Add';
			}

            if($this->getRequest()->getPost()){

                if($form->isValid($this->_request->getPost())){
                        $id = $this->_request->getParam('id');
                        
        
        
                        $date = gmdate("Y-m-d H:i:s");
        
                        $data = [
                            'name'=>$this->_request->getParam('name'),
                                       'code'=>$this->_request->getParam('code'),
                                       'disabilitycategory_id'=>$this->_request->getParam('disabilitycategory_id'),
                                      'disabilitytype_id'=>$this->_request->getParam('disabilitytype_id'),
                                       'description'=>$this->_request->getParam('description'),
                                        'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
        
                                    ];
                        if($id!=''){
        
                            $data['modifiedby'] = $loginUserId;
                            $data['modifieddate'] = $date;
                            $where = ['disability_id=?'=>$id];
                        }
                        else
                        {
                            $data['createdby'] = $loginUserId;
                            $data['createddate'] = $date;
                            $where = '';
                        }
        
                    $Id = $dmtmodel->SaveorUpdateData($data, $where);
                        
                        if($Id == 'update')
                        {
                            $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Disability  updated successfully."]);
                        }
                        else
                        {
                         $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Disability  added successfully."]);
                        }
                            
                        $this->_redirect('disability');
                    
                        
                            
                    }else{
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



        // if ($this->getRequest()->isPost()) {
        //     if ($form->isValid($this->getRequest()->getPost())) {
        //         $data = array(
        //             'name'=>$this->_request->getParam('name'),
        //             'code'=>$this->_request->getParam('code'),
        //             'disabilitycategory_id'=>$this->_request->getParam('disabilitycategory_id'),
        //             'disabilitytype_id'=>$this->_request->getParam('disabilitytype_id'),
        //             'description'=>$this->_request->getParam('description'),
        //             'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
        //             'modifiedby'=>$loginUserId,
        //             'modifieddate'=>gmdate('Y-m-d H:i:s')
        //         );
        //         $dmtmodel->SaveorUpdateData($data,'disability_id='.$id);
        //         $tableid = $id;
        //         $actionflag = 2;
        //         $result = sapp_Global::logManager(332,$actionflag,$loginUserId,$tableid);
        //         $this->_helper->getHelper("FlashMessenger")->addMessage(array('success'=>'Disability  Updated Successful'));
        //         $this->_redirect('disability');
        //     }
        //     $messages = $form->getMessages();
        //     foreach ($messages as $key => $val) {
        //         foreach ($val as $key2 => $val2) {
        //             $msgArray[$key] = $val2;
        //             break;
        //         }
        //     }
        // }
        // $this->view->msgarray = $msgArray;
        // $this->view->form = $form;
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }
    // public function viewAction()
    // {
    //     $form = new Default_Form_Certprovidercategory();
    //     $dmtmodel = new Default_Model_Certprovidercategory();
    //     $auth = Zend_Auth::getInstance();
    //     $id = $this->_getParam('id');
    //     $dmtdata=$dmtmodel->getcertprovidercategory($id);
    //     if ($auth->hasIdentity()) {
    //         $loginUserId = $auth->getStorage()->read()->id;
    //     }
    //     $form->populate($dmtdata);
       
     
    //     $form->isactive->setValue($dmtdata['isactive']);
    //     $this->view->form = $form; 
    //     $this->view->isactive = $dmtdata['isactive'];
    //     $this->view->form = $form;
    //     $this->view->id = $id;

    //     $this->view->controllername='skillcategory';
    // }
}
