<?php

class Default_DisabilitytypeController extends Zend_Controller_Action
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
      
      $dmtmodel = new Default_Model_Disabilitytype();
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

    public function addpopupAction()
    {
        
        try {
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");       //code...
      
        $form = new Default_Form_Disabilitytype();
        $dmtmodel = new Default_Model_Disabilitytype();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $id = $this->_getParam('id');
        $form->setAction(BASE_URL.'disabilitytype/addpopup/id/'.$id);
        if ($this->getRequest()->isPost()) {
           
            
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];
                $Id= $dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(331,$actionflag,$loginUserId,$tableid);
               $this->view->popup = 'close';
               $linecategoryData = $dmtmodel->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($linecategoryData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['disabilitytype_id'], $record['name']);
				}
                if ($id==1) {
                    $this->view->nested=true;
                }
                else{
                    $this->view->nested = false;
                }
				$this->view->disabilitytype = $opt;
               $this->view->controllername = 'disabilitytype';
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
        try {
            //code...
        $form = new Default_Form_Disabilitytype();
        $dmtmodel = new Default_Model_Disabilitytype();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
		$id = $this->getRequest()->getParam('id');
		$objName = 'disabilitytype';

        if($id)
			{	//Edit Record...
				
				if(is_numeric($id) && $id>0)
				{
					$data = $dmtmodel->getdisabilitytype($id);
			

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
                        
                        $name = $this->_request->getParam('name');
                        $code = $this->_request->getParam('code');
        
                        $description = $this->_request->getParam('description');
        
                        $date = gmdate("Y-m-d H:i:s");
        
                        $data = [
                                    'name'  => $name,
                                    'code'  => $code,
                                    'description'  => $description,
                                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
        
                                    ];
                        if($id!=''){
        
                            $data['modifiedby'] = $loginUserId;
                            $data['modifieddate'] = $date;
                            $where = ['disabilitytype_id=?'=>$id];
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
                            $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Disability Type updated successfully."]);
                        }
                        else
                        {
                         $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Disability Type added successfully."]);
                        }
                            
                        $this->_redirect('disabilitytype');
                    
                        
                            
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
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }
    
    // public function viewAction()
    // {
    //     $form = new Default_Form_Skillsource();
    //     $dmtmodel = new Default_Model_skillsource();
    //     $auth = Zend_Auth::getInstance();
    //     $id = $this->_getParam('id');
    //     $dmtdata=$dmtmodel->getskillsource($id);
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
