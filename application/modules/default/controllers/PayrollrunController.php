<?php

/**
 * Payroll Run Controller
 *
 * Master Control Dashboard for viewing, authorizing, and cancelling payroll runs.
 * Enforces strict transactional uniqueness and state machine boundaries.
 * Complies with SSOT v6.7.7.
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_PayrollrunController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Payrollrun();
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
                echo Zend_Json::encode(['login' => 'failed']);
                exit;
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
            ->addActionContext('delete', 'json')
            ->addActionContext('cancelrun', 'json')
            ->addActionContext('sync', 'json')
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

            // Default Sort: Pay Group ASC, then Cycle Start Date ASC
            $sort    = (string) $request->getParam('sort', 'ASC');
            $by      = (string) $request->getParam('by', 'paygroup_name');

            $perPage = ($dashboardcall === 'Yes')
                ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
                : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'ASC';
                $by = 'paygroup_name';
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

        $this->view->controllername = 'payrollrun';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE (Ad-Hoc Runs Only)
     * ================================================================================= */

    public function addAction()
    {
        $this->_helper->flashMessenger->addMessage(array('info' => 'Payroll runs are generated automatically. Use "Sync / Fill Calendar Gaps".'));
        return $this->_redirect('payrollrun/index');
    }

    public function editAction()
    {
        if (defined('PAYROLLRUN_MENU_ID') && sapp_Global::_checkprivileges(PAYROLLRUN_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
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
            return $this->_redirect('payrollrun/index');
        }

        $id = (int) $request->getParam('id', 0);
        if (!$id) {
            return $this->_helper->json(array('result' => 'exception', 'message' => 'Manual creation of Payroll Runs is prohibited. Use the Calendar Sync Engine.', 'msgtype' => 'error'));
        }

        $existing = $this->_model->getRecordById($id);
        if (!$existing) {
            return $this->_helper->json(array('result' => 'exception', 'message' => 'Payroll Run not found.', 'msgtype' => 'error'));
        }

        // Only 'Scheduled' runs allow editing Pay Date, Description, and Active status
        if ($existing['run_status'] !== 'Scheduled') {
            return $this->_helper->json(array('result' => 'exception', 'message' => "Run is in '{$existing['run_status']}' status and is strictly read-only.", 'msgtype' => 'error'));
        }

        $form = $this->_prepareForm($id);

        // SSOT V.12: Bypass validation on disabled structural fields
        foreach (array('pay_group_id', 'cycle_sequence', 'cycle_start_date', 'cycle_end_date', 'run_status') as $fieldName) {
            if ($el = $form->getElement($fieldName)) {
                $el->setRequired(false)->clearValidators();
            }
        }

        if ($form->isValid($request->getPost())) {

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                $payDate = sapp_Global::change_date($request->getParam('pay_date'), 'database');

                // Validate against authoritative DB cycle_end_date (never trust disabled POST)
                if ($existing['cycle_end_date'] > $payDate) {
                    throw new Exception("Pay Date (Execution) cannot be before Cycle End Date (" . sapp_Global::change_date($existing['cycle_end_date'], 'view') . ").");
                }

                // Strictly update ONLY pay_date, description, and isactive
                $data = array(
                    'pay_date'     => $payDate,
                    'description'  => trim((string) $request->getParam('description', '')),
                    'isactive'     => (int) $request->getParam('isactive', 1),
                    'modifiedby'   => $this->_loginUserId,
                    'modifieddate' => gmdate("Y-m-d H:i:s")
                );

                $this->_model->update($data, array('id = ?' => $id));

                if (defined('PAYROLLRUN_MENU_ID')) {
                    sapp_Global::logManager(PAYROLLRUN_MENU_ID, 2, $this->_loginUserId, $id);
                }

                $db->commit();
                $this->_helper->flashMessenger->addMessage(array('success' => 'Payroll Run updated successfully.'));
                $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("Payroll Run Save Error: " . $e->getMessage());
                $this->_helper->json(array('result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error'));
            }
        } else {
            // SSOT V.6: Return raw nested Zend Form messages
            $this->_helper->json($form->getMessages());
        }
    }

    /* =================================================================================
     * GROUP 4: STATE MACHINE TRANSITIONS & REMOVAL
     * ================================================================================= */

    /**
     * AJAX Action: Cancels an initiated/review payroll run, preserving the audit trail.
     * Enforces the Cancellation Lock (Cannot cancel Locked/Closed runs).
     */
    public function cancelrunAction()
    {
        $id = (int) $this->getRequest()->getParam('objid', 0);

        if ($id) {
            try {
                if (!$this->_model->canBeCancelled($id)) {
                    throw new Exception("This Payroll Run has already been Locked or Closed. It cannot be cancelled. Corrections must be handled via Arrears.");
                }

                $data = [
                    'run_status'   => 'Cancelled',
                    'modifiedby'   => $this->_loginUserId,
                    'modifieddate' => gmdate("Y-m-d H:i:s")
                ];

                $this->_model->update($data, ['id = ?' => $id]);

                if (defined('PAYROLLRUN_MENU_ID')) {
                    sapp_Global::logManager(PAYROLLRUN_MENU_ID, 2, $this->_loginUserId, $id); // 2 = Edit/Update
                }

                return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Payroll Run successfully cancelled.', 'msgtype' => 'success']);
            } catch (\Throwable $e) {
                return $this->_helper->json(['result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error']);
            }
        }
        return $this->_helper->json(['result' => 'error', 'message' => 'Invalid request']);
    }

    /**
     * AJAX Action: Soft-Deletes un-initiated 'Scheduled' runs (e.g. Obsolete Calendars).
     * Enforces the Soft-Delete Lock.
     */
    public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('objid', 0);

        if ($id) {
            try {
                if (!$this->_model->canBeSoftDeleted($id)) {
                    throw new Exception("Only un-initiated 'Scheduled' runs can be deleted. Once a run begins processing, it must be Cancelled instead to preserve the audit trail.");
                }

                $data = [
                    'isactive'     => 5,
                    'modifiedby'   => $this->_loginUserId,
                    'modifieddate' => gmdate("Y-m-d H:i:s")
                ];

                $this->_model->update($data, ['id = ?' => $id]);

                if (defined('PAYROLLRUN_MENU_ID')) {
                    sapp_Global::logManager(PAYROLLRUN_MENU_ID, 3, $this->_loginUserId, $id);
                }

                return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Scheduled run deleted successfully.', 'msgtype' => 'success']);
            } catch (\Throwable $e) {
                return $this->_helper->json(['result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error']);
            }
        }
        return $this->_helper->json(['result' => 'error', 'message' => 'Invalid request']);
    }

    /* =================================================================================
     * GROUP 5: SYSTEM GENERATORS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Placeholder for the Batch Generator (methodX).
     * This will be fleshed out with the actual calendar math engine in a future sprint.
     */
    public function generateRuns()
    {
        // 1. Accepts Pay Group ID and Fiscal Year.
        // 2. Looks up associated Pay Frequency dictionary.
        // 3. Executes Rolling Math or Anchor plots.
        // 4. Wraps in $db->beginTransaction().
        // 5. Deletes existing 'Scheduled' runs for that year/group (isactive=5).
        // 6. Inserts the 12-52 new rows safely.
        // 7. Commits.
    }

    /**
     * AJAX Action: Triggers the Generator Engine manually and returns the Metric Summary.
     */
    public function syncAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_helper->json(array('status' => 'error', 'message' => 'Invalid request.'));
        }

        try {
            $payGroupId = (int) $request->getParam('pay_group_id', 0);
            $startDate  = sapp_Global::change_date($request->getParam('start_date'), 'database');
            $endDate    = sapp_Global::change_date($request->getParam('end_date'), 'database');
            $isDryRun   = (bool) $request->getParam('is_dry_run', false);

            if (empty($payGroupId) || empty($startDate) || empty($endDate)) {
                throw new Exception("Please select a Pay Group and define valid start and end dates.");
            }

            require_once APPLICATION_PATH . '/../library/Sentrifugo/Service/PayrollRunEngine.php';
            $engine = new Sentrifugo_Service_PayrollRunEngine();
            $metrics = $engine->generateManual($payGroupId, $startDate, $endDate, $this->_loginUserId, $isDryRun);

            $this->_helper->json(array('status' => 'success', 'metrics' => $metrics));
        } catch (\Throwable $e) {
            $this->_helper->json(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        if (!$id) {
            return null;
        }

        $form = new Default_Form_Payrollrun();

        $pgModel = new Default_Model_Paygroup();
        $form->pay_group_id->addMultiOption('', 'Select Target Pay Group...');
        $form->pay_group_id->addMultiOptions($pgModel->getPairs(true));

        $data = $this->_model->getRecordById($id);
        if (!$data) return null;

        foreach ($data as $key => $val) {
            if ($val !== null) {
                $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
            }
        }

        if (!empty($data['cycle_start_date'])) $data['cycle_start_date'] = sapp_Global::change_date($data['cycle_start_date'], 'view');
        if (!empty($data['cycle_end_date']))   $data['cycle_end_date']   = sapp_Global::change_date($data['cycle_end_date'], 'view');
        if (!empty($data['pay_date']))         $data['pay_date']         = sapp_Global::change_date($data['pay_date'], 'view');

        $form->populate($data);
        $form->getElement('submit')->setLabel('Update Run Details');

        $isScheduled = ($data['run_status'] === 'Scheduled');
        $this->view->runStatus = $data['run_status'];
        $this->view->isEditableRun = (!$readonly && $isScheduled);

        if ($readonly || !$isScheduled) {
            // Non-Scheduled runs (Initiated, Review, Locked, Closed, Cancelled) are 100% read-only
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        } else {
            // Scheduled run edit: lock structural fields; allow pay_date, description, isactive
            foreach (array('pay_group_id', 'cycle_sequence', 'cycle_start_date', 'cycle_end_date', 'run_status') as $locked) {
                if ($el = $form->getElement($locked)) {
                    $el->setAttrib('disabled', 'disabled');
                }
            }
        }

        return $form;
    }
}
