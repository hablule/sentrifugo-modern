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


class Default_Model_Users extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_users';
	protected $_primary = 'id';


	/**
	 * Check if a username is a Ldap user
	 *
	 * @param string $username
	 * @return boolean
	 */
	public function getData($sort, $by, $pageNo, $perPage, $searchQuery, $a)
	{
		// $where = "1";
		if ($a != '') {
			$where = 'empid=' . $a;
		}

		if ($searchQuery && $a != '')
			$where .= "  AND " . $searchQuery;
		else
			$where .= " " . $searchQuery;

		if ($where == " ") {
			$where = '1';
		}


		$bank = $this->select()
			->setIntegrityCheck(false)
			->from('main_users', array('code as code', 'username as username', 'description as description', 'if(isactive=1,"Yes","No") as isactive', 'id as id', 'empid as empid'))
			->order("$by $sort")
			->where($where)
			->limitPage($pageNo, $perPage);

		return $bank;
	}

	public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
	{
		try {

			$searchQuery = '';
			if ($a != '') {
				$objName = 'empuser';
			} else {
				$objName = 'user';
			}
			$searchArray = array();
			if ($searchData != '' && $searchData != 'undefined') {
				$searchValues = json_decode($searchData);
				foreach ($searchValues as $key => $val) {
					$searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
					$searchArray[$key] = $val;
				}
				$searchQuery = rtrim($searchQuery, " AND");
			}
			$tableFields = array(
				'action' => 'Action',
				'code' => 'Code',
				'username' => ' User Name',
				'description' => 'Description',
				'isactive' => 'Active',
				// 'password' => 'Password',

			);

			$tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery, $a);
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
				'add' => 'add',
				'menuName' => 'Users',
				'jsFillFnName' => '',
				'searchArray' => $searchArray,
				'call' => $call,
				'dashboardcall' => $dashboardcall,
				'search_filters' => array(
					'isactive' => array(
						'type' => 'select',
						'filter_data' => array('' => 'All', 'Y' => 'Yes', 'N' => 'No')
					),
				)
			);
			if ($a != '') {
				$dataTmp = array(
					'sort' => $sort,
					'by' => $by,
					'pageNo' => $pageNo,
					'perPage' => $perPage,
					'tablecontent' => $tablecontent,
					'objectname' => $objName,
					'extra' => array(),
					'add' => 'add',
					'tableheader' => $tableFields,
					'jsGridFnName' => 'getEmployeeAjaxgridData',
					'jsFillFnName' => '',
					'userAction' => $c,
					'unitId' => $a,
					'menuName' => 'Employee User',
					'context' => $b,
					'formgrid' => 'true',
					'searchArray' => $searchArray,
					'call' => $call,
					'dashboardcall' => $dashboardcall,
					'search_filters' => array(
						'isactive' => array(
							'type' => 'select',
							'filter_data' => array('' => 'All', 'Y' => 'Yes', 'N' => 'No')
						),
					)
				);
			}
			return $dataTmp;
			//code...
		} catch (\Throwable $th) {

			// Zend_Debug::dump($th->getMessage());
			Zend_Debug::dump($th->getMessage());
		}
	}
	// public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
	// {
	//     $searchQuery = '';
	//     $searchArray = array();
	//     if ($searchData != '' && $searchData != 'undefined') {
	//         $searchValues = json_decode($searchData);
	//         foreach ($searchValues as $key => $val) {
	//             $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
	//             $searchArray[$key] = $val;
	//         }
	//         $searchQuery = rtrim($searchQuery, " AND");
	//     }
	//     $objName = 'user';
	//     $tableFields = array(
	//         'action' => 'Action',
	//         'code' => 'Code',
	//         'username' => ' User Name',
	//         'description' => 'Description',
	//         'isactive' => 'Active',
	// 		'password' => 'Password',

	//     );

	//     $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery);
	//     $dataTmp = array(
	//         'sort' => $sort,
	//         'by' => $by,
	//         'pageNo' => $pageNo,
	//         'perPage' => $perPage,
	//         'tablecontent' => $tablecontent,
	//         'objectname' => $objName,
	//         'extra' => array(),
	//         'add'=>'add', 
	//         'tableheader' => $tableFields,
	//         'jsGridFnName' => 'getAjaxgridData',
	//         'jsFillFnName' => '',
	//         'searchArray' => $searchArray,
	//         'call' => $call,
	//         'dashboardcall' => $dashboardcall,
	//         'search_filters' => array(
	//             'isactive' => array(
	//                 'type' => 'select',
	//                 'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
	//         )
	//     );
	//     return $dataTmp;
	// }

	public function isLdapUser($username)
	{

		if (empty($username)) {
			return false;
		}

		try {
			$db = Zend_Db_Table::getDefaultAdapter();

			$query = $db->select()
				->from(array('u' => $this->_name))
				->where("u.ldapUserName = ? AND u.isLdapuser = 'Y'", array($username));

			$result_one = $db->fetchAll($query);

			if (count($result_one) > 0) {
				return true;
			} else {
				return false;
			}
		} catch (\Throwable $th) {
			// Zend_Debug::dump($th->getMessage());
			Zend_Debug::dump($th->getMessage());

			die("This is error");
		}
	}

	public function hasLdapOption($username)
	{

		if (empty($username)) {
			return false;
		}

		try {
			$db = Zend_Db_Table::getDefaultAdapter();

			$query = $db->select()
				->from(array('u' => $this->_name))
				->where("u.ldapUserName = ? AND u.isLdapuser = 'N'", array($username));

			$result_one = $db->fetchAll($query);

			if (count($result_one) > 0) {
				return true;
			} else {
				return false;
			}
		} catch (\Throwable $th) {
			// Zend_Debug::dump($th->getMessage());
			Zend_Debug::dump($th->getMessage());

			die("ff");
		}
	}

	public function getUserObject($username)
	{
		if (empty($username)) {
			return false;
		}

		$query = $this->select()
			->setIntegrityCheck(false)
			->from(array('u' => $this->_name), array('u.*', 'emprole' => 'r.id', 'x_menu_id' => new Zend_Db_Expr(0)))
			->joinInner(array('ur' => 'main_userrole'), 'ur.user_id=u.id', array('urid' => 'ur.userrole_id'))
			->joinInner(array('r' => ROLE_ARRAY['tablename']), "ur.role_id = r." . ROLE_ARRAY['colid'] . " and r." . ROLE_ARRAY['colisactive'] . " = 'Y'", array('group_id' => ROLE_ARRAY['colgroupid']))
			->joinLeft(array('e' => 'main_employees'), "e.id = u.empid", array('e.profileimg', 'e.id as empid', 'e.userfullname', 'is_orghead'))
			//->where("(u.employeeId = '".$username."' OR u.emailaddress = '".$username."') and u.isactive = 1");
			->where("u.isactive = 1 and (u.username = ? OR u.emailaddress = ? OR u.ldapUserName = ?)", array($username));
		//die($query);
		$result_one = $this->fetchAll($query);

		if (isset($result_one[0]) && $result_one[0]['isactive'] == 1) {

			$userObject = $result_one[0];
			return $userObject;
		} else {
			return false;
		}
	}

	public function returnuser($username)
	{
		try {

			$query = $this->select()
				->setIntegrityCheck(false)
				->from(array('u' => $this->_name), array('u.*', 'emprole' => 'r.id', 'x_menu_id' => new Zend_Db_Expr(0)))
				->joinInner(array('ur' => 'main_userrole'), 'ur.user_id=u.id')
				->joinInner(array('r' => ROLE_ARRAY['tablename']), "ur.role_id = r." . ROLE_ARRAY['colid'] . " and r." . ROLE_ARRAY['colisactive'] . " = 'Y'", array('group_id' => ROLE_ARRAY['colgroupid']))
				->joinLeft(array('e' => 'main_employees'), "e.id = u.empid", array('e.profileimg', 'e.id as empid', 'e.userfullname', 'is_orghead'))
				//->where("(u.employeeId = '".$username."' OR u.emailaddress = '".$username."') and u.isactive = 1");
				->where("u.isactive = 1 and (u.username = ? OR u.emailaddress = ? OR u.ldapUserName = ?)", array($username));
			// die($query);
			$result_one = $this->fetchRow($query);
			return $result_one;
		} catch (\Throwable $th) {
			// Zend_Debug::dump($th->getMessage());
			Zend_Debug::dump($th->getMessage());

			die("ff");
		}
	}

	public function addOrUpdateUserModel($data, $where, $userid = '')
	{
		if ($where != '') {
			$this->update($data, $where);
			if ($userid) {
				$db = Zend_Db_Table::getDefaultAdapter();
				$query = "update main_employees  set backgroundchk_status = '" . $data['backgroundchk_status'] . "', modifiedby = " . $data['modifiedby'] . ", modifieddate = '" . $data['modifieddate'] . "' where id = " . $userid;
				$db->query($query);
			}
			return 'update';
		} else {
			$this->insert($data);
			$id = $this->getAdapter()->lastInsertId('main_users');
			return $id;
		}
	}

	public function updatePassword($data, $where)
	{
		$this->update($data, 'email = "' . $where . '"');
	}

	public function isActiveUser($corpEmail)
	{
		// die(c);
		try {
			$db = Zend_Db_Table::getDefaultAdapter();

			$userData = $db->select()
				->from(array('a' => 'main_users'), array('aid' => 'a.id'))
				->joinInner(array('ur' => 'main_userrole'), 'ur.user_id=a.id')
				->joinLeft(array('r' => ROLE_ARRAY['tablename']), 'r.' . ROLE_ARRAY['colid'] . '=ur.role_id', array("def_status" => "if(r." . ROLE_ARRAY['colgroupid'] . " in (1,5) and a.userstatus = 'new','old',a.userstatus)"))
				->where("a.isactive = 1 AND r." . ROLE_ARRAY['colisactive'] . " = 'Y' AND a.emptemplock = 0 AND (a.username = ? OR a.emailaddress = ? OR a.ldapUserName = ?)", array($corpEmail));

			$new_userdata = $db->select()
				->from(array('ac' => $userData), array('count' => 'count(*)'))
				->where("ac.def_status = 'old'");
		} catch (Exception $e) {
			// echo $e->getFile();
			echo $e->getFile();
		}

		return $db->fetchAll($new_userdata);
	}

	public function getActiveStatus($corpEmail)
	{
		try {

			$userData = $this->select()->setIntegrityCheck(false)
				->from(array('u' => 'main_users'), array('status' => 'u.isactive', 'isaccountlock' => 'u.emptemplock'))

				->where("u.username = ? OR u.emailaddress = ? OR u.ldapUserName = ?", array($corpEmail));
		} catch (Exception $e) {
			// Zend_Debug::dump($e->getFile());
			Zend_Debug::dump($e->getFile());
		}
		return $this->fetchAll($userData)->toArray();
	}

	public function getUserDateOfJoining($corpEmail)
	{
		try {
			$userData = $this->select()->setIntegrityCheck(false)
				->from(array('u' => 'main_users'), array())
				->joinInner(array('e' => 'main_employees'), 'e.id=u.empid', array('date_of_joining', "doj" => "if(e.date_of_joining <= CURDATE(),1,0)"))
				->where("u.username = ? OR u.emailaddress = ? OR u.ldapUserName = ?", array($corpEmail));
		} catch (Exception $e) {
			// Zend_Debug::dump($e->getFile());
			Zend_Debug::dump($e->getFile());
		}
		return $this->fetchAll($userData)->toArray();
	}

	public function edituserPassword($data, $where)
	{

		$update = $this->update($data, $where);
	}

	public function getUserDetailsByID($id, $flag = '')
	{

		if ($id != '' && $id != NULL) {
			if ($flag == 'all') {
				$result =  $this->select()
					->setIntegrityCheck(false)
					->from(array('u' => 'main_users'), array('u.*'))
					->where("u.id = " . $id);
			} else {
				$result =  $this->select()
					->setIntegrityCheck(false)
					->from(array('u' => 'main_users'), array('u.*'))
					->where("u.isactive = 1 AND u.id = " . $id);
			}
			return $this->fetchAll($result)->toArray();
		}
	}

	public function getUserDetails($id)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('u' => 'main_employees'), array('u.*'))
			->where('u.id="' . $id . '" ');
		// die($select);
		return $this->fetchAll($select)->toArray();
	}
	public function getUserDetailsprofile($id)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('e' => 'main_employees'), array('e.*'))
			->joinInner(array('u' => 'main_users'), 'u.empid = e.id', array('user_id' => 'u.id'))

			->where('u.id="' . $id . '" ');
		return $this->fetchAll($select)->toArray();
	}
	public function getUsers()
	{
		$db = Zend_Db_Table::getDefaultAdapter();

		$usersData = $db->query("select u.id,u.userfullname from main_users u
		join main_userrole ur on u.id = ur.user_id
        join " . ROLE_ARRAY['tablename'] . " r on ur.role_id = r." . ROLE_ARRAY['colid'] . " where r." . ROLE_ARRAY['colgroupid'] . " IN(" . MANAGER_GROUP . "," . HR_GROUP . "," . EMPLOYEE_GROUP . "," . SYSTEMADMIN_GROUP . ") AND u.userstatus='new' AND u.isactive=1 ");

		$usersResult = $usersData->fetchAll();
		return $usersResult;
	}

	public function getUserDetailsByIDandFlag($id)
	{
		$result =  $this->select()
			->setIntegrityCheck(false)
			->from(array('e' => 'main_employees'), array('e.*'))
			->where("e.isactive IN (1,2,3,4,0) AND  e.id = " . $id);
		$db = Zend_Db_Table::getDefaultAdapter();
		return $this->fetchAll($result)->toArray();
	}
	/**
		Purpose:	To get the manager roles from all groups..
		Modified Date:	30/09/2013.
		Modified By:	sapplica.
	 */
	public function getReportingManagerList($dept_id, $employee_id = '', $employee_group = '')
	{

		/*
			When there are no managers for selected department.get management as reporting managers.
			*/
		$res = "";
		$qry = "";
		$db = Zend_Db_Table::getDefaultAdapter();
		if ($dept_id != "") {
			$qry = "select * from ((SELECT u.id, concat(u.userfullname,if(j.name is null,'',concat(' , ',j.name))) as 		name,u.profileimg 
			FROM main_users u 
			INNER JOIN main_userrole ur ON u.id = ur.user_id 
			INNER JOIN " . ROLE_ARRAY['tablename'] . " r ON ur.role_id = r." . ROLE_ARRAY['colid'] . " 
			INNER JOIN main_employees e ON u.id = e.id
			left join main_jobtitle j on j.jobtitle_id = e.jobtitle_id 
			WHERE e.department_id = " . $dept_id;

			if ($employee_id != "") {
				$qry .= " AND  e.user_id != " . $employee_id;
			}
			if (isset($employee_group) && !empty($employee_group) && $employee_group == MANAGEMENT_GROUP) {
				$qry .= " AND r." . ROLE_ARRAY['colgroupid'] . " IN (" . MANAGEMENT_GROUP . ") ";
			} else {
				$qry .= " AND r." . ROLE_ARRAY['colgroupid'] . " IN (" . MANAGEMENT_GROUP . "," . MANAGER_GROUP . "," . HR_GROUP . "," . SYSTEMADMIN_GROUP . ") ";
			}
			$qry .= " AND u.userstatus='old' AND u.isactive=1 AND r.isactive=1) union
			(select u.id,concat(u.userfullname,if(j.name is null,'',concat(' , ',j.name))) as name,u.profileimg from main_users u
			INNER JOIN main_employees e ON u.id = e.user_id 
                        left join main_jobtitle j on j.jobtitle_id = e.jobtitle_id
			INNER JOIN " . ROLE_ARRAY['tablename'] . " r ON u.emprole = r." . ROLE_ARRAY['colid'] . "  where  ";
			if ($employee_id != "") {
				$qry .= " e.user_id != " . $employee_id . " AND ";
			}
			$qry .= "  e.department_id = " . $dept_id . " AND r." . ROLE_ARRAY['colgroupid'] . " = " . MANAGEMENT_GROUP . " AND u.isactive=1 AND r." . ROLE_ARRAY['colisactive'] . "=1)";
			/*	TO get the superAdmin record when the employee adding/editing is of management  role.	*/
			if (isset($employee_group) && !empty($employee_group) && $employee_group == MANAGEMENT_GROUP) {
				$qry .= " union (select " . SUPERADMIN . " id ,'Super Admin' name,'' profileimg) ";
			}
			$qry .= " ) app order by app.name asc";


			$reportingManagersData = $db->query($qry);
			$res = $reportingManagersData->fetchAll();
		}

		return $res;
	}
	/**
	 * This function is used to get reporting managers in employee screen.
	 * @param Integer $dept_id        = id of department
	 * @param Integer $employee_id    = id of employee
	 * @param Integer $employee_group = group id of employee.
	 * @return DataSet Consists of reporting managers of names and their id's.
	 */
	public function getReportingManagerList_employees($dept_id = '', $employee_id = '', $employee_group)
	{
		/*
                When there are no managers for selected department.get management as reporting managers.
            */
		$res = "";
		$qry = "";
		$db = Zend_Db_Table::getDefaultAdapter();
		if ($employee_group != "") {
			if ($employee_group == MANAGEMENT_GROUP) {
				$qry_str = " SELECT u.id, concat(u.userfullname,if(j.name is null,'',concat(' , ',j.name))) as name,u.profileimg 
                                FROM main_users u 
                                INNER JOIN " . ROLE_ARRAY['tablename'] . " r ON u.emprole = r." . ROLE_ARRAY['colid'] . " 
                                INNER JOIN main_employees e ON u.id = e.user_id 
                                LEFT join main_jobtitle j on j.jobtitle_id = e.jobtitle_id 
                                WHERE  r." . ROLE_ARRAY['colgroupid'] . " IN (1)  AND u.userstatus='old' AND u.isactive=1 AND r." . ROLE_ARRAY['colisactive'] . "=1 ";
				if ($dept_id == '') {
					$qry = $qry_str;
					if ($employee_id != "") {
						$qry .= " AND  e.user_id != " . $employee_id;
					}
				} else {
					$qry = "select * from ((  " . $qry_str;
					if ($employee_id != "") {
						$qry .= " AND  e.user_id != " . $employee_id;
					}
					$qry .= "  ) 
                                union 
                                (  " . $qry_str . " and e.department_id = " . $dept_id . " ";
					if ($employee_id != "") {
						$qry .= " AND  e.user_id != " . $employee_id;
					}
					$qry .= "     )) q";
				}
				$qry .= " order by name asc ";
			} else {
				if ($dept_id != '') {
					$qry = "select * from ((SELECT u.id, concat(u.userfullname,if(j.name is null,'',concat(' , ',j.name))) as name,u.profileimg 
                                FROM main_users u 
                                INNER JOIN " . ROLE_ARRAY['tablename'] . " r ON u.emprole = r." . ROLE_ARRAY['colid'] . " 
                                INNER JOIN main_employees e ON u.id = e.user_id 
                                left join main_jobtitle j on j.jobtitle_id = e.jobtitle_id 
                                WHERE e.department_id = " . $dept_id . " ";

					if ($employee_id != "") {
						$qry .= " AND  e.user_id != " . $employee_id;
					}
					if (isset($employee_group) && !empty($employee_group) && $employee_group == MANAGEMENT_GROUP) {
						$qry .= " AND r." . ROLE_ARRAY['colgroupid'] . " IN (" . MANAGEMENT_GROUP . ") ";
					} else {
						$qry .= " AND r." . ROLE_ARRAY['colgroupid'] . " IN (" . MANAGEMENT_GROUP . "," . MANAGER_GROUP . "," . HR_GROUP . "," . SYSTEMADMIN_GROUP . "," . EMPLOYEE_GROUP . "," . CUSTOM_GROUP . ") ";
					}
					$qry .= " AND u.userstatus='old' AND u.isactive=1 AND r.isactive=1) union
                        (select u.id,concat(u.userfullname,if(j.name is null,'',concat(' , ',j.name))) as name,u.profileimg from main_users u
                        INNER JOIN main_employees e ON u.id = e.user_id 
                        left join main_jobtitle j on j.jobtitle_id = e.jobtitle_id
                        INNER JOIN " . ROLE_ARRAY['tablename'] . " r ON u.emprole = r." . ROLE_ARRAY['colid'] . "  where  ";
					if ($employee_id != "") {
						$qry .= " e.user_id != " . $employee_id . " AND ";
					}
					$qry .= "   r." . ROLE_ARRAY['colgroupid'] . " = " . MANAGEMENT_GROUP . " AND u.isactive=1 AND r.isactive=1)";

					$qry .= " ) app order by app.name asc";
				}
			}
			if ($qry != '') {
				$reportingManagersData = $db->query($qry);
				$res = $reportingManagersData->fetchAll();
			}
		}


		return $res;
	}
	public function getReportingManagerList_30092013()
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('u' => 'main_users'), array('u.id', 'u.userfullname'))
			->joinInner(array('r' => ROLE_ARRAY['tablename']), 'u.emprole = r.' . ROLE_ARRAY['colid'], array())
			->where('r.' . ROLE_ARRAY['colgroupid'] . '=' . MANAGER_GROUP . ' AND u.userstatus="old" AND u.isactive=1');

		return $this->fetchAll($select)->toArray();
	}

	public function updateuserstatus($userid)
	{
		$db = Zend_Db_Table::getDefaultAdapter();

		$db->query("update main_users  set userstatus = 2 where id = '" . $userid . "' ");
	}

	public function addOrUpdateProfileImage($data, $where)
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

	public function getLoggedInUserPwd($id, $email, $employeid)
	{

		$db = Zend_Db_Table::getDefaultAdapter();
		$sql = $db->query("select um.emppassword from main_users um where um.id='" . $id . "' and um.emailaddress = '" . $email . "' and um.username='" . $employeid . "' ");

		$result = $sql->fetch();

		return $result;
	}

	public function editadminPassword($newpswd, $id = '', $email, $employeid = '')
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		if ($id != '' && $employeid != '') {
			$db->query("update main_users  set emppassword = '" . $newpswd . "' where id='" . $id . "' and emailaddress = '" . $email . "' and username='" . $employeid . "' ");
		} else {
			$db->query("update main_users  set emppassword = '" . $newpswd . "' where emailaddress = '" . $email . "'");
		}
	}

	public function getEmailAddressCount($email)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('u' => 'main_users'), array('emailcount' => 'count(u.emailaddress)', 'u.userfullname'))
			->where('u.emailaddress="' . $email . '" AND u.isactive = 1 AND u.emptemplock = 0');

		return $this->fetchAll($select)->toArray();
	}

	public function getEmpDetailsByEmailAddress($email)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('u' => 'main_users'), array('u.emppassword', 'u.userfullname', 'u.isactive', 'u.emptemplock'))
			->where('u.emailaddress="' . $email . '" ');

		return $this->fetchAll($select)->toArray();
	}

	public function updatePwd($encodedPswd, $emailaddress)
	{
		$db = Zend_Db_Table::getDefaultAdapter();

		$db->query("update main_users  set emppassword = '" . $encodedPswd . "' where emailaddress = '" . $emailaddress . "'");
	}

	/*
	 Modified Date: 24/09/2013
	 Purpose:	To get the BG status of particular user for "send to BG checks" link in employeedetails helper
	 If the BG status is "yet to start" then only we can send that emp to BG checks
	 */
	public function getBGstatus($userId)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('u' => 'main_users'), array('u.backgroundchk_status', 'u.isactive'))
			->joinInner(array('ur' => 'main_userrole'), 'u.id = ur.user_id', array())
			->joinInner(array('r' => ROLE_ARRAY['tablename']), 'ur.role_id = r.' . ROLE_ARRAY['colid'], array('r.' . ROLE_ARRAY['colgroupid']))
			->where('u.id=' . $userId . ' AND u.isactive=1');
		$userBGstatus = $this->fetchAll($select)->toArray();

		return $userBGstatus;
	}

	public function getDepartmentAddress($deptId)
	{
		$res = array();
		if ($deptId) {
			$db = Zend_Db_Table::getDefaultAdapter();
			$deptAddRes = $db->query("select d.address1,c.country,s.state,ct.city
									from main_departments d 
									inner join main_countries c ON c.country_id_org = d.country
									inner join main_states s ON s.state_id_org = d.state
									inner join main_cities ct ON ct.city_org_id = d.city
									where d.id = " . $deptId . " and d.isactive = 1;");
			return $res = $deptAddRes->fetch();
		} else return $res;
	}

	public function getOrganizationAddress()
	{
		$res = array();
		$db = Zend_Db_Table::getDefaultAdapter();
		$deptAddRes = $db->query("select d.address1,c.country,s.state,ct.city
									from main_organisationinfo d 
									inner join main_countries c ON c.country_id_org = d.country
									inner join main_states s ON s.state_id_org = d.state
									inner join main_cities ct ON ct.city_org_id = d.city
									where d.isactive = 1;");
		return $res = $deptAddRes->fetch();
	}

	public function addUserLoginLogManager($dataTmp)
	{
		// try {


		$db = Zend_Db_Table::getDefaultAdapter();
		$rows = $db->query("INSERT INTO `main_userloginlog` (userid,emprole,group_id,username,emailaddress,userfullname,logindatetime,empipaddress,profileimg) VALUES (" . $dataTmp['id'] . "," . $dataTmp['emprole'] . "," . $dataTmp['group_id'] . ",'" . $dataTmp['username'] . "','" . $dataTmp['emailaddress'] . "','" . $dataTmp['userfullname'] . "','" . $dataTmp['logindatetime'] . "','" . $dataTmp['empipaddress'] . "','" . $dataTmp['profileimg'] . "')");

		$id = $this->getAdapter()->lastInsertId('main_logmanager');
		return $id;
		//code...
		// } catch (\Throwable $th) {
		// 	Zend_Debug::dump(debug_backtrace());
		// }
	}

	/**
	 * This function is used to get all user for any dropdown list.
	 *
	 * @return Array of menu names and ids.
	 */
	public function getUserListForActivitylog()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select g.id,g.username,(select count(*) from main_logmanager where is_active =1 and last_modifiedby = g.id) cnt
                   from main_users g where g.isactive = 1";
		$result = $db->query($query);
		$username_arr = array();
		while ($row = $result->fetch()) {
			$username_arr[$row['id']]['userfullname'] = $row['userfullname'];
			$username_arr[$row['id']]['cnt'] = $row['cnt'];
		}

		return $username_arr;
	}


	/**
	 * This function is used in expenses to get all users except login user.
	 *
	
	 */
	public function getUserList($id)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select g.id,g.username,(select count(*) from main_logmanager where is_active =1 and last_modifiedby = g.id) cnt
                   from main_users g where g.isactive = 1 and g.id !=" . $id;
		$result = $db->query($query);
		$username_arr = array();
		while ($row = $result->fetch()) {
			$username_arr[$row['id']]['userfullname'] = $row['userfullname'];
			$username_arr[$row['id']]['cnt'] = $row['cnt'];
		}

		return $username_arr;
	}



	/**
	 * This function is used to get organizationimg.
	 *
	 * @return orgimg.
	 */
	public function getOrganizationImg()
	{
		// try {

		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select o.org_image as orgimg from main_organisationinfo o where o.isactive = 1";
		$result = $db->query($query);

		$orgimg = $result->fetch();

		return $orgimg['orgimg'];
		//code...
		// } catch (\Throwable $th) {
		// 	Zend_Debug::dump(debug_backtrace());
		// }
	}

	public function getUserData()
	{
		$result =  $this->select()
			->setIntegrityCheck(false)
			->from(array('u' => 'main_users'), array('u.*'));

		$db = Zend_Db_Table::getDefaultAdapter();
		return $this->fetchAll($result)->toArray();
	}

	public function getUserImageByIds($userIdArray)
	{
		$resultstring = implode(',', $userIdArray);
		$userArray = array();
		if ($resultstring) {
			try {
				$qry = "select ob.id, ob.profileimg from main_users ob
                                        where ob.id IN (" . $resultstring . ") and ob.isactive = 1";
				$db = Zend_Db_Table::getDefaultAdapter();
				$sqlRes = $db->query($qry);
				$userRes = $sqlRes->fetchAll();

				if (!empty($userRes)) {
					foreach ($userRes as $user) {
						$userArray[$user['id']] = $user['profileimg'];
					}
				}
			} catch (Exception $e) {
				echo "Error Encountered - " . $e->getfile();
			}
		}
		return $userArray;
	}

	public function getMailSettingsData()
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('m' => 'main_mail_settings'), array('m.*'));

		return $this->fetchAll($select)->toArray();
	}

	public function addOrUpdateSettingsData($data, $where)
	{
		$date = gmdate("Y-m-d H:i:s");
		$db = Zend_Db_Table::getDefaultAdapter();

		$select = 'SELECT count(id) as cnt FROM main_mail_settings';
		$res = $db->query($select)->fetchAll();

		if (!empty($res) && isset($res[0]['cnt']) && $res[0]['cnt'] > 0) {
			$qry = "UPDATE main_mail_settings SET tls='" . $data['tls'] . "', auth='" . $data['auth'] . "', port=" . $data['port'] . ", username='" . $data['username'] . "', password='" . $data['password'] . "', server_name='" . $data['server_name'] . "', createddate='" . $date . "', modifieddate='" . $date . "' ";
			$db->query($qry);
			return 'update';
		} else {
			$qry = "INSERT INTO main_mail_settings(tls,auth,port,username,password,server_name,createddate,modifieddate) values('" . $data['tls'] . "','" . $data['auth'] . "'," . $data['port'] . ",'" . $data['username'] . "', '" . $data['password'] . "', '" . $data['server_name'] . "','" . $date . "', '" . $date . "') ";
			$db->query($qry);
			return 'insert';
		}
	}

	//for time management
	public function getUserTimemanagementRole($userId)
	{

		try {
			if ($userId != 1) {

				$select = $this->select()
					->setIntegrityCheck(false)
					->from(array('u' => $this->_name), array('tm_role' => new Zend_Db_Expr("(CASE WHEN g." . GROUP_ARRAY['colname'] . " = 'Management' || g." . GROUP_ARRAY['colname'] . " = 'Manager'  THEN  'Manager'
 				ELSE CASE WHEN u.id IN (SELECT id FROM main_employees) THEN 'Manager'
      				ELSE 'Employee' END END)")))
					->joinInner(array('ur' => 'main_userrole'), "ur.user_id = u.id", array())
					->joinInner(array('r' => ROLE_ARRAY['tablename']), "r." . ROLE_ARRAY['colid'] . " = ur.role_id", array())
					->joinInner(array('g' => GROUP_ARRAY['tablename']), "g." . GROUP_ARRAY['colid'] . " = r." . ROLE_ARRAY['colgroupid'] . "", array())
					->where(" u.id = ? ", array($userId));


				$tmRole = $this->fetchAll($select);
			} else {
				$tmRole[0]['tm_role'] = 1;
			}

			return	$tmRole[0]['tm_role'];
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function getUserDetailsforView($data)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = 'SELECT concat(userfullname) as name FROM main_users where id in(' . $data . ') group by id';
		$result = $db->query($query);
		$data = $result->fetchAll();
		return $data;
	}

	public function getUserFromEmp($empId)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('u' => 'main_users'), 'u.id')
			->where('u.empid=' . $empId);

		return $this->fetchAll($select)->toArray();
	}

	public function getEmpFromUser($userid)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('u' => 'main_users'), 'u.empid')
			->where('u.id=' . $userid);
		return $this->fetchAll($select)->toArray();
	}
}
