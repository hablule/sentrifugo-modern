<?php


class Default_ListcandidatesController extends Zend_Controller_Action
{
    public function preDispatch()
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getcandidates','json')->initContext();
    }

    public function init()
    {
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function indexAction()
    {
        $candidatemodel = new Default_Model_Selfcandidateadd();
        $req = $candidatemodel->getRequistion();
        $call = $this->_getParam('call');
        if($call == 'ajaxcall')
            //$this->_helper->layout->disableLayout();
       $auth = Zend_Auth::getInstance();
       $refresh = $this->_getParam('refresh');
       $dashboardcall = $this->_getParam('dashboardcall');
       
       if($refresh == 'refresh')
       {
           if($dashboardcall == 'Yes')
               $perPage = DASHBOARD_PERPAGE;
           else	
               $perPage = PERPAGE;
       }
       else
       {
        $sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
        $by = 'p.id';
        if($dashboardcall == 'Yes')
				$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
			else $perPage = $this->_getParam('per_page',PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            /** search from grid - START **/
            $searchData = $this->_getParam('searchData');
       }
       
       if($auth->hasIdentity())
       {
        $loginUserId = $auth->getStorage()->read()->id;
        $loginuserGroup = $auth->getStorage()->read()->group_id;
       }

      
       $sort = 'DESC';
       $by = 'p.id';

       $searchData = '';
       $searchQuery = '';
       $dataTmp = $candidatemodel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall);
       $this->view->dataArray = $dataTmp;
       $this->view->req = $req;
       $this->view->messages = $this->_helper->flashMessenger->getMessages();
       $this->view->call = $call;
       
    }

    public function getcandidatesAction()
    {
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $cand_model = new Default_Model_Candidatedetails();
        $interviewmodel = new Default_Model_Interviewdetails();
        $interview_round_model = new Default_Model_Interviewrounddetails();
        $requi_model = new Default_Model_Requisition();
        $user_model = new Default_Model_Usermanagement();
        $ids = $this->_getParam('ids',null);
		$requisition_id = $this->_getParam('req_id',null);
        $interview_status = $this->_getParam('interview_status',null);			
		$interviewer_id = $this->_getParam('interviewer_id',null);
		$int_location = $this->_getParam('int_location',null);	
		$country = $this->_getParam('country',null); 
		$state = $this->_getParam('state',null);
		$city = $this->_getParam('city',null);
		$interview_mode = $this->_getParam('interview_mode',null);
		$interview_time = $this->_getParam('interview_time',null);
		$interview_date = $this->_getParam('interview_date',null);	
		$interview_round = $this->_getParam('interview_round',null);
        
        foreach ($ids as $key => $value) {
            $cand_model->SaveorUpdateCandidateData(['requisition_id'=>$requisition_id],'id='.$value);
            $idata = [
                'req_id' => $requisition_id,
                'candidate_id' => trim($value),
                'interview_status' => trim($interview_status),				
                'isactive' => 1,
                'createdby' => trim($loginUserId),
                'modifiedby' => trim($loginUserId),
                'createddate' => gmdate("Y-m-d H:i:s"),
                'modifieddate' => gmdate("Y-m-d H:i:s"),
            ];
          $iresult = $interviewmodel->SaveorUpdateInterviewData($idata, '');
        
            $irdata = [
                'interview_id' => $iresult,
                'req_id' => $requisition_id,
                'candidate_id' => $value,
                'interviewer_id' => $interviewer_id,
                'interview_time' => sapp_Global::change_time(trim($interview_time),'database'),
                'interview_date' => sapp_Global::change_date($interview_date, 'database'),
                'interview_mode' => $interview_mode,
                'interview_round_number' => 1,
                'interview_round' => trim($interview_round),
                'int_location' => trim($int_location),
                'int_country' => trim(intval($country)),
                'int_state'	=>trim(intval($state)),
                'int_city'	=>trim(intval($city)),					
                'isactive' => 1,
                'createdby' => trim($loginUserId),
                'modifiedby' => trim($loginUserId),
                'createddate' => gmdate("Y-m-d H:i:s"),
                'modifieddate' => gmdate("Y-m-d H:i:s"),
        ];
        $ir_result = $interview_round_model->SaveorUpdateInterviewroundData($irdata,'');

        $candidateData = $cand_model->getCandidateForView($value);
        $chdata = [
            'createdby' =>trim($loginUserId),
            'modifiedby' => trim($loginUserId),
            'requisition_id'=>$requisition_id,		
            
    ];	
    $where = "candidate_id = ".$value;
    $reqh_model = new Default_Model_Requisitionhistory();
   $history=$reqh_model->saveOrUpdateRequisitionHistory($chdata,$where);
    // to insert new recored
    $ch_data = [
            'createdby' =>trim($loginUserId),
            'modifiedby' => trim($loginUserId),
            'candidate_id' => trim($value),
            'candidate_name' => $candidateData["candidate_name"],
            'requisition_id'=>$requisition_id,		
            'createddate' => gmdate("Y-m-d H:i:s"),
            'modifieddate' => gmdate("Y-m-d H:i:s"),
            'description' => 'Candidate:'.$candidateData["candidate_name"].' has been added to Requisition:'.$requisition_data['requisition_code'].' by ',
    ];	
    $iwhere="";
   $history=$reqh_model->saveOrUpdateRequisitionHistory($ch_data,$iwhere);
  
    $history = 'Candidate:'.$candidateData["candidate_name"].' has been scheduled for an interview by ';
                    $createdby = $loginUserId;
					$modifiedby =$loginUserId;
					
					$reqh_model = new Default_Model_Requisitionhistory();
					$requisition_history = [
                                        'candidate_id' =>$value,
                                         'interview_id' =>$iresult,										
                                        'candidate_name'=>  $candidateData['candidate_name'],	
										'requisition_id' =>$requisition_id,
										'description' => $history,
										'createdby' => $createdby,
										'modifiedby' => $modifiedby,
										'isactive' => 1,
										'createddate' =>gmdate("Y-m-d H:i:s"),
										'modifieddate'=> gmdate("Y-m-d H:i:s"),
									];
					$where = '';
				  $historyId = $reqh_model->saveOrUpdateRequisitionHistory($requisition_history,$where);
              
                    
                    $candData = [
						'cand_status'=> 'Scheduled',		
						'requisition_id'=>$requisition_id,
						'modifiedby' => trim($loginUserId),
						'modifieddate' => gmdate("Y-m-d H:i:s")
				];
                $where = "id = ".$value;
				
				$candResult = $cand_model->SaveorUpdateCandidateData($candData,$where);

                //mail starts here
                $requisition_data = $requi_model->getRequisitionDataById($requisition_id); 
                $report_person_data = $user_model->getUserDataById($interviewer_id);
                $cand_data = $cand_model->getCandidateById($value);
                $mail_arr = [  'HR'=>  defined('REQ_HR_'.$requisition_data['businessunit_id'])?constant('REQ_HR_'.$requisition_data['businessunit_id']):"",                                                        
                $report_person_data['userfullname'] => $report_person_data['emailaddress']
                    ];
                    foreach($mail_arr as $ename => $email)
                    {
                        $base_url = 'http://'.$this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
                        $view = $this->getHelper('ViewRenderer')->view;
                        $this->view->emp_name = $ename;                           
                        $this->view->base_url=$base_url;
                        $this->view->candidate_name = $cand_data['candidate_name'];
                        $this->view->interview_type = $interview_mode;
                        $this->view->interview_location = $int_location;
                        $this->view->interview_date = $interview_date;                                            
                        $this->view->interview_time = sapp_Global::change_time($interview_time,'view'); 
                        $this->view->requisition_code = $requisition_data['requisition_code'];
                        $text = $view->render('mailtemplates/interviewrounds.phtml');
                        $options['subject'] = APPLICATION_NAME.': Interview schedule';
                        $options['header'] = 'Interview schedule';
                        $options['toEmail'] = $email; 
                        $options['toName'] = $ename;
                        $options['message'] = $text;
                        $options['cron'] = 'yes';
                        sapp_Global::_sendEmail($options);
                       
                    }
                    $options['subject'] = APPLICATION_NAME.': Interview schedule';
                    $options['header'] = 'Interview invite';
                    $options['toEmail'] = $cand_data['emailid']; 
                    $options['toName'] = $cand_data['candidate_name'];
                    $options['message'] = 'Dear, '.$cand_data['candidate_name'].'
                                          Thank you for applying for the position  
                                          We would like to invite you to come to our office to interview for the position. Your interview 
                                          has been scheduled '.$interview_date.', '.$interview_time.' at '.$int_location;
                    $options['cron'] = 'yes';
                    $result=sapp_Global::_sendEmail($options);
                    
                    //mail ends here
                   
                    //$candResult='u';
        }
        
       if($candResult=='update')
       {
        $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Interview scheduled successfully."]);
         $this->_helper->json(['result'=>'success']); 

         return 'success';
         
       }
       else{
            $this->_helper->json(['result'=>'error']); 
          
           return 'error';
       }
      
    }
   

    

}





?>