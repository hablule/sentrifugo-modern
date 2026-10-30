<?php

/**
 * Performance (Parent Menu) Redirector Controller
 * 
 * Intercepts clicks on the top-level "Performance" menu (ID 391) and dynamically 
 * routes the user to their default sub-menu based on DB config and ACL permissions.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 */
class Default_PerformanceController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    
    // The Menu ID of the Parent Menu this controller represents
    protected $_parentMenuId = 391; 

    public function init()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;
        }
    }

    public function indexAction()
    {
        $menuModel = new Default_Model_Menu();
        
        // 1. Fetch dynamic hierarchy from the database (Ordered by isdefault DESC)
        $childRoutes = $menuModel->getChildRoutesForRedirector($this->_parentMenuId);

        // 2. Iterate and check ACL
        foreach ($childRoutes as $route) {
            // Check if the user's Group/Role has 'View' permission for this specific child menu ID
            $hasAccess = sapp_Global::_checkprivileges($route['id'], $this->_loginuserGroup, $this->_loginuserRole, 'view');
            
            if ($hasAccess === 'Yes') {
                // Instantly redirect to the highest-priority permitted route
                // Strip leading slash if present to ensure proper Zend routing
                $cleanUrl = ltrim($route['url'], '/'); 
                $this->_redirect($cleanUrl);
                return;
            }
        }

        // 3. Failsafe: No permissions for ANY child menus.
        $this->_helper->flashMessenger->addMessage(array('error' => "You do not have permission to view any modules within Performance."));
        $this->_redirect('dashboard');
    }
}