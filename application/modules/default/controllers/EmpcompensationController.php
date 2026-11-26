<?php

/**
 * Employee Compensation Controller
 *
 * Manages effective-dated compensation profiles, enforcing non-overlapping intervals
 * and payroll-run immutability locks.
 * Complies with SSOT v6.7.7 (PHP 7.4 strict, Raw Array JSON, Dependency Checks).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @version    Sentrifugo v3.2
 */
class Default_EmpcompensationController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Empcompensation();
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
            $by      = (string) $request->getParam('by', 'employee_name');

            $perPage = ($dashboardcall === 'Yes')
                ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
                : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'ASC';
                $by = 'employee_name';
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
        $id   = (int) $this->getRequest()->getParam('id', 0);
        $form = $this->_prepareForm($id, true);

        if (!$form) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->form = $form;
            $this->view->id   = $id;
        }

        $this->view->controllername = 'empcompensation';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        if (defined('EMP_COMPENSATION_MENU_ID') && sapp_Global::_checkprivileges(EMP_COMPENSATION_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $prefillEmpId = (int) $this->getRequest()->getParam('employee_id', 0);
        $this->view->form = $this->_prepareForm(null, false, $prefillEmpId);
        $this->render('save');
    }

    public function editAction()
    {
        if (defined('EMP_COMPENSATION_MENU_ID') && sapp_Global::_checkprivileges(EMP_COMPENSATION_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $id   = (int) $this->getRequest()->getParam('id', 0);
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
            return $this->_redirect('empcompensation/index');
        }

        $id      = (int) $request->getParam('id', 0);
        $isInUse = ($id > 0 && $this->_model->checkInUse($id));
        $form    = $this->_prepareForm($id);

        if (!$form) {
            return $this->_helper->json(array('result' => 'exception', 'message' => 'Record not found.', 'msgtype' => 'error'));
        }

        // SSOT V.12: Bypass validation on disabled employee_id during Edit
        if ($id > 0 && $form->getElement('employee_id')) {
            $form->getElement('employee_id')->setRequired(false)->clearValidators();
        }

        // If Payroll Runs already processed against this compensation, lock financial fields; only allow setting end_date/description/isactive
        if ($isInUse) {
            foreach (array('pay_group_id', 'remunerationbasis_id', 'currency_id', 'base_amount', 'start_date') as $locked) {
                if ($el = $form->getElement($locked)) {
                    $el->setRequired(false)->clearValidators();
                }
            }
        }

        if ($form->isValid($request->getPost())) {

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                if ($id > 0) {
                    $existing   = $this->_model->getRecordById($id);
                    $employeeId = (int) $existing['employee_id'];
                } else {
                    $employeeId = (int) $request->getParam('employee_id', 0);
                }

                $startDate = $isInUse
                    ? $existing['start_date']
                    : sapp_Global::change_date($request->getParam('start_date'), 'database');

                $endDateRaw = $request->getParam('end_date');
                $endDate    = !empty($endDateRaw) ? sapp_Global::change_date($endDateRaw, 'database') : null;

                // 1. Date Order Check
                if (!empty($endDate) && ($startDate > $endDate)) {
                    throw new Exception("Effective Start Date cannot be after the Effective End Date.");
                }

                // 2. Non-Overlapping Temporal Check
                $isActiveVal = (int) $request->getParam('isactive', 1);
                if ($isActiveVal === 1) {
                    $overlap = $this->_model->checkDateOverlap($employeeId, $startDate, $endDate, $id > 0 ? $id : null);
                    if ($overlap) {
                        throw new Exception($overlap['message']);
                    }
                }

                if ($isInUse) {
                    // Protected mode: allow closing out end_date, updating notes, or toggling active
                    $data = array(
                        'end_date'    => $endDate,
                        'description' => trim((string) $request->getParam('description', '')),
                        'isactive'    => $isActiveVal
                    );
                } else {
                    $data = array(
                        'employee_id'          => $employeeId,
                        'pay_group_id'         => (int) $request->getParam('pay_group_id', 0),
                        'remunerationbasis_id' => (int) $request->getParam('remunerationbasis_id', 0),
                        'currency_id'          => (int) $request->getParam('currency_id', 0),
                        'base_amount'          => (float) $request->getParam('base_amount', 0),
                        'start_date'           => $startDate,
                        'end_date'             => $endDate,
                        'description'          => trim((string) $request->getParam('description', '')),
                        'isactive'             => $isActiveVal
                    );
                }

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

                if (defined('EMP_COMPENSATION_MENU_ID')) {
                    sapp_Global::logManager(EMP_COMPENSATION_MENU_ID, $actionFlag, $this->_loginUserId, $savedId);
                }

                $db->commit();

                $this->_helper->flashMessenger->addMessage(array('success' => "Employee Compensation {$saveMode} successfully."));
                $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("Employee Compensation Save Error: " . $e->getMessage());
                $this->_helper->json(array('result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error'));
            }
        } else {
            // SSOT V.6: Return raw nested Zend Form messages
            $this->_helper->json($form->getMessages());
        }
    }

    /* =================================================================================
     * GROUP 4: REMOVAL
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('objid', 0);

        if ($id) {
            if (defined('EMP_COMPENSATION_MENU_ID') && sapp_Global::_checkprivileges(EMP_COMPENSATION_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(array('result' => 'error', 'message' => 'Permission Denied.'));
            }

            try {
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("This Compensation record overlaps with processed Payroll Runs and cannot be deleted. Set an End Date to close it instead.");
                }

                $this->_model->deleteRecord($id);
                if (defined('EMP_COMPENSATION_MENU_ID')) {
                    sapp_Global::logManager(EMP_COMPENSATION_MENU_ID, 3, $this->_loginUserId, $id);
                }

                return $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success'));
            } catch (\Throwable $e) {
                error_log("Employee Compensation Delete Error: " . $e->getMessage());
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
            if (defined('EMP_COMPENSATION_MENU_ID')) {
                sapp_Global::logManager(EMP_COMPENSATION_MENU_ID, 4, $this->_loginUserId, $id);
            }

            $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.'));
        } catch (\Throwable $e) {
            error_log("Employee Compensation Toggle Error: " . $e->getMessage());
            $this->_helper->json(array('result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.'));
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $empId = (int) $this->getRequest()->getParam('employee_id', 0);
        $form  = $this->_prepareForm(null, false, $empId);

        $form->setAction(BASE_URL . 'empcompensation/addpopup');
        $form->setAttrib('id', 'popupformid');
        $form->getElement('submit')->setAttrib('onclick', null);

        $msgarray = array();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $employeeId = (int) $this->getRequest()->getParam('employee_id', 0);
                    $startDate  = sapp_Global::change_date($this->getRequest()->getParam('start_date'), 'database');
                    $endDateRaw = $this->getRequest()->getParam('end_date');
                    $endDate    = !empty($endDateRaw) ? sapp_Global::change_date($endDateRaw, 'database') : null;

                    if (!empty($endDate) && ($startDate > $endDate)) {
                        throw new Exception("Effective Start Date cannot be after End Date.");
                    }

                    $overlap = $this->_model->checkDateOverlap($employeeId, $startDate, $endDate);
                    if ($overlap) {
                        throw new Exception($overlap['message']);
                    }

                    $data = array(
                        'employee_id'          => $employeeId,
                        'pay_group_id'         => (int) $this->getRequest()->getParam('pay_group_id', 0),
                        'remunerationbasis_id' => (int) $this->getRequest()->getParam('remunerationbasis_id', 0),
                        'currency_id'          => (int) $this->getRequest()->getParam('currency_id', 0),
                        'base_amount'          => (float) $this->getRequest()->getParam('base_amount', 0),
                        'start_date'           => $startDate,
                        'end_date'             => $endDate,
                        'description'          => trim((string) $this->getRequest()->getParam('description', '')),
                        'isactive'             => (int) $this->getRequest()->getParam('isactive', 1)
                    );

                    $newId = $this->_model->saveRecord($data);

                    if (defined('EMP_COMPENSATION_MENU_ID')) {
                        sapp_Global::logManager(EMP_COMPENSATION_MENU_ID, 1, $this->_loginUserId, $newId);
                    }
                    $db->commit();

                    $activeRecords = $this->_model->getPairs(true);
                    $optionsHtml = '';
                    foreach ($activeRecords as $valId => $valName) {
                        $optionsHtml .= sapp_Global::selectOptionBuilder($valId, $valName, $newId);
                    }

                    $this->view->optionsHtml   = $optionsHtml;
                    $this->view->newInsertedId = $newId;
                    $this->view->eventact      = 'added';
                    $this->view->popup         = 'close';
                } catch (\Throwable $e) {
                    $db->rollBack();
                    $msgarray['error'] = $e->getMessage();
                }
            } else {
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
            }
        }

        $this->view->msgarray = $msgarray;
        $this->view->form     = $form;
    }

    private function _prepareForm($id = null, $readonly = false, $prefillEmpId = 0)
    {
        $form = new Default_Form_Empcompensation();
        $isInUse = false;

        // 1. Populate Employees
        if (class_exists('Default_Model_Employee')) {
            $empModel = new Default_Model_Employee();
            $form->employee_id->addMultiOption('', 'Select Employee...');
            $form->employee_id->addMultiOptions($empModel->getPairs(true));
        }

        // 2. Populate Pay Groups
        $pgModel = new Default_Model_Paygroup();
        $form->pay_group_id->addMultiOption('', 'Select Pay Group...');
        $form->pay_group_id->addMultiOptions($pgModel->getPairs(true));

        // 3. Populate Remuneration Basis
        $rbModel = new Default_Model_Remunerationbasis();
        $form->remunerationbasis_id->addMultiOption('', 'Select Remuneration Basis...');
        $form->remunerationbasis_id->addMultiOptions($rbModel->getPairs(true));

        // 4. Populate Currencies
        if (class_exists('Default_Model_Currency')) {
            $curModel = new Default_Model_Currency();
            $form->currency_id->addMultiOption('', 'Select Currency...');
            $form->currency_id->addMultiOptions($curModel->getPairs(true));
        }

        if ($prefillEmpId > 0) {
            $form->employee_id->setValue($prefillEmpId);
        }

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }

            if (!empty($data['start_date'])) $data['start_date'] = sapp_Global::change_date($data['start_date'], 'view');
            if (!empty($data['end_date']))   $data['end_date']   = sapp_Global::change_date($data['end_date'], 'view');

            if ($form->getElement('employee_id_hidden')) {
                $form->getElement('employee_id_hidden')->setValue($data['employee_id']);
            }

            $form->populate($data);
            $form->getElement('submit')->setLabel('Update Compensation');

            if (!$readonly && $this->_model->checkInUse($id)) {
                $isInUse = true;
                foreach (array('pay_group_id', 'remunerationbasis_id', 'currency_id', 'base_amount', 'start_date') as $locked) {
                    if ($el = $form->getElement($locked)) {
                        $el->setAttrib('disabled', 'disabled');
                    }
                }
            }
        }

        if ($readonly) {
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        $this->view->isInUse = $isInUse;
        return $form;
    }
}
