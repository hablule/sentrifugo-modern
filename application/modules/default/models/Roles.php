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

class Default_Model_Roles extends Zend_Db_Table_Abstract
{
    /* --------------------------------------------------------------
     * Attributes
     * -------------------------------------------------------------- */

	/** @var string Primary table name */
	protected $_name = 'main_role';

	/** @var string Primary key */
	protected $_primary = 'id';

    /* --------------------------------------------------------------
     * Functional Group 1: Core CRUD & Data Retrieval
     * -------------------------------------------------------------- */

	/**
	 * Check if a role name exists
	 */
	public function checkRoleName($roleName)
	{
		$select = $this->select()
			->where('name = ?', trim($roleName))
			->where('isactive != ?', '5');

		return $this->fetchRow($select);
	}

	/**
	 * Soft delete a role record
	 */
	public function deleteRole($id)
	{
		return $this->update(
			['isactive' => 'N'],
			['id = ?' => (int)$id]
		);
	}


	public function getEmpForRoleMail($group_ids, $object_id)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select u.id,u.userfullname,u.emailaddress,r." . ROLE_ARRAY['colname'] . "
                  from main_employees u 
				  inner join  main_users ue on  ue.empid=u.id
				  inner join  main_userrole ur on  ur.user_id=ue.id
				  inner join " . ROLE_ARRAY['tablename'] . " r on r." . ROLE_ARRAY['colid'] . " = ur.role_id 
                  and r." . ROLE_ARRAY['colgroupid'] . " in (" . $group_ids . ") and r." . ROLE_ARRAY['colisactive'] . " = 'Y' 
                  inner join main_privileges p on p.group_id = r." . ROLE_ARRAY['colgroupid'] . " and p.role = r." . ROLE_ARRAY['colid'] . " 
                  and p.object = " . $object_id . " and (p.editpermission = 'Yes' or p.addpermission = 'Yes') and p.isactive = 1 
                  where u.isactive = 1 group by u.id 
                  union 
                  select u.id,u.userfullname,u.emailaddress,r." . ROLE_ARRAY['colname'] . " 
                  from main_employees u 
				  inner join  main_users ue on  ue.empid=u.id
				  inner join  main_userrole ur on  ur.user_id=ue.id
				  inner join " . ROLE_ARRAY['tablename'] . " r on r." . ROLE_ARRAY['colid'] . " = ur.role_id and r." . ROLE_ARRAY['colisactive'] . " = 'Y' 
                  and ur.role_id = " . SUPERADMINROLE . " 
                  where u.isactive = 1  ";
		$result = $db->query($query);
		$emp_arr = [];
		while ($row = $result->fetch()) {
			$emp_arr[$row['id']]['userfullname'] = $row['userfullname'];
			$emp_arr[$row['id']]['emailaddress'] = $row['emailaddress'];
			$emp_arr[$row['id']]['name'] = $row['name'];
		}

