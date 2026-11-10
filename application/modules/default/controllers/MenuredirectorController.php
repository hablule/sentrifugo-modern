<?php

/**
 * Universal Menu Redirector Controller
 * 
 * Intercepts clicks on top-level Parent Menus and dynamically 
 * routes the user to their default sub-menu based on DB config and ACL permissions.
 * Strictly adheres to SSOT v6.7.4.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 */
class Default_MenuredirectorController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

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

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL (Smart Routing)
     * ================================================================================= */

    public function indexAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $pid = (int)$this->getRequest()->getParam('pid', 0);

        if ($pid === 0) {
            $this->_redirect('dashboard');
            return;
        }

        // Call the private helper to resolve the optimal redirect target
        $targetRoute = $this->_getLeafRoute($pid);

        if ($targetRoute) {
            $this->_redirect($targetRoute);
        } else {
            // Failsafe: No permissions for any sub-modules
            $this->_helper->flashMessenger->addMessage(['error' => "You do not have permission to view any modules within this section."]);
            $this->_redirect('dashboard');
        }
    }

    /* =================================================================================
     * GROUP 5: INTERNAL HELPERS (Method Decoupling)
     * ================================================================================= */

    /**
     * Recursively checks all leaf nodes in the hierarchy to find the optimal, 
     * permitted redirect target, strictly prioritizing any deeply nested 'isdefault' items.
     * 
     * @param int $pid The parent menu ID
     * @return string|null The target redirect URL, or null if none permitted
     */
    private function _getLeafRoute($pid)
    {
        $menuModel = new Default_Model_Menu();

        // Fetch all flattened clickable leaf nodes under this parent, prioritized by isdefault
        $leafRoutes = $menuModel->getLeafRoutesForRedirector($pid);

        foreach ($leafRoutes as $route) {
            // Check ACL View permission for the logged-in user
            $hasAccess = sapp_Global::_checkprivileges($route['id'], $this->_loginuserGroup, $this->_loginuserRole, 'view');

            if ($hasAccess === 'Yes') {
                return ltrim($route['url'], '/'); // Clean path for redirect
            }
        }

        return null;
    }
}
