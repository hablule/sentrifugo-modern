<?php


class Default_WorkexpController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function indexAction()
    {

       
        $workxpform = new Default_Form_workexp();
        // $jobtitlesmodel = new Default_Model_Jobtitles();
        // $jobpositionmodel = new Default_Model_Positions();
        $FormModel = new Default_Model_Formview();
        $workexpmodel = new Default_Model_Workexp();


        //Displaying forms from DB Start
        $elements = $FormModel->getActiveElements('Work Experience');

        $elementNames = array();
        $elementLabels = array();
        $elementOptions = array();
        $elementTypes = array();
        $validators = array();
        foreach ($elements as $element) {
            $elementName = $element['elementNameOrID'];
            $elementOption = array();
            if ($element['occupyWholeLine']) {
                $elementOption = array('OccupyLine' => 'OccupyLine');
            } else if ($element['startNewLine']) {
                $elementOption = array('NewLine' => 'NewLine');
            }
            $workxpform->addElement($element['dataType'], $element['elementNameOrID']);
            array_push($elementNames, $element['elementNameOrID']);
            array_push($elementLabels, $element['label']);
            array_push($elementOptions, $elementOption);
            array_push($elementTypes,$element['dataType']);

            if ($element['dataType'] == 'Select' && $element['get'] != '') {
                //die(json_encode($element['getFrom']));
                if ($element['parent'] != '') {
                    $parent = $element['parent'];
                    $workxpform->$parent->setAttrib('onchange', 'displayParticularData_ForAll(this,"' . $element['getFrom'] . '","' . $element['get'] . '","' . $element['elementNameOrID'] . '","' . $element['queryWith'] . '")');
                } else {
                    $opt = $FormModel->getSelectData($element['getFrom'], $element['get']);

                    
                    $workxpform->$elementName->addMultiOption('empty_option', 'Select...');
                    foreach ($opt as $op) {
                        $workxpform->$elementName->addMultiOption($op['id'], $op[$element['get']]);
                    }
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
        $workxpform->start_date->setAttrib('title', 'Due Date.');
        $workxpform->start_date->setAttrib('maxLength', 10);
        $workxpform->start_date->setAttrib('readonly', 'readonly');
        $workxpform->start_date->setAttrib('onfocus', 'this.blur()');
        $workxpform->start_date->addValidator('NotEmpty', false, array('messages' => 'Please select start date.'));
        $workxpform->start_date->setRequired(true);

        $workxpform->end_date->setAttrib('title', 'Due Date.');
        $workxpform->end_date->setAttrib('maxLength', 10);
        $workxpform->end_date->setAttrib('readonly', 'readonly');
        $workxpform->end_date->setAttrib('onfocus', 'this.blur()');
        $workxpform->end_date->addValidator('NotEmpty', false, array('messages' => 'Please select start date.'));
        $workxpform->end_date->setRequired(true);
        //Displaying forms from DB End 
        // $jobpositions = $jobpositionmodel->getTotalPositionList();
        // $jobtitles = $workexpmodel->getjobtitles();
        //  foreach ($jobtitles as $jobtitle) {
        // //     # code...
        //     $workxpform->job_title->addMultiOption($jobtitle['id'],$jobtitle['jobtitlename']);
        // }
        // foreach ($jobpositions as $jobposition) {
        //     # code...
        //     $this->view->job_postion->addMultiOption($jobposition['id'],$jobposition['positionname']);
        // }

        $messages = $workxpform->getMessages();
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $candidateid = $this->_request->getParam('candidate_id');
        $workxpform->id_hidden->setValue($candidateid);
        $this->view->form = $workxpform;
        $this->view->elementNames = $elementNames;
        $this->view->elementLabels = $elementLabels;
        $this->view->elementOptions = $elementOptions;
        $this->view->elementTypes = $elementTypes;
        $this->view->count = array();
        $this->view->candidate_id = $this->_request->getParam('candidate_id');
        $this->view->validators = $validators;
        //die($this->_request->getParam('candidate_id'));
    }
    public function addAction()
    {
        $tempArray = explode('&', $_POST['data']);
        $data = [];
        foreach ($tempArray as $array) {
            $temp = explode("=", $array);
            if (strpos($temp[0], 'date')) {
                $data[$temp[0]] = sapp_Global::change_date($temp[1], 'database');
            } else {
                $data[$temp[0]] = $temp[1];
            }
        }
        //$data['candidate_id'] = (int)$this->_request->getParam('candidate_id');
        $data['candidate_id'] = $_POST['candidate_id'];
        //die(json_encode($data));

        //$candidatedetails = new Default_Form_selfcandidatepersonaldetail();
        $workexpmodel = new Default_Model_Workexp();
        // $candidatejobtitle = $this->_request->getParam('job_title');
        // $candidateemployer = $this->_request->getParam('employer');
        // $candidatestartdate = $this->_request->getParam('start_date');
        // $candidateenddate = $this->_request->getParam('end_date');
        // $candidatejobpostion  = $this->_request->getParam('job_postion');
        // $candidateleavingreason  = $this->_request->getParam('leaving_reason');
        // $candidateempsector  = $this->_request->getParam('employer_sector');
        // $candidateemptype  = $this->_request->getParam('employer_type');
        // $candidateengagearea  = $this->_request->getParam('engagment_area');
        // $candidateresponsibilities  = $this->_request->getParam('responsibilities');


        //$data= array("employer"=>$candidateemployer,"job_title"=>$candidatejobtitle,"job_position"=>$candidatejobpostion,"start_date"=>sapp_Global::change_date($candidatestartdate, 'database'),"end_date"=>sapp_Global::change_date($candidateenddate, 'database'),"leaving_reason"=>$candidateleavingreason,"employer_sector"=>$candidateempsector,"employer_type"=>$candidateemptype,"engagment_area"=>$candidateengagearea,"responsibilities"=>$candidateresponsibilities,"candidate_id"=>$this->_request->getParam('id'));
        $id = $workexpmodel->saveworkexp($data);
    }
    public function nextAction()
    {
        $this->_forward('index', 'candidateeducation', 'default', array('candidate_id' => $this->_request->getParam('id')));
    }
    public function addpersonaldetailAction()
    {
        $this->_redirect('addpersonaldetail');
    }
}
