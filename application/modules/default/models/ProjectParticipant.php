<?php

/**
 * Project Participant Mapping Model
 *
 * Manages the assignment of individuals or organizational units to cross-functional Projects.
 * Complies with SSOT v6.5 (PHP 7.4 safe, Zend_Db_Select, Dynamic Target Resolution).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Projectparticipant extends Zend_Db_Table_Abstract
{
    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'map_project_participant';

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
     * getPairs is not typically applicable for a pure mapping table.
     * Retained for abstract compliance but returns empty.
     */
    public function getPairs($activeOnly = true)
    {
        return array();
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
                        $searchQuery .= " m.isactive = " . $dbVal . " AND ";
                    } elseif ($key === 'project_name') {
                        $searchQuery .= " proj.name LIKE '%" . trim((string)$val) . "%' AND ";
                    } elseif ($key === 'participant_name') {
                        // Search across all dynamically resolved participant names
                        $searchQuery .= " (comp.name LIKE '%" . trim((string)$val) . "%' OR sbu.name LIKE '%" . trim((string)$val) . "%' OR divi.name LIKE '%" . trim((string)$val) . "%' OR dept.name LIKE '%" . trim((string)$val) . "%' OR sec.name LIKE '%" . trim((string)$val) . "%' OR team.name LIKE '%" . trim((string)$val) . "%' OR emp.userfullname LIKE '%" . trim((string)$val) . "%') AND ";
                    } else {
                        $searchQuery .= " m." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        $tableHeaders = array(
            'action'           => 'Action',
            'project_name'     => 'Project Name',
            'participant_type' => 'Participant Type',
            'participant_name' => 'Participant Name',
            'description'      => 'Description',
            'status_text'      => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'projectparticipant', // Controller name
            'extra'          => array(),
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Project Participants',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
            )
        );
    }

    /**
     * Returns the raw Zend_Db_Select object for the Grid.
     * Implements Dynamic Participant Resolution via COALESCE and LEFT JOINs.
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by === 'status_text') {
            $by = 'm.isactive';
        } elseif ($by === 'project_name') {
            $by = 'proj.name';
        } elseif ($by === 'participant_name') {
            $by = 'COALESCE(emp.userfullname, comp.name, sbu.name, divi.name, dept.name, sec.name, team.name)';
        } elseif ($by === 'participant_type') {
            $by = 'm.employee_id'; // Proxy sorting
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('m' => $this->_name),
                array(
                    'id' => 'm.id',
                    'description',
                    'participant_type' => new Zend_Db_Expr("
                        CASE 
                            WHEN m.employee_id IS NOT NULL THEN 'Employee'
                            WHEN m.company_id IS NOT NULL THEN 'Company'
                            WHEN m.sbunit_id IS NOT NULL THEN 'Business Unit'
                            WHEN m.division_id IS NOT NULL THEN 'Division'
                            WHEN m.department_id IS NOT NULL THEN 'Department'
                            WHEN m.section_id IS NOT NULL THEN 'Section'
                            WHEN m.team_id IS NOT NULL THEN 'Team'
                            ELSE 'Unknown' 
                        END
                    "),
                    'participant_name' => new Zend_Db_Expr("COALESCE(emp.userfullname, comp.name, sbu.name, divi.name, dept.name, sec.name, team.name, '—')"),
                    'status_text' => new Zend_Db_Expr("CASE WHEN m.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
                )
            )
            // Primary Project Join
            ->joinInner(
                array('proj' => 'main_project'),
                'm.project_id = proj.id',
                array('project_name' => 'proj.name')
            )
            // Poly-Participant Resolution Joins
            ->joinLeft(array('emp' => 'main_employee'), 'm.employee_id = emp.id', array())
            ->joinLeft(array('comp' => 'main_company'), 'm.company_id = comp.id', array())
            ->joinLeft(array('sbu' => 'main_businessunit'), 'm.sbunit_id = sbu.id', array())
            ->joinLeft(array('divi' => 'main_division'), 'm.division_id = divi.id', array())
            ->joinLeft(array('dept' => 'main_department'), 'm.department_id = dept.id', array())
            ->joinLeft(array('sec' => 'main_section'), 'm.section_id = sec.id', array())
            ->joinLeft(array('team' => 'main_team'), 'm.team_id = team.id', array())
            ->where('m.isactive != ?', 5);

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

    /**
     * Validates uniqueness of a specific Project + Participant combination.
     * Prevents mapping the same department or employee to the same project twice.
     * 
     * @param int $projectId
     * @param array $participantData e.g., ['employee_id' => 15] or ['department_id' => 2]
     * @param int|null $excludeId For edit mode
     * @return bool True if duplicate exists
     */
    public function checkMappingDuplicate($projectId, array $participantData, $excludeId = null)
    {
        $select = $this->select()
            ->where('project_id = ?', (int)$projectId)
            ->where('isactive != ?', 5);

        // Bind the specific participant column being checked
        foreach ($participantData as $col => $val) {
            $select->where("$col = ?", (int)$val);
        }

        if (!empty($excludeId)) {
            $select->where('id != ?', (int)$excludeId);
        }

        return (bool)$this->fetchRow($select);
    }

    public function checkDuplicate($field, $value, $excludeId = null)
    {
        return null; // Not applicable for composite/mapping logic. Use checkMappingDuplicate.
    }

    public function checkInUse($id)
    {
        // Mappings are safe to delete as they are terminal relationships.
        return false;
    }
}
