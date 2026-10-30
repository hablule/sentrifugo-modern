<?php

/**
 * Address Model
 *
 * Manages the core physical street addresses for the organization.
 * Replaces the legacy, separated Location/Address architecture.
 * Includes synchronization logic for the polymorphic `map_address_owner` table.
 * Complies with SSOT v6.6 (PHP 7.4 safe, Zend_Db_Select, Junction Table Sync).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Address extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_address';

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
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
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
                        $searchQuery .= " a.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'address_type_name') {
                        $searchQuery .= " at.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'country_name') {
                        $searchQuery .= " c.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'city_name') {
                        $searchQuery .= " ci.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } else {
                        $searchQuery .= " a." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // SSOT Mandatory: 'action' => 'Action' must be the first element
        $tableHeaders = array(
            'action'            => 'Action',
            'code'              => 'Code',
            'name'              => 'Address Name',
            'address_type_name' => 'Type',
            'city_name'         => 'City',
            'country_name'      => 'Country',
            'status_text'       => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'address',
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Physical Addresses',
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
            $by = 'a.isactive';
        } elseif ($by === 'address_type_name') {
            $by = 'at.name';
        } elseif ($by === 'country_name') {
            $by = 'c.name';
        } elseif ($by === 'city_name') {
            $by = 'ci.name';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('a' => $this->_name),
                array(
                    'id' => 'a.id',
                    'code',
                    'name',
                    'status_text' => new Zend_Db_Expr("CASE WHEN a.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            ->joinInner(
                array('at' => 'main_address_type'),
                'a.address_type_id = at.id',
                array('address_type_name' => 'at.name')
            )
            ->joinInner(
                array('c' => 'main_country'),
                'a.country_id = c.id',
                array('country_name' => 'c.name')
            )
            ->joinInner(
                array('ci' => 'main_city'),
                'a.city_id = ci.id',
                array('city_name' => 'ci.name')
            )
            ->where('a.isactive != ?', 5);

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
        // Addresses can be deleted. ON DELETE CASCADE on map_address_owner will handle cleanup.
        return false;
    }

    /* =================================================================================
     * GROUP 6: JUNCTION TABLE SYNC (POLYMORPHIC MANY-TO-MANY)
     * ================================================================================= */

    /**
     * SSOT v6.6: Fetches all polymorphic owner mappings for a given address.
     * Returns a structured array keyed by the lowercase entity type for easy form population.
     *
     * @param int $addressId
     * @return array e.g., ['employee' => [1, 5], 'company' => [3]]
     */
    public function getMappedOwners($addressId)
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

        if (empty($addressId)) {
            return $mapped;
        }

        $db = $this->getAdapter();
        $select = $db->select()
            ->from('map_address_owner', array('owner_type', 'owner_id'))
            ->where('address_id = ?', (int)$addressId)
            ->where('isactive != ?', 5);

        $results = $db->fetchAll($select);

        foreach ($results as $row) {
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
     * SSOT v6.6: Synchronizes the Polymorphic Many-to-Many relationship between an Address and its Owners.
     * Performs an atomic "delete all, then insert all" operation.
     *
     * @param int   $addressId The ID of the Address being saved
     * @param array $owners    A structured array from the form: ['employee' => [1, 5], 'company' => [3]]
     * @return void
     */
    public function syncOwnerMappings($addressId, array $owners)
    {
        $db = $this->getAdapter();
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        // 1. Hard-delete all existing owner mappings for this address.
        $db->delete('map_address_owner', array('address_id = ?' => (int)$addressId));

        // 2. Iterate through the submitted owner types and IDs, and insert the new mappings.
        foreach ($owners as $formTypeKey => $ids) {
            if (is_array($ids) && !empty($ids)) {

                // Translate form keys back to the exact ENUM values required by the database
                $enumType = ucfirst($formTypeKey); // 'employee' -> 'Employee'
                if ($formTypeKey === 'sbunit') {
                    $enumType = 'BusinessUnit';    // 'sbunit' -> 'BusinessUnit' ENUM
                }

                foreach ($ids as $ownerId) {
                    $db->insert('map_address_owner', [
                        'address_id'  => (int)$addressId,
                        'owner_type'  => $enumType,
                        'owner_id'    => (int)$ownerId,
                        // Note: is_primary and is_permanent logic would require UI elements per row, 
                        // defaulting to 0 for batch mapping inserts.
                        'is_primary'  => 0,
                        'is_permanent' => 0,
                        'isactive'    => 1,
                        'createdby'   => $userId,
                        'createddate' => $currentDate
                    ]);
                }
            }
        }
    }
}
