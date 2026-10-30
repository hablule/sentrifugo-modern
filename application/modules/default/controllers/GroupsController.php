<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
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
 * Groups Controller
 * 
 * Manages User Groups and their associated Menu Privileges.
 * Refactored to adhere to SSOT and Schema v2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_GroupsController extends Zend_Controller_Action
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
        $this->_model = new Default_Model_Groups();
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

    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                echo Zend_Json::encode(array('login' => 'failed'));
                die();
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getgroupmenugroup', 'html')->initContext();
        $ajaxContext->addActionContext('save', 'json')->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Index Action (List View)
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

            // Set Defaults
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

            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (Exception $e) {
            if (APPLICATION_ENV == 'development') {
                Zend_Debug::dump($e->getMessage());
            }
        }
    }

    /**
     * View Action (Read-Only)
     */
    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');

        // Prepare form in read-only mode
        $form = $this->_prepareForm($id, true);
        $data = $this->_model->getGroupDataById($id);

        if (!$form) {
            $this->view->nodata = "nodata";
        } else {
            // Check active status text
            $this->view->active = ($data['isactive'] == 'Y') ? 'Yes' : 'No';
            $this->view->data   = $data;
        }

        // Additional View Data
        $menu_model = new Default_Model_Menu();
        $menuArray = $menu_model->getMenuPrivilegesByGroup($id);
        $menuTree = $menu_model->buildMenuTree($menuArray);


        $this->view->groupMenuArray = $menuTree;
        $this->view->form           = $form;
        $this->view->id             = $id;        
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    /**
     * Add Action
     */
    public function addAction()
    {
        $menu_model = new Default_Model_Menu();
        $menuArray = $menu_model->getMenuPrivilegesByGroup(0);
        $menuTree = $menu_model->buildMenuTree($menuArray);

        $form = $this->_prepareForm();

        $this->view->groupMenuArray = $menuTree;
        $this->view->form           = $form;
    }

    /**
     * Edit Action
     */
    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');

        $form = $this->_prepareForm($id);
        if (!$form) {
            $this->view->nodata = "nodata";
        }

        $menu_model = new Default_Model_Menu();
        $menuArray = $menu_model->getMenuPrivilegesByGroup($id);
        $menuTree = $menu_model->buildMenuTree($menuArray);


        $this->view->groupMenuArray = $menuTree;
        $this->view->form           = $form;
        $this->view->id             = $id;
    }

    /**
     * Save Action (Handles Insert and Update)
     */
    public function saveAction()
    {
        $form = new Default_Form_Groups();
        $request = $this->getRequest(); // Keep this as the Request Object
        $response = array('result' => 'error', 'message' => 'Invalid data');

        if ($request->isPost()) {

            // 1. Get POST data into a separate array variable
            $postData = $request->getPost();

            // 2. Extract and Decode Permissions
            $jsonRaw = isset($postData['permissions_json']) ? $postData['permissions_json'] : '';
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
                    $data = array(
                        'name'         => trim($request->getParam('name')),
                        'code'         => trim($request->getParam('code')),
                        'description'  => trim($request->getParam('description')),
                        'displayorder' => $request->getParam('displayorder'),
                        'isactive'     => $request->getParam('isactive'), 
                        'modifiedby'   => $this->_loginUserId,
                        'modified'     => gmdate("Y-m-d H:i:s"),
                    );

                    $actionFlag = 2; // Edit
                    $saveMode   = 'Updated';

                    if (!$id) {
                        $data['createdby'] = $this->_loginUserId;
                        $data['created']   = gmdate("Y-m-d H:i:s");
                        $actionFlag = 1; // Add
                        $saveMode   = 'Added';
                    }

                    // Save Group
                    $savedId = $this->_model->saveRecord($data, $id);

                    $groupId = ($id) ? $id : $savedId;

                    // 5. CRITICAL FIX: Pass the decoded $permissions array, not the POST request
                    $this->_savePrivileges($groupId, $permissions);

                    // Logging
                    $menuModel = new Default_Model_Menu();
                    $objidArr  = $menuModel->getMenuObjID('/groups');
                    $objID     = isset($objidArr[0]['id']) ? $objidArr[0]['id'] : 302;

                    sapp_Global::logManager($objID, $actionFlag, $this->_loginUserId, $groupId);

                    $db->commit();

                    $response = array(
                        'result'    => 'saved',
                        'nomessage' => 'no',
                        'message'   => "Group $saveMode successfully."
                    );

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
    private function _savePrivileges($groupId, array $permissions)
    {
        $privilegeModel = new Default_Model_Privileges();
        $currentDate    = gmdate("Y-m-d H:i:s");

        // 1. Fetch Existing Map [MenuID => PrivilegeID] via Model
        $existingMap = $privilegeModel->getPrivilegeMapByGroup($groupId);

        // 2. Loop through Incoming Data (Insert or Update)
        foreach ($permissions as $menuId => $perms) {
            
            // Prepare DB Payload
            $data = array(
                'group_id'          => $groupId,
                'object'            => $menuId,
                'isactive'          => 1,
                'modifiedby'        => $this->_loginUserId,
                'modifieddate'      => $currentDate,
                // Default to 'No' if key missing in JSON
                'addpermission'     => isset($perms['add']) ? $perms['add'] : 'No',
                'editpermission'    => isset($perms['edit']) ? $perms['edit'] : 'No',
                'deletepermission'  => isset($perms['delete']) ? $perms['delete'] : 'No',
                'viewpermission'    => isset($perms['view']) ? $perms['view'] : 'No',
                'uploadattachments' => isset($perms['upload']) ? $perms['upload'] : 'No',
                'viewattachments'   => isset($perms['viewup']) ? $perms['viewup'] : 'No'
            );

            if (isset($existingMap[$menuId])) {
                // --- UPDATE ---
                $privId = $existingMap[$menuId];
                
                // Use Model's generic save method or standard update
                $privilegeModel->SaveorUpdatePrivilegesData($data, array('id = ?' => $privId));

                // CASCADE UPDATE: Enforce restrictions on Roles
                $privilegeModel->enforceGroupLimitsOnRoles($groupId, $menuId, $data);

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

                // CASCADE DELETE: Remove this menu from all Roles in the group
                $privilegeModel->deleteRolePrivilegesForGroupObject($groupId, $menuId);
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
        $form = new Default_Form_Groups();
        $util = new utlis();

        if ($id) {
            $data = $this->_model->getGroupDataById($id);
            if (!$data) return null;

            // Sanitize
            foreach ($data as $key => $val) {
                $data[$key] = htmlentities(stripslashes($val), ENT_QUOTES, "UTF-8");
            }

            $form->populate($data);
            $form->getElement('isactive')->setValue($util->getcheckboxvalue($data['isactive'], 'form'));

            $form->getElement('submit')->setLabel('Update');
            $form->setAttrib('action', BASE_URL . 'groups/save');
        } else {
            $form->setAttrib('action', BASE_URL . 'groups/save');
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
