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

class Default_Model_Leaverequest extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_leave_request';
	protected $_id = 'leaverequest_id';

	
	
	public function getAvailableLeaves($loginUserId)
	{
	 	$select = $this->select()
    					   ->setIntegrityCheck(false)	
                           ->from(array('e'=>'main_leavesetting'),array('leavelimit'=>'e.alloteddays','remainingleaves'=>'e.alloteddays'))
						   ->joinInner(array('u'=>'main_users'),'e.emp_id=u.empid')
						   ->where('u.id='.$loginUserId);
		return $this->fetchAll($select)->toArray();   
	
	}

	public function getAllLeaves($leaveid)
	{
		$select = $this->select()
					   ->setIntegrityCheck(false)
					   ->from(array('e'=>'main_leave'),array('leavetype'=>'e.name','numberofdays'=>'e.alloteddays','durationtype'=>'e.durationtype'))
					   ->where("e.isactive = 'Y' AND e.leave_id=".$leaveid);
		return $this->fetchAll($select)->toArray();
	}
	
	public function getLeaveAlloted($leaveid)
	{
		$select = $this->select()
					   ->setIntegrityCheck(false)
					   ->from(array('e'=>'main_leave'),array('leavelimit'=>'e.alloteddays','remainingleaves'=>'e.alloteddays'))
					   ->where('e.leave_id = '.$leaveid);
		return $this->fetchAll($select)->toArray();
	}
	public function getLeavebyid($leaveid)
	{
		$select = $this->select()
					   ->setIntegrityCheck(false)
					   ->from(array('e'=>'main_leave'),'e.durationtype')
					   ->where('e.leave_id = '.$leaveid);
					   //die($select);
		return $this->fetchRow($select)->toArray();
	}
	public function SaveorUpdateLeaveRequest($data, $where)
	{
		//ie(json_encode($data));
		$config = array(
			'name'=>'main_leave_request',
			'primary'=>'leaverequest_id'
		);
		$table = new Zend_Db_Table($config);
	    if($where != '')
		{
			$table->update($data, $where);
			return 'update';
		}
		else
		{
			$table->insert($data);
			$id=$table->getAdapter()->lastInsertId('main_leave_request');
			return $id;
		}
	}
	public function DeleteData($where)
	{
		//ie(json_encode($data));
		$config = array(
			'name'=>'main_leave_request',
			'primary'=>'leaverequest_id'
		);
		$table = new Zend_Db_Table($config);
	   
			$table->delete($where);
			return 'delete';
	
	}
	public function getsinglePendingLeavesData($id)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leave_request'),array('l.*'))
					->joinInner(array('ml'=>'main_leave'), 'ml.leave_id=l.leave_id',array('leavename'=>'ml.name'))
					->joinLeft(array('u'=>'main_employees'), 'l.emp_id=u.id',array('userfullname'=>'u.userfullname','useremail'=>'u.emailaddress'))
					->joinLeft(array('mu'=>'main_employees'), 'l.approvedby=mu.id',array('approvedname'=>'mu.userfullname'))
 	  				->where(" l.leaverequest_id = ".$id);
    	return $this->fetchAll($result)->toArray();
	}
	public function getsingleLeavesDatabyemp($id)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leave_request'),array('l.*'))
					->joinInner(array('ml'=>'main_leave'), 'ml.leave_id=l.leave_id',array('leavename'=>'ml.name'))
					->joinLeft(array('u'=>'main_employees'), 'l.emp_id=u.id',array('userfullname'=>'u.userfullname','useremail'=>'u.emailaddress'))
					->joinLeft(array('mu'=>'main_employees'), 'l.approvedby=mu.id',array('approvedname'=>'mu.userfullname'))
 	  				->where(" l.emp_id = ".$id);
    	return $this->fetchAll($result)->toArray();
	}
	public function getsingleLeavessummaryDatabyemp($id)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'empleave_summary'),array('l.*'))
 	  				->where("l.emp_id = ".$id);
    	return $this->fetchAll($result)->toArray();
	}
	public function leavefromsetting($id)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leavesetting'),array('l.*'))
 	  				->where("l.emp_id = ".$id);
    	return $this->fetchAll($result)->toArray();
	}
	
	public function getexpectedLeavessummary($leavesid)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('ml'=>'main_leave'),array('ml.*','approvedrequired'=>'if(ml.approvedrequired="N","No","Yes")','ispreset'=>'if(ml.ispreset="N","No","Yes")','isactive'=>'if(ml.isactive="N","No","Yes")'))
					->joinInner(array('lc'=>'main_leavecategory'), 'ml.leavecategory_id=lc.leavecategory_id',array('categoryname'=>'lc.name'))
 	  				->where(" ml.leave_id not in ( ".$leavesid.") and ml.isactive='Y'");
    	return $this->fetchAll($result)->toArray();
	}

	public function getUserLeavesData($id)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leaverequest'),array('l.*'))
 	  				->where("l.isactive = 1 AND l.user_id = ".$id);
    	return $this->fetchAll($result)->toArray();
	}
	
	public function getUserApprovedOrPendingLeavesData($id)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
       
		
		$query = "SELECT `l`.*,IF(l.leavestatus = 'Approved', 'A', 'P') as status FROM `main_leaverequest` AS `l` WHERE (l.isactive = 1 AND l.user_id = '$id' and l.leavestatus IN(1,2))";
		
        $result = $db->query($query)->fetchAll();
	    return $result;
	}
	
	public function getManagerApprovedOrPendingLeavesData($id)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
       
		
		$query = "SELECT `l`.*,IF(l.leavestatus = 'Approved', 'A', 'P') as status,u.userfullname
				 FROM `main_leaverequest` AS `l` left join main_empoyees u on u.id=l.user_id 
				 WHERE (l.isactive = 1 AND l.rep_mang_id = '$id' and l.leavestatus IN(1,2))";
		
        $result = $db->query($query)->fetchAll();
	    return $result;
	}
	
	public function getReportingManagerId($id)
	{
	    $result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leaverequest'),array('repmanager'=>'l.rep_mang_id','hrmanager'=>'l.hr_id'))
 	  				->where("l.isactive = 1 AND l.id = ".$id);
	
    	return $this->fetchAll($result)->toArray();
	}
	
	
	
	public function getLeaveStatusHistory($sort, $by, $pageNo, $perPage,$searchQuery,$queryflag,$loginUserId,$empids,$perm)
	{	
	    $auth = Zend_Auth::getInstance();
		// 	if($auth->hasIdentity()){
		// 		$loginUserId = $auth->getStorage()->read()->id;
		// }  
	
		 
		/* Removing isactive checking from configuration table */ 
				
		$where = "l.emp_id in (" . $empids . ") ";     
		if($loginUserId != '') 
		$where .= " AND l.emp_id=".$loginUserId ."";     

		if($queryflag !='')
		{
		   if($queryflag == 'pending')
		   {
		     $where .=" AND l.status='Pending'";
		   }
		   else if($queryflag == 'approved')
		   {
		     $where .=" AND l.status = 'Approved' ";
		   }
		   else if($queryflag == 'cancel')
		   {
		     $where .=" AND l.status = 'Cancelled' ";
		   }
		   else if($queryflag == 'rejected')
		   {
		     $where .=" AND l.status = 'Rejected' ";
		   }
		
		}else
		{
		  $where .=" 1";
		}
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		$leaveStatusData =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'empleave_summary'),array('l.*'))
		           ->where($where)
		            ->order("$by $sort") 
                    ->limitPage($pageNo, $perPage);
		// $leaveStatusData = $this->select()
    	// 				   ->setIntegrityCheck(false)	
        //                    ->from(array('l'=>'main_leave_request'),
		// 				          array('l.*','id'=>'leaverequest_id','from_date'=>'DATE_FORMAT(l.leave_from,"'.DATEFORMAT_MYSQL.'")',
		// 						         'to_date'=>'DATE_FORMAT(l.leave_to,"'.DATEFORMAT_MYSQL.'")',
		// 								 'applieddate'=>'DATE_FORMAT(l.createddate,"'.DATEFORMAT_MYSQL.'")',
		// 								 'staus'=>'l.status',
		// 								 'remark'=>'SUBSTRING(l.remark,18,LENGTH(l.remark)-18)'
		// 						       ))
		// 							   ->joinLeft(array('ml'=>'main_leave'), 'ml.leave_id=l.leave_id',array('leavename'=>'ml.name'))

        //                    ->joinLeft(array('u'=>'main_employees'), 'u.id=l.approvedby',array('approvedname'=>'u.userfullname'))
        //                    ->joinLeft(array('mu'=>'main_employees'), 'mu.id=l.emp_id',array('employeename'=>'mu.userfullname'))						                 			   						   
		// 				   ->where($where)
    	// 				   ->order("$by $sort") 
    	// 				   ->limitPage($pageNo, $perPage);
		//die($leaveStatusData);
		return $leaveStatusData;
		
	}
	
	
	public function getEmployeeLeaveRequest($sort, $by, $pageNo, $perPage,$searchQuery,$loginUserId,$empids,$perm)
	{	
		//$where = "l.isactive = 1 AND l.leavestatus IN(1,2) AND u.isactive=1 AND l.rep_mang_id=".$loginUserId." ";
		$where = " l.emp_id in (" . $empids . ") ";     

		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		
	   $employeeleaveData = $this->select()
	   ->setIntegrityCheck(false)	
	   ->from(array('l'=>'main_leave_request'),
			  array( 'l.*','id'=>'leaverequest_id','from_date'=>'DATE_FORMAT(l.leave_from,"'.DATEFORMAT_MYSQL.'")',
					 'to_date'=>'DATE_FORMAT(l.leave_to,"'.DATEFORMAT_MYSQL.'")',
					 'applieddate'=>'DATE_FORMAT(l.createddate,"'.DATEFORMAT_MYSQL.'")',
					 'staus'=>'l.status',
					 'processed'=>'if(l.processed="N","No","Yes")',
					 'approved'=>'if(l.approved="N","No","Yes")',
					 'pro'=>'l.processed',
					//  'app'=>'l.approved',
					 'remark'=>'SUBSTRING(l.remark,18,LENGTH(l.remark)-18)'
					 



				   ))
				   ->joinLeft(array('ml'=>'main_leave'), 'ml.leave_id=l.leave_id',array('leavename'=>'ml.name'))
	   ->joinLeft(array('u'=>'main_employees'), 'u.id=l.approvedby',array('approvedname'=>'u.userfullname'))
	   ->joinLeft(array('mu'=>'main_employees'), 'mu.id=l.emp_id',array('userfullname'=>'mu.userfullname','profileimg'=>'mu.profileimg'))						                 			   						   
	   ->where($where)
	   ->order("$by $sort") 
	   ->limitPage($pageNo, $perPage);			 
		
		//die($employeeleaveData);
		return $employeeleaveData;       		
	}
	
	public function updateemployeeleaves($appliedleavescount,$employeeid)
	{
		
		$db = Zend_Db_Table::getDefaultAdapter();
		$db->query("update main_employeeleaves  set used_leaves = used_leaves+".$appliedleavescount." where user_id = ".$employeeid." AND alloted_year = year(now()) AND isactive = 1 ");		
	
	}
	
	public function updatecancelledemployeeleaves($appliedleavescount,$employeeid)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$db->query("update main_employeeleaves  set used_leaves = used_leaves-".$appliedleavescount." where user_id = ".$employeeid." AND alloted_year = year(now()) AND isactive = 1 ");		
	
	}
	
	public function getUserID($id)
    {
    	$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leave_request'),array('l.emp_id'))
 	  				->where(" l.leaverequest_id = ".$id);
	
    	return $this->fetchAll($result)->toArray();
    }
	
	public function getLeaveRequestDetails($id)
    {
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'empleave_summary'),array('l.*'))
 	  				->where(" l.id = ".$id);
    	return $this->fetchAll($result)->toArray();
    	// $result =  $this->select()
    	// 			->setIntegrityCheck(false) 	
    	// 			->from(array('l'=>'main_leave_request'),array('l.*'))
		// 			->joinInner(array('ml'=>'main_leave'), 'ml.leave_id=l.leave_id',array('leavename'=>'ml.name'))
		// 			->joinLeft(array('u'=>'main_employees'), 'l.emp_id=u.id',array('userfullname'=>'u.userfullname','useremail'=>'u.emailaddress'))
		// 			->joinLeft(array('mu'=>'main_employees'), 'l.approvedby=mu.id',array('approvedname'=>'mu.userfullname'))
 	  	// 			->where(" l.leaverequest_id = ".$id);
    	// return $this->fetchAll($result)->toArray();
    }
	
	public function checkdateexists($from_date, $to_date,$loginUserId)
	{
	    $db = Zend_Db_Table::getDefaultAdapter();
        
		
		$query = "select count(l.id) as dateexist from main_leaverequest l where l.user_id=".$loginUserId." and l.leavestatus IN(1,2) and l.isactive = 1
        and (l.from_date between '".$from_date."' and '".$to_date."' OR l.to_date between '".$from_date."' and '".$to_date."' )";
		
        $result = $db->query($query)->fetchAll();
	    return $result;
	
	}
	
	/* This function is common to manager employee leaves, employee leaves , approved,cancel,pending and rejected leaves
       Here differentiation is done based on objname. 
    */	   
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$objName,$queryflag,$loginUserId,$empids,$perm)
	{	
        $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					// $loginUserId = $auth->getStorage()->read()->id;
		}	
        $searchQuery = '';
        $searchArray = array();
        $data = array();
		if($objName == 'manageremployeevacations')
		{
			if($searchData != '' && $searchData!='undefined')
			{
				$searchValues = json_decode($searchData);
				foreach($searchValues as $key => $val)
				{
					if($key == 'leavename')
					 $searchQuery .= " ml.name like '%".$val."%' AND ";					
					else if($key == 'status')
					{
						$searchQuery .= " l.status like '%".$val."%' AND ";					
					}
					else if($key == 'from_date')
					{
						$searchQuery .= "l.leave_from like '%".  sapp_Global::change_date($val,'database')."%' AND ";
					}
					else if($key == 'to_date')
					{
						$searchQuery .= "l.leave_to like '%".  sapp_Global::change_date($val,'database')."%' AND ";
					}
					else if($key == 'applieddate')
					{	
						$searchQuery .= "l.requested like '%".  sapp_Global::change_date($val,'database')."%' AND ";
					}
					else if($key == 'approved')
					{
						$searchQuery .= " l.approved like '%".$val."%' AND ";					
					}
					else if($key == 'processed')
					{
						$searchQuery .= " l.processed like '%".$val."%' AND ";					
					}
					else 
					 $searchQuery .= " ".$key." like '%".$val."%' AND ";
					$searchArray[$key] = $val;
				}
				$searchQuery = rtrim($searchQuery," AND");	
				//die($searchQuery);				
			}
				
			$tableFields = array('action'=>'Action','leavename' => 'Leave ','userfullname'=>'Employee Name','status' => 'Status','remark' => 'Reason',
			'from_date' => 'From Date','to_date' => 'To Date',
			'applieddate' => 'Applied On','processed' => 'Processed');

                $tablecontent = $this->getEmployeeLeaveRequest($sort, $by, $pageNo, $perPage,$searchQuery,$loginUserId,$empids,$perm);      				
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
					'add' =>'add',
					'perm'=>$perm,
					'call'=>$call,
					'dashboardcall'=>$dashboardcall,
								'search_filters' => array(
									'from_date' =>array('type'=>'datepicker'),
									'to_date' =>array('type'=>'datepicker'),
									'applieddate'=>array('type'=>'datepicker'),
									'approved' => array(
										'type' => 'select',
										'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
										'processed' => array(
											'type' => 'select',
											'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
											'status' => array(
												'type' => 'select',
												'filter_data' => array(''=>'All','Pending'=>'Pending','Approved'=>'Approved','Rejected'=>'Rejected','Cancelled'=>'Cancelled')),
								)
				);
		}
		else if($objName == 'empleavesummary')
		{
		        $managerstring= "true";
				 
		         if($searchData != '' && $searchData!='undefined')
					{
						$searchValues = json_decode($searchData);
						foreach($searchValues as $key => $val)
						{
						  if($key !='leavestatus')
						  {
							if($key == 'reportingmanagername')
							 $searchQuery .= " u.userfullname like '%".$val."%' AND ";
							else if($key == 'employeename')
							 $searchQuery .= " mu.userfullname like '%".$val."%' AND "; 
							else if($key == 'applieddate')
							{
							$searchQuery .= " l.createddate  like '%".  sapp_Global::change_date($val,'database')."%' AND ";
							}
							else if($key == 'from_date' || $key == 'to_date')
							{
								$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
							}
							else 
							 $searchQuery .= " ".$key." like '%".$val."%' AND ";
							
							}
							$searchArray[$key] = $val;
						}
						$searchQuery = rtrim($searchQuery," AND");					
					}
					
				    $statusid = '';				
			        if($queryflag !='')
					{
					   $statusid = $queryflag;
						   if($statusid == 1)
							  $queryflag = 'pending';
						   else if($statusid == 2)
							  $queryflag = 'approved'; 
						   else if($statusid == 3)
							  $queryflag = 'rejected'; 
						   else if($statusid == 4)
							  $queryflag = 'cancel'; 
					}
					else
					{
						$queryflag = 'approved';
					}
					

            //$tableFields = array('action'=>'Action','employeename' => 'Leave Applied By','leavetype' => 'Leave Type','leaveday' => 'Leave Duration','from_date' => 'From Date','to_date' => 'To Date','reason' => 'Reason','approver_comments' => 'Comments','reportingmanagername'=>'Reporting Manager','appliedleavescount' => 'Leave Count','applieddate' => 'Applied On');
            $tableFields = array('action'=>'Action','userfullname' => 'Employee','leavefrom' => 'From Date','leaveto' => 'To Date',
            'leave' => 'Leave', 'status' => 'Status','leavecategory' => 'Leave Category','requested' => 'Applied On');						 
				 
			$leave_arr = array('' => 'All',1 =>'Full Day',2 => 'Half Day');	 
			
			$search_filters = array(
										'leavefrom' =>array('type'=>'datepicker'),
										'leaveto' =>array('type'=>'datepicker'),
										'requested'=>array('type'=>'datepicker'),
										// 'leaveday' => array(
										// 					'type' => 'select',
										// 					'filter_data' => $leave_arr,
										// 				),
										);
										
			
            /* This is for dashboard call.
               Here one additional column Status is build by passing it to table fields
            */ 			   
			if($dashboardcall == 'Yes')
            {
					$tableFields['status'] = "Status";
					$search_filters['status'] = array(
					'type' => 'select',
					'filter_data' => array('pending' => 'Pending for approval','approved'=>'Approved','rejected'=>'Rejected','cancel'=>'Cancelled',),
				);
				if(isset($searchArray['status']))
				{
					$queryflag = $searchArray['status'];
					 if($queryflag =='')
					 {
						$queryflag = 'pending';
					 }	
				}
				
			}
			
			$tablecontent = $this->getLeaveStatusHistory($sort, $by, $pageNo, $perPage,$searchQuery,$queryflag,$loginUserId,$empids,$perm);    
			
			
			if(isset($queryflag) && $queryflag != '') 
		      $formgrid = 'true';
			else 
		      $formgrid = '';  
			  
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
				'add' =>'add',
				'formgrid' => $formgrid,
				'unitId'=>sapp_Global::_encrypt($statusid),
				'call'=>$call,
				'dashboardcall'=>$dashboardcall,
				'search_filters' => $search_filters
			);
		
		 }
		else
		{
			if($searchData != '' && $searchData!='undefined')
			{
				$searchValues = json_decode($searchData);
				foreach($searchValues as $key => $val)
				{
					if($key == 'leavename')
					 $searchQuery .= " ml.name like '%".$val."%' AND ";					
					else if($key == 'status')
					{
						$searchQuery .= " l.status like '%".$val."%' AND ";					
					}
					else if($key == 'from_date')
					{
						$searchQuery .= "l.leave_from like '%".  sapp_Global::change_date($val,'database')."%' AND ";
					}
					else if($key == 'to_date')
					{
						$searchQuery .= "l.leave_to like '%".  sapp_Global::change_date($val,'database')."%' AND ";
					}
					else if($key == 'applieddate')
					{	
						$searchQuery .= "l.requested like '%".  sapp_Global::change_date($val,'database')."%' AND ";
					}
					else if($key == 'remark')
					{
						$searchQuery .= " l.remark like '%".$val."%' AND ";					
					}
					else if($key == 'processed')
					{
						$searchQuery .= " l.processed like '%".$val."%' AND ";					
					}
					else 
					 $searchQuery .= " ".$key." like '%".$val."%' AND ";
					$searchArray[$key] = $val;
				}
				$searchQuery = rtrim($searchQuery," AND");	
				//die($searchQuery);				
			}
				
				/*$tableFields = array('leavetype' => 'Leave Type','leaveday' => 'Leave Duration',
							'from_date' => 'From Date','to_date' => 'To Date','reason' => 'Reason','approver_comments' => 'Comments',
							"reportingmanagername"=>"Reporting Manager",'appliedleavescount' => 'Leave Count',
							'applieddate' => 'Applied On','action'=>'Action',);*/
				if($objName=='pendingleaves' || $objName=='cancelleaves') {	
					$tableFields = array('action'=>'Action','leavename' => 'Leave ','userfullname'=>'Employee Name','status' => 'Status','remark' => 'Reason',
							'from_date' => 'From Date','to_date' => 'To Date',
							'applieddate' => 'Applied On');
				}else{
					$tableFields = array('action'=>'Action','leavename' => 'Leave','userfullname'=>'Employee Name','reason' => 'Reason',
							'from_date' => 'From Date','to_date' => 'To Date','appliedleavescount' => 'Days',
							'applieddate' => 'Applied On','modifieddate' => 'Approved/Rejected On');
				}	
				$leave_arr = array('' => 'All',1 =>'Full Day',2 => 'Half Day');	
				
				$tablecontent = $this->getLeaveStatusHistory($sort, $by, $pageNo, $perPage,$searchQuery,$queryflag,$loginUserId,$empids,$perm);    
				
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
					'add' =>'add',
					'call'=> $call,
					'dashboardcall'=>$dashboardcall,
					'search_filters' => array(
									'from_date' =>array('type'=>'datepicker'),
									'to_date' =>array('type'=>'datepicker'),
									'applieddate'=>array('type'=>'datepicker'),
									'leaveday' => array(
										'type' => 'select',
										'filter_data' => $leave_arr,
									),
								)
				);
        }
		
		return $dataTmp;
	}
	
	public function getUsersAppliedLeaves($userId)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leaverequest'),array('l.from_date','l.to_date'))
 	  				->where("l.isactive = 1 AND l.user_id = ".$userId." AND l.leavestatus IN(1,2)");
		
    	return $this->fetchAll($result)->toArray();
	}
	
	public function checkLeaveExists($applied_from_date,$applied_to_date,$from_date, $to_date,$loginUserId)
	{
	    $db = Zend_Db_Table::getDefaultAdapter();
        
		
		$query = "select count(l.id) as leaveexist from main_leaverequest l where l.user_id=".$loginUserId." and l.leavestatus IN(1,2) and l.isactive = 1
        and ('".$from_date."' between '".$applied_from_date."' and '".$applied_to_date."' OR '".$to_date."' between '".$applied_from_date."' and '".$applied_to_date."' )";
		
        $result = $db->query($query)->fetchAll();
	    return $result;
	
	}
	
