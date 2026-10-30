<?php

use function PHPSTORM_META\type;

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

class Default_AddperkController extends Zend_Controller_Action
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
    public function perkAction()
    {
        // $data = array();

        // $dataTmp = $cand_model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call,$dashboardcall,'','','','');

        // array_push($data,$dataTmp);
        // $this->view->dataArray = $dataTmp;
        $util = new utlis();

            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
                $loginuserRole = $auth->getStorage()->read()->emprole;
                $loginuserGroup = $auth->getStorage()->read()->group_id;
            }
                $perkaddpermission = sapp_Global::_checkprivileges(ADDPERK,$loginuserGroup,$loginuserRole,'add');
                
                $this->view->perkaddpermission = $perkaddpermission;
        $form = new Default_Form_perk();
        $method_form = new Default_Form_userdefinedclass();
        $formModel = new Default_Model_Perk();
        $form->setAttrib('action', BASE_URL . 'addperk/perk');
        $perk_categories = $formModel->getCategory();
        $form->category->addMultiOption('', 'Select...');
        foreach ($perk_categories as $category) {
            $form->category->addMultiOption($category['perkcategory_id'], $category['name']);
        }
        $filter = ['argNo' => ['min' => 0, 'max' => 3], 'returnType' => 'String'];
        $classes = Default_UserdefinedclassController::getAllClasses($filter);


        $method_form->classfun->addMultiOption('empty', "Select...");
        foreach ($classes as $class) {
            foreach ($class['methods'] as $method) {
                $method_form->classfun->addMultiOption($method["name"], $method["name"]);
            }
        }

        if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){

                $data = array(
                    'code' => $this->getRequest()->getParam('code'),
                    'name' => $this->getRequest()->getParam('name'),
                    'category' => (int)$this->getRequest()->getParam('category'),
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
                    'istaxable' => $util->getcheckboxvalue($this->_request->getParam('taxable')),
                    'auto_approved' => $util->getcheckboxvalue($this->_request->getParam('auto_approved'))
                );
        
                $data['isactive'] = 'Y';
              // die(json_encode($data));
                $tableid=$formModel->addData($data);
                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Perk   added successfully."));
                $this->view->messages = $this->_helper->flashMessenger->getMessages();

                $result = sapp_Global::logManager(229,1,$loginUserId,$tableid);
                $this->_redirect('addperk');
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

        // $form->type->addMultiOption('0', 'Select...');
        // $form->type->addMultiOption('Individual', 'Individual');
        // $form->type->addMultiOption('Percentage', 'Percentage');
        // $form->type->addMultiOption('Fixed', 'Fixed');
        // $form->type->addMultiOption('Calculated', 'Calculated');
        // $form->type->addMultiOption('Spot', 'Spot');


        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->form = $form;
        $this->view->method_form = $method_form;
        $this->view->classes = $classes;
    }


    public function  addcategoryAction()
    {
        
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $form = new Default_Form_perkcategory();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $formModel = new Default_Model_Perk();
        $form->setAction(BASE_URL . 'addperk/addcategory');
        $auth = Zend_Auth::getInstance();
        if ($this->getRequest()->isPost()) {

            if ($form->isValid($this->getRequest()->getPost())) {

                $data = array(
                    'name' => $this->_request->getParam('name_cat'),
                    'description' => $this->_request->getParam('description_cat'),
                    'isactive' => 'Y',

                );
                //  die(json_encode($data));
                $tableid=$formModel->addCategory($data);
                $result = sapp_Global::logManager(229,1,$loginUserId,$tableid);
                $categoryData = $formModel->getCategory();

                $opt = '';
                foreach ($categoryData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['perkcategory_id'], $record['name']);
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

        $controllername = 'addperk';
        $this->view->msgarray = $msgArray;
        $this->view->form = $form;
      

        $this->view->controllername = $controllername;
    }
    // public function addcategoryAction()
    // {
    //     $formModel = new Default_Model_Perk();
    //     if (isset($_POST['data'])) {
    //         $temp_data = explode("&", $_POST['data']);
    //         $data = array();
    //         foreach ($temp_data as $temp) {
    //             $temp = explode("=", $temp);
    //             $data[$temp[0]] = $temp[1];
    //         }
    //         $data['isactive'] = 'Yes';
    //         $formModel->addCategory($data);
    //         $perk_categories = $formModel->getCategory();
    //         die(json_encode($perk_categories));
    //     } else {
    //         die(json_encode('Error'));
    //     }
    // }

    public function editAction()
    {
        $util = new utlis();
     
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;
        }
            $perkaddpermission = sapp_Global::_checkprivileges(ADDPERK,$loginuserGroup,$loginuserRole,'add');
            
            $this->view->perkaddpermission = $perkaddpermission;
      
        $id = (int)$this->getRequest()->getParam('id');
        $method_form = new Default_Form_userdefinedclass();
        $filter = ['argNo' => ['min' => 0, 'max' => 3], 'returnType' => 'String'];
        $classes = Default_UserdefinedclassController::getAllClasses($filter);
        $this->view->method_form = $method_form;
        $this->view->classes = $classes;

        $method_form->classfun->addMultiOption('empty', "Select...");
        foreach ($classes as $class) {
            foreach ($class['methods'] as $method) {
                $method_form->classfun->addMultiOption($method["name"], $method["name"]);
            }
        }
        $form = new Default_Form_perk();
        $form->code->removeValidator('Db_NoRecordExists');
		$form->name->removeValidator('Db_NoRecordExists');
        $formModel = new Default_Model_Perk();
        $form->setAttrib('action', BASE_URL . 'addperk/edit');
        $perkData = $formModel->getSinglePerk($id);
        $method_form->classfun->setvalue($perkData[0]['method']);
        $params = $perkData[0]['parameters'];
        $this->view->params = $params;
        $perk_categories = $formModel->getCategory();
        $form->category->addMultiOption('0', 'Select...');
        foreach ($perk_categories as $category) {
            $form->category->addMultiOption($category['perkcategory_id'], $category['name']);
        }

        $form->type->addMultiOption('0', 'Select...');
        $form->type->addMultiOption('Individualized', 'Individualized');
        $form->type->addMultiOption('Percentage', 'Percentage');
        $form->type->addMultiOption('Fixed', 'Fixed');
        $form->type->addMultiOption('Calculated', 'Calculated');
        $form->id->setValue($perkData[0]['perk_id']);
        $form->name->setValue($perkData[0]['name']);
        $form->category->setValue($perkData[0]['category']);
        $form->type->setValue($perkData[0]['type']);
        $form->description->setValue($perkData[0]['description']);
        $form->start_date->setValue($perkData[0]['start']);
        $form->end_date->setValue($perkData[0]['end']);
        $form->amount->setValue($perkData[0]['amount']);
        $form->code->setValue($perkData[0]['code']);
        $form->auto_approved->setValue($perkData[0]['auto_approved']);
        $form->taxable->setValue($perkData[0]['istaxable']);
        $form->isactive->setValue(utlis::getcheckboxvalue($perkData[0]['isactive'],'form'));
        $form->submit->setLabel('Update');

        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->form = $form;
        $this->view->params= $params;
        
         if ($this->getRequest()->getPost()){
        if($form->isValid($this->_request->getPost())){
            $tableid=$this->getRequest()->getParam('id');
            $result = sapp_Global::logManager(229,2,$loginUserId,$tableid);
            //die(json_encode($this->getRequest()->getPost()));
            $where = array('perk_id=?' => $this->getRequest()->getParam('id'));
            $data = array(
                'code' => $this->getRequest()->getParam('code'),
                'name' => $this->getRequest()->getParam('name'),
                'category' => (int)$this->getRequest()->getParam('category'),
                'description' => $this->getRequest()->getParam('description'),
                'start' => sapp_Global::change_date($this->getRequest()->getParam('start_date'), 'database'),
                'end' => sapp_Global::change_date($this->getRequest()->getParam('end_date'), 'database'),
                'type' => $this->getRequest()->getParam('type'),
                'amount' => doubleval($this->getRequest()->getParam('amount')),
                'classPath' => $this->getRequest()->getParam('classPath'),
                'method' => $this->getRequest()->getParam('classfun'),
                'parameters' => trim($this->getRequest()->getParam('parameters'), ','),
                'istaxable' => $util->getcheckboxvalue($this->_request->getParam('taxable')),
                'auto_approved' => $util->getcheckboxvalue($this->_request->getParam('auto_approved')),
                'modifiedby' => $loginUserId,
                'modifieddate' => gmdate("Y-m-d H:i:s"),
                'isactive' => utlis::getcheckboxvalue($this->getRequest()->getParam('isactive'),'database'),
            );
            //$data['isactive'] = 'Yes';
            //die(json_encode($data));
            $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Perk updated successfully."));
            $this->view->messages = $this->_helper->flashMessenger->getMessages();

            $formModel->editPerk($data, $where);
            $this->_redirect('addperk');
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
			$model = new Default_Model_Perk();

		
			$where = array('perk_id=?' => $id);
			$Id = $model->DeleteData($where);
			if ($Id == 'delete') {
				$messages['message'] = 'Perk deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Perk  cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Perk cannot be deleted.';
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
	} // end of delete action
    // public function deleteAction()
    // {
    //     $id = $this->getRequest()->getParam('perk_id');
    //     $formModel = new Default_Model_Perk();
    //     $where = array('perk_id=?' => $id);
    //     $data = array(
    //         'isactive' => 'No'
    //     );
    //     $formModel->deletePerk($data, $where);
    //     $this->_helper->getHelper("FlashMessenger")->addMessage(array('messages' => 'Deleted Successful'));
    //     $this->_redirect('addperk');
    // }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $form = new Default_Form_perk();
        $formModel = new Default_Model_Perk();
        $form->setAttrib('action', BASE_URL . 'addperk');
        $perk = $formModel->getPerkDetailview($id);
        $form->back->setLabel('Back');
        $this->view->perks = $perk;
        $this->view->form = $form;
    }


    public function indexAction()
    {

try {
    //code...

        $auth = Zend_Auth::getInstance();
        $popConfigPermission = array();
        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;
        }
   $auth = Zend_Auth::getInstance();
   $access = Zend_Session::namespaceGet('access_control');
   $empids = $access['empids'];
   $perm = $access['perm'];
   $limit=LIMIT;
   $offset=0;
   $search_val = $this->_request->getParam('search_val',null);
   $search_str = $this->_request->getParam('search_str',null);
   $role_id = $this->_request->getParam('role_id',null);

   

   $this->view->perm = $perm;
        $payrollmodel = new Default_Model_Addperk();
        $empmodel = new Default_Model_Emplist();
        $form = new Default_Form_addperk();
        $inter_options = array();
        $call = $this->_getParam('call');
        if ($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();
        if ($this->_request->isPost()) {
           
            if (isset($_POST['perk_ids'])) {
                $perk =  $_POST['perk_ids']; 
                //die($perk);
                $empid = $payrollmodel->getEmpIdperk($perk);
                die(json_encode($empid));
            }
           
            
        }

        

       // $view = Zend_Layout::getMvcInstance()->getView();
       $objname = $this->_getParam('objname');
       $refresh = $this->_getParam('refresh');
       $dashboardcall = $this->_getParam('dashboardcall');
        $data = array();
        $data2 = array();
        $searchQuery2 = '';
        $searchArray2 = array();

        $searchQuery = '';
        $searchArray = array();
        $tablecontent = '';
        $tablecontent2 = '';

        $dashboardcall = $this->_getParam('dashboardcall');

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes'){
                $perPage = DASHBOARD_PERPAGE;
                $perPage = 5;}
            else{
                $perPage = PERPAGE;
                $perPage = 5;
            }
            $sort = 'DESC';
            $by = 'p.name';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $sort2 = 'DESC';
            $by2 = 'e.id';
            $pageNo2 = 5;
            $searchData2 = '';
            $searchQuery2 = '';

            $searchArray = array();
            $searchArray2 = array();
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $sort2 = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'ASC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'p.name';
            $by2 = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'e.id';

            if ($dashboardcall == 'Yes'){
            $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
        }
        else{
            $perPage = $this->_getParam('per_page', PERPAGE);}
           
            $pageNo = $this->_getParam('page', 1);
            $pageNo2 = $this->_getParam('page', 1);

            $searchData = $this->_getParam('searchData');
            $searchData2 = $this->_getParam('searchData');

        }
        if ($call == 'ajaxcall') {
            if ($objname == 'addperk') {
                $dataTmp = $payrollmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
                $this->view->dataArray = $dataTmp;
            } else if ($objname == 'overtime') {
                $dataTmp2=$empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData2, $call, $dashboardcall, '', '', '', '','',$loginUserId,$limit,$offset,$search_val,$search_str,$role_id,$empids,$perm);
                // $dataTmp2 = $empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData2, $call, $dashboardcall, '', '', '', '',$empids,$perm);
                $this->view->dataArray2 = $dataTmp2;
            }
        } else {
            $dataTmp2=$empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData2, $call, $dashboardcall, '', '', '', '','',$loginUserId,$limit,$offset,$search_val,$search_str,$role_id,$empids,$perm);

            $dataTmp = $payrollmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
            $this->view->dataArray = $dataTmp;
            $this->view->dataArray2 = $dataTmp2;
        }
        array_push($data, $dataTmp);
        array_push($data, $dataTmp2);
        $this->view->call = $call;
        $this->view->objectName = $objname;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->form = $form;
    } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());    }
    }

    public function addAction()
    {
        try {
            //code...
      
        /* $this->_helper->setLayout()->disableLayout(); */
        /* $this->_helper->viewRender()->setNoRender(true); */
        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
		}
        $payrollmodel = new Default_Model_Addperk();

        $temp_id = $_POST['emp_ids'];
        $temp_perks_id = $_POST['perk_ids'];
        $temp_perks_autos = $_POST['perk_autos'];
        $datas = array();
        foreach ($temp_id as $id) {
            foreach ($temp_perks_id as $perk_id) {
                foreach ($temp_perks_autos as $perk_auto) {
                    $data = array();
                    $amounts=$payrollmodel->getperkamount((int)$perk_id);
                    $type=$payrollmodel->getperktype((int)$perk_id);
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

}    $amount=$amounts[0]['amount'];

                $data['emp_id'] = (int) $id;
                $data['perk_id'] = (int)$perk_id;
                

                $data['amount'] =$ammountaaproved;
                $data['modifiedby'] = $loginUserId;  
                $data['modifieddate'] = gmdate("Y-m-d H:i:s"); 
                if($perk_auto=="Y"){
                    $data['amount_approved'] =$amount;
                }
                $data['approved'] = $perk_auto;

                array_push($datas, $data);
            }
        }
    }
        
        $payrollmodel->saveMultiEmpPerk($datas);
        return $this->_helper->_json(array('sucess'=>'Perk Assigned Successfully'));
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }
    
    // public function deleteAction()
	// {
	// 	$auth = Zend_Auth::getInstance();
	// 	if ($auth->hasIdentity()) {
	// 		$loginUserId = $auth->getStorage()->read()->id;
	// 	}

	// 	$id = $this->_request->getParam('objid');
	// 	$deleteflag = $this->_request->getParam('deleteflag');
	// 	$messages['message'] = '';
	// 	$messages['msgtype'] = "";
	// 	$actionflag = 3;
	// 	if ($id) {
	// 		$model = new Default_Model_Perk();

		
	// 		$where = array('perk_id=?' => $id);
	// 		$Id = $model->DeleteData($where);
	// 		if ($Id == 'delete') {
	// 			$messages['message'] = 'perk deleted successfully.';
	// 			$messages['msgtype'] = 'success';
	// 		} else {
	// 			$messages['message'] = 'perk  cannot be deleted.';
	// 			$messages['msgtype'] = 'error';
	// 		}
	// 	} else {
	// 		$messages['message'] = 'Perk cannot be deleted.';
	// 		$messages['msgtype'] = 'error';
	// 	}
	// 	if ($deleteflag == 1) {
	// 		if ($messages['msgtype'] == 'error') {
	// 			$this->_helper->getHelper("FlashMessenger")->addMessage(array("error" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
	// 		}
	// 		if ($messages['msgtype'] == 'success') {
	// 			$this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
	// 		}
	// 	}
	// 	$this->_helper->json($messages);
	// } // end of delete action
    // public function deleteAction()
    // {
    //     if (isset($_POST['objid'])) {
    //         $objId = (int)$_POST['objid'];
    //         $perkmodel = new Default_Model_Perk();

    //         $perkmodel->deleteperk($objId);
    //         $response = array(
    //             'message' => 'perk Successfully Removed.',
    //             'msgtype' => 'success'
    //         );
    //         die(json_encode($response));
    //     } else {
    //         //$data = array('fe'=>'fa');
    //         //die(json_encode($data));
    //     }
    // }
    
}
