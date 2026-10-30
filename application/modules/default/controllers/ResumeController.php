<?php

class Default_ResumeController extends Zend_Controller_Action
{
    private $options;
    
    public function preDispatch()
    {
    }
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function indexAction()
    {
        try {
           
        $candidatedetails = $this->createForm();
        $statemodel = new Default_Model_States();
        $citymodel = new Default_Model_Cities();
        $formModel = new Default_Model_Formview();
        $profile = new Default_Model_Selfcandidateadd(); 
        if ($this->getRequest()->isPost()) {
            $country=$this->getRequest()->getPost()['country'];
            $datapost = $this->getRequest()->getPost();
            if (!$country==0) {
             
              $statelist=$statemodel->getStatesList($country);
              
              foreach ($statelist as  $value) {
                  $candidatedetails->state->addMultiOption($value['id'],$value['state_name']);
              }
              
             if (!$this->getRequest()->getPost()['state']==0) {
                 # code...
             
              $citylist = $citymodel->getCitiesList($this->getRequest()->getPost()['state']);
             
              foreach ($citylist as  $value) {
                  $candidatedetails->city->addMultiOption($value['id'],$value['city_name']);
              }
            }
            }
           
            if ($candidatedetails->isValid($this->getRequest()->getPost())) {
               
                $elements = $formModel->getAllElements('Personal Detail');
                $storeto = $elements[0]['storeTo'];
                $data = array();
                $firsttime = true;
                foreach ($elements as $key => $value) {
                    if ($storeto!='main_candlang') {
                    if($storeto==$value['storeTo'])
                    {
                        $data[$value['elementNameOrID']]=$datapost[$value['elementNameOrID']];
                    }
                    else{
                        if ($firsttime) {
                         
                                $candidateid = $profile->saveData($data,$storeto);
                                $firsttime = false;
                            
                            //Zend_Debug::dump($data);
                           
                        }
                        else{

                           $Id= $profile->saveData($data,$storeto);
                            //Zend_Debug::dump($data);
                        }
                        
                        $storeto = $value['storeTo'];
                        $data = array();
                        
                        $data['candidate_id']=$candidateid;
                        $data[$value['elementNameOrID']]=$this->getRequest()->getPost()[$value['elementNameOrID']];
                        $this->_forward('index','workexp','default',array('candidate_id'=>$Id));
                    }
                }
            }
            }
            else{
                $messages = $candidatedetails->getMessages();
                
                Zend_Debug::dump($messages);
            }
        }
         //code...
        } catch (\Throwable $th) {
           Zend_Debug::dump($th->getMessage());
        }
        $this->view->form = $candidatedetails;
        
    }


