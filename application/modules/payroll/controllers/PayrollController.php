<?php


class Payroll_PayrollController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function indexAction()
    {
        $payrollmodel = new Payroll_Model_payroll();
        $call = $this->_getParam('call');
        if ($call=='ajaxcall') {
            $this->_helper->layout->disableLayout();
        }
        $dashboardcall = $this->_getParam('dashboardcall');
        $refresh = $this->_getParam('refresh');
        $data = array();
        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes') {
                $perPage = DASHBOARD_PERPAGE;
            }
            else{
                $perPage = PERPAGE;
            }
            $sort = 'DESC';
            $by = 'approved';
            $perNo = 1;
            $searchData = '';
        }
        else
        {
            $sort = ($this->_getParam('sort')!='')? $this->_getParam('sort'):'DESC';
            $by = ($this->_getParam('by')!='')? $this->_getParam('by'):'approved';
            if ($dashboardcall=='Yes') {
                $perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);

            }
            else
            {
                $perPage = $this->_getParam('per_page',PERPAGE);
            }
            $pageNo = $this->_getParam('page', 1);
            $searchData = $this->_getParam('searchData');
			$searchData = rtrim($searchData,',');
        }
        $dataTmp = $payrollmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData,$call,$dashboardcall);
        array_push($data,$dataTmp);
        $this->view->dataArray = $data;
        $this->view->call = $call ;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }   
}

?>