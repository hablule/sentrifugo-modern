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
/**
 * Sentrifugo - Open Source HRMS
 *
 * @category   Sentrifugo
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @modified  Sentrifugo v3.2
 * @date	  June 2024
 */
class Default_MenugroupController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    protected $_options;

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Menugroup();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            $this->view->loginuserRole  = $this->_loginuserRole;
            $this->view->loginuserGroup = $this->_loginuserGroup;
        }
        require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
    }

    public function preDispatch()
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')->addActionContext('delete', 'json')->initContext();
    }

    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') $this->_helper->layout->disableLayout();

            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);
            $searchData    = $request->getParam('searchData');

            $sort    = $request->getParam('sort', 'DESC');
            $by      = $request->getParam('by', 'id');
            $perPage = ($dashboardcall == 'Yes') ? $request->getParam('per_page', DASHBOARD_PERPAGE) : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'DESC'; $by = 'id'; $page = 1; $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);
            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;

            // --- MESSAGE HANDLING FIX ---
            // 1. Check for standard FlashMessenger messages
            $messages = $this->_helper->flashMessenger->getMessages();
            
            // 2. Check for Custom Session Messages (Set by saveAction)
            if (isset($_SESSION['menugroup_msg'])) {
                // Determine class (success/error)
                $cls = isset($_SESSION['menugroup_cls']) ? $_SESSION['menugroup_cls'] : 'success';
                
                // Append to standard messages array format
                $messages[] = array($cls => $_SESSION['menugroup_msg']);
                
                // Clear the session so it doesn't show again on refresh
                unset($_SESSION['menugroup_msg']);
                unset($_SESSION['menugroup_cls']);
            }

            $this->view->messages = $messages;

        } catch (Exception $e) {
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id);
        $this->render('save');
    }

    public function saveAction()
    {
        $form = new Default_Form_Menugroup();
        $request = $this->getRequest();
        $response = array('result' => 'error', 'message' => 'Invalid data');

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $id = (int)$request->getParam('id');
                    
                    // Determine if we need to cascade a deactivation
                    $isBecomingInactive = false;
                    $newStatus = (int)$request->getParam('isactive');

                    if ($id > 0 && $newStatus === 0) {
                        // Fetch the old record BEFORE we save the new one to see if it changed
                        $oldRecord = $this->_model->getRecordById($id);
                        if ($oldRecord && $oldRecord['isactive'] == 1) {
                            $isBecomingInactive = true;
                        }
                    }

                    $data = array(
                        'name'            => trim($request->getParam('name')),
                        'code'            => trim($request->getParam('code')),
                        'backgroundcolor' => trim($request->getParam('backgroundcolor')),
                        'description'     => trim($request->getParam('description')),
                        'isactive'        => $newStatus,
                    );

                    // --- File Upload Handling ---
                    if ($form->iconpath->isUploaded()) {
                        $form->iconpath->receive();
                        $data['iconpath'] = basename($form->iconpath->getFileName());
                    }

                    if (!$id) {
                        $data['createdby']   = $this->_loginUserId;
                        $data['createddate'] = gmdate("Y-m-d H:i:s");
                        $savedId = $this->_model->saveRecord($data);
                        $msg = 'Added';
                    } else {
                        $data['modifiedby']   = $this->_loginUserId;
                        $data['modifieddate'] = gmdate("Y-m-d H:i:s");
                        $this->_model->saveRecord($data, $id);
                        $savedId = $id;
                        $msg = 'Updated';
                    }

                    // --- CASCADING LOGIC ---
                    if ($isBecomingInactive) {
                        $menuModel = new Default_Model_Menu();
                        $menuModel->cascadeGroupDeactivation($savedId);
                    }

                    $db->commit();
                    $response = array('result' => 'saved', 'nomessage' => 'no', 'message' => "Menu Group $msg successfully.");
                    $_SESSION['menugroup_msg'] = $response['message'];
                    $_SESSION['menugroup_cls'] = 'success';

                } catch (Exception $e) {
                    $db->rollBack();
                    if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
                    $response['message'] = 'Something went wrong, please try again later.';
                }
            } else {
                $response['message'] = $form->getMessages();
            }
        }
        $this->_helper->json($response);
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'menugroup';
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Menugroup();

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
                $this->view->currentIcon = $data['iconpath']; // Pass for display in view
            }
        }

        if ($readonly) {
            foreach ($form->getElements() as $el) $el->setAttrib('disabled', 'disabled');
            $form->removeElement('submit');
        }

        return $form;
    }
}