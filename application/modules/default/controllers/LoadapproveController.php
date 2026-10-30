<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_LoadapproveController extends Zend_Controller_Action
{

    private $options;
	public function preDispatch()
	{
		 
		
	}
	
    public function init()
    {
        $this->_options= $this->getInvokeArg('bootstrap')->getOptions();
		
    }


    public function indexAction()
    {

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $access = Zend_Session::namespaceGet('access_control');
        $empids = $access['empids'];
        $perm = $access['perm'];

   $this->view->perm = $perm;
        $loadmodel = new Default_Model_Loadapprove();
        $inter_options = array();
        $call = $this->_getParam('call');
        if ($call == 'ajaxcall'){
            $this->_helper->layout->disableLayout();
        }
        $view = Zend_Layout::getMvcInstance()->getView();
        $objname = $this->_getParam('objname');
        $refresh = $this->_getParam('refresh');
        $data = array();
        $searchQuery = '';
        $searchArray = array();
        $tablecontent = '';
        

        $dashboardcall = $this->_getParam('dashboardcall');

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes')
                $perPage = DASHBOARD_PERPAGE;
            else
                $perPage = PERPAGE;

            $sort = 'DESC';
            $by = 'el.emp_id';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $sort2 = 'DESC';
            $searchArray = array();
            $searchArray2 = array();
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
          
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'el.emp_id';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else $perPage = $this->_getParam('per_page', PERPAGE);
            $searchData = $this->_getParam('searchData');
            $pageNo = $this->_getParam('page',1);
        }
       
        $dataTmp = $loadmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall,$empids,$perm);
        $this->view->dataArray = $dataTmp;

        array_push($data, $dataTmp);
        $this->view->call = $call;
        $this->view->objectName = $objname;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
   	
	public function viewAction()
	{	

	    $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
		}
		$id =$this->getRequest()->getParam('id');
$form=new Default_Form_loadapprove();

        $form->setAttrib('action',BASE_URL.'loadapprove/edit/id/'.$id);
        $loadModal = new Default_Model_Loadapprove();
        $empload = $loadModal->loaddetail($id);  
        //die(json_encode($empperk));
        $this->view->emploads = $empload;
        $this->view->form = $form;
	}
		
   		
    public function editpopupAction()
	{	
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");

		$controllername = 'loadapprove';
        $messagestr = '';
        $successmessagestr = '';
	    $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
		}

		$id =$this->getRequest()->getParam('id');
        $form = new Default_Form_loadapprove();

        $form->setAttrib('action',BASE_URL.'loadapprove/editpopup/id/'.$id);
        $loadModal = new Default_Model_Loadapprove();
        $empload = $loadModal->loaddetail($id);
        $type =$empload[0]['type'];
        if($type=='Percentage')
{
    $emp=$empload[0]['emp_id'];
    $salarymodel = $loadModal->getsalary($emp);
    if(empty($salarymodel)){
        $salary=0; 
    }
    else{
        $salary= $salarymodel[0]['amount'];

    }
          //  die(json_encode($salarymodel));

    $percent =$empload[0]['amount'];

    $amountapproved=($salary*$percent)/100;
}else{
    $amountapproved =$empload[0]['amount'];

}
        //die(json_encode($empload));
     

        $form->approvedamount->setvalue($amountapproved);
        if($this->getRequest()->getPost()){

			if($form->isValid($this->_request->getPost())){
               
                $where = array('employeeload_id=?'=>$id);
                $data = array(
                    'modifiedby'=>$loginUserId,
                    'modifieddate'=>gmdate("Y-m-d H:i:s")
                );
                if(isset($_POST['approvedamount']))
                {
                $amount=$this->getRequest()->getParam('approvedamount');
    $data['amount_approved']=$amount;
    
                }
                {
                    $approved=$this->getRequest()->getParam('managerstatus');
        $data['approved']=$approved;
        
                    }
                $loadModal->editempload($data,$where);
                $tableid = $this->_getParam('id');
               // $result = sapp_Global::logManager(245,2,$loginUserId,$tableid);
               if($approved=='Y'){
                $this->view->eventact = 'Approved';

               }elseif($approved=='N'){
                $this->view->eventact = 'Rejected';
               }else{

               }
                $close = 'close';
                $this->view->popup=$close;
          
             
            }

                else
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
        $previlage = sapp_Global::permemp($emp);
$this->view->previlage=$previlage;

            $this->view->emploads = $empload;
        $this->view->form = $form;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
      
        $this->view->controllername = $controllername;

	}
    public function deleteAction()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$id = $this->_request->getParam('objid');
		$deleteflag = $this->_request->getParam('deleteflag');
		$messages['message'] = '';
		$messages['msgtype'] = "";
		$actionflag = 3;
		if ($id) {
			$model = new Default_Model_Loadapprove();

			
			$where = array('employeeload_id=?' => $id);
			$Id = $model->DeleteData($where);
			if ($Id == 'delete') {
				$messages['message'] = 'Perk Request deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Perk Request cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Perk Request cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		if ($deleteflag == 1) {
			if ($messages['msgtype'] == 'error') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("error" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
			}
			if ($messages['msgtype'] == 'success') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
			}
		}
		$this->_helper->json($messages);
        $this->view->messages = $this->_helper->flashMessenger->getMessages();

	} // end of delete action


}

