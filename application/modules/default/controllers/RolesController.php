<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/


/**
 * Sentrifugo - Open Source HRMS
 *
 * @category   Sentrifugo
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @modified  Sentrifugo v3.2
 * @date	  June 2024
 */

class Default_RolesController extends Zend_Controller_Action
{
    /* --------------------------------------------------------------
     * Attributes
     * -------------------------------------------------------------- */

    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    /** @var array Bootstrap options */
    private $_options;

    /* --------------------------------------------------------------
     * Functional Group 1: Application Lifecycle & Initialization
     * -------------------------------------------------------------- */

    /**
     * Bootstrap initialization
     */
    public function init()
    {
        $this->_model = new Default_Model_Roles();
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

        require_once 'application/modules/default/library/utilClasses/Util.php';
    }

    /**
     * Access control and Ajax context setup
     */
    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                die(Zend_Json::encode(['login' => 'failed']));
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getgroupmenu', 'html')->initContext();
        $ajaxContext->addActionContext('save', 'json')->initContext();
    }

    /* --------------------------------------------------------------
     * Functional Group 2: Data Display & Retrieval
     * -------------------------------------------------------------- */

    /**
     * List view for roles with search and pagination
     */
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
            $searchData    = $request->getParam('searchData');

            $sort    = $request->getParam('sort', 'DESC');
            $by      = $request->getParam('by', 'name');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort       = 'DESC';
                $by         = 'name';
                $page       = 1;
                $searchData = '';
            }

            // Fetch Grid Data
            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (Exception $e) {
            Zend_Debug::dump($e->getMessage());
        }
    }

    /**
     * View action for read-only role details
     */
    public function viewAction()
    {
        try {

            $id = abs((int)$this->_getParam('id'));

            if ($this->_getParam('call') == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $form = $this->_prepareForm($id, true);

            if (!$form) {
                $this->view->nodata = "nodata";
            }            

            $this->view->MenuArray  = $this->getrolemenuArray($id, $form->getValue('group_id'));
            $this->view->form       = $form;
            $this->view->id         = $id;
        } catch (Exception $e) {
            Zend_Debug::dump($e->getMessage());
        }
    }

    /* --------------------------------------------------------------
     * Functional Group 3: Modification & Persistence
     * -------------------------------------------------------------- */

    /**
     * Action to add a Role via a popup modal (Iframe)
     */
    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

        $auth = Zend_Auth::getInstance();
        $loginUserId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : null;

        $form = new Default_Form_Roles();
        $rolesModel = new Default_Model_Roles();
        $groupsModel = new Default_Model_Groups();
        $msgarray = [];

        // Populate groups
        $form->group_id->addMultiOptions(['' => 'Select Group'] + $groupsModel->getActiveGroupsForSelect());

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                try {
                    $data = [
                        'name'         => $this->_getParam('name'),
                        'code'         => trim($this->_getParam('code')),
                        'description'  => trim($this->_getParam('description')),
                        'group_id'     => $this->_getParam('group_id'),
                        'levelid'      => $this->_getParam('levelid'),
                        'createdby'    => $loginUserId,
                        'createddate'  => gmdate("Y-m-d H:i:s"),
                        'isactive'     => 'Y'
                    ];

                    $newId = $rolesModel->SaveorUpdateRolesData($data);

                    // Generate updated options for parent window refresh
                    $allRoles = $rolesModel->fetchAll("isactive = 'Y'", "name")->toArray();
                    $optionsHtml = '';
                    foreach ($allRoles as $record) {
                        $optionsHtml .= sapp_Global::selectOptionBuilder($record['id'], $record['name']);
                    }

                    $this->view->roleOptionsHtml = $optionsHtml;
                    $this->view->newRoleId      = $newId; // Pass back for auto-selection
                    $this->view->eventact       = 'added';
                    $this->view->popup          = 'close';
                } catch (Exception $e) {
                    $msgarray['error'] = $e->getMessage();
                }
            } else {
                $msgarray = $form->getMessages();
            }
        }

        $this->view->msgarray       = $msgarray;
        $this->view->form           = $form;
        $this->view->controllername = 'roles';
    }

    /**
     * Displays form for adding/editing roles
     */
    public function editAction()
    {
        try {

            $id = abs((int)$this->_getParam('id'));

            if ($this->_getParam('call') == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $form = $this->_prepareForm($id, false);

            if (!$form) {
                $this->view->nodata = "nodata";
            }            

            $this->view->MenuArray  = $this->getrolemenuArray($id, $form->getValue('group_id'));
            $this->view->form       = $form;
            $this->view->id         = $id;
        } catch (Exception $e) {
            Zend_Debug::dump($e->getMessage());
        }
    }

    /**
     * AJAX Action: Returns HTML for the Menu Privileges Accordion
     * Used when changing Group dropdown or loading Edit page.
     */
    public function getrolemenuAction()
    {
        // 1. Disable Layout (We only want the HTML fragment)
        $this->_helper->layout->disableLayout();
        
        $request = $this->getRequest();
        $groupId = (int)$request->getParam('group_id');
        $roleId  = (int)$request->getParam('role_id');
        
       
        // 4. Pass to View
        $this->view->menuArray = $this->getrolemenuArray($roleId, $groupId);
    }

    /**
     * Helper method to fetch and build the menu tree with privileges for a given role and group
     * @param int $roleId
     * @param int $groupId
     * @return array Nested menu structure with privilege data
     */
    public function getrolemenuArray($roleId, $groupId)
    {
        $menu_model = new Default_Model_Menu();
        $menuArray = $menu_model->getMenuPrivilegesByRole($roleId, $groupId);
        $menuTree = $menu_model->buildMenuTree($menuArray);
       
        return $menuTree;
    }

    /**
     * Handles the complex saving of role data and menu privileges
     */
    public function saveAction()
    {
        $form = new Default_Form_Roles();
        $request = $this->getRequest(); // Keep this as the Request Object
        $response = ['result' => 'error', 'message' => 'Invalid data'];

        if ($request->isPost()) {

            // 1. Get POST data into a separate array variable
            $postData = $request->getPost();

            // 2. Extract and Decode Permissions
            $jsonRaw = $postData['permissions_json'] ?? '';
            $permissions = json_decode($jsonRaw, true);

            // Remove from validation check (it's handled manually)
            unset($postData['permissions_json']);

            // 3. Validate the Array, not the Object
            if ($form->isValid($postData)) {

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    // 4. Use $request object safely here because we didn't overwrite it
                    $id = (int)$request->getParam('id');
                    $util = new utlis();

                    // Prepare Group Data using $postData or $request params
                    $data = [
                        'name'         => trim($request->getParam('name')),
                        'code'         => trim($request->getParam('code')),
                        'levelid'      => $request->getParam('levelid'),
                        'group_id'     => $request->getParam('group_id'),
                        'description'  => trim($request->getParam('description')),
                        'isactive'     => $request->getParam('isactive'),
                        'modifiedby'   => $this->_loginUserId,
                        'modifieddate' => gmdate("Y-m-d H:i:s")
                    ];

                    $actionFlag = 2; // Edit
                    $saveMode   = 'Updated';

                    if (!$id) {
                        $data['createdby'] = $this->_loginUserId;
                        $data['createddate']   = gmdate("Y-m-d H:i:s");
                        $actionFlag = 1; // Add
                        $saveMode   = 'Added';
                    }

                    // Save Group
                    $savedId = $this->_model->saveRecord($data, $id);

                    $roleId = $id ?: $savedId;

                    // 5. CRITICAL FIX: Pass the decoded $permissions array, not the POST request
                    $this->_savePrivileges($roleId, $permissions);

                    // Logging
                    $menuModel = new Default_Model_Menu();
                    $objidArr  = $menuModel->getMenuObjID('/roles');
                    $objID     = $objidArr[0]['id'] ?? 20;

                    sapp_Global::logManager($objID, $actionFlag, $this->_loginUserId, $roleId);

                    $db->commit();

                    $response = [
                        'result'    => 'saved',
                        'nomessage' => 'no',
                        'message'   => "Role $saveMode successfully."
                    ];

                    $_SESSION['role_msg'] = $response['message'];
                    $_SESSION['role_cls'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    if (APPLICATION_ENV == 'development') {
                        Zend_Debug::dump($e->getMessage());
                    }
                    $response['message'] = 'Something went wrong, please try again later.';
                    $_SESSION['role_msg'] = $response['message'];
                    $_SESSION['role_cls'] = 'error';
                    $response['result'] = 'exception';
                }
            } else {
                $response['message'] = $form->getMessages();
            }
        }

        $this->_helper->json($response);
    }

    /* --------------------------------------------------------------
     * Functional Group 4: Record Removal
     * -------------------------------------------------------------- */

    /**
     * Delete roles and associated privilege records
     */
    public function deleteAction()
    {
        $auth = Zend_Auth::getInstance();
        $loginUserId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : null;

        $id = $this->_getParam('objid');
        $deleteflag = $this->_getParam('deleteflag');
        $messages = ['message' => '', 'msgtype' => 'error'];

        if ($id) {
            $rolesModel = new Default_Model_Roles();
            $userModel = new Default_Model_Usermanagement();

            if ($userModel->getUserCntByRole($id) == 0) {
                $privilegesModel = new Default_Model_Privileges();
                $menuModel = new Default_Model_Menu();

                $data = [
                    'isactive'     => 'N',
                    'modifiedby'   => $loginUserId,
                    'modifieddate' => gmdate("Y-m-d H:i:s"),
                ];

                if ($rolesModel->SaveorUpdateRolesData($data, ['id=?' => $id]) == 'update') {
                    $privData = [
                        'isactive'     => 0,
                        'modifiedby'   => $loginUserId,
                        'modifieddate' => gmdate("Y-m-d H:i:s"),
                    ];
                    $privilegesModel->SaveorUpdatePrivilegesData($privData, "role = " . $id);
                    sapp_Global::generateAccessControl();

                    $objID = $menuModel->getMenuObjID('/roles')[0]['id'];
                    sapp_Global::logManager($objID, 3, $loginUserId, $id);

                    $messages['message'] = 'Role deleted successfully';
                    $messages['msgtype'] = 'success';
                }
            } else {
                $messages['message'] = 'Role cannot be deleted as Employees with the role exist.';
            }
        }

        if ($deleteflag == 1) {
            $this->_helper->getHelper("FlashMessenger")->addMessage([$messages['msgtype'] => $messages['message']]);
        }
        $this->_helper->json($messages);
    }

    /* --------------------------------------------------------------
     * Functional Group 5: AJAX & UI Dependent Logic
     * -------------------------------------------------------------- */

    /**
     * AJAX Action to fetch menu items for a specific group/role
     */
    public function getgroupmenuAction()
    {
        $this->_helper->layout->disableLayout();
        $menuModel = new Default_Model_Menu();

        $id = $this->_getParam('id');
        $group_id = $this->_getParam('group_id');
        $pri_val = $this->_getParam('pri_val');

        $menu_data = $menuModel->getgroupmenu($group_id, $id, $pri_val);
        $active_menus = $menuModel->getisactivemenus();

        $act_menus = [];
        foreach ($active_menus as $act) {
            $act_menus[$act['id']] = $act;
        }

        $this->view->act_menus = $act_menus;
        $this->view->menu_arr = $menu_data['tmpArr'];
        $this->view->menu_data_post = $menu_data['menu_data_post'];
        $this->view->menu_data = $menu_data['menu_data'];
        $this->view->permission_data = $menu_data['permission_data'];
        $this->view->group_level = $menu_data['group_data']['level'];
        $this->view->disabled = $this->_getParam('disabled', '');
        $this->view->group_id = $group_id;
    }

    /**
     * Returns JSON list of active roles for AJAX refreshes
     */
    public function getrolesjsonAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $rolesModel = new Default_Model_Roles();
        $roles = $rolesModel->getRolesList('dept');

        $result = [];
        foreach ($roles as $id => $name) {
            $result[] = ['id' => $id, 'rolename' => $name];
        }

        echo json_encode($result);
    }

    /* --------------------------------------------------------------
     * Functional Group 6: Internal System Helpers
     * -------------------------------------------------------------- */

    /**
     * Helper for role creation/update notifications
     */
    public function mailing_function($sess_values, $role_name, $save_type, $rolesModel)
    {
        $group_ids = MANAGEMENT_GROUP . "," . SYSTEMADMIN_GROUP;
        $emp_arr = $rolesModel->getEmpForRoleMail($group_ids, ROLES);
        unset($emp_arr[$sess_values->id]);

        if ($save_type == 'add') {
            $hr_arr = $rolesModel->getEmpForRoleMail(HR_GROUP, EMPLOYEE);
            unset($hr_arr[$sess_values->id]);
            $emp_arr = $emp_arr + $hr_arr;
        }

        $headerText = ($save_type == 'add') ? 'created' : 'updated';
        $view = $this->getHelper('ViewRenderer')->view;

        foreach ($emp_arr as $empdata) {
            $this->view->emp_name = $empdata['userfullname'];
            $this->view->base_url = 'http://' . $this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
            $this->view->type = $save_type;
            $this->view->created_by = $sess_values->userfullname;
            $this->view->role_name = $role_name;

            $options = [
                'subject' => APPLICATION_NAME . ': Role is ' . $headerText,
                'header'  => 'Role is ' . $headerText,
                'toEmail' => $empdata['emailaddress'],
                'toName'  => $empdata['userfullname'],
                'message' => $view->render('mailtemplates/role.phtml'),
                'cron'    => 'yes'
            ];
            sapp_Global::_sendEmail($options);
        }
    }


    /* =================================================================================
     * PRIVATE METHODS
     * ================================================================================= */

    /**
     * Helper to process logic for saving/updating privileges via JSON payload.
     * Uses Model methods and performs Hard Delete for removals.
     * 
     * @param int $groupId
     * @param array $permissions Array derived from JSON { "menu_id": { "add": "Yes", ... } }
     */
    private function _savePrivileges($roleId, array $permissions)
    {
        $privilegeModel = new Default_Model_Privileges();
        $currentDate    = gmdate("Y-m-d H:i:s");

        // 1. Fetch Existing Map [MenuID => PrivilegeID] via Model
        $existingMap = $privilegeModel->getPrivilegeMapByRole($roleId);

        // 2. Loop through Incoming Data (Insert or Update)
        foreach ($permissions as $menuId => $perms) {

            // Prepare DB Payload
            $data = [
                'role'              => $roleId,
                'object'            => $menuId,
                'isactive'          => 1,
                'modifiedby'        => $this->_loginUserId,
                'modifieddate'      => $currentDate,
                // Default to 'No' if key missing in JSON
                'addpermission'     => $perms['add'] ?? 'No',
                'editpermission'    => $perms['edit'] ?? 'No',
                'deletepermission'  => $perms['delete'] ?? 'No',
                'viewpermission'    => $perms['view'] ?? 'No',
                'uploadattachments' => $perms['upload'] ?? 'No',
                'viewattachments'   => $perms['viewup'] ?? 'No'
            ];

            if (isset($existingMap[$menuId])) {
                // --- UPDATE ---
                $privId = $existingMap[$menuId];

                // Use Model's generic save method or standard update
                $privilegeModel->SaveorUpdatePrivilegesData($data, ['id = ?' => $privId]);

                // Remove from map so we know it's handled (remaining items will be deleted)
                unset($existingMap[$menuId]);
            } else {
                // --- INSERT ---
                $data['createdby']   = $this->_loginUserId;
                $data['createddate'] = $currentDate;

                $privilegeModel->SaveorUpdatePrivilegesData($data, '');
            }
        }

        // 3. Hard Delete Remaining
        // Any Menu ID left in $existingMap was NOT in the JSON, meaning the user unchecked it.
        if (!empty($existingMap)) {
            foreach ($existingMap as $menuId => $privId) {
                // Use the new Model method for Hard Delete
                $privilegeModel->deletePrivilege($privId);
            }
        }
    }

    /**
     * Form Factory
     * @param int|null $id
     * @param bool $readonly
     * @return Default_Form_Groups|null
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Roles();
        $groupsModel = new Default_Model_Groups();
        $util = new utlis();

        $data = [];

        $isExistingRecord = ($id && is_numeric($id) && $id > 0);

        $form->group_id = $util->populate_select($form->group_id, $groupsModel->getDataList(!$isExistingRecord), 'id', 'name', 'Select Group');

        if ($isExistingRecord) {
            $data = $this->_model->getRoleDataById($id);
            if (!$data) return null;

            // Sanitize
            foreach ($data as $key => $val) {
                $data[$key] = htmlentities(addslashes($val), ENT_QUOTES, "UTF-8");
            }

            $form->setDefaults($data);

            $form->getElement('submit')->setLabel('Update');
        }

        $form->setAttrib('action', BASE_URL . 'roles/save');

        if ($readonly) {
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }

}
