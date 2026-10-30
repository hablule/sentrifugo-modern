<?php

/**
 * Holiday Category Controller
 *
 * Manages the lookup classifications for holidays (e.g., Religious, National).
 * Complies with SSOT v6.7.7 (PHP 7.4 strict, Raw Array JSON, Dependency Checks).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_HolidaycategoryController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Holidaycategory();
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
        }

        $this->view->controllername = 'holidaycategory';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        
        if (sapp_Global::_checkprivileges(HOLIDAY_CATEGORY, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
           $this->_redirect('error/privilege');
        }

        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        if (sapp_Global::_checkprivileges(HOLIDAY_CATEGORY, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
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
            return $this->_redirect('holidaycategory/index');
        }

        $id = (int) $request->getParam('id', 0);
        $form = $this->_prepareForm($id);

        if ($form->isValid($request->getPost())) {

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                $data = [
                    'code'        => trim((string) $request->getParam('code', '')),
                    'name'        => trim((string) $request->getParam('name', '')),
                    'description' => trim((string) $request->getParam('description', '')),
                    'isactive'    => (int) $request->getParam('isactive', 1)
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

                // Logging 
                sapp_Global::logManager(HOLIDAY_CATEGORY, $actionFlag, $this->_loginUserId, $savedId);

                $db->commit();

                // FlashMessenger for standard redirect
                $this->_helper->flashMessenger->addMessage(['success' => "Category {$saveMode} successfully."]);

                // SSOT Strict AJAX JSON Response
                $this->_helper->json(['result' => 'saved', 'nomessage' => 'yes']);
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("Holiday Category Save Error: " . $e->getMessage());

                $this->_helper->json([
                    'result'  => 'exception',
                    'message' => 'System error occurred. Check logs.',
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
            if (sapp_Global::_checkprivileges(HOLIDAY_CATEGORY, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
               return $this->_helper->json(['result' => 'error', 'message' => 'Permission Denied.']);
            }

            try {
                // Dependency check (SSOT)
                if ($this->_model->checkInUse($id)) {
                    throw new Exception("This Category is actively used in Holiday Policies and cannot be deleted.");
                }

                $this->_model->deleteRecord($id);
                sapp_Global::logManager(HOLIDAY_CATEGORY, 3, $this->_loginUserId, $id);

                return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success']);
            } catch (\Throwable $e) {
                error_log("Holiday Category Delete Error: " . $e->getMessage());
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
            sapp_Global::logManager(HOLIDAY_CATEGORY, 4, $this->_loginUserId, $id);

            $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.']);
        } catch (\Throwable $e) {
            error_log("Holiday Category Toggle Error: " . $e->getMessage());
            $this->_helper->json(['result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.']);
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function addpopupAction()
    {
        // SSOT v6.7.7: Proper Layout Switch for Iframes
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $prev_cntrl = (string) $this->getRequest()->getParam('prev_cntrl', '');

        $form = $this->_prepareForm();

        $form->setAction(BASE_URL . 'holidaycategory/savepopup/prev_cntrl/' . $prev_cntrl);
        $form->setAttrib('id', 'popupformid');

        // Allow standard POST submission for simple popup
        $form->getElement('submit')->setAttrib('onclick', null);

        $this->view->form = $form;
        $this->view->prev_cntrl = $prev_cntrl;
    }

    public function savepopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_redirect('holidaycategory/addpopup');
        }

        $form = $this->_prepareForm();
        $msgarray = [];

        $this->view->popup = '';
        $this->view->prev_cntrl = (string) $request->getParam('prev_cntrl', '');

        if ($form->isValid($request->getPost())) {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                $data = [
                    'code'        => trim((string) $request->getParam('code', '')),
                    'name'        => trim((string) $request->getParam('name', '')),
                    'description' => trim((string) $request->getParam('description', '')),
                    'isactive'    => (int) $request->getParam('isactive', 1)
                ];

                $newId = $this->_model->saveRecord($data);

                sapp_Global::logManager(HOLIDAY_CATEGORY, 1, $this->_loginUserId, $newId);
                $db->commit();

                // Prepare HTML options for parent dropdown
                $activeRecords = $this->_model->getPairs(true);
                $optionsHtml = '';
                foreach ($activeRecords as $valId => $valName) {
                    $optionsHtml .= sapp_Global::selectOptionBuilder($valId, $valName);
                }

                $this->view->optionsHtml = $optionsHtml;
                $this->view->newInsertedId = $newId;
                $this->view->eventact    = 'added';
                $this->view->popup       = 'close';
            } catch (\Throwable $dbError) {
                $db->rollBack();
                $msgarray['error'] = 'Database Error: ' . $dbError->getMessage();
            }
        } else {
            foreach ($form->getMessages() as $field => $errors) {
                $msgarray[$field] = current($errors);
            }
        }

        $this->view->msgarray = $msgarray;
        $this->view->form = $form;

        $this->render('addpopup');
    }

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Holidaycategory();

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if (!$data) return null;

            // XSS Prevention / Sanitation
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
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