		return $emp_arr;
	}

	public function getEmpRoleNamesByIds($empRoleIdArray)
	{
		$resultstring = implode(',', $empRoleIdArray);
		$empRoleArray = [];
		if ($resultstring) {
			try {
				$qry = "select ob." . ROLE_ARRAY['colid'] . ", ob." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " ob
                                        where ob." . ROLE_ARRAY['colid'] . " IN (" . $resultstring . ") and ob." . ROLE_ARRAY['colisactive'] . " = 'Y'";
				$db = Zend_Db_Table::getDefaultAdapter();
				$sqlRes = $db->query($qry);
				$empRoleRes = $sqlRes->fetchAll();

				if (!empty($empRoleRes)) {
					foreach ($empRoleRes as $empRole) {
						$empRoleArray[$empRole['role_id']] = $empRole['name'];
					}
				}
			} catch (Exception $e) {
				echo "Error Encountered - " . $e->getFile();
			}
		}
		return $empRoleArray;
	}

	/**
	 * This function is used in roles group report to get roles data.
	 * @param Integer $group_id = id of group 
	 * @param String $sort_name = field name to be sort.
	 * @param String $sort_type = type of sorting     
	 * @return Array  Array of groups and their role count.
	 */
	public function getdata_for_rolesgroup_popup($group_id, $sort_name, $sort_type)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r 
                  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colgroupid'] . " = " . $group_id . " order by " . $sort_name . " " . $sort_type;

		$result = $db->query($query);
		$data = $result->fetchAll();
		return $data;
	}

	/**
	 * Standard Fetch record for main_group table
	 */
	public function getGroupDataById($id)
	{
		$select = $this->getAdapter()->select()
			->from(['g' => 'main_group'], [
				'id' => 'group_id',
				'activename' => 'isactive',
				'g.*'
			])
			->where('g.group_id = ?', (int)$id);

		$row = $this->getAdapter()->fetchRow($select);
		return $row ?: [];
	}


	public function getRoles($con = '')
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		if (!empty($con) && $con == 'orghead') {
			$query = "select r." . ROLE_ARRAY['colgroupid'] . ",r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r
                  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colgroupid'] . " is not null 
                  and r." . ROLE_ARRAY['colgroupid'] . " in (" . MANAGEMENT_GROUP . ") order by r." . ROLE_ARRAY['colname'] . " asc";
		} else {
			$query = "select r." . ROLE_ARRAY['colgroupid'] . ",r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r
                  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colname'] . " is not null 
                  and r." . ROLE_ARRAY['colgroupid'] . " not in (" . USERS_GROUP . ") order by r." . ROLE_ARRAY['colname'] . " asc";
		}
		$result = $db->query($query);

		return $result->fetchAll();
	}


	/**
	 * Standard Fetch record for roles table
	 */
	public function getRoleDataById($id)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(['r' => $this->_name], ['id' => 'id', 'r.*'])
			->where('r.id = ?', (int)$id);

		$row = $this->fetchRow($select);
		return ($row) ? $row->toArray() : [];
	}

	/**
	 * Detailed Role fetch by ID
	 */
	public function getRolesDataByID($id)
	{
		$select = $this->select()
			->from($this->_name, ['*'])
			->where("isactive = 'Y'")
			->where('id = ?', (int)$id);

		return $this->fetchAll($select)->toArray();
	}

	/**
	 * THE UNIVERSAL ROLE LISTER
	 */
	public function getRolesList($context = '', $asPairs = true, $excludeSA = false, $excludeGN = true)
	{
		$select = $this->select()
			->from($this->_name, ['id', 'name', 'group_id'])
			->where("isactive = 'Y'");

		if ($excludeGN) {
			$select->where('group_id IS NOT NULL');
		}

		if ($excludeSA) {
			$select->where('id != ?', (int)SUPERADMINROLE);
		}

		switch ($context) {
			case 'orghead':
				$select->where('group_id IN (?)', (int)MANAGEMENT_GROUP);
				break;
			case 'um':
			case 'dept':
				$select->where('group_id NOT IN (?)', (int)USERS_GROUP);
				break;
		}

		$select->order('name ASC');
		$results = $this->fetchAll($select)->toArray();

		if (!$asPairs) return $results;

		$role_arr = [];
		foreach ($results as $row) {
			$key = $row['id'] . '_' . $row['group_id'];
			$role_arr[$key] = utf8_encode($row['name']);
		}
		return $role_arr;
	}

	/**
	 * This function is used to get all roles for dropdown list.
	 *
	 * @return Array array of role names and ids.
	 */
	public function getRolesListForUsers($id = '', $type = '')
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		if ($id == '') {

			$query = "select r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " 
			          from main_privileges p 
					  inner join " . ROLE_ARRAY['tablename'] . " r on r." . ROLE_ARRAY['colid'] . " = p.role and r." . ROLE_ARRAY['colisactive'] . " = 'Y' 
					  where p.group_id = " . USERS_GROUP . " and p.isactive = 1  and p.role is not null 
					  and p.role not in (select distinct role from main_privileges where object = " . EMPSCREENING . " and role is not null 
					  and isactive = 'Y' and group_id = " . USERS_GROUP . ") group by r." . ROLE_ARRAY['colid'] . " order by r." . ROLE_ARRAY['colname'];
		} else {
			if (str_contains($type, 'Background Agency')) {

				$query = "select r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from main_privileges p
							inner join " . ROLE_ARRAY['tablename'] . " r on r." . ROLE_ARRAY['colid'] . " = p.role
							where object = " . EMPSCREENING . " and role is not null and p.isactive = 1 and p.group_id = " . USERS_GROUP . ";";
			} else {

				$query = "select r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " 
			          from main_privileges p 
					  inner join " . ROLE_ARRAY['tablename'] . " r on r." . ROLE_ARRAY['colid'] . " = p.role and r." . ROLE_ARRAY['colisactive'] . " = 'Y' 
					  where p.group_id = " . USERS_GROUP . " and p.isactive = 1  and p.role is not null 
					  and p.role not in (select distinct role from main_privileges where object = " . EMPSCREENING . " and role is not null 
					  and isactive = 'Y' and group_id = " . USERS_GROUP . ") group by r." . ROLE_ARRAY['colid'] . " order by r." . ROLE_ARRAY['colname'];
			}
		}

		$result = $db->query($query);
		$role_arr = [];
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colname']];
		}

		return $role_arr;
	}

	/**
	 * This function is used to get all roles dropdown list in department screen.
	 *
	 * @return Array  array of role names and ids,group ids.
	 */
	public function getRolesList_Dept($dept_id = '')
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		if ($dept_id != '')
			$role_str = '';
		else
			$role_str = " and r." . ROLE_ARRAY['colgroupid'] . " in (" . MANAGEMENT_GROUP . ") ";
		$query = "select r." . ROLE_ARRAY['colgroupid'] . ",r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r
				  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colgroupid'] . " is not null $role_str
				  order by r." . ROLE_ARRAY['colname'] . " asc";  //#
		$result = $db->query($query);
		$role_arr = [];
		while ($row = $result->fetch()) {
			$role_arr[ROLE_ARRAY['colid'] . '_' . $row[ROLE_ARRAY['colgroupid']]] = $row[ROLE_ARRAY['colname']];
		}

		return $role_arr;
	}

	/**
	 * This function is used to get all management roles dropdown list in organisation screen.
	 *
	 * @return Array  array of role names and ids.
	 */
	public function getRolesList_orginfo()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r
                      where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colgroupid'] . " is not null 
                      and r." . ROLE_ARRAY['colgroupid'] . " in (" . MANAGEMENT_GROUP . ") order by r." . ROLE_ARRAY['colname'] . " asc";
		$result = $db->query($query);
		$role_arr = [];
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colname']];
		}

		return $role_arr;
	}

	/**
	 * This function is used to get all roles dropdown list by group id.
	 *
	 * @return Array  array of role names and ids.
	 */
	public function getRolesListByGroupID($groupID)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select r." . ROLE_ARRAY['colgroupid'] . ",r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r
                  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colgroupid'] . " is not null 
                  and r." . ROLE_ARRAY['colgroupid'] . " = " . $groupID . " order by r." . ROLE_ARRAY['colname'] . " asc";
		$result = $db->query($query);
		$role_arr = [];
		while ($row = $result->fetch()) {
			$role_arr[ROLE_ARRAY['colid'] . '_' . $row[ROLE_ARRAY['colgroupid']]] = $row[ROLE_ARRAY['colname']];
		}

		return $role_arr;
	}

	/**
	 * This function is used to get all roles dropdown list in emp screen.
	 *
	 * @return Array  array of role names and ids,group ids.
	 */
	public function getRolesList_USERLOG()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select " . ROLE_ARRAY['colid'] . "," . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " where " . ROLE_ARRAY['colisactive'] . " ='Y' order by " . ROLE_ARRAY['colname'];
		$result = $db->query($query);
		$role_arr = [];
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colname']];
		}

		return $role_arr;
	}


	/*
	 * This function is used to get group for a particular  role.
	 *
	 * returns group id
	 */
	public function getRolegroup($roleId)
	{
		$role_arr = [];
		$db = Zend_Db_Table::getDefaultAdapter();
		if ($roleId != "") {
			$query = "select " . ROLE_ARRAY['colid'] . "," . ROLE_ARRAY['colgroupid'] . " from " . ROLE_ARRAY['tablename'] . " where " . ROLE_ARRAY['colisactive'] . " = 'Y' and " . ROLE_ARRAY['colid'] . "=" . $roleId;
			$result = $db->query($query);
			$role_arr = [];
			while ($row = $result->fetch()) {
				$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colgroupid']];
			}
		}
		return $role_arr;
	}


	/**
	 * This function is used to get all roles dropdown list in usermanagement screen.
	 *
	 * @return Array  array of role names and ids.
	 */
	public function getRolesList_UM()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r
                  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colgroupid'] . " is not null 
                  and r." . ROLE_ARRAY['colgroupid'] . " not in (" . USERS_GROUP . ") order by r." . ROLE_ARRAY['colname'] . " asc";
		$result = $db->query($query);
		$role_arr = [];
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colname']];
		}

		return $role_arr;
	}

	/**
	 * This function is used to get all groups for any dropdown list.
	 *
	 * @return Array of role names and ids.
	 */
	public function getRoleListForUserLoginLog()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . ",(select count(*) from main_userloginlog where emprole = r." . ROLE_ARRAY['colid'] . ") cnt
               from " . ROLE_ARRAY['tablename'] . " r where r." . ROLE_ARRAY['colisactive'] . " = 'Y'";
		$result = $db->query($query);
		$role_arr = [];
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]]['name'] = $row[ROLE_ARRAY['colname']];
			$role_arr[$row[ROLE_ARRAY['colid']]]['cnt'] = $row['cnt'];
		}

		return $role_arr;
	}

	/*
	 * This function is used to get role id and role type except admin role i,e id>1
	 *
	 * returns Array of role ids and role types except admin role.
	 */
	public function getRoleTypesForAccess()
	{

		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select " . ROLE_ARRAY['colid'] . "," . ROLE_ARRAY['colcode'] . "," . ROLE_ARRAY['colgroupid'] . " from " . ROLE_ARRAY['tablename'] . " where " . ROLE_ARRAY['colid'] . " > 1 and " . ROLE_ARRAY['colisactive'] . " = 'Y'";
		$result = $db->query($query);

		$role_arr = [];
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]]['roletype'] = $row[ROLE_ARRAY['colcode']];
			$role_arr[$row[ROLE_ARRAY['colid']]]['group_id'] = $row[ROLE_ARRAY['colgroupid']];
		}


		//die();

		return $role_arr;
	}

	/**
	 * Standard Pairs Retrieval for Rulebook Target Population.
	 * Maps the legacy 'Y' status to the SSOT active filtering logic.
	 * 
	 * @param bool $activeOnly
	 * @return array [id => name]
	 */
	public function getPairs($activeOnly = true)
	{
		$select = $this->select()
			->from($this->_name, ['id', 'name'])
			->order('name ASC');

		if ($activeOnly) {
			// Sentrifugo's main_role table uses 'Y'/'N' for status
			$select->where('isactive = ?', 'Y');
		}

		return $this->getAdapter()->fetchPairs($select);
	}

	/**
	 * Unified Save or Update method
	 * @deprecated Use saveRecord() instead for better clarity and type handling
	 */
	public function SaveorUpdateRolesData($data, $where = "")
	{
		if ($where != '') {
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			return $this->getAdapter()->lastInsertId($this->_name);
		}
	}

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

    /* --------------------------------------------------------------
     * Functional Group 2: DataGrid & Listing Logic
     * -------------------------------------------------------------- */

	/**
	 * Centralized grid data provider
	 */
	public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
	{
		$group_model = new Default_Model_Groups();
		$searchQuery = '';
		$searchArray = [];

		if ($searchData != '' && $searchData != 'undefined') {
			$searchValues = json_decode($searchData, true);
			if (!empty($searchValues)) {
				foreach ($searchValues as $key => $val) {
					if ($key == 'status_text') {
						$dbVal = ($val == 'Active') ? 'Y' : (($val == 'Inactive') ? 'N' : $val);
						$searchQuery .= " r.isactive = '" . $dbVal . "' AND ";
					} elseif ($key == 'group_name') {
						$searchQuery .= " g.name = '" . $val . "' AND ";
					} else {
						$searchQuery .= "r." . $key . " LIKE '%" . $val . "%' AND ";
					}
					$searchArray[$key] = $val;
				}
				$searchQuery = rtrim($searchQuery, " AND");
			}
		}

		$tableHeaders = [
			'action'      => 'Action',
			'code'        => 'Code',
			'name'        => 'Role Name',
			'description' => 'Role Description',
			'group_name'  => 'Group',
			'status_text' => 'Status'
		];

		$tablecontent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);
		$group_data = $group_model->getPairs(false);
		$group_arr = [];

		foreach ($group_data as $gkey => $gdata) {
			$group_arr[$gdata] = $gdata;
		}

		$dataTmp = [
			'sort'           => $sort,
			'by'             => $by,
			'pageNo'         => $pageNo,
			'perPage'        => $perPage,
			'tablecontent'   => $tablecontent,
			'objectname'     => 'roles',
			'extra'          => [],
			'tableheader'    => $tableHeaders,
			'jsGridFnName'   => 'getAjaxgridData',
			'jsFillFnName'   => '',
			'searchArray'    => $searchArray,
			'menuName'       => 'Roles',
			'dashboardcall'  => $dashboardcall,
			'call'           => $call,
			'search_filters' => [
				'status_text' => [
					'type'        => 'select',
					'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']
				],
				'group_name' => [
					'type'        => 'select',
					'filter_data' => ['' => 'All'] + $group_arr,
				]
			],
		];

		return $dataTmp;
	}

	/**
	 * Standard Fetch query for DataGrid logic using main_group schema
	 */
	public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery)
	{
		$where = "r.isactive != '5'";
		if (!empty($searchQuery)) {
			$where .= " AND " . $searchQuery;
		}

		$select = $this->select()
			->setIntegrityCheck(false)
			->from(
				['r' => $this->_name],
				[
					'id'          => 'r.id',
					'name'        => 'r.name',
					'code'        => 'r.code',
					'description' => 'r.description',
					'isactive',
					'status_text' => new Zend_Db_Expr("CASE WHEN r.isactive='Y' THEN 'Active' ELSE 'Inactive' END")
				]
			)
			->joinInner(
				['g' => 'main_group'],
				"g.id = r.group_id",
				['group_name' => 'g.name']
			)
			->where($where)
			->order("$by $sort")
			->limitPage((int)$pageNo, (int)$perPage);


		return $select;
	}

    /* --------------------------------------------------------------
     * Functional Group 3: Reporting & Statistical Data
     * -------------------------------------------------------------- */

	/**
	 * Fetches detailed role data for groups/roles/employee reports
	 */
	public function getdata_emprolesgroup_rpt($sort_name, $sort_type)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(['r' => $this->_name], [
				'role_id'  => 'id',
				'rolename' => 'name',
				'group_id' => 'group_id'
			])
			->joinLeft(
				['u' => 'main_userrole'],
				"u.role_id = r.id AND u.isactive = 'Y'",
				['user_cnt' => 'COUNT(u.user_id)']
			)
			->joinInner(
				['g' => 'main_group'],
				'g.id = r.group_id',
				['group_name' => 'name']
			)
			->where("r.isactive = 'Y'")
			->group('r.id')
			->order("$sort_name $sort_type");

		return $this->getAdapter()->fetchAll($select);
	}

	/**
	 * Data for the Roles Group main report
	 */
	public function getdata_for_rolesgroup_rpt($sort_name, $sort_type)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(['r' => $this->_name], [
				'group_id',
				'name',
				'cnt' => 'COUNT(*)'
			])
			->joinInner(
				['g' => 'main_group'],
				'r.group_id = g.id',
				[]
			)
			->where("r.isactive = 'Y'")
			->group('g.id')
			->order("$sort_name $sort_type");

		return $this->getAdapter()->fetchAll($select);
	}

	/**
	 * Total count of active system roles
	 */
	public function getTotalRolecnt()
	{
		$select = $this->select()
			->from($this->_name, ['cnt' => 'COUNT(id)'])
			->where("isactive = 'Y'")
			->where('group_id IS NOT NULL');

		$row = $this->fetchRow($select);
		return (int)$row['cnt'];
	}
}
