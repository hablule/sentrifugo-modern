<?php

class Default_cardproviderController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';

    }
    public function indexAction()
    {
        try {
            //code...
      
      $dmtmodel = new Default_Model_Cardprovider();
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
          $by = 'cp.name';
          $pageNo = 1;
          $searchData = '';
      }
      else{
          $sort = ($this->_getParam('sort')!='') ? $this->_getParam('sort'):'DESC';
          $by = ($this->_getParam('by')!='') ? $this->_getParam('by'):'cp.name';
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
        $form = new Default_Form_Cardprovider();
        $dmtmodel = new Default_Model_Cardprovider();
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $bankpermission = '';
        $bankpermission = sapp_Global::_checkprivileges(BANK,$loginuserGroup,$loginuserRole,'add');
        $this->view->bankpermission = $bankpermission;
        ///////////////////////////////
        $modelb = new Default_Model_Bank();
        $add = $modelb->getBanks();
        $form->bank_id->addMultiOption('', 'Select...');
        if (empty($add)) {
            $msgarray['bank_id'] = 'Bank  is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->bank_id->addMultiOptions([$adds['bank_id'] => $adds['name']]);
            }
        }

        if($this->_request->getParam('isbank')==0){
        $form->bank_id->setRequired(false);

        }
     
        
        if ($this->getRequest()->isPost()) {
            $data = [
                'name'=>$this->_request->getParam('name'),
                'code'=>$this->_request->getParam('code'),
                'bank_id'=>$this->_request->getParam('bank_id'),
                'isbank'=>utlis::getcheckboxvalue($this->_request->getParam('isbank')),
                'description'=>$this->_request->getParam('description'),
                'createdby'=>$loginUserId,
                'createddate'=>gmdate('Y-m-d H:i:s')
            ];
          

   
            if ($form->isValid($this->getRequest()->getPost())) {
    
                $Id=$dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(334,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Card Provider Added Successful']);
                $this->_redirect('cardprovider');
            }
            $messages = $form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
            $this->view->msgarray = $msgArray;
            $this->view->form = $form; 
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
    //code...

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $form = new Default_Form_Cardprovider();
        $dmtmodel = new Default_Model_Cardprovider();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $id = $this->_request->getParam('id');
		$this->view->status=$id;
    ///////////////////////////////
    $modelb = new Default_Model_Bank();
    $add = $modelb->getBanks();
    $form->bank_id->addMultiOption('', 'Select...');
    if (empty($add)) {
        $msgarray['bank_id'] = 'Bank  is not configured yet.';
    } else {
        foreach ($add as $adds) {
            $form->bank_id->addMultiOptions([$adds['bank_id'] => $adds['name']]);
        }
    }

    if($this->_request->getParam('isbank')==0){
    $form->bank_id->setRequired(false);

    }

        $form->setAttrib('action', BASE_URL . 'cardprovider/addpopup');
        if ($this->getRequest()->getPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $data = [
                    'name'=>$this->_request->getParam('name'),
                'code'=>$this->_request->getParam('code'),
                'bank_id'=>$this->_request->getParam('bank_id'),
                'isbank'=>utlis::getcheckboxvalue($this->_request->getParam('isbank')),
                'description'=>$this->_request->getParam('description'),
                'createdby'=>$loginUserId,
                'createddate'=>gmdate('Y-m-d H:i:s')
                ];

                $tableid=$dmtmodel->SaveorUpdateData($data, '');
                sapp_Global::logManager(334,1,$loginUserId,$tableid);
                $currencyData = $dmtmodel->fetchAll('isactive = "Y"', 'name')->toArray();
                $opt = '';
                foreach ($currencyData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['cardprovider_id'], $record['name']);
                }
                $this->view->cardprovider= $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->view->controllername = "cardprovider";
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
        require_once 'application/modules/default/library/utilClasses/Util.php';
        $form = new Default_Form_Cardprovider();
        $dmtmodel = new Default_Model_Cardprovider();
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $bankpermission = '';
        $bankpermission = sapp_Global::_checkprivileges(BANK,$loginuserGroup,$loginuserRole,'add');
        $this->view->bankpermission = $bankpermission;
                $id = $this->_getParam('id');
 
          ///////////////////////////////
          $modelb = new Default_Model_Bank();
          $add = $modelb->getBanks();
          $form->bank_id->addMultiOption(' ', 'Select...');
          if (empty($add)) {
              $msgarray['bank_id'] = 'Bank  is not configured yet.';
          } else {
              foreach ($add as $adds) {
                  $form->bank_id->addMultiOptions([$adds['bank_id'] => $adds['name']]);
              }
          }
  
          //////////////////////////////

        $dmtdata=$dmtmodel->getcardprovider($id);
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }


        $form->populate($dmtdata);
        $form->setAttrib('action',BASE_URL.'cardprovider/edit/id/'.$id);
        $form->isactive->setValue(utlis::getcheckboxvalue($dmtdata['isactive'],'form'));
        $form->isbank->setValue(utlis::getcheckboxvalue($dmtdata['isbank'],'form'));
        if($this->_request->getParam('isbank')==0){
            $form->bank_id->setRequired(false);
    
            }
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'bank_id'=>$this->_request->getParam('bank_id'),
                    'isbank'=>utlis::getcheckboxvalue($this->_request->getParam('isbank')),
                    'description'=>$this->_request->getParam('description'),
                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'modifiedby'=>$loginUserId,
                    'modifieddate'=>gmdate('Y-m-d H:i:s')
                ];
                $dmtmodel->SaveorUpdateData($data,'cardprovider_id='.$id);
                $tableid = $id;
                $actionflag = 2;
                $result = sapp_Global::logManager(334,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Card provider  Updated Successful']);
                $this->_redirect('cardprovider');
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
