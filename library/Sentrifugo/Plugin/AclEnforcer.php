<?php

/**
 * ACL Enforcement Plugin
 * 
 * Intercepts all requests and verifies access against the generated ACL Cache.
 * SSOT v4.5 Compliant: Handles AJAX gracefully, case-insensitive routing, 
 * and strict Default Deny.
 */
class Sentrifugo_Plugin_AclEnforcer extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        // Failsafe: If the request is already trying to render an error, let it pass!
        // This prevents infinite loops if the error controller itself triggers the ACL.
        if ($request->getControllerName() === 'error') {
            return;
        }

        // 1. Get Current Request Targets (Forced Lowercase for Safe Matching)
        $module     = strtolower($request->getModuleName());
        $controller = strtolower($request->getControllerName());
        $action     = strtolower($request->getActionName() . 'action');

        // 2. Public Exceptions (Whitelisted Controllers - No ACL required)
        $publicControllers = array('index', 'error', 'login', 'logout');
        if (empty($controller) || in_array($controller, $publicControllers)) {
            return; // Allow access
        }

        // 3. Get User Identity
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()) {
            $this->_denyAccess($request, 'Session expired. Please log in again.', true);
            return;
        }

        $user = $auth->getStorage()->read();
        $roleId = (int)$user->emprole;

        // 4. Load ACL Cache
        $cacheFile = DATA_PATH . '/acl_cache.php';
        if (!file_exists($cacheFile)) {
            $this->_denyAccess($request, 'System Security Configuration Missing. Please contact Administrator.');
            return;
        }

        $aclCache = include $cacheFile;

        // 5. Strictly Verify Access in Tree (Default Deny)
        $isAllowed = isset($aclCache[$roleId][$module][$controller][$action]) 
                     && $aclCache[$roleId][$module][$controller][$action] === true;

        if (!$isAllowed) {
            $this->_denyAccess($request, 'You do not have permission to access this action.');
        }
    }

    
    /**
     * Helper to redirect unauthorized requests safely.
     * Detects AJAX requests to prevent UI freezing.
     * 
     * @param Zend_Controller_Request_Abstract $request
     * @param string $message
     * @return void
     */
    private function _denyAccess(Zend_Controller_Request_Abstract $request, $message, $isAuthFailure = false)
    {
        // Handle AJAX Requests (Return JSON instead of HTML redirect)
        if ($request->isXmlHttpRequest()) {
            $response = array(
                'result'  => 'error',
                'msgtype' => 'error',
                'message' => $message,
                'login'   => $isAuthFailure ? 'failed' : 'valid'
            );
            
            // Clean output buffer and send JSON
            ob_clean();
            header('Content-Type: application/json');
            echo Zend_Json::encode($response);
            exit; // Immediately halt execution
        }

        // Handle Standard HTML Requests
        if ($isAuthFailure) {
            $request->setModuleName('default')
                    ->setControllerName('index')
                    ->setActionName('index');
        } else {
            // Route to Sentrifugo's standard privilege error handler
            $request->setModuleName('default')
                    ->setControllerName('error')
                    ->setActionName('privilege')
                    ->setParam('msg', $message);
        }
    }
}