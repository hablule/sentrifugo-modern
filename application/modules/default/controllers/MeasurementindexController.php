<?php

class Default_MeasurementindexController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
      
      $dmtmodel = new Default_Model_Measurementindex();
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
            $form = new Default_Form_Measurementindex();
        $dmtmodel = new Default_Model_Measurementindex();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $modelb = new Default_Model_Measurement();
        $add = $modelb->getmeasurementlist();
        $form->measurement_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['measurement_id'] = 'Measurement is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->measurement_id->addMultiOptions(array($adds['measurement_id'] => $adds['name']));
            }
        }
        if ($this->getRequest()->isPost()) {
           
           
            if ($form->isValid($this->getRequest()->getPost())) {

                $data = array(
                    'value'=>$this->_request->getParam('value'),
                    'measurement_id'=>$this->_request->getParam('measurement_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );

                $Id=$dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(291,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(array('success'=>'Measurement Index Added Successful'));
                $this->_redirect('measurementindex');
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
        $form = new Default_Form_Measurementindex();
        $form->setAttrib('action', BASE_URL . 'measurementindex/addpopup');
        $modelb = new Default_Model_Measurement();
        $add = $modelb->getmeasurementlist();
        $form->measurement_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['measurement_id'] = 'Measurement is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->measurement_id->addMultiOptions(array($adds['measurement_id'] => $adds['name']));
            }
        }
        if ($this->getRequest()->getPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $model = new Default_Model_Measurementindex();
                $data = array(
                    'value'=>$this->_request->getParam('value'),
                    'measurement_id'=>$this->_request->getParam('measurement_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );

                $tableid=$model->SaveorUpdateData($data, '');
                sapp_Global::logManager(318,1,$loginUserId,$tableid);
                $currencyData = $model->fetchAll('isactive = "Y"', 'value')->toArray();
                $opt = '';
                foreach ($currencyData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['measurementindex_id'], $record['value']);
                }
                $this->view->measurementindex = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->view->controllername = "measurementindex";
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
        $form = new Default_Form_Measurementindex();
        $dmtmodel = new Default_Model_Measurementindex();
        $auth = Zend_Auth::getInstance();
        $id = $this->_getParam('id');
        $modelb = new Default_Model_Measurement();
        $add = $modelb->getmeasurementlist();
        $form->measurement_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['measurement_id'] = 'Measurement is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->measurement_id->addMultiOptions(array($adds['measurement_id'] => $adds['name']));
            }
        }

        $dmtdata=$dmtmodel->getmeasurement($id);
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $form->populate($dmtdata);
        $form->setAttrib('action',BASE_URL.'measurementindex/edit/id/'.$id);
        $form->isactive->setValue(utlis::getcheckboxvalue($dmtdata['isactive'],'form'));
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = array(
                    'value'=>$this->_request->getParam('value'),
                    'measurement_id'=>$this->_request->getParam('measurement_id'),
                    'description'=>$this->_request->getParam('description'),
                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'modifiedby'=>$loginUserId,
                    'modifieddate'=>gmdate('Y-m-d H:i:s')
                );
                $dmtmodel->SaveorUpdateData($data,'measurementindex_id='.$id);
                $tableid = $id;
                $actionflag = 2;
                $result = sapp_Global::logManager(291,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(array('success'=>'Measurement Index Updated Successful'));
                $this->_redirect('measurementindex');
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
    public function viewAction()
    {
        $form = new Default_Form_Measurementindex();
        $dmtmodel = new Default_Model_Measurementindex();
        $auth = Zend_Auth::getInstance();
        $id = $this->_getParam('id');
        $dmtdata=$dmtmodel->getmeasurement($id);
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $form->populate($dmtdata);
       
     
        $form->isactive->setValue($dmtdata['isactive']);
        $this->view->form = $form; 
        $this->view->isactive = $dmtdata['isactive'];
        $this->view->form = $form;
        $this->view->id = $id;

        $this->view->controllername='measurementindex';
    }
}
