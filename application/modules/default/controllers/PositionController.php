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
 * Position Controller (The Structural Seat)
 *
 * Manages discrete headcount seats, enforces the Single Org Owner rule, and applies the Job Title Freeze logic.
 * Complies with SSOT v6.7.7 (PHP 7.4 strict, Raw Array JSON, Dependency Checks).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_PositionController extends Zend_Controller_Action
{
    /* --------------------------------------------------------------
     * Attributes (Visibility -> Alphabetical)
     * -------------------------------------------------------------- */

    protected $_loginUserId;
    protected $_loginuserGroup;
    protected $_loginuserRole;
    protected $_model;
    protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    public function init()
    {
        $this->_model   = new Default_Model_Position();
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = (int)$user->id;
            $this->_loginuserRole  = (int)$user->emprole;
            $this->_loginuserGroup = (int)$user->group_id;

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

            $sort    = (string) $request->getParam('sort', 'ASC');
            $by      = (string) $request->getParam('by', 'code');

            $perPage = ($dashboardcall === 'Yes')
                ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
                : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'ASC';
                $by = 'code';
                $page = 1;
                $searchData = '';
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

        $this->view->controllername = 'position';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        if (defined('POSITION_MENU_ID') && sapp_Global::_checkprivileges(POSITION_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        if (defined('POSITION_MENU_ID') && sapp_Global::_checkprivileges(POSITION_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
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
            return $this->_redirect('position/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                // ENFORCE THE SINGLE ORG OWNER RULE AT APPLICATION LEVEL:
                $orgType = trim((string) $request->getParam('org_type', ''));
                $orgFields = array('company_id', 'businessunit_id', 'division_id', 'department_id', 'section_id', 'team_id');

                $rawName = trim((string) $request->getParam('name', ''));
                $reportsTo = $request->getParam('reports_to_position_id');

                $data = array(
                    'code'            => trim((string) $request->getParam('code', '')),
                    'name'            => ($rawName === '') ? null : $rawName,
                    'jobtitle_id'     => (int) $request->getParam('jobtitle_id'),
                    'reports_to_position_id' => !empty($reportsTo) ? (int)$reportsTo : null,
                    'status'          => trim((string) $request->getParam('status', 'Proposed')),
                    'max_fte'         => (float) $request->getParam('max_fte', 1.00),
                    'company_id'      => null,
                    'businessunit_id' => null,
                    'division_id'     => null,
                    'department_id'   => null,
                    'section_id'      => null,
                    'team_id'         => null,
                    'description'     => trim((string) $request->getParam('description', '')),
                    'isactive'        => (int) $request->getParam('isactive', 1)
                );

                // Assign exclusively the selected target level to satisfy the DB CHECK constraint
                $targetMap = array(
                    'Company'      => 'company_id',
                    'BusinessUnit' => 'businessunit_id',
                    'Division'     => 'division_id',
                    'Department'   => 'department_id',
                    'Section'      => 'section_id',
                    'Team'         => 'team_id'
                );

                if (!isset($targetMap[$orgType])) {
                    throw new Exception("Please select a valid organizational owner level.");
                }

                $targetField = $targetMap[$orgType];
                $targetValue = (int) $request->getParam($targetField);

                if (empty($targetValue)) {
                    throw new Exception("Please select the specific {$orgType} that owns this position.");
                }

                $data[$targetField] = $targetValue;

                $actionFlag = 2;
                $saveMode   = 'updated';

                if (!$id) {
                    $actionFlag = 1;
                    $saveMode   = 'added';
                    $savedId    = $this->_model->saveRecord($data);
                } else {
                    $this->_model->saveRecord($data, $id);
                    $savedId    = $id;
                }

                if (defined('POSITION_MENU_ID')) {
                    sapp_Global::logManager(POSITION_MENU_ID, $actionFlag, $this->_loginUserId, $savedId);
                }

                $db->commit();

                $this->_helper->flashMessenger->addMessage(array('success' => "Position {$saveMode} successfully."));
                $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("Position Save Error: " . $e->getMessage());

                $this->_helper->json(array(
                    'result'  => 'exception',
                    'message' => $e->getMessage(),
                    'msgtype' => 'error'
                ));
            }
        } else {
            $msgarray = array();
            foreach ($form->getMessages() as $field => $errors) {
                $msgarray[$field] = current($errors);
            }
            $this->_helper->json($msgarray);
        }
    }

    /* =================================================================================
     * GROUP 4: REMOVAL
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('objid', 0);

        if ($id) {
            if (defined('POSITION_MENU_ID') && sapp_Global::_checkprivileges(POSITION_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(array('result' => 'error', 'message' => 'Permission Denied.'));
            }

            try {
                // Dependency Check: Cannot delete if position has historical occupants
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("This Position has historical or active employee assignments and cannot be deleted. Change status to 'Eliminated' instead.");
                }

                $this->_model->deleteRecord($id);
                if (defined('POSITION_MENU_ID')) {
                    sapp_Global::logManager(POSITION_MENU_ID, 3, $this->_loginUserId, $id);
                }

                return $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success'));
            } catch (\Throwable $e) {
                error_log("Position Delete Error: " . $e->getMessage());
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
            if (defined('POSITION_MENU_ID')) {
                sapp_Global::logManager(POSITION_MENU_ID, 4, $this->_loginUserId, $id);
            }

            $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.'));
        } catch (\Throwable $e) {
            error_log("Position Toggle Error: " . $e->getMessage());
            $this->_helper->json(array('result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.'));
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $prev_cntrl = (string) $this->getRequest()->getParam('prev_cntrl', '');
        $form = $this->_prepareForm();

        $form->setAction(BASE_URL . 'position/savepopup/prev_cntrl/' . $prev_cntrl);
        $form->setAttrib('id', 'popupformid');
        $form->getElement('submit')->setAttrib('onclick', null);

        $this->view->form = $form;
        $this->view->prev_cntrl = $prev_cntrl;
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Position();

        // 1. Populate Job Titles (The Blueprint)
        $jobTitleModel = new Default_Model_Jobtitle();
        $form->jobtitle_id->addMultiOption('', 'Select Job Title Blueprint...');
        $form->jobtitle_id->addMultiOptions($jobTitleModel->getPairs(true));

        // 2. Populate Reports To (Parent Position)
        $positions = $this->_model->getPairs(true);
        if ($id) {
            unset($positions[$id]); // Prevent self-reporting
        }
        $form->reports_to_position_id->addMultiOption('', 'Top Level / No Manager');
        $form->reports_to_position_id->addMultiOptions($positions);

        // 3. Populate Organizational Units
        if (class_exists('Default_Model_Company')) {
            $compModel = new Default_Model_Company();
            $form->company_id->addMultiOption('', 'Select Company...');
            $form->company_id->addMultiOptions($compModel->getPairs(true));
        }

        if (class_exists('Default_Model_Businessunit')) {
            $buModel = new Default_Model_Businessunit();
            $form->businessunit_id->addMultiOption('', 'Select Business Unit...');
            $form->businessunit_id->addMultiOptions($buModel->getPairs(true));
        }

        if (class_exists('Default_Model_Division')) {
            $divModel = new Default_Model_Division();
            $form->division_id->addMultiOption('', 'Select Division...');
            $form->division_id->addMultiOptions($divModel->getPairs(true));
        }

        if (class_exists('Default_Model_Department')) {
            $deptModel = new Default_Model_Department();
            $form->department_id->addMultiOption('', 'Select Department...');
            $form->department_id->addMultiOptions($deptModel->getPairs(true));
        }

        if (class_exists('Default_Model_Section')) {
            $secModel = new Default_Model_Section();
            $form->section_id->addMultiOption('', 'Select Section...');
            $form->section_id->addMultiOptions($secModel->getPairs(true));
        }

        if (class_exists('Default_Model_Team')) {
            $teamModel = new Default_Model_Team();
            $form->team_id->addMultiOption('', 'Select Team...');
            $form->team_id->addMultiOptions($teamModel->getPairs(true));
        }

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }

            // Determine which org_type was populated to set the UI switcher
            if (!empty($data['company_id'])) $data['org_type'] = 'Company';
            elseif (!empty($data['businessunit_id'])) $data['org_type'] = 'BusinessUnit';
            elseif (!empty($data['division_id'])) $data['org_type'] = 'Division';
            elseif (!empty($data['department_id'])) $data['org_type'] = 'Department';
            elseif (!empty($data['section_id'])) $data['org_type'] = 'Section';
            elseif (!empty($data['team_id'])) $data['org_type'] = 'Team';

            $form->populate($data);

            // ENFORCE THE JOB TITLE FREEZE RULE:
            // If seat was ever occupied, freeze the Job Title dropdown
            if ($this->_model->isUtilized($id)) {
                $form->jobtitle_id->setAttrib('disabled', 'disabled');
                $form->jobtitle_id->setAttrib('title', 'Job Title is frozen because this position has historical occupants.');
            }

            $form->getElement('submit')->setLabel('Update Position');
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
