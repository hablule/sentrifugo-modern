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

class Default_Model_Structure extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_sbunit';
	protected $_primary = 'sbunit_id';

	public function getOrgData()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$orgData = $db->query("select company_id,name from main_company where isactive = 'Y';");
		$result = $orgData->fetch();
		return $result;
	}

	public function getbusinessdata()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$businessdata = $db->query("select sbunit_id,name from main_sbunit where isactive ='Y' order by name asc;");
		$result = $businessdata->fetchAll();
		return $result;
	}
	public function getdivisiondata()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$businessdata = $db->query("SELECT (CASE
		WHEN company_id IS NOT NULL THEN company_id 
		WHEN sbunit_id IS NOT NULL THEN sbunit_id 
		ELSE parentdivision_id
		END) AS parent_id,
		(CASE WHEN company_id IS NOT NULL THEN 'company_id' 
		WHEN sbunit_id IS NOT NULL THEN 'sbunit_id' 
		ELSE 'parentdivision_id'
		END) AS parent_name,division_id,name
		FROM main_division ORDER by name ASC;");
		$result = $businessdata->fetchAll();
		return $result;
	}
	public function getsectiondata()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$businessdata = $db->query("SELECT (CASE
		WHEN company_id IS NOT NULL THEN company_id 
		WHEN sbunit_id IS NOT NULL THEN sbunit_id 
		WHEN division_id IS NOT NULL THEN division_id 
		WHEN departement_id IS NOT NULL THEN departement_id 
		ELSE parentsection_id
		END) AS parent_id,
		(CASE WHEN company_id IS NOT NULL THEN 'company_id' 
		WHEN sbunit_id IS NOT NULL THEN 'sbunit_id' 
		WHEN division_id IS NOT NULL THEN 'division_id' 
		WHEN departement_id IS NOT NULL THEN 'departement_id' 

		ELSE 'parentsection_id'
		END) AS parent_name,section_id,name
		FROM main_section ORDER by name ASC;");
		$result = $businessdata->fetchAll();
		return $result;
	}
	public function getprojectdata()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$businessdata = $db->query("SELECT (CASE
		WHEN company_id IS NOT NULL THEN company_id 
		WHEN sbunit_id IS NOT NULL THEN sbunit_id 
		WHEN division_id IS NOT NULL THEN division_id 
		WHEN department_id IS NOT NULL THEN department_id 
		WHEN section_id IS NOT NULL THEN section_id 

		ELSE associateproject_id
		END) AS parent_id,
		(CASE WHEN company_id IS NOT NULL THEN 'company_id' 
		WHEN sbunit_id IS NOT NULL THEN 'sbunit_id' 
		WHEN division_id IS NOT NULL THEN 'division_id' 
		WHEN department_id IS NOT NULL THEN 'department_id' 
		WHEN section_id IS NOT NULL THEN 'section_id' 

		ELSE associateproject_id
		END) AS parent_name,projectco_id,description
		FROM main_projectco ORDER by project_id ASC;");
		$result = $businessdata->fetchAll();
		return $result;
	}
	public function getDeptData()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$deptData = $db->query("SELECT (CASE
		WHEN company_id IS NOT NULL THEN company_id 
		WHEN sbunit_id IS NOT NULL THEN sbunit_id 
		WHEN division_id IS NOT NULL THEN division_id 
		ELSE parentdepartement_id
		END) AS parent_id,
		(CASE WHEN company_id IS NOT NULL THEN 'company_id' 
		WHEN sbunit_id IS NOT NULL THEN 'sbunit_id' 
		WHEN division_id IS NOT NULL THEN 'division_id' 
		ELSE 'parentdepartement_id'
		END) AS parent_name,department_id,name
		FROM main_department ORDER by name ASC;");
		$result = $deptData->fetchAll();
		return $result;
	}
}
