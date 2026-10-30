<?php

class Schedule_SchedulecycleshiftController extends Zend_Controller_Action
{
    public function init()
    {
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
      //  require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
        require_once 'application/modules/default/library/utilClasses/Util.php';

    }
    public function indexAction()
    {
        $model = new Schedule_Model_Schedulecycleshift();
      
        $call = $this->_getParam('call');
        if ($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();
        $view = Zend_Layout::getMvcInstance()->getView();
        $objname = $this->_getParam('objname');
        $refresh = $this->_getParam('refresh');
        $data = array();
        $searchQuery = '';
        $searchArray = array();
        $tablecontent = '';
        $dashboardcall = $this->_getParam('dashboardcall');

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes')
                $perPage = DASHBOARD_PERPAGE;
            else
                $perPage = PERPAGE;
            $sort = 'DESC';
            $by = 'sc.modifieddate';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $searchArray = array();
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'sc.modifieddate  ';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else $perPage = $this->_getParam('per_page', PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            /** search from grid - START **/
            $searchData = $this->_getParam('searchData');

            /** search from grid - END **/
        }
        $dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');

        array_push($data, $dataTmp);
        $this->view->dataArray = $dataTmp;
        $this->view->call = $call;
        $this->view->dashboardcall = $dashboardcall;
        
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }

    public function editAction()
    {
        $cycleShiftForm = new Schedule_Form_Schedulecycleshift();
        $this->view->form = $cycleShiftForm;
    }
    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $cycleShiftForm = new Schedule_Form_Schedulecycleshift();
        $this->view->form = $cycleShiftForm;
    }
    public function viewAction()
    {
        $id = $this->_getParam('id');
        $schModel = new Schedule_Model_Schedulecycleshift();
        $schData = $schModel->getSchedulecycleshiftById($id);
        $this->view->data = $schData;
    }
}
