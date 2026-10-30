<?php
require_once 'UserdefinedclassController.php';
class Default_PerkController extends Zend_Controller_Action
{
    public function preDispatch()
    {
    }
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }
    public function indexAction()
    {
        // $data = array();

        // $dataTmp = $cand_model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call,$dashboardcall,'','','','');

        // array_push($data,$dataTmp);
        // $this->view->dataArray = $dataTmp;
        $util = new utlis();

            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }
        $form = new Default_Form_perk();
        $method_form = new Default_Form_userdefinedclass();
        $formModel = new Default_Model_Perk();
        $form->setAttrib('action', BASE_URL . 'perk');
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

                $data = [
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
                ];
        
                $data['isactive'] = 'Y';
              // die(json_encode($data));
                $tableid=$formModel->addData($data);
                $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Perk   added successfully."]);
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

    // public function addAction()
    // {
    //     $util = new utlis();

    //     $auth = Zend_Auth::getInstance();
    //     if ($auth->hasIdentity()) {
    //         $loginUserId = $auth->getStorage()->read()->id;
    //     }
    //     $formModel = new Default_Model_Perk();
    //     $form= new Default_Form_perk();

    //     if($this->getRequest()->getPost()){
	// 		if($form->isValid($this->_request->getPost())){
    //             $data = array(
    //                 'code' => $this->getRequest()->getParam('code'),
    //                 'name' => $this->getRequest()->getParam('name'),
    //                 'category' => (int)$this->getRequest()->getParam('category'),
    //                 'description' => $this->getRequest()->getParam('description'),
    //                 'start' => sapp_Global::change_date($this->getRequest()->getParam('start_date'), 'database'),
    //                 'end' => sapp_Global::change_date($this->getRequest()->getParam('end_date'), 'database'),
    //                 'type' => $this->getRequest()->getParam('type'),
    //                 'amount' => doubleval($this->getRequest()->getParam('amount')),
    //                 'classPath' => $this->getRequest()->getParam('classPath'),
    //                 'method' => $this->getRequest()->getParam('classfun'),
    //                 'parameters' => trim($this->getRequest()->getParam('parameters'), ','),
    //                 'createdby' => $loginUserId,
    //                 'createddate' => gmdate("Y-m-d H:i:s"),
    //                 'istaxable' => $util->getcheckboxvalue($this->_request->getParam('taxable')),
    //                 'auto_approved' => $util->getcheckboxvalue($this->_request->getParam('auto_approved'))
    //             );
        
    //             $data['isactive'] = 'Y';
    //             // die(json_encode($data));
    //             $tableid=$formModel->addData($data);
    //             $this->_helper->getHelper("FlashMessenger")->addMessage(array('messages' => 'Perk Added Successful'));
    //             $this->view->messages = $this->_helper->flashMessenger->getMessages();
    //             $result = sapp_Global::logManager(229,1,$loginUserId,$tableid);
    //             $this->_redirect('addperk');
    //         }
    //     else
    //     {
    //         $messages = $form->getMessages();
    //         foreach ($messages as $key => $val)
    //         {
    //             foreach($val as $key2 => $val2)
    //             {
    //                 $msgarray[$key] = $val2;
    //                                         break;
    //             }
    //         }
    //         $this->view->msgarray = $msgarray;
    //     }
    // }
       
    // }
    public function  addcategoryAction()
    {
        
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $form = new Default_Form_perkcategory();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $formModel = new Default_Model_Perk();
        $form->setAction(BASE_URL . 'perk/addcategory');
        $auth = Zend_Auth::getInstance();
        if ($this->getRequest()->isPost()) {

            if ($form->isValid($this->getRequest()->getPost())) {

                $data = [
                    'name' => $this->_request->getParam('name_cat'),
                    'description' => $this->_request->getParam('description_cat'),
                    'isactive' => 'Y',

                ];
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

        $controllername = 'perk';
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
        }
      
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
        $form->setAttrib('action', BASE_URL . 'perk/edit');
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
            $where = ['perk_id=?' => $this->getRequest()->getParam('id')];
            $data = [
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
            ];
            //$data['isactive'] = 'Yes';
            //die(json_encode($data));
            $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Perk updated successfully."]);
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

		
			$where = ['perk_id=?' => $id];
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
}
