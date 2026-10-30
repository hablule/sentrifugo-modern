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
 * Menu Model
 * Manages system navigation menus and their base configurations.
 * Refactored for SSOT v2.4 (Security, Zend_Db_Select, Standardization).
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Menu extends Zend_Db_Table_Abstract
{
    /** @var string Table name */
    protected $_name = 'main_menu';
    
    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 1: SSOT STANDARD CORE METHODS
     * ================================================================================= */

    /**
     * Check for duplicate values in a specific column.
     * 
     * @param string $field
     * @param string $value
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
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
     * Soft delete a menu record.
     * 
     * @param int $id
     * @return int
     */
    public function deleteRecord($id)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array(
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /**
     * Fetch all records with optional filtering and pagination.
     * 
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $search_val
     * @param string|null $search_str
     * @param int|null $activeStatus
     * @return array
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
     * SSOT Grid Configuration Method.
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
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            if (!empty($searchValues)) {
                foreach ($searchValues as $key => $val) {
                    if ($key == 'isactive') {
                        $dbVal = ($val == 'Yes') ? 1 : 0;
                        $searchQuery .= " m.isactive = " . $dbVal . " AND ";
                    } else {
                        $searchQuery .= " m." . $key . " LIKE '%" . $val . "%' AND ";
                    }
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }

        $tableHeaders = array(
            'action'     => 'Action',
            'menuName'   => 'Menu Name',
            'url'        => 'URL',
            'modulename' => 'Module Name',
            'isactive'   => 'Active'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort, 'by' => $by, 'pageNo' => $pageNo, 'perPage' => $perPage,
            'tablecontent'   => $tableContent, 'objectname' => 'menu', 'tableheader' => $tableHeaders,
            'extra'          => array(), 'jsGridFnName' => 'getAjaxgridData', 'jsFillFnName' => '',
            'searchArray'    => $searchArray, 'menuName' => 'Menu Management', 'call' => $call, 
            'dashboardcall'  => $dashboardcall, 
            'search_filters' => array(
                'isactive' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Yes' => 'Yes', 'No' => 'No'))
            )
        );
    }

    /**
     * SSOT Grid Data Fetcher.
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
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('m' => $this->_name), 
                array('*', 'isactive' => new Zend_Db_Expr("IF(m.isactive = 1, 'Yes', 'No')"))
            )
            ->where('m.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);
        return $select;
    }

    /**
     * Fetch ID => Name pairs for dropdowns.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'menuName'))->order('menuName ASC');
        if ($activeOnly) {
            $select->where('isactive = 1');
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Single record retrieval.
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
     * Fetch records by Parent ID.
     * 
     * @param int $parentId
     * @param bool $activeOnly
     * @return array
     */
    public function getRecordsByParentId($parentId, $activeOnly = true)
    {
        $select = $this->select()->where('parent = ?', (int)$parentId)->where('isactive != 5');
        if ($activeOnly) {
            $select->where('isactive = 1');
        }
        return $this->fetchAll($select)->toArray();
    }

    /**
     * Standard persistence method.
     * 
     * @param array $data
     * @param int|null $id
     * @return mixed
     */
    public function saveRecord($data, $id = null)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        
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

    /**
     * Toggle active status.
     * 
     * @param int $id
     * @param int $status
     * @return int
     */
    public function toggleStatus($id, $status)
    {
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $data = array('isactive' => $status, 'modifiedby' => $userId, 'modifieddate' => gmdate("Y-m-d H:i:s"));
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL (Legacy & Specific)
     * ================================================================================= */

    /**
     * @deprecated Use getRecordById() instead.
     * 
     * @param int $menuid
     * @return array
     */
    public function checkmenustatus($menuid)
    {
        $select = $this->select()->where('id = ?', (int)$menuid);
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : array();
    }

    /**
     * Fetches controllers related to a particular role id.
     * Refactored to Zend_Db_Select and bound parameters.
     * 
     * @param int $role_id
     * @param string|int $group_id
     * @return array
     */
    public function getControllersByRole($role_id, $group_id = '')
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('m' => $this->_name), array(
                'menuName'   => 'm.menuName',
                'url'        => new Zend_Db_Expr("SUBSTRING(m.url, 2)"),
                'nurl'       => new Zend_Db_Expr("SUBSTR(m.url, LOCATE('/', m.url) + 1, (CHAR_LENGTH(m.url) - LOCATE('/', REVERSE(m.url)) - LOCATE('/', m.url)))"),
                'vurl'       => new Zend_Db_Expr("SUBSTR(m.url, CHAR_LENGTH(SUBSTRING_INDEX(m.url, '/', 2)) + 2)"),
                'modulename' => 'm.modulename'
            ))
            ->joinInner(array('p' => 'main_privileges'), 'p.object = m.id', array(
                'addpermission', 'editpermission', 'deletepermission', 
                'viewpermission', 'uploadattachments', 'viewattachments'
            ))
            ->where("m.url != '/#'")
            ->where("m.isactive = 1")
            ->where("p.isactive = 1")
            ->where("p.role = ?", (int)$role_id);

        $results = $this->fetchAll($select)->toArray();
        $url_arr = array();

        foreach ($results as $row) {
            $modulename = empty($row['modulename']) ? 'default' : $row['modulename'];
            if ($modulename == 'default') {
                $url = empty($row['nurl']) ? $row['url'] : $row['nurl'];
            } else {
                $url = empty($row['vurl']) ? $row['url'] : $row['vurl'];
            }
            
            $url_arr[$url . "controller.php"]['url'] = $url;
            $url_arr[$url . "controller.php"]['modulename'] = $modulename;
            
            if ($url != 'servicerequests') {
                $url_arr[$url . "controller.php"]['actions'] = $this->getControllersByRole_helper($row);
            } else {
                $service_actions = sapp_Global::generateAccessControl_helper(array('servicerequestscontroller.php' => array('url' => 'servicerequests')), 'random');
                $url_arr[$url . "controller.php"]['actions'] = $service_actions['servicerequestscontroller.php'];
            }
        }

        // Standard index arrays
        $index_arr = array();
        $index_arr['indexcontroller.php'] = array('url' => 'index', 'modulename' => 'default');
        $index_arr['indexcontroller.php']['actions'] = sapp_Global::generateAccessControl_helper(array('indexcontroller.php' => array('url' => 'index')), 'random')['indexcontroller.php'];

        $index_arr['dashboardcontroller.php'] = array('url' => 'dashboard', 'modulename' => 'default');
        $index_arr['dashboardcontroller.php']['actions'] = sapp_Global::generateAccessControl_helper(array('dashboardcontroller.php' => array('url' => 'dashboard')), 'random')['dashboardcontroller.php'];

        if ($role_id == SUPERADMINROLE) {
            $index_arr['managemenuscontroller.php'] = array('url' => 'managemenus', 'modulename' => 'default');
            $index_arr['managemenuscontroller.php']['actions'] = sapp_Global::generateAccessControl_helper(array('managemenuscontroller.php' => array('url' => 'managemenus')), 'random')['managemenuscontroller.php'];

            $index_arr['wizardcontroller.php'] = array('url' => 'wizard', 'modulename' => 'default');
            $index_arr['wizardcontroller.php']['actions'] = sapp_Global::generateAccessControl_helper(array('wizardcontroller.php' => array('url' => 'wizard')), 'random')['wizardcontroller.php'];
        }

        if (!empty($group_id) && $group_id == HR_GROUP) {
            $index_arr['hrwizardcontroller.php'] = array('url' => 'hrwizard', 'modulename' => 'default');
            $index_arr['hrwizardcontroller.php']['actions'] = sapp_Global::generateAccessControl_helper(array('hrwizardcontroller.php' => array('url' => 'hrwizard')), 'random')['hrwizardcontroller.php'];
        }

        return $url_arr + $index_arr;
    }

    /**
     * Gets menus containing specific hardcoded IDs in nav_ids.
     * 
     * @return array
     */
    public function getExcludedMenuids()
    {
        $select = $this->select()
            ->from($this->_name, array('id', 'menuName', 'nav_ids'))
            ->where("nav_ids LIKE '%,2,%' OR nav_ids LIKE '%,70,%' OR nav_ids LIKE '%,131,%'");
            
        return $this->fetchAll($select)->toArray();
    }

    /**
     * Fetch full menu tree using Recursion.
     * 
     * @return array Nested array of menus
     */
    public function getFullMenuTree()
    {
        $select = $this->select()
            ->from($this->_name, array('id', 'parent', 'menuName', 'iconPath', 'url'))
            ->where('isactive = ?', 1)
            ->where('parent IS NOT NULL')
            ->order(array('parent ASC', 'menuOrder ASC'));

        $menus = $this->fetchAll($select)->toArray();
        $grouped = array();

        $defaultPermissions = array(
            'addpermission'     => 'Yes', 'editpermission'    => 'Yes',
            'deletepermission'  => 'Yes', 'viewpermission'    => 'Yes',
            'uploadattachments' => 'Yes', 'viewattachments'   => 'Yes'
        );

        foreach ($menus as $menu) {
            $menu['default_permissions'] = $defaultPermissions;
            $parentId = (int)$menu['parent'];
            if (!isset($grouped[$parentId])) {
                $grouped[$parentId] = array();
            }
            $grouped[$parentId][] = $menu;
        }

        return $this->_buildTreeRecursive($grouped, 0);
    }

    /**
     * @deprecated Use getMenuArray_formenu() instead.
     * 
     * @param int $role_id
     * @param int $group_id
     * @return array
     */
    public function getMenuArray($role_id, $group_id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('m' => $this->_name), array('*'))
            ->joinInner(
                array('mp' => 'main_privileges'),
                'm.id = mp.object',
                array('addpermission', 'editpermission', 'deletepermission', 'viewpermission', 'uploadattachments', 'viewattachments')
            )
            ->where('m.isactive IN (?)', array(1, 2))
            ->where('m.parent IS NOT NULL')
            ->where('mp.isactive = ?', 1);

        if ($role_id == SUPERADMINROLE) {
            $select->where('mp.role = ?', (int)$role_id)
                   ->where('mp.group_id IS NULL');
        } else {
            $select->where('mp.role IS NULL')
                   ->where('mp.group_id = ?', (int)$group_id);
        }

        $select->order(array('m.parent ASC', 'm.menuOrder ASC'));
        return $this->fetchAll($select)->toArray();
    }

    /**
     * Get clickable menus for activity log report.
     * 
     * @return array
     */
    public function getMenuArrayActivityLogReport()
    {
        $noOperationMenus = array(APPROVEDLEAVES, CANCELLEAVES, EMPLOYEETABS, EMPLEAVESUMMARY, MYHOLIDAYCALENDAR, HEIRARCHY, STRUCTURE, MYEMPLOYEES, REJECTEDLEAVES, REJECTEDREQUISITIONS, REPORTS, VENDORSCREENING, MYDETAILS);
        
        $select = $this->select()
            ->where("url != '/#'")
            ->where("id NOT IN (?)", $noOperationMenus)
            ->where("isactive = 1")
            ->order("menuName ASC");

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Fetch menu array based on group ID permissions.
     * 
     * @param int $group_id
     * @return array
     */
    public function getMenuArrayByGroup($group_id)
    {
        if (empty($group_id)) return array();

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('m' => $this->_name), array('*'))
            ->joinInner(
                array('mp' => 'main_privileges'),
                'm.id = mp.object',
                array('addpermission', 'editpermission', 'deletepermission', 'viewpermission', 'uploadattachments', 'viewattachments')
            )
            ->where('m.isactive IN (?)', array(1, 2))
            ->where('mp.isactive = ?', 1)
            ->where('mp.group_id = ?', (int)$group_id)
            ->where('m.parent IS NOT NULL')
            ->order(array('m.parent ASC', 'm.menuOrder ASC'));

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Retrieves grouped active menus for structural mapping.
     * 
     * @return array
     */
    public function getMenuArraygroup()
    {
        $select = $this->select()
            ->where('isactive IN (?)', array(1, 2))
            ->where('parent IS NOT NULL')
            ->order(array('parent ASC', 'menuOrder ASC'));

        return $this->fetchAll($select)->toArray();
    }

    /**
     * This function gives all menus in an array, joined with specific privileges.
     *
     * @param int $role_id
     * @param int $group_id
     * @return array
     */
    public function getMenuArray_formenu($role_id, $group_id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('m' => $this->_name),
                array('*') // Select all columns from main_menu
            )
            ->joinInner(
                array('mp' => 'main_privileges'),
                'm.id = mp.object',
                array(
                    'addpermission',
                    'editpermission',
                    'deletepermission',
                    'viewpermission',
                    'uploadattachments',
                    'viewattachments'
                )
            )
            // m.isactive in (1,2) handles different active states if applicable in your system
            ->where('m.isactive IN (?)', array(1, 2))
            ->where('m.parent IS NOT NULL')
            ->where('mp.isactive = ?', 1)
            ->where('mp.role = ?', (int)$role_id);

        // Handle Super Admin Edge Case
        /* if ($role_id == SUPERADMINROLE) {
            $select->where('mp.group_id IS NULL');
        } else {
            $select->where('mp.group_id = ?', (int)$group_id);
        } */

        if (!$role_id && $group_id > 0) {
            $select->where('mp.group_id = ?', (int)$group_id);
        }

        $select->order(array('m.parent ASC', 'm.menuOrder ASC'));

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Get internal menu ID by URL.
     * 
     * @param string $url
     * @return array
     */
    public function getMenuObjID($url)
    {
        $select = $this->select()
            ->from($this->_name, array('id'))
            ->where('url = ?', $url);
        return $this->fetchAll($select)->toArray();
    }

    /**
     * Fetch menu + privilege data for a GROUP (Master Layer).
     * 
     * @param int $groupId
     * @return array
     */
    public function getMenuPrivilegesByGroup($groupId)
    {
        $db = $this->getAdapter();

        $select = $db->select()
            ->from(
                array('m' => $this->_name),
                array('menu_id' => 'm.id', 'menuName' => 'm.menuName', 'parent' => 'm.parent', 'menuOrder' => 'm.menuOrder', 'url' => 'm.url', 'modulename' => 'm.modulename',
                      'isactive' => new Zend_Db_Expr("CASE WHEN p.id IS NOT NULL AND p.isactive = 1 THEN 'Active' ELSE 'InActive' END"))
            )
            ->joinLeft(
                array('p' => 'main_privileges'),
                $db->quoteInto('p.object = m.id AND p.group_id = ?', (int)$groupId),
                array(
                    'privilege_id' => 'p.id', 'group_id' => 'p.group_id',
                    'addpermission' => new Zend_Db_Expr("IF(p.addpermission = 'Yes', 'Active', 'InActive')"),
                    'editpermission' => new Zend_Db_Expr("IF(p.editpermission = 'Yes', 'Active', 'InActive')"),
                    'deletepermission' => new Zend_Db_Expr("IF(p.deletepermission = 'Yes', 'Active', 'InActive')"),
                    'viewpermission' => new Zend_Db_Expr("IF(p.viewpermission = 'Yes', 'Active', 'InActive')"),
                    'uploadattachments' => new Zend_Db_Expr("IF(p.uploadattachments = 'Yes', 'Active', 'InActive')"),
                    'viewattachments' => new Zend_Db_Expr("IF(p.viewattachments = 'Yes', 'Active', 'InActive')")
                )
            )
            ->where('m.isactive = ?', 1)
            ->where('m.parent >= ?', 0)
            ->where('p.role IS NULL OR p.role = 0')
            ->order(array('m.parent ASC', 'm.menuOrder ASC'));

        return $db->fetchAll($select);
    }

    /**
     * Fetch menu + privilege data for a ROLE.
     * Enforces that Role permissions are a subset of Group permissions.
     *
     * @param int $roleId
     * @param int $groupId
     * @return array
     */
    public function getMenuPrivilegesByRole($roleId, $groupId)
    {
        $db = $this->getAdapter();

        $select = $db->select()
            ->from(
                array('m' => $this->_name),
                array('menu_id' => 'm.id', 'menuName' => 'm.menuName', 'parent' => 'm.parent', 'menuOrder' => 'm.menuOrder', 'url' => 'm.url', 'modulename' => 'm.modulename',
                      'isactive' => new Zend_Db_Expr("CASE WHEN rp.isactive = 1 AND gp.isactive = 1 THEN 'Active' ELSE 'InActive' END"))
            )
            ->joinInner(
                array('gp' => 'main_privileges'),
                $db->quoteInto('gp.object = m.id AND gp.group_id = ? AND gp.isactive = 1', (int)$groupId),
                array()
            )
            ->joinLeft(
                array('rp' => 'main_privileges'),
                $db->quoteInto('rp.object = m.id AND rp.role = ?', (int)$roleId),
                array(
                    'privilege_id' => 'rp.id', 'role_id' => 'rp.role', 'group_id' => 'gp.group_id',
                    'addpermission' => new Zend_Db_Expr("CASE WHEN rp.addpermission = 'Yes' AND gp.addpermission = 'Yes' THEN 'Active' ELSE 'InActive' END"),
                    'adddisabled' => new Zend_Db_Expr("CASE WHEN gp.addpermission = 'No' THEN 'Yes' ELSE '' END"),
                    'editpermission' => new Zend_Db_Expr("CASE WHEN rp.editpermission = 'Yes' AND gp.editpermission = 'Yes' THEN 'Active' ELSE 'InActive' END"),
                    'editdisabled' => new Zend_Db_Expr("CASE WHEN gp.editpermission = 'No' THEN 'Yes' ELSE '' END"),
                    'deletepermission' => new Zend_Db_Expr("CASE WHEN rp.deletepermission = 'Yes' AND gp.deletepermission = 'Yes' THEN 'Active' ELSE 'InActive' END"),
                    'deletedisabled' => new Zend_Db_Expr("CASE WHEN gp.deletepermission = 'No' THEN 'Yes' ELSE '' END"),
                    'viewpermission' => new Zend_Db_Expr("CASE WHEN rp.viewpermission = 'Yes' AND gp.viewpermission = 'Yes' THEN 'Active' ELSE 'InActive' END"),
                    'viewdisabled' => new Zend_Db_Expr("CASE WHEN gp.viewpermission = 'No' THEN 'Yes' ELSE '' END"),
                    'uploadattachments' => new Zend_Db_Expr("CASE WHEN rp.uploadattachments = 'Yes' AND gp.uploadattachments = 'Yes' THEN 'Active' ELSE 'InActive' END"),
                    'uploaddisabled' => new Zend_Db_Expr("CASE WHEN gp.uploadattachments = 'No' THEN 'Yes' ELSE '' END"),
                    'viewattachments' => new Zend_Db_Expr("CASE WHEN rp.viewattachments = 'Yes' AND gp.viewattachments = 'Yes' THEN 'Active' ELSE 'InActive' END"),
                    'viewupdisabled' => new Zend_Db_Expr("CASE WHEN gp.viewattachments = 'No' THEN 'Yes' ELSE '' END")
                )
            )
            ->where('m.isactive = ?', 1)
            ->where('m.parent >= ?', 0)
            ->order(array('m.parent ASC', 'm.menuOrder ASC'));

        return $db->fetchAll($select);
    }

    /**
     * @deprecated Use getRecordById() instead.
     */
    public function getMenusDataById($id)
    {
        $select = $this->select()->from($this->_name, array('url'))->where('id = ?', (int)$id);
        return $this->fetchAll($select)->toArray();
    }

    /**
     * Get menu names and IDs with log counts for activity log dropdown.
     * Refactored to use LEFT JOIN instead of correlated subquery.
     * 
     * @return array
     */
    public function getMenusListForActivitylog()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('g' => $this->_name), array('id', 'menuname'))
            ->joinLeft(
                array('l' => 'main_logmanager'),
                'g.id = l.menuId AND l.is_active = 1',
                array('cnt' => new Zend_Db_Expr('COUNT(l.id)'))
            )
            ->where('g.isactive = ?', 1)
            ->group('g.id');

        $result = $this->fetchAll($select);
        $menu_arr = array();
        foreach ($result as $row) {
            $menu_arr[$row->id] = array(
                'menuname' => $row->menuname,
                'cnt'      => $row->cnt
            );
        }
        return $menu_arr;
    }

    /**
     * Get menu names in string format by IDs.
     * 
     * @param string $menuIds Comma separated IDs
     * @return string
     */
    public function getMenusNamesByIds($menuIds)
    {
        if (empty($menuIds)) return "";
        
        $idArray = explode(',', $menuIds);
        $select = $this->select()
            ->from($this->_name, array('menuname'))
            ->where('id IN (?)', $idArray)
            ->where('isactive = ?', 1);

        $names = $this->getAdapter()->fetchCol($select);
        return implode(',', $names);
    }

    /**
     * Fetch array of descendant menu IDs for a given parent menu ID.
     * 
     * @param int $menu_id
     * @return array
     */
    public function getMenusWithChilds($menu_id)
    {
        $allMenus = $this->getTotalMenuArray();
        $childs_array = array((int)$menu_id);

        $this->_findChildrenRecursive((int)$menu_id, $allMenus, $childs_array);

        return $childs_array;
    }

    /**
     * Get parent menus only.
     * 
     * @return array
     */
    public function getParentMenus()
    {
        $select = $this->select()->where('parent = 0 OR parent IS NULL');
        return $this->fetchAll($select)->toArray();
    }

    /**
     * Retrieves all top-level menus for radial display.
     * 
     * @return array
     */
    public function getTopLevelMenus()
    {
        $select = $this->select()
            ->from($this->_name, array('id', 'menuName', 'iconPath', 'isactive'))
            ->where('parent = 0 OR parent IS NULL')
            ->order('menuOrder ASC');

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Fetch all menus sorted by hierarchy.
     * 
     * @return array
     */
    public function getTotalMenuArray()
    {
        $select = $this->select()->order(array('parent ASC', 'menuOrder ASC'));
        return $this->fetchAll($select)->toArray();
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE (Legacy & Specific)
     * ================================================================================= */

    /**
     * Custom Upsert implementation for logmanager.
     * 
     * @param int $menuId
     * @param int $actionflag
     * @param string $jsonlogarr
     * @param int $userid
     * @param string $menuNames
     * @return int
     */
    public function addOrUpdateMenuLogManager($menuId, $actionflag, $jsonlogarr, $userid, $menuNames)
    {
        $date = gmdate("Y-m-d H:i:s");
        $db = $this->getAdapter();
        
        $sql = "INSERT INTO `main_logmanager` 
                    (menuId, user_action, log_details, last_modifiedby, last_modifieddate, key_flag, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1) 
                ON DUPLICATE KEY UPDATE 
                    log_details = CONCAT(log_details, ',', ?), 
                    key_flag = ?, 
                    last_modifiedby = ?, 
                    last_modifieddate = ?";
                    
        $db->query($sql, array(
            $menuId, $actionflag, $jsonlogarr, $userid, $date, $menuNames,
            $jsonlogarr, $menuNames, $userid, $date
        ));

        return $db->lastInsertId('main_logmanager');
    }

    /**
     * Cascades an INACTIVE status down to all menus belonging to a specific Menu Group.
     * 
     * @param int $menuGroupId
     * @return int
     */
    public function cascadeGroupDeactivation($menuGroupId)
    {
        if (empty($menuGroupId)) return 0;
        $data = array('isactive' => 0);
        $where = array('menugroup_id = ?' => (int)$menuGroupId, 'isactive = ?' => 1);
        return $this->update($data, $where);
    }

    /**
     * @deprecated Use specific Zend_Db_Table_Abstract update/saveRecord methods instead.
     */
    public function UpdateMenus($querystring)
    {
        $db = $this->getAdapter();
        $db->query($querystring);
    }

    /**
     * @deprecated Use update() method directly.
     * Deactivates menus not in the provided CSV list.
     * 
     * @param string $resarr
     */
    public function UpdateMenuTable($resarr)
    {
        if (empty($resarr)) return;
        $idArray = explode(',', $resarr);
        $this->update(array('isactive' => 0), array('id NOT IN (?)' => $idArray));
    }

    /* =================================================================================
     * GROUP 4: AJAX & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Internal recursive function to build branch mapping.
     */
    protected function _buildMenuBranch(array $groupedMenus, $parentId)
    {
        $branch = array();
        if (!isset($groupedMenus[$parentId])) return $branch;

        foreach ($groupedMenus[$parentId] as $row) {
            $menuId = $row['menu_id'];
            $node = array(
                'menu_ID'          => $menuId,
                'priv_ID'          => $row['privilege_id'],
                'grp_ID'           => $row['group_id'],
                'mnu_Name'         => $row['menuName'],
                'parent_ID'        => $row['parent'],
                'active'           => $row['isactive'],
                'canadd'           => $row['addpermission'],
                'adddisabled'      => (!empty($row['adddisabled']) && $row['adddisabled'] == 'Yes') ? 'disabled' : '',
                'canedit'          => $row['editpermission'],
                'editdisabled'     => (!empty($row['editdisabled']) && $row['editdisabled'] == 'Yes') ? 'disabled' : '',
                'canview'          => $row['viewpermission'],
                'viewdisabled'     => (!empty($row['viewdisabled']) && $row['viewdisabled'] == 'Yes') ? 'disabled' : '',
                'candelete'        => $row['deletepermission'],
                'deletedisabled'   => (!empty($row['deletedisabled']) && $row['deletedisabled'] == 'Yes') ? 'disabled' : '',
                'canupload'        => $row['uploadattachments'],
                'uploaddisabled'   => (!empty($row['uploaddisabled']) && $row['uploaddisabled'] == 'Yes') ? 'disabled' : '',
                'canviewupload'    => $row['viewattachments'],
                'viewupdisabled'   => (!empty($row['viewupdisabled']) && $row['viewupdisabled'] == 'Yes') ? 'disabled' : ''
            );

            $children = $this->_buildMenuBranch($groupedMenus, $menuId);
            if (!empty($children)) {
                $node['child'] = $children;
            }
            $branch[$menuId] = $node;
        }
        return $branch;
    }

    /**
     * Recursive Helper to build tree by value.
     */
    private function _buildTreeRecursive($grouped, $parentId)
    {
        $branch = array();
        if (isset($grouped[$parentId])) {
            foreach ($grouped[$parentId] as $node) {
                $childs = $this->_buildTreeRecursive($grouped, $node['id']);
                if (!empty($childs)) {
                    $node['childs'] = $childs;
                }
                $branch[$node['id']] = $node;
            }
        }
        return $branch;
    }

    /**
     * Recursively populates children IDs for getMenusWithChilds.
     */
    private function _findChildrenRecursive($parentId, $allMenus, &$childrenIds)
    {
        foreach ($allMenus as $menu) {
            if ($menu['parent'] == $parentId) {
                $childrenIds[] = $menu['id'];
                $this->_findChildrenRecursive($menu['id'], $allMenus, $childrenIds);
            }
        }
    }

    /**
     * Check if a key exists anywhere in a multidimensional array.
     */
    protected function array_key_exists_recursive($key, $array)
    {
        if (array_key_exists($key, $array)) return true;
        foreach ($array as $element) {
            if (is_array($element)) {
                if ($this->array_key_exists_recursive($key, $element)) return true;
            }
        }
        return false;
    }

    /**
     * Convert flat menu array to nested tree.
     */
    public function buildMenuTree(array $menuRows)
    {
        usort($menuRows, function ($a, $b) {
            if ($a['parent'] == $b['parent']) {
                return $a['menuOrder'] <=> $b['menuOrder'];
            }
            return $a['parent'] <=> $b['parent'];
        });

        $grouped = array();
        foreach ($menuRows as $row) {
            $grouped[$row['parent']][] = $row;
        }
        return $this->_buildMenuBranch($grouped, 0);
    }

    /**
     * Helper mapping for getControllersByRole.
     */
    public function getControllersByRole_helper($row)
    {
        $permi_arr = array();
        if ($row['addpermission'] == 'Yes') $permi_arr[] = 'add';
        if ($row['editpermission'] == 'Yes') $permi_arr[] = 'edit';
        if ($row['deletepermission'] == 'Yes') $permi_arr[] = 'delete';
        if ($row['viewpermission'] == 'Yes') $permi_arr[] = 'view';
        if ($row['uploadattachments'] == 'Yes') $permi_arr[] = 'upload';
        if ($row['viewattachments'] == 'Yes') $permi_arr[] = 'uploadview';
        $permi_arr[] = $row['menuName'];
        return $permi_arr;
    }

    /**
     * Retrieves specific legacy structure for roles UI.
     */
    public function getgroupmenu($group_id, $role_id, $id)
    {
        $priveleges_model = new Default_Model_Privileges();
        $group_model = new Default_Model_Groups();
        $group_data = $group_model->getRecordById($group_id);

        $res = $this->getMenuArray_formenu($role_id, $group_id);
        
        $tmpArr = array();
        $tmpMenuArr = array();
        $tmpMenuDataArr = array();

        foreach ($res as $row) {
            $tmpMenuArr[$row['id']] = $row['parent'];
            $tmp = array(
                'id' => $row['id'],
                'parent' => $row['parent'],
                'menuName' => $this->getMenuText($row['menuName'], $role_id),
                'iconPath' => $row['iconPath'],
                'url' => $row['url'],
                'default_permissions' => array(
                    'addpermission' => $row['addpermission'],
                    'editpermission' => $row['editpermission'],
                    'deletepermission' => $row['deletepermission'],
                    'viewpermission' => $row['viewpermission'],
                    'uploadattachments' => $row['uploadattachments'],
                    'viewattachments' => $row['viewattachments']
                )
            );
            $tmpMenuDataArr[$row['id']] = $tmp;
        }

        foreach ($tmpMenuArr as $key => $value) {
            if ($value == 0 && !array_key_exists($value, $tmpArr)) {
                $tmpArr[$key] = $tmpMenuDataArr[$key];
            } else if (array_key_exists($value, $tmpArr) && !array_key_exists($key, $tmpArr[$value])) {
                $tmpArr[$value]['childs'][$key] = $tmpMenuDataArr[$key];
            }
        }

        foreach ($tmpArr as $key => $value_arr) {
            if (is_array($value_arr) && isset($value_arr['childs']) && is_array($value_arr['childs'])) {
                foreach ($value_arr['childs'] as $c_key => $ch_value) {
                    foreach ($tmpMenuArr as $tkey => $tvalue) {
                        if ($tvalue == $c_key && !array_key_exists($tkey, $tmpArr)) {
                            $tmpArr[$key]['childs'][$c_key]['childs'][$tkey] = $tmpMenuDataArr[$tkey];
                        }
                    }
                }
            }
        }

        $menu_data = array();
        $menu_data_post = array();
        $permission_data = array();
        
        if (!empty($id)) {
            $mdata = $priveleges_model->getRecordsByRole($id);
            foreach ($mdata as $val_arr) {
                $menu_data[] = $val_arr['object'];
                $menu_data_post[$val_arr['id']] = $val_arr['object'];
                if (isset($tmpMenuArr[$val_arr['object']]) && $tmpMenuArr[$val_arr['object']] != 0) {
                    $menu_data[] = $tmpMenuArr[$val_arr['object']];
                }
                $permission_data[$val_arr['object']] = array(
                    'addpermission' => $val_arr['addpermission'],
                    'editpermission' => $val_arr['editpermission'],
                    'deletepermission' => $val_arr['deletepermission'],
                    'viewpermission' => $val_arr['viewpermission'],
                    'uploadattachments' => $val_arr['uploadattachments'],
                    'viewattachments' => $val_arr['viewattachments']
                );
            }
            $menu_data1 = array();
            foreach ($menu_data as $menu_id) {
                if (array_key_exists($menu_id, $tmpMenuArr)) {
                    $menu_data1[] = $tmpMenuArr[$menu_id];
                }
            }
            $menu_data = array_merge_recursive(array_unique($menu_data), array_unique($menu_data1));
        }

        return array(
            'tmpArr' => $tmpArr, 'menu_data_post' => $menu_data_post,
            'menu_data' => $menu_data, 'permission_data' => $permission_data, 'group_data' => $group_data
        );
    }

    /**
     * Retrieves specific legacy structure for mobile services.
     */
    public function getgroupmenu_service($group_id, $role_id)
    {
        $default_parent_menu = sapp_Global::mobile_parent_menus();
        $default_child_menu = sapp_Global::mobile_child_menus();

        $res = $this->getMenuArray_formenu($role_id, $group_id);
        $tmpArr = array();
        $tmpMenuArr = array();
        $tmpMenuDataArr = array();

        foreach ($res as $row) {
            $tmpMenuArr[$row['id']] = $row['parent'];
            $tmpMenuDataArr[$row['id']] = array('id' => $row['id'], 'menuName' => $row['menuName']);
        }

        foreach ($tmpMenuArr as $key => $value) {
            if ($value == 0 && !array_key_exists($value, $tmpArr) && in_array($key, $default_parent_menu)) {
                $tmpArr[$key] = $tmpMenuDataArr[$key];
            } else if (array_key_exists($value, $tmpArr) && !array_key_exists($key, $tmpArr[$value])) {
                $tmpArr[$value]['childs'][$key] = $tmpMenuDataArr[$key];
            }
        }

        foreach ($tmpArr as $key => $value_arr) {
            if (is_array($value_arr) && isset($value_arr['childs']) && is_array($value_arr['childs'])) {
                foreach ($value_arr['childs'] as $c_key => $ch_value) {
                    foreach ($tmpMenuArr as $tkey => $tvalue) {
                        if ($tvalue == $c_key && !array_key_exists($tkey, $tmpArr) && in_array($tkey, $default_child_menu)) {
                            $tmpArr[$key]['childs'][$tkey] = $tmpMenuDataArr[$tkey];
                        }
                    }
                }
            }
        }

        foreach ($tmpArr as $key => $value_arr) {
            if (is_array($value_arr) && isset($value_arr['childs']) && is_array($value_arr['childs'])) {
                $new_childs = array();
                foreach ($value_arr['childs'] as $c_key => $ch_value) {
                    if (in_array($c_key, $default_child_menu)) {
                        $new_childs[$c_key] = $ch_value;
                    }
                }
                $tmpArr[$key]['childs'] = $new_childs;
            }
        }
        return $tmpArr;
    }

    /**
     * Retrieves specific legacy structure for group UI.
     */
    public function getgroupmenuforgroup($group_id, $role_id, $id)
    {
        $priveleges_model = new Default_Model_Privileges();
        $group_model = new Default_Model_Groups();

        $group_data = $group_model->getRecordById($group_id);
        $res = $this->getMenuArraygroup();

        $tmpArr = array();
        $tmpMenuArr = array();
        $tmpMenuDataArr = array();

        foreach ($res as $row) {
            $tmpMenuArr[$row['id']] = $row['parent'];
            $tmpMenuDataArr[$row['id']] = array(
                'id' => $row['id'], 'parent' => $row['parent'], 'menuName' => $row['menuName'],
                'iconPath' => $row['iconPath'], 'url' => $row['url'],
                'default_permissions' => array(
                    'addpermission' => 'Yes', 'editpermission' => 'Yes', 'deletepermission' => 'Yes',
                    'viewpermission' => 'Yes', 'uploadattachments' => 'Yes', 'viewattachments' => 'Yes'
                )
            );
        }

        $tmpArr = $this->getFullMenuTree();
        $menu_data = array();
        $menu_data_post = array();
        $permission_data = array();

        if (!empty($group_id)) {
            $mdata = $priveleges_model->getRecordsByGroupId($group_id);
            foreach ($mdata as $val_arr) {
                $menu_data[] = $val_arr['object'];
                $menu_data_post[$val_arr['id']] = $val_arr['object'];
                if (isset($tmpMenuArr[$val_arr['object']]) && $tmpMenuArr[$val_arr['object']] != 0) {
                    $menu_data[] = $tmpMenuArr[$val_arr['object']];
                }
                $permission_data[$val_arr['object']] = array(
                    'addpermission' => $val_arr['addpermission'],
                    'editpermission' => $val_arr['editpermission'],
                    'deletepermission' => $val_arr['deletepermission'],
                    'viewpermission' => $val_arr['viewpermission'],
                    'uploadattachments' => $val_arr['uploadattachments'],
                    'viewattachments' => $val_arr['viewattachments']
                );
            }
            $menu_data1 = array();
            foreach ($menu_data as $menu_id) {
                if (array_key_exists($menu_id, $tmpMenuArr)) {
                    $menu_data1[] = $tmpMenuArr[$menu_id];
                }
            }
            $menu_data = array_merge_recursive(array_unique($menu_data), array_unique($menu_data1));
        }

        return array(
            'tmpArr' => $tmpArr, 'menu_data_post' => $menu_data_post,
            'menu_data' => $menu_data, 'permission_data' => $permission_data, 'group_data' => $group_data
        );
    }

    /**
     * Translates menu text conditionally.
     */
    public function getMenuText($menu_name, $group_id = '')
    {
        if ($menu_name == 'Scheduled Interviews') {
            if (empty($group_id)) {
                $auth = Zend_Auth::getInstance();
                $group_id = $auth->getStorage()->read()->group_id;
            }
            if (in_array($group_id, array(MANAGEMENT_GROUP, MANAGER_GROUP, EMPLOYEE_GROUP)) || empty($group_id)) {
                return 'Scheduled Interviews';
            }
        }
        return $menu_name;
    }
}