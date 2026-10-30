<?php

class Default_SkillController extends Zend_Controller_Action
{ 
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
      
      $dmtmodel = new Default_Model_Skill();
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
            $form = new Default_Form_Skill();
        $dmtmodel = new Default_Model_Skill();
        $auth = Zend_Auth::getInstance();
 $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $categorypermission = '';
        $categorypermission = sapp_Global::_checkprivileges(SKILLCATEGORY,$loginuserGroup,$loginuserRole,'add');
        $this->view->categorypermission = $categorypermission;
      
        $indexpermission = '';
        $indexpermission = sapp_Global::_checkprivileges(MEASURMENTINDEX,$loginuserGroup,$loginuserRole,'add');
        $this->view->indexpermission = $indexpermission;


        $modelb = new Default_Model_Measurement();
        $add = $modelb->getmeasurementlist();
        $form->measurement_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['measurement_id'] = 'Measurement is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->measurement_id->addMultiOptions([$adds['measurement_id'] => $adds['name']]);
            }
        }
        $modelc = new Default_Model_Skillcategory();
        $addc = $modelc->getallcategory();
        $form->skillcategory_id->addMultiOption(' ', 'Select...');

        if (empty($addc)) {
            $msgarray['skillcategory_id'] = 'skillcategory is not configured yet.';
        } else {
            foreach ($addc as $addsc) {
                $form->skillcategory_id->addMultiOptions([$addsc['skillcategory_id'] => $addsc['name']]);
            }
        }
        $form->parentskill_id->addMultiOption(' ', 'Select...');
        $pskill=$dmtmodel->getskilllistsummary();

 if (empty($pskill)) {
            $msgarray['parentskill_id'] = 'skill is not configured yet.';
        } else {
            foreach ($pskill as $pskills) {
                $form->parentskill_id->addMultiOptions([$pskills['skill_id'] => $pskills['name']]);
            }
        }
        if ($this->getRequest()->isPost()) {
           
           
            if ($form->isValid($this->getRequest()->getPost())) {

                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'skillcategory_id'=>$this->_request->getParam('skillcategory_id'),
                    'measurement_id'=>$this->_request->getParam('measurement_id'),
                    'parentskill_id'=>$this->_request->getParam('parentskill_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];
                $Id=$dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(291,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Skill Added Successful']);
                $this->_redirect('skill');
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
      
        $form = new Default_Form_Skill();
        $dmtmodel = new Default_Model_skill();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $id = $this->_request->getParam('id');
		$this->view->status=$id;
        $form->setAction(BASE_URL.'skill/addpopup/id/'.$id);
        $modelb = new Default_Model_Measurement();
        $add = $modelb->getmeasurementlist();
        $form->measurement_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['measurement_id'] = 'Measurement is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->measurement_id->addMultiOptions([$adds['measurement_id'] => $adds['name']]);
            }
        }
        $modelc = new Default_Model_Skillcategory();
        $addc = $modelc->getallcategory();
        $form->skillcategory_id->addMultiOption(' ', 'Select...');

        if (empty($addc)) {
            $msgarray['skillcategory_id'] = 'skillcategory is not configured yet.';
        } else {
            foreach ($addc as $addsc) {
                $form->skillcategory_id->addMultiOptions([$addsc['skillcategory_id'] => $addsc['name']]);
            }
        }
        $form->parentskill_id->addMultiOption(' ', 'Select...');
        $pskill=$dmtmodel->getskilllistsummary();

 if (empty($pskill)) {
            $msgarray['parentskill_id'] = 'skill is not configured yet.';
        } else {
            foreach ($pskill as $pskills) {
                $form->parentskill_id->addMultiOptions([$pskills['skill_id'] => $pskills['name']]);
            }
        }
        if ($this->getRequest()->isPost()) {
           
            
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'skillcategory_id'=>$this->_request->getParam('skillcategory_id'),
                    'measurement_id'=>$this->_request->getParam('measurement_id'),
                    'parentskill_id'=>$this->_request->getParam('parentskill_id'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];
                $Id= $dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(291,$actionflag,$loginUserId,$tableid);
               $this->view->popup = 'close';
               $linecategoryData = $dmtmodel->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($linecategoryData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['skil_id'], $record['name']);
				}
                if ($id==1) {
                    $this->view->nested=true;
                }
                else{
                    $this->view->nested = false;
                }
				$this->view->skill = $opt;
               $this->view->controllername = 'skill';
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
        $form = new Default_Form_Skill();
        $dmtmodel = new Default_Model_Skill();
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $categorypermission = '';
        $categorypermission = sapp_Global::_checkprivileges(SKILLCATEGORY,$loginuserGroup,$loginuserRole,'add');
        $this->view->categorypermission = $categorypermission;
       
        $indexpermission = '';
        $indexpermission = sapp_Global::_checkprivileges(MEASURMENTINDEX,$loginuserGroup,$loginuserRole,'add');
        $this->view->indexpermission = $indexpermission;


        $id = $this->_getParam('id');
        $modelb = new Default_Model_Measurement();
        $add = $modelb->getmeasurementlist();
        $form->measurement_id->addMultiOption(' ', 'Select...');
        if (empty($add)) {
            $msgarray['measurement_id'] = 'Measurement is not configured yet.';
        } else {
            foreach ($add as $adds) {
                $form->measurement_id->addMultiOptions([$adds['measurement_id'] => $adds['name']]);
            }
        }
        $modelc = new Default_Model_Skillcategory();
        $addc = $modelc->getallcategory();
        $form->skillcategory_id->addMultiOption(' ', 'Select...');

        if (empty($addc)) {
            $msgarray['skillcategory_id'] = 'skillcategory is not configured yet.';
        } else {
            foreach ($addc as $addsc) {
                $form->skillcategory_id->addMultiOptions([$addsc['skillcategory_id'] => $addsc['name']]);
            }
        }
        $form->parentskill_id->addMultiOption(' ', 'Select...');
        $pskill=$dmtmodel->getskilllist();

 if (empty($pskill)) {
            $msgarray['parentskill_id'] = 'skill is not configured yet.';
        } else {
            foreach ($pskill as $pskills) {
                $form->parentskill_id->addMultiOptions([$pskills['skill_id'] => $pskills['name']]);
            }
        }

        $dmtdata=$dmtmodel->getskill($id);
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $form->populate($dmtdata);
        $form->setAttrib('action',BASE_URL.'skill/edit/id/'.$id);
        $form->isactive->setValue(utlis::getcheckboxvalue($dmtdata['isactive'],'form'));
        $form->issummary->setValue(utlis::getcheckboxvalue($dmtdata['issummary'],'form'));

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'skillcategory_id'=>$this->_request->getParam('skillcategory_id'),
                    'measurement_id'=>$this->_request->getParam('measurement_id'),
                    'parentskill_id'=>$this->_request->getParam('parentskill_id'),
                    'description'=>$this->_request->getParam('description'),
                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'issummary'=>utlis::getcheckboxvalue($this->_request->getParam('issummary')),

                    'modifiedby'=>$loginUserId,
                    'modifieddate'=>gmdate('Y-m-d H:i:s')
                ];
                $dmtmodel->SaveorUpdateData($data,'skill_id='.$id);
                $tableid = $id;
                $actionflag = 2;
                $result = sapp_Global::logManager(291,$actionflag,$loginUserId,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Skill Updated Successful']);
                $this->_redirect('skill');
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
        $form = new Default_Form_Skill();
        $dmtmodel = new Default_Model_Skill();
        $auth = Zend_Auth::getInstance();
        $id = $this->_getParam('id');
        $dmtdata=$dmtmodel->getskillforview($id);
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $form->populate($dmtdata);
       
     
        $form->isactive->setValue($dmtdata['isactive']);
        $this->view->form = $form; 
        $this->view->isactive = $dmtdata['isactive'];
        $this->view->form = $form;
        $this->view->data = $dmtdata;

        $this->view->id = $id;

        $this->view->controllername='skill';
    }
}
