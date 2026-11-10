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

class Default_Model_Reports extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_leaverequest';
	
	public function getEmpLeaveHistory($sort, $by,$pageNo, $perPage,$searchQuery='')
	{
		$accesscontrol = Zend_Session::namespaceGet('access_control');
		$usrid = $accesscontrol['empids'];
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$where = 'l.emp_id in ('.$usrid.')';
	
		if($searchQuery !='')
		$where .= 'AND '.$searchQuery;
                
		$leaveStatusData = $this->select()
		->setIntegrityCheck(false)
		->from(['l'=>'empleave_summary'],
		[ 'l.*','from_date'=>'DATE_FORMAT(l.leavefrom,"'.DATEFORMAT_MYSQL.'")',
								         'to_date'=>'DATE_FORMAT(l.leaveto,"'.DATEFORMAT_MYSQL.'")',
										 'applieddate'=>'DATE_FORMAT(l.requested,"'.DATEFORMAT_MYSQL.'")',
                                          										 
		])
		
		->where($where)
		->order("$sort $by ")
		->limitPage($pageNo, $perPage);
		//die($leaveStatusData);
		
		return $this->fetchAll($leaveStatusData)->toArray();

	}

	public function getEmpLeaveHistoryCount($searchQuery=''){
		$where = '1 = 1 ';
		if($searchQuery !='')
		$where .= 'AND '.$searchQuery;
			
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['l'=>'empleave_summary'], ['count'=>'COUNT(*)'])
		
		->where($where);
			
		return $this->fetchAll($select)->toArray();

	}
	
	public function getLeaveManagementSummary($sort, $by,$pageNo, $perPage,$searchQuery='')
	{
	    
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$where = 'l.isactive = 1 ';
		if($searchQuery !='')
		$where .= 'AND '.$searchQuery;
		
     	$leaveManagementData = 	$this->select()
								->setIntegrityCheck(false)
								->from(['l'=>'main_leavemanagement_summary'],
								[ 'l.*',
									   'halfday'=>'if(l.is_halfday = 1,"Yes","No")',
									   'leavetransfer'=>'if(l.is_leavetransfer = 1,"Yes","No")',
									   'skipholidays'=>'if(l.is_skipholidays = 1,"Yes","No")', 			   
								])
								->where($where)
								->order("$sort $by ")
								->limitPage($pageNo, $perPage);
		
		return $this->fetchAll($leaveManagementData)->toArray();
	}
	
	public function get_sd_report($param_arr, $per_page, $page_no, $sort_name, $sort_type,$request_for='')
    {
    	$search_str = 'r.isactive = 1';
    	if($request_for!='')
    		$search_str .=' AND r.request_for='.$request_for.'';
    	//echo '<pre>';
    	//print_r($param_arr);
    	//echo "REQ".$request_for;//exit;
		    	
        foreach($param_arr as $key => $value)
        {
        	if($value != '')
            {
            	if($key == 'raised_date')
                	$search_str .= " and DATE(createddate) = '".sapp_Global::change_date($value,'database')."'";
                else if($key=='service_desk_id') {
                	if($request_for==1) {
                		$search_str .= " and r.service_desk_id = '".$value."'";
                	}	
                	else { 	
                		$search_str .= " and ac.category = '".$value."'";
                	}	
                }
             	else if($key=='service_request_id') {
                	if($request_for==1) {
                		$search_str .= " and r.service_request_id = '".$value."'";
                	}	
                	else { 	
                		$search_str .= " and ac.id = '".$value."'";
                	}	
                }						
               	else
                	$search_str .= " and ".$key." = '".$value."'";
       		}
		}
     	$offset = ($per_page*$page_no) - $per_page;
        $db = Zend_Db_Table::getDefaultAdapter();
        $limit_str = " limit ".$per_page." offset ".$offset;
        $count_query = "select count(*) cnt from main_sd_requests_summary r 
        				left join assets ac ON r.service_desk_id = ac.id and ac.isactive=1 
               			left join assets_categories a ON r.service_request_id = a.id AND a.is_active=1 and a.parent=0
               			where ".$search_str;
        $count_result = $db->query($count_query);
        $count_row = $count_result->fetch();
        $count = $count_row['cnt'];
        $page_cnt = ceil($count/$per_page);
            
       	$query = "select if(r.request_for=1,r.service_desk_name,a.name) as service_desk_name,if(r.request_for=1,r.service_request_name,ac.name) as service_request_name,r.id,
         r.request_for,r.sd_requests_id,r.service_desk_id,r.service_desk_conf_id,r.service_request_id,r.priority,r.description,r.attachment,r.status,r.raised_by,r.raised_by_name,
         r.raised_by_empid,r.ticket_number,r.executor_id,r.executor_name,r.executor_comments,r.reporting_manager_id,r.reporting_manager_name,r.approver_status_1,r.approver_status_2,
         r.approver_status_3,r.reporting_manager_status,r.approver_1,r.approver_1_name,r.approver_2,r.approver_2_name,r.approver_3,r.approver_1_comments,r.approver_2_comments,
         r.approver_3_comments,r.reporting_manager_comments,r.to_mgmt_comments,r.to_manager_comments,r.approver_3_name,r.isactive,r.createdby,r.modifiedby,r.createddate,r.modifieddate,     		
         	  DATE_FORMAT(r.createddate,'".DATEFORMAT_MYSQL."') as createddate from main_sd_requests_summary r 
        		 	 left join assets ac ON r.service_desk_id = ac.id and ac.isactive=1 
               		left join assets_categories a ON r.service_request_id = a.id AND a.is_active=1 and a.parent=0 	
        			where ".$search_str." order by ".$sort_name." ".$sort_type." ".$limit_str;
       //exit;
        $result = $db->query($query);
        $rows = $result->fetchAll();
        return ['rows' => $rows,'page_cnt' => $page_cnt];
	}
	
	public function getLeaveManagementCount($searchQuery='')
	{
		$where = 'l.isactive = 1 ';
		if($searchQuery !='')
		$where .= 'AND '.$searchQuery;
			
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['l'=>'main_leavemanagement_summary'], ['count'=>'COUNT(l.id)'])
		->where($where);
			
		return $this->fetchAll($select)->toArray();
	}

	public function getEmpLeaveNamesByIds($empleavetypesArray)
	{
		if(!empty($empleavetypesArray))
		{
			$empLeavetypeArray = [];
			$select = $this->select()
			->setIntegrityCheck(false)
			->from(['et'=>'main_employeeleavetypes'],['et.id','leavetype'=>'et.leavetype'])
			->where('et.id IN(?)',$empleavetypesArray);

			$result =  $this->fetchAll($select)->toArray();
		 
			if(!empty($result))
			{
				foreach($result as $val)
				{
					$empLeavetypeArray[$val['id']]= $val['leavetype'];
				}
			}
			return $empLeavetypeArray;
		}
		
	}

	public function getUserNamesByIds($usersArray)
	{
		if(!empty($usersArray))
		{
			$usernameArray = [];
			$select = $this->select()
			->setIntegrityCheck(false)
			->from(['mu'=>'main_users'],['mu.id','employeename'=>'mu.userfullname'])
			->where('mu.id IN(?)',$usersArray);

			$result =  $this->fetchAll($select)->toArray();
		 
			if(!empty($result))
			{
				foreach($result as $val)
				{
					$usernameArray[$val['id']]= $val['employeename'];
				}
			}
			return $usernameArray;
		}
		
	}

	public function getRepManagerNamesByIds($repmanagerArray)
	{
		if(!empty($repmanagerArray))
		{
			$repmanagernameArray = [];
			$select = $this->select()
			->setIntegrityCheck(false)
			->from(['u'=>'main_users'],['u.id','reportingmanagername'=>'u.userfullname'])
			->where('u.id IN(?)',$repmanagerArray);

			$result =  $this->fetchAll($select)->toArray();
		 
			if(!empty($result))
			{
				foreach($result as $val)
				{
					$repmanagernameArray[$val['id']]= $val['reportingmanagername'];
				}
			}
			return $repmanagernameArray;
		}
		
	}

	

	public function getBusinessUnitsInfo($sortby,$by,$pageNo,$perPage,$searchQuery,$funorder='')
	{
		if($searchQuery != '')
		$where = " WHERE ".$searchQuery;
		else $where = ' WHERE 1=1 ';
		$where .= ' AND b.sbunit_id <> 0';

		if($pageNo != 0)
		$limitpage = (($pageNo-1)*$perPage);
		else
		$limitpage = 0;
		
		$db = Zend_Db_Table::getDefaultAdapter();
		if($funorder != '')
		{
			$query = "SELECT b.*,COUNT(b.sbunit_id) AS deptcount, b.name as unitname,b.code as unitcode,
						if(b.isactive = 'N','Inactive','Active') as status,COUNT(DISTINCT(ep.employee_id)) AS noofemp,
						DATE_FORMAT(b.createddate,'".DATEFORMAT_MYSQL."') as startdate					
						FROM main_sbunit b 
						left join main_empposition ep on b.sbunit_id=ep.sbunit_id
						".$where." GROUP BY b.sbunit_id ORDER BY ".$funorder." LIMIT ".$limitpage.", ".$perPage;
		}
		else
		{
			$query = "SELECT b.*,COUNT(b.sbunit_id) AS deptcount, b.name as unitname,b.code as unitcode,
						if(b.isactive = 'N','Inactive','Active') as status,COUNT(DISTINCT(ep.employee_id)) AS noofemp,
						DATE_FORMAT(b.createddate,'".DATEFORMAT_MYSQL."') as startdate					
						FROM main_sbunit b
						left join main_empposition ep on b.sbunit_id=ep.sbunit_id

						".$where." GROUP BY b.sbunit_id ORDER BY ".$sortby." ".$by." LIMIT ".$limitpage.", ".$perPage;
		}
		//die($query);
		$result = $db->query($query)->fetchAll();
		return $result;
	}

	public function getBusinessUnitsCount($searchQuery)
	{
		if($searchQuery != '')
		$where = " WHERE ".$searchQuery;
		else $where = ' WHERE 1=1 ';
		$where .= ' AND b.sbunit_id <> 0';

		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "SELECT count(*) AS count
					FROM main_sbunit b
					".$where." GROUP BY b.sbunit_id";	
		
		$result = $db->query($query)->fetchAll();
		return count($result);
	}

	public function getDepartmentsInfo($sortby,$by,$pageNo,$perPage,$searchQuery,$funorder='')
	{
		if($searchQuery != '')
		$where = " WHERE ".$searchQuery;
		else $where = ' WHERE 1=1 ';
		if($pageNo != 0)
		$limitpage = (($pageNo-1)*$perPage);
		else
		$limitpage = 0;

		$db = Zend_Db_Table::getDefaultAdapter();
		if($funorder != '')
		{			

			$query = "SELECT d.*,d.name as deptname,d.code as deptcode,count(DISTINCT(ep.employee_id)) AS empcount,
						IF(d.isactive = 'N','Inactive','Active') AS status
						FROM main_department d
						LEFT JOIN main_empposition ep ON d.department_id = ep.department_id
						".$where." GROUP BY d.id ORDER BY ".$funorder." LIMIT ".$limitpage.", ".$perPage;

		}
		else
		{
			$query = "SELECT d.name as deptname,d.code as deptcode,count(DISTINCT(ep.employee_id)) AS empcount,
						IF(d.isactive ='N','Inactive','Active') AS status
						FROM main_department d
						LEFT JOIN main_empposition ep ON d.department_id = ep.department_id
						".$where." GROUP BY d.department_id ORDER BY ".$sortby." ".$by." LIMIT ".$limitpage.", ".$perPage;

		}
		//die($query);
		$result = $db->query($query)->fetchAll();
		return $result;
	}

	public function getDepartmentsCount($searchQuery)
	{
		if($searchQuery != '')
		$where = " WHERE ".$searchQuery;
		else $where = ' WHERE 1=1 ';

		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "SELECT count(*) AS count
					FROM main_department d
					LEFT JOIN main_employees e ON d.representative_id = e.id			
					".$where." GROUP BY d.department_id";	
		$result = $db->query($query)->fetchAll();
		return count($result);
	}



	

	public function getAutoReportBunit($search_str,$flag = '')
	{
		if($flag == '')
		$where = ' and b.sbunit_id<>0 ';
		else $where = '';
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select b.sbunit_id bid,b.name,b.code,
					case 
						when b.name like '".$search_str."%' then 4  
						when b.name like '__".$search_str."%' then 2 
						when b.name like '_".$search_str."%' then 3 
						when b.name like '%".$search_str."%' then 1 
					else 0 end uname 
					from main_sbunit b
					where b.name like '%".$search_str."%'
					".$where." order by uname desc
					limit 0,10";		
		$result = $db->query($query);
		$res_arr = [];
		$res_arr = $result->fetchAll();
		return $res_arr;
	}

	public function getAutoReportBunitcode($search_str)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select b.sbunit_id bid,b.code,
					case 
						when b.code like '".$search_str."%' then 4  
						when b.code like '__".$search_str."%' then 2 
						when b.code like '_".$search_str."%' then 3 
						when b.code like '%".$search_str."%' then 1 
					else 0 end ucode 
					from main_sbunit b
					where b.code like '%".$search_str."%'
					and b.sbunit_id<>0 order by ucode desc
					limit 0,10";

		$result = $db->query($query);
		$res_arr = [];
		$res_arr = $result->fetchAll();
		return $res_arr;
	}

	public function getAutoReportDeptcode($search_str)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select d.department_id did,d.code deptcode,
					case 
						when d.code like '".$search_str."%' then 4  
						when d.code like '__".$search_str."%' then 2 
						when d.code like '_".$search_str."%' then 3 
						when d.code like '%".$search_str."%' then 1 
					else 0 end dcode 
					from main_department d
					where d.code like '%".$search_str."%'
					and d.department_id<>0 order by dcode desc
					limit 0,10";

		$result = $db->query($query);
		$res_arr = [];
		$res_arr = $result->fetchAll();
		return $res_arr;
	}

	public function getAutoReportDept($search_str)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select d.department_id did,d.name deptname,d.code deptcode,
					case 
						when d.name like '".$search_str."%' then 4  
						when d.name like '__".$search_str."%' then 2 
						when d.name like '_".$search_str."%' then 3 
						when d.name like '%".$search_str."%' then 1 
					else 0 end dname 
					from main_department d
					where d.name like '%".$search_str."%'
					and d.department_id<>0 order by dname desc
					limit 0,10";
		$result = $db->query($query);
		$res_arr = [];
		$res_arr = $result->fetchAll();
		return $res_arr;
	}

	public function getCityOrder($order)
	{
		$qry = "select city_org_id from main_cities order by city {$order}";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$cityArr = $sqlRes->fetchAll();

		foreach($cityArr as $key => $cityid){
			$cityArray[] = $cityid['city_org_id'];

		}
		return $cityArray;
	}

	public function getStateOrder($order)
	{
		$qry = "select state_id_org from main_states order by state {$order}";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$stateArr = $sqlRes->fetchAll();

		foreach($stateArr as $key => $stateid){
			$stateArray[] = $stateid['state_id_org'];

		}
		return $stateArray;
	}
	public function getCountryOrder($order)
	{
		$qry = "select country_id_org from main_countries order by country {$order}";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$countryArr = $sqlRes->fetchAll();

		foreach($countryArr as $key => $countryid){
			$countryArray[] = $countryid['country_id_org'];

		}
		return $countryArray;
	}
	public function getEmpRoleOrder($order)
	{
		$emprole = "";
		$qry = "select ".ROLE_ARRAY['colid']." as id from ".ROLE_ARRAY['tablename']." order by ".ROLE_ARRAY['colname']." {$order}";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$emprole = $sqlRes->fetchAll();

		$roleorderidArray = [];
		foreach($emprole as $key => $roleid){
			$roleorderidArray[] = $roleid['id'];

		}

		return $roleorderidArray;
	}
	public function getEmpgroupOrder($order)
	{
		$emprole = "";
		$qry = "select ".GROUP_ARRAY['colid']." from ".GROUP_ARRAY['tablename']." order by ".GROUP_ARRAY['colname']." {$order}";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$empgroup = $sqlRes->fetchAll();

		$grouporderidArray = [];
		foreach($empgroup as $key => $groupid){
			$grouporderidArray[] = $groupid['id'];

		}

		return $grouporderidArray;
	}

	/**
	 * This function gives data for report.
	 * @parameters
	 * @sort          = ascending or descending
	 * @by            = name of field which to be sort
	 * @pageNo        = page number
	 * @perPage       = no.of records per page
	 * @searchQuery   = search string
	 *
	 * @return  ResultSet;
	 */
	public function getUserWiseLogData($by="logindatetime",$order="desc",$pageNo=1, $perPage=20,$searchQuery=1,$selectfield=['*'])
	{
		$where = "1";
		$by = trim($by);

		if($searchQuery)
		$where .= " AND ".$searchQuery;

		if($by == ''){

			$select = $this->select()
			->setIntegrityCheck(false)
			->from([$this->_name],$selectfield)
			->where($where)
			->order($order)
			->limitPage($pageNo, $perPage);
		}else{
			$select = $this->select()
			->setIntegrityCheck(false)
			->from([$this->_name],$selectfield)
			->where($where)
			->order("$by $order")
			->limitPage($pageNo, $perPage);
		}

		
		$userLogRecords = $this->fetchAll($select)->toArray();
		

		return $userLogRecords;
	}

	public function getUserLogCount($searchQuery = 1)
	{
		$where = "1";

		if($searchQuery)
		$where .= " AND ".$searchQuery;

		$qry = "select count(*) as count from main_userloginlog where {$where}";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$count = $sqlRes->fetchAll();
			
		return $count[0]['count'];
	}
	/**
	 ** requisition stats report.
	 **/
	public function getRequisitionStats()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select count(case when req_status = 'Initiated' then (id) end) as initiated_req, count(case when req_status = 'Approved' then (id) end) as approved_req, count(case when req_status = 'Rejected' then (id) end) as rejected_req,count(case when req_status = 'Closed' then (id) end) as closed_req,
