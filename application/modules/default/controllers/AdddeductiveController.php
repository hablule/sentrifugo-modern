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
require_once 'UserdefinedclassController.php';

class Default_AdddeductiveController extends Zend_Controller_Action
{

    private $options;
    public function preDispatch()
    {
    }

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }
    public function deductiveAction()
    {
        $util = new utlis();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;
        }
            $loadaddpermission = sapp_Global::_checkprivileges(ADDDEDUCTIVE,$loginuserGroup,$loginuserRole,'add');
            
            $this->view->loadaddpermission = $loadaddpermission;

        $form = new Default_Form_deductive();
        $method_form = new Default_Form_userdefinedclass();
        $formModel = new Default_Model_Deductive();
        $form->setAttrib('action', BASE_URL . 'adddeductive/deductive');
        $deductive_categories = $formModel->getCategory();
        foreach ($deductive_categories as $category) {
            $form->category->addMultiOption($category['load_category_id'], $category['name']);
        }
        $filter = ['argNo' => ['min' => 0, 'max' => 2], 'returnType' => 'String'];
        $classes = Default_UserdefinedclassController::getAllClasses($filter);


        $method_form->classfun->addMultiOption('empty', "Select...");
        foreach ($classes as $class) {
            foreach ($class['methods'] as $method) {
                $method_form->classfun->addMultiOption($method["name"], $method["name"]);
            }
        }
        if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){
                $data = [
                    'code' => $this->getRequest()->getParam('code'),
                    'name' => $this->getRequest()->getParam('name'),
                    'load_category_id' => (int)$this->getRequest()->getParam('category'),
                    'istax' => $util->getcheckboxvalue($this->_request->getParam('istax')),
                    'ispenality' => $util->getcheckboxvalue($this->_request->getParam('ispenality')),
                    'autoapprove' => $util->getcheckboxvalue($this->_request->getParam('autoapprove')),
                    'description' => $this->getRequest()->getParam('description'),
                    'start' => sapp_Global::change_date($this->getRequest()->getParam('start_date'), 'database'),
                    'end' => sapp_Global::change_date($this->getRequest()->getParam('end_date'), 'database'),
                    'type' => $this->getRequest()->getParam('type'),
                    'amount' => doubleval($this->getRequest()->getParam('amount')),
                    'classPath' => $this->getRequest()->getParam('classPath'),
                    'method' => $this->getRequest()->getParam('classfun'),
                    'parameters' => trim($this->getRequest()->getParam('parameters'), ','),
                    'createdby' => $loginUserId,
                    'createddate' => gmdate("Y-m-d H:i:s"),
                ];
                $data['isactive'] = 'Y';
                $tableid=$formModel->addData($data);
                $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Load Added successfully."]);
                $this->view->messages = $this->_helper->flashMessenger->getMessages();
                
                $result = sapp_Global::logManager(DEDUCTIVE,1,$loginUserId,$tableid);
        
                $this->_redirect('adddeductive');

            }   else   {
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

        $this->view->form = $form;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->method_form = $method_form;
     
        $this->view->classes = $classes;

   
    }
    public function  addcategoryAction()
    {
        try {
          
        $form = new Default_Form_deductivecategory();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $formModel = new Default_Model_Deductive();
        $form->setAction(BASE_URL . 'adddeductive/addcategory');
        $auth = Zend_Auth::getInstance();
        $loginUserId = $auth->getStorage()->read()->id;
        if ($this->getRequest()->isPost()) {

            if ($form->isValid($this->getRequest()->getPost())) {

                $data = [
                    'name' => $this->_request->getParam('name_cat'),
                    'description' => $this->_request->getParam('description_cat'),
                    'isactive' => 'Y',

                ];
                
                //  die(json_encode($data));
                $tableid = $formModel->addCategory($data);

                $categoryData = $formModel->fetchAll('isactive="Y"','name')->toArray();
               
                $result = sapp_Global::logManager(DEDUCTIVE,1,$loginUserId,$tableid);
                $opt = '';
               
                foreach ($categoryData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['load_category_id'], $record['name']);
                }
                $this->view->categoryData = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
               
                $this->view->popup = $close;
            }
            $messages = $form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }

        $controllername = 'adddeductive';
        $this->view->msgarray = $msgArray;
        $this->view->form = $form;
        $this->view->controllername = $controllername;
          //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function editAction()
    {
        $util = new utlis();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;
        }
            $loadaddpermission = sapp_Global::_checkprivileges(ADDDEDUCTIVE,$loginuserGroup,$loginuserRole,'add');
            
            $this->view->loadaddpermission = $loadaddpermission;
        $id = (int)$this->getRequest()->getParam('id');
        $form = new Default_Form_deductive();
        $form->code->removeValidator('Db_NoRecordExists');
		$form->name->removeValidator('Db_NoRecordExists');
        $formModel = new Default_Model_Deductive();
        $form->setAttrib('action', BASE_URL . 'adddeductive/edit');
        $method_form = new Default_Form_userdefinedclass();
        $filter = ['argNo' => ['min' => 0, 'max' => 2], 'returnType' => 'String'];
        $classes = Default_UserdefinedclassController::getAllClasses($filter);
        $this->view->method_form = $method_form;
        $this->view->classes = $classes;

        $method_form->classfun->addMultiOption('empty', "Select...");
        foreach ($classes as $class) {
            foreach ($class['methods'] as $method) {
                $method_form->classfun->addMultiOption($method["name"], $method["name"]);
            }
        }
        $loadData = $formModel->getSingleDeductive($id);
        $method_form->classfun->setvalue($loadData[0]['method']);
        $params = $loadData[0]['parameters'];
        $this->view->params = $params;
        $perk_categories = $formModel->getCategory();
        $form->category->addMultiOption('0', 'Select...');
        foreach ($perk_categories as $category) {
            $form->category->addMultiOption($category['load_category_id'], $category['name']);
        }
        //die(json_encode($loadData));
        $form->id->setValue($loadData[0]['load_id']);
        $form->type->setValue($loadData[0]['type']);
        $form->name->setValue($loadData[0]['name']);
        $form->category->setValue($loadData[0]['load_category_id']);
        $form->type->setValue($loadData[0]['type']);
        $form->description->setValue($loadData[0]['description']);
        $form->start_date->setValue($loadData[0]['start']);
        $form->end_date->setValue($loadData[0]['end']);
        $form->amount->setValue($loadData[0]['amount']);
        $form->code->setValue($loadData[0]['code']);
        $form->ispenality->setValue($loadData[0]['ispenality']);
        $form->istax->setValue($loadData[0]['istax']);
        $form->autoapprove->setValue($loadData[0]['autoapprove']);
        $form->isactive->setValue(utlis::getcheckboxvalue($loadData[0]['isactive'], 'form'));
        $form->submit->setLabel('Update');

        $this->view->form = $form;


        if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){
            //die(json_encode($this->getRequest()->getPost()));
            $where = ['load_id=?' => $this->getRequest()->getParam('id')];
            $data = [
                'code' => $this->getRequest()->getParam('code'),
                'name' => $this->getRequest()->getParam('name'),
                'load_category_id' => (int)$this->getRequest()->getParam('category'),
                'istax' => $util->getcheckboxvalue($this->_request->getParam('istax')),
                'ispenality' => $util->getcheckboxvalue($this->_request->getParam('ispenality')),
                'autoapprove' => $util->getcheckboxvalue($this->_request->getParam('autoapprove')),
                'description' => $this->getRequest()->getParam('description'),
                'start' => sapp_Global::change_date($this->getRequest()->getParam('start_date'), 'database'),
                'end' => sapp_Global::change_date($this->getRequest()->getParam('end_date'), 'database'),
                'type' => $this->getRequest()->getParam('type'),
                'amount' => doubleval($this->getRequest()->getParam('amount')),
                'classPath' => $this->getRequest()->getParam('classPath'),
                'method' => $this->getRequest()->getParam('classfun'),
                'parameters' => trim($this->getRequest()->getParam('parameters'), ','),
                'modifiedby' => $loginUserId,
                'modifieddate' => gmdate("Y-m-d H:i:s"),
                'isactive' => utlis::getcheckboxvalue($this->getRequest()->getParam('isactive')),
            ];
            //$data['isactive'] = 'Yes';
            // die(json_encode($data));
            $tableid = $this->getRequest()->getParam('id');
            $result = sapp_Global::logManager(DEDUCTIVE,2,$loginUserId,$tableid);
            $formModel->editDeductive($data, $where);
            $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Load updated successfully."]);
            $this->_redirect('adddeductive');
        }
        else   {
            $messages = $form->getMessages();
            foreach ($messages as $key => $val)
            {
                foreach($val as $key2 => $val2)
                {
                    $msgarray[$key] = $val2;
                                   
            }
            $this->view->msgarray = $msgarray;
        }
    }
    }
}
public function viewAction()
{
    $id = (int)$this->getRequest()->getParam('id');
    $form = new Default_Form_deductive();
    $form->setAttrib('action', BASE_URL . 'adddeductive');
    $deductiveModal = new Default_Model_Deductive();
    $empDeductive = $deductiveModal->getDeductiveDetailview($id);
    $form = new Default_Form_deductive();
    $form->back->setLabel('Back');
    $this->view->empDeductives = $empDeductive;
    $this->view->form = $form;
    //die(json_encode($empDeductive));   
}
    public function indexAction()
    {
        try {
            
        
        $payrollmodel = new Default_Model_Adddeductive();
        $empmodel = new Default_Model_Emplistdeductive();
        $form = new Default_Form_adddeductive();
        $inter_options = [];
        $call = $this->_getParam('call');
        if ($this->_request->isPost()) {

            if (isset($_POST['deductive_ids'])) {
                $deductive =  $_POST['deductive_ids'];
                //die($perk);
                $empid = $payrollmodel->getEmpIddeductive($deductive);
                die(json_encode($empid));
            }
        }

        if ($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();

        $view = Zend_Layout::getMvcInstance()->getView();
        $objname = $this->_getParam('objname');
        $refresh = $this->_getParam('refresh');
        $data = [];
        $data2 = [];
        $searchQuery2 = '';
        $searchArray2 = [];

        $searchQuery = '';
        $searchArray = [];
        $tablecontent = '';
        $tablecontent2 = '';
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $dashboardcall = $this->_getParam('dashboardcall');
        $access = Zend_Session::namespaceGet('access_control');
        $empids = $access['empids'];
        $perm = $access['perm'];
        $limit=LIMIT;
        $offset=0;
        $search_val = $this->_request->getParam('search_val',null);
        $search_str = $this->_request->getParam('search_str',null);
        $role_id = $this->_request->getParam('role_id',null);
     
        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes'){
                $perPage = DASHBOARD_PERPAGE;
            }
            else{
                $perPage = PERPAGE;
                }

            $sort = 'DESC';
            $by = 'p.name';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $sort2 = 'DESC';
            $by2 = 'e.id';
            $pageNo2 = 1;
            $searchData2 = '';
            $searchQuery2 = '';

            $searchArray = [];
            $searchArray2 = [];
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $sort2 = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'p.name';
            $by2 = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'e.id';

            if ($dashboardcall == 'Yes'){
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);}
            else {
                $perPage = $this->_getParam('per_page', PERPAGE);
                //$perPage =5;
            }
           // $perPage = 5;
            $searchData = $this->_getParam('searchData');
        }
      
        if ($call == 'ajaxcall') {
            if ($objname == 'adddeductive') {
                $dataTmp = $payrollmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
                $this->view->dataArray = $dataTmp;
            } else if ($objname == 'overtime') {
                $dataTmp2=$empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '','',$loginUserId,$limit,$offset,$search_val,$search_str,$role_id,$empids,$perm);     
                // $dataTmp2 = $empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData, $call, $dashboardcall, $empids, '', '', '',$perm);
                $this->view->dataArray2 = $dataTmp2;
            }
        } else {
            $dataTmp = $payrollmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
            $dataTmp2=$empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '','',$loginUserId,$limit,$offset,$search_val,$search_str,$role_id,$empids,$perm);     
            // $dataTmp2 = $empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData, $call, $dashboardcall, $empids, '', '', '',$perm);

            $this->view->dataArray = $dataTmp;
            $this->view->dataArray2 = $dataTmp2;
        }
        array_push($data, $dataTmp);
        array_push($data, $dataTmp2);
        $this->view->call = $call;
        $this->view->perm = $perm;
        $this->view->objectName = $objname;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->form = $form;
        //code...
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }
    public function addAction()
    {
     
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
		}
            $payrollmodel = new Default_Model_Adddeductive();

             $temp_id = $_POST['emp_ids'];
        $temp_deductives_id = $_POST['deductive_ids'];
        $temp_deductives_autos = $_POST['deductive_autos'];
        $datas = [];
        foreach ($temp_id as $id) {

            foreach ($temp_deductives_id as $deductive_id) {
                foreach ($temp_deductives_autos as $deductive_auto) {

                $data = [];
                $amounts=$payrollmodel->getloadamount((int)$deductive_id);

                $type=$payrollmodel->getloadtype((int)$deductive_id);
                $type=$type[0];

                if($type['type']=='Percentage'){
  
                    $salarymodel=$payrollmodel->getsalary($id);
                    $salary=$salarymodel[0]['amount'];
                    $percent=$amounts[0]['amount'];
                    $ammountaaproved=($salary * $percent)/100;
                    
                    if($type['auto_approved']=='Y'){
                        $data['amount_approved'] =$ammountaaproved;
                
                    }else{
                        $data['amount_approved'] =0;
                
                    }
                
                }else{
                    $ammountaaproved=$amounts[0]['amount'];
                
                } 
                $data['emp_id'] = (int) $id;
                $data['load_id'] = (int)$deductive_id;
                $data['amount'] = $ammountaaproved;
                $data['modifiedby'] = $loginUserId;  
                $data['modifieddate'] = gmdate("Y-m-d H:i:s"); 
                if($deductive_auto=="Y"){
                    $data['amount_approved'] =$ammountaaproved;
                }
                $data['approved'] = $deductive_auto;

                array_push($datas, $data);

            }

        
        }
        
        
    }
          // die(json_encode($data));
            $payrollmodel->saveMultiEmpLoad($datas);
            return $this->_helper->_json(['sucess'=>'Load Assigned Successfully']);
        
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
			$model = new Default_Model_Deductive();

		
			$where = ['load_id=?' => $id];
			$Id = $model->DeleteData($where);
			if ($Id == 'delete') {
				$messages['message'] = 'Load deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Load  cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Load cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		if ($deleteflag == 1) {
			if ($messages['msgtype'] == 'error') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(["error" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag]);
			}
			if ($messages['msgtype'] == 'success') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(["success" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag]);
			}
		}
		$this->_helper->json($messages);
	} // end of delete action
    // public function deleteAction()
    // {
    //     if (isset($_POST['objid'])) {
    //         $objId = (int)$_POST['objid'];
    //         $dedmodel = new Default_Model_Deductive();

    //         $dedmodel->deleteded($objId);
    //         $response = array(
    //             'message' => 'Load Successfully Removed.',
    //             'msgtype' => 'success'
    //         );
    //         die(json_encode($response));
    //     } else {
    //         //$data = array('fe'=>'fa');
    //         //die(json_encode($data));
    //     }
    // }

}
