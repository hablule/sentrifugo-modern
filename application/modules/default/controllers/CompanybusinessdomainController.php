<?php

/**
 * Company Business Domain Controller
 *
 * Manages the mapping between top-level Companies and Business Domains.
 * Refactored to comply with SSOT v6.4 (PHP 7.4 compatibility, Zend_Db_Select, Composite PK Handling).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_CompanybusinessdomainController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_CompanyBusinessdomain();
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
            $by      = (string) $request->getParam('by', 'company_name');

            $perPage = ($dashboardcall === 'Yes')
                ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
                : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'ASC';
                $by = 'company_name';
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
        $id = (string) $this->getRequest()->getParam('id', ''); // Composite key e.g., '1_5'
        $form = $this->_prepareForm($id, true);

        if (!$form) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->form = $form;
            $this->view->id   = $id;
        }

        $this->view->controllername = 'companybusinessdomain';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        if (sapp_Global::_checkprivileges(COMPANYENGAGEMENT_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        if (sapp_Global::_checkprivileges(COMPANYENGAGEMENT_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $id = (string) $this->getRequest()->getParam('id', '');
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
            return $this->_redirect('companybusinessdomain/index');
        }

        $id = (string) $request->getParam('id', '');
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                // If editing, the primary key dropdowns are disabled and not posted.
                // We extract their values from the hidden workaround fields per SSOT VI.
                $companyId = (int) $request->getParam('company_id_hidden', $request->getParam('company_id', 0));
                $domainId  = (int) $request->getParam('businessdomain_id_hidden', $request->getParam('businessdomain_id', 0));

                if ($companyId <= 0 || $domainId <= 0) {
                    throw new Exception("Invalid Company or Domain mapping specified.");
                }

                $data = array(
                    'company_id'        => $companyId,
                    'businessdomain_id' => $domainId,
                    'description'       => trim((string) $request->getParam('description', '')),
                    'isactive'          => (int) $request->getParam('isactive', 1)
                );

                // Custom Composite Uniqueness Check
                $existing = $this->_model->checkMappingExists($companyId, $domainId, $id);
                if ($existing) {
                    // Send explicit validation error back to UI matching input fields
                    echo Zend_Json::encode(array(
                        'businessdomain_id' => 'This Business Domain is already mapped to this Company.'
                    ));
                    $db->rollBack();
                    exit;
                }

                $actionFlag = 2; // Edit
                $saveMode   = 'updated';

                if (empty($id)) {
                    $actionFlag = 1; // Add
                    $saveMode   = 'mapped';
                    $savedId    = $this->_model->saveRecord($data); // Model returns composite string '1_5'
                } else {
                    $this->_model->saveRecord($data, $id);
                    $savedId    = $id;
                }

                // Logging requires an integer ID. We use the Company ID as the primary context object.
                sapp_Global::logManager(COMPANYENGAGEMENT_MENU_ID, $actionFlag, $this->_loginUserId, $companyId);

                $db->commit();

                // Set FlashMessenger for the redirect page
                $this->_helper->flashMessenger->addMessage(array('success' => "Company Engagement {$saveMode} successfully."));

                // SSOT v6.4 Strict AJAX JSON Response (Success with Redirect)
                $this->_helper->json(array(
                    'result'    => 'saved',
                    'nomessage' => 'yes'
                ));
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("CompanyBusinessDomain Save Error: " . $e->getMessage());

                // SSOT v6.4 Strict AJAX JSON Response (Exception)
                $this->_helper->json(array(
                    'result'  => 'exception',
                    'message' => 'System error occurred.',
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
        $id = (string) $this->getRequest()->getParam('objid', ''); // Composite '1_5'

        if (!empty($id)) {
            if (sapp_Global::_checkprivileges(COMPANYENGAGEMENT_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(array('result' => 'error', 'message' => 'Permission Denied.'));
            }

            try {
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("Record is in use and cannot be deleted.");
                }

                $this->_model->deleteRecord($id);

                // Logging requires int, extract Company ID
                $companyId = (int) explode('_', $id)[0];
                sapp_Global::logManager(COMPANYENGAGEMENT_MENU_ID, 3, $this->_loginUserId, $companyId);

                return $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'message' => 'Mapping deleted successfully.', 'msgtype' => 'success'));
            } catch (\Throwable $e) {
                error_log("CompanyBusinessDomain Delete Error: " . $e->getMessage());
                return $this->_helper->json(array('result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error'));
            }
        }

        return $this->_helper->json(array('result' => 'error', 'message' => 'Invalid request'));
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id     = (string) $this->getRequest()->getParam('id', '');
            $status = (int) $this->getRequest()->getParam('status', 0);

            $this->_model->toggleStatus($id, $status);

            $companyId = (int) explode('_', $id)[0];
            sapp_Global::logManager(COMPANYENGAGEMENT_MENU_ID, 4, $this->_loginUserId, $companyId);

            $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.'));
        } catch (\Throwable $e) {
            error_log("CompanyBusinessDomain Toggle Error: " . $e->getMessage());
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
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $prev_cntrl = (string) $this->getRequest()->getParam('prev_cntrl', '');

        $form = $this->_prepareForm();

        // Ensure form posts to savepopupAction
        $form->setAction(BASE_URL . 'companybusinessdomain/savepopup/prev_cntrl/' . $prev_cntrl);
        $form->setAttrib('id', 'popupformid');

        // Remove saveDetails onclick for standard modal POST behavior
        $form->getElement('submit')->setAttrib('onclick', null);

        $this->view->form = $form;
        $this->view->prev_cntrl = $prev_cntrl;
    }

    /**
     * Internal Form Preparation
     * 
     * @param string|null $id Composite key
     * @param bool $readonly
     * @return Default_Form_CompanyBusinessdomain|null
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_CompanyBusinessdomain();

        // 1. Populate Core Dropdowns using Native Zend implementation (No populate_select)
        $companyModel = new Default_Model_Company();
        $form->company_id->addMultiOption('', 'Select Company');
        $form->company_id->addMultiOptions($companyModel->getPairs());

        $domainModel = new Default_Model_Businessdomain();
        $form->businessdomain_id->addMultiOption('', 'Select Business Domain');
        $form->businessdomain_id->addMultiOptions($domainModel->getPairs());

        if (!empty($id)) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention & Sanitation
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }

            // Populate Hidden Override fields for Edit mode
            if ($form->getElement('company_id_hidden')) {
                $form->getElement('company_id_hidden')->setValue($data['company_id']);
            }
            if ($form->getElement('businessdomain_id_hidden')) {
                $form->getElement('businessdomain_id_hidden')->setValue($data['businessdomain_id']);
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
