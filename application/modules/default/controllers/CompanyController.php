<?php

/**
 * Company Controller
 *
 * Manages the top-level organizational entity (Companies) and their Domain Mappings.
 * Refactored to comply with SSOT v6.4 (PHP 7.4 compatibility, Atomic Updates, Many-to-Many Sync).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_CompanyController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Company();
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

            // Fetch mapped Domains for display (Read-Only List)
            $db = Zend_Db_Table::getDefaultAdapter();
            $select = $db->select()
                ->from(array('map' => 'map_company_businessdomain'), array())
                ->joinInner(array('dom' => 'main_businessdomain'), 'map.businessdomain_id = dom.id', array('name', 'code'))
                ->where('map.company_id = ?', $id)
                ->where('map.isactive = ?', 1);

            $this->view->mappedDomains = $db->fetchAll($select);

            // Fetch Organization Image (Current specific handling)
            $record = $this->_model->getRecordById($id);
            $this->view->currentImage = ($record && !empty($record['org_image'])) ? $record['org_image'] : null;
        }

        $this->view->controllername = 'company';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        // Permission check (Ensure COMPANY_MENU_ID is defined in constants)
        if (sapp_Global::_checkprivileges(COMPANY_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        if (sapp_Global::_checkprivileges(COMPANY_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $id = (int) $this->getRequest()->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if (!$form) {
            $this->view->nodata = "nodata";
        } else {
            $this->view->form = $form;
            $this->view->id   = $id;

            $record = $this->_model->getRecordById($id);
            $this->view->currentImage = ($record && !empty($record['org_image'])) ? $record['org_image'] : null;
        }

        $this->render('save');
    }

    public function saveAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_redirect('company/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                $data = array(
                    'code'                => trim((string) $request->getParam('code', '')),
                    'name'                => trim((string) $request->getParam('name', '')),
                    'registration_number' => trim((string) $request->getParam('registration_number', '')),
                    'website'             => trim((string) $request->getParam('website', '')),
                    'representative_id'   => (int) $request->getParam('representative_id'),
                    'org_start_date'      => sapp_Global::change_date($request->getParam('org_start_date'), 'database'),
                    'description'         => trim((string) $request->getParam('description', '')),
                    'isactive'            => (int) $request->getParam('isactive', 1)
                );

                // Handle File Upload 
                if ($form->org_image->isUploaded()) {
                    $form->org_image->receive();
                    $data['org_image'] = basename($form->org_image->getFileName());
                }

                $actionFlag = 2; // Edit
                $saveMode   = 'updated';
                $syncId     = $id; // The ID to use for the mapping table sync

                if (!$id) {
                    $actionFlag = 1; // Add
                    $saveMode   = 'added';
                    $savedId    = $this->_model->saveRecord($data);
                    $syncId     = $savedId; // New ID generated
                } else {
                    $this->_model->saveRecord($data, $id);
                    $savedId    = $id;
                }

                // Many-to-Many Sync Logic: Company -> Business Domains
                $selectedDomains = $request->getParam('businessdomain', array());

                // 1. Hard Delete all existing mappings for this Company
                // Note: The mapping table must be updated to `map_company_businessdomain` via DDL in your DB.
                $db->delete('map_company_businessdomain', array('company_id = ?' => $syncId));

                // 2. Insert new mappings
                if (!empty($selectedDomains)) {
                    foreach ($selectedDomains as $domainId) {
                        $db->insert('map_company_businessdomain', array(
                            'company_id'        => $syncId,
                            'businessdomain_id' => (int)$domainId,
                            'createdby'         => $this->_loginUserId,
                            'createddate'       => gmdate("Y-m-d H:i:s"),
                            'isactive'          => 1
                        ));
                    }
                }

                // Logging
                sapp_Global::logManager(COMPANY_MENU_ID, $actionFlag, $this->_loginUserId, $savedId);
                $db->commit();

                // Set FlashMessenger for the redirect page
                $this->_helper->flashMessenger->addMessage(array('success' => "Company {$saveMode} successfully."));

                // SSOT v6.4 Strict AJAX JSON Response (Success with Redirect)
                $this->_helper->json(array(
                    'result'    => 'saved',
                    'nomessage' => 'yes'
                ));
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("Company Save Error: " . $e->getMessage());

                // SSOT v6.4 Strict AJAX JSON Response (Exception)
                $this->_helper->json(array(
                    'result'  => 'exception',
                    'message' => 'System error occurred.',
                    'msgtype' => 'error'
                ));
            }
        } else {
            // SSOT v6.4 Strict AJAX JSON Response (Validation Error - Raw Array)
            $this->_helper->json($form->getMessages());
        }
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS TOGGLE
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('objid', 0);

        if ($id) {
            if (sapp_Global::_checkprivileges(COMPANY_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(array('result' => 'error', 'message' => 'Permission Denied.'));
            }

            try {
                // Dependency check (SSOT v6.4)
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("This Company is actively linked to Business Units or Divisions and cannot be deleted.");
                }

                $this->_model->deleteRecord($id);
                sapp_Global::logManager(COMPANY_MENU_ID, 3, $this->_loginUserId, $id);

                // Standard Success Response
                return $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success'));
            } catch (\Throwable $e) {
                error_log("Company Delete Error: " . $e->getMessage());
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
            sapp_Global::logManager(COMPANY_MENU_ID, 4, $this->_loginUserId, $id);

            $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.'));
        } catch (\Throwable $e) {
            error_log("Company Toggle Error: " . $e->getMessage());
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
        $form->setAction(BASE_URL . 'company/savepopup/prev_cntrl/' . $prev_cntrl);
        $form->setAttrib('id', 'popupformid');

        // 3. Remove saveDetails onclick for standard/modal POST behavior
        $form->getElement('submit')->setAttrib('onclick', null);

        $this->view->form = $form;
        $this->view->prev_cntrl = $prev_cntrl;
    }

    /**
     * Internal Form Preparation
     * 
     * @param int|null $id
     * @param bool $readonly
     * @return Default_Form_Company|null
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Company();

        // 1. Populate Dropdowns using SSOT v6.4 standard
        $empModel = new Default_Model_Employee(); // Assuming Employee/User model implementation
        $form->representative_id->addMultiOption('', 'Select Representative');
        $form->representative_id->addMultiOptions($empModel->getPairs());

        $domainModel = new Default_Model_Businessdomain();
        $domains = $domainModel->getPairs(true);
        $form->businessdomain->addMultiOptions($domains);

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }

            // Formatting Dates for UI
            if (!empty($data['org_start_date'])) {
                $data['org_start_date'] = sapp_Global::change_date($data['org_start_date'], 'view');
            }

            // Fetch mapped domains using Model encapsulation (SSOT v6.4)
            $mapModel = new Default_Model_CompanyBusinessdomain();
            $mappedDomainIds = $mapModel->getDomainIdsByCompany($id);
            $data['businessdomain'] = $mappedDomainIds; // Set the multi-select values for business domains 

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
