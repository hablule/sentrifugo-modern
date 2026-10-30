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
 * Manage Menus Controller
 * Handles the global activation/deactivation of core system modules.
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @modified  Sentrifugo v3.2
 * @date	  June 2024
 */
class Default_ManagemenusController extends Zend_Controller_Action
{
    // SSOT Standard Properties;
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    protected $_options;
    

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Menu(); // Reusing the primary menu model

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
        // Enforce Admin access for global module management
       /*  if ($this->_loginuserGroup != SUPERADMIN) {
            $this->_redirect('error/privilege');
        } */
    }

    public function indexAction()
    {
        try {
            // 1. Get all Active Menu Group IDs (to determine initial SVG state)
            // We need a method to get which groups are currently active in the system.
            // Since menugroups themselves have an 'isactive' flag, we can just use that.
            $groupModel = new Default_Model_Menugroup();
            
            // 2. Fetch Top-Level Menus (The slices of the donut)
            $topLevelMenus = $groupModel->getTopLevelMenus();
            
            // 3. Determine which ones are currently 'Active' to light them up
            $activeIds = array();
            foreach ($topLevelMenus as $menu) {
                // If the group is returned by getTopLevelMenus, it is active (isactive=1)
                // If your system allows "disabling" a module globally by setting group isactive=0,
                // then all groups returned here are technically active.
                // *However*, to match the toggle logic, we just push all IDs here.
                $activeIds[] = $menu['id'];
            }
            
            $this->view->topLevelMenus = $topLevelMenus;
            $this->view->activeIds = $activeIds;
            
            // Pass Flash Messages
            $this->view->messages = $this->_helper->flashMessenger->getMessages();
            
        } catch (Exception $e) {
            if (APPLICATION_ENV == 'development') {
                Zend_Debug::dump($e->getMessage());
            }
        }
    }

    public function saveAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                // Get the array of defined core modules from constants
                // e.g., array(130, 19, 5, 8, 143, 149, ...)
                $defined_menus = unserialize(MANAGE_MODULE_ARRAY);

                // Get user selection (comma separated string -> array)
                $chk_menu_raw = trim($request->getParam('chk_menu', ''), ',');
                $chk_menu = !empty($chk_menu_raw) ? explode(',', $chk_menu_raw) : array();

                // Determine what to activate and deactivate
                $disable_menus = array_diff($defined_menus, $chk_menu);

                // 1. Activate selected modules and their children
                foreach ($chk_menu as $menuId) {
                    $this->_toggleMenuState((int)$menuId, 1);
                }

                // 2. Deactivate unselected modules and their children
                foreach ($disable_menus as $menuId) {
                    $this->_toggleMenuState((int)$menuId, 0);
                }

                // 3. Logging
                if (!empty($chk_menu)) {
                    $menuNames = $this->_model->getMenusNamesByIds($chk_menu_raw);

                    $logarr = array(
                        'userid' => $this->_loginUserId,
                        'recordid' => $chk_menu_raw,
                        'childrecordid' => $menuNames,
                        'date' => gmdate('Y-m-d H:i:s')
                    );

                    // Uses existing log manager function (assumed to be in Model or Global)
                    $this->_model->addOrUpdateMenuLogManager(
                        MANAGEMODULE,
                        2,
                        json_encode($logarr),
                        $this->_loginUserId,
                        $menuNames
                    );
                }

                // 4. Regenerate ACL / Menus
                sapp_Global::generateAccessControl();

                $db->commit();
                $this->_helper->flashMessenger->addMessage(array("success" => "Modules updated successfully."));
            } catch (Exception $e) {
                $db->rollBack();
                if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
                $this->_helper->flashMessenger->addMessage(array("error" => "Error updating modules."));
            }

            $this->_redirect('managemenus');
        }
    }

    /**
     * SSOT Helper to update Menu and Privilege states using Zend_Db_Table
     */
    private function _toggleMenuState($menuId, $isActive)
    {
        // Get all child IDs for this menu
        $menuIds = $this->_model->getMenusWithChilds($menuId);

        if (!empty($menuIds)) {
            // Update main_menu
            $this->_model->update(
                array('isactive' => $isActive),
                array('id IN (?)' => $menuIds)
            );

            // Update main_privileges
            $privModel = new Default_Model_Privileges();
            $privModel->update(
                array('isactive' => $isActive),
                array('object IN (?)' => $menuIds)
            );
        }
    }
}
