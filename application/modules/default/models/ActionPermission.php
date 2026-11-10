<?php

/**
 * Action Permission Model
 * 
 * Manages the mapping of specific Controller Actions to standard Privileges (Add, Edit, View, etc.).
 * Generates the physical ACL cache file utilized by the security plugin.
 * Strictly formatted to SSOT v4.5.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_ActionPermission extends Zend_Db_Table_Abstract
{
    /** @var string Table name */
    protected $_name = 'main_action_permission';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 1: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Standard retrieval of all records.
     */
    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');

        if ($activeStatus !== null) {
            $select->where('isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        }

        if ($limit !== null) {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Retrieves a detailed record including joined Module, Controller, and Action names.
     * Used for populating the Edit/View Form context fields.
     * 
     * @param int $id
     * @return array|null
     */
    public function getDetailedRecordById($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ap' => $this->_name], ['ap.*'])
            ->joinLeft(['a' => 'main_action'], 'ap.action_id = a.id', ['action_name' => 'a.name'])
            ->joinLeft(['c' => 'main_controller'], 'a.controller_id = c.id', ['controller' => 'c.controller'])
            ->joinLeft(['m' => 'main_module'], 'c.module_id = m.id', ['module_name' => 'm.name'])
            ->where('ap.id = ?', (int)$id)
            ->where('ap.isactive != 5');

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /**
     * SSOT Standard Grid Configuration.
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                // Map search fields to actual DB aliases
                if ($key == 'action_name') $searchQuery .= " a.name LIKE '%$val%' AND ";
                elseif ($key == 'controller') $searchQuery .= " c.controller LIKE '%$val%' AND ";
                elseif ($key == 'module_name') $searchQuery .= " m.name LIKE '%$val%' AND ";
                elseif ($key == 'status_text') {
                    $dbVal = ($val == 'Active') ? 1 : 0;
                    $searchQuery .= " ap.isactive = $dbVal AND ";
                } else $searchQuery .= " ap.$key LIKE '%$val%' AND ";
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // FIX: Re-added the 'action' key so Sentrifugo renders the Edit/View icons
        $tableHeaders = [
            'action'      => 'Action',
            'module_name' => 'Module',
            'controller'  => 'Controller',
            'action_name' => 'Method Name',
            'permission'  => 'Required Privilege',
            'status_text' => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tableContent,
            'objectname' => 'permissionmap',
            'tableheader' => $tableHeaders,
            'extra' => [],
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => [],
            'menuName' => 'Permission Maps',
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
            ]
        ];
    }

    /**
     * SSOT Standard Grid Data Fetcher.
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        // Handle alias sorting maps
        if ($by == 'action_name') $by = 'a.name';
        if ($by == 'module_name') $by = 'm.name';
        if ($by == 'controller')  $by = 'c.controller';
        if ($by == 'status_text') $by = 'ap.isactive';

        // Ensure default sort prefix if generic 'id' or 'permission'
        if (in_array($by, ['id', 'permission'])) $by = 'ap.' . $by;

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ap' => $this->_name], [
                'id',
                'permission',
                'isactive',
                'status_text' => new Zend_Db_Expr("CASE WHEN ap.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->joinLeft(['a' => 'main_action'], 'ap.action_id = a.id', ['action_name' => 'a.name'])
            ->joinLeft(['c' => 'main_controller'], 'a.controller_id = c.id', ['controller' => 'c.controller'])
            ->joinLeft(['m' => 'main_module'], 'c.module_id = m.id', ['module_name' => 'm.name'])
            ->where('ap.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    /**
     * Standard Pairs Retrieval.
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'permission'])->order('permission ASC');
        if ($activeOnly) {
            $select->where("isactive = 1");
        }

        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Standard ID Retrieval.
     */
    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /**
     * Helper to fetch active records by action ID.
     */
    public function getRecordsByActionId($actionId, $activeOnly = true)
    {
        $select = $this->select()->where('action_id = ?', (int)$actionId)->where("isactive != 5");
        if ($activeOnly) {
            $select->where("isactive = 1");
        }
        return $this->fetchAll($select)->toArray();
    }

    /* =================================================================================
     * GROUP 2: PERSISTENCE
     * ================================================================================= */

    /**
     * Generates a flat PHP array mapping Roles -> Modules -> Controllers -> Actions
     * and saves it to a cache file for high-performance ACL checks.
     * 
     * @return bool True on success
     * @throws Exception If cache file cannot be written atomically
     */
    public function generateAclCacheFile()
    {
        $db = $this->getAdapter();

        // 1. Build the Query using strict Zend_Db_Select
        $select = $db->select()
            ->from(['r' => 'main_role'], ['role_id' => 'r.id'])
            ->joinInner(['p' => 'main_privileges'], 'r.id = p.role', [])
            ->joinInner(['menu' => 'main_menu'], 'p.object = menu.id', [])
            // Link menu to controller safely using Zend_Db_Expr
            ->joinInner(
                ['c' => 'main_controller'],
                new Zend_Db_Expr("(menu.url LIKE CONCAT('%/', c.controller, '%') OR menu.url = c.controller OR menu.url LIKE CONCAT('%', c.controller))"),
                ['controller' => 'c.controller']
            )
            ->joinInner(['a' => 'main_action'], 'c.id = a.controller_id', ['action_name' => 'a.name'])
            ->joinInner(['ap' => 'main_action_permission'], 'a.id = ap.action_id', [])
            ->joinInner(['m' => 'main_module'], 'c.module_id = m.id', ['module_name' => 'm.name'])

            // Apply Active Status Filters
            ->where('r.isactive = ?', 'Y')
            ->where('p.isactive = ?', 1)
            ->where('a.status = ?', 'Active')
            ->where('ap.isactive = ?', 1)
            ->where('m.isactive = ?', 1)

            // Match the required permission against the Role's granted privilege
            ->where(new Zend_Db_Expr("
                (ap.permission = 'add' AND p.addpermission = 'Yes') OR
                (ap.permission = 'edit' AND p.editpermission = 'Yes') OR
                (ap.permission = 'delete' AND p.deletepermission = 'Yes') OR
                (ap.permission = 'view' AND p.viewpermission = 'Yes') OR
                (ap.permission = 'upload' AND p.uploadattachments = 'Yes') OR
                (ap.permission = 'viewupload' AND p.viewattachments = 'Yes')
            "))
            ->order('r.id ASC')
            ->order('m.name ASC')
            ->order('c.controller ASC')
            ->order('a.name ASC');

        $results = $db->fetchAll($select);

        // 2. Build the Multi-dimensional Array (Strictly Explicit)
        $aclTree = [];

        foreach ($results as $row) {
            $roleId     = (int)$row['role_id'];
            $module     = strtolower($row['module_name']);
            $controller = strtolower($row['controller']);
            $action     = strtolower($row['action_name']);

            $aclTree[$roleId] ??= [];
            $aclTree[$roleId][$module] ??= [];
            $aclTree[$roleId][$module][$controller] ??= [];

            // Set to true (Allowed)
            $aclTree[$roleId][$module][$controller][$action] = true;
        }

        // 3. Prepare File Content
        $cacheFilePath = DATA_PATH . '/acl_cache.php';

        $fileContent = "<?php\n";
        $fileContent .= "/**\n";
        $fileContent .= " * Auto-generated ACL Cache File\n";
        $fileContent .= " * Generated on: " . gmdate("Y-m-d H:i:s") . " GMT\n";
        $fileContent .= " * DO NOT EDIT MANUALLY. Run Scanner and Save Mappings to regenerate.\n";
        $fileContent .= " */\n";
        $fileContent .= "return " . var_export($aclTree, true) . ";\n";

        // 4. ATOMIC WRITE (Prevents Race Conditions)
        $tempFile = tempnam(DATA_PATH, 'acl_');
        if ($tempFile === false) {
            throw new Exception("Unable to create temporary ACL cache file in " . DATA_PATH);
        }

        file_put_contents($tempFile, $fileContent);
        chmod($tempFile, 0666);

        $success = rename($tempFile, $cacheFilePath);

        if (!$success) {
            @unlink($tempFile);
            throw new Exception("Failed to atomically swap ACL cache file.");
        }

        return true;
    }

    /**
     * Standard Save/Update.
     */
    public function saveRecord($data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if ($id) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, ['id = ?' => (int)$id]);
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        $this->insert($data);
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 3: REMOVAL
     * ================================================================================= */

    /**
     * Soft delete standard execution. Checks dependencies first.
     * 
     * @param int $id
     * @return int
     * @throws Exception if record is in use
     */
    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this record as it is currently in use.");
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /**
     * Hard delete all permissions for a specific action.
     * Used exclusively during the re-mapping process to prevent DB bloat.
     */
    public function purgePermissionsByActionId($actionId)
    {
        return $this->delete(['action_id = ?' => (int)$actionId]);
    }

    /**
     * Standard active status toggle.
     */
    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 4: INTEGRITY HELPERS
     * ================================================================================= */

    /**
     * Standard duplicate field checker.
     */
    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim($value))
            ->where("isactive != 5");

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * Required Dependency Checker (SSOT X.1)
     * Permission mappings do not have child records, so this safely returns false.
     * 
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        return false;
    }
}
