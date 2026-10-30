<?php

class Default_fingerprintconfigController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        $fingerPrintConfigModel = new Default_Model_fingerprintconfig();
        $call = $this->_getParam('call');
        if ($call == 'ajaxcall') {
            $this->_helper->layout->disableLayout();
        }
        $refresh = $this->_getParam('refresh');
        $dashboardcall = $this->_getParam('dashboardcall');
        $data = array();
        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes') {
                $perPage = DASHBOARD_PERPAGE;
            } else {
                $perPage = PERPAGE;
            }
            $sort = 'DESC';
            $by = 'id';
            $perpage = 1;
            $searchData = '';
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'id';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else
                $perPage = $this->_getParam('per_page', PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            $searchData = $this->_getParam('searchData');
            $searchData = rtrim($searchData, ',');
        }

        $dataTmp = $fingerPrintConfigModel->getGrid($sort, $by, $pageNo, $perPage, $searchData, $call, $dashboardcall);
        array_push($data, $dataTmp);
        $this->view->data = $data;
        $this->view->call = $call;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    public function addAction()
    {
        $fingerprintconfig_Form = new Default_Form_fingerprintconfig();
        $this->view->form = $fingerprintconfig_Form;
    }
    public function editAction()
    {
        try {

            $fingerprintconfig_Form = new Default_Form_fingerprintconfig();
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }
            $id = $this->getRequest()->getParam('id');
            $callval = $this->getRequest()->getParam('call');
            if ($callval == 'ajaxcall')
                $this->_helper->layout->disableLayout();

            $fingerprintconfig_Model = new Default_Model_fingerprintconfig();

            try {
                if ($id) {
                    if (is_numeric($id) && $id > 0) {
                        $data = $fingerprintconfig_Model->getFingerprintConfigData($id);
                       
                        if (!empty($data)) {
                            $fingerprintconfig_Form->populate($data);
                            $fingerprintconfig_Form->submit->setLabel('Update');
                            $this->view->id = $id;
                            $this->view->ermsg = '';
                        } else {
                            $this->view->ermsg = 'norecord';
                        }
                    } else {
                        $this->view->ermsg = 'norecord';
                    }
                } else {
                    $this->view->ermsg = '';
                }
            } catch (Exception $e) {
                $this->view->ermsg = 'nodata';
            }

            if ($this->getRequest()->isPost()) {

                if ($fingerprintconfig_Form->isValid($this->_request->getPost())) {

                    $id = $this->_request->getParam('id');
                    $securityLevel = $this->_request->getParam('security_level');
                    $fastMode = $this->_request->getParam('fast_mode');
                    $sensitivity = $this->_request->getParam('sensitivity');
                    $sensorMode = $this->_request->getParam('sensor_mode');
                    $templateFormat = $this->_request->getParam('template_format');
                    $scanTimeout = $this->_request->getParam('scan_timeout');
                    $showImage = $this->_request->getParam('show_image');
                    $LFDLevel = $this->_request->getParam('ifd_level');
                    $checkDuplicate = $this->_request->getParam('check_duplicate');
                    $actionflag = '';
                    $tableid  = '';
                    $data = array(
                        'security_level' => $securityLevel,
                        'fast_mode' => $fastMode,
                        'sensitivity' => $sensitivity,
                        'sensor_mode' => $sensorMode,
                        'template_format' => $templateFormat,
                        'scan_timeout' => $scanTimeout,
                        'show_image' => $showImage,
                        'ifd_level' => $LFDLevel,
                        'check_duplicate' => $checkDuplicate,
                    );

                    if ($id != '') {
                        $where = array('id=?' => $id);
                        $actionflag = 2;
                    } else {
                        $where = '';
                        $actionflag = 1;
                    }
                    $Id = $fingerprintconfig_Model->SaveorUpdateData($data, $where);
                    if ($Id == 'update') {
                        $tableid = $id;
                        $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Configuration updated successfully."));
                    } else {
                        $tableid = $Id;
                        $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Configuration  added successfully."));
                    }
                    $menuID = GENDER;
                    $result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $tableid);
                    //$this->_redirect('fingerprintconfig');
                } else {
                    $messages = $fingerprintconfig_Form->getMessages();
                    foreach ($messages as $key => $val) {
                        foreach ($val as $key2 => $val2) {
                            $msgarray[$key] = $val2;
                            break;
                        }
                    }
                    $this->view->msgarray = $msgarray;
                }
            }
            $this->view->form = $fingerprintconfig_Form;
            //code...
        } catch (\Throwable $th) {
            //throw $th
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function viewAction()
    {
        $fingerprintconfig_Form = new Default_Form_fingerprintconfig();
        $this->view->form = $fingerprintconfig_Form;
    }
}
