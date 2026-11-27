<?php

/*********************************************************************************
 *  This file is part of Sentrifugo-Modern.
 *  Copyright (C) 2026 Hablul.
 ********************************************************************************/

/**
 * Skill Source Model
 *
 * Manages skill acquisition sources (e.g., On-the-Job, Formal Education, Certification).
 * Refactored to comply with SSOT v7.0.0.
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution <https://hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo-Modern v4.0
 */
class Default_Model_Skillsource extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_skill_source';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Retrieves all non-deleted skill source records.
     *
     * @param int|null    $limit
     * @param int|null    $offset
     * @param string|null $search_val
     * @param string|null $search_str
     * @param int|null    $activeStatus
     * @return array
     */
    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');

        if ($activeStatus !== null) {
            $select->where('isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            $allowedCols = ['code', 'name', 'description'];
            if (in_array($search_val, $allowedCols, true)) {
                $select->where("$search_val LIKE ?", '%' . $search_str . '%');
            }
        }

        $select->order('name ASC');

        if ($limit !== null) {
            $select->limit((int)$limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Builds the DataGrid configuration and query for Skill Sources.
     *
     * @param string      $sort
     * @param string      $by
     * @param int         $perPage
     * @param int         $pageNo
     * @param string      $searchData
     * @param string|null $call
     * @param string|null $dashboardcall
     * @return array
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                $adapter     = $this->getAdapter();
                $allowedCols = ['code', 'name', 'description'];
                foreach ($searchValues as $key => $val) {
                    if ($key == 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " ss.isactive = " . $dbVal . " AND ";
                    } elseif (in_array($key, $allowedCols, true)) {
                        $searchQuery .= $adapter->quoteInto(" ss.$key LIKE ?", '%' . $val . '%') . " AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        $tableHeaders = [
            'action'      => 'Action',
            'code'        => 'Source Code',
            'name'        => 'Source Name',
            'description' => 'Description',
            'status_text' => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'skillsource',
            'tableheader'    => $tableHeaders,
            'extra'          => [],
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Skill Sources',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'status_text' => [
                    'type'        => 'select',
                    'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']
                ]
            ]
        ];
    }

    /**
     * Constructs the Zend_Db_Select query for the Skill Source grid.
     *
     * @param string $sort
     * @param string $by
     * @param int    $pageNo
     * @param int    $perPage
     * @param string $searchQuery
     * @return Zend_Db_Table_Select
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') {
            $by = 'ss.isactive';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ss' => $this->_name], [
                'id'          => 'ss.id',
                '*',
                'status_text' => new Zend_Db_Expr("CASE WHEN ss.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->where('ss.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    /**
     * Returns an associative [id => name] pair array for dropdowns.
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'name'])->order('name ASC');
        if ($activeOnly) {
            $select->where('isactive = 1');
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Retrieves a single non-deleted skill source by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    /**
     * Inserts or updates a skill source record with audit metadata.
     *
     * @param array    $data
     * @param int|null $id
     * @return int|string
     */
    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate('Y-m-d H:i:s');
            $this->update($data, ['id = ?' => (int)$id]);
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate('Y-m-d H:i:s');
        $this->insert($data);
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    /**
     * Soft-deletes a skill source after verifying no employee skills reference it.
     *
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception('Cannot delete this Skill Source. It is currently linked to employee skill records.');
        }

        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate('Y-m-d H:i:s')
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /**
     * Toggles active/inactive status.
     *
     * @param int $id
     * @param int $status
     * @return int
     */
    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = [
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate('Y-m-d H:i:s')
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /* =================================================================================
     * GROUP 5: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Checks whether a given field value already exists on a non-deleted record.
     *
     * @param string   $field
     * @param string   $value
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim($value))
            ->where('isactive != 5');

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * Verifies if the skill source is referenced by employee skill records.
     *
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $select = $db->select()
                ->from('main_empskill', 'empskill_id')
                ->where('skillsource_id = ?', (int)$id)
                ->where('isactive = ?', 'Y')
                ->limit(1);
            if ((bool)$db->fetchOne($select)) {
                return true;
            }
        } catch (\Throwable $e) {
            error_log('Skillsource Dependency Check Error: ' . $e->getMessage());
        }
        return false;
    }
}
