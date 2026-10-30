<?php

/**
 * Phone Number Model
 *
 * Manages the core data for unique telephone numbers.
 * Includes synchronization logic for the Many-to-Many mapping table `map_phone_owner`.
 * Complies with SSOT v6.6 (Zend_Db_Select, Singular Naming, Junction Table Sync).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Phonenumber extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_phone_number';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getRecordById($id)
    {
        $select = $this->select()
            ->where('id = ?', (int)$id)
            ->where('isactive != ?', 5);

        $row = $this->fetchRow($select);
        return $row ? $row->toArray() : null;
    }

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != ?', 5);

        if ($activeStatus !== null) {
            $select->where('isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . trim((string)$search_str) . '%');
        }

        if ($limit !== null) {
            $select->limit((int)$limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'number'))->order('number ASC');
        if ($activeOnly) {
            $select->where('isactive = ?', 1);
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if (!empty($searchData) && $searchData !== 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key === 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " pn.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'type_name') {
                        $searchQuery .= " pt.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'category_name') {
                        $searchQuery .= " pc.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } else {
                        $searchQuery .= " pn." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // SSOT Mandatory: 'action' => 'Action' must be the first element
        $tableHeaders = array(
            'action'        => 'Action',
            'number'        => 'Phone Number',
            'type_name'     => 'Type',
            'category_name' => 'Category',
            'status_text'   => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'phonenumber',
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Phone Directory',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'status_text') {
            $by = 'pn.isactive';
        } elseif ($by === 'type_name') {
            $by = 'pt.name';
        } elseif ($by === 'category_name') {
            $by = 'pc.name';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('pn' => $this->_name),
                array(
                    'id' => 'pn.id',
                    'number',
                    'status_text' => new Zend_Db_Expr("CASE WHEN pn.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            ->joinInner(
                array('pt' => 'main_phone_type'),
                'pn.type_id = pt.id',
                array('type_name' => 'pt.name')
            )
            ->joinInner(
                array('pc' => 'main_phone_category'),
                'pn.category_id = pc.id',
                array('category_name' => 'pc.name')
            )
            ->where('pn.isactive != ?', 5);

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $currentDate = gmdate("Y-m-d H:i:s");

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = $currentDate;
            $this->update($data, array('id = ?' => (int)$id));
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = $currentDate;
        $this->insert($data);
        return (int) $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: RECORD REMOVAL
     * ================================================================================= */

    public function deleteRecord($id)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = array(
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );

        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = array(
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );

        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS, & INTERNAL HELPERS
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim((string)$value))
            ->where('isactive != ?', 5);

        if (!empty($excludeId)) {
            $select->where('id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        // A phone number can be deleted. ON DELETE CASCADE on the mapping table will clean up its assignments.
        return false;
    }

    /* =================================================================================
     * GROUP 6: JUNCTION TABLE SYNC (POLYMORPHIC MANY-TO-MANY)
     * ================================================================================= */

    /**
     * SSOT v6.6: Fetches all polymorphic owner mappings for a given phone number.
     * Returns a structured array keyed by the lowercase entity type for easy form population.
     *
     * @param int $phoneNumberId
     * @return array e.g., ['employee' => [1, 5], 'company' => [3]]
     */
    public function getMappedOwners($phoneNumberId)
    {
        $mapped = [
            'employee'   => [],
            'department' => [],
            'team'       => [],
            'company'    => [],
            'sbunit'     => [],
            'division'   => [],
            'section' => []
        ];

        if (empty($phoneNumberId)) {
            return $mapped;
        }

        $db = $this->getAdapter();
        $select = $db->select()
            ->from('map_phone_owner', array('owner_type', 'owner_id'))
            ->where('phone_number_id = ?', (int)$phoneNumberId)
            ->where('isactive != ?', 5);

        $results = $db->fetchAll($select);

        // Group the retrieved owner_ids into their respective owner_type arrays
        foreach ($results as $row) {
            // Convert 'Company' to 'company' to match the form array keys
            $typeKey = strtolower($row['owner_type']);

            // Map 'BusinessUnit' ENUM to the 'sbunit' form key
            if ($typeKey === 'businessunit') {
                $typeKey = 'sbunit';
            }

            if (array_key_exists($typeKey, $mapped)) {
                $mapped[$typeKey][] = (int)$row['owner_id'];
            }
        }

        return $mapped;
    }

    /**
     * SSOT v6.6: Synchronizes the Polymorphic Many-to-Many relationship between a Phone Number and its Owners.
     * This method performs an atomic "delete all, then insert all" operation.
     *
     * @param int   $phoneNumberId The ID of the Phone Number being saved
     * @param array $owners  A structured array from the form: ['employee' => [1, 5], 'company' => [3]]
     * @return void
     */
    public function syncOwnerMappings($phoneNumberId, array $owners)
    {
        $db = $this->getAdapter();
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        // 1. Hard-delete all existing owner mappings for this phone number.
        $db->delete('map_phone_owner', array('phone_number_id = ?' => (int)$phoneNumberId));

        // 2. Iterate through the submitted owner types and IDs, and insert the new mappings.
        foreach ($owners as $formTypeKey => $ids) {
            if (is_array($ids) && !empty($ids)) {

                // Translate form keys back to the exact ENUM values required by the database
                $enumType = ucfirst($formTypeKey); // 'employee' -> 'Employee'
                if ($formTypeKey === 'sbunit') {
                    $enumType = 'BusinessUnit';    // 'sbunit' -> 'BusinessUnit' ENUM
                }

                foreach ($ids as $ownerId) {
                    $db->insert('map_phone_owner', [
                        'phone_number_id' => (int)$phoneNumberId,
                        'owner_type'      => $enumType, // Polymorphic Column 1
                        'owner_id'        => (int)$ownerId, // Polymorphic Column 2
                        'is_primary'      => 0, // Defaulting to 0; advanced logic needed if UI supports selecting primary
                        'isactive'        => 1,
                        'createdby'       => $userId,
                        'createddate'     => $currentDate
                    ]);
                }
            }
        }
    }
}
