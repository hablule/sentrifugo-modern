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

class Default_PerkapproveController extends Zend_Controller_Action
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
        try {
       

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $perkmodel = new Default_Model_Perkapprove();
        $access = Zend_Session::namespaceGet('access_control');
        $empids = $access['empids'];
        $perm = $access['perm'];

        $this->view->perm = $perm;
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
            $by = 'ep.emp_id';
            
            $searchData = '';
            $searchQuery = '';
            $sort2 = 'DESC';
            $searchArray = array();
            $searchArray2 = array();
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
          
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ep.emp_id';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else $perPage = $this->_getParam('per_page', PERPAGE);
            $searchData = $this->_getParam('searchData');
            $pageNo = $this->_getParam('page',1);
        }
       
        $dataTmp = $perkmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall,$empids,$perm);
        $this->view->dataArray = $dataTmp;

        array_push($data, $dataTmp);
        $this->view->call = $call;
        $this->view->objectName = $objname;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
             //code...
            } catch (\Throwable $th) {
                Zend_Debug::dump($th->getMessage());
            }
    }
   	
	public function viewAction()
	{	

	    $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
		}
		$id =$this->getRequest()->getParam('id');
    $form=new Default_Form_perkapprove();

        $form->setAttrib('action',BASE_URL.'perkapprove/edit/id/'.$id);
        $perkModal = new Default_Model_Perkapprove();
        $empperk = $perkModal->perkdetail($id);  
        //die(json_encode($empperk));
        $this->view->empperks = $empperk;
        $this->view->form = $form;
	}
		
    public function editpopupAction()
	{	
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$controllername = 'perkapprove';
        $messagestr = '';
        $successmessagestr = '';
	    $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
		}

		$id =$this->getRequest()->getParam('id');
        $form = new Default_Form_perkapprove();

        $form->setAttrib('action',BASE_URL.'perkapprove/editpopup/id/'.$id);
        $perkModal = new Default_Model_Perkapprove();

        $empperk = $perkModal->perkdetail($id);
$popo=$empperk[0];
        //die(json_encode($empperk));
        $type =$empperk[0]['type'];
        if($type=='Percentage')
{
    $emp=$empperk[0]['emp_id'];
    $salarymodel = $perkModal->getsalary($emp);
    if(empty($salarymodel)){
        $salary=0; 
    }
    else{
        $salary= $salarymodel[0]['amount'];

    }
          //  die(json_encode($salarymodel));

    $percent =$empperk[0]['amount'];
    $amountapproved=($salary*$percent)/100;
}else{
    $amountapproved =$empperk[0]['amount'];

}
     

        $form->approvedamount->setvalue($amountapproved);
        $state=$this->getRequest()->getParam('managerstatus');
         if ($this->getRequest()->getPost()) {
             if($form->isValid($this->_request->getpost())){

            
                $where = array('employeeperk_id=?'=>$id);
                $data = array(
               
                    'modifiedby'=>$loginUserId,
                    'modifieddate'=>gmdate("Y-m-d H:i:s")
                );
                if(isset($_POST['approvedamount']))
                {
                $amount=$this->getRequest()->getParam('approvedamount');
    $data['amount_approved']=$amount;
                }
                if(isset($_POST['managerstatus']))
                {
                $approved=$this->getRequest()->getParam('managerstatus');
    $data['approved']=$approved;
    
                }
                $perkModal->editempperk($data,$where);
                if($approved=='Y'){
                    $this->view->eventact = 'Approved';

                }else if($approved=='N'){
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
        $previlage = sapp_Global::permemp($popo['emp_id']);
        // die($previlage);
$this->view->previlage=$previlage;
            $this->view->empperks = $empperk;
        $this->view->form = $form;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
            $this->view->call = $this->_getParam('call');
        $this->view->controllername = $controllername;
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");

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
			$model = new Default_Model_Perkapprove();

			
			$where = array('employeeperk_id=?' => $id);
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

