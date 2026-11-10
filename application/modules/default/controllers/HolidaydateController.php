<?php

/**
 * Holiday Date Controller (Holiday Calendar / Occurrences)
 *
 * Manages the Business Truth of holiday calendar occurrences and provides the batch generation interface.
 * Complies with SSOT v6.7.7 (PHP 7.4 strict, Raw Array JSON, Dependency Checks, Service Layer Integration).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_HolidaydateController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Holidaydate();
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
            ->addActionContext('generate', 'json')
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

            // SSOT v6.7.7: Default sort chronologically
            $sort    = (string) $request->getParam('sort', 'ASC');
            $by      = (string) $request->getParam('by', 'start_date');

            $perPage = ($dashboardcall === 'Yes')
                ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
                : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'ASC';
                $by = 'start_date';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

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
            $this->view->form  = $form;
            $this->view->id    = $id;
            // Fetch discrete lines to show the System Truth in read-only mode
            $this->view->lines = $this->_model->getLinesByHeaderId($id);
        }

        $this->view->controllername = 'holidaydate';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        if (defined('HOLIDAY_DATE') && sapp_Global::_checkprivileges(HOLIDAY_DATE, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        if (defined('HOLIDAY_DATE') && sapp_Global::_checkprivileges(HOLIDAY_DATE, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
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
            return $this->_redirect('holidaydate/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {

            $holidayId = (int) $request->getParam('holiday_id_hidden', $request->getParam('holiday_id', 0));
            $startDate = sapp_Global::change_date($request->getParam('start_date'), 'database');
            $endDate   = sapp_Global::change_date($request->getParam('end_date'), 'database');
            $forceSave = (int) $request->getParam('force_save', 0);

            // Phase 7 Overlap Validation Check...
            if (!$forceSave) {
                require_once APPLICATION_PATH . '/../library/Sentrifugo/Service/HolidayEngine.php';
                $engine = new Sentrifugo_Service_HolidayEngine();
                $conflicts = $engine->checkOverlap($startDate, $endDate, $holidayId, $id > 0 ? $id : null);

                if (!empty($conflicts)) {
                    return $this->_helper->json([
                        'result'    => 'conflict_warning',
                        'conflicts' => $conflicts,
                        'message'   => 'Date overlap detected with existing holiday policies.'
                    ]);
                }
            }

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                // Fetch previous state for ledger diffing if updating
                $oldRecord = ($id > 0) ? $this->_model->getRecordById($id) : null;

                $data = [
                    // [Standard Header Data Array...]
                ];

                $actionFlag = 2;
                $saveMode   = 'updated';

                if (!$id) {
                    $actionFlag = 1;
                    $saveMode   = 'added';
                    $savedId    = $this->_model->saveRecord($data);
                } else {
                    $this->_model->saveRecord($data, $id);
                    $savedId = $id;
                }

                // --- CORRECTION 2: ATOMIC LINE SYNCING ---
                $lineModel = new Default_Model_Holidaydateline();
                $existingLines = $id > 0 ? $lineModel->fetchAll(['holiday_date_id = ?' => $id, 'isactive != ?' => 5])->toArray() : [];
                $existingDatesMap = [];
                foreach ($existingLines as $line) {
                    $existingDatesMap[$line['effective_date']] = $line['id'];
                }

                $dtStart = new DateTime($startDate);
                $dtEnd   = new DateTime($endDate);
                $curr    = clone $dtStart;
                $seq     = 1;
                $currentDate = gmdate("Y-m-d H:i:s");
                $newDatesMap = [];

                // Calculate required new dates
                while ($curr <= $dtEnd) {
                    $dateStr = $curr->format('Y-m-d');
                    $newDatesMap[$dateStr] = $seq;
                    $curr->modify('+1 day');
                    $seq++;
                }

                $ledgerModel = new Default_Model_Financialpolicyledger();

                // 1. Delete lines no longer in the block
                $datesToRemove = array_diff(array_keys($existingDatesMap), array_keys($newDatesMap));
                foreach ($datesToRemove as $dateToRemove) {
                    $lineId = $existingDatesMap[$dateToRemove];
                    $lineModel->update(['isactive' => 5, 'modifieddate' => $currentDate], ['id = ?' => $lineId]);

                    // Audit Deletion
                    $ledgerModel->logChange('HOLIDAY_DATE_LINE', $lineId, 'DELETED', $dateToRemove, null, null, null, $this->_loginUserId);
                }

                // 2. Insert new lines or leave existing ones intact
                foreach ($newDatesMap as $dateStr => $sequenceNo) {
                    if (!isset($existingDatesMap[$dateStr])) {
                        // Insert New
                        $newLineId = $lineModel->saveRecord([
                            'holiday_date_id' => $savedId,
                            'sequence_no'     => $sequenceNo,
                            'effective_date'  => $dateStr,
                            'isactive'        => 1,
                            'createddate'     => $currentDate
                        ]);

                        // Audit Creation
                        $ledgerModel->logChange('HOLIDAY_DATE_LINE', $newLineId, 'CREATED', $dateStr, null, null, null, $this->_loginUserId);
                    } else {
                        // Optional: Update sequence_no if it shifted, but date ID remains preserved
                        $lineId = $existingDatesMap[$dateStr];
                        $lineModel->update(['sequence_no' => $sequenceNo], ['id = ?' => $lineId]);
                    }
                }

                // --- CORRECTION 3: MULTI-DAY LEDGER AUDITING FOR POLICY CHANGES ---
                if ($id > 0) {
                    $trackedFields = [
                        'payroll_multiplier',
                        'work_policy',
                        'is_paid',
                        'is_mandatory',
                        'start_time',
                        'end_time'
                    ];

                    // We must log the policy change for EVERY valid line in the block, 
                    // so Payroll knows exactly which physical calendar days require arrears.
                    foreach ($newDatesMap as $dateStr => $seq) {
                        $ledgerModel->logBatchChanges(
                            'HOLIDAY_DATE',
                            $savedId,
                            $dateStr, // The specific calendar day affected
                            $oldRecord,
                            $data,
                            $trackedFields,
                            $this->_loginUserId
                        );
                    }
                }

                // Standard Sentrifugo Logging
                if (defined('HOLIDAY_DATE')) {
                    sapp_Global::logManager(HOLIDAY_DATE, $actionFlag, $this->_loginUserId, $savedId);
                }

                $db->commit();

                $this->_helper->flashMessenger->addMessage(['success' => "Holiday occurrence {$saveMode} successfully."]);
                $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes']);
                
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("Holiday Date Save Error: " . $e->getMessage());
                $this->_helper->json([
                    'result'  => 'exception',
                    'message' => $e->getMessage(),
                    'msgtype' => 'error'
                ]);
            }
        } else {
            $msgarray = [];
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
            if (defined('HOLIDAY_DATE') && sapp_Global::_checkprivileges(HOLIDAY_DATE, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(['result' => 'error', 'message' => 'Permission Denied.']);
            }

            try {
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("This Holiday Date falls within a closed/locked Payroll period and cannot be deleted.");
                }

                $record = $this->_model->getRecordById($id);

                $this->_model->deleteRecord($id);

                // --- PHASE 8: LOG DELETION TO FINANCIAL LEDGER ---
                if ($record) {
                    $ledgerModel = new Default_Model_Financialpolicyledger();
                    $ledgerModel->logChange(
                        'HOLIDAY_DATE',
                        $id,
                        'DELETED',
                        $record['start_date'],
                        null,
                        "Deleted Holiday occurrence: {$record['start_date']} to {$record['end_date']}",
                        null,
                        $this->_loginUserId
                    );
                }

                if (defined('HOLIDAY_DATE')) {
                    sapp_Global::logManager(HOLIDAY_DATE, 3, $this->_loginUserId, $id);
                }

                return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success']);
            } catch (\Throwable $e) {
                error_log("Holiday Date Delete Error: " . $e->getMessage());
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
            if (defined('HOLIDAY_DATE')) {
                sapp_Global::logManager(HOLIDAY_DATE, 4, $this->_loginUserId, $id);
            }

            $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.']);
        } catch (\Throwable $e) {
            error_log("Holiday Date Toggle Error: " . $e->getMessage());
            $this->_helper->json(['result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.']);
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & BATCH ENGINE
     * ================================================================================= */

    /**
     * AJAX Action: Triggers the Service Layer Look-Ahead Batch Generator.
     */
    public function generateAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_helper->json(['result' => 'error', 'message' => 'Invalid request method.']);
        }

        try {
            $targetYear = (int) $request->getParam('target_year', date('Y'));

            require_once APPLICATION_PATH . '/../library/Sentrifugo/Service/HolidayEngine.php';
            $engine = new Sentrifugo_Service_HolidayEngine();

            $summary = $engine->generateAllHolidaysForYear($targetYear, $this->_loginUserId);

            $msg = "Generation Complete for {$targetYear}. Generated: {$summary['generated']}, Skipped: {$summary['skipped']}.";
            if (!empty($summary['errors'])) {
                $msg .= " Errors: " . implode(', ', $summary['errors']);
            }

            $this->_helper->json([
                'result'  => 'saved',
                'nomessage' => 'no',
                'message' => $msg,
                'msgtype' => empty($summary['errors']) ? 'success' : 'warning'
            ]);
        } catch (\Throwable $e) {
            error_log("Holiday Batch Generation Error: " . $e->getMessage());
            $this->_helper->json([
                'result'  => 'exception',
                'message' => 'Generation failed: ' . $e->getMessage(),
                'msgtype' => 'error'
            ]);
        }
    }

    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $prev_cntrl = (string) $this->getRequest()->getParam('prev_cntrl', '');
        $form = $this->_prepareForm();

        $form->setAction(BASE_URL . 'holidaydate/savepopup/prev_cntrl/' . $prev_cntrl);
        $form->setAttrib('id', 'popupformid');
        $form->getElement('submit')->setAttrib('onclick', null);

        $this->view->form = $form;
        $this->view->prev_cntrl = $prev_cntrl;
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Holidaydate();

        // Populate Master Holiday Dropdown natively per SSOT VI.12
        $holidayMasterModel = new Default_Model_Holiday();
        $form->holiday_id->addMultiOption('', 'Select Parent Policy...');
        $form->holiday_id->addMultiOptions($holidayMasterModel->getPairs(true));

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation per SSOT XI.3
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

            // Sync hidden override for disabled primary parent holiday_id
            if ($form->getElement('holiday_id_hidden')) {
                $form->getElement('holiday_id_hidden')->setValue($data['holiday_id']);
            }

            $form->populate($data);
            $form->getElement('submit')->setLabel('Update Occurrence');
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