    protected function createForm()
    {
        try {
            //code...
      
        $FormModel = new Default_Model_Formview();
        $candidatedetails = new Default_Form_Resume();
        $candidatedetails->setAction(BASE_URL.'resume');
        $elements = $FormModel->getActiveElements('Personal Detail');
        $elementLabels = array();
        $elementNames = array();
        $elementOptions = array();
        $validators = array();
        foreach ($elements as $element) {
            $elementOption = array();
            $elementName = $element['elementNameOrID'];
            if ($element['occupyWholeLine']) {
                $elementOption = array('OccupyLine' => 'OccupyLine');
            } else if ($element['startNewLine']) {
                $elementOption = array('NewLine' => 'NewLine');
            }

            $candidatedetails->addElement($element['dataType'], $element['elementNameOrID']);

            array_push($elementLabels, $element['label']);
            array_push($elementNames, $element['elementNameOrID']);
            array_push($elementOptions, $elementOption);

            if ($element['dataType'] == 'Select' && $element['get'] != '') {

                if ($element['parent'] != '') {
                    $parent = $element['parent'];
                    // if($parent=='country'){
                    //     $candidatedetails->$parent->setAttrib('onchange', 'displayParticularState_normal(this,"","state","city")');
                    // }
                    // if($parent=='state'){
                    //     $candidatedetails->$parent->setAttrib('onchange', 'displayParticularCity_normal(this,"","city","")');
                    // }
                   
                 
                    $candidatedetails->$parent->setAttrib('onchange', 'displayParticularData_ForAll(this,"' . $element['getFrom'] . '","' . $element['get'] . '","' . $element['elementNameOrID'] . '","' . $element['queryWith'] . '")');
                } else {
                    $candidatedetails->$elementName->addMultiOption(0, 'Select...');
                    $opt = $FormModel->getSelectData($element['getFrom'], $element['get']);
                    foreach ($opt as $op) {
                        $candidatedetails->$elementName->addMultiOption($op['id'], $op[$element['get']]);
                    }
                }
            }

            if ($element['validator'] != '') {
                $validatorKeys = $FormModel->getValidators($element['validator']);
                $validator = array();
              
                try {
                    //code...
              
                foreach ($validatorKeys as $key => $value) {
                   
                    if ($value['validate']=='Regex') {
                        $pattern = '';
                        if ($value['regxtype']=='onlyalphabet') {
                            $pattern = '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/';
                        }
                        elseif ($value['regxtype']=='onlynumber') {
                            $pattern = '/^[0-9]+$/';
                            
                        }
                        
                        $candidatedetails->$elementName->addValidator('regex',true,array(
                            'pattern'=>$pattern,
                            'messages'=>array(
                                    'regexNotMatch'=>$value['description']
                            )
                        )
                            );
                  

                    }else{
                        $candidatedetails->$elementName->addValidator($value['validate'],false,array('messages'=>$value['description']));
                    }
                    
                }

            } catch (\Throwable $th) {
                Zend_Debug::dump($th->getMessage());
            }
           
               
                $candidatedetails->$elementName->setRequired(true);
                foreach ($validatorKeys as $validats) {
                    $tempArray = array();
                    $val = $validats['validate'];
                    $message = $validats['description'];
                    //$candidatedetails->$elementName->addValidator($val, false, array('messages' => $message));
                    //array_push($validator, $this->createValidator($validats['validate']));
                    $tempArray['validate'] = $validats['validate'];
                    $tempArray['description'] = $validats['description'];
                    array_push($validator, $tempArray);
                    
                   
                }

                $validators[$elementName] = $validator;
            }
        }
        
        $this->view->form = $candidatedetails;
        $this->view->elementLabels = $elementLabels;
        $this->view->elementNames = $elementNames;
        $this->view->elementOptions = $elementOptions;
        $this->view->validators = $validators;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $candidatedetails->state->setRegisterInArrayValidator(false);
        $candidatedetails->city->setRegisterInArrayValidator(false);
        $candidatedetails->country->setRegisterInArrayValidator(false);

        return  $candidatedetails;
    } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());    }
    }

    
    public function addAction()
    {
        try {
            
        $tempArray = explode('&',$_POST['data']);
        $temp_data = [];
        foreach($tempArray AS $array){
            $temp = explode("=",$array);
            if(strpos($temp[0],'date')){
                $temp_data[$temp[0]] = sapp_Global::change_date($temp[1], 'database');
            }else{
                $temp_data[$temp[0]] = $temp[1];
            } 
        }
        $formModel = new Default_Model_Formview();
        $profile = new Default_Model_Selfcandidateadd();
        $validator = $this->view->validator;
        $elements = $formModel->getAllElements('Personal Detail');
        $formArray = [];
        foreach ($elements as $element) {

            $tempArray = [];

            $i = 0;
            foreach ($elements as $ele) {
                if ($element['storeTo'] === $ele['storeTo']) {
                    array_push($tempArray, $ele['elementNameOrID']);
                }
                $i++;
            }
            $formArray[$element['storeTo']] = $tempArray;
        }
        
        $firstTime = true;
        $candidateID = '456';
        foreach ($formArray as $key => $values) {
            $data = array();
            foreach ($values as $value) {
                //$data[$value] = $this->_request->getParam($value);
                $data[$value] = $temp_data[$value];
            }
            
            if ($key != '') {
                if ($firstTime) {
                    $candidateID = $profile->saveData($data, $key);
                    $firstTime = false;
                } else {
                    $data['candidate_id'] = (int)$candidateID;
                    $profile->saveData($data, $key);
                }
            } else {
            }
            
        }
        //die(json_encode($_POST['data']));
        $this->getRequest()->setParam('candidate_id',$candidateID);
        die(json_encode($this->getRequest()->getParam('candidate_id')));
        //code...
    } catch (\Throwable $th) {
       Zend_Debug::dump($th->getMessage());
    }
        
    }
    public function languageAction(){
       
        $profile = new Default_Model_Selfcandidateadd();
        $tempArray = explode('&',$_POST['data']);
        $data = [];
        foreach($tempArray AS $array){
            $temp = explode("=",$array);
            if(strpos($temp[0],'date')){
                $data[$temp[0]] = sapp_Global::change_date($temp[1], 'database');
            }else{
                $data[$temp[0]] = $temp[1];
            } 
        }
        $data['candidate_id'] = (int)$_POST['candidate_id'];
        $profile->saveData($data, 'main_candlang');
    
        //die(json_encode('success'));
        $this->getRequest()->setParam('candidate_id',$_POST['candidate_id']);
        die($this->getRequest()->getParam('candidate_id'));
    }
    public function nextAction(){
        $form = new Default_Form_Resume();
        Zend_Debug::dump($form->getMessages());
        $candidateID = $this->getRequest()->getParam('id');
        Zend_Debug::dump('he');
        die();
        $this->_forward('index','workexp','default',array('candidate_id'=>$candidateID));
    }
    public function resumeAction()
    {
       
        $candidatedetails = $this->createForm();
        $formmodel = new Default_Model_Formview();
        //$candidatedetails = new Default_Form_selfcandidateadd();
        $profile = new Default_Model_Selfcandidateadd();
        $validator = $this->view->validator;
        //Zend_Debug::dump($validator['first_name']);
        //die();
        $data = array();
        $id = 0;
        //$elements = $formmodel->getActiveElements('Personal Detail');
        $elements = $formmodel->getAllElements('Personal Detail');
        $formArray = [];
        foreach ($elements as $element) {

            $tempArray = [];

            $i = 0;
            foreach ($elements as $ele) {
                if ($element['storeTo'] === $ele['storeTo']) {
                    array_push($tempArray, $ele['elementNameOrID']);
                }
                $i++;
            }
            $formArray[$element['storeTo']] = $tempArray;

            // $value = '';
            // $key = '';
            // $data[$value['elementNameOrID']] = $this->_request->getParam($value['elementNameOrID']);

            // //array_push($data,array($value["elementNameOrID"]=>$this->_request->getParam($value['elementNameOrID'])));
            // if ($key == 2) {
            //     $id = $profile->saveprofiledata($data); //profile
            //     $data = array();
            //     //break;
            // }
            // if ($key == 9) {
            //     $data['candidate_id'] = $id;
            //     $profile->saveaddressdata($data);
            //     $data = array(); //address
            // }
            // if ($key == 13) {
            //     $data['candidate_id'] = $id;
            //     $profile->savephonedata($data);
            //     $data = array(); //tele
            // }
            // if ($key == 15) {
            //     $data['candidate_id'] = $id;
            //     $profile->savenationalitydata($data);
            //     $data = array(); //nationality

            // }
            // if ($key == 20) {
            //     $data['candidate_id'] = $id;
            //     $profile->savenativelangdata($data);
            //     $data = array(); //native
            // }
            // if ($key == 25) {
            //     $profile->savefluentlangdata($data);
            //     $data['candidate_id'] = $id;
            //     $data = array(); //fluent
            // }
        }
        //die(json_encode($formArray));
        //die(json_encode($candidatedetails->getValidValues($_POST)));
        // Zend_Debug::dump($candidatedetails);
        // $da['first_name'] = "vsdfsdgfd";
        // if(!($error = $candidatedetails->isValid($this->getRequest()->getPost()))){
        //     die(json_encode($error));
        // }else{
        //     die('valid');
        // }

        foreach ($elements as $element) {
            Zend_Debug::dump($element['elementNameOrID']);
            if (!($validator[$element['elementNameOrID']][0]->isValid($this->getRequest()->getPost()[$element['elementNameOrID']]))) {
                Zend_Debug::dump($validator['first_name']);
                //$this->_redirect(BASE_URL.'addpersonaldetail',array('data'=>$_POST));
                //$this->_forward('index','selfcandidateadd',null, array('data'=>'dfa'));
                $this->_redirect('resume', array('iddddd' => 'good'));

                //die('invalid');
            } else {
                die('valid');
            }
        }
        // $firstTime = true;
        // $candidateID = '';
        // foreach($formArray AS $key=>$values){
        //     $data = array();
        //     foreach($values AS $value){
        //         $data[$value] = $this->_request->getParam($value);
        //     }

        //     if($key!=''){
        //         if($firstTime){
        //             $candidateID=$profile->saveData($data, $key);
        //            $firstTime = false;
        //         }else{
        //             $data['candidate_id'] = (int)$candidateID;
        //             $profile->saveData($data, $key);

        //         }
        //     }else{
        //     }  
        // }

        //$this->_forward('index','workexp','default',array('candidate_id'=>$candidateID));
        /* 
        if ($candidatedetails->isValid($this->getRequest()->getPost())) {
            # code...
            $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Application added successfully."));
            $this->_redirect('addpersonaldetail');
        } */

        /* $candidatedetails = new Default_Form_selfcandidatepersonaldetail();
        

        if ($candidatedetails->isValid($this->_request->getPost())) {
        $profilemodel = new Default_Model_Selfcandidateadd();
        
        $candidatefname = $this->_request->getParam('candidatefirstname');
        $candidatelname = $this->_request->getParam('candidatelastname');
        $candidatemname = $this->_request->getParam('candidatemiddlename');
        $country =intval($this->_request->getParam('country')); 
        $state = intval($this->_request->getParam('state')); 
        $city = intval($this->_request->getParam('city')); 
        $countrycode = $this->_request->getParam('countrycode');
        $zipcode = $this->_request->getParam('zipcode');
        $email = $this->_request->getParam('email');
        $nationality = intval($this->_request->getParam('nationality'));
        $dualnationality = intval($this->_request->getParam('dualnationality'));
        $nativlang  = $this->_request->getParam('nativelang');
        $nativelangread = $this->_request->getParam('nativelangread');
        $nativelangwrite = $this->_request->getParam('nativelangwrite');
        $nativelanglisten = $this->_request->getParam('nativelanglisten');
        $nativelangspeak = $this->_request->getParam('nativelangspeak');
        $fluentlang  = $this->_request->getParam('fluentlang');
        $fluentlangread = $this->_request->getParam('fluentlangread');
        $fluentlangwrite = $this->_request->getParam('fluentlangwrite');
        $fluentlanglisten = $this->_request->getParam('fluentlanglisten');
        $fluentlangspeak = $this->_request->getParam('fluentlangspeak');
        $mobilenumber = $this->_request->getParam('mobilenumber');
        $phonenumber = $this->_request->getParam('homenumber');
        
        $data = array("first_name"=>$candidatefname,"middle_name"=>$candidatemname,"last_name"=>$candidatelname);
        $id=$profilemodel->saveprofiledata($data);

        $addressdata = array("country"=>$country,"state"=>$state,"city"=>$city,"country_code"=>$countrycode,"zip_code"=>$zipcode,"email"=>$email,"candidate_id"=>$id);
        $profilemodel->saveaddressdata($addressdata);

        $nationalitydata = array('nationality'=>$nationality,'dual_nationality'=>$dualnationality,'candidate_id'=>$id);
        $profilemodel->savenationalitydata($nationalitydata);

        $nativelangdata = array('language_id'=>$nativlang,'reading'=>$nativelangread,'writing'=>$nativelangwrite,'speaking'=>$nativelangspeak,'listening'=>$nativelanglisten,'candidate_id'=>$id);
        $profilemodel->savenativelangdata($nativelangdata);

        $fluentlangdata = array('language_id'=>$fluentlang,'reading'=>$fluentlangread,'writing'=>$fluentlangwrite,'speaking'=>$fluentlangspeak,'listening'=>$fluentlanglisten,'candidate_id'=>$id);
        $profilemodel->savefluentlangdata($fluentlangdata);

        $phonedata = array('mobile'=>$mobilenumber,'home'=>$phonenumber,'candidate_id'=>$id);
        $profilemodel->savephonedata($phonedata);
            # code...
            $this->_forward('index','workexp','default',array('candidate_id'=>$id));
        }
        else{
           
            
            $this->_forward('index','selfcandidateadd','default',array('form'=>$candidatedetails));
          
          
        }
         */

        /* $_POST['candidate_id'] = 1;
        $this->_request->setParam('workexp',1); */
    }

    public function createValidator($type)
    {
        $validator = null;
        if ($type == 'NotEmpty') {
            $validator = new Zend_Validate_NotEmpty();
        } else if ($type == 'EmaileAddress') {
            $validator = new Zend_Validate_EmailAddress();
        }
        return $validator;
    }
}