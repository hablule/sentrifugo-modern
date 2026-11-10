<?php

class Default_CertproviderController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
      
      $dmtmodel = new Default_Model_Certprovider();
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
          $by = 'name';
          $pageNo = 1;
          $searchData = '';
      }
      else{
          $sort = ($this->_getParam('sort')!='') ? $this->_getParam('sort'):'DESC';
          $by = ($this->_getParam('by')!='') ? $this->_getParam('by'):'name';
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
    public function addAction()
    {
        try {
            //code...
        $form = new Default_Form_Certprovider();
        $dmtmodel = new Default_Model_Certprovider();
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $categorypermission = '';
        $categorypermission = sapp_Global::_checkprivileges(CERTPROVIDERCATEGORY,$loginuserGroup,$loginuserRole,'add');
        $this->view->categorypermission = $categorypermission;
        $modelb = new Default_Model_Certprovidercategory();
        $add = $modelb->getallcategory();
        $form->certprovidercategory_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['certprovidercategory_id'] = 'Certification Provider Category is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->certprovidercategory_id->addMultiOptions([$adds['certprovidercategory_id'] => $adds['name']]);
            }
        }
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'certprovidercategory_id'=>$this->_request->getParam('certprovidercategory_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];
                $Id=$dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(321,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Certificate Provider   Added Successful']);
                $this->_redirect('certprovider');
            }
            $messages = $form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }
        $this->view->msgarray = $msgArray;
        $this->view->form = $form; 
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }


    public function addpopupAction()
    {
        
        try {
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");       //code...
      
        $form = new Default_Form_Certprovider();
        $dmtmodel = new Default_Model_Certprovider();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $id = $this->_request->getParam('id');
		$this->view->status=$id;        
        $form->setAction(BASE_URL.'certprovider/addpopup/id/'.$id);
        $modelb = new Default_Model_Certprovidercategory();
        $add = $modelb->getallcategory();
        $form->certprovidercategory_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['certprovidercategory_id'] = 'Certification Provider Category  is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->certprovidercategory_id->addMultiOptions([$adds['certprovidercategory_id'] => $adds['name']]);
            }
        }
        if ($this->getRequest()->isPost()) {
           
            
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'certprovidercategory_id'=>$this->_request->getParam('certprovidercategory_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];
                $Id= $dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(322,$actionflag,$loginUserId,$tableid);
               $this->view->popup = 'close';
               $linecategoryData = $dmtmodel->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($linecategoryData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['certprovider_id'], $record['name']);
				}
                if ($id==1) {
                    $this->view->nested=true;
                }
                else{
                    $this->view->nested = false;
                }
				$this->view->certprovider = $opt;
               $this->view->controllername = 'certprovider';
            }
            $messages = $form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }
        $this->view->msgarray = $msgArray;
        $this->view->form = $form; 
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }

    public function editAction()
    {
        try{
        require_once 'application/modules/default/library/utilClasses/Util.php';
        $form = new Default_Form_Certprovider();
        $dmtmodel = new Default_Model_Certprovider();
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $categorypermission = '';
        $categorypermission = sapp_Global::_checkprivileges(CERTPROVIDERCATEGORY,$loginuserGroup,$loginuserRole,'add');
        $this->view->categorypermission = $categorypermission;
        $id = $this->_getParam('id');
        $dmtdata=$dmtmodel->getcertprovider($id);
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $modelb = new Default_Model_Certprovidercategory();
        $add = $modelb->getallcategory();
        $form->certprovidercategory_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['certprovidercategory_id'] = 'Certification Provider  is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->certprovidercategory_id->addMultiOptions([$adds['certprovidercategory_id'] => $adds['name']]);
            }
        }
        $form->populate($dmtdata);
        $form->setAttrib('action',BASE_URL.'certprovider/edit/id/'.$id);
        $form->isactive->setValue(utlis::getcheckboxvalue($dmtdata['isactive'],'form'));
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'description'=>$this->_request->getParam('description'),
                    'certprovidercategory_id'=>$this->_request->getParam('certprovidercategory_id'),

                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'modifiedby'=>$loginUserId,
                    'modifieddate'=>gmdate('Y-m-d H:i:s')
                ];
                $dmtmodel->SaveorUpdateData($data,'certprovider_id='.$id);
                $tableid = $id;
                $actionflag = 2;
                $result = sapp_Global::logManager(322,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Certificate provider  Updated Successful']);
                $this->_redirect('certprovider');
            }
            $messages = $form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }
        $this->view->msgarray = $msgArray;
        $this->view->form = $form;
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
