<?php

/**
 * Holiday Master Policy Controller
 *
 * Manages the definition of master holiday policies, recurrence rules, and criteria targeting payloads.
 * Complies with SSOT v6.7.7 (PHP 7.4 strict, Raw Array JSON, Dependency Checks, Balanced Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_HolidayController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Holiday();
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

            // SSOT v6.7.7: Default sort human-readable
            $sort    = (string) $request->getParam('sort', 'ASC');
            $by      = (string) $request->getParam('by', 'name');

            $perPage = ($dashboardcall === 'Yes')
                ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
                : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'ASC';
                $by = 'name';
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
            $this->view->form = $form;
            $this->view->id   = $id;

            // Criteria payload setup for Phase 4B View integration
            $criteriaModel = new Default_Model_Holidaycriteria();
            $this->view->existingCriteria = json_encode($criteriaModel->getCriteriaByHolidayId($id));
        }

        $this->view->controllername = 'holiday';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        if (defined('HOLIDAY') && sapp_Global::_checkprivileges(HOLIDAY, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->view->existingCriteria = json_encode([]); // Empty state for Phase 4B
        $this->render('save');
    }

    public function editAction()
    {
        if (defined('HOLIDAY') && sapp_Global::_checkprivileges(HOLIDAY, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $id = (int) $this->getRequest()->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if (!$form) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->form = $form;
            $this->view->id   = $id;

            // Load existing criteria for Phase 4B UI
            $criteriaModel = new Default_Model_Holidaycriteria();
            $this->view->existingCriteria = json_encode($criteriaModel->getCriteriaByHolidayId($id));
        }

        $this->render('save');
    }

    public function saveAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_redirect('holiday/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                $data = [
                    'name'               => trim((string) $request->getParam('name', '')),
                    'category_id'        => (int) $request->getParam('category_id'),
                    'tag_id'             => (int) $request->getParam('tag_id'),
                    'duration_days'      => (float) $request->getParam('duration_days', 1.00),
                    'start_time'         => trim((string) $request->getParam('start_time', '00:00:00')),
                    'end_time'           => trim((string) $request->getParam('end_time', '23:59:59')),
                    'payroll_multiplier' => (float) $request->getParam('payroll_multiplier', 1.00),
                    'priority'           => (int) $request->getParam('priority', 0),
                    'work_policy'        => trim((string) $request->getParam('work_policy', 'OFF_DAY')),
                    'weekend_policy'     => trim((string) $request->getParam('weekend_policy', 'FIXED_DATE')),
                    'recurrence_type'    => trim((string) $request->getParam('recurrence_type', 'MANUAL')),
                    'recur_month'        => $request->getParam('recur_month') !== '' ? (int)$request->getParam('recur_month') : null,
                    'recur_day'          => $request->getParam('recur_day') !== '' ? (int)$request->getParam('recur_day') : null,
                    'recur_week_num'     => $request->getParam('recur_week_num') !== '' ? (int)$request->getParam('recur_week_num') : null,
                    'recur_day_of_week'  => $request->getParam('recur_day_of_week') !== '' ? (int)$request->getParam('recur_day_of_week') : null,
                    'is_paid'            => (int) $request->getParam('is_paid', 1),
                    'is_mandatory'       => (int) $request->getParam('is_mandatory', 1),
                    'auto_generate'      => (int) $request->getParam('auto_generate', 1),
                    'description'        => trim((string) $request->getParam('description', '')),
                    'isactive'           => (int) $request->getParam('isactive', 1)
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

                // Phase 4B Criteria Sync Integration
                $criteriaPayload = $request->getParam('criteria_payload');
                if (!empty($criteriaPayload)) {
                    $criteriaArray = json_decode($criteriaPayload, true);
                    if (is_array($criteriaArray)) {
                        $criteriaModel = new Default_Model_Holidaycriteria();
                        $criteriaModel->syncCriteria($savedId, $criteriaArray);
                    }
                }

                // Logging
                if (defined('HOLIDAY')) {
                    sapp_Global::logManager(HOLIDAY, $actionFlag, $this->_loginUserId, $savedId);
                }

                $db->commit();

                // FlashMessenger for standard redirect
                $this->_helper->flashMessenger->addMessage(['success' => "Holiday Policy {$saveMode} successfully."]);

                // SSOT Strict AJAX JSON Response (Success with Redirect)
                $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes']);
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("Holiday Master Policy Save Error: " . $e->getMessage());

                $this->_helper->json([
                    'result'  => 'exception',
                    'message' => $e->getMessage(),
                    'msgtype' => 'error'
                ]);
            }
        } else {
            // Validation Error - Raw Array output directly per SSOT
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
            if (defined('HOLIDAY') && sapp_Global::_checkprivileges(HOLIDAY, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(['result' => 'error', 'message' => 'Permission Denied.']);
            }

            try {
                // Dependency check: Blocks delete if occurrences exist in main_holiday_date
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("This Holiday Policy has generated calendar dates and cannot be deleted. Deactivate it instead.");
                }

                $this->_model->deleteRecord($id);
                if (defined('HOLIDAY')) {
                    sapp_Global::logManager(HOLIDAY, 3, $this->_loginUserId, $id);
                }

                return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success']);
            } catch (\Throwable $e) {
                error_log("Holiday Delete Error: " . $e->getMessage());
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
            if (defined('HOLIDAY')) {
                sapp_Global::logManager(HOLIDAY, 4, $this->_loginUserId, $id);
            }

            $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.']);
        } catch (\Throwable $e) {
            error_log("Holiday Toggle Error: " . $e->getMessage());
            $this->_helper->json(['result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.']);
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Holiday();

        // Populate Category and Tag Dropdowns natively per SSOT VI.12
        $categoryModel = new Default_Model_Holidaycategory();
        $form->category_id->addMultiOption('', 'Select Category...');
        $form->category_id->addMultiOptions($categoryModel->getPairs(true));

        $tagModel = new Default_Model_Holidaytag();
        $form->tag_id->addMultiOption('', 'Select Reporting Tag / Group...');
        $form->tag_id->addMultiOptions($tagModel->getPairs(true));

        // --- NEW: INJECT TARGETING CRITERIA LOOKUPS FOR TAB 2 ---
        $criteriaLookups = [
            'Country'        => (class_exists('Default_Model_Country')) ? (new Default_Model_Country())->getPairs(true) : [],
            'Department'     => (class_exists('Default_Model_Department')) ? (new Default_Model_Department())->getPairs(true) : [],
            'BusinessUnit'   => (class_exists('Default_Model_Businessunit')) ? (new Default_Model_Businessunit())->getPairs(true) : [],
            'Division'       => (class_exists('Default_Model_Division')) ? (new Default_Model_Division())->getPairs(true) : [],
            'Gender'         => (class_exists('Default_Model_Gender')) ? (new Default_Model_Gender())->getPairs(true) : ['Male' => 'Male', 'Female' => 'Female'],
            'Religion'       => (class_exists('Default_Model_Religion')) ? (new Default_Model_Religion())->getPairs(true) : [],
            'EmploymentType' => (class_exists('Default_Model_Employmenttype')) ? (new Default_Model_Employmenttype())->getPairs(true) : []
        ];
        $this->view->criteriaLookups = json_encode($criteriaLookups);
        // --- END LOOKUP INJECTION ---

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation per SSOT XI.3
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }

            $form->populate($data);
            $form->getElement('submit')->setLabel('Update Policy');
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
