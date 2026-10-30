<?php

class Default_CandidateeducationController extends Zend_Controller_Action
{

    public function preDispatch()
    {
    }

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function indexAction()
    {

        $educationForm = new Default_Form_Candidateeducation();

        $countryListModel = new Default_Model_Countries();
        $countries = $countryListModel->getTotalCountriesList();
        $educationLevelModel = new Default_Model_Educationlevelcode();
        $educationLevel = $educationLevelModel->getEducationlevelData();
        $FormModel = new Default_Model_Formview();

        //$educationForm->id->setValue($this->_request->getParam('candidate_id'));

        //Displaying forms from DB Start

        $educationElements = $FormModel->getActiveElements('Education');

        $educationElementNames = array();
        $educationElementLabels = array();
        $educationElementOptions = array();
        $validators = array();
        foreach ($educationElements as $element) {
            $elementName = $element['elementNameOrID'];
            $educationForm->addElement($element['dataType'], $element['elementNameOrID']);
            array_push($educationElementNames, $element['elementNameOrID']);
            array_push($educationElementLabels, $element['label']);

           
            if ($element['dataType'] == 'Select' && $element['get'] != '') {
                
                if ($element['parent'] != '') {
                } else {
                    $opt = $FormModel->getSelectData($element['getFrom'], $element['get']);
                    $educationForm->$elementName->addMultiOption('empty_option', 'Select...');
                    foreach ($opt as $op) {
                        $educationForm->$elementName->addMultiOption($op['id'], $op[$element['get']]);
                    }
                }

                if ($element['parent'] != '') {
                    $parent = $element['parent'];
                   $educationForm->$parent->setAttrib('onchange', 'displayParticularData_ForAll(this,"' . $element['getFrom'] . '","' . $element['get'] . '","' . $element['elementNameOrID'] . '","' . $element['queryWith'] . '")');
                }
            }
            if ($element['validator'] != '') {
                $validatorKeys = $FormModel->getValidators($element['validator']);
                $validator = array();
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
        $educationForm->id->setValue(1);
        //Displaying forms from DB End

        //$educationForm->country->setAttrib('onchange', 'displayParticularState_normal(this,"","state_province","city")');
        //$educationForm->state_province->setAttrib('onchange', 'displayParticularCity_normal(this,"","city","")');

        $educationForm->start_date->setAttrib('title', 'Due Date.');
        $educationForm->start_date->setAttrib('maxLength', 10);
        $educationForm->start_date->setAttrib('readonly', 'readonly');
        $educationForm->start_date->setAttrib('onfocus', 'this.blur()');
        $educationForm->start_date->addValidator('NotEmpty', false, array('messages' => 'Please select start date.'));
        $educationForm->start_date->setRequired(true);

        $educationForm->end_date->setAttrib('title', 'Due Date.');
        $educationForm->end_date->setAttrib('maxLength', 10);
        $educationForm->end_date->setAttrib('readonly', 'readonly');
        $educationForm->end_date->setAttrib('onfocus', 'this.blur()');
        $educationForm->end_date->addValidator('NotEmpty', false, array('messages' => 'Please select start date.'));
        $educationForm->end_date->setRequired(true);

        $educationForm->education_level->addMultiOption('', 'Not Specified');
        foreach ($educationLevel as $education) {
            $educationForm->education_level->addMultiOption($education['id'], $education['description']);
        }
        // $educationForm->country->addMultiOption('', 'Select Country');
        // foreach ($countries as $country) {
        //     $educationForm->country->addMultiOption($country['id'], $country['country_name']);
        // }

        // if (isset($_POST['country']) && $_POST['country'] != '') {
        //     $statesModel = new Default_Model_States();
        //     $statesModelData = $statesModel->getStatesList(intval($_POST['country']));
        //     if (count($statesModelData) > 0) {
        //         foreach ($statesModelData as $dstate) {
        //             $st_opt[$dstate['id'] . '!@#' . $dstate['state_name']] = $dstate['state_name'];
        //         }
        //     }
        //     $educationForm->state_province->addMultiOptions(array('' => 'Select State') + $st_opt);
        // }
        // if (isset($_POST['state']) && $_POST['state'] != '') {
        //     $citiesmodel = new Default_Model_Cities();
        //     $citiesmodeldata = $citiesmodel->getCitiesList(intval($_POST['state']));
        //     $ct_opt = array();
        //     if (count($citiesmodeldata) > 0) {
        //         foreach ($citiesmodeldata as $dcity) {
        //             $ct_opt[$dcity['id'] . '!@#' . $dcity['city_name']] = $dcity['city_name'];
        //         }
        //     }
        //     $educationForm->city->addMultiOptions(array('' => 'Select City') + $ct_opt);
        // }

        $this->view->form = $educationForm;
        $this->view->educationElementNames = $educationElementNames;
        $this->view->educationElementLabels = $educationElementLabels;
        $this->view->validators = $validators;
        $this->view->candidate_id = $this->_request->getParam('candidate_id');
        
    }
    public function addAction()
    {
    
       
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
        
        //$data['candidate_id'] = (int)$this->_request->getParam('candidate_id');
        $data['candidate_id'] = $_POST['candidate_id'];
        //die(json_encode(($data)));
        $eudcation_model = new Default_Model_Candidateeducation();
        //$FormModel = new Default_Model_Formview();

        //$educationElements = $FormModel->getActiveElements('Education');
       // $education_data = array();
        // foreach ($educationElements as $element) {
        //     if (
        //         !($element['dataType'] == "Button") && !($element['dataType'] == "Hidden") && !($element['dataType'] == "Captcha") &&
        //         !($element['dataType'] == "Reset")
        //     ) {

        //         if ((int)$this->_request->getParam($element['elementNameOrID'])) {
        //             $education_data[$element['elementNameOrID']] =  (int)$this->_request->getParam($element['elementNameOrID']);
        //         } else {

        //             if (strpos($element['elementNameOrID'], "date") !== false) {
        //                 $date = $this->_request->getParam($element['elementNameOrID']);

        //                 $education_data[$element['elementNameOrID']] =  sapp_Global::change_date($date, 'database');
        //             } else {

        //                 $education_data[$element['elementNameOrID']] =  $this->_request->getParam($element['elementNameOrID']);
        //             }
        //         }
        //     }
        // }

        //$education_data['candidate_id'] = (int)$this->_request->getParam('id');
       
        // $institution = $this->_request->getParam('institution');
        // $course = $this->_request->getParam('course');
        // $education_level = (int)$this->_request->getParam('education_level');
        // $start_date = $this->_request->getParam('start_date');
        // $end_date = $this->_request->getParam('end_date');
        // $country = (int)$this->_request->getParam('country');
        // $state = (int)$this->_request->getParam('state');
        // $city = (int)$this->_request->getParam('city');
        // $id = $this->_request->getParam('id');

        // $education_data = array('institution'=>$institution,
        //                         'course_programe'=>$course,
        //                         'education_level'=>$education_level,
        //                         'start_date'=>sapp_Global::change_date($start_date, 'database'),
        //                         'end_date'=>sapp_Global::change_date($end_date, 'database'),
        //                         'country'=>$country,
        //                         'state_province'=>$state,
        //                         'city'=>$city,
        //                         'candidate_id'=>$id);   
        //die(json_encode($education_data));
        
        $eudcation_model->saveEducationData($data);
        

        // $this->_redirect('addcv');
    }
    public function nextAction()
    {
       
        $this->_forward('index', 'cvadd', 'default', array('candidate_id' => $this->_request->getParam('id')));
    }
    public function prevAction()
    {
        $this->_redirect('workexp');
    }
}