public function getLeaveDetails($id)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leave_request'),array('l.*','emp_idb'=>'l.emp_id'))
					->joinInner(array('ml'=>'main_leave'), 'ml.leave_id=l.leave_id',array('leavename'=>'ml.name'))
					->joinLeft(array('u'=>'main_employees'), 'l.emp_id=u.id',array('userfullname'=>'u.userfullname','useremail'=>'u.emailaddress'))
					->joinLeft(array('mu'=>'main_employees'), 'l.approvedby=mu.id',array('approvedname'=>'mu.userfullname'))
 	  				->where(" l.leaverequest_id = ".$id);
    	return $this->fetchAll($result)->toArray();
	}
	
	public function getLeavesCount($userid,$status='') {
		$db = Zend_Db_Table::getDefaultAdapter();
        $leavestatus = "";
        if($status != '')
            $leavestatus = " and l.status = '$status' ";
		
        $query = "select count(*) cnt from main_leave_request l 
                  where l.emp_id = $userid ".$leavestatus;
				 //die($query);
        $result = $db->query($query)->fetch();
        return $result['cnt'];
	}
	public function getHrApprovedOrPendingLeavesData($id)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
    
		$query = "SELECT `l`.*,IF(l.leavestatus = 'Approved', 'A', 'P') as status,u.userfullname
				 FROM `main_leaverequest` AS `l` left join main_employee u on u.id=l.user_id 
				 WHERE (l.isactive = 1 AND l.hr_id = '$id'  and user_id != '$id' and l.leavestatus IN(1,2))";
		
        $result = $db->query($query)->fetchAll();
	    return $result;
	}
}