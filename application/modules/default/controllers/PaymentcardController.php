<?php

class Default_PaymentcardController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
      
      $dmtmodel = new Default_Model_Paymentcard();
      $call = $this->_getParam('call');

      if ($call == 'ajaxcall') {
          $this->_helper->layout->disableLayout();
      }

      $refresh = $this->_getParam('refresh');
      $dashboradcall = $this->_getParam('dashboradcall');

      $data = array();

      if ($refresh == 'refresh') {
          if ($dashboradcall == 'Yes') {
              $perPage = DASHBOARD_PERPAGE;
          }
          else{
              $perPage = PERPAGE;
          }
          $sort = 'DESC';
          $by = 'mp.name';
          $pageNo = 1;
          $searchData = '';
      }
      else{
          $sort = ($this->_getParam('sort')!='') ? $this->_getParam('sort'):'DESC';
          $by = ($this->_getParam('by')!='') ? $this->_getParam('by'):'mp.name';
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
        $form = new Default_Form_Paymentcard();
        $dmtmodel = new Default_Model_Paymentcard();
        $auth = Zend_Auth::getInstance();

        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $typepermission = '';
        $typepermission = sapp_Global::_checkprivileges(PAYMENTCARDTYPE,$loginuserGroup,$loginuserRole,'add');
        $this->view->typepermission = $typepermission;
        $providerpermission = '';
        $providerpermission = sapp_Global::_checkprivileges(CARDPROVIDER,$loginuserGroup,$loginuserRole,'add');
        $this->view->providerpermission = $providerpermission;  
        ///////////////////////////////
        $modelb = new Default_Model_Cardprovider();
        $add = $modelb->getallcardprovider();
        $form->cardprovider_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['cardprovider_id'] = 'card Provider  is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->cardprovider_id->addMultiOptions(array($adds['cardprovider_id'] => $adds['name']));
            }
        }

        //////////////////////////////
           ///////////////////////////////
           $modelc = new Default_Model_Paymentcardtype();
           $addc = $modelc->getallpaymentcardtype();
           $form->paymentcardtype_id->addMultiOption(' ', 'Select...');
           if (empty($addc)) {
               $msgarray['paymentcardtype_id'] = 'Payment Card Type  is not configured yet.';
           } else {
               foreach ($addc as $addcs) {
                   $form->paymentcardtype_id->addMultiOptions(array($addcs['paymentcardtype_id'] => $addcs['name']));
               }
           }
   
           //////////////////////////////
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = array(
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'paymentcardtype_id'=>$this->_request->getParam('paymentcardtype_id'),
                    'cardprovider_id'=>$this->_request->getParam('cardprovider_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );
                $Id=$dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(339,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(array('success'=>'Payment Card Added Successful'));
                $this->_redirect('paymentcard');
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
    //code...

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $form = new Default_Form_Paymentcard();
        $dmtmodel = new Default_Model_Paymentcard();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
      ///////////////////////////////
      $modelb = new Default_Model_Cardprovider();
      $add = $modelb->getallcardprovider();
      $form->cardprovider_id->addMultiOption(' ', 'Select...');
      if (empty($add)) {
          $msgarray['cardprovider_id'] = 'card Provider  is not configured yet.';
      } else {
          foreach ($add as $adds) {
              $form->cardprovider_id->addMultiOptions(array($adds['cardprovider_id'] => $adds['name']));
          }
      }

      //////////////////////////////
         ///////////////////////////////
         $modelc = new Default_Model_Paymentcardtype();
         $addc = $modelc->getallpaymentcardtype();
         $form->paymentcardtype_id->addMultiOption(' ', 'Select...');
         if (empty($addc)) {
             $msgarray['paymentcardtype_id'] = 'Payment Card Type  is not configured yet.';
         } else {
             foreach ($addc as $addcs) {
                 $form->paymentcardtype_id->addMultiOptions(array($addcs['paymentcardtype_id'] => $addcs['name']));
             }
         }
 
        $form->setAttrib('action', BASE_URL . 'paymentcard/addpopup');
        if ($this->getRequest()->getPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {

                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $data = array(
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'paymentcardtype_id'=>$this->_request->getParam('paymentcardtype_id'),
                    'cardprovider_id'=>$this->_request->getParam('cardprovider_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );

                $tableid=$dmtmodel->SaveorUpdateData($data, '');
                sapp_Global::logManager(334,1,$loginUserId,$tableid);
                $currencyData = $dmtmodel->fetchAll('isactive = "Y"', 'name')->toArray();
                $opt = '';
                foreach ($currencyData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['paymentcard_id'], $record['name']);
                }
                $this->view->paymentcard = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->view->controllername = "paymentcard";
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
        $form = new Default_Form_Paymentcard();
        $dmtmodel = new Default_Model_Paymentcard();
        $auth = Zend_Auth::getInstance();
      
        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $typepermission = '';
        $typepermission = sapp_Global::_checkprivileges(PAYMENTCARDTYPE,$loginuserGroup,$loginuserRole,'add');
        $this->view->typepermission = $typepermission;
        $providerpermission = '';
        $providerpermission = sapp_Global::_checkprivileges(CARDPROVIDER,$loginuserGroup,$loginuserRole,'add');
        $this->view->providerpermission = $providerpermission;  
        $id = $this->_getParam('id');
///////////////////////////////
$modelb = new Default_Model_Cardprovider();
$add = $modelb->getallcardprovider();
$form->cardprovider_id->addMultiOption(' ', 'Select...');
if (empty($add)) {
    $msgarray['cardprovider_id'] = 'card Provider  is not configured yet.';
} else {
    foreach ($add as $adds) {
        $form->cardprovider_id->addMultiOptions(array($adds['cardprovider_id'] => $adds['name']));
    }
}

//////////////////////////////
   ///////////////////////////////
   $modelc = new Default_Model_Paymentcardtype();
   $addc = $modelc->getallpaymentcardtype();
   $form->paymentcardtype_id->addMultiOption(' ', 'Select...');
   if (empty($addc)) {
       $msgarray['paymentcardtype_id'] = 'Payment Card Type  is not configured yet.';
   } else {
       foreach ($addc as $addcs) {
           $form->paymentcardtype_id->addMultiOptions(array($addcs['paymentcardtype_id'] => $addcs['name']));
       }
   }

   //////////////////////////////

        $dmtdata=$dmtmodel->getpaymentcard($id);
      


        $form->populate($dmtdata);
        $form->setAttrib('action',BASE_URL.'paymentcard/edit/id/'.$id);
        $form->isactive->setValue(utlis::getcheckboxvalue($dmtdata['isactive'],'form'));
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = array(
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'paymentcardtype_id'=>$this->_request->getParam('paymentcardtype_id'),
                    'cardprovider_id'=>$this->_request->getParam('cardprovider_id'),
                    'description'=>$this->_request->getParam('description'),
                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'modifiedby'=>$loginUserId,
                    'modifieddate'=>gmdate('Y-m-d H:i:s')
                );
                $dmtmodel->SaveorUpdateData($data,'paymentcard_id='.$id);
                $tableid = $id;
                $actionflag = 2;
                $result = sapp_Global::logManager(339,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(array('success'=>'Paymentcard  Updated Successful'));
                $this->_redirect('paymentcard');
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
  
}
