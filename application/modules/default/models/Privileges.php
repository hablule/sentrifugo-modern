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
 * Privileges Model
 * Manages Access Control Lists (ACL) and Menu Permissions.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Privileges extends Zend_Db_Table_Abstract
{
    /* --------------------------------------------------------------
     * Attributes
     * -------------------------------------------------------------- */

    /** @var string Table name */
    protected $_name = 'main_privileges';

    /** @var string Primary key */
    protected $_primary = 'id';

    /**
     * Fetch a key-value pair of [Menu_ID => Privilege_ID] for a specific group.
     * Used to quickly determine if a permission needs Update or Insert.
     * 
     * @param int $groupId
     * @return array
     */
    public function getPrivilegeMapByGroup($groupId)
    {
        $select = $this->select()
            ->from($this->_name, ['object', 'id']) // object = Menu ID, id = Privilege ID
            ->where('group_id = ?', (int)$groupId);

        // fetchPairs returns an associative array: [col1 => col2]
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Fetch a key-value pair of [Menu_ID => Privilege_ID] for a specific group.
     * Used to quickly determine if a permission needs Update or Insert.
     * 
     * @param int $groupId
     * @return array
     */
    public function getPrivilegeMapByRole($roleId)
    {
        $select = $this->select()
            ->from($this->_name, ['object', 'id']) // object = Menu ID, id = Privilege ID
            ->where('role = ?', (int)$roleId);

        // fetchPairs returns an associative array: [col1 => col2]
        return $this->getAdapter()->fetchPairs($select);
    }

    /* --------------------------------------------------------------
     * Functional Group 1: Data Retrieval
     * -------------------------------------------------------------- */

    /**
     * Get menu items assigned to a specific Role or Group.
     * 
     * @param int|null $role_id Role ID
     * @param int|null $groupid Group ID
     * @return array
     */
    public function getMenuItemsByRoleId($role_id, $groupid = '')
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from($this->_name)
            ->where('isactive = ?', 1);

        if (!empty($groupid)) {
            $select->where('group_id = ?', (int)$groupid);
        } elseif (!empty($role_id)) {
            $select->where('role = ?', (int)$role_id);
        }
        // If both are empty, it returns all active privileges (Legacy behavior maintained)

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Gets Privileges assigned to a Particular Group.
     * 
     * @param int $groupid
     * @return array 
     */
    public function getMenuItemsByGroupId($groupid = '')
    {
        if (empty($groupid)) {
            return [];
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from($this->_name)
            ->where('group_id = ?', (int)$groupid)
            ->where('isactive = ?', 1);

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Get privileges for specific menu object(s).
     * 
     * @param string|int $objId   Single Menu ID or Comma Separated IDs
     * @param string     $groupId (Not used in original logic, kept for signature compatibility)
     * @param int        $role_id Role ID
     * @param int        $idCsv   Flag: 0 = Single ID, 1 = Multiple IDs (IN clause)
     * @return array|mixed
     */
    public function getObjPrivileges($objId, $groupId = "", $role_id = null, $idCsv = 0)
    {
        if (empty($objId) || empty($role_id)) {
            return [];
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from($this->_name)
            ->where('isactive = ?', 1)
            ->where('role = ?', (int)$role_id);

        // Scenario 1: Fetch Multiple Objects (CSV)
        if ($idCsv == 1) {
            // Explode CSV string to array for safe binding
            $objIds = explode(',', $objId);

            $select->reset(Zend_Db_Select::COLUMNS)
                ->columns(['object', 'addpermission', 'viewpermission', 'viewattachments', 'isactive'])
                ->where('object IN (?)', $objIds);

            return $this->fetchAll($select)->toArray();
        }

        // Scenario 2: Fetch Single Object
        else {
            $select->reset(Zend_Db_Select::COLUMNS)
                ->columns([
                    'addpermission',
                    'editpermission',
                    'deletepermission',
                    'viewpermission',
                    'uploadattachments',
                    'viewattachments',
                    'isactive'
                ])
                ->where('object = ?', (int)$objId);

            $row = $this->fetchRow($select);
            return ($row) ? $row->toArray() : [];
        }
    }

    /* --------------------------------------------------------------
     * Functional Group 2: Persistence (Save/Update)
     * -------------------------------------------------------------- */

    /**
     * Save or Update privileges data.
     * 
     * @param array $data Column data
     * @param string|array $where SQL Where clause or Zend Where Array
     * @return int|string Last Insert ID or 'update'
     */
    public function SaveorUpdatePrivilegesData($data, $where)
    {
        if (!empty($where)) {
            $this->update($data, $where);
            return 'update';
        } else {
            $this->insert($data);
            return $this->getAdapter()->lastInsertId($this->_name);
        }
    }

    /**
     * Hard delete a privilege record by ID.
     * 
     * @param int $id
     * @return int Number of rows deleted
     */
    public function deletePrivilege($id)
    {
        return $this->delete(['id = ?' => (int)$id]);
    }

    /**
     * Cascade Delete: Removes privileges for all Roles in a Group for a specific Menu.
     * Used when a menu is removed from the Group.
     * 
     * @param int $groupId
     * @param int $menuId
     */
    public function deleteRolePrivilegesForGroupObject($groupId, $menuId)
    {
        // 1. Find all Role IDs belonging to this Group
        $roleSelect = $this->select()
            ->setIntegrityCheck(false)
            ->from(['r' => 'main_role'], ['id'])
            ->where('r.group_id = ?', (int)$groupId);

        $roleIds = $this->getAdapter()->fetchCol($roleSelect);

        if (!empty($roleIds)) {
            // 2. Delete privileges for these roles for this specific menu object
            $where = [
                'object = ?' => (int)$menuId,
                'role IN (?)' => $roleIds
            ];
            $this->delete($where);
        }
    }

    /**
     * Cascade Update: Downgrades Role permissions if Group permission is set to 'No'.
     * Used when a specific permission (e.g., 'Edit') is removed from the Group.
     * 
     * @param int $groupId
     * @param int $menuId
     * @param array $groupData The new data array being saved for the group
     */
    public function enforceGroupLimitsOnRoles($groupId, $menuId, $groupData)
    {
        // 1. Find all Role IDs belonging to this Group
        $roleSelect = $this->select()
            ->setIntegrityCheck(false)
            ->from(['r' => 'main_role'], ['id'])
            ->where('r.group_id = ?', (int)$groupId);

        $roleIds = $this->getAdapter()->fetchCol($roleSelect);

        if (empty($roleIds)) {
            return;
        }

        // 2. Prepare update data for Roles
        // We only want to update fields that are 'No' in the group. 
        // If Group says 'Yes', we leave the Role as is (it might be 'Yes' or 'No').
        // If Group says 'No', the Role MUST be 'No'.
        $roleUpdateData = [];
        $cols = ['addpermission', 'editpermission', 'deletepermission', 'viewpermission', 'uploadattachments', 'viewattachments'];

        foreach ($cols as $col) {
            if (isset($groupData[$col]) && $groupData[$col] === 'No') {
                $roleUpdateData[$col] = 'No';
            }
        }

        // 3. Apply updates if there are restrictions to enforce
        if (!empty($roleUpdateData)) {
            $roleUpdateData['modifieddate'] = gmdate("Y-m-d H:i:s");

            $where = [
                'object = ?'  => (int)$menuId,
                'role IN (?)' => $roleIds
            ];

            $this->update($roleUpdateData, $where);
        }
    }
}
