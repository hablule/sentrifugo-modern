<?php

class Assets_AssettypeController extends Zend_Controller_Action
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
      
      $dmtmodel = new Assets_Model_Assettype();
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
    public function editAction(){
		try {
            //code...
       
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$objName = 'assettype';
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();

		$assetscategoriesForm = new Assets_Form_Assettype();
		$assetcategoriesModel = new Assets_Model_Assettype();
					

		try{
			
			if($id)
			{	//Edit Record...
				
				if(is_numeric($id) && $id>0)
				{
					$data = $assetcategoriesModel->getassettype($id);
				

					if(!empty($data) && $data != "norows")
					{
						$assetscategoriesForm->populate($data[0]);
                        $assetscategoriesForm->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'],'form'));

						$assetscategoriesForm->submit->setLabel('Update');
						$this->view->form = $assetscategoriesForm;
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
				$this->view->form = $assetscategoriesForm;
				$this->view->inpage = 'Add';
			}
		}
		catch(Exception $e)
		{
			$this->view->ermsg = 'nodata';
		}
		if($this->getRequest()->getPost()){

    	if($assetscategoriesForm->isValid($this->_request->getPost())){
				$id = $this->_request->getParam('id');
                
				$name = $this->_request->getParam('name');
				$code = $this->_request->getParam('code');
                $description = $this->_request->getParam('description');

				$date = gmdate("Y-m-d H:i:s");

				$data = array(
							'name'  => $name ,
							'code'  => $code,
                            'description'  => $description,
                            'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),

							);
				if($id!=''){

					$data['modifiedby'] = $loginUserId;
					$data['modifieddate'] = $date;
					$where = array('assettype_id=?'=>$id);
				}
				else
				{
					$data['createdby'] = $loginUserId;
					$data['createddate'] = $date;
					$data['modifieddate'] = $date;
					$where = '';
				}

			$Id = $assetcategoriesModel->SaveorUpdateData($data, $where);
				
				if($Id == 'update')
				{
					$this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Asset Type updated successfully."));
				}
				else
				{
			     $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Asset Type added successfully."));
				}
					
				$this->_redirect('assets/assettype');
			
				
					
			}else{
                $messages = $assetscategoriesForm->getMessages();
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
Zend_Debug::dump($th->getMessage());    }
	}

    public function addpopupAction()
    {
        try {
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");       //code...
      
        $form = new Assets_Form_Assettype();
        $dmtmodel = new Assets_Model_Assettype();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        	 
		 $isfrompopup = $this->getRequest()->getParam('isfrompopup');
         $isfrompopup = !empty($isfrompopup)?$this->getRequest()->getParam('isfrompopup'):'';
         $this->view->isfrompopup=$isfrompopup;
 
        $id = $this->_getParam('id');
        $form->setAction(BASE_URL.'assets/assettype/addpopup/id/'.$id);
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = array(
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),

                    'createddate'=>gmdate('Y-m-d H:i:s')
                );
                $Id= $dmtmodel->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(341,$actionflag,$loginUserId,$tableid);
               $this->view->popup = 'close';
               $linecategoryData = $dmtmodel->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($linecategoryData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['assettype_id'], $record['name']);
				}
                if ($id==1) {
                    $this->view->nested=true;
                }
                else{
                    $this->view->nested = false;
                }
				$this->view->assettype = $opt;
               $this->view->controllername = 'assettype';
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

	public function viewAction(){

		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();
		$objName = 'assettype';

			$assetscategoriesModel = new Assets_Model_Assettype();
		
		try
		{
			if(is_numeric($id) && $id>0)
			{
				$data = $assetscategoriesModel->getassettype($id);
				
				
				if(!empty($data) && $data != "norows")
				{
					
					
					$this->view->id = $id;
					$this->view->controllername=$objName;
					$this->view->ermsg = '';
					$this->view->inpage = 'Edit';
					$this->view->data = $data;
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
		catch(Exception $e)
		{
			$this->view->ermsg = 'nodata';
		}
	}
}