count(case when req_status = 'On hold' then (id) end) as onhold_req, count(case when req_status = 'Complete' then (id) end) as complete_req, count(case when req_status = 'In process' then (id) end) as inprocess_req from main_requisition where isactive = 1 and year(createdon) = year(now());";
		$result = $db->query($query);
		$rows = $result->fetchAll();
		return $rows;
	}

	/**
	 ** user login stats report.
	 **/
	public function getUserloginStats()
	{
            $db = Zend_Db_Table::getDefaultAdapter();
            
            $query = "select count(ul.id) as cnt, day(nn.thedate) as logindate 
                      from main_userloginlog ul 
                      right join ( select DATE_FORMAT(NOW() ,'%Y-%m-01') + INTERVAL n DAY AS thedate 
                      from numbers n where DATE_FORMAT(NOW() ,'%Y-%m-01') + INTERVAL n DAY <= last_day(now())) nn 
                      on nn.thedate = date(ul.logindatetime)  group by day(nn.thedate); ";
            $result = $db->query($query);
            $rows = $result->fetchAll();
            return $rows;
	}

    /**
	 ** Ateration Status report.
	 **/
	public function getAterationStats()
	{
            $db = Zend_Db_Table::getDefaultAdapter();
            
            $query = "SELECT count(`id`) as cnt ,YEAR(`date_of_leaving`) as yeear from main_employees where YEAR( `date_of_leaving`) >= YEAR(DATE_SUB(CURDATE(), INTERVAL 4 YEAR))
 GROUP BY YEAR( `date_of_leaving`) ORDER BY YEAR( `date_of_leaving`) DESC;";
            $result = $db->query($query);
            $rows = $result->fetchAll();
            return $rows;
	}

	public function getBusinessunitOrder($order)
	{
		$qry = "select id from main_businessunits order by unitname {$order}";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$BUArr = $sqlRes->fetchAll();

		foreach($BUArr as $key => $BU){
			$BUArray[] = $BU['id'];

		}
		return $BUArray;
	}


	public function getEmpHolidayResult($sortby,$by,$pageNo,$perPage)
	{
		if($pageNo != 0)
		$limitpage = (($pageNo-1)*$perPage);
		else
		$limitpage = 0;

		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select g.holidaycategory_id,g.name,count(distinct h.holiday_id) as datecount,count(distinct e.id) as empcount
		          from main_holidaycategory as g 
                  left join main_holidaydates as h on h.holidaycategory_id = g.holidaycategory_id and h.isactive=1
                  left join main_employees as e on e.holiday_group = g.holidaycategory_id and e.isactive=1
                  where g.isactive =1 and h.holidayyear = year(now()) group by g.holidaycategory_id ORDER BY ".$sortby." ".$by." LIMIT ".$limitpage.", ".$perPage;
				#die($query);
		$result = $db->query($query);
		$data = $result->fetchAll();
		
		return $data;

	}

	public function getEmpHolidayCount()
	{


		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select count(*) as count
		          from main_holidaycategory as g 
                  left join main_holidaydates as h on h.holidaycategory_id = g.holidaycategory_id and h.isactive=1
                  left join main_employees as e on e.holiday_group = g.holidaycategory_id and e.isactive=1
                  where g.isactive = 1 and h.holidayyear = year(now()) group by g.holidaycategory_id ";
		
		$result = $db->query($query);
		$data = $result->rowCount();

		return $data;

	}

	/**
	 ** Employee stats report.
	 **/
	public function getEmpStats()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select count(case when isactive = 1 then user_id end) as 'active',count(case when isactive = 0 then user_id end) as 'inactive',count(case when isactive = 2 then user_id end) as 'resigned',count(case when isactive = 3 then user_id end) as 'left',count(case when isactive = 4 then user_id end) as 'suspended',count(case when isactive = 5 then user_id end) as 'deleted'
