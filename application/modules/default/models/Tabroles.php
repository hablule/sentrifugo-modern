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

class Default_Model_Tabroles extends Zend_Db_Table_Abstract
{
	protected $_name = ROLE_ARRAY['tablename'];
	protected $_primary = ROLE_ARRAY['colid'];

	/**
	 * This function gives data for grid view.
	 * @parameters
	 * @sort          = ascending or descending
	 * @by            = name of field which to be sort
	 * @pageNo        = page number
	 * @perPage       = no.of records per page
	 * @searchQuery   = search string
	 *
	 * @return  ResultSet;
	 */
	public function getRolesData($sort, $by, $pageNo, $perPage, $searchQuery)
	{

		$where = "r." . ROLE_ARRAY['colisactive'] . " = 'Y'";

		if ($searchQuery)
			$where .= " AND " . $searchQuery;

		$roleData = $this->select()
			->setIntegrityCheck(false)
			->from(array('r' => $this->_name), array('id' => 'r.' . ROLE_ARRAY['colid'], 'name' => 'r.' . ROLE_ARRAY['colname'], 'code' => 'r.' . ROLE_ARRAY['colcode'], 'description' => 'r.' . ROLE_ARRAY['coldescription']))
			->joinInner(array('g' => GROUP_ARRAY['tablename']), "g." . GROUP_ARRAY['colid'] . " = r." . ROLE_ARRAY['colgroupid'] . " and g." . GROUP_ARRAY['colisactive'] . " = 'Y'", array('group_name' => 'g.' . GROUP_ARRAY['colname'] . ''))
			->where($where)
			->order("$by $sort")
			->limitPage($pageNo, $perPage);

		return $roleData;
	}
	/*
	 * This function will return all data of role by passing its primay key id.
	 * @parameters
	 * @id   =  id of role (primary key)
	 *
	 * returns Array of role's data.
	 */
	public function getRoleDataById($id)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('r' => ROLE_ARRAY['tablename']), array('r.' . ROLE_ARRAY['colid'] . ' as id', 'r.*'))
			->where('r.' . ROLE_ARRAY['colisactive'] . ' = "Y" AND r.' . ROLE_ARRAY['colid'] . '=' . $id . ' ');
		//die($select);
		return $this->fetchRow($select)->toArray();
	}
	public function getGroupDataById($id)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('g' => GROUP_ARRAY['tablename']), array('g.' . GROUP_ARRAY['colid'] . ' as id', 'g.' . GROUP_ARRAY['colisactive'] . ' as activename', 'g.*'))
			->where('g.' . GROUP_ARRAY['colisactive'] . ' = "Y" AND g.' . GROUP_ARRAY['colid'] . '=' . $id . ' ');
		return $this->fetchRow($select)->toArray();
	}
	/*
	 * This function is used to save/update data in database.
	 * @parameters
	 * @data  =  array of form data.
	 * @where =  where condition in case of update.
	 *
	 * returns  Primary id when new record inserted,'update' string when a record updated.
	 */
	public function SaveorUpdateRolesData($data, $where)
	{
		if ($where != '') {
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id = $this->getAdapter()->lastInsertId($this->_name);
			return $id;
		}
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
		$role_arr = array();
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]]['roletype'] = $row[ROLE_ARRAY['colcode']];
			$role_arr[$row[ROLE_ARRAY['colid']]]['group_id'] = $row[ROLE_ARRAY['colgroupid']];
		}


		//die();

		return $role_arr;
	}
	/**
	 * This function is used to get all roles for any dropdown list.
	 *
	 * @return Array array of role names and ids.
	 */
	public function getRolesList()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select " . ROLE_ARRAY['colid'] . "," . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " where " . ROLE_ARRAY['colisactive'] . " ='Y' and " . ROLE_ARRAY['colgroupid'] . " is not null order by " . ROLE_ARRAY['colname'];
		$result = $db->query($query);
		$role_arr = array();
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colname']];
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
			if (strpos($type, 'Background Agency') !== false) {

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
		$role_arr = array();
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colname']];
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
		$role_arr = array();
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colname']];
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
		$role_arr = array();
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colname']];
		}

		return $role_arr;
	}
	/**
	 * This function is used to get all roles dropdown list in emp screen.
	 *
	 * @return Array  array of role names and ids,group ids.
	 */
	public function getRolesList_EMP($con = '')
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		if (!empty($con) && $con == 'orghead') {
			$query = "select r." . ROLE_ARRAY['colgroupid'] . ",r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r
                  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colgroupid'] . " is not null 
                  and r." . ROLE_ARRAY['colgroupid'] . " in (" . MANAGEMENT_GROUP . ") order by r." . ROLE_ARRAY['colname'] . " asc";
		} else {
			$query = "select r." . ROLE_ARRAY['colgroupid'] . ",r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r
                  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colgroupid'] . " is not null 
                  and r." . ROLE_ARRAY['colgroupid'] . " not in (" . USERS_GROUP . ") order by r." . ROLE_ARRAY['colname'] . " asc";
		}
		$result = $db->query($query);
		$role_arr = array();
		while ($row = $result->fetch()) {
			$role_arr[ROLE_ARRAY['colid'] . '_' . $row[ROLE_ARRAY['colgroupid']]] = $row[ROLE_ARRAY['colname']];
		}

		return $role_arr;
	}
	public function getRolesList_EMP2()
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('e' => 'main_role'), array('e.*'))
			->where('e.isactive = "Y"');
		return $this->fetchAll($select)->toArray();
	}
	public function getRolesListByGroupID($groupID)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select r." . ROLE_ARRAY['colgroupid'] . ",r." . ROLE_ARRAY['colid'] . ",r." . ROLE_ARRAY['colname'] . " from " . ROLE_ARRAY['tablename'] . " r
                  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' and r." . ROLE_ARRAY['colgroupid'] . " is not null 
                  and r." . ROLE_ARRAY['colgroupid'] . " = " . $groupID . " order by r." . ROLE_ARRAY['colname'] . " asc";
		$result = $db->query($query);
		$role_arr = array();
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
		$role_arr = array();
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
		$role_arr = array();
		$db = Zend_Db_Table::getDefaultAdapter();
		if ($roleId != "") {
			$query = "select " . ROLE_ARRAY['colid'] . "," . ROLE_ARRAY['colgroupid'] . " from " . ROLE_ARRAY['tablename'] . " where " . ROLE_ARRAY['colisactive'] . " = 'Y' and " . ROLE_ARRAY['colid'] . "=" . $roleId;
			$result = $db->query($query);
			$role_arr = array();
			while ($row = $result->fetch()) {
				$role_arr[$row[ROLE_ARRAY['colid']]] = $row[ROLE_ARRAY['colgroupid']];
			}
		}
		return $role_arr;
	}

	public function getRolesDataByID($id)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('r' => ROLE_ARRAY['tablename']), array('r.*'))
			->where('r.' . ROLE_ARRAY['colisactive'] . ' = "Y" AND r.' . ROLE_ARRAY['colid'] . '=' . $id . ' ');
		return $this->fetchAll($select)->toArray();
	}
	/**
	 * This function gives all content for grid view.
	 * @parameters
	 * @param $sort          = ascending or descending
	 * @param $by            = name of field which to be sort
	 * @param $pageNo        = page number
	 * @param $perPage       = no.of records per page
	 * @param $searchData    = search string
	 * @param $call          = type of call like ajax.
	 * @return  Array;
	 */
	public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $p2, $p3, $p4, $p5)
	{
		$group_model = new Default_Model_Groups();
		$searchQuery = '';
		$searchArray = array();
		$data = array();

		if ($searchData != '' && $searchData != 'undefined') {
			$searchValues = json_decode($searchData);
			if (count($searchValues) > 0) {
				foreach ($searchValues as $key => $val) {
					$searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
					$searchArray[$key] = $val;
				}
				$searchQuery = rtrim($searchQuery, " AND");
			}
		}
		$objName = 'tabroles';

		$tableFields = array(
			'action' => 'Action',
			ROLE_ARRAY['colname'] => 'Role Name',
			ROLE_ARRAY['colcode'] => 'Code',
			ROLE_ARRAY['coldescription'] => 'Role Description',
			'group_name' => 'Group'
		);

		$tablecontent = $this->getRolesData($sort, $by, $pageNo, $perPage, $searchQuery);
		$group_data = $group_model->getGroupsListForRoles();
		$group_arr = array();

		foreach ($group_data as $gkey => $gdata) {
			$group_arr[$gdata['name']] = $gdata['name'];
		}
		$dataTmp = array(
			'sort' => $sort,
			'by' => $by,
			'pageNo' => $pageNo,
			'perPage' => $perPage,
			'tablecontent' => $tablecontent,
			'objectname' => $objName,
			'extra' => array(),
			'tableheader' => $tableFields,
			'jsGridFnName' => 'getAjaxgridData',
			'jsFillFnName' => '',
			'searchArray' => $searchArray,
			'call' => $call,
			'dashboardcall' => $dashboardcall,
			'search_filters' => array(
				'group_name' => array(
					'type' => 'select',
					'filter_data' => array('' => 'All') + $group_arr,
				)
			),
		);

		return $dataTmp;
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
		$role_arr = array();
		while ($row = $result->fetch()) {
			$role_arr[$row[ROLE_ARRAY['colid']]]['name'] = $row[ROLE_ARRAY['colname']];
			$role_arr[$row[ROLE_ARRAY['colid']]]['cnt'] = $row['cnt'];
		}

		return $role_arr;
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
		$emp_arr = array();
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
		$empRoleArray = array();
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
	 * This function is used in roles group report to get data.
	 * @param String $sort_name = field name to be sort.
	 * @param String $sort_type = type of sorting
	 * @return Array  Array of groups and their role count.
	 */
	public function getdata_for_rolesgroup_rpt($sort_name, $sort_type)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select r." . ROLE_ARRAY['colgroupid'] . ",r." . ROLE_ARRAY['colname'] . ",count(*) cnt from " . GROUP_ARRAY['tablename'] . " g," . ROLE_ARRAY['tablename'] . " r 
                  where r." . ROLE_ARRAY['colgroupid'] . " = g." . GROUP_ARRAY['colid'] . " and r." . ROLE_ARRAY['colisactive'] . " = 'Y' group by g." . GROUP_ARRAY['colid'] . " order by " . $sort_name . " " . $sort_type;

		$result = $db->query($query);
		$data = $result->fetchAll();

		return $data;
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
	 * This function is used to get data for groups,roles and employee report.
	 * @param String $sort_name = field name to be sort.
	 * @param String $sort_type = sorting type.
	 * @return Array  Array of groups,roles and employee count.
	 */
	public function getdata_emprolesgroup_rpt($sort_name, $sort_type)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select r." . ROLE_ARRAY['colgroupid'] . ",r." . ROLE_ARRAY['colid'] . " role_id,g." . GROUP_ARRAY['colname'] . " AS group_name ,r.name as rolename,count(u.user_id) user_cnt 
                  from " . ROLE_ARRAY['tablename'] . " r 
                  left join main_userrole u on u.role_id = r." . ROLE_ARRAY['colid'] . " and u.isactive ='Y' 
                  inner join " . GROUP_ARRAY['tablename'] . " g on g." . GROUP_ARRAY['colid'] . " = r." . ROLE_ARRAY['colgroupid'] . " 
                  where r." . ROLE_ARRAY['colisactive'] . " = 'Y' group by r." . ROLE_ARRAY['colid'] . " order by r." . $sort_name . " " . $sort_type;

		$result = $db->query($query);

		$data = $result->fetchAll();
		return $data;
	}

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
		$role_arr = array();
		while ($row = $result->fetch()) {
			$role_arr[ROLE_ARRAY['colid'] . '_' . $row[ROLE_ARRAY['colgroupid']]] = $row[ROLE_ARRAY['colname']];
		}

		return $role_arr;
	}
	public function getTotalRolecnt()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select count(" . ROLE_ARRAY['colid'] . ") cnt from " . ROLE_ARRAY['tablename'] . " where " . ROLE_ARRAY['colisactive'] . " = 'Y' and " . ROLE_ARRAY['colgroupid'] . " is not null";
		$result = $db->query($query);
		$row = $result->fetch();
		return $row['cnt'];
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
}//end of class