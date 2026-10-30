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

class Default_Model_Mydetails extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_employees';
    protected $_primary = 'id';
	
	public function getEmployeesData($sort, $by, $pageNo, $perPage,$searchQuery)
	{
		$where = " e.isactive = 1 ";
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		
		$employeesData = $this->select()
    					   ->setIntegrityCheck(false)	 
						   ->from(['e' => 'main_employees'],['id'=>'e.user_id'])
						   ->joinInner(['u'=>'main_employees'],'e.reporting_manager=u.id',['reportingmanager'=>'u.userfullname'])
						   ->joinInner(['mu'=>'main_employees'],'e.user_id=mu.id',['empId'=>'mu.employeeId','empname'=>'mu.userfullname','empemail'=>'mu.emailaddress'])
						   ->joinInner(['b'=>'main_businessunits'],'e.businessunit_id=b.id',['businessunit'=>'b.unitname'])
						   ->joinInner(['d'=>'main_departments'],'e.department_id=d.id',['department'=>'d.deptname'])
						   ->joinLeft(['j'=>'main_jobtitles'],'e.jobtitle_id=j.id',['jobtitle'=>'j.jobtitlename'])
						   ->joinLeft(['p'=>'main_positions'],'e.position_id=p.id',['position'=>'p.positionname'])
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);
		
		return $employeesData;       		
	}
	
	public function getsingleEmployeeData($id)
	{
		$row = $this->fetchRow("user_id = '".$id."'");
		if (!$row) {
			throw new Exception("Could not find row $id");
		}
		return $row->toArray();
	}
	
	public function SaveorUpdateEmployeeData($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_employees');
			return $id;
		}
		
	}
	
	
}
?>