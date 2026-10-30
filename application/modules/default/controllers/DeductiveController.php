<?php
require_once 'UserdefinedclassController.php';

class Default_DeductiveController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }

    public function indexAction()
    {
    
        $util = new utlis();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }

        $form = new Default_Form_deductive();
        $method_form = new Default_Form_userdefinedclass();
        $formModel = new Default_Model_Deductive();
        $form->setAttrib('action', BASE_URL . 'deductive');
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

    // public function addAction()
    // {

    //     $util = new utlis();

    //     $auth = Zend_Auth::getInstance();
    //     if ($auth->hasIdentity()) {
    //         $loginUserId = $auth->getStorage()->read()->id;
    //     }
    //     $formModel = new Default_Model_Deductive();
    //     $data = array(
    //         'code' => $this->getRequest()->getParam('code'),
    //         'name' => $this->getRequest()->getParam('name'),
    //         'load_category_id' => (int)$this->getRequest()->getParam('category'),
    //         'istax' => $util->getcheckboxvalue($this->_request->getParam('istax')),
    //         'ispenality' => $util->getcheckboxvalue($this->_request->getParam('ispenality')),
    //         'autoapprove' => $util->getcheckboxvalue($this->_request->getParam('autoapprove')),
    //         'description' => $this->getRequest()->getParam('description'),
    //         'start' => sapp_Global::change_date($this->getRequest()->getParam('start_date'), 'database'),
    //         'end' => sapp_Global::change_date($this->getRequest()->getParam('end_date'), 'database'),
    //         'type' => $this->getRequest()->getParam('type'),
    //         'amount' => doubleval($this->getRequest()->getParam('amount')),
    //         'classPath' => $this->getRequest()->getParam('classPath'),
    //         'method' => $this->getRequest()->getParam('classfun'),
    //         'parameters' => trim($this->getRequest()->getParam('parameters'), ','),
    //         'createdby' => $loginUserId,
    //         'createddate' => gmdate("Y-m-d H:i:s"),
    //     );
    //     $data['isactive'] = 'Y';

    //     $formModel->addData($data);
    //     $this->_helper->getHelper("FlashMessenger")->addMessage(array('messages' => ' Load Added Successful'));
    //     $this->view->messages = $this->_helper->flashMessenger->getMessages();

    //     $this->_redirect('adddeductive');
    // }

    public function editAction()
    {
        $util = new utlis();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $id = (int)$this->getRequest()->getParam('id');
        $form = new Default_Form_deductive();
        $form->code->removeValidator('Db_NoRecordExists');
		$form->name->removeValidator('Db_NoRecordExists');
        $formModel = new Default_Model_Deductive();
        $form->setAttrib('action', BASE_URL . 'deductive/edit');
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

    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $form = new Default_Form_deductive();
        $formModel = new Default_Model_Deductive();
        $form->setAttrib('action', BASE_URL . 'deductive/add');
        $deductive_categories = $formModel->getCategory();
        $form->category->addMultiOption('0', 'Select...');
        foreach ($deductive_categories as $category) {
            $form->category->addMultiOption($category['deductivecategory_id'], $category['name']);
        }
        // $form->type->addMultiOption('0', 'Select...');
        // $form->type->addMultiOption('Individualized', 'Individualized');
        // $form->type->addMultiOption('Percentage', 'Percentage');
        // $form->type->addMultiOption('Fixed', 'Fixed');
        // $form->type->addMultiOption('Calculated', 'Calculated');
        $this->view->form = $form;
        $msgarray = [];
        $controllername = 'empdeductive';
        $this->view->controllername = $controllername;
        $this->view->eventact = 'added';
        $close = 'close';
        $this->view->popup = $close;
    }

    public function  addcategoryAction()
    {
        
        $form = new Default_Form_deductivecategory();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $formModel = new Default_Model_Deductive();
        $form->setAction(BASE_URL . 'deductive/addcategory');
        $auth = Zend_Auth::getInstance();
        $loginUserId = $auth->getStorage()->read()->id;
        if ($this->getRequest()->isPost()) {

            if ($form->isValid($this->getRequest()->getPost())) {

                $data = [
                    'name' => $this->_request->getParam('name_cat'),
                    'description' => $this->_request->getParam('description_cat'),
                    'isactive' => 'Yes',

                ];
                
                //  die(json_encode($data));
                $tableid = $formModel->addCategory($data);

                $categoryData = $formModel->getCategory();
               
                $result = sapp_Global::logManager(DEDUCTIVE,1,$loginUserId,$tableid);
                $opt = '';
                foreach ($categoryData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['deductive_id'], $record['name']);
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
    }
}
