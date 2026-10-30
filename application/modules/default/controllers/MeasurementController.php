<?php

class Default_MeasurementController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
      
      $dmtmodel = new Default_Model_Measurement();
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
            $form = new Default_Form_Measurement();
        $dmtmodel = new Default_Model_Measurement();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        if ($this->getRequest()->isPost()) {
           
           
            if ($form->isValid($this->getRequest()->getPost())) {

                $data = array(
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'description'=>$this->_request->getParam('description'),
                    'valuetype'=>$this->_request->getParam('valuetype'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );

                $Id=$dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(291,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(array('success'=>'Measurement Added Successful'));
                $this->_redirect('measurement');
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
      
        $form = new Default_Form_Measurement();
        $dmtmodel = new Default_Model_Measurement();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $id = $this->_getParam('id');
        $form->setAction(BASE_URL.'measurement/addpopup/id/'.$id);
        if ($this->getRequest()->isPost()) {
           
            
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = array(
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'valuetype'=>$this->_request->getParam('valuetype'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );
                $Id= $dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(291,$actionflag,$loginUserId,$tableid);
               $this->view->popup = 'close';
               $linecategoryData = $dmtmodel->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($linecategoryData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['measurement_id'], $record['name']);
				}
                if ($id==1) {
                    $this->view->nested=true;
                }
                else{
                    $this->view->nested = false;
                }
				$this->view->measurement = $opt;
               $this->view->controllername = 'measurement';
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
        $form = new Default_Form_Measurement();
        $dmtmodel = new Default_Model_Measurement();
        $auth = Zend_Auth::getInstance();
        $id = $this->_getParam('id');
        $dmtdata=$dmtmodel->getmeasurement($id);
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $form->populate($dmtdata);
        $form->setAttrib('action',BASE_URL.'measurement/edit/id/'.$id);
        $form->isactive->setValue(utlis::getcheckboxvalue($dmtdata['isactive'],'form'));
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = array(
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'description'=>$this->_request->getParam('description'),
                    'valuetype'=>$this->_request->getParam('valuetype'),
                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'modifiedby'=>$loginUserId,
                    'modifieddate'=>gmdate('Y-m-d H:i:s')
                );
                $dmtmodel->SaveorUpdateData($data,'measurement_id='.$id);
                $tableid = $id;
                $actionflag = 2;
                $result = sapp_Global::logManager(291,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(array('success'=>'Measurement Updated Successful'));
                $this->_redirect('measurement');
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
        $form = new Default_Form_Measurement();
        $dmtmodel = new Default_Model_Measurement();
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

        $this->view->controllername='measurement';
    }
}
