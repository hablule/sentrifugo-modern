<?php

/**
 * Phone Number Controller
 *
 * Manages the Phone Directory, handling core number data and owner mappings.
 * Complies with SSOT v6.6 (PHP 7.4 safe, Raw Array JSON, Junction Table Sync).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_PhonenumberController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Phonenumber();
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = (int)$user->id;
            $this->_loginuserRole  = (int)$user->emprole;
            $this->_loginuserGroup = (int)$user->group_id;

            // SSOT v6.6 Mandatory View Assignments
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

            // SSOT v6.6: Default sort human-readable
            $sort    = (string) $request->getParam('sort', 'ASC');
            $by      = (string) $request->getParam('by', 'number');

            $perPage = ($dashboardcall === 'Yes')
                ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
                : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'ASC';
                $by = 'number';
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

        $this->view->controllername = 'phonenumber';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        // SSOT v6.6: Ensure PHONENUMBER is defined in constants.php
        if (sapp_Global::_checkprivileges(PHONENUMBER, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        if (sapp_Global::_checkprivileges(PHONENUMBER, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
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
            return $this->_redirect('phonenumber/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                $data = array(
                    'number'      => trim((string) $request->getParam('number', '')),
                    'type_id'     => (int) $request->getParam('type_id'),
                    'category_id' => (int) $request->getParam('category_id'),
                    'description' => trim((string) $request->getParam('description', '')),
                    'isactive'    => (int) $request->getParam('isactive', 1)
                );

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

                // --- SSOT v6.6 Atomic Mapping Sync (Offloaded to Model) ---
                $owners = [
                    'employee'   => $request->getParam('owners_employee', []),
                    'department' => $request->getParam('owners_department', []),
                    'company'    => $request->getParam('owners_company', []),
                    'team'       => $request->getParam('owners_team', []),
                    'division'   => $request->getParam('owners_division', []),
                    'sbunit'     => $request->getParam('owners_sbunit', []),
                    'section'    => $request->getParam('owners_section', []),
                ];

                $this->_model->syncOwnerMappings($savedId, $owners);
                // --- END SYNC LOGIC ---

                // Logging
                sapp_Global::logManager(PHONENUMBER, $actionFlag, $this->_loginUserId, $savedId);
                $db->commit();

                // FlashMessenger for standard redirect
                $this->_helper->flashMessenger->addMessage(array('success' => "Phone Number {$saveMode} successfully."));

                // SSOT Strict AJAX JSON Response (Success with Redirect)
                $this->_helper->json(array(
                    'result'    => 'saved',
                    'nomessage' => 'yes'
                ));
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("PhoneNumber Save Error: " . $e->getMessage());

                // Exception returned in specific structure
                $this->_helper->json(array(
                    'result'  => 'exception',
                    'message' => 'System error occurred. Check logs.',
                    'msgtype' => 'error'
                ));
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
            if (sapp_Global::_checkprivileges(PHONENUMBER, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(array('result' => 'error', 'message' => 'Permission Denied.'));
            }

            try {
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("This record is actively in use and cannot be deleted.");
                }

                $this->_model->deleteRecord($id);
                sapp_Global::logManager(PHONENUMBER, 3, $this->_loginUserId, $id);

                return $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success'));
            } catch (\Throwable $e) {
                error_log("PhoneNumber Delete Error: " . $e->getMessage());
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
            sapp_Global::logManager(PHONENUMBER, 4, $this->_loginUserId, $id);

            $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.'));
        } catch (\Throwable $e) {
            error_log("PhoneNumber Toggle Error: " . $e->getMessage());
            $this->_helper->json(array('result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.'));
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Note: "On-The-Fly" Popups are not suitable for this module due to the complexity
     * of its Many-to-Many relationships. The primary Add/Edit form is the correct UI.
     */

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Phonenumber();

        // 1. Populate standard dropdowns
        $typeModel = new Default_Model_Phonetype();
        $form->type_id->addMultiOption('', 'Select Type...');
        $form->type_id->addMultiOptions($typeModel->getPairs());

        $catModel = new Default_Model_Phonecategory();
        $form->category_id->addMultiOption('', 'Select Category...');
        $form->category_id->addMultiOptions($catModel->getPairs());

        // 2. Populate the Multiselect owner options

        $companyModel = new Default_Model_Company();
        $form->owners_company->addMultiOptions($companyModel->getPairs());

        $sbuModel = new Default_Model_Businessunit();
        $form->owners_sbunit->addMultiOptions($sbuModel->getPairs());

        $divModel = new Default_Model_Division();
        $form->owners_division->addMultiOptions($divModel->getPairs());

        $deptModel = new Default_Model_Department();
        $form->owners_department->addMultiOptions($deptModel->getPairs());

        $secModel = new Default_Model_Section();
        $form->owners_section->addMultiOptions($secModel->getPairs());

        $teamModel = new Default_Model_Team();
        $form->owners_team->addMultiOptions($teamModel->getPairs());

        $empModel = new Default_Model_Employee();
        $form->owners_employee->addMultiOptions($empModel->getPairs());

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }

            // Fetch and set the currently mapped owners
            $mappedData = $this->_model->getMappedOwners($id);
            $form->owners_company->setValue($mappedData['company']);
            $form->owners_sbunit->setValue($mappedData['sbunit']);
            $form->owners_division->setValue($mappedData['division']);
            $form->owners_department->setValue($mappedData['department']);
            $form->owners_section->setValue($mappedData['section']);
            $form->owners_team->setValue($mappedData['team']);
            $form->owners_employee->setValue($mappedData['employee']);

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
