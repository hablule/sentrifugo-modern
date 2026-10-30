<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Rating Controller
 * 
 * Strictly adheres to SSOT v6.2 (Method Grouping & Alphabetical Ordering).
 */
class Default_ParatingController extends Zend_Controller_Action
{
    /* -----------------------------------------------------------------------------
     * ATTRIBUTES (Protected, Alphabetical)
     * ----------------------------------------------------------------------------- */
    protected $_loginUserId;
    protected $_loginuserGroup;
    protected $_loginuserRole;
    protected $_model;
    protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE (Alphabetical)
     * ================================================================================= */

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Parating();

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

    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) die(Zend_Json::encode(['login' => 'failed']));
            else $this->_redirect('');
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('delete', 'json')
            ->addActionContext('makeactiveinactive', 'json')
            ->addActionContext('save', 'json')
            ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL (Alphabetical)
     * ================================================================================= */

    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') $this->_helper->layout->disableLayout();

            $searchData = (string)$request->getParam('searchData', '');
            $sort       = $request->getParam('sort', 'ASC');
            $by         = $request->getParam('by', 'name');
            $page       = $request->getParam('page', 1);
            $perPage    = $request->getParam('per_page', PERPAGE);

            if ($request->getParam('refresh') == 'refresh') {
                $sort = 'ASC';
                $by = 'name';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, null);
            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) {
            error_log("PaRating Index Error: " . $e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'parating';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE (Alphabetical)
     * ================================================================================= */

    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');

        // IMMUTABLE LEDGER LOCK
        if ($this->_model->checkIfUsedInAssessment($id)) {
            $this->_helper->flashMessenger->addMessage(['error' => "This rating scale has been used in an active employee assessment. To preserve historical data integrity, it cannot be modified. Please disable it and create a new scale."]);
            $this->_redirect('parating/index');
            return;
        }


        $this->view->form = $this->_prepareForm($id, false);
        $this->render('save');
    }

    public function saveAction()
    {
        $form = new Default_Form_Parating();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $dataType = trim($request->getParam('data_type'));
                $uiWidget = trim($request->getParam('ui_widget'));

                $optionsPayload = json_decode($request->getParam('options_payload', '[]'), true);
                if (!is_array($optionsPayload)) $optionsPayload = [];

                if ($dataType === 'Structured' && empty($optionsPayload)) {
                    $this->_helper->json(['options_payload' => 'Structured scales require at least one rating option.']);
                    return;
                }

                // SLIDER CONSTRAINT (Exactly 2 Options)
                if ($uiWidget === 'Slider' && count($optionsPayload) !== 2) {
                    $this->_helper->json(['error' => 'Slider widgets MUST have exactly two Rating Options defined: a Minimum bound and a Maximum bound.']);
                    return;
                }

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {

                    // Handle default and validation for step_value
                    $stepValue = null;
                    if ($uiWidget === 'Slider') {
                        $rawStep = $request->getParam('step_value');
                        // Use default 0.5 if empty or invalid
                        $stepValue = (is_numeric($rawStep) && (float)$rawStep > 0) ? (float)$rawStep : 0.50;
                    }

                    $data = [
                        'code'        => trim($request->getParam('code')),
                        'name'        => trim($request->getParam('name')),
                        'data_type'   => $dataType,
                        'ui_widget'   => $uiWidget,
                        'step_value'  => $stepValue,
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive', 1)
                    ];

                    $actionFlag = ($id > 0) ? 2 : 1;
                    $ratingId = $this->_model->saveRecord($data, $id);

                    // Sync Child Options (Merged Form handling)
                    $this->_model->syncOptions($ratingId, $dataType, $optionsPayload);

                    // 410 = Rating Scales Menu ID
                    $menuId = defined('RATING_SCALES') ? RATING_SCALES : 410;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $ratingId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(['success' => "Rating Scale $msg successfully."]);
                    $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes']);
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("PaRating Save Error: " . $e->getMessage());
                    $this->_helper->json(['result' => 'exception', 'message' => 'System error occurred while saving the record.']);
                }
            } else {
                $msgarray = [];
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $this->_helper->json($msgarray);
            }
        }
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS (Alphabetical)
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                $this->_model->deleteRecord($id);
                $menuId = defined('RATING_SCALES') ? RATING_SCALES : 410;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);
                $this->_helper->json(['msgtype' => 'success', 'message' => 'Scale deleted successfully.']);
            } catch (\Throwable $e) {
                $this->_helper->json(['msgtype' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            $this->_helper->json(['msgtype' => 'error', 'message' => 'Invalid Request.']);
        }
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');

            $this->_model->toggleStatus($id, $status);

            $menuId = defined('RATING_SCALES') ? RATING_SCALES : 410;
            sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);
            $this->_helper->json(['result' => 'yes']);
        } catch (\Throwable $e) {
            $this->_helper->json(['result' => 'no']);
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX & INTERNAL HELPERS (Alphabetical)
     * ================================================================================= */

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Parating();

        // Default empty array for UI
        $this->view->childOptions = [];

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');

                if ($data['data_type'] === 'Structured') {
                    $optionModel = new Default_Model_Paratingoption();
                    $this->view->childOptions = $optionModel->getOptionsByRatingId($id, true);
                }
            }
        }

        // SSOT V.7
        if ($readonly) {
            foreach ($form->getElements() as $el) $el->setAttrib('disabled', 'disabled');
            $form->removeElement('submit');
        }

        return $form;
    }
}
