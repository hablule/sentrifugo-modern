<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Rulebook Controller
 * 
 * Orchestrates the "One-Shot" save pattern for the Merged Form UI.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_ParulebookController extends Zend_Controller_Action
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
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Parulebook();

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
            if ($this->getRequest()->isXmlHttpRequest()) die(Zend_Json::encode(array('login' => 'failed')));
            else $this->_redirect('');
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
            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) {
            error_log("PaRulebook Index Error: " . $e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'parulebook';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, false);
        $this->render('save');
    }

    /**
     * The Master "One-Shot" Save Method
     * Extracts header from Form, extracts mappings from JSON payloads.
     */
    public function saveAction()
    {
        $form = new Default_Form_Parulebook();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V.4

                try {
                    // 1. Prepare Header Data
                    $data = array(
                        'name'         => trim($request->getParam('name')),
                        'code'         => trim($request->getParam('code')),
                        'frequency'    => trim($request->getParam('frequency')),
                        'trigger_type' => trim($request->getParam('trigger_type')),
                        'is_mandatory' => (int)$request->getParam('is_mandatory'),
                        'description'  => trim($request->getParam('description')),
                        'isactive'     => (int)$request->getParam('isactive', 1)
                    );

                    // 2. Save Header
                    $actionFlag = ($id > 0) ? 2 : 1;
                    $rulebookId = $this->_model->saveRecord($data, $id);

                    // 3. Extract & Decode JSON Payloads
                    $targets    = json_decode((string)$request->getParam('targets_payload'), true);
                    $evaluators = json_decode((string)$request->getParam('evaluators_payload'), true);
                    $questions  = json_decode((string)$request->getParam('questions_payload'), true);

                    // Sanity check arrays
                    if (!is_array($targets)) $targets = array();
                    if (!is_array($evaluators)) $evaluators = array();
                    if (!is_array($questions)) $questions = array();

                    // 4. Execute Group 6 Junction Sync
                    $this->_model->syncRulebookMappings($rulebookId, $targets, $evaluators, $questions);

                    // 5. Audit Log (398 = Rulebooks Menu ID)
                    $menuId = defined('RULEBOOKS') ? RULEBOOKS : 398;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $rulebookId);

                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(array('success' => "Rulebook $msg successfully."));
                    $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("PaRulebook Save Error: " . $e->getMessage());
                    $this->_helper->json(array('result' => 'exception', 'message' => 'System error occurred while saving the rulebook.'));
                }
            } else {
                $msgarray = array();
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $this->_helper->json($msgarray);
            }
        }
    }

    // addpopupAction is intentionally omitted. A Rulebook is too complex for an iframe modal.

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                $this->_model->deleteRecord($id);
                $menuId = defined('RULEBOOKS') ? RULEBOOKS : 398;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);
                $this->_helper->json(array('msgtype' => 'success', 'message' => 'Rulebook deleted successfully.'));
            } catch (\Throwable $e) {
                $this->_helper->json(array('msgtype' => 'error', 'message' => $e->getMessage()));
            }
        } else {
            $this->_helper->json(array('msgtype' => 'error', 'message' => 'Invalid Request.'));
        }
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');
            $this->_model->toggleStatus($id, $status);

            $menuId = defined('RULEBOOKS') ? RULEBOOKS : 398;
            sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);
            $this->_helper->json(array('result' => 'yes'));
        } catch (\Throwable $e) {
            $this->_helper->json(array('result' => 'no'));
        }
    }

    /* =================================================================================
     * GROUP 5: INTERNAL HELPERS
     * ================================================================================= */

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Parulebook();
        $this->_fetchDropdownOptionsForView(); // Supplies data for the HTML Tabs

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);
                if (!$readonly) {
                    // With standard Submit element, this will set the button text
                    $form->getElement('submit')->setLabel('Update');
                }

                // Pass existing mapping data to view as JSON for JS hydration
                $mappings = $this->_model->getExistingMappings($id);
                $this->view->existingTargets    = json_encode($mappings['targets']);
                $this->view->existingEvaluators = json_encode($mappings['evaluators']);
                $this->view->existingQuestions  = json_encode($mappings['questions']);
            }
        } else {
            // Default empty states for new records
            $this->view->existingTargets = '[]';
            $this->view->existingEvaluators = '[]';
            $this->view->existingQuestions = '[]';
        }

        if ($readonly) {
            foreach ($form->getElements() as $el) $el->setAttrib('disabled', 'disabled');
            $form->removeElement('submit');
        }

        return $form;
    }

    /**
     * Dynamically fetches real data for ALL Polymorphic Target Dropdowns
     */
    private function _fetchDropdownOptionsForView()
    {
        // 1. Target Types (Keys MUST match the DB ENUM exactly)
        $this->view->targetTypes = array(
            'Company'      => 'Company',
            'BusinessUnit' => 'Business Unit',
            'Division'     => 'Division',
            'Department'   => 'Department',
            'Section'      => 'Section',
            'Project'      => 'Project',
            'Team'         => 'Team',
            'Location'     => 'Location',
            'Role'         => 'Role',
            'Employee'     => 'Specific Employee',
            'All'          => 'All Employees (Company-Wide)'
        );

        // 2. Fetch Actual Data Arrays for Target ID Selects
        $this->view->targetData = array();

        $this->view->targetData['Company'] = (new Default_Model_Company())->getPairs(true);

        $this->view->targetData['BusinessUnit'] = (new Default_Model_Businessunit())->getPairs(true);

        $this->view->targetData['Division'] = (new Default_Model_Division())->getPairs(true);

        $this->view->targetData['Department'] = (new Default_Model_Department())->getPairs(true);

        $this->view->targetData['Section'] = (new Default_Model_Section())->getPairs(true);

        $this->view->targetData['Project'] = (new Default_Model_Project())->getPairs(true);

        $this->view->targetData['Team'] = (new Default_Model_Team())->getPairs(true);

        $this->view->targetData['Location'] = (new Default_Model_Location())->getPairs(true);

        $this->view->targetData['Role'] = (new Default_Model_Roles())->getPairs(true);

        $this->view->targetData['Employee'] = (new Default_Model_Employee())->getPairs(true);


        // 3. Evaluator Types
        $this->view->evaluatorTypes = array(
            'Self' => 'Self',
            'Manager' => 'Manager',
            'Peer' => 'Peer',
            'Subordinate' => 'Subordinate',
            'HR' => 'HR Rep'
        );

        // 4. Question Pool
        $questionModel = new Default_Model_Paquestion();
        $this->view->questionOptions = $questionModel->getQuestionOptionsWithMetaData(true);
    }
}
