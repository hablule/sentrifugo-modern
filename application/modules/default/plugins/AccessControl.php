<?php

/**
 * DEPRECATED: Legacy Access Control Plugin
 * 
 * @deprecated Since SSOT v5.0. Replaced by Sentrifugo_Plugin_AclEnforcer.
 * 
 * This plugin has been safely deprecated ("defanged"). It no longer intercepts, 
 * blocks, or redirects routes. It only exists to populate Zend_Registry('acl') 
 * with a permissive bypass object to prevent legacy view scripts from crashing.
 * 
 * Actual security enforcement is strictly handled by Sentrifugo_Plugin_AclEnforcer.
 */
class Default_Plugin_AccessControl extends Zend_Controller_Plugin_Abstract
{
    private $_acl;

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        // 1. We NO LONGER block or redirect requests here.
        // 2. We only call _getAcl() to ensure Zend_Registry::set('acl', $acl) is populated
        //    in case any legacy .phtml files are still calling Zend_Registry::get('acl').
        $this->_getAcl();

        // If the new AclEnforcer plugin skipped registering 'tm_role', 
        // force a default value here so the layout broker doesn't crash.
        if (!Zend_Registry::isRegistered('tm_role')) {
            // Zend_Registry::set('tm_role', 'guest');
        }
    }

    protected function _getAcl()
    {
        if ($this->_acl == null) {
            // Create a custom bypass ACL that always returns true
            $acl = new Sentrifugo_Legacy_Acl_Bypass();
            
            // Satisfy the legacy registry requirement
            Zend_Registry::set('acl', $acl);
            $this->_acl = $acl;
        }
        return $this->_acl;
    }
}

/**
 * Dummy ACL class to satisfy legacy Zend_Registry calls without enforcing rules.
 * By returning true, we ensure old UI buttons aren't accidentally hidden, 
 * while the actual Route Security is enforced by the new ABAC Plugin.
 */
class Sentrifugo_Legacy_Acl_Bypass extends Zend_Acl 
{
    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        return true; 
    }
    
    public function has($resource)
    {
        return true;
    }
}