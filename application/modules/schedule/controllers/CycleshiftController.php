<?php


class Schedule_CycleshiftController extends Zend_Controller_Action
{
    public function init()
    {
        require 'application/modules/default/library/utilClasses/Util.php';
    }

    public function indexAction()
    {
        try {
            //code...

            $model = new Schedule_Model_Cycleshift();

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
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function editAction()
    {
        $form = new Schedule_Form_Cycleshift();
        $model = new Schedule_Model_Cycleshift();
        $id = $this->_getParam('id');
        if ($id != '') {
            $cycleShiftData = $model->getCycleShiftById($id);
            //die(Zend_Debug::dump($cycleShiftData));
            $form->shiftid->setValue($cycleShiftData['shift_id']);
            $form->schedulecycle_id->setValue($cycleShiftData['schedulecycle_id']);
            //  die(Zend_Debug::dump($cycleShiftData));
            //die(Zend_Debug::dump($cycleShiftData));
            $form->populate($cycleShiftData);

            if ($this->getRequest()->isPost()) {
                $this->save($form);
            }
        }

        $this->view->form = $form;
    }

    public function viewAction()
    {

        die('help');
    }

    public function addpopupAction()
    {

        try {


            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
            $form = new Schedule_Form_Cycleshift();

            if ($this->_getParam('shift') != '') {
                $shifts = array();
                $shiftParam = json_decode($this->_getParam('shift'), true);

                foreach ($shiftParam as $shiftData) {

                    array_push($shifts, $shiftData['shift_id']);
                }
                $form->shiftid->setValue($shifts);
                $form->description->setValue($shiftParam[0]['description']);
                $form->isactive->setValue(utlis::getcheckboxvalue($shiftParam[0]['isactive'], 'form'));
            }
            if ($this->getRequest()->isPost()) {
                // utlis::isoverlappingshift($this->_getParam('shiftid'));
                if (!utlis::isoverlappingshift($this->_getParam('shiftid'))) {
                    if ($form->isValid($this->getRequest()->getPost())) {
                        $cycleshiftdata = array();

                        foreach ($this->_getParam('shiftid') as $key => $value) {
                            $data = array(
                                'shift_id' => $value,
                                'description' => $this->_getParam('description'),
                                'isactive' => $this->_getParam('isactive'),
                            );
                            array_push($cycleshiftdata, $data);
                        }


                        $cycleShift = new Zend_Session_Namespace('popupdata');
                        $cycleShift->cycleshift = $cycleshiftdata;


                        $this->view->pop = 'close';
                        $this->view->controllername = 'cycleshift';
                        $this->view->shiftData = json_encode($cycleshiftdata);
                        $this->view->eventact = "added";
                    } else {
                        $messages = $form->getMessages();
                        foreach ($messages as $key => $val) {
                            foreach ($val as $key2 => $val2) {
                                $msgarray[$key] = $val2;
                                break;
                            }
                        }
                    }
                } else {
                    $msgarray['shiftid'] = 'Shift overlap';
                }
            }

            $this->view->msgarray = $msgarray;
            $this->view->form = $form;
        } catch (\Exception $e) {
            Zend_Debug::dump($e->getMessage());
        }
    }

    public function editpopupAction()
    {
        try {

            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
            $form = new Schedule_Form_Cycleshift();
            $model = new Schedule_Model_Cycleshift();
            $id = $this->_getParam('id');

            if ($id != '') {
                $cycleshiftData = $model->getCycleShift($id);

                $shifts = array();
                foreach ($cycleshiftData as $value) {
                    array_push($shifts, $value['shift_id']);
                }
                $form->shiftid->setValue($shifts);
                $form->populate($cycleshiftData[0]);
            }


            if ($this->getRequest()->isPost()) {

                if (!utlis::isoverlappingshift($this->_getParam('shiftid'))) {

                    if ($form->isValid($this->getRequest()->getPost())) {


                        $db_shift = array_diff($shifts, $form->shiftid->getValue());
                        $form_shift = array_diff($form->shiftid->getValue(), $shifts);
                        /* Zend_Debug::dump($db_shift,'test1'); */

                        if (count($db_shift) == 0 && count($form_shift) == 0) {
                            $data = array(
                                'description' => $this->_getParam('description'),
                                'isactive' => utlis::getcheckboxvalue($this->_getParam('isactive'))
                            );
                            $model->SaveorUpdateData($data, 'schedulecycle_id=' + $id);
                        }
                        if (count($form_shift) > 0) {
                            foreach ($form_shift as $value) {
                                $data = array(
                                    'schedulecycle_id' => $cycleshiftData[0]['schedulecycle_id'],
                                    'shift_id' => $value,
                                    'description' => $this->_getParam('description'),
                                    'isactive' => utlis::getcheckboxvalue($this->_getParam('isactive'))
                                );
                                $model->SaveorUpdateData($data, '');
                            }
                        }
                        if (count($db_shift) != count($shifts)) {
                            if (count($db_shift) > 0) {
                                foreach ($db_shift as $value) {
                                    $data = array(
                                        'schedulecycle_id' => $cycleshiftData[0]['schedulecycle_id'],
                                        'shift_id' => $value,
                                        'description' => $this->_getParam('description'),
                                        'isactive' => utlis::getcheckboxvalue($this->_getParam('isactive'), 'database')
                                    );
                                }
                                $model->deleteCycleshift($value, $id);
                            }
                            $this->view->pop = 'close';
                        }
                    } else {

                        $messages = $form->getMessages();
                        foreach ($messages as $key => $val) {
                            foreach ($val as $key2 => $val2) {
                                $msgarray[$key] = $val2;
                                break;
                            }
                        }
                        $this->view->msgarray = $msgarray;
                    }
                }
                //die();
            }

            $this->view->controllername = 'cycleshift';
            $this->view->form = $form;
            $this->view->msgarray = $msgarray;
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }


    public function checkoverlayAction()
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('checkoverlay', 'json')->initContext();
        $this->_helper->json();
    }

    public function save($appraisalCategoryForm)
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $cyclemodel = new Schedule_Model_Cycleshift();
        $msgarray = array();

        if ($appraisalCategoryForm->isValid($this->_request->getPost())) {
            try {

                $id = $this->_request->getParam('id');
                $actionflag = '';
                $tableid  = '';

                $data = array(
                    'schedulecycle_id' => $this->_getParam('schedulecycle_id'),
                    'shift_id' => $this->_getParam('shift_id'),
                    'description' => $this->_getParam('description'),
                    'isactive' => $this->_getParam('isactive')
                );
                if ($id != '') {
                    $where = array('id=?' => $id);
                    $actionflag = 2;
                } else {
                    $data['createdby'] = $loginUserId;
                    $data['createddate'] = gmdate("Y-m-d H:i:s");
                    $data['isactive'] = 1;
                    $where = '';
                    $actionflag = 1;
                }
                $Id = $cyclemodel->SaveorUpdateData($data, $where);
                if ($Id == 'update') {
                    $tableid = $id;
                    $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Cycle shift updated successfully."));
                } else {
                    $tableid = $Id;
                    $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Cycle shift added successfully."));
                }
                $menuID = 359;
                $result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $tableid);
            } catch (Exception $e) {
                $msgarray['category_name'] = "Something went wrong, please try again.";
                return $msgarray;
            }
        } else {
            $messages = $appraisalCategoryForm->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgarray[$key] = $val2;
                    break;
                }
            }
            return $msgarray;
        }
    }
}
