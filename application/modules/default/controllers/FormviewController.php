<?php

use Zend_Form;

class Default_FormviewController extends Zend_Controller_Action
{
    protected $allFormNames = array();
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    
    
    public function indexAction()
    {
        $Formviewform = new Default_Form_Formview();
        $popupAddElement = new Default_Form_Formaddelement();
        $FormModel = new Default_Model_Formview();
        
        //displaying all Personal Detail Elements Begin
        $this->displayFormController("Personal Detail",$Formviewform );
        //displaying all Personal Detail Elements End

        //Displaying all Work Experience Elements Begin
        $this->displayFormController("Work Experience",$Formviewform );
        //Displaying all work Experience Elements End
        
        //Displaying all Education Detail Elements Begin
        $this->displayFormController("Education",$Formviewform );
        //Displaying all Education Detail Elements End

        //Displaying all CV Elements Begin
        $this->displayFormController('CV',$Formviewform);
        //Diplaying all CV Elements End
        
        $this->view->form = $Formviewform;
        $this->view->popup = $popupAddElement;
     

    

        
     
        if ($this->_request->isPost()) {
            $ids = array();
            $elementsData = array();
            foreach ($this->allFormNames as $form) {
                foreach ($form as $key => $value) {
                    array_push($ids, substr($value, strpos($value, '0') + 1));
                    $isActive = array('isActive' => $this->_request->getParam($value));
                    array_push($elementsData, $isActive);
                }
            }

            $FormModel->updateElements($ids, $elementsData);
            sleep(1);
            $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Form Option  updated successfully."));

            $this->_redirect('formview');    
       }
       $this->view->messages = $this->_helper->flashMessenger->getMessages();

    }
    public function displayFormController($formName,$form){
       
        $FormModel = new Default_Model_Formview();
        
        $formElements = $FormModel->getAllElements($formName);
        $formElementNames = array();
        $formElementLabels = array();
        foreach($formElements AS $element){
            $elementName = $element['elementNameOrID'] . '0' . $element['id'];
            $form->addElement('CheckBox', $elementName);
            $form->$elementName->setChecked((int)$element['isActive']);
            array_push($formElementNames, $elementName);
            array_push($formElementLabels, $element['label']);
        }
        array_push($this->allFormNames, $formElementNames);
        $tempVar = str_replace(' ','', $formName).'ElementNames';
        $tempVar2 = str_replace(' ','', $formName).'ElementLabels';
        $this->view->$tempVar = $formElementNames;
        $this->view->$tempVar2 = $formElementLabels;
       
    }
    // public function addpopupAction()
    // {

    //     Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
    //     $bank_form = new Default_Form_Bank();
    //     $bank_form->setAttrib('action', BASE_URL . 'bank/addpopup');
    //     if ($this->getRequest()->getPost()) {
    //         if ($bank_form->isValid($this->getRequest()->getPost())) {
    //             $auth = Zend_Auth::getInstance();
    //             if ($auth->hasIdentity()) {
    //                 $loginUserId = $auth->getStorage()->read()->id;
    //             }
    //             $model = new Default_Model_Bank();
    //             $data = array(
    //                 'name' => $this->getRequest()->getParam('name'),
    //                 'description' => $this->getRequest()->getParam('description'),
    //                 'routingno' => $this->getRequest()->getParam('routingno'),
    //                 'swift_code' => $this->getRequest()->getParam('swift_code'),
    //                 'bank_verification_class' => $this->getRequest()->getParam('verification_class'),
    //                 'isactive' => 'Y',
    //                 'createddate' => gmdate("Y-m-d H:i:s"),
    //                 'createdby' => $loginUserId,
    //                 'modifieddate' => gmdate("Y-m-d H:i:s"),
    //                 'modifiedby' => $loginUserId
    //             );

