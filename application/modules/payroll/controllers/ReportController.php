<?php
class Payroll_ReportController extends Zend_Controller_Action
{
    public function init()
    {
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/payroll/models/report.php';
        require_once 'application/modules/payroll/forms/report.php';
        require_once 'application/modules/payroll/library/utilities.php';
    }

    public function indexAction()
    {


        $missmodel = new Payroll_Model_report();
        $inter_options = array();
        $call = $this->_getParam('call');
        if ($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();

        $objname = $this->_getParam('objname');
        $refresh = $this->_getParam('refresh');
        $dashboardcall = $this->_getParam('dashboardcall');
        $data = array();
        $searchQuery = '';
        $searchArray = array();

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes')
                $perPage = DASHBOARD_PERPAGE;
            else
                $perPage = PERPAGE;
            $sort = 'DESC';
            $by = 'pr.id';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $searchArray = array();
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'pr.id';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else
                $perPage = $this->_getParam('per_page', PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            $searchData = $this->_getParam('searchData');
        }
        $dataTmp = $missmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);

        $this->view->dataArray = $dataTmp;

        array_push($data, $dataTmp);
        $this->view->call = $call;
        $this->view->objectName = $objname;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    public function viewAction()
    {

        try {
            $form = new Payroll_Form_report();
            $repmode = new Payroll_Model_report();
            $util = new Payroll_Utitlites();

            $emps = $repmode->getemp();
            $id = $this->_request->getParam('id');
            $form->setAttrib('action', BASE_URL . 'payroll/report/view/id/' . $id);
            foreach ($emps as  $value) {
                $form->empid->addMultiOption($value['id'], $value['userfullname']);
            }
            if ($this->getRequest()->isPost()) {
                if ($form->isValid($this->getRequest()->getPost())) {
                    $empid = $this->_request->getParam('empid');
                    $ot = array();
                    $addunm = array();
                    $missedhours = array();
                    $empname = $form->empid->getValue();
                    $payroll = $repmode->getpayrollinfo($id);
                    $payroll_line = $repmode->getpayline($id, $empid);
                    $payroll_detail = $repmode->getpayrolldetail($payroll_line['id']);
                    $empname = $repmode->getempbyid($empname);
                    // Zend_Debug::dump($payroll_detail);
                    $subtotal = 0;
                    foreach ($payroll_detail as  $value) {

                        if ($value['addundm_id'] != null) {
                            $addunm[] = $util->getdata($value['addundm_id'], 'addundum', $empname['id']);
                        }
                        if ($value['ot_approval_id'] != null) {
                            $ot[] = $util->getdata($value['ot_approval_id'], 'overtime', $empname['id']);
                        }
                        if ($value['absent_approval_id'] != null) {
                            $missedhours[] = $util->getdata($value['absent_approval_id'], '', $empname['id']);
                        }
                        $subtotal = $subtotal + $value['amount'];
                    }
                }
            }
            $this->view->payroll = $payroll;
            $this->view->payroll_line = $payroll_line;
            $this->view->ot = $ot;
            $this->view->addundum = $addunm;
            $this->view->missedhours = $missedhours;
            $this->view->subtotal = $subtotal;
            $this->view->empname = $empname;
            $this->view->form = $form;
        } catch (\Throwable $th) {
            $this->view->form = $form;
        }
    }
}
