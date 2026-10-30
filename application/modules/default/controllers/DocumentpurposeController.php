<?php

class Default_DocumentpurposeController extends Zend_Controller_Action
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
      
      $dmtmodel = new Default_Model_Documentpurpose();
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
      
        $Form = new Default_Form_Documentpurpose();
        $model = new Default_Model_Documentpurpose();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $id = $this->_getParam('id');
        $Form->setAction(BASE_URL.'documentpurpose/addpopup/id/'.$id);
        if ($this->getRequest()->isPost()) {
           
            
            if ($Form->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name'=>$this->_request->getParam('name'),
                    'code'=>$this->_request->getParam('code'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];
                $Id= $model->SaveorUpdateData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(347,$actionflag,$loginUserId,$tableid);
               $this->view->popup = 'close';
               $linecategoryData = $model->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($linecategoryData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['iddocumentpurpose_id'], $record['name']);
				}
                if ($id==1) {
                    $this->view->nested=true;
                }
                else{
                    $this->view->nested = false;
                }
				$this->view->purpose = $opt;
               $this->view->controllername = 'documentpurpose';
            }
            $messages = $Form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }
        $this->view->msgarray = $msgArray;
        $this->view->form = $Form; 
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
		$objName = 'documentpurpose';
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();

		$Form = new Default_Form_Documentpurpose();
		$Model = new Default_Model_Documentpurpose();
					

		try{
			
			if($id)
			{	//Edit Record...
				
				if(is_numeric($id) && $id>0)
				{
					$data = $Model->getdocumentpurpose($id);
			

					if(!empty($data) && $data != "norows")
					{
						$Form->populate($data[0]);
						$Form->submit->setLabel('Update');
                        $Form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'],'form'));

						$this->view->form = $Form;
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
				$this->view->form = $Form;
				$this->view->inpage = 'Add';
			}
		}
		catch(Exception $e)
		{
			$this->view->ermsg = 'nodata';
		}
		if($this->getRequest()->getPost()){

    	if($Form->isValid($this->_request->getPost())){
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
					$where = ['iddocumentpurpose_id=?'=>$id];
				}
				else
				{
					$data['createdby'] = $loginUserId;
					$data['createddate'] = $date;
					$data['isactive'] = 'Y';
					$where = '';
				}

			$Id = $Model->SaveorUpdateData($data, $where);
				
				if($Id == 'update')
				{
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Document Purpose updated successfully."]);
				}
				else
				{
			     $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Document Purpose added successfully."]);
				}
					
				$this->_redirect('documentpurpose');
			
				
					
			}else{
                $messages = $Form->getMessages();
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
 
    // public function viewAction()
    // {
    //     $Linecate_Form = new Default_Form_Linecategory();
    //     $dmtmodel = new Default_Model_Linecategory();
    //     $auth = Zend_Auth::getInstance();
    //     $id = $this->_getParam('id');
    //     $dmtdata=$dmtmodel->getlinecategory($id);
    //     if ($auth->hasIdentity()) {
    //         $loginUserId = $auth->getStorage()->read()->id;
    //     }
    //     $Linecate_Form->populate($dmtdata);
       
     
    //     $Linecate_Form->isactive->setValue($dmtdata['isactive']);
    //     $this->view->form = $Linecate_Form; 
    //     $this->view->isactive = $dmtdata['isactive'];
    //     $this->view->form = $Linecate_Form;
    //     $this->view->id = $id;

    //     $this->view->controllername='linecategory';
    // }
}