    //             $tableid=$model->SaveorUpdateBankData($data, '');
    //             $menuID = BANK;
    //             sapp_Global::logManager($menuID,$this->actionadd,$loginUserId,$tableid);
    //             $successmessagestr  = "Bank added successfully.";
    //             // $this->_helper->getHelper("FlashMessenger")->addMessage(array('messages' => $successmessagestr));
    //             $currencyData = $model->fetchAll('isactive = "Y"', 'name')->toArray();
    //             $opt = '';
    //             foreach ($currencyData as $record) {
    //                 $opt .= sapp_Global::selectOptionBuilder($record['bank_id'], $record['name']);
    //             }
    //             $this->view->bankData = $opt;
    //             $this->view->eventact = 'added';
    //             $close = 'close';
    //             $this->view->popup = $close;
    //             $this->view->controllername = "bank";
    //         }
    //         $messages = $bank_form->getMessages();
    //         foreach ($messages as $key1 => $value) {
    //             foreach ($value as $key2 => $value2) {
    //                 $msgArray[$key1] = $value2;
    //                 break;
    //             }
    //         }
    //     }
    //     $this->view->form = $bank_form;
    //     $this->view->msgarray = $msgArray;
    // }
    public function addpopupAction(){
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $msgarray = array();
        $FormModel = new Default_Model_Formview();

      
        $popupAddElement = new Default_Form_Formaddelement();
        $popupAddElement->setAttrib('action', BASE_URL . 'formview/addpopup');
        $formNames = $FormModel->getAllFormNames();
        $elemnetTypes = $FormModel->getAllElementTypes();
        $tables = $FormModel->getAllTables();
        $validatetors=$FormModel->getelmentvalidators();
        $this->view->popup = $popupAddElement;
        $FormModel = new Default_Model_Formview();
        $popupAddElement->formName->setAttrib('onchange',"displayParticularData(this,'parent,child','/getformelement','elementNameOrID')");
        foreach($formNames AS $formName1){
            $popupAddElement->formName->addMultiOption($formName1['formName'],$formName1['formName']);
        }
        $popupAddElement->elementType->addMultiOption('empty_option','Select...');
        foreach($elemnetTypes AS $elemnetType){
            $popupAddElement->elementType->addMultiOption($elemnetType['dataType'],$elemnetType['dataType']);
        }
        $popupAddElement->elementTable->addMultiOption('empty_option','Select...');
        $popupAddElement->getFrom->addMultiOption('empty_option','Select...');

        $popupAddElement->getFrom->setAttrib('onchange',"displayParticularData(this,'get','/getTableColumns','Field')");
        
        foreach($tables AS $table){
            $popupAddElement->elementTable->addMultiOption($table['Tables_in_sentrifugo'],$table['Tables_in_sentrifugo']);
            $popupAddElement->getFrom->addMultiOption($table['Tables_in_sentrifugo'], $table['Tables_in_sentrifugo']);
        }
         
        foreach($validatetors AS $validator){
            $popupAddElement->validators->addMultiOption($validator['id'],$validator['validate']." ".$validator['regxtype']);
        }
        $popupAddElement->startNewLine->addMultiOption(0,'No');
        $popupAddElement->startNewLine->addMultiOption(1,'Yes');

        $popupAddElement->occupyWholeLine->addMultiOption(0,'No');
        $popupAddElement->occupyWholeLine->addMultiOption(1,'Yes');
        if ($this->getRequest()->isPost()) {
                
                $getFrom = $this->_request->getParam('getFrom');
                if($getFrom!='empty_option'){

            
                $columns=$FormModel->getAllColumns($getFrom); 
                foreach($columns AS $column){

                    $popupAddElement->get->addMultiOption($column['Field'],$column['Field']);
                    }
                }
            

            $formName = $this->_request->getParam('formName');

            $elements=$FormModel->getFormElements($formName);  
            //die(json_encode($elements));
            foreach($elements AS $element){

                $popupAddElement->parent->addMultiOption($element['elementNameOrID'],$element['elementNameOrID']);
                $popupAddElement->child->addMultiOption($element['elementNameOrID'],$element['elementNameOrID']);

                }
         
        if ($popupAddElement->isValid($this->getRequest()->getPost())) {  
            $formName = $this->_request->getParam('formName');
            $elementType = $this->_request->getParam('elementType');
            $elementName = $this->_request->getParam('elementName');
            $elementLabel = $this->_request->getParam('elementLabel');
            $startNewLine = $this->_request->getParam('startNewLine');
            $occupyWholeLine = $this->_request->getParam('occupyWholeLine');
            $tableName = $this->_request->getParam('elementTable');
            $getFrom = $this->_request->getParam('getFrom');
            $get = $this->_request->getParam('get');
            $parent = $this->_request->getParam('parent');
            $child = $this->_request->getParam('child');
            $validator = $this->_request->getParam('validators');
            $allvalidator="";
    foreach ($validator as $a){
        //echo $a;
        $com_values = implode(",", array_filter([$a])) ;
        $allvalidator=$allvalidator.$com_values.",";
    }
    //die($all);
            $elementData = array(
                'formName' => $formName,
                'dataType' => $elementType,
                'elementNameOrID' => $elementName,
                'label' => $elementLabel,
                'startNewLine' => (int)$startNewLine,
                'occupyWholeLine' => (int)$occupyWholeLine,
                'getFrom' => $getFrom,
                'get' => $get,
                'validator' => $allvalidator,
                'parent' => $parent,
                'child' => $child,
    
            );
            //die(json_encode($elementData));
            
            $FormModel->addElement($elementData);
            $addelement='yes';

                $this->view->eventact = 'added';
               $close = 'close';

               $this->view->popup = $close;
            if($elementType == 'Button'|| $elementType == 'Captcha'|| $elementType == 'Exception'||
            $elementType == 'Hidden'|| $elementType == 'Reset'){
           
              //  die('successfuly worked');
               // $this->_redirect('formview');
            }else{
                $columnType = '';
                if($elementType == 'TextArea'){
                    $columnType = 'Text';
                }else if($elementType == 'Image'){
                    $columnType = 'Blob';
                }else{
                    $columnType = 'Varchar(100)';
                }
    //die("fff");
                $FormModel->addElementColumnToTable($tableName,$elementName,$columnType);
              //  $this->_redirect('formview');
            }        
                    }  
             else   {
                        $messages = $popupAddElement->getMessages();
                        foreach ($messages as $key => $val)
                        {
                            foreach($val as $key2 => $val2)
                            {
                                $msgarray[$key] = $val2;
                                                        break;
                            }
                        }
                        Zend_Debug::dump($msgarray);
                       // die();
                        $this->view->msgarray = $msgarray;
                    }
                }
                    $this->view->form = $popupAddElement;
                     $this->view->msgarray = $msgarray;
                     $controllername = 'formview';
                     $this->view->controllername = $controllername;
                     $this->view->addelement = $addelement;

                }
                

