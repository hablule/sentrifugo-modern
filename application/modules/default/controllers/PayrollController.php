<?php
require_once 'UserdefinedclassController.php';

class Default_PayrollController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/payroll/models/cronjob.php';
        require_once 'application/modules/default/library/utilClasses/Util.php';

    }

    public function indexAction()
    {
        $payrollmodel = new Default_Model_payroll();
        $call = $this->_getParam('call');
        if ($call == 'ajaxcall') {
            $this->_helper->layout->disableLayout();
        }
        $dashboardcall = $this->_getParam('dashboardcall');
        $refresh = $this->_getParam('refresh');
        $data = [];
        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes') {
                $perPage = DASHBOARD_PERPAGE;
            } else {
                $perPage = PERPAGE;
            }
            $sort = 'DESC';
            $by = 'approved';
            $perNo = 1;
            $searchData = '';
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'approved';
            if ($dashboardcall == 'Yes') {
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            } else {
                $perPage = $this->_getParam('per_page', PERPAGE);
            }
            $pageNo = $this->_getParam('page', 1);
            $searchData = $this->_getParam('searchData');
            $searchData = rtrim($searchData, ',');
        }
        $dataTmp = $payrollmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);
        array_push($data, $dataTmp);
        $this->view->dataArray = $data;
        $this->view->call = $call;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    public function calculateAction()
    {
        die('payroll calculate');
    }
    public function addAction()
    {
        $util = new utlis();

        $payrollform = new Default_Form_Payroll();
        $getperoids = new Default_Model_periodcontroltype();
        $payrollmodel = new Default_Model_payroll();
        $cronmodel = new Payroll_Model_cronjob();
        $id = $cronmodel->lineautoinc('main_payroll','docment_no','id');
        $doccount = $id['docment_no'] + 1;
        $auth = Zend_Auth::getInstance();
        $loginuserid = $auth->getStorage()->read()->id;
      //  Zend_Debug::dump($id);
      $menuID = PAYROLL;
        $payrollform->docno->setValue($doccount);
        $payrollform->docno->setAttrib('Readonly',true);
        if ($this->getRequest()->isPost()) {
            if ($payrollform->isValid($this->getRequest()->getPost())) {
                $data = [
                    'docment_no'=>$this->_request->getParam('docno'),
                    'paydate'=>sapp_Global::change_date($this->_request->getParam('paydate'),'database'),
                    'autogenrerated' => $util->getcheckboxvalue($this->_request->getParam('autogen')),

                ];
                
                
               try {
                $id = $payrollmodel->saveorupdate($data,'');
               } catch (\Throwable $th) {
                   Zend_Debug::dump($th->getMessage());
               }
                sapp_Global::logManager($menuID,1,$loginuserid,$id);
               $this->_redirect('payroll');
               $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Payroll updated successfully."]);
               $this->view->messages = $this->_helper->flashMessenger->getMessages();
                
            }
            $messages = $payrollform->getMessages();
            foreach ($messages as $key1 => $value) {
                foreach ($value as $key2 => $value2) {
                    $msgArray[$key1] = $value2;
                    break;
                }
            }
        }
        $this->view->msgarray = $msgArray;
        $this->view->form = $payrollform;
    }

    public function editpopupAction()
    {
        
        try {
            //code...
       
        $controllername = 'payroll';
        $payrollform = new Default_Form_Payroll();
        $payrollmodel = new Default_Model_Payroll();
        $payroll = $payrollmodel->getpayrollid($this->_request->getParam('id'));
        $payrollform->setAttrib('action',BASE_URL.'payroll/editpopup/id/'.$this->_request->getParam('id'));
        $payrollform->docno->setAttrib('Readonly',true);
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginuserid = $auth->getStorage()->read()->id;
        }
        if ($this->getRequest()->isPost()) {
            $approval = $this->_request->getParam('approve');
            if ($approval == 'Approve') {
                $data = [
                    'approved'=>sapp_Global::change_date(date('Y-m-d'),'database'),
                    'approvedby'=>$loginuserid,
                    'autogenrerated'=>'N'
                    
                ];

                $payrollmodel->saveorupdate($data,'id = '.$this->_request->getParam('id'));
                $this->_redirect('payroll');
            }
        }

        $this->view->data = $payroll;
        $this->view->controllername=$controllername;
        
        $this->view->form = $payrollform->approvalform();
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
        
    }

    public function editAction(){
        try {
            //code...
       
        $payrollform = new Default_Form_Payroll();
        $payrollmodel = new Default_Model_payroll();
        $util = new utlis();
        $id = $this->_request->getParam('id');
        $payrollform->setAttrib('action',BASE_URL.'payroll/edit/id/'.$id);
        $payroll = $payrollmodel->getpayrollid($id);
        $payrollform->docno->setAttrib('Readonly',true);
        if($payroll['autogenrerated']=='Y'){
            $payroll['autogenrerated']=1;
        }
        else{          
              $payroll['autogenrerated']=0;
        }
        
        $payrollform->docno->setValue($payroll['docment_no']);
        $payrollform->paydate->setValue($payroll['paydate']);
        $payrollform->autogen->setValue($payroll['autogenrerated']);
        
       // die();
       $auth = Zend_Auth::getInstance();
       $loginuserid = $auth->getStorage()->read()->id;
       $menuID = PAYROLL;
        if ($this->getRequest()->isPost()) {
            
            if ($payrollform->isValid($this->getRequest()->getPost())) {
               
                $data = [
                    'docment_no'=>$this->_request->getParam('docno'),
                    'paydate'=>sapp_Global::change_date($this->_request->getParam('paydate'),'database'),
                    'autogenrerated' => $util->getcheckboxvalue($this->_request->getParam('autogen')),
                ];
                
                $payrollmodel->saveorupdate($data,'id = '.$id);
                        
                $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Perk updated successfully."]);
                $this->view->messages = $this->_helper->flashMessenger->getMessages();
                sapp_Global::logManager($menuID,2,$loginuserid,$id);
                $this->_redirect('payroll');

            }
            

            

        }
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    $messages = $payrollform->getMessages();
    foreach ($messages as $key => $value) {
        foreach ($value as $key2 => $value2) {
            $msgArray[$key] = $value2;
        }
    }
        $this->view->form = $payrollform;
        $this->view->msgarray = $msgArray;
    
    }
}
