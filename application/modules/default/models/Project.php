<?php

/**
 * Project Model
 *
 * Manages cross-functional Project entities.
 * Complies with SSOT v6.5 (PHP 7.4 safe, Zend_Db_Select, Singular Naming).
 * Note: Projects are flat entities; relationships are managed via map_project_participant.
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Project extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'main_project';

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

    /**
     * Generates a flat ID => Name array for Select dropdowns.
     * 
     * @param bool $activeOnly
     * @return array
     */
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
                        $searchQuery .= " p.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'representative_name') {
                        $searchQuery .= " e.userfullname LIKE '%" . trim((string)$val) . "%' AND ";
                    } else {
                        $searchQuery .= " p." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // SSOT Mandatory: 'action' => 'Action' must be the first element
        $tableHeaders = array(
            'action'              => 'Action',
            'code'                => 'Code',
            'name'                => 'Project Name',
            'representative_name' => 'Project Manager',
            'status_text'         => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'project',
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Projects',
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
            $by = 'p.isactive';
        } elseif ($by === 'representative_name') {
            $by = 'e.userfullname';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('p' => $this->_name),
                array(
                    'id' => 'p.id',
                    'code',
                    'name',
                    'status_text' => new Zend_Db_Expr("CASE WHEN p.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            ->joinInner( // Inner join as representative_id is NOT NULL
                array('e' => 'main_employee'),
                'p.representative_id = e.id',
                array('representative_name' => 'e.userfullname')
            )
            ->where('p.isactive != ?', 5);

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
        $db = $this->getAdapter();

        // Check if mapped to any participants in the participant mapping table
        $mapSelect = $db->select()
            ->from('map_project_participant', array('id'))
            ->where('project_id = ?', (int)$id)
            ->where('isactive = ?', 1);

        if ($db->fetchRow($mapSelect)) return true;

        // Check if mapped to a Distribution List
        $distSelect = $db->select()
            ->from('main_distribution_list', array('id'))
            ->where('project_id = ?', (int)$id)
            ->where('isactive = ?', 1);
        if ($db->fetchRow($distSelect)) return true;

        return false;
    }
    
    /* =================================================================================
     * GROUP 6: JUNCTION TABLE SYNC (MANY-TO-MANY)
     * ================================================================================= */

    /**
     * SSOT v6.6: Fetches all participant mappings for a given project.
     * Returns a structured array keyed by entity type for easy form population and diffing.
     *
     * @param int $projectId
     * @return array e.g., ['employee' => [1, 5], 'department' => [3]]
     */
    public function getMappedParticipants($projectId)
    {
        $mapped = [
            'company'    => [],
            'sbunit'     => [],
            'division'   => [],
            'department' => [],
            'section'    => [],
            'team'       => [],
            'employee'   => []            
        ];

        if (empty($projectId)) {
            return $mapped;
        }

        $db = $this->getAdapter();
        $select = $db->select()
            ->from('map_project_participant')
            ->where('project_id = ?', (int)$projectId)
            ->where('isactive != ?', 5);

        $results = $db->fetchAll($select);

        foreach ($results as $row) {
            if (!empty($row['company_id']))    $mapped['company'][]    = (int)$row['company_id'];
            if (!empty($row['sbunit_id']))     $mapped['sbunit'][]     = (int)$row['sbunit_id'];
            if (!empty($row['division_id']))   $mapped['division'][]   = (int)$row['division_id'];
            if (!empty($row['department_id'])) $mapped['department'][] = (int)$row['department_id'];
            if (!empty($row['section_id']))    $mapped['section'][]    = (int)$row['section_id'];
            if (!empty($row['team_id']))       $mapped['team'][]       = (int)$row['team_id'];
            if (!empty($row['employee_id']))   $mapped['employee'][]   = (int)$row['employee_id'];            
        }

        return $mapped;
    }

    /**
     * SSOT v6.6: Synchronizes the Many-to-Many relationship between Projects and their Participants.
     * This method performs an atomic "delete all, then insert all" operation to ensure the
     * mapping table is an exact reflection of the submitted data.
     *
     * @param int   $projectId     The ID of the Project being saved
     * @param array $participants  A structured array from the form: ['employee' => [1, 5], 'department' => [3]]
     * @return void
     */
    public function syncParticipantMappings($projectId, array $participants)
    {
        $db = $this->getAdapter();
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        // 1. Hard-delete all existing participant mappings for this project.
        $db->delete('map_project_participant', array('project_id = ?' => (int)$projectId));

        // 2. Iterate through the submitted participant types and IDs, and insert the new mappings.
        foreach ($participants as $type => $ids) {
            if (is_array($ids)) {
                foreach ($ids as $participantId) {
                    $db->insert('map_project_participant', [
                        'project_id'    => (int)$projectId,
                        $type . '_id'   => (int)$participantId, // e.g., 'employee_id' => 15
                        'isactive'      => 1,
                        'createdby'     => $userId,
                        'createddate'   => $currentDate
                    ]);
                }
            }
        }
    }
}
