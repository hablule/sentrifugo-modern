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

class Default_Model_Organisationinfo extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_company';
    protected $_primary = 'company_id';
	
	public function getOrganisationData($id)
	{
		$row = $this->fetchRow("company_id = '".$id."' AND isactive ='Y'");
		if (!$row) {
			throw new Exception("Could not find row $id");
		}
		return $row->toArray();
	}

	public function getdomainbyorgid($id)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select * from main_companyengage where  company_id = ".$id." ";
		$result = $db->query($query)->fetchAll();
	    return $result;
	}
	
	public function getOrganisationDetails($id)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select * from main_company where  company_id = '".$id."' and isactive='Y' ";
		$result = $db->query($query)->fetchAll();
	    return $result;
	}

	public function getdomains()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select b.name as bname  from main_companyengage as a inner join main_businessdomain as b on a.businessdomain_id = b.businessdomain_id ";
		$result = $db->query($query)->fetchAll();
		//die($result);
		return $result;
	}

	public function getclocation()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select c.address1,c.address2,c.address3,c.address4,e.country_name,d.city_name,f.state_name  from main_company as a inner join main_address as b on a.company_id = b.company_id inner join main_location as c on c.location_id=b.location_id inner join tbl_cities d on d.id=c.city_id inner join tbl_countries e on e.id = c.country_id inner join tbl_states f on f.id = c.region_id";
		$result = $db->query($query)->fetchAll();
		//die($result);
		return $result;
	}
	
	public function getOrganisationInfo()
	{
	   $select = $this->select()
						->setIntegrityCheck(false)
						->from(array('s'=>'main_company'),array('s.company_id as id','s.code as code','s.website as website','s.org_start_date as org_start_date','s.name as organisationname','s.domain as domain','s.org_image as org_image','s.description as orgdescription'))
					    ->where('s.isactive = "Y"');
						//die($select);
		return $this->fetchAll($select)->toArray();
	
	}
	public function getBusinessdomain()
	{
	   $select = $this->select()
						->setIntegrityCheck(false)
						->from(array('s'=>'main_businessdomain'),array('s.businessdomain_id as id','s.name as name'))
					    ->where('s.isactive = "Y"');
		return $this->fetchAll($select)->toArray();
	
	}
	public function SaveorUpdateData($data, $where)
	{
		
     
		
		if($where != ''){
			//$this->update($data, $where);
			$this->update($data,$where);
			return 'update';
		} else {
			$this->insert($data);
			//$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_company');
			return $id;
		}
	}
	public function getorghead_details()
        {
            $db = Zend_Db_Table::getDefaultAdapter();
            $query1 = "select id from main_employees where is_orghead = 1";
            $result1 = $db->query($query1);
            $row1 = $result1->fetch();
            $head_id = $row1['id'];
            $head_details = array();
            if($head_id != '')
            {
                $query2 = "select userfullname,jobtitle_name from employees_summary where id = ".$head_id;
                $result2 = $db->query($query2);
                $row2 = $result2->fetch();
                $head_details['head_name'] = $row2['userfullname'];
                $head_details['jobtitle_name'] = $row2['jobtitle_name'];
            }
            return $head_details;
        }
	public function getorgrecords()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select o.* from main_company o
					where o.isactive='Y';";
		$result = $db->query($query)->fetchAll();
		
	    return $result;
	}
	public function allemployee()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "SELECT COUNT(*) as 'number'
		FROM main_employees e
					where e.isactive=1;";
		$result = $db->query($query)->fetchAll();
	    return $result;
	}
	public function getSelecteOrgRecords()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select o.company_id, o.name,o.org_image,o.website,DATE_FORMAT(org_start_date,'".DATEFORMAT_MYSQL."') as startedon from main_company o where o.isactive='Y';";
		$result = $db->query($query)->fetch();

	    return $result;
	}
	
	
	
	public function validateOrgStartDate($startdate,$con='',$unitid = '')
	{
	 if($con == 'organisationinfo')
	   {
	    $startdatedata = $this->select()
    					   ->setIntegrityCheck(false)	 
						   ->from(array('b' => 'main_businessunits'),array('unitid'=>'b.id'))
                           ->joinLeft(array('d'=>'main_departments'), 'b.id=d.unitid',array('deptid'=>'d.id'))							   						   
						   ->where('(b.startdate <= "'.$startdate.'" OR d.startdate <= "'.$startdate.'") AND b.startdate !="0000-00-00" 
						             AND d.startdate !="0000-00-00" AND b.isactive = 1 AND d.isactive = 1');
		
		$result = $this->fetchAll($startdatedata)->toArray();
		return $result;	
	   }
      else if($con == 'businessunit')
       {
	    $db = Zend_Db_Table::getDefaultAdapter();
		$query = "";
		if($unitid !='')
		{
			$query .= "SELECT  d.id AS deptid  FROM main_departments AS d WHERE d.startdate <= '".$startdate."' AND d.isactive = 1 AND d.startdate !='0000-00-00' AND d.unitid = ".$unitid." UNION ";
		}      
		$query .= "SELECT o.id FROM main_organisationinfo AS o
                  where o.org_startdate >= '".$startdate."'  AND o.isactive = 1 AND o.org_startdate !='0000-00-00'";
		
		
		$result = $db->query($query)->fetch();
        return $result;		
       }
       else if($con == 'departments' || $con == 'deptunit')
       {
	    $db = Zend_Db_Table::getDefaultAdapter();
		$query = "";
		if($unitid !='')
		{
			$query .= "SELECT  b.id AS unitid  FROM main_businessunits AS b WHERE b.startdate >= '".$startdate."' AND b.isactive = 1 AND b.id = ".$unitid." AND b.startdate !='0000-00-00' UNION ";
		}
		$query .= "SELECT o.id FROM main_organisationinfo AS o
                  where o.org_startdate >= '".$startdate."'  AND o.isactive = 1 AND o.org_startdate !='0000-00-00'";
		
		$result = $db->query($query)->fetch();
        return $result;		
       }	   
	
	}
	
	public function validateEmployeeJoiningDate($startdate,$unitid = '',$deptid='')
	{
	 
	    $db = Zend_Db_Table::getDefaultAdapter();
		$query = "";
		if($unitid !='')
		{
			$query .= "SELECT  b.id AS unitid  
						FROM main_businessunits AS b 
						WHERE b.startdate >= '".$startdate."' AND b.isactive = 1 AND b.id = ".$unitid." UNION "; //  AND b.startdate !='0000-00-00'
		}
		if($deptid !='')
		{
			$query .= "SELECT  d.id AS deptid  
						FROM main_departments AS d 
						WHERE d.startdate >= '".$startdate."' AND d.isactive = 1 AND d.id = ".$deptid." UNION "; // AND d.startdate !='0000-00-00' 
		}  
		
		$query .= "SELECT o.id 
					FROM main_organisationinfo AS o
                  	WHERE o.org_startdate >= '".$startdate."'  AND o.isactive = 1 "; // AND o.org_startdate !='0000-00-00'";
				  
		
		$result = $db->query($query)->fetch();
        return $result;		
   
	
	}
	
	public function getOrgLogo()
	{
	    $db = Zend_Db_Table::getDefaultAdapter();
	    $query = "SELECT o.org_image FROM main_organisationinfo AS o
                  where  o.isactive = 1";
	  
		$result = $db->query($query)->fetch();
        return $result;		
	}
	
	public function changeOrgHead($oldhead, $newhead,$oldheadRM = '')
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$db = Zend_Db_Table::getDefaultAdapter();	
		
		$empmodel = new Default_Model_Employee();
		$oldRMData = $empmodel->getsingleEmployeeData($oldhead);
		try
		{			
			$empQuery1 = "UPDATE main_employees SET reporting_manager = ".$newhead.", modifieddate = '".gmdate("Y-m-d H:i:s")."', modifiedby = ".$loginUserId." WHERE reporting_manager=".$oldhead." and isactive = 1 AND user_id <> ".$newhead.";";
				
			
				
			if($oldheadRM != '')	
			{
				$orgQuery1 = "UPDATE main_employees SET is_orghead = 0, reporting_manager= ".$oldheadRM.", modifieddate = '".gmdate("Y-m-d H:i:s")."', modifiedby = ".$loginUserId." WHERE user_id=".$oldhead." ;";				
				$db->query($orgQuery1);
			}
			
			$orgQuery2 = "UPDATE main_employees SET is_orghead = 1,reporting_manager= 0, modifieddate = '".gmdate("Y-m-d H:i:s")."', modifiedby = ".$loginUserId." WHERE user_id=".$newhead." ;";		
			
			$db->query($orgQuery2);
				
			$db->query($empQuery1);
			return 'success';
		}
		catch(Exception $e)
		{			
			return 'failed';
			
		}
	}
}