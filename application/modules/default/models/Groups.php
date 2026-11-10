<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/
/**
 * Sentrifugo - Open Source HRMS
 *
 * @category   Sentrifugo
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @modified  Sentrifugo v3.2
 * @date	  June 2024
 */
class Default_Model_Groups extends Zend_Db_Table_Abstract
{


    protected $_name = 'main_group';
    protected $_primary = 'id';

    /* --------------------------------------------------------------
     * 1. Persistence & CRUD 
     * -------------------------------------------------------------- */

    /**
     * Save or Update a record.
     * 
     * @param array $data
     * @param int|null $id
     * @return int|string Last Insert ID or 'update'
     */
    public function saveRecord($data, $id = null)
    {
        if (!empty($id)) {
            $this->update($data, ['id = ?' => (int)$id]);
            return 'update';
        } else {
            $this->insert($data);
            return $this->getAdapter()->lastInsertId($this->_name);
        }
    }


    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id);
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /**
     * Soft Delete (isactive = 5)
     */
    public function deleteRecord($id)
    {
        $data = [
            'isactive' => '5',
            'modified' => gmdate("Y-m-d H:i:s")
        ];
        return $this->update($data, ['id = ?' => (int)$id]);
    }

    /**
     * hard Delete 
     */
    public function distroyRecord($id)
    {
        return $this->delete(['id = ?' => (int)$id]);
    }



    /* --------------------------------------------------------------
     * Functional Group 2: Data Display & Grid Retrieval
     * -------------------------------------------------------------- 
     * */

    /**
     * Functional Group: Data Display & Retrieval
     * Method 1: The Public Grid Configuration
     * 
     * @param string $sort
     * @param string $by
     * @param int $perPage
     * @param int $pageNo
     * @param string $searchData
     * @param string $call
     * @param string $dashboardcall
     * @return array
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (!empty($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key == 'status_text') {
                        $dbVal = ($val == 'Active') ? 'Y' : (($val == 'Inactive') ? 'N' : $val);
                        $searchQuery .= " g.isactive LIKE '%" . $dbVal . "%' AND ";
                    } else {
                        $searchQuery .= " g." . $key . " LIKE '%" . $val . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }

        $tableHeaders = [
            'action'      => 'Action',
            'name'        => 'Group Name',
            'code'        => 'Code',
            'description' => 'Description',
            'status_text' => 'Status'
        ];

        // Fetch Query Object
        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return [
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent, // Returns Zend_Db_Select
            'objectname'     => 'groups',
            'extra'          => [],
            'tableheader'    => $tableHeaders,
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            'menuName'       => 'Groups',
            'dashboardcall'  => $dashboardcall,
            'call'           => $call,
            'search_filters' => [
                'status_text' => [
                    'type'        => 'select',
                    'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']
                ]
            ]
        ];
    }


    /**
     * Method 2: The Internal Data Fetcher
     * Returns the Zend_Db_Select object (Query), NOT the result array.
     * 
     * @param string $sort
     * @param string $by
     * @param int $pageNo
     * @param int $perPage
     * @param string $searchQuery
     * @return Zend_Db_Select
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        $where = "g.isactive != '5'";
        if (!empty($searchQuery)) {
            $where .= " AND " . $searchQuery;
        }

        if ($by == 'status_text') $by = 'isactive';

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['g' => $this->_name],
                [
                    'id',
                    'name',
                    'code',
                    'description',
                    'isactive',
                    'status_text' => new Zend_Db_Expr("CASE WHEN g.isactive='Y' THEN 'Active' ELSE 'Inactive' END")
                ]
            )
            ->where($where)
            ->order("$by $sort")
            ->limitPage((int)$pageNo, (int)$perPage);

        return $select; // Returns Query Object
    }

    /* --------------------------------------------------------------
     * Functional Group 3: Collection Retrieval
     * -------------------------------------------------------------- */

    public function getActiveGroupsForSelect()
    {
        return $this->getPairs(true);
    }

    /**
     * Standard Pairs Retrieval
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'name']);
        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }
        $select->order('name ASC');

        $results = $this->fetchAll($select);
        $data = [];
        foreach ($results as $row) {
            $data[$row['id']] = $row['name'];
        }
        return $data;
    }

    /**
     * Group Lister For populating dropdowns with id => name pairs, with option to filter only active records.
     */
    public function getDataList($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, ['id', 'name']);
        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }
        $select->order('name ASC');

        $results = $this->fetchAll($select)->toArray();        
        
        return $results;
    }




    /**
     * Get Groups List for Roles with Count of Active Roles
     * @return array
     * 
     */
    #[Deprecated]
    public function getGroupsListForRoles()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                ['g' => $this->_name],
                ['id', 'group_name' => 'g.name']
            )
            ->joinLeft(
                ['r' => 'main_role'],
                "g.id = r.group_id AND r.isactive = 'Y'",
                ['cnt' => new Zend_Db_Expr('COUNT(r.id)')]
            )
            ->where("g.isactive = 'Y'")
            ->group('g.id')
            ->order('g.name ASC');

        $result = $this->fetchAll($select);

        $group_arr = [];
        foreach ($result as $row) {
            $group_arr[$row['id']] = [
                'group_name' => $row['group_name'],
                'cnt'        => $row['cnt']
            ];
        }

        return $group_arr;
    }

    /**
     * Get Groups List for Roles View
     * @return array
     */
    public function getGroupsListForRolesview($activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, ['id', 'name'])
            ->order('displayorder ASC');

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        return $this->fetchAll($select)->toArray();
    }

    /* --------------------------------------------------------------
     * Functional Group 4: Specific Record Retrieval
     * -------------------------------------------------------------- */

    /**
     * Get Group Data by ID
     * @param int $id
     * @return array
     */
    public function getGroupDataById($id)
    {
        $select = $this->select()
            ->where('id = ?', (int)$id);

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : [];
    }

    /**
     * Get Group Data by Code
     * @param string $code
     * @return array
     */
    public function getGroupByCode($code, $activeOnly = true)
    {
        $select = $this->select()
            ->where('code = ?', $code);

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : [];
    }

    /**
     * Get Group Data by Name
     * @param string $code
     * @return array
     */
    public function getGroupByName($name, $activeOnly = true)
    {
        $select = $this->select()
            ->where('name = ?', $name);

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : [];
    }



    /* --------------------------------------------------------------
     * Functional Group 5: Integrity & Helper Logic
     * -------------------------------------------------------------- */

    public function checkName($name, $excludeId = null,  $activeOnly = true)
    {
        $select = $this->select()
            ->where('name = ?', trim($name));

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }
    
    public function checkCode($code, $excludeId = null,  $activeOnly = true)
    {
        $select = $this->select()
            ->where('code = ?', trim($code));

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }

        return $this->fetchRow($select);
    }

    public function getNextDisplayOrder()
    {
        $select = $this->select()
            ->from($this->_name, ['max_order' => new Zend_Db_Expr('MAX(displayorder)')]);

        $row = $this->fetchRow($select);
        return ($row && $row['max_order']) ? ($row['max_order'] + 1) : 1;
    }

    public function getTotalGroupCount($activeOnly = true)
    {
        $select = $this->select()
            ->from(
                $this->_name,
                ['cnt' => new Zend_Db_Expr('COUNT(id)')]
            );

        if ($activeOnly) {
            $select->where("isactive = 'Y'");
        }

        $row = $this->fetchRow($select);
        return ($row) ? (int)$row['cnt'] : 0;
    }
}
