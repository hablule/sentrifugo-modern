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

class Default_Model_Heirarchy extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_hierarchylevels';
	protected $_primary = 'id';

	public function getOrgData()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$orgData = $db->query("select id,orghead from main_organisationinfo where isactive = 1;");
		$result = $orgData->fetch();
		return $result;
	}

	public function getUnitData()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$unitData = $db->query("select * from main_sbunit where isactive = 'Y';");
		$result = $unitData->fetchAll();
		return $result;
	}

	public function getDeptData()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$deptData = $db->query("select id,depthead,unitid from main_departments where isactive = 1;");
		$result = $deptData->fetchAll();
		return $result;
	}

	public function getAllEmployees()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$empmodel = new Default_Model_Employee();
		$acceptedrolesArr = $empmodel->getUserRole();
		$roles = $acceptedrolesArr[0]['roles'];
		$where = "u.isactive = 1 AND u.userstatus = 'old' ";
		// if($roles != '')
		// $where .= " AND ur.role_id IN(".$roles.")";	
		$ert = "SELECT e.id,e.profileimg,
							   concat(e.userfullname,GROUP_CONCAT(p.name)) as name
							   FROM main_employees e
        left JOIN main_users u ON u.empid = e.id
        left JOIN main_userrole ur ON ur.user_id = u.id
        left JOIN main_role r ON r.id = ur.role_id
        left JOIN main_title t ON e.title_id = t.id
        left join main_empposition ep on ep.employee_id=e.id
        Left join main_employees el on ep.reportto_id=el.id
        left JOIN main_position p ON p.id=ep.position_id
        left JOIN main_jobtitle jt ON jt.id=ep.jobtitle_id
		
							   WHERE " . $where . " AND u.id NOT IN (SELECT userid from main_hierarchylevels WHERE isactive = 1)
							   Group BY e.id
							    ORDER BY name ASC";
		$empData = $db->query($ert);
		$result = $empData->fetchAll();
		return $result;
	}

	public function getVAllEmployees()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$empmodel = new Default_Model_Employee();
		$acceptedrolesArr = $empmodel->getUserRole();
		$roles = $acceptedrolesArr[0]['roles'];
		$where = "u.isactive = 1 AND u.userstatus = 'old' ";
		if ($roles != '')
			$where .= " AND ur.role_id IN(" . $roles . ")";
		$empData = $db->query("SELECT e.id,e.profileimg,
		concat(e.userfullname,GROUP_CONCAT(p.name)) as name
		FROM main_employees e
				left JOIN main_users u ON u.empid = e.id
				left JOIN main_userrole ur ON ur.user_id = u.id
				left JOIN main_role r ON r.id = ur.role_id
				left JOIN main_title t ON e.title_id = t.id
				left join main_empposition ep on ep.employee_id=e.id
				Left join main_employees el on ep.reportto_id=el.id
				left JOIN main_position p ON p.id=ep.position_id
				left JOIN main_jobtitle jt ON jt.id=ep.jobtitle_id
											WHERE " . $where . "    Group BY e.id ORDER BY name ASC");
		$result = $empData->fetchAll();
		return $result;
	}

	public function getlevelsusernames()
	{
		$db = Zend_Db_Table::getDefaultAdapter();

		// $query = "select 1 level_number,user_id userid,reporting_manager parent,userfullname,profileimg,
		//           jobtitle_name jobtitlename 
		//           from employees_summary where isactive = 1 
		//           order by reporting_manager,userfullname asc";
		$query = "
				SELECT 
					1 level_number,
					e.id userid,
					ep.reportto_id parent,
					e.userfullname,
					e.profileimg,
					GROUP_CONCAT(p.name) jobtitlename
				FROM
					main_employees e
					inner join main_empposition ep on ep.employee_id=e.id
					left join main_position p on p.id=ep.position_id

				WHERE
					e.isactive = 1
					GROUP BY reportto_id,e.id
				ORDER BY ep.reportto_id , userfullname ASC
			";
		//die($query);
		//the above query is to build hierarchy based on reporting manager relation from summary table.
		$data = $db->query($query);
		$result = $data->fetchAll();
		return $result;
	}

	public function getlevelcount()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "SELECT COUNT(*) as count,level_number FROM main_hierarchylevels GROUP BY level_number;";
		$data = $db->query($query);
		$result = $data->fetchAll();
		return $result;
	}

	public function SaveorUpdateleveldata($data, $where)
	{

		if ($where != '') {
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id = $this->getAdapter()->lastInsertId('main_businessunits');
			return $id;
		}
	}

	public function getchildren($userid)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "SELECT h.* from
					main_hierarchylevels h 					
					where h.isactive = 1 AND parent = " . $userid . ";";
		$data = $db->query($query);
		$result = $data->fetchAll();
		return $result;
	}
}
