<?php

/*********************************************************************************
 *  This file is part of Sentrifugo-Modern.
 *  Copyright (C) 2026 Hablul.
 ********************************************************************************/

/**
 * Measurement Controller
 *
 * Manages CRUD, Grid, Popup, and Merged Master-Detail synchronization for
 * Skill Measurements and Measurement Index rows.
 * Refactored to comply with SSOT v7.0.0.
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution <https://hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo-Modern v4.0
 */
class Default_MeasurementController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserGroup;
    protected $_loginuserRole;
    protected $_model;
    protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initializes model, auth context, and view privilege variables.
     *
     * @return void
     */
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Measurement();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            $this->view->loginuserRole  = $this->_loginuserRole;
            $this->view->loginuserGroup = $this->_loginuserGroup;
        }

        require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
    }

    /**
     * Validates active session and configures AjaxContext for JSON actions.
     *
     * @return void
     */
    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                die(Zend_Json::encode(['login' => 'failed']));
            }
            $this->_redirect('');
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

    /**
     * Renders the Measurements DataGrid.
     *
     * @return void
     */
    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $searchData    = (string)$request->getParam('searchData', '');
            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);

            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'name');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort       = 'ASC';
                $by         = 'name';
                $page       = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray     = [$dataTmp];
            $this->view->call          = $call;
            $this->view->messages      = $this->_helper->flashMessenger->getMessages();
            $this->view->menu_id       = defined('MEASUREMENT_MENU_ID') ? MEASUREMENT_MENU_ID : 317;
            $this->view->activeMenuUrl = rtrim(BASE_URL, '/') . '/measurement';
        } catch (\Throwable $e) {
            error_log('Measurement Index Error: ' . $e->getMessage());
            if (APPLICATION_ENV == 'development') {
                Zend_Debug::dump($e->getMessage());
            }
        }
    }

    /**
     * Renders a read-only view of a Measurement scale and its index values.
     *
     * @return void
     */
    public function viewAction()
    {
        $id   = (int)$this->getRequest()->getParam('id');
        $form = $this->_prepareForm($id, true);

        if ($id <= 0 || !$this->_model->getRecordById($id)) {
            $this->view->nodata = true;
        }

        $this->view->form           = $form;
        $this->view->id             = $id;
        $this->view->controllername = 'measurement';
        $this->view->menu_id        = defined('MEASUREMENT_MENU_ID') ? MEASUREMENT_MENU_ID : 317;
        $this->view->activeMenuUrl  = rtrim(BASE_URL, '/') . '/measurement';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    /**
     * Displays the Add Measurement form.
     *
     * @return void
     */
    public function addAction()
    {
        $this->view->form          = $this->_prepareForm();
        $this->view->activeMenuUrl = rtrim(BASE_URL, '/') . '/measurement';
        $this->render('save');
    }

    /**
     * Handles the On-The-Fly Popup Add workflow (SSOT VIII).
     *
     * @return void
     */
    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . '/layouts/scripts/popup/');

        $form = new Default_Form_Measurement();
        $form->setAction(BASE_URL . 'measurement/addpopup');
        $form->getElement('submit')->setAttrib('onclick', null);

        $request              = $this->getRequest();
        $msgarray             = [];
        $this->view->popup    = '';
        $indexesPayloadRaw    = (string)$request->getParam('indexes_payload', '[]');
        $decodedIndexes       = json_decode($indexesPayloadRaw, true);
        $this->view->childIndexes = is_array($decodedIndexes) ? $decodedIndexes : [];

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $valueType = trim((string)$request->getParam('value_type', 'LIST'));
                $indexesPayload = is_array($decodedIndexes) ? $decodedIndexes : [];

                if ($valueType === 'LIST' && empty($indexesPayload)) {
                    $msgarray['indexes_payload'] = 'List measurements require at least one index value.';
                } else {
                    $db = Zend_Db_Table::getDefaultAdapter();
                    $startedTransaction = !$db->getConnection()->inTransaction();
                    if ($startedTransaction) {
                        $db->beginTransaction();
                    }

                    try {
                        $descVal = trim((string)$request->getParam('description', ''));

                        $data = [
                            'code'        => strtoupper(trim((string)$request->getParam('code'))),
                            'name'        => trim((string)$request->getParam('name')),
                            'value_type'  => $valueType,
                            'description' => ($descVal !== '') ? $descVal : null,
                            'isactive'    => (int)$request->getParam('isactive', 1)
                        ];

                        $newId = $this->_model->saveRecord($data);
                        $this->_model->syncIndexes($newId, $valueType, $indexesPayload);

                        $menuId = defined('MEASUREMENT_MENU_ID') ? MEASUREMENT_MENU_ID : 317;
                        sapp_Global::logManager($menuId, 1, $this->_loginUserId, $newId);

                        if ($startedTransaction) {
                            $db->commit();
                        }

                        $activeRecords = $this->_model->getPairs(true);
                        $optionsHtml   = sapp_Global::selectOptionBuilder('', 'Select Measurement');
                        foreach ($activeRecords as $valId => $valName) {
                            $optionsHtml .= sapp_Global::selectOptionBuilder($valId, $valName, $newId);
                        }

                        $this->view->optionsHtml   = $optionsHtml;
                        $this->view->newInsertedId = $newId;
                        $this->view->eventact      = 'added';
                        $this->view->popup         = 'close';
                    } catch (\Throwable $dbError) {
                        if ($startedTransaction && $db->getConnection()->inTransaction()) {
                            $db->rollBack();
                        }
                        error_log('Measurement Popup Save Error: ' . $dbError->getMessage());
                        $msgarray['error'] = $dbError->getMessage();
                    }
                }
            } else {
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
            }
        }

        $this->view->controllername = 'measurement';
        $this->view->msgarray       = $msgarray;
        $this->view->form           = $form;
    }

    /**
     * Displays the Edit Measurement form.
     *
     * @return void
     */
    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form          = $this->_prepareForm($id, false);
        $this->view->activeMenuUrl = rtrim(BASE_URL, '/') . '/measurement';
        $this->render('save');
    }

    /**
     * Persists a Measurement and synchronizes child Index rows via AJAX JSON submission.
     *
     * @return void
     */
    public function saveAction()
    {
        $form    = new Default_Form_Measurement();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id        = (int)$request->getParam('id');
                $valueType = trim((string)$request->getParam('value_type', 'LIST'));

                $indexesPayload = json_decode((string)$request->getParam('indexes_payload', '[]'), true);
                if (!is_array($indexesPayload)) {
                    $indexesPayload = [];
                }

                if ($valueType === 'LIST' && empty($indexesPayload)) {
                    $this->_helper->json(['indexes_payload' => 'List measurements require at least one index value.']);
                    return;
                }

                $db = Zend_Db_Table::getDefaultAdapter();
                $startedTransaction = !$db->getConnection()->inTransaction();
                if ($startedTransaction) {
                    $db->beginTransaction();
                }

                try {
                    $descVal = trim((string)$request->getParam('description', ''));

                    $data = [
                        'code'        => strtoupper(trim((string)$request->getParam('code'))),
                        'name'        => trim((string)$request->getParam('name')),
                        'value_type'  => $valueType,
                        'description' => ($descVal !== '') ? $descVal : null,
                        'isactive'    => (int)$request->getParam('isactive', 1)
                    ];

                    $actionFlag    = ($id > 0) ? 2 : 1;
                    $measurementId = $this->_model->saveRecord($data, $id);

                    $this->_model->syncIndexes($measurementId, $valueType, $indexesPayload);

                    $menuId = defined('MEASUREMENT_MENU_ID') ? MEASUREMENT_MENU_ID : 317;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $measurementId);

                    if ($startedTransaction) {
                        $db->commit();
                    }

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(['success' => "Measurement $msg successfully."]);
                    $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes']);
                } catch (\Throwable $e) {
                    if ($startedTransaction && $db->getConnection()->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('Measurement Save Error: ' . $e->getMessage());
                    $this->_helper->json(['result' => 'exception', 'message' => $e->getMessage()]);
                }
            } else {
                $this->_helper->json($form->getMessages());
            }
        }
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    /**
     * Soft-deletes a Measurement record via AJAX.
     *
     * @return void
     */
    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                $this->_model->deleteRecord($id);

                $menuId = defined('MEASUREMENT_MENU_ID') ? MEASUREMENT_MENU_ID : 317;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);

                $this->_helper->json(['msgtype' => 'success', 'message' => 'Measurement deleted successfully.']);
            } catch (\Throwable $e) {
                $this->_helper->json(['msgtype' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            $this->_helper->json(['msgtype' => 'error', 'message' => 'Invalid Request.']);
        }
    }

    /**
     * Toggles active/inactive state via AJAX.
     *
     * @return void
     */
    public function makeactiveinactiveAction()
    {
        try {
            $id     = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');

            $this->_model->toggleStatus($id, $status);

            $menuId = defined('MEASUREMENT_MENU_ID') ? MEASUREMENT_MENU_ID : 317;
            sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);

            $this->_helper->json(['result' => 'yes']);
        } catch (\Throwable $e) {
            $this->_helper->json(['result' => 'no']);
        }
    }

    /* =================================================================================
     * GROUP 5: INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Populates the Measurement form and loads child index records for Merged UI rendering.
     *
     * @param int|null $id
     * @param bool     $readonly
     * @return Default_Form_Measurement
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Measurement();
        $this->view->childIndexes = [];

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');

                if ($data['value_type'] === 'LIST') {
                    $indexModel = new Default_Model_Measurementindex();
                    $this->view->childIndexes = $indexModel->getIndexesByMeasurementId($id, true);
                }
            }
        }

        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }
}
