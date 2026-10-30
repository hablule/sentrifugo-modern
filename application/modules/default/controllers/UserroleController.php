<?php

/**
 * User Role Controller
 * Controller for User Role Management
 * Handles role assignments to users in Sentrifugo 3.2
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 **/
class Default_UserroleController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    protected $_options;

    /* --------------------------------------------------------------
     * Pre-dispatch (Ajax contexts)
     * -------------------------------------------------------------- */
    public function preDispatch()
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('makeactiveinactive', 'json')->initContext();
        $ajaxContext->addActionContext('delete', 'json')->initContext();
        $ajaxContext->addActionContext('getmoreuserroles', 'html')->initContext();
    }

    /* --------------------------------------------------------------
     * Init
     * -------------------------------------------------------------- */
    public function init()
    {
        $this->_model   = new Default_Model_Userrole();
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();

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

    /* --------------------------------------------------------------
     * Index (List View)
     * -------------------------------------------------------------- */

    public function indexAction()
    {
        try {
            // 1. Initialize session for view preference (Card vs Grid)
            $session = new Zend_Session_Namespace('userrole_view_pref');
            $viewType = $this->_getParam('view_type', ($session->view_type ?: 'card'));
            $session->view_type = $viewType;
            $this->view->viewType = $viewType;

            // 2. Handle AJAX Layout and Parameters
            $call = $this->_getParam('call');
            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $refresh = $this->_getParam('refresh');
            $dashboardcall = $this->_getParam('dashboardcall');

            // 3. Grid Pagination and Sorting Logic
            if ($refresh == 'refresh') {
                $sort = 'ASC';
                $by = 'userfullname'; // Standard sorting for new records
                $pageNo = 1;
                $searchData = '';
                $perPage = ($dashboardcall == 'Yes') ? DASHBOARD_PERPAGE : PERPAGE;
            } else {
                $sort = $this->_getParam('sort', 'ASC');
                $by = $this->_getParam('by', 'userfullname');
                $pageNo = $this->_getParam('page', 1);

                if ($dashboardcall == 'Yes') {
                    $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
                } else {
                    $perPage = $this->_getParam('per_page', PERPAGE);
                }

                $searchData = $this->_getParam('searchData');
            }

            // 4. Permission Check (Sentrifugo Pattern)
            $popConfigPermission = [];
            if (sapp_Global::_checkprivileges(USERROLE_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'add') == 'Yes') {
                array_push($popConfigPermission, 'userrole');
            }
            $this->view->popConfigPermission = $popConfigPermission;

            // 5. Fetch Data for the automated Grid Helper ($this->grid())
            // This prepares the headers, filters, and paginated row content
            $data = [];
            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);
            array_push($data, $dataTmp);

            $this->view->dataArray = $data; // Accessed in view as $this->dataArray[0]

            // 6. Fetch Data for the Manual Card View (If Active)
            // Note: We only fetch full card data if not in a grid AJAX call to save memory
            if ($viewType == 'card' && $call != 'ajaxcall') {
                $limit = LIMIT;
                $offset = 0;

                $search_val = $this->_getParam('search_val', null);
                $search_str = $this->_getParam('search_str', null);

                // Fetch initial batch for Card View
                $this->view->userroles = $this->_model->getAllUserRoles($limit, $offset, $search_val, $search_str);
                $totalRecords = $this->_model->getAllUserRoles('', '', $search_val, $search_str);

                $totalcount = count($totalRecords);
                $currentCount = count($this->view->userroles);

                $this->view->totalcount = $totalcount;
                $this->view->limit = $limit;
                $this->view->offset = $offset + $limit;
                $this->view->remainingcount = $totalcount - $currentCount;
            }

            $this->view->call = $call;
            $this->view->messages = $this->_helper->flashMessenger->getMessages();
        } catch (Exception $e) {
            // Error handling matching Sentrifugo standards
            $this->view->rowexist = "norows";
            if (APPLICATION_ENV == 'development') {
                Zend_Debug::dump($e->getMessage());
            }
        }
    }

    /* --------------------------------------------------------------
     * Add Action (Entry point)
     * -------------------------------------------------------------- */
    public function addAction()
    {
        $this->_checkPermission('add');

        $form = $this->_prepareForm();

        $this->view->form = $form;
        $this->view->id   = 0;
        $this->render('save');
    }

    /* --------------------------------------------------------------
     * Edit Action (Entry point)
     * -------------------------------------------------------------- */
    public function editAction()
    {
        $this->_checkPermission('edit');

        $id = (int)$this->_getParam('id');

        $form = $this->_prepareForm($id);

        $this->view->form = $form;
        $this->view->userrole_id = $id;
        $this->view->id = $id;
        $this->render('save');
    }

    /* --------------------------------------------------------------
     * Save Action (The POST Processor)
     * -------------------------------------------------------------- */
    public function saveAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->_redirect('userrole/index');
        }

        $id   = (int)$this->_getParam('id');
        $form = $this->_prepareForm($id);
        $msgarray = [];

        if ($form->isValid($this->_request->getPost())) {
            $params = $this->_request->getPost();

            // --- NEW: COMPOSITE VALIDATION CHECK ---
            $existing = $this->_model->checkUserRoleCombination($params['user_id'], $params['role_id'], $id);
            if ($existing) {
                // Assign the error to the user_id field so it highlights in red on the UI
                $msgarray['user_id'] = "This user is already assigned to this specific role.";
                $msgarray['role_id'] = "This role is already assigned to this specific user.";
            } else {
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $data = $this->_mapPostData($params);

                    if ($id > 0) {
                        $this->_model->saveRecord($data, $id);
                        $logAction = 2; // Update
                        $message   = 'Role assignment updated successfully.';
                    } else {
                        $data['createdby']   = $this->_loginUserId;
                        $data['createddate'] = gmdate('Y-m-d H:i:s');
                        $id = $this->_model->saveRecord($data);
                        $logAction = 1; // Add
                        $message   = 'Role assigned successfully.';
                    }

                    sapp_Global::logManager(USERROLE_MENU_ID, $logAction, $this->_loginUserId, $id);
                    $db->commit();

                    $this->_helper->flashMessenger->addMessage(['success' => $message]);
                    return $this->_redirect('userrole/index');
                } catch (Exception $e) {
                    $db->rollBack();
                    $msgarray['error'] = $e->getMessage();
                }
            }
        } else {
            $messages = $form->getMessages();
            $msgarray = [];

            // Flatten the nested array into simple strings
            foreach ($messages as $field => $errors) {
                // This takes the first error message for each field
                $msgarray[$field] = current($errors);
            }

            $this->view->msgarray = $msgarray;
        }

        // If validation fails, re-render the save view
        $this->view->form     = $form;
        $this->view->msgarray = $msgarray;
        $this->view->id       = $id;
        $this->render('save');
    }

    /* --------------------------------------------------------------
     * View Action (Read-Only)
     * -------------------------------------------------------------- */
    public function viewAction()
    {
        $this->_checkPermission('view');

        $id = (int)$this->_getParam('id');
        $record = $this->_model->getRecordById($id);

        if (!$record) {
            $this->view->rowexist = "norows";
        } else {
            $form = $this->_prepareForm();
            $record['datefrom'] = sapp_Global::change_date($record['datefrom'], 'view');
            $record['dateto']   = (!empty($record['dateto'])) ? sapp_Global::change_date($record['dateto'], 'view') : '';

            $form->populate($record);

            // Disable all elements for view mode
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');

            $this->view->form = $form;
            $this->view->id = $id;
            $this->view->rowexist = "rows";
        }
        $this->view->controllername = 'userrole';
    }

    /* --------------------------------------------------------------
     * Delete (AJAX Soft-Delete)
     * -------------------------------------------------------------- */
    public function deleteAction()
    {
        $id = (int)$this->_getParam('objid');
        $response = ['msgtype' => 'error', 'message' => 'Invalid request'];

        if ($id) {
            try {
                $this->_model->saveRecord([
                    'isactive'      => '5', // Sentrifugo delete status
                    'modifiedby'    => $this->_loginUserId,
                    'modifieddate'  => gmdate('Y-m-d H:i:s')
                ], $id);

                sapp_Global::logManager(USERROLE_MENU_ID, 3, $this->_loginUserId, $id);
                $response = ['msgtype' => 'success', 'message' => 'Assignment deleted successfully.'];
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        $this->_helper->json($response);
    }

    /* --------------------------------------------------------------
     * Toggle Active Status (AJAX)
     * -------------------------------------------------------------- */
    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->_getParam('id');
            $this->_model->saveRecord(
                ['isactive' => $this->_getParam('status')],
                $id
            );

            sapp_Global::logManager(USERROLE_MENU_ID, 4, $this->_loginUserId, $id);
            $this->_helper->json(['result' => 'yes']);
        } catch (Exception $e) {
            $this->_helper->json(['result' => 'no']);
        }
    }

    /* --------------------------------------------------------------
     * Popup Modal Action
     * -------------------------------------------------------------- */
    public function adduserrolepopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . '/layouts/scripts/popup/');

        $uid  = $this->_getParam('uid');
        $form = $this->_prepareForm();
        $msgarray = [];

        $form->setAction(BASE_URL . 'userrole/adduserrolepopup/uid/' . $uid);

        if ($uid) {
            $form->user_id->setValue($uid)->setAttrib('disabled', 'disabled');
        }

        if ($this->getRequest()->isPost()) {
            $post = $this->_request->getPost();
            if ($uid) $post['user_id'] = $uid;

            if ($form->isValid($post)) {
                if ($this->_model->checkUserExists($post['user_id'])) {
                    $msgarray['user_id'] = 'This user already has a role assigned.';
                } else {
                    $db = Zend_Db_Table::getDefaultAdapter();
                    $db->beginTransaction();
                    try {
                        $data = $this->_mapPostData($form->getValues());
                        $data['createdby']   = $this->_loginUserId;
                        $data['createddate'] = gmdate('Y-m-d H:i:s');
                        $data['isactive']    = '1';

                        $id = $this->_model->saveRecord($data);
                        sapp_Global::logManager(USERROLE_MENU_ID, 1, $this->_loginUserId, $id);
                        $db->commit();

                        $this->view->eventact = 'added';
                        $this->view->popup    = 'close';
                    } catch (Exception $e) {
                        $db->rollBack();
                        $msgarray['error'] = $e->getMessage();
                    }
                }
            } else {
                foreach ($form->getMessages() as $k => $v) {
                    $msgarray[$k] = current($v);
                }
            }
        }

        $this->view->msgarray       = $msgarray;
        $this->view->userrole_form  = $form;
    }

    /* --------------------------------------------------------------
     * AJAX Helper Actions
     * -------------------------------------------------------------- */
    public function getmoreuserrolesAction()
    {
        // 1. Security & Context
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()) {
            echo "Session Expired";
            exit;
        }

        
        $loginuserRole = $auth->getStorage()->read()->emprole;
        $loginuserGroup = $auth->getStorage()->read()->group_id;

        // Replace USERROLE_MENU_ID with your actual ID
        if (sapp_Global::_checkprivileges(USERROLE_MENU_ID, $loginuserGroup, $loginuserRole, 'view') != 'Yes') {
            echo "<div class='ml-alert-1-error'>You do not have permission to view more records.</div>";
            exit;
        }


        $this->_helper->layout->disableLayout();
        
        // 2. Extract Parameters
        $limit           = $this->_getParam('limit', LIMIT);
        $offset          = $this->_getParam('offset', 0);
        $count_remaining = $this->_getParam('count_remaining', 0);
        $search_val      = $this->_getParam('search_val');
        $search_str      = $this->_getParam('search_str');
        $viewType        = $this->_getParam('view_type', 'card');

        // 3. Fetch Data batch
        $dataTmp = $this->_model->getAllUserRoles($limit, $offset, $search_val, $search_str);
        
        // 4. Update view variables for the partial
        $this->view->userroles = $dataTmp;
        $this->view->viewType  = $viewType;
        
        // Calculate the new remaining count for the JS success handler
        $itemsAdded = count($dataTmp);
        $this->view->remainingcount = $count_remaining - $itemsAdded;
        
        // 5. Render the partial fragment
        // This file contains ONLY the <div> card or <tr> row logic
        $this->render('getmoreuserroles');
    }

    public function getlatestusersAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $users = (new Default_Model_Usermanagement())->getUsersList();
        $this->_helper->json($users);
    }

    /* --------------------------------------------------------------
     * PRIVATE HELPERS
     * -------------------------------------------------------------- */

    private function _checkPermission($type)
    {
        if (sapp_Global::_checkprivileges(USERROLE_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, $type) !== 'Yes') {
            return $this->_redirect('error');
        }
    }

    /**
     * Form Factory: Initializes form, sets defaults, and locks fields for existing records.
     * 
     * @param int|null $id The record ID (if editing)
     * @return Default_Form_Userrole
     */
    private function _prepareForm($id = null)
    {
        $form = new Default_Form_Userrole();
        $userModel = new Default_Model_Usermanagement();
        $roleModel = new Default_Model_Roles();

        $util = new utlis();

        // 1. Populate Dropdowns
        // The keys are IDs (hidden from user), and the values are Names (visible to user)
        $form->user_id = $util->populate_select($form->user_id, $userModel->getUsersList(), 'id', 'name', 'Select User');
        $form->role_id = $util->populate_select($form->role_id, $roleModel->getRolesList('', false, false), 'id', 'name', 'Select Role');


        // 2. Logic for Existing Record (Modification)
        if ($id && (int)$id > 0) {
            // Fetch the existing data to set defaults
            $record = $this->_model->getRecordById($id);

            if ($record) {

                // Populate and format dates for View
                $record['datefrom'] = sapp_Global::change_date($record['datefrom'], 'view');
                if (!empty($record['dateto'])) {
                    $record['dateto'] = sapp_Global::change_date($record['dateto'], 'view');
                }

                $form->setDefaults($record);


                // Set User field to Read-Only (Disabled visually)
                $form->user_id->setAttrib('disabled', 'disabled');

                // This ensures 'user_id' is present in the POST data
                $userIdHidden = new Zend_Form_Element_Hidden('userIdHidden');

                $form->addElement($userIdHidden);

                $form->userIdHidden->setValue($record['user_id']);
                $form->userIdHidden->setName('user_id');  // Overrides the name for the POST array

            } else {
                // Record not found, redirect back to index
                $this->_helper->flashMessenger->addMessage(['error' => 'Record not found.']);
                return $this->_redirect('userrole/index');
            }

            $form->setAttrib('action', BASE_URL . 'userrole/save/id/' . $id);
            $form->submit->setLabel('Update');
        }
        // 3. Logic for New Entry
        else {
            $form->setAttrib('action', BASE_URL . 'userrole/save');
            $form->submit->setLabel('Save');
        }

        return $form;
    }

    private function _mapPostData($params)
    {
        return [
            'user_id'      => $params['user_id'],
            'role_id'      => $params['role_id'],
            'description'  => $params['description'],
            'datefrom'     => sapp_Global::change_date($params['datefrom'], 'database'),
            'dateto'       => !empty($params['dateto']) ? sapp_Global::change_date($params['dateto'], 'database') : null,
            'isactive'     => $params['isactive'] ?? '1',
            'modifiedby'   => $this->_loginUserId,
            'modifieddate' => gmdate('Y-m-d H:i:s')
        ];
    }
}
