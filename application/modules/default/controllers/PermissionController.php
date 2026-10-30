<?php

/**
 * Permission Management Controller
 * 
 * Manages the ABAC (Attribute-Based Access Control) scanner and mappings.
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
class Default_PermissionController extends Zend_Controller_Action
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
        $this->_model   = new Default_Model_Action(); 

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
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('scan', 'json')
                    ->addActionContext('savemapping', 'json')
                    ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Prepares the mapping matrix.
     * Fetches controllers (joined with modules), actions, and mapped permissions.
     * 
     * @return void
     */
    public function indexAction()
    {
        // 1. Fetch hierarchical data for the Grid
        $controllerModel = new Default_Model_Controller();
        
        // Fix: Use getGridData to ensure 'module_name' is joined from 'main_module'
        $select = $controllerModel->getGridData('ASC', 'module_name', 1, 99999);
        $controllers = $controllerModel->fetchAll($select)->toArray();
        
        $actionModel = new Default_Model_Action();
        // Fetch active/new actions for mapping
        $allActions = $actionModel->getActionsForMapping();
        
        $permModel = new Default_Model_ActionPermission();
        $mappedPerms = $permModel->getAllRecords(null, null, null, null, 1);

        // 2. Build the Matrix [ControllerID][ActionID] => [Permissions]
        $matrix = [];
        
        // Group Actions by Controller
        foreach ($allActions as $act) {
            $matrix[$act['controller_id']]['actions'][$act['id']] = $act;
            $matrix[$act['controller_id']]['actions'][$act['id']]['permissions'] = [];
        }
        
        // Attach Permissions
        foreach ($mappedPerms as $perm) {
            foreach ($matrix as $ctrlId => $ctrlData) {
                if (isset($ctrlData['actions'][$perm['action_id']])) {
                    $matrix[$ctrlId]['actions'][$perm['action_id']]['permissions'][] = $perm['permission'];
                }
            }
        }

        // Attach Controller & Module Details
        foreach ($controllers as $ctrl) {
            if (isset($matrix[$ctrl['id']])) {
                $matrix[$ctrl['id']]['details'] = $ctrl;
            }
        }

        $this->view->matrix = $matrix;
        $this->view->standardPerms = ['add', 'edit', 'delete', 'view', 'upload', 'viewupload'];
    }

    /* =================================================================================
     * GROUP 3: PERSISTENCE & AJAX ACTIONS
     * ================================================================================= */

    public function scanAction()
    {
        try {
            require_once APPLICATION_PATH . '/../library/Sentrifugo/System/Scanner.php';
            $scanner = new Sentrifugo_System_Scanner();
            $results = $scanner->scan();
            
            $this->_helper->json([
                'status'  => 'success', 
                'message' => "Scan Complete. New: {$results['new']}, Updated: {$results['updated']}, Obsolete: {$results['obsolete']}."
            ]);
        } catch (Exception $e) {
            $this->_helper->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Saves the action-to-permission mappings submitted via the UI matrix.
     * 
     * @return void Outputs JSON response
     */
    public function savemappingAction()
    {
        $request = $this->getRequest();
        
        if (!$request->isPost()) {
            $this->_helper->json(['status' => 'error', 'message' => 'Invalid Request Method.']);
            return;
        }

        $jsonRaw = $request->getParam('mappings');
        $mappings = json_decode($jsonRaw, true);

        if (!is_array($mappings)) {
            $this->_helper->json(['status' => 'error', 'message' => 'Invalid data format received.']);
            return;
        }

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->beginTransaction();

        try {
            $permModel   = new Default_Model_ActionPermission();
            $actionModel = new Default_Model_Action();
            $currentDate = gmdate("Y-m-d H:i:s");
            
            $actionsUpdated = 0;

            foreach ($mappings as $actionId => $selectedPerms) {
                // 1. Wipe existing mappings cleanly via Model (Hard Delete for mapping tables)
                $permModel->purgePermissionsByActionId((int)$actionId);

                // 2. Insert new mappings
                if (!empty($selectedPerms)) {
                    foreach ($selectedPerms as $perm) {
                        // Use the standard saveRecord method
                        $permModel->saveRecord([
                            'action_id'  => (int)$actionId,
                            'permission' => $perm,
                            'isactive'   => 1
                        ]);
                    }
                    // Mark Action as Active (Mapped)
                    $actionModel->saveRecord(['status' => 'Active'], (int)$actionId);
                    $actionsUpdated++;
                } else {
                    // If no perms selected, it reverts to 'New' (Unmapped) state
                    $actionModel->saveRecord(['status' => 'New'], (int)$actionId);
                }
            }

            // 3. Generate Cache File for Gatekeeper Plugin
            // This will throw an Exception if file permissions block the atomic write
            $permModel->generateAclCacheFile();

            // 4. Logging (ActionFlag 2 = Edit)
            // Assuming SYSTEM_CONFIG_MENU_ID or similar constant exists for this module
            $menuId = defined('SYSTEM_CONFIG_MENU_ID') ? SYSTEM_CONFIG_MENU_ID : 377; // 377 = Permission Map Menu ID
            sapp_Global::logManager($menuId, 2, $this->_loginUserId, 0);

            $db->commit();
            
            $this->_helper->json([
                'status'  => 'success', 
                'message' => "Successfully updated mappings for {$actionsUpdated} actions and regenerated ACL cache."
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            
            // Log the actual error for debugging, but return a safe message to the UI
            error_log("Sentrifugo ACL Mapping Error: " . $e->getMessage());
            
            $this->_helper->json([
                'status'  => 'error', 
                'message' => 'Error saving mappings: ' . $e->getMessage()
            ]);
        }
    }
}