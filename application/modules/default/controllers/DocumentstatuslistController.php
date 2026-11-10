<?php

class Default_DocumentstatuslistController extends Zend_Controller_Action
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
      
      $dmtmodel = new Default_Model_Documentstatuslist();
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
          $by = 'value';
          $pageNo = 1;
          $searchData = '';
      }
      else{
          $sort = ($this->_getParam('sort')!='') ? $this->_getParam('sort'):'DESC';
          $by = ($this->_getParam('by')!='') ? $this->_getParam('by'):'value';
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
    //code...

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $form = new Default_Form_Documentstatuslist();
        $form->setAttrib('action', BASE_URL . 'documentstatuslist/addpopup');
        if ($this->getRequest()->getPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $model = new Default_Model_Documentstatuslist();
                $data = [
                    'iddocumentstatus_id'=>$this->_request->getParam('iddocumentstatus_id'),
                    'value'=>$this->_request->getParam('value'),
                    'description'=>$this->_request->getParam('description'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];

                $tableid=$model->SaveorUpdateData($data, '');
                sapp_Global::logManager(346,1,$loginUserId,$tableid);
                $currencyData = $model->fetchAll('isactive = "Y"', 'value')->toArray();
                $opt = '';
                foreach ($currencyData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['iddocumentstatuslist_id'], $record['value']);
                }
                $this->view->statuslist = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->view->controllername = "documentstatuslist";
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
    public function editAction(){
		try {
            //code...
       
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $statuspermission = '';
        $statuspermission = sapp_Global::_checkprivileges(DOCUMENTSTATUS,$loginuserGroup,$loginuserRole,'add');
        $this->view->statuspermission = $statuspermission;
		$objName = 'documentstatuslist';
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();

		$Form = new Default_Form_Documentstatuslist();
		$Model = new Default_Model_Documentstatuslist();
					

		try{
			
			if($id)
			{	//Edit Record...
				
				if(is_numeric($id) && $id>0)
				{
					$data = $Model->getstatuslist($id);
			

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
                
				$iddocumentstatus_id = $this->_request->getParam('iddocumentstatus_id');
				$value = $this->_request->getParam('value');
                $description = $this->_request->getParam('description');

				$date = gmdate("Y-m-d H:i:s");

				$data = [
                    'iddocumentstatus_id'  => $iddocumentstatus_id ,

							'value'  => $value,
                            'description'  => $description,
                            'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),

							];
				if($id!=''){

					$data['modifiedby'] = $loginUserId;
					$data['modifieddate'] = $date;
					$where = ['iddocumentstatuslist_id=?'=>$id];
				}
				else
				{
					$data['createdby'] = $loginUserId;
					$data['createddate'] = $date;
					$data['modifieddate'] = $date;
					$data['isactive'] = 'Y';
					$where = '';
				}

			$Id = $Model->SaveorUpdateData($data, $where);
				
				if($Id == 'update')
				{
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Document Status List updated successfully."]);
				}
				else
				{
			     $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Document Status List added successfully."]);
				}
					
				$this->_redirect('documentstatuslist');
			
				
					
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
