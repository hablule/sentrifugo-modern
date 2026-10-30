<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
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
 * Ethnic Code Controller
 * 
 * Manages the CRUD and On-the-Fly Popup logic for Ethnic Codes.
 * Refactored for SSOT v5.2: Strict typing, transactional safety, 
 * dynamic popup handling, and strict Controller/Form separation of concerns.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_EthniccodeController extends Zend_Controller_Action
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
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Ethniccode();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();

            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            // SSOT V.5: Explicitly assign context to view for UI-level ACL checks
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
                die(Zend_Json::encode(['login' => 'failed']));
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
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);

            // SSOT IX: Strict string casting to prevent PHP 8 rtrim() Fatal TypeError
            $searchData    = (string)$request->getParam('searchData', '');

            // SSOT V: Default sort is A-Z by the primary descriptive column
            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'ethnicname');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'ASC';
                $by   = 'ethnicname';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) { // SSOT IX: Catch \Throwable
            error_log("EthnicCode Index Error: " . $e->getMessage());
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'ethniccode';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, false);
        $this->render('save');
    }

    public function saveAction()
    {
        $form = new Default_Form_ethniccode();
        $request = $this->getRequest();
        $response = ['result' => 'error', 'message' => 'Invalid data'];

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V: Transaction Required

                try {
                    $data = [
                        'ethnicname'  => trim($request->getParam('ethnicname')),
                        'ethniccode'  => trim($request->getParam('ethniccode')),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive')
                    ];

                    $actionFlag = ($id > 0) ? 2 : 1; // 2=Edit, 1=Add
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    // SSOT I: Dynamic Menu ID logging
                    $menuId = defined('ETHNICCODE_MENU_ID') ? ETHNICCODE_MENU_ID : 85;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(['success' => "Ethnic Code $msg successfully."]);

                    // SSOT V: Standardized JSON Response
                    $response = ['result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success'];
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("EthnicCode Save Error: " . $e->getMessage());
                    $response = ['result' => 'exception', 'message' => 'System error occurred.', 'msgtype' => 'error'];
                }
            } else {
                // SSOT V: Validation Error Extraction
                $msgarray = [];
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $response = ['result' => 'error', 'message' => $msgarray];
            }
        }
        $this->_helper->json($response);
    }

    /**
     * Phase 4 Feature: On-The-Fly Popup Addition
     * Disables main layout, handles standard POST (no AJAX), and passes 
     * the generated HTML options to the Sentrifugo global JS closure function.
     */
    public function addpopupAction()
    {
        // 1. Force the layout to use the popup shell
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $form = new Default_Form_ethniccode();
        $form->setAction(BASE_URL . 'ethniccode/addpopup');

        // Remove standard AJAX submit handler; popup requires standard POST to trigger JS callback
        $form->getElement('submit')->setAttrib('onclick', null);

        $msgarray = [];
        $controllername = 'ethniccode';

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V

                try {
                    $data = [
                        'ethnicname'  => trim($this->getRequest()->getParam('ethnicname')),
                        'ethniccode'  => trim($this->getRequest()->getParam('ethniccode')),
                        'description' => trim($this->getRequest()->getParam('description')),
                        'isactive'    => (int)$this->getRequest()->getParam('isactive', 1)
                    ];

                    $newId = $this->_model->saveRecord($data);

                    $menuId = defined('ETHNICCODE_MENU_ID') ? ETHNICCODE_MENU_ID : 85;
                    sapp_Global::logManager($menuId, 1, $this->_loginUserId, $newId);

                    $db->commit();

                    // Generate new dropdown options string for the parent window
                    $ethniccodeData = $this->_model->getPairs(true);
                    $opt = '';
                    foreach ($ethniccodeData as $id => $name) {
                        $opt .= sapp_Global::selectOptionBuilder($id, $name);
                    }

                    // Expose data to View to be consumed by closeiframeAddPopup()
                    $this->view->ethniccodeData = $opt;
                    $this->view->eventact       = 'added';
                    $this->view->popup          = 'close';
                } catch (\Throwable $e) {
                    $db->rollBack();
                    $msgarray['error'] = 'System error occurred: ' . $e->getMessage();
                }
            } else {
                // Sentrifugo standard error extraction
                $messages = $form->getMessages();
                foreach ($messages as $key => $val) {
                    foreach ($val as $key2 => $val2) {
                        $msgarray[$key] = $val2;
                        break;
                    }
                }
            }
        }

        $this->view->controllername = $controllername;
        $this->view->msgarray       = $msgarray;
        $this->view->form           = $form;
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                // Dependency check occurs inside the Model per SSOT IV
                $this->_model->deleteRecord($id);

                $menuId = defined('ETHNICCODE_MENU_ID') ? ETHNICCODE_MENU_ID : 85;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);

                $this->_helper->json(['msgtype' => 'success', 'message' => 'Ethnic Code deleted successfully.']);
            } catch (\Throwable $e) {
                // Returns the dependency error cleanly to UI
                $this->_helper->json(['msgtype' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            $this->_helper->json(['msgtype' => 'error', 'message' => 'Invalid Request.']);
        }
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');

            $this->_model->toggleStatus($id, $status);

            $menuId = defined('ETHNICCODE_MENU_ID') ? ETHNICCODE_MENU_ID : 85;
            sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);

            $this->_helper->json(['result' => 'yes']);
        } catch (\Throwable $e) {
            $this->_helper->json(['result' => 'no']);
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX & INTERNAL HELPERS
     * ================================================================================= */

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_ethniccode();

        // SSOT VI: Notice there is NO manipulation of the 'onclick' attribute here.
        // That logic strictly lives inside the Form's init() method.

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
            }
        }

        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }
}