    public function updateAction(){
        
        // $FormModel = new Default_Model_Formview();
        // $ids = array();
        // $elementsData = array();
        // foreach ($this->allFormNames as $form) {
        //     foreach ($form as $key => $value) {
        //         array_push($ids, substr($value, strpos($value, '0') + 1));
        //         $isActive = array('isActive' => $this->_request->getParam($value));
        //         array_push($elementsData, $isActive);
        //     }
        // }
        // die(json_encode($this->allFormNames));
        // $FormModel->updateElements($ids, $elementsData);
        // sleep(1);
        // $this->_redirect('formview');
    }
    

    public function getcolumnsAction(){
       
       $FormModel = new Default_Model_Formview();
        if(isset($_POST['data'])){
            $columns = $FormModel->getAllColumns($_POST['data']);
            die(json_encode($columns));
        }else{
            die('error');
        }
    }

    public function getformelementAction(){//For displaying particualr columns in parent and child elements based on selected form
        $FormModel = new Default_Model_Formview();
        //die($_POST['data']);
        if(isset($_POST['data'])){
            $elements = $FormModel->getFormElements($_POST['data']);
            die(json_encode($elements));

        }else{
            die(json_encode('error'));
        }
    }

    public function getparticulardataforallAction(){
        if(isset($_POST['tableName'],$_POST['columnName'],$_POST['data']) && isset($_POST['queryWith'])){
            $FormModel = new Default_Model_Formview();
            $result = $FormModel->getRequestedData($_POST['tableName'],$_POST['columnName'],$_POST['data'],$_POST['queryWith']);
            die(json_encode($result));
        }else{
            die(json_encode(array('result'=>'error')));
        }
    }
   
}
