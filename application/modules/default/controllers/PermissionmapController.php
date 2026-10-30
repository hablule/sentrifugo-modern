<?php

/**
 * Action Permission Map Controller
 * 
 * Manages the grid view, editing, and viewing of specific permission assignments.
 * Refactored for SSOT v4.5: Delegates grid logic to Model, enforces transactions, 
 * standardizes JSON responses, and implements audit logging.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_PermissionmapController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_ActionPermission();

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
            if ($this->getRequest()->isXmlHttpRequest()) {
                die(Zend_Json::encode(array('login' => 'failed')));
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);

            // FIX 1: Enforce string type to prevent PHP 8 rtrim() Fatal TypeError
            $searchData    = (string) $request->getParam('searchData', '');

            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'module_name');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'DESC';
                $by = 'id';
                $page = 1;
                $searchData = '';
            }

            // Method call now perfectly safe
            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) { // FIX 2: Catch \Throwable to trap PHP 8 Fatal Errors
            error_log("PermissionMap Index Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            if (APPLICATION_ENV == 'development') {
                Zend_Debug::dump($e->getMessage());
            }
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'permissionmap';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, false);
        $this->view->id = $id;
        $this->render('save');
    }

    public function saveAction()
    {
        $form = new Default_Form_Permissionmap();
        $request = $this->getRequest();
        $response = array('result' => 'error', 'message' => 'Invalid data');

        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V: Transactions Mandatory

                try {
                    $data = array(
                        'permission' => trim($request->getParam('permission')),
                        'description' => trim($request->getParam('description')),
                        'isactive'   => (int)$request->getParam('isactive')
                    );

                    // 1. Save mapping
                    $this->_model->saveRecord($data, $id);

                    // 2. Re-generate the ACL cache
                    $this->_model->generateAclCacheFile();

                    // 3. Log the modification (SSOT V: ActionFlag 2 = Edit)
                    $menuId = defined('SYSTEM_CONFIG_MENU_ID') ? SYSTEM_CONFIG_MENU_ID : 377;
                    sapp_Global::logManager($menuId, 2, $this->_loginUserId, $id);

                    $db->commit();

                    // SSOT X.2: FlashMessenger for Success across redirects
                    $this->_helper->flashMessenger->addMessage(array('success' => 'Permission mapping updated successfully.'));

                    // SSOT X.2: Strict JSON Response Contract
                    $response = array(
                        'result'    => 'saved',
                        'nomessage' => 'no',
                        'message'   => 'Permission mapping updated successfully.',
                        'msgtype'   => 'success'
                    );
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Sentrifugo Mapping Save Error: " . $e->getMessage());
                    $response = array(
                        'result'  => 'exception',
                        'message' => 'System error occurred while updating ACL mapping.',
                        'msgtype' => 'error'
                    );
                }
            } else {
                // SSOT X.3: Flatten Form Validation Errors
                $msgarray = array();
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $response = array('result' => 'error', 'message' => $msgarray);
            }
        }
        $this->_helper->json($response);
    }

    /* =================================================================================
     * GROUP 5: AJAX & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Initializes form, sets defaults, and locks fields for read-only view.
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Permissionmap();

        if ($id) {
            // Delegate complex JOIN fetch to the Model
            $data = $this->_model->getDetailedRecordById($id);

            if ($data) {
                $form->populate($data);

                // Populate custom visual context fields
                $form->getElement('display_module')->setValue($data['module_name']);
                $form->getElement('display_controller')->setValue($data['controller']);
                $form->getElement('display_action')->setValue($data['action_name']);

                $form->getElement('submit')->setLabel('Update');
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
