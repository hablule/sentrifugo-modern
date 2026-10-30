<?php

/**
 * Business Unit Controller
 *
 * Manages Organizational Business Units.
 * Refactored to comply with SSOT v6.4.
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_BusinessunitController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Businessunit();
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = (int)$user->id;
            $this->_loginuserRole  = (int)$user->emprole;
            $this->_loginuserGroup = (int)$user->group_id;

            // SSOT v6.4 Mandatory View Assignments
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

            // SSOT v6.4: Default sort human-readable
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

        $this->view->controllername = 'businessunit';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        // Permission check (Ensure BUSINESSUNITS is defined)
        if (sapp_Global::_checkprivileges(BUSINESSUNITS, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        if (sapp_Global::_checkprivileges(BUSINESSUNITS, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
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
            return $this->_redirect('businessunit/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {

            // --- SERVER-SIDE HIERARCHY VALIDATION ---
            $companyParent = $request->getParam('company_id');
            $sbuParent = $request->getParam('parent_id');
            $parentCount = 0;
            if (!empty($companyParent)) $parentCount++;
            if (!empty($sbuParent)) $parentCount++;

            if ($parentCount > 1 || $parentCount === 0) {
                $errorMsg = ($parentCount > 1)
                    ? "A Business Unit can only have one parent. Please select either a Company or a Parent Business Unit, not both."
                    : "A Business Unit must have a parent. Please select either a Company or a Parent Business Unit.";

                // Manually add the error to the form object
                $form->getElement('company_id')->addError($errorMsg);

                // Return the error in the standard JSON format that hrms.js expects
                echo Zend_Json::encode($form->getMessages());
                exit;
            }
            // --- END VALIDATION ---

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                // Ensure empty string selects convert to NULL for foreign keys
                $data = array(
                    'code'              => trim((string) $request->getParam('code', '')),
                    'name'              => trim((string) $request->getParam('name', '')),
                    'startdate'         => sapp_Global::change_date($request->getParam('startdate'), 'database'),
                    'company_id'        => $request->getParam('company_id') ?: null,
                    'parent_id'         => $request->getParam('parent_id') ?: null,
                    'unithead_id'       => $request->getParam('unithead_id') ?: null,
                    'country'           => $request->getParam('country') ?: null,
                    'state'             => $request->getParam('state') ?: null,
                    'city'              => $request->getParam('city') ?: null,
                    'address1'          => trim((string) $request->getParam('address1', '')),
                    'address2'          => trim((string) $request->getParam('address2', '')),
                    'address3'          => trim((string) $request->getParam('address3', '')),
                    'timezone'          => $request->getParam('timezone') ?: null,
                    'service_desk_flag' => (int) $request->getParam('service_desk_flag', 1),
                    'description'       => trim((string) $request->getParam('description', '')),
                    'isactive'          => (int) $request->getParam('isactive', 1)
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
                sapp_Global::logManager(BUSINESSUNITS, $actionFlag, $this->_loginUserId, $savedId);
                $db->commit();

                // Set FlashMessenger for the redirect page
                $this->_helper->flashMessenger->addMessage(array('success' => "Business Unit {$saveMode} successfully."));

                // SSOT v6.4 Strict AJAX JSON Response (Success with Redirect)
                $this->_helper->json(array(
                    'result'    => 'saved',
                    'nomessage' => 'yes'
                ));
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("BusinessUnit Save Error: " . $e->getMessage());

                // SSOT v6.4 Strict AJAX JSON Response (Exception)
                $this->_helper->json(array(
                    'result'  => 'exception',
                    'message' => $e->getMessage(), // Pass specific rule-break message to UI
                    'msgtype' => 'error'
                ));
            }
        } else {
            // SSOT v6.4 Strict AJAX JSON Response (Validation Error - Raw Array)
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
            if (sapp_Global::_checkprivileges(BUSINESSUNITS, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(array('result' => 'error', 'message' => 'Permission Denied.'));
            }

            try {
                // Dependency check (SSOT v6.4)
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("This Business Unit is currently active and mapped to Employees or Divisions. It cannot be deleted.");
                }

                $this->_model->deleteRecord($id);
                sapp_Global::logManager(BUSINESSUNITS, 3, $this->_loginUserId, $id);

                // Standard Success Response
                return $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success'));
            } catch (\Throwable $e) {
                error_log("BusinessUnit Delete Error: " . $e->getMessage());
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
            sapp_Global::logManager(BUSINESSUNITS, 4, $this->_loginUserId, $id);

            $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.'));
        } catch (\Throwable $e) {
            error_log("BusinessUnit Toggle Error: " . $e->getMessage());
            $this->_helper->json(array('result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.'));
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * SSOT v6.4 "On-The-Fly" Popup Pattern
     */
    public function addpopupAction()
    {
        // 1. Shift layout to popup shell
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $prev_cntrl = (string) $this->getRequest()->getParam('prev_cntrl', '');

        $form = $this->_prepareForm();

        // Ensure form posts to savepopupAction
        $form->setAction(BASE_URL . 'businessunit/savepopup/prev_cntrl/' . $prev_cntrl);
        $form->setAttrib('id', 'popupformid');

        // 3. Remove saveDetails onclick for standard/modal POST behavior
        $form->getElement('submit')->setAttrib('onclick', null);

        $this->view->form = $form;
        $this->view->prev_cntrl = $prev_cntrl;
    }

    /**
     * Internal Form Preparation (Poly-Hierarchical)
     * 
     * @param int|null $id
     * @param bool $readonly
     * @return Default_Form_Businessunit|null
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Businessunit();

        // --- POPULATE PARENT DROPDOWNS ---
        $companyModel = new Default_Model_Company();
        $form->company_id->addMultiOption('', 'Select Company (if applicable)');
        $form->company_id->addMultiOptions($companyModel->getPairs());

        // For the recursive parent, exclude the current SBU to prevent self-parenting
        $selfPairs = $this->_model->getPairs(true, $id);
        $form->parent_id->addMultiOption('', 'Select Parent Business Unit (if applicable)');
        $form->parent_id->addMultiOptions($selfPairs);

        // --- POPULATE OTHER DROPDOWNS ---
        $empModel = new Default_Model_Employee();
        $form->unithead_id->addMultiOption('', 'Select Unit Head');
        $form->unithead_id->addMultiOptions($empModel->getPairs());

        $countryModel = new Default_Model_Country();
        $form->country->addMultiOption('', 'Select Country');
        $form->country->addMultiOptions($countryModel->getPairs());

        $timeZoneModel = new Default_Model_Timezone();
        $form->timezone->addMultiOption('', 'Select Timezone');
        $form->timezone->addMultiOptions($timeZoneModel->getPairs());

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }

            // Date Conversion for View
            if (!empty($data['startdate'])) {
                $data['startdate'] = sapp_Global::change_date($data['startdate'], 'view');
            }

            // Populate dependent dropdowns (State/City) if country exists
            if (!empty($data['country'])) {
                $stateModel = new Default_Model_State();
                $form->state->addMultiOption('', 'Select State / Province');
                $form->state->addMultiOptions($stateModel->getPairsByCountry($data['country']));
            }
            if (!empty($data['state'])) {
                $cityModel = new Default_Model_City();
                $form->city->addMultiOption('', 'Select City');
                $form->city->addMultiOptions($cityModel->getPairsByState($data['state']));
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