from main_userrole 
where role_id in (select ".ROLE_ARRAY['colid']." as id from ".ROLE_ARRAY['tablename']." where ".ROLE_ARRAY['colgroupid']." <> ".USERS_GROUP.");";
		$res = $db->query($query);
		$empStats = $res->fetchAll();
		return $empStats;

	}

	/**
	 * This function gives data for report.
	 * @parameters
	 * @sort          = ascending or descending
	 * @by            = name of field which to be sort
	 * @pageNo        = page number
	 * @perPage       = no.of records per page
	 * @searchQuery   = search string
	 *
	 * @return  ResultSet;
	 */
	public function getAgencyListReportData($by="agencyname",$order="asc",$pageNo=1, $perPage=20,$searchQuery=1,$selectfield=['*'])
	{
		$where = "isactive = 1";
		$by = trim($by);

		if($searchQuery)
		$where .= " AND ".$searchQuery;

		if($by == ''){

			$select = $this->select()
			->setIntegrityCheck(false)
			->from(['main_bgagencylist'],$selectfield)
			->where($where)
			->order($order)
			->limitPage($pageNo, $perPage);
		}
		else if($pageNo == ''){
			$select = $this->select()
			->setIntegrityCheck(false)
			->from(['main_bgagencylist'],$selectfield)
			->where($where);

		}else{
			$select = $this->select()
			->setIntegrityCheck(false)
			->from(['main_bgagencylist'],$selectfield)
			->where($where)
			->order("$by $order")
			->limitPage($pageNo, $perPage);
		}

		
		$agencyListReportRecords = $this->fetchAll($select)->toArray();
		

		return $agencyListReportRecords;
	}

	public function getAgencyListReportCount($searchQuery = 1)
	{
		$where = "isactive = 1";

		if($searchQuery)
		$where .= " AND ".$searchQuery;

		$qry = "select count(*) as count from main_bgagencylist where {$where}";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$count = $sqlRes->fetchAll();
			
		return $count[0]['count'];
	}

	public function getBgCheckNamesByIds($bgcheckidArray){
		$resultstring = implode(',', $bgcheckidArray);
		$bgcheckArray = [];
		if($resultstring)
		{
			try
			{
				$qry = "select ob.id, ob.type from main_bgchecktype ob
                                        where ob.id IN (".$resultstring.") and ob.isactive = 1";		
				$db = Zend_Db_Table::getDefaultAdapter();
				$sqlRes = $db->query($qry);
				$bgcheckRes = $sqlRes->fetchAll();

				if(!empty($bgcheckRes))
				{
					foreach($bgcheckRes as $bgcheck)
					{
						$bgcheckArray[$bgcheck['id']]= $bgcheck['type'];
					}
				}
			}
			catch(Exception $e)
			{
				echo "Error Encountered - ".$e->getFile();
			}
		}
		return $bgcheckArray;
	}

	/**
	 ** Employee leaves by day report.
	 **/
	public function getEmpLeavesByDay()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$yearStr = date('Y'); $monthStr = date('m');
		$fromDate = "'".$yearStr."-".$monthStr."-01'";
		$toDate = "'".$yearStr."-".$monthStr."-31'";
		$query = "select day(nn.thedate) theday,count(distinct user_id) cnt 
                          from main_leaverequest lr 
                          right join (select ".$fromDate." + INTERVAL n DAY AS thedate 
                          from numbers n where ".$fromDate." + INTERVAL n DAY <= ".$toDate.") nn on nn.thedate 
                          between from_date and to_date and leavestatus = 'Approved' group by nn.thedate;";
		$res = $db->query($query);
		$empleavesByDay = $res->fetchAll();
		return $empleavesByDay;

	}
	public function getAutoAgencyName($search_str)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select DISTINCT(u.agencyname),
                  case
                   when u.agencyname like '".$search_str."%' then 4
                   when u.agencyname like '__".$search_str."%' then 2 
                   when u.agencyname like '_".$search_str."%' then 3 
                   when u.agencyname like '%".$search_str."%' then 1 
                  else 0 end agency 
                  from main_bgagencylist u where (u.agencyname like '%".$search_str."%') and isactive = 1
                  order by agency desc
                  limit 0,10";
		$result = $db->query($query);
		$agency_arr = [];
		$agency_arr = $result->fetchAll();
		return $agency_arr;
	}

	public function getAutoAgencyWebsiteUrl($search_str)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select u.website_url,
                  case
                   when u.website_url like '".$search_str."%' then 4
                   when u.website_url like '__".$search_str."%' then 2 
                   when u.website_url like '_".$search_str."%' then 3 
                   when u.website_url like '%".$search_str."%' then 1 
                  else 0 end agency 
                  from main_bgagencylist u where (u.website_url like '%".$search_str."%') and isactive = 1
                  order by agency desc
                  limit 0,10";
		$result = $db->query($query);
		$agency_arr = [];
		$agency_arr = $result->fetchAll();
		return $agency_arr;
	}
	/**
	 ** Activity by Date report
	 **/
	public function getActivityByDate()
	{
            $db = Zend_Db_Table::getDefaultAdapter();
		
            $query = "select count(lm.id) cnt,day(nn.thedate) theday,ifnull(lm.user_action,1) user_action 
                      from main_logmanager lm right join ( select DATE_FORMAT(NOW() ,'%Y-%m-01') + INTERVAL n DAY AS thedate 
                      from numbers n where DATE_FORMAT(NOW() ,'%Y-%m-01') + INTERVAL n DAY <= last_day(now())) nn 
                      on nn.thedate = date(lm.last_modifieddate) and lm.menuId != 0 group by day(nn.thedate),lm.user_action;";
                
                
            $res = $db->query($query);
            $activityByDate = $res->fetchAll();

            return $activityByDate;
	}
	/**
	 ** Employees by Department report
	 **/
	
	public function getEmpByDepartment()
	{
            $db = Zend_Db_Table::getDefaultAdapter();
            $query = "select count(a.id) cnt,d.name,d.code from main_empposition a 
                      inner join main_department d on a.department_id = d.department_id group by d.department_id order by cnt desc limit 10; ";
            $res = $db->query($query);
            return $res->fetchAll();
	}
	public function getEmpBysbunit()
	{
            $db = Zend_Db_Table::getDefaultAdapter();
            $query = "select COALESCE(count(a.id),0) cnt,d.name,d.code from main_empposition a 
                      inner join main_sbunit d on a.sbunit_id = d.sbunit_id group by d.sbunit_id order by cnt desc limit 10; ";
            $res = $db->query($query);
			
            return $res->fetchAll();
	}
	public function getEmpBydivision()
	{
            $db = Zend_Db_Table::getDefaultAdapter();
            $query = "select COALESCE(count(a.id),0) cnt,d.name,d.code from main_empposition a 
                      inner join main_division d on a.division_id = d.division_id group by d.division_id order by cnt desc limit 10; ";
            $res = $db->query($query);
			
            return $res->fetchAll();
	}
	public function getEmpBysection()
	{

            $db = Zend_Db_Table::getDefaultAdapter();
            $query = "select COALESCE(count(a.id),0) cnt,d.name,d.code from main_empposition a 
                      inner join main_section d on a.section_id = d.section_id group by d.section_id order by cnt desc limit 10; ";
            $res = $db->query($query);
            return $res->fetchAll();
			
	}
	public function getEmpscreeningInfo($sortby,$by,$pageNo,$perPage,$searchQuery)
	{
		if($searchQuery != '')
		$where = " WHERE ".$searchQuery;
		else $where = ' WHERE 1=1 ';
		
		if($pageNo != 0)
		$limitpage = (($pageNo-1)*$perPage);
		else
		$limitpage = 0;
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "SELECT *,DATE_FORMAT(createddate,'".DATEFORMAT_MYSQL."') as createddate	FROM main_bgchecks_summary $where GROUP BY id ORDER BY ".$sortby." ".$by." LIMIT ".$limitpage.", ".$perPage;		
		$result = $db->query($query)->fetchAll();
		return $result;		
	}
	
	public function getspecimenNames($search_str)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select distinct(specimen_name),id,
					case 
						when specimen_name like '".$search_str."%' then 4  
						when specimen_name like '__".$search_str."%' then 2 
						when specimen_name like '_".$search_str."%' then 3 
						when specimen_name like '%".$search_str."%' then 1 
					else 0 end sname 
					from main_bgchecks_summary 
					where specimen_name like '%".$search_str."%'
					group by sname order by sname desc
					limit 0,10";		
		$result = $db->query($query);
		$res_arr = [];
		$res_arr = $result->fetchAll();
		return $res_arr;
	}
	
	public function getagencyNames($search_str)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select agencyname,id,
					case 
						when agencyname like '".$search_str."%' then 4  
						when agencyname like '__".$search_str."%' then 2 
						when agencyname like '_".$search_str."%' then 3 
						when agencyname like '%".$search_str."%' then 1 
					else 0 end aname 
					from main_bgchecks_summary 
					where agencyname like '%".$search_str."%'
					group by aname order by aname desc
					limit 0,10";		
		$result = $db->query($query);
		$res_arr = [];
		$res_arr = $result->fetchAll();
		return $res_arr;
	}
	
	public function getEmpscreeningCount($searchQuery)
	{
		if($searchQuery != '')
		$where = " WHERE ".$searchQuery;
		else $where = ' WHERE 1=1 ';		

		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "SELECT *,DATE_FORMAT(createddate,'".DATEFORMAT_MYSQL."') as createddate	FROM main_bgchecks_summary $where GROUP BY id ";	
		
		$result = $db->query($query)->fetchAll();
		return count($result);
	}
	
}