<?php

class Default_LinetypeController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
      
      $dmtmodel = new Default_Model_Linetype();
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
        try{
        $Linetype_Form = new Default_Form_Linetype();
        $dmtmodel = new Default_Model_Linetype();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        if ($this->getRequest()->isPost()) {
            if ($Linetype_Form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];
                die(json_encode($data));
                $dmtmodel->SaveorUpdateData($data,'');

                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Line Type Added Successful']);
                $this->_redirect('linetype');
            }
            $messages = $Linetype_Form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }
       
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    $this->view->msgarray = $msgArray;
    $this->view->form = $Linetype_Form; 
    }

   
	public function addpopupAction()
	{
        try {
            //code...
      
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->_request->getParam('id');
		$this->view->status=$id;
		$msgarray = [];
		$controllername = 'linetype';
		$form = new Default_Form_Linetype();
		$model = new Default_Model_Linetype();
		$form->setAction(BASE_URL.'linetype/addpopup/id/'.$id);
		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){
				$id = $this->_request->getParam('id');
                $form->id->setValue($id);
				$name = $this->_request->getParam('name');
                $code = $this->_request->getParam('code');

				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = ['name'=>$name,
                                'code'=>$code,
				 				'description'=>$description,
						        'createdby'=>$loginUserId,
								'createddate'=>gmdate("Y-m-d H:i:s")
				];

			
				
					$data['isactive'] = "Y";
					$where = '';
					$actionflag = 1;
                if($id==1)
                {
                    $this->view->nested = true;
                    
                }
                else{
                    $this->view->nested = false;
                }
				$Id = $model->SaveorUpdateData($data, $where);
				$tableid = $Id;
				$result = sapp_Global::logManager(290,$actionflag,$loginUserId,$tableid);
				$linetypeData = $model->fetchAll('isactive = "Y"','name')->toArray();
				$opt =''.sapp_Global::selectOptionBuilder('', 'Select line tyoe');;
				foreach($linetypeData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['linetype_id'], $record['name']);
				}
                
				$this->view->linetypeData = $opt;

				$this->view->eventact = 'added';
				$close = 'close';
				$this->view->popup=$close;
			}else
			{
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
		$this->view->controllername = $controllername;
		$this->view->form = $form;
		$this->view->ermsg = '';
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }

	}
    public function editAction()
    {
       
        require_once 'application/modules/default/library/utilClasses/Util.php';
        $Linetype_Form = new Default_Form_Linetype();
        $dmtmodel = new Default_Model_Linetype();
        $auth = Zend_Auth::getInstance();
        $id = $this->_getParam('id');
        $dmtdata=$dmtmodel->getLinetype($id);
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $Linetype_Form->populate($dmtdata);
        $Linetype_Form->setAttrib('action',BASE_URL.'linetype/edit/id/'.$id);
        $Linetype_Form->isactive->setValue(utlis::getcheckboxvalue($dmtdata['isactive'],'form'));
        if ($this->getRequest()->isPost()) {
            if ($Linetype_Form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'description'=>$this->_request->getParam('description'),
                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'modifiedby'=>$loginUserId,
                    'modifieddate'=>gmdate('Y-m-d H:i:s')
                ];
                // die(json_encode($data));
                $id=$dmtmodel->SaveorUpdateData($data,'linetype_id='.$id);
                $tableid = $id;
                $actionflag = 2;
                // $result = sapp_Global::logManager(290,$actionflag,$loginUserid,$tableid);
                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Line Type Updated Successful']);
                $this->_redirect('linetype');
            }
            $messages = $Linetype_Form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }
        $this->view->msgarray = $msgArray;
        $this->view->form = $Linetype_Form;  
    }
    public function viewAction()
    {
        $Linetype_Form = new Default_Form_Linetype();
        $dmtmodel = new Default_Model_Linetype();
        $auth = Zend_Auth::getInstance();
        $id = $this->_getParam('id');
        $dmtdata=$dmtmodel->getLinetype($id);
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $Linetype_Form->populate($dmtdata);
       
     
        $Linetype_Form->isactive->setValue($dmtdata['isactive']);
        $this->view->form = $Linetype_Form; 
        $this->view->isactive = $dmtdata['isactive'];
        $this->view->form = $Linetype_Form;
        $this->view->id = $id;

        $this->view->controllername='linetype';
    }
}
