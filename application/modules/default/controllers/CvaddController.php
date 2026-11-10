<?php



class Default_CvaddController extends Zend_Controller_Action
{
    private  $forms;
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
    
        $this->forms = new Default_Form_cvadd();
        $FormModel = new Default_Model_Formview();
        $res = new Default_Model_Selfcandidateadd();
       // $this->forms->coverletter->setValue('mikias');
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->form = $this->forms;
        //$this->forms->id = $this->_request->getParam("candidate_id");
        $req = $res->getallrequistions();
        //Displaying forms from DB Start
        $cvElements = $FormModel->getActiveElements('CV');
        $cvElementNames = [];
        $cvElementLabels = [];
        $cvElementOptions = [];
        $cvElementTypes = [];
        foreach($cvElements AS $element){
            $elementOption = [];
            if($element['occupyWholeLine']=='1'){
                $elementOption = ['OccupyLine'=>'OccupyLine'];
            }
            else if($element['startNewLine']=='1'){
                $elementOption = ['NewLine'=>'NewLine'];
            }
            $this->forms->addElement($element['dataType'],$element['elementNameOrID']);
            
            array_push($cvElementNames, $element['elementNameOrID']);
            array_push($cvElementLabels,$element['label']);
            array_push($cvElementOptions,$elementOption);
            array_push($cvElementTypes,$element['dataType']);
        }
        //Displaying forms from DB End
        // foreach ($req as $job) {
        //     $this->forms->job_type->addMultiOption($job['requisition_id'],$job['jobtitlename']);
        // }
        $this->view->cvElementNames = $cvElementNames;
        $this->view->cvElementLabels = $cvElementLabels;
        $this->view->cvElementOptions = $cvElementOptions;
        $this->view->cvElementTypes = $cvElementTypes;
        $this->view->candidate_id = $this->_request->getParam('candidate_id');
        $this->view->count = [];
            //code...
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
        
       
    }
    public function addcvAction()
    {
        $FormModel = new Default_Model_Formview();
        $cvElements = $FormModel->getActiveElements('CV');
        $cvdata = [];
        foreach($cvElements AS $element){
            if (
                !($element['dataType'] == "Button") && !($element['dataType'] == "Hidden") && !($element['dataType'] == "Captcha") &&
                !($element['dataType'] == "Reset")
            ){
                if((int)$this->_request->getParam($element['elementNameOrID'])){
                    $cvdata[$element['elementNameOrID']] =  (int)$this->_request->getParam($element['elementNameOrID']);
                }else{
                    
                    if(str_contains($element['elementNameOrID'],"date")){
                        $date = $this->_request->getParam($element['elementNameOrID']);
                        
                        $cvdata[$element['elementNameOrID']] =  sapp_Global::change_date($date, 'database');
                    }else{
                        $cvdata[$element['elementNameOrID']] =  $this->_request->getParam($element['elementNameOrID']);
                    }
            }
            }
        }
        //die(json_encode($cvdata));
        $cvmodel  = new Default_Model_Cvadd();
        $file_original_names = $this->getRequest()->getParam('file_original_names');
		$file_new_names = $this->getRequest()->getParam('file_new_names');
        // $sourcetracking = $this->getRequest()->getParam('sourcetracking');
        // $coverletter = $this->getRequest()->getParam('coverletter');
        // $empnoticeperiod = $this->getRequest()->getParam('employeenoticeperiod');
        // $cvdata = array('cv'=>json_encode(array('file_original_name'=>$file_original_names,'file_new_name'=>$file_new_names)),'cover_letter'=>$coverletter,'employement_notice_peroid'=>$empnoticeperiod,'source_tracking'=>$sourcetracking,'candidate_id'=>1);
        $cvdata['cv'] = $file_new_names;
        $cvdata['candidate_id'] = $this->_request->getParam('candidate_id');
     
       $bool=$cvmodel->addcv($cvdata);
        if(false)
        {
            $this->_helper->json(['result'=>'success']);
            return $this->_helper->json(['result'=>'success']);
        }
        else{
            return $this->_helper->json(['result'=>'error']);
        }
        
        
       
    }
    public function prevAction(){
        $this->_redirect('education/index');
    }

    public function uploadsaveAction() 
    {		
        $user_id = sapp_Global::_readSession('id');
        $filedata = [];
        
    	// Validate file with size greater than default(Upload Max Filesize)limit
        if ($_FILES["myfile"]["size"] == 0 || $_FILES["myfile"]["size"] > (2*1024*1024)) {
            $this->_helper->json(['error' => 'filesize']);
        } else if(isset($_FILES["myfile"])) {
            $fileName = $_FILES["myfile"]["name"];
            $fileName = preg_replace('/[^a-zA-Z0-9.\']/', '_', $fileName);			  	
            $newName  = time().'_'.$user_id.'_'.str_replace(' ', '_', $fileName);

            $filedata['original_name'] = $fileName;
            $filedata['new_name'] = $newName;
            
        	if (true) {
        		move_uploaded_file($_FILES["myfile"]["tmp_name"],CV_UPLOAD_PATH.$newName);
        	} else {
        		move_uploaded_file($_FILES["myfile"]["tmp_name"],CV_UPLOAD_TEMP_PATH.$newName);
        	}

            $this->_helper->json(['filedata' => $filedata]);
            
        }        
    }
       
          
}


?>