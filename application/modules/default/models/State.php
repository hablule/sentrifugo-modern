<?php

/**
 * State Lookup Model
 * 
 * Manages geographical states/provinces/regions (Pillar 1, Level 2).
 * Strictly adheres to SSOT v6.1: Zend_Db_Select, dependency checks, 
 * standardized grid formatting, and grouped method architecture.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_State extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT Singular Naming Convention) */
    protected $_name = 'main_state';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 1: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');
        if ($activeStatus !== null) $select->where('isactive = ?', (int)$activeStatus);
        if (!empty($search_val) && !empty($search_str)) $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        $select->order('name ASC');
        if ($limit !== null) $select->limit($limit, (int)$offset);
        return $this->fetchAll($select)->toArray();
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'status_text') {
                    if ($val === 'Active') $searchQuery .= " s.isactive = 1 AND ";
                    elseif ($val === 'Inactive') $searchQuery .= " s.isactive = 0 AND ";
                } elseif ($key == 'country_name') {
                    $searchQuery .= " c.name LIKE '%$val%' AND ";
                } else {
                    $searchQuery .= " s.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        $tableHeaders = array(
            'action'       => 'Action',
            'country_name' => 'Country',
            'name'         => 'State/Province',
            'code'         => 'Code',
            'status_text'  => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);
        $countryModel = new Default_Model_Country();
        $countries = array('' => 'All') + $countryModel->getPairs(false);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'state',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'States',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text'  => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive')),
                'country_name' => array('type' => 'select', 'filter_data' => $countries)
            )
        );
    }

    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') $by = 's.isactive';
        if ($by == 'country_name') $by = 'c.name';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('s' => $this->_name), array(
                'id' => 's.id',
                '*',
                'status_text' => new Zend_Db_Expr("CASE WHEN s.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ))
            ->joinInner(array('c' => 'main_country'), 's.country_id = c.id', array('country_name' => 'c.name'))
            ->where('s.isactive != 5');

        if (!empty($searchQuery)) $select->where($searchQuery);
        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
        if ($activeOnly) $select->where("isactive = 1");
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Generates a flat ID => Name array of States for a specific Country.
     * Used primarily for cascading AJAX dropdowns.
     * 
     * @param int  $countryId  The ID of the parent Country
     * @param bool $activeOnly Whether to filter out inactive states
     * @return array An associative array of [id => name]
     */
    public function getPairsByCountry($countryId, $activeOnly = true)
    {
        if (empty($countryId)) {
            return array();
        }

        $select = $this->select()
            ->from($this->_name, array('id', 'name'))
            ->where('country_id = ?', (int)$countryId)
            ->where('isactive != ?', 5)
            ->order('name ASC');

        if ($activeOnly) {
            $select->where('isactive = ?', 1);
        }

        return $this->getAdapter()->fetchPairs($select);
    }

    public function getRecordById($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('s' => $this->_name), array('s.*'))
            ->joinInner(array('c' => 'main_country'), 's.country_id = c.id', array('country_name' => 'c.name'))
            ->where('s.id = ?', (int)$id)
            ->where('s.isactive != 5');

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /* =================================================================================
     * GROUP 2: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, array('id = ?' => (int)$id));
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

    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this State as it is currently assigned to a City.");
        }

        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = array('isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = array('isactive' => (int)$status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 4: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()->where("$field = ?", trim($value))->where("isactive != 5");
        if ($excludeId) $select->where('id != ?', (int)$excludeId);
        return $this->fetchRow($select);
    }

    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from('main_city', 'id') // Assuming the renamed city table will be 'main_city'
            ->where('state_id = ?', (int)$id)
            ->limit(1);

        return (bool)$db->fetchOne($select);
    }

    /* =================================================================================
     * GROUP 6: POLYMORPHIC JUNCTION SYNC (GEOGRAPHY GROUPS)
     * ================================================================================= */

    /**
     * Retrieves an array of Geography Group IDs currently mapped to this State.
     */
    public function getMappedGeographyGroupIds($stateId)
    {
        if (empty($stateId)) return array();

        $db = $this->getAdapter();
        $select = $db->select()
            ->from('map_geographygroup_entity', 'geographygroup_id')
            ->where('entity_type = ?', 'State')
            ->where('entity_id = ?', (int)$stateId)
            ->where('isactive != 5');

        return $db->fetchCol($select);
    }

    /**
     * Synchronizes the Polymorphic Many-to-Many relationship for States.
     */
    public function syncGeographyGroupMappings($stateId, array $groupIds)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        $currentMappedIds = $this->getMappedGeographyGroupIds($stateId);
        $toAdd    = array_diff($groupIds, $currentMappedIds);
        $toRemove = array_diff($currentMappedIds, $groupIds);

        if (!empty($toRemove)) {
            $db->delete('map_geographygroup_entity', array(
                'entity_type = ?' => 'State',
                'entity_id = ?'   => (int)$stateId,
                'geographygroup_id IN (?)' => $toRemove
            ));
        }

        if (!empty($toAdd)) {
            foreach ($toAdd as $gId) {
                $db->insert('map_geographygroup_entity', array(
                    'geographygroup_id' => (int)$gId,
                    'entity_type'       => 'State',
                    'entity_id'         => (int)$stateId,
                    'isactive'          => 1,
                    'createdby'         => $userId,
                    'createddate'       => $currentDate
                ));
            }
        }
    }
}
