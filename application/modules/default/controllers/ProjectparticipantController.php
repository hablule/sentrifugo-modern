<?php

/**
 * Project Participant Controller
 *
 * Manages mapping between Projects and participants (Poly-Targets).
 * Complies with SSOT v6.5 (Server-side validation, Raw Array JSON).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_ProjectparticipantController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Projectparticipant();
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = (int)$user->id;
            $this->_loginuserRole  = (int)$user->emprole;
            $this->_loginuserGroup = (int)$user->group_id;

            // SSOT v6.5 Mandatory View Assignments
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

            // SSOT v6.5: Default sort human-readable
            $sort    = (string) $request->getParam('sort', 'ASC');
            $by      = (string) $request->getParam('by', 'project_name');

            $perPage = ($dashboardcall === 'Yes')
                ? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
                : (int) $request->getParam('per_page', PERPAGE);

            if ($refresh === 'refresh') {
                $sort = 'ASC';
                $by = 'project_name';
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
        }

        $this->view->controllername = 'projectparticipant';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        if (sapp_Global::_checkprivileges(PROJECTCO, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        if (sapp_Global::_checkprivileges(PROJECTCO, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
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
            return $this->_redirect('projectparticipant/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {

            $projectId = $request->getParam('project_id_hidden') ?: $request->getParam('project_id');

            // --- SSOT v6.5 SERVER-SIDE HIERARCHY VALIDATION ("Only One Target") ---
            $pEmployee = $request->getParam('employee_id');
            $pCompany  = $request->getParam('company_id');
            $pSbu      = $request->getParam('sbunit_id');
            $pDiv      = $request->getParam('division_id');
            $pDept     = $request->getParam('department_id');
            $pSec      = $request->getParam('section_id');
            $pTeam     = $request->getParam('team_id');

            $participantCount = 0;
            $activeTargetColumn = '';
            $activeTargetValue = 0;

            $targets = [
                'employee_id' => $pEmployee,
                'company_id' => $pCompany,
                'sbunit_id' => $pSbu,
                'division_id' => $pDiv,
                'department_id' => $pDept,
                'section_id' => $pSec,
                'team_id' => $pTeam
            ];

            foreach ($targets as $col => $val) {
                if (!empty($val)) {
                    $participantCount++;
                    $activeTargetColumn = $col;
                    $activeTargetValue = (int)$val;
                }
            }

            if ($participantCount !== 1) {
                $errorMsg = ($participantCount > 1)
                    ? "A mapping can only have ONE participant. Please clear extra selections."
                    : "You must select exactly ONE participant (Employee or Organizational Unit) for this project mapping.";

                $form->getElement('employee_id')->addError($errorMsg);
                echo Zend_Json::encode($form->getMessages());
                exit;
            }

            // 2. Uniqueness validation: Prevent mapping the exact same target to the same project twice
            if ($this->_model->checkMappingDuplicate($projectId, [$activeTargetColumn => $activeTargetValue], $id)) {
                $form->getElement($activeTargetColumn)->addError("This participant is already mapped to this project.");
                echo Zend_Json::encode($form->getMessages());
                exit;
            }
            // --- END VALIDATION ---

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                $data = [
                    'project_id'    => (int)$projectId,
                    'employee_id'   => $pEmployee ?: null,
                    'company_id'    => $pCompany ?: null,
                    'sbunit_id'     => $pSbu ?: null,
                    'division_id'   => $pDiv ?: null,
                    'department_id' => $pDept ?: null,
                    'section_id'    => $pSec ?: null,
                    'team_id'       => $pTeam ?: null,
                    'description'   => trim((string) $request->getParam('description', '')),
                    'isactive'      => (int) $request->getParam('isactive', 1)
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

                // Logging context is the Project
                sapp_Global::logManager(PROJECTCO, $actionFlag, $this->_loginUserId, $projectId);
                $db->commit();

                // FlashMessenger for standard redirect
                $this->_helper->flashMessenger->addMessage(['success' => "Project Participant {$saveMode} successfully."]);

                // SSOT v6.5 Strict AJAX JSON Response (Success with Redirect)
                $this->_helper->json([
                    'result'    => 'saved',
                    'nomessage' => 'yes'
                ]);
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("ProjectParticipant Save Error: " . $e->getMessage());

                $this->_helper->json([
                    'result'  => 'exception',
                    'message' => 'System error occurred. Check logs.',
                    'msgtype' => 'error'
                ]);
            }
        } else {
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
            if (sapp_Global::_checkprivileges(PROJECTCO, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
                return $this->_helper->json(['result' => 'error', 'message' => 'Permission Denied.']);
            }

            try {
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("Record is in use and cannot be deleted.");
                }

                $record = $this->_model->getRecordById($id);
                $this->_model->deleteRecord($id);
                sapp_Global::logManager(PROJECTCO, 3, $this->_loginUserId, $record['project_id']);

                return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success']);
            } catch (\Throwable $e) {
                error_log("ProjectParticipant Delete Error: " . $e->getMessage());
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

            $record = $this->_model->getRecordById($id);
            $this->_model->toggleStatus($id, $status);
            sapp_Global::logManager(PROJECTCO, 4, $this->_loginUserId, $record['project_id']);

            $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.']);
        } catch (\Throwable $e) {
            error_log("ProjectParticipant Toggle Error: " . $e->getMessage());
            $this->_helper->json(['result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.']);
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $prev_cntrl = (string) $this->getRequest()->getParam('prev_cntrl', '');

        $form = $this->_prepareForm();

        $form->setAction(BASE_URL . 'projectparticipant/savepopup/prev_cntrl/' . $prev_cntrl);
        $form->setAttrib('id', 'popupformid');

        $this->view->form = $form;
        $this->view->prev_cntrl = $prev_cntrl;
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Projectparticipant();

        // 1. Populate Dropdowns natively
        $projModel = new Default_Model_Project();
        $form->project_id->addMultiOption('', 'Select Project');
        $form->project_id->addMultiOptions($projModel->getPairs());

        // Target Poly-Mappers
        $empModel = new Default_Model_Employee();
        $form->employee_id->addMultiOption('', 'Select Employee');
        $form->employee_id->addMultiOptions($empModel->getPairs());

        $compModel = new Default_Model_Company();
        $form->company_id->addMultiOption('', 'Select Company');
        $form->company_id->addMultiOptions($compModel->getPairs());

        $sbuModel = new Default_Model_Businessunit();
        $form->sbunit_id->addMultiOption('', 'Select Business Unit');
        $form->sbunit_id->addMultiOptions($sbuModel->getPairs());

        $divModel = new Default_Model_Division();
        $form->division_id->addMultiOption('', 'Select Division');
        $form->division_id->addMultiOptions($divModel->getPairs());

        $deptModel = new Default_Model_Department();
        $form->department_id->addMultiOption('', 'Select Department');
        $form->department_id->addMultiOptions($deptModel->getPairs());

        $secModel = new Default_Model_Section();
        $form->section_id->addMultiOption('', 'Select Section');
        $form->section_id->addMultiOptions($secModel->getPairs());

        $teamModel = new Default_Model_Team();
        $form->team_id->addMultiOption('', 'Select Team');
        $form->team_id->addMultiOptions($teamModel->getPairs());

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }

            // Sync hidden override for disabled primary project_id
            if ($form->getElement('project_id_hidden')) {
                $form->getElement('project_id_hidden')->setValue($data['project_id']);
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
