<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Schedule_ManageremployeeovertimeController extends Zend_Controller_Action
{

    private $options;
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
            //code...
            $auth = Zend_Auth::getInstance();
            $popConfigPermission = [];
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
                $loginuserRole = $auth->getStorage()->read()->emprole;
                $loginuserGroup = $auth->getStorage()->read()->group_id;
            }
            $auth = Zend_Auth::getInstance();
            $access = Zend_Session::namespaceGet('access_control');
           
            $empids = $access['empids'];
            $perm = $access['perm'];
            $overtimemodel = new Schedule_Model_Overtimerequest();
            $form = new Schedule_Form_managerovertimerequest();
            $inter_options = [];
            $call = $this->_getParam('call');
            if ($call == 'ajaxcall')
                $this->_helper->layout->disableLayout();

            $objname = $this->_getParam('objname');
            $refresh = $this->_getParam('refresh');
            $dashboardcall = $this->_getParam('dashboardcall');
            $data = [];
            $searchQuery = '';
            $searchArray = [];

            if ($refresh == 'refresh') {
                if ($dashboardcall == 'Yes')
                    $perPage = DASHBOARD_PERPAGE;
                else
                    $perPage = PERPAGE;
                $sort = 'DESC';
                $by = 'oa.OT_id';
                $pageNo = 1;
                $searchData = '';
                $searchQuery = '';
                $searchArray = [];
            } else {
                $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
                $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'oa.OT_id';
                if ($dashboardcall == 'Yes')
                    $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
                else
                    $perPage = $this->_getParam('per_page', PERPAGE);
                $pageNo = $this->_getParam('page', 1);
                $searchData = $this->_getParam('searchData');
            }

            $dataTmp = $overtimemodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $empids, $perm);
            $this->view->dataArray = $dataTmp;

            array_push($data, $dataTmp);
            $this->view->call = $call;
            $this->view->objectName = $objname;
            $this->view->messages = $this->_helper->flashMessenger->getMessages();
            $this->view->form = $form;
        } catch (\Throwable $th) {

            Zend_Debug::dump($th->getMessage());
        }
    }

    public function viewAction()
    {

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $id = $this->getRequest()->getParam('id');

        $form = new Schedule_Form_managerovertimerequest();

        $form->setAttrib('action', BASE_URL . 'manageremployeeovertime/edit/id/' . $id);
        $overtimeModal = new Schedule_Model_Overtimerequest();
        $empovertime = $overtimeModal->overtimedetail($id);
        $stat = $empovertime[0]['approved'];
        $emp_id = $empovertime[0]['emp_id'];
        if ($stat == "N") {
            $form->managerstatus->clearMultiOptions();
            $form->managerstatus->addMultiOption('Y', 'Approve');
            $form->managerstatus->addMultiOption('P', 'Pending');
        } else if ($stat == "Y") {
            $form->managerstatus->clearMultiOptions();
            $form->managerstatus->addMultiOption('N', 'Reject');
            $form->managerstatus->addMultiOption('P', 'Pending');
        }
        $this->view->empovertimes = $empovertime;
        $this->view->form = $form;

        if ($this->getRequest()->getPost()) {

            $where = ['OT_approval_id=?' => $id];
            $data = [
                'approved' => $this->getRequest()->getParam('managerstatus')

            ];
            $overtimeModal->editovertime($data, $where);
            $this->_redirect('manageremployeeovertime');
        }
    }

    public function editpopupAction()
    {
        try {
            //code...

            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

            $controllername = 'manageremployeeovertime';
            $messagestr = '';
            $successmessagestr = '';
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }

            $id = $this->getRequest()->getParam('id');

            $form = new Schedule_Form_managerovertimerequest();

            $form->setAttrib('action', BASE_URL . 'manageremployeeovertime/editpopup/id/' . $id);
            $overtimeModal = new Schedule_Model_Overtimerequest();
            $empovertime = $overtimeModal->overtimedetail($id);
            $stat = $empovertime[0]['approved'];
            $amountdue = $empovertime[0]['amount_due'];
            $earnedtime = $empovertime[0]['earned_time'];
            $amountapproved = $amountdue * $earnedtime;
            $form->approvedamount->setvalue($amountapproved);

            $state = $this->getRequest()->getParam('managerstatus');


            if ($this->getRequest()->getPost()) {
                if ($form->isValid($this->_request->getPost())) {
                    $approved = $this->getRequest()->getParam('managerstatus');

                    $where = ['OT_approval_id=?' => $id];
                    $data = [
                        'approved' => $this->getRequest()->getParam('managerstatus'),
                        'approved_amount' => $this->getRequest()->getParam('approvedamount'),
                        'approvedBy' => $loginUserId,
                        'approved_date' => gmdate("Y-m-d H:i:s")
                    ];
                    // die(json_encode($data));
                    $overtimeModal->editovertime($data, $where);
                    if ($approved == 'Y') {
                        $this->view->eventact = 'Approved';
                    } elseif ($approved == 'N') {
                        $this->view->eventact = 'Rejected';
                    } else {
                    }
                    $close = 'close';
                    $this->view->popup = $close;

                    //$this->_forward('index','manageremployeeovertime');

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
            $this->view->empovertimes = $empovertime;
            $previlage = sapp_Global::permemp($empovertime[0]['emp_idb']);
            $this->view->previlage = $previlage;
            $this->view->form = $form;
            $this->view->messages = $this->_helper->flashMessenger->getMessages();

            $this->view->controllername = $controllername;
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function deleteAction()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }

        $id = $this->_request->getParam('objid');
        $deleteflag = $this->_request->getParam('deleteflag');
        $messages['message'] = '';
        $messages['msgtype'] = "";
        $actionflag = 3;
        $menuID = EMPLOYEEOVERTIME;
        if ($id) {
            $model = new Schedule_Model_Overtimerequest();


            $where = ['OT_approval_id=?' => $id];
            $Id = $model->DeleteData($where);
            sapp_Global::logManager($menuID, $actionflag, $loginUserId, $id);
            if ($Id == 'delete') {
                $messages['message'] = 'Overtime Request deleted successfully.';
                $messages['msgtype'] = 'success';
            } else {
                $messages['message'] = 'Overtime Request cannot be deleted.';
                $messages['msgtype'] = 'error';
            }
        } else {
            $messages['message'] = 'Overtime Request cannot be deleted.';
            $messages['msgtype'] = 'error';
        }
        if ($deleteflag == 1) {
            if ($messages['msgtype'] == 'error') {
                $this->_helper->getHelper("FlashMessenger")->addMessage(["error" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag]);
            }
            if ($messages['msgtype'] == 'success') {
                $this->_helper->getHelper("FlashMessenger")->addMessage(["success" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag]);
            }
        }
        $this->_helper->json($messages);
    } // end of delete action
    public function closepopup()
    {
    }
}
