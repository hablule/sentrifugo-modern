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
 * Account Class Type Controller
 *
 * Manages the lookup data entry for Account Class Types.
 * Refactored to comply with SSOT v6.3.
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_AccountclasstypeController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    public function init()
    {
        $this->_model   = new Default_Model_Accountclasstype();
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = (int)$user->id;
            $this->_loginuserRole  = (int)$user->emprole;
            $this->_loginuserGroup = (int)$user->group_id;

            // SSOT v6.3 Mandatory View Assignments
            $this->view->loginuserRole  = $this->_loginuserRole;
            $this->view->loginuserGroup = $this->_loginuserGroup;
        }

        require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
    }

    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                echo Zend_Json::encode(array('login' => 'failed'));
                exit;
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
                    ->addActionContext('delete', 'json')
                    ->addActionContext('makeactiveinactive', 'json')
                    ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call    = (string) $request->getParam('call', '');

            if ($call === 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $refresh       = (string) $request->getParam('refresh', '');
            $dashboardcall = (string) $request->getParam('dashboradcall', '');
            $page          = (int) $request->getParam('page', 1);
            $searchData    = (string) $request->getParam('searchData', '');

            // SSOT v6.3: Default sort human-readable (name)
            $sort    = (string) $request->getParam('sort', 'ASC');
            $by      = (string) $request->getParam('by', 'name'); 
            
            $perPage = ($dashboardcall === 'Yes') 
                       ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE) 
                       : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'ASC'; $by = 'name'; $page = 1; $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);
            
            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;

            $this->view->messages  = $this->_helper->flashMessenger->getMessages();

        } catch (\Throwable $e) {
            if (APPLICATION_ENV === 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int) $this->getRequest()->getParam('id', 0);
        $form = $this->_prepareForm($id, true);

        if (!$form) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->form = $form;
            $this->view->id   = $id;
        }

        $this->view->controllername = 'accountclasstype';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        // Permission check (Ensure ACCOUNTCLASSTYPE is defined in your constants)
        if (sapp_Global::_checkprivileges(ACCOUNTCLASSTYPE, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        if (sapp_Global::_checkprivileges(ACCOUNTCLASSTYPE, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $id = (int) $this->getRequest()->getParam('id', 0);
        $form = $this->_prepareForm($id);
        
        if (!$form) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->form = $form;
            $this->view->id   = $id;
        }

        $this->render('save');
    }

    public function saveAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_redirect('accountclasstype/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                $data = array(
                    'code'         => trim((string) $request->getParam('code', '')),
                    'name'         => trim((string) $request->getParam('name', '')),
                    'description'  => trim((string) $request->getParam('description', '')),
                    'isactive'     => (int) $request->getParam('isactive', 1)
                );

                $actionFlag = 2; // Edit
                $saveMode   = 'updated';

                if (!$id) {
                    $actionFlag = 1; // Add
                    $saveMode   = 'added';
                    $savedId    = $this->_model->saveRecord($data);
                } else {
                    $this->_model->saveRecord($data, $id);
                    $savedId    = $id;
                }

                // Logging
                sapp_Global::logManager(ACCOUNTCLASSTYPE, $actionFlag, $this->_loginUserId, $savedId);
                $db->commit();

                // Set FlashMessenger for the redirect page
                $this->_helper->flashMessenger->addMessage(array('success' => "Account Class Type {$saveMode} successfully."));

                // SSOT v6.3 Strict AJAX JSON Response (Success with Redirect)
                $this->_helper->json(array(
                    'result'    => 'saved',
                    'nomessage' => 'yes'
                ));

            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("AccountClassType Save Error: " . $e->getMessage()); 
                
                // SSOT v6.3 Strict AJAX JSON Response (Exception)
                $this->_helper->json(array(
                    'result'  => 'exception', 
                    'message' => 'System error occurred.',
                    'msgtype' => 'error'
                ));
            }
        } else {
            // SSOT v6.3 Strict AJAX JSON Response (Validation Error - Raw Array)
            echo Zend_Json::encode($form->getMessages());
            exit;
        }
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS TOGGLE
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('objid', 0);
        
        if ($id) {
            if (sapp_Global::_checkprivileges(ACCOUNTCLASSTYPE, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(array('result' => 'error', 'message' => 'Permission Denied.'));
            }

            try {
                // Dependency check (SSOT v6.3)
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("Record is in use and cannot be deleted.");
                } 
                
                $this->_model->deleteRecord($id);
                sapp_Global::logManager(ACCOUNTCLASSTYPE, 3, $this->_loginUserId, $id);
                
                // Standard Success Response
                return $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success'));
                
            } catch (\Throwable $e) {
                error_log("AccountClassType Delete Error: " . $e->getMessage());
                return $this->_helper->json(array('result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error'));
            }
        }
        
        return $this->_helper->json(array('result' => 'error', 'message' => 'Invalid request'));
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id     = (int) $this->getRequest()->getParam('id', 0);
            $status = (int) $this->getRequest()->getParam('status', 0);
            
            $this->_model->toggleStatus($id, $status);
            sapp_Global::logManager(ACCOUNTCLASSTYPE, 4, $this->_loginUserId, $id);
            
            $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.'));
        } catch (\Throwable $e) {
            error_log("AccountClassType Toggle Error: " . $e->getMessage());
            $this->_helper->json(array('result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.'));
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * SSOT v6.3 "On-The-Fly" Popup Pattern
     */
    public function addpopupAction()
    {
        // 1. Shift layout to popup shell
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        
        $prev_cntrl = (string) $this->getRequest()->getParam('prev_cntrl', '');
        $user_id    = (string) $this->getRequest()->getParam('user_id', '');

        $form = $this->_prepareForm();
        
        // Ensure form posts to savepopupAction or handles custom logic via generic popup handler
        $form->setAction(BASE_URL . 'accountclasstype/savepopup/prev_cntrl/' . $prev_cntrl);
        $form->setAttrib('id', 'popupformid');

        // 3. Remove saveDetails onclick for standard/modal POST behavior
        $form->getElement('submit')->setAttrib('onclick', null);

        $this->view->form = $form;
        $this->view->prev_cntrl = $prev_cntrl;
        $this->view->user_id = $user_id;
    }

    /**
     * Internal Form Preparation
     * 
     * @param int|null $id
     * @param bool $readonly
     * @return Default_Form_Accountclasstype|null
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Accountclasstype();

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation
            foreach ($data as $key => $val) {
                $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
            }

            $form->populate($data);
            $form->getElement('submit')->setLabel('Update');
        }

        if ($readonly) {
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }
}

