<?php

/**
 * Fiscal Year Controller
 *
 * Manages the Fiscal Year entity and acts as the orchestrator for the child Period grid payload.
 * Complies with SSOT v6.8 (Master-Detail JSON Pattern, Server-Side Exception Handling).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_FiscalyearController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Fiscalyear();
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = (int)$user->id;
            $this->_loginuserRole  = (int)$user->emprole;
            $this->_loginuserGroup = (int)$user->group_id;

            // SSOT Mandatory View Assignments
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
                echo Zend_Json::encode(['login' => 'failed']);
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

            // SSOT v6.8: Default sort chronologically
            $sort    = (string) $request->getParam('sort', 'DESC');
            $by      = (string) $request->getParam('by', 'start_date');

            $perPage = ($dashboardcall === 'Yes')
                ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
                : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'DESC';
                $by = 'start_date';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            // Add custom action button linking to the Period Locks dashboard
            $dataTmp['extra']['action'] = [
                'name' => 'manage_locks',
                'value' => '<a href="' . BASE_URL . 'period/index/fiscalyear_id/{{id}}" class="fa fa-lock" title="Manage Period Locks for this Year" style="color: #f39c12; margin-left: 5px; cursor: pointer;"></a>'
            ];

            $this->view->dataArray = [$dataTmp];
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

            // Fetch Read-Only list of periods for display
            $periodModel = new Default_Model_Period();
            $this->view->existingPeriods = json_encode($periodModel->getPeriodsByFiscalYear($id));
        }

        $this->view->controllername = 'fiscalyear';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        // SSOT v6.8: Ensure CALENDERYEAR is defined in constants.php
        if (sapp_Global::_checkprivileges(CALENDERYEAR, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();

        // Pass empty array for JS grid initialization
        $this->view->existingPeriods = json_encode([]);

        $this->render('save');
    }

    public function editAction()
    {
        if (sapp_Global::_checkprivileges(CALENDERYEAR, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $id = (int) $this->getRequest()->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if (!$form) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->form = $form;
            $this->view->id   = $id;

            // Load existing periods into the JS Grid
            $periodModel = new Default_Model_Period();
            $this->view->existingPeriods = json_encode($periodModel->getPeriodsByFiscalYear($id));
        }

        $this->render('save');
    }

    public function saveAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_redirect('fiscalyear/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($id > 0) {
            $form->getElement('calendar_id')->setRequired(false);
        }
        

        if ($form->isValid($request->getPost())) {

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                // If editing, extract the calendar_id directly from the database record, 
                // NOT from the POST data (since it's disabled and unreliable/missing).
                if ($id > 0) {
                    $existingRecord = $this->_model->getRecordById($id);
                    $calendarIdToSave = $existingRecord['calendar_id'];
                } else {
                    $calendarIdToSave = (int) $request->getParam('calendar_id');
                }

                $data = [
                    'name'        => trim((string) $request->getParam('name', '')),
                    'calendar_id' => $calendarIdToSave,
                    'start_date'  => $request->getParam('start_date'),
                    'end_date'    => $request->getParam('end_date'),
                    'description' => trim((string) $request->getParam('description', '')),
                    'isactive'    => (int) $request->getParam('isactive', 1)
                ];

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

                // --- SSOT v6.8 THE MERGER: Surgical Array Sync for Periods ---
                $jsonPayload = $request->getParam('periods_payload');
                $periodsArray = json_decode($jsonPayload, true);

                if (!empty($periodsArray)) {
                    $periodModel = new Default_Model_Period();
                    // This method handles Server-Side overlap checks and surgical Add/Edit/Deletes
                    $periodModel->syncPeriods($savedId, $periodsArray);
                }
                // --- END SURGICAL SYNC ---

                // Logging
                sapp_Global::logManager(CALENDERYEAR, $actionFlag, $this->_loginUserId, $savedId);

                $db->commit();

                // FlashMessenger for standard redirect
                $this->_helper->flashMessenger->addMessage(['success' => "Fiscal Year and Periods {$saveMode} successfully."]);

                // SSOT Strict AJAX JSON Response (Success with Redirect)
                $this->_helper->json([
                    'result'    => 'saved',
                    'nomessage' => 'yes'
                ]);
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("FiscalYear Save Error: " . $e->getMessage());

                // Exception returned in specific structure. 
                // Passed directly so user sees the specific generation error (e.g. "Overlapping Dates").
                $this->_helper->json([
                    'result'  => 'exception',
                    'message' => $e->getMessage(),
                    'msgtype' => 'error'
                ]);
            }
        } else {
            // Validation Error - Raw Array output directly
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
            if (sapp_Global::_checkprivileges(CALENDERYEAR, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(['result' => 'error', 'message' => 'Permission Denied.']);
            }

            try {
                // Dependency check
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("This Fiscal Year has active Periods linked to it and cannot be deleted.");
                }

                $this->_model->deleteRecord($id);
                sapp_Global::logManager(CALENDERYEAR, 3, $this->_loginUserId, $id);

                return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success']);
            } catch (\Throwable $e) {
                error_log("FiscalYear Delete Error: " . $e->getMessage());
                return $this->_helper->json(['result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error']);
            }
        }

        return $this->_helper->json(['result' => 'error', 'message' => 'Invalid request']);
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id     = (int) $this->getRequest()->getParam('id', 0);
            $status = (int) $this->getRequest()->getParam('status', 0);

            $this->_model->toggleStatus($id, $status);
            sapp_Global::logManager(CALENDERYEAR, 4, $this->_loginUserId, $id);

            $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.']);
        } catch (\Throwable $e) {
            error_log("FiscalYear Toggle Error: " . $e->getMessage());
            $this->_helper->json(['result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.']);
        }
    }

    /* =================================================================================
     * GROUP 5: INTERNAL HELPERS
     * ================================================================================= */

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Fiscalyear();

        // Populate Dropdowns natively
        $calendarModel = new Default_Model_Calendar();
        $form->calendar_id->addMultiOption('', 'Select Parent Calendar');
        $form->calendar_id->addMultiOptions($calendarModel->getPairs());

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }

            // Convert DB dates to JS format for UI
            if (!empty($data['start_date'])) {
                $data['start_date'] = sapp_Global::change_date($data['start_date'], 'view');
            }
            if (!empty($data['end_date'])) {
                $data['end_date'] = sapp_Global::change_date($data['end_date'], 'view');
            }           

            $form->populate($data);
            $form->getElement('submit')->setLabel('Update Fiscal Year');
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
