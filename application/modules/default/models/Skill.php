<?php

/*********************************************************************************
 *  This file is part of Sentrifugo-Modern.
 *  Copyright (C) 2026 Hablul.
 ********************************************************************************/

/**
 * Skill Model
 *
 * Manages the N-level hierarchical skill taxonomy (`main_skill`) using
 * materialized paths (`nav_ids`) and lexicographical sort paths (`order_path`).
 * Refactored to comply with SSOT v7.0.0.
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution <https://hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo-Modern v4.0
 */
class Default_Model_Skill extends Zend_Db_Table_Abstract
{
    /** @var string Table name (SSOT II.1 Singular Naming) */
    protected $_name = 'main_skill';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Retrieves all non-deleted skill records ordered by tree hierarchy.
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
            $allowedCols = ['code', 'name', 'taxonomy_code', 'description'];
            if (in_array($search_val, $allowedCols, true)) {
                $select->where("$search_val LIKE ?", '%' . $search_str . '%');
            }
        }

        $select->order(['order_path ASC', 'name ASC']);

        if ($limit !== null) {
            $select->limit((int)$limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Builds the DataGrid configuration and query for Skills, including
     * breadcrumb parent filtering and contextual tree sorting (SSOT IV.14-IV.16).
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
        $searchQuery      = '';
        $searchArray      = [];
        $hasParentFilter  = false;

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (is_array($searchValues)) {
                $adapter     = $this->getAdapter();
                $allowedCols = ['code', 'name', 'taxonomy_code'];
                foreach ($searchValues as $key => $val) {
                    if ($key == 'status_text') {
                        $dbVal = ($val === 'Active') ? 1 : 0;
                        $searchQuery .= " s.isactive = " . $dbVal . " AND ";
                    } elseif ($key == 'node_type') {
                        $isSum = ($val === 'Summary Group') ? 1 : 0;
                        $searchQuery .= " s.is_summary = " . $isSum . " AND ";
                    } elseif ($key == 'parent_name' && !empty($val)) {
                        // SSOT IV.14: Materialized path subtree filter
                        $hasParentFilter = true;
                        $searchQuery .= $adapter->quoteInto(" s.nav_ids LIKE ?", '%,' . (int)$val . ',%') . " AND ";
                    } elseif ($key == 'category_name' && !empty($val)) {
                        $searchQuery .= " s.skill_category_id = " . (int)$val . " AND ";
                    } elseif ($key == 'measurement_name' && !empty($val)) {
                        $searchQuery .= " s.measurement_id = " . (int)$val . " AND ";
                    } elseif (in_array($key, $allowedCols, true)) {
                        $searchQuery .= $adapter->quoteInto(" s.$key LIKE ?", '%' . $val . '%') . " AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND ");
            }
        }

        // SSOT IV.16: Override sorting to preserve tree hierarchy when filtering by parent
        if ($hasParentFilter) {
            $by   = 'order_path';
            $sort = 'ASC';
        }

        $tableHeaders = [
            'action'           => 'Action',
            'code'             => 'Skill Code',
            'name'             => 'Skill Name',
            'parent_name'      => 'Parent Group',
            'category_name'    => 'Category',
            'measurement_name' => 'Measurement',
            'node_type'        => 'Node Type',
            'status_text'      => 'Status'
        ];

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        $catModel  = new Default_Model_Skillcategory();
        $measModel = new Default_Model_Measurement();

        $parentFilterData = ['' => 'All'] + $this->getSummaryBreadcrumbPairs(null, false);
        $catFilterData    = ['' => 'All'] + $catModel->getPairs(false);
        $measFilterData   = ['' => 'All'] + $measModel->getPairs(false);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'skill',
            'tableheader'    => $tableHeaders,
            'extra'          => [],
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Skills',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => [
                'parent_name' => [
                    'type'        => 'select',
                    'filter_data' => $parentFilterData
                ],
                'category_name' => [
                    'type'        => 'select',
                    'filter_data' => $catFilterData
                ],
                'measurement_name' => [
                    'type'        => 'select',
                    'filter_data' => $measFilterData
                ],
                'node_type' => [
                    'type'        => 'select',
                    'filter_data' => ['' => 'All', 'Summary Group' => 'Summary Group', 'Leaf Skill' => 'Leaf Skill']
                ],
                'status_text' => [
                    'type'        => 'select',
                    'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']
                ]
            ]
        ];
    }

    /**
     * Constructs the Zend_Db_Select query for the Skill DataGrid.
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
            $by = 's.isactive';
        } elseif ($by == 'node_type') {
            $by = 's.is_summary';
        } elseif ($by == 'parent_name') {
            $by = 'p.name';
        } elseif ($by == 'category_name') {
            $by = 'sc.name';
        } elseif ($by == 'measurement_name') {
            $by = 'm.name';
        } elseif ($by == 'order_path') {
            $by = 's.order_path';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['s' => $this->_name], [
                'id'          => 's.id',
                'code'        => 's.code',
                'name'        => 's.name',
                'order_path'  => 's.order_path',
                'node_type'   => new Zend_Db_Expr("CASE WHEN s.is_summary = 1 THEN 'Summary Group' ELSE 'Leaf Skill' END"),
                'status_text' => new Zend_Db_Expr("CASE WHEN s.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
            ])
            ->joinLeft(['p' => $this->_name], 's.parent_id = p.id', [
                'parent_name' => new Zend_Db_Expr("COALESCE(p.name, 'Root Level')")
            ])
            ->joinInner(['sc' => 'main_skill_category'], 's.skill_category_id = sc.id', [
                'category_name' => 'sc.name'
            ])
            ->joinInner(['m' => 'main_measurement'], 's.measurement_id = m.id', [
                'measurement_name' => 'm.name'
            ])
            ->where('s.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    /**
     * Returns leaf skills formatted with full hierarchical breadcrumb paths for dropdowns.
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getLeafBreadcrumbPairs($activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, ['id', 'parent_id', 'name', 'is_summary'])
            ->where('isactive != 5')
            ->order('order_path ASC');

        if ($activeOnly) {
            $select->where('isactive = 1');
        }

        $rows = $this->fetchAll($select)->toArray();
        $map  = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row;
        }

        $pairs = [];
        foreach ($rows as $row) {
            if ((int)$row['is_summary'] !== 0) {
                continue;
            }
            $pairs[(int)$row['id']] = $this->_buildBreadcrumbLabel((int)$row['id'], $map);
        }

        return $pairs;
    }

    /**
     * Returns an associative [id => name] pair array for dropdowns.
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, ['id', 'name'])
            ->where('isactive != 5')
            ->order(['order_path ASC', 'name ASC']);
        if ($activeOnly) {
            $select->where('isactive = 1');
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Returns an associative [id => name] pair array filtered by Skill Category (SSOT IV.6).
     *
     * @param int  $skillCategoryId
     * @param bool $activeOnly
     * @return array
     */
    public function getPairsBySkillcategory($skillCategoryId, $activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, ['id', 'name'])
            ->where('skill_category_id = ?', (int)$skillCategoryId)
            ->where('isactive != 5')
            ->order(['order_path ASC', 'name ASC']);
        if ($activeOnly) {
            $select->where('isactive = 1');
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Retrieves a single non-deleted skill by ID.
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

    /**
     * Returns Summary Group nodes formatted as Breadcrumb Paths (Parent > Child)
     * for parent selectors and tree filters (SSOT IV.15).
     * Excludes `$excludeId` and all its descendants to prevent circular loops.
     *
     * @param int|null $excludeId
     * @param bool     $activeOnly
     * @return array
     */
    public function getSummaryBreadcrumbPairs($excludeId = null, $activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, ['id', 'parent_id', 'name', 'nav_ids'])
            ->where('is_summary = 1')
            ->where('isactive != 5')
            ->order('order_path ASC');

        if ($activeOnly) {
            $select->where('isactive = 1');
        }

        if (!empty($excludeId)) {
            $select->where('id != ?', (int)$excludeId)
                ->where('nav_ids NOT LIKE ?', '%,' . (int)$excludeId . ',%');
        }

        $rows = $this->fetchAll($select)->toArray();
        $map  = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row;
        }

        $pairs = [];
        foreach ($rows as $row) {
            $pairs[(int)$row['id']] = $this->_buildBreadcrumbLabel((int)$row['id'], $map);
        }

        return $pairs;
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    /**
     * Inserts or updates a skill record and synchronizes materialized hierarchy paths.
     *
     * @param array    $data
     * @param int|null $id
     * @return int
     */
    public function saveRecord(array $data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate('Y-m-d H:i:s');
            $this->update($data, ['id = ?' => (int)$id]);
            $recordId = (int)$id;
        } else {
            $data['nav_ids']     = '';
            $data['order_path']  = '';
            $data['createdby']   = $userId;
            $data['createddate'] = gmdate('Y-m-d H:i:s');
            $this->insert($data);
            $recordId = (int)$this->getAdapter()->lastInsertId($this->_name);
        }

        $parentNavIds   = '';
        $parentOrderPath = '';
        if (!empty($data['parent_id'])) {
            $parent = $this->getRecordById((int)$data['parent_id']);
            if ($parent) {
                $parentNavIds    = $parent['nav_ids'];
                $parentOrderPath = $parent['order_path'];
            }
        }

        $this->updateHierarchyPaths($recordId, $parentNavIds, $parentOrderPath);

        return $recordId;
    }

    /**
     * Atomically updates both `nav_ids` and `order_path` on a node and
     * recursively cascades updates down its entire subtree (SSOT IV.13).
     *
     * @param int    $id
     * @param string $parentNavIds
     * @param string $parentOrderPath
     * @return void
     */
    public function updateHierarchyPaths($id, $parentNavIds = '', $parentOrderPath = '')
    {
        $node = $this->getRecordById((int)$id);
        if (!$node) {
            return;
        }

        $sortOrder  = max(1, (int)$node['sort_order']);
        $paddedStep = str_pad((string)$sortOrder, 5, '0', STR_PAD_LEFT);

        $newNavIds = ($parentNavIds !== '')
            ? rtrim($parentNavIds, ',') . ',' . (int)$id . ','
            : ',' . (int)$id . ',';

        $newOrderPath = ($parentOrderPath !== '')
            ? $parentOrderPath . '.' . $paddedStep
            : $paddedStep;

        $this->update([
            'nav_ids'    => $newNavIds,
            'order_path' => $newOrderPath
        ], ['id = ?' => (int)$id]);

        $childrenSelect = $this->select()
            ->from($this->_name, ['id'])
            ->where('parent_id = ?', (int)$id)
            ->where('isactive != 5')
            ->order(['sort_order ASC', 'id ASC']);

        $children = $this->fetchAll($childrenSelect)->toArray();
        foreach ($children as $child) {
            $this->updateHierarchyPaths((int)$child['id'], $newNavIds, $newOrderPath);
        }
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    /**
     * Soft-deletes a skill after verifying no active child nodes or dependents exist.
     *
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function deleteRecord($id)
    {
        if ($this->hasActiveChildren($id)) {
            throw new Exception('Cannot delete this Skill Group. Please remove or reassign its child skills first.');
        }

        if ($this->checkInUse($id)) {
            throw new Exception('Cannot delete this Skill. It is currently linked to employee skill profiles or appraisal questions.');
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
     * Verifies if the skill is referenced by employee skills or PA appraisal questions.
     *
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        try {
            $empSkillSelect = $db->select()
                ->from('main_empskill', 'empskill_id')
                ->where('skill_id = ?', (int)$id)
                ->where('isactive = ?', 'Y')
                ->limit(1);
            if ((bool)$db->fetchOne($empSkillSelect)) {
                return true;
            }

            $paSelect = $db->select()
                ->from('map_pa_question_skill', 'question_id')
                ->where('skill_id = ?', (int)$id)
                ->where('isactive != 5')
                ->limit(1);
            if ((bool)$db->fetchOne($paSelect)) {
                return true;
            }
        } catch (\Throwable $e) {
            error_log('Skill Dependency Check Error: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Checks whether a skill node has any non-deleted child skills.
     *
     * @param int $id
     * @return bool
     */
    public function hasActiveChildren($id)
    {
        $select = $this->select()
            ->from($this->_name, ['id'])
            ->where('parent_id = ?', (int)$id)
            ->where('isactive != 5')
            ->limit(1);

        return (bool)$this->getAdapter()->fetchOne($select);
    }

    /**
     * Checks if assigning `$candidateParentId` to `$nodeId` would create a cycle.
     *
     * @param int      $nodeId
     * @param int|null $candidateParentId
     * @return bool
     */
    public function isDescendantOrSelf($nodeId, $candidateParentId)
    {
        if (empty($nodeId) || empty($candidateParentId)) {
            return false;
        }
        if ((int)$nodeId === (int)$candidateParentId) {
            return true;
        }

        $candidate = $this->getRecordById((int)$candidateParentId);
        if (!$candidate) {
            return false;
        }

        return strpos((string)$candidate['nav_ids'], ',' . (int)$nodeId . ',') !== false;
    }

    /**
     * Recursively constructs a `Parent > Child` breadcrumb string from an in-memory map.
     *
     * @param int   $nodeId
     * @param array $map
     * @return string
     */
    private function _buildBreadcrumbLabel($nodeId, array $map)
    {
        $parts   = [];
        $current = $nodeId;
        $guard   = 0;

        while (!empty($current) && isset($map[$current]) && $guard < 20) {
            array_unshift($parts, $map[$current]['name']);
            $current = !empty($map[$current]['parent_id']) ? (int)$map[$current]['parent_id'] : null;
            $guard++;
        }

        return implode(' > ', $parts);
    }
}
