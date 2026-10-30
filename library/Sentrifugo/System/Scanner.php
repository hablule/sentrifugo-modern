<?php
/**
 * Controller & Action Scanner Engine
 * 
 * Introspects controller files safely using the PHP Tokenizer to populate 
 * the resource registry. Fully compatible with Zend Framework 1 routing standards.
 * 
 * @category   Library
 * @package    Sentrifugo_System
 * @author     Hablul Soft Solution < /hablul.in>
 * @version    Sentrifugo v3.2
 * @modified   Sentrifugo v3.2
 * @date	   June 2024
 */

/**
 * Controller & Action Scanner Engine
 * 
 * Introspects controller files safely using the PHP Tokenizer to populate 
 * the resource registry. This forms the foundation of the Sentrifugo ABAC 
 * (Attribute-Based Access Control) system by automatically discovering routable endpoints.
 * 
 * Refactored for SSOT v4.5:
 * - Detects and ignores Abstract classes, Interfaces, and Traits.
 * - Handles ZF1 'default' module normalization.
 * - Implements Reverse Sync (Soft-deletes removed files).
 * - Hardened Tokenizer for complex method signatures.
 * - Fully documented per SSOT XI.3.
 * 
 * @category   Library
 * @package    Sentrifugo_System
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Sentrifugo_System_Scanner
{
    /* --------------------------------------------------------------
     * Attributes
     * -------------------------------------------------------------- */

    /**
     * Model instance for managing Module records (main_module).
     * 
     * @var Default_Model_Module
     */
    protected $_moduleModel;

    /**
     * Model instance for managing Controller records (main_controller).
     * 
     * @var Default_Model_Controller
     */
    protected $_controllerModel;

    /**
     * Model instance for managing Action/Method records (main_action).
     * 
     * @var Default_Model_Action
     */
    protected $_actionModel;

    /* --------------------------------------------------------------
     * Public Methods (Initialization & Execution)
     * -------------------------------------------------------------- */

    /**
     * Constructor
     * 
     * Initializes the necessary database models required for synchronization 
     * during the scanning process.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->_moduleModel     = new Default_Model_Module();
        $this->_controllerModel = new Default_Model_Controller();
        $this->_actionModel     = new Default_Model_Action();
    }

    /**
     * Main Entry Point: Execute Directory Scanning
     * 
     * Iterates through the specified modules directory, identifies controller directories,
     * triggers file processing logic, and performs reverse-synchronization to remove 
     * ghost endpoints from the database.
     * 
     * @param string|null $path Optional root path to the modules directory (Defaults to APPLICATION_PATH/modules).
     * @return array An associative array summarizing scan metrics (new, updated, obsolete, skipped, deleted).
     * @throws Exception If the provided or default modules directory does not exist.
     */
    public function scan($path = null)
    {
        $path = $path ? $path : APPLICATION_PATH . '/modules';
        $results = array('new' => 0, 'updated' => 0, 'obsolete' => 0, 'skipped' => 0, 'deleted' => 0);

        if (!is_dir($path)) {
            throw new Exception("Modules directory not found at: " . $path);
        }

        $modules = new DirectoryIterator($path);
        
        foreach ($modules as $moduleInfo) {
            if ($moduleInfo->isDir() && !$moduleInfo->isDot()) {
                
                // ZF1 Normalization: Force lowercase for strict URL matching.
                $moduleName = strtolower($moduleInfo->getBasename());
                $controllersPath = $moduleInfo->getPathname() . '/controllers';

                if (!is_dir($controllersPath)) {
                    continue;
                }

                // Retrieve or create the Module ID in the database
                $moduleId = $this->_moduleModel->getOrCreateId($moduleName);
                
                // Track physical files found to perform Reverse Sync later
                $foundControllers = array();
                
                $controllers = new DirectoryIterator($controllersPath);

                foreach ($controllers as $controllerInfo) {
                    if ($controllerInfo->isFile() && $controllerInfo->getExtension() == 'php') {
                        
                        $ctrlName = $this->_processControllerFile($controllerInfo, $moduleId, $results);
                        if ($ctrlName) {
                            $foundControllers[] = $ctrlName;
                        }
                    }
                }
                
                // Reverse Sync: Soft-delete controllers in DB that no longer exist on disk
                $this->_cleanupDeletedControllers($moduleId, $foundControllers, $results);
            }
        }
        
        return $results;
    }

    /* --------------------------------------------------------------
     * Private Helper Methods (Processing & Parsing)
     * -------------------------------------------------------------- */

    /**
     * Process a Single Controller File
     * 
     * Analyzes the physical file, checks its hash against the database to optimize performance,
     * extracts routable actions, and synchronizes the differences (New, Obsolete) with the DB.
     * 
     * @param DirectoryIterator $file     The iterator object representing the controller file.
     * @param int               $moduleId The database ID of the parent module.
     * @param array             &$results Reference to the metrics array to increment counts.
     * @return string|null The formatted controller name if successfully processed, or null if skipped.
     */
    private function _processControllerFile(DirectoryIterator $file, $moduleId, array &$results)
    {
        $filePath = $file->getPathname();
        $fileName = $file->getBasename('.php');
        
        // Ensure file adheres to Zend Framework naming conventions
        if (substr($fileName, -10) !== 'Controller') {
            return null; 
        }

        // ZF1 formatting: Strip 'Controller' suffix and convert CamelCase to dashed-lowercase
        $rawName = substr($fileName, 0, -10);
        $controllerName = $this->_formatControllerName($rawName);
        
        // Hardened Check: Ensure it's an instantiable class (Not Abstract/Interface/Trait)
        $classAnalysis = $this->_analyzeClassStructure($filePath);
        if (!$classAnalysis['isValid']) {
            return null;
        }

        $currentHash = md5_file($filePath);
        $dbController = $this->_controllerModel->getRecordByModuleIdAndController($moduleId, $controllerName);

        // Performance Optimization: Skip DB writes if the file hasn't changed
        if ($dbController && $dbController['file_hash'] === $currentHash) {
            $results['skipped']++;
            return $controllerName;
        }

        $scannedActions = $classAnalysis['actions'];
        $currentDate = gmdate("Y-m-d H:i:s");
        
        $dbData = array(
            'module_id'    => $moduleId,
            'controller'   => $controllerName,
            'file_path'    => $filePath,
            'file_hash'    => $currentHash,
            'last_scanned' => $currentDate,
            'isactive'     => 1
        );
        
        $controllerId = $dbController ? $dbController['id'] : null;

        // Upsert Controller Record
        if ($controllerId) {
            $this->_controllerModel->saveRecord($dbData, $controllerId);
        } else {
            $controllerId = $this->_controllerModel->saveRecord($dbData);
        }

        // Compare DB Actions against Physically Scanned Actions
        $existingActions = $this->_actionModel->getActionNamesByControllerId($controllerId);
        
        $newActions = array_diff($scannedActions, $existingActions);
        $obsoleteActions = array_diff($existingActions, $scannedActions);
        
        // Insert New Actions
        foreach ($newActions as $action) {
            $this->_actionModel->saveRecord(array(
                'controller_id' => $controllerId, 
                'name'          => $action, 
                'status'        => 'New',
                'isactive'      => 1
            ));
            $results['new']++;
        }
        
        // Mark Removed Actions as Obsolete
        if (!empty($obsoleteActions)) {
            $this->_actionModel->markAsObsolete($obsoleteActions, $controllerId);
            $results['obsolete'] += count($obsoleteActions);
        }

        $results['updated']++;
        
        return $controllerName;
    }

    /**
     * Analyze Class Structure via PHP Tokenizer
     * 
     * Safely extracts class metadata and public methods ending in 'Action' without 
     * loading the class into memory (preventing fatal dependency errors). 
     * Actively rejects Abstract classes, Interfaces, and Traits.
     * 
     * @param string $filePath Absolute path to the PHP controller file.
     * @return array Associative array containing 'isValid' (bool) and 'actions' (array of strings).
     */
    private function _analyzeClassStructure($filePath)
    {
        $source = file_get_contents($filePath);
        $tokens = token_get_all($source);
        
        $analysis = array(
            'isValid' => true,
            'actions' => array()
        );
        
        $isPublic = true; 

        foreach ($tokens as $index => $token) {
            if (is_array($token)) {
                $type = $token[0];

                // Instantly reject unroutable class types
                if ($type === T_ABSTRACT || $type === T_INTERFACE || $type === T_TRAIT) {
                    $analysis['isValid'] = false;
                    return $analysis; 
                }

                // Track method visibility scope
                if ($type === T_PUBLIC) {
                    $isPublic = true;
                } elseif ($type === T_PROTECTED || $type === T_PRIVATE) {
                    $isPublic = false;
                } 
                
                // Extract public methods
                elseif ($type === T_FUNCTION && $isPublic) {
                    $lookAhead = $index + 1;
                    while (isset($tokens[$lookAhead])) {
                        $nextToken = $tokens[$lookAhead];
                        
                        // Capture the method name
                        if (is_array($nextToken) && $nextToken[0] === T_STRING) {
                            $methodName = $nextToken[1];
                            // ZF1 Standard: Routable methods must end with 'Action'
                            if (substr($methodName, -6) === 'Action') {
                                $analysis['actions'][] = $methodName;
                            }
                            break; 
                        }
                        
                        // Ignore whitespace and comments, stop parsing at parenthesis
                        if (is_array($nextToken) && !in_array($nextToken[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
                            break;
                        }
                        if (!is_array($nextToken) && $nextToken !== '&') {
                            break; 
                        }
                        $lookAhead++;
                    }
                }
            } elseif ($token === '{' || $token === '}') {
                $isPublic = true; // Reset visibility scope on block closure
            }
        }

        $analysis['actions'] = array_unique($analysis['actions']);
        return $analysis;
    }

    /**
     * Reverse Sync: Clean Up Deleted Controllers
     * 
     * Identifies controllers existing in the database that no longer exist on the 
     * physical file system, and applies a soft-delete (isactive = 5) to them.
     * 
     * @param int   $moduleId         The database ID of the parent module.
     * @param array $foundControllers Array of controller names physically found on disk.
     * @param array &$results         Reference to the metrics array to increment 'deleted' count.
     * @return void
     */
    private function _cleanupDeletedControllers($moduleId, array $foundControllers, array &$results)
    {
        // Fetch strictly active controllers for this module
        $dbControllers = $this->_controllerModel->getRecordsByModuleId($moduleId, true);
        
        foreach ($dbControllers as $dbCtrl) {
            // If DB controller is missing from the physical file array
            if (!in_array($dbCtrl['controller'], $foundControllers)) {
                
                // SSOT IV: Perform Soft Delete via Model implementation
                $this->_controllerModel->deleteRecord($dbCtrl['id']);
                
                // Cascade status update: Mark all child actions as Obsolete
                $actions = $this->_actionModel->getActionNamesByControllerId($dbCtrl['id']);
                if (!empty($actions)) {
                    $this->_actionModel->markAsObsolete($actions, $dbCtrl['id']);
                }
                
                $results['deleted']++;
            }
        }
    }

    /**
     * Format Controller Name for URL Routing
     * 
     * Converts a CamelCase controller filename into the standard dashed-lowercase 
     * format utilized by the Zend Framework 1 routing engine.
     * Example: "UserRole" becomes "user-role".
     * 
     * @param string $unformatted The raw controller name without the 'Controller' suffix.
     * @return string The URL-safe, dashed controller name.
     */
    private function _formatControllerName($unformatted)
    {
        // Insert a dash before any uppercase letter that follows a lowercase letter or number
        $dashed = preg_replace('/(?<=[a-z0-9])([A-Z])/', '-$1', $unformatted);
        return strtolower($dashed);
    }
}