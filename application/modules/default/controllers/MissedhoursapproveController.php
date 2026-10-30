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

class Default_MissedhoursapproveController extends Zend_Controller_Action
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
           
        $auth = Zend_Auth::getInstance();
        $popConfigPermission = array();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;
        }
        $auth = Zend_Auth::getInstance();
        $access = Zend_Session::namespaceGet('access_control');
        $empids = $access['empids'];
        $perm = $access['perm'];

        $missmodel = new Default_Model_Missedhoursapprove();
        $inter_options = array();
        $call = $this->_getParam('call');
        if ($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();
        $view = Zend_Layout::getMvcInstance()->getView();

        $objname = $this->_getParam('objname');
        $refresh = $this->_getParam('refresh');
        $dashboardcall = $this->_getParam('dashboardcall', null);
        $data = array();
        $searchQuery = '';
        $searchArray = array();

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes')
                $perPage = DASHBOARD_PERPAGE;
            else
                $perPage = PERPAGE;
            $sort = 'DESC';
            $by = 'ms.sch_id';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $searchArray = array();
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ms.schedule_id';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else
                $perPage = $this->_getParam('per_page', PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            $searchData = $this->_getParam('searchData');
        }
        $dataTmp = $missmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $empids, $perm);
        $this->view->dataArray = $dataTmp;

        array_push($data, $dataTmp);
        $this->view->call = $call;
        $this->view->objectName = $objname;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
         //code...
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

        $form = new Default_Form_missedhoursapprove();
        $form->setAttrib('action', BASE_URL . 'missedhoursapprove/view/id/' . $id);
        $missModal = new Default_Model_Missedhoursapprove();
        $misshour = $missModal->missedhoursdetail($id);
        //die(json_encode($misshour));
        $this->view->misshours = $misshour;
        $this->view->form = $form;
    }

    public function editpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $controllername = 'missedhoursapprove';
        $messagestr = '';
        $successmessagestr = '';
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }

        $id = $this->getRequest()->getParam('id');
        $form = new Default_Form_missedhoursapprove();
        $form->setAttrib('action', BASE_URL . 'missedhoursapprove/editpopup/id/' . $id);
        $missModal = new Default_Model_Missedhoursapprove();

        $misshour = $missModal->missedhoursdetail($id);

        //die(json_encode($misshour));
        $amountapproved = $misshour[0]['amount_lost'];
        $empid = $misshour[0]['emp_id'];


        $form->approvedamount->setvalue($amountapproved);

        if ($this->getRequest()->getPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $approved = $this->getRequest()->getParam('managerstatus');
                $where = array('missed_hours_id=?' => $id);
                $data = array(
                    'approved' => $this->getRequest()->getParam('managerstatus'),
                    'approved_amount' => $this->getRequest()->getParam('approvedamount'),
                    'approvedby' => $loginUserId,
                    'approved_date' => gmdate("Y-m-d H:i:s")
                );

                // die(json_encode($data));
                $missModal->editmissedhours($data, $where);
                if ($approved == 'Y') {
                    $this->view->eventact = 'Approved';
                } elseif ($approved == 'N') {
                    $this->view->eventact = 'Rejected';
                } else {
                }
                $close = 'close';
                $this->view->popup = $close;
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
        $previlage = sapp_Global::permemp($empid);
        $this->view->previlage = $previlage;

        $this->view->misshours = $misshour;
        $this->view->form = $form;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();

        $this->view->controllername = $controllername;
    }
}
