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

class Default_Model_Activitylog extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_logmanager';
	protected $_primary = 'id';

	public function getLogManagerData($page=0,$perPage = 20)
	{
		$pageNo = $page + 1;
		$perPage = 20;

		$db = Zend_Db_Table::getDefaultAdapter();
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['cc'=>'main_logmanager'],['cc.*'])
		->where('cc.is_active = 1 and cc.user_action != 4')
		->order("cc.id DESC")
		->limitPage($pageNo, $perPage);
			
		return $this->fetchAll($select)->toArray();
	}

	public function getLogManagerDataSort($page=0,$perPage = 20,$sortfield = null,$order = null)
	{
		$by = 'cc.'.$sortfield;

		$db = Zend_Db_Table::getDefaultAdapter();
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['cc'=>'main_logmanager'],['cc.*'])
		->where('cc.is_active = 1 and cc.user_action != 4')
		->order("$by $order")
		->limitPage($page, $perPage);
			
		return $this->fetchAll($select)->toArray();
	}

	public function getLogManagerCount()
	{

		$qry = "select count(*) as count from main_logmanager where is_active = 1 and user_action != 4";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$count = $sqlRes->fetchAll();

		return $count[0]['count'];
	}


	public function getMenuNamesByIds($menuIdArray)
	{
		$menuArray = [];
	    if(count($menuIdArray) > 0){
		    $resultstring = implode(',', $menuIdArray);
			
			if($resultstring)
			{
				try
				{
					$qry = "select ob.id, ob.menuName,ob.url from main_menu ob
	                                        where ob.id IN (".$resultstring.") and ob.isactive = 1";		
					$db = Zend_Db_Table::getDefaultAdapter();
					$sqlRes = $db->query($qry);
					$menuRes = $sqlRes->fetchAll();
	
					if(!empty($menuRes))
					{
						foreach($menuRes as $menu)
						{
							$menuArray[$menu['id']]['name']= $menu['menuName'];
							$menuArray[$menu['id']]['url']= $menu['url'];
	
						}
					}
				}
				catch(Exception $e)
				{
					echo "Error Encountered - ".$e->getFile();
				}
			}
	    }
		return $menuArray;
	}

	public function getuserNamesByIds($userArray)
	{
	
		$userResultArray = [];			
		if(count($userArray) > 0){
		    $resultstring = implode(',', $userArray);
			
			if($resultstring)
			{
				try
				{
					$qry = "select u.id,u.userfullname,u.employeeId,u.profileimg from main_users u  where u.id IN (".$resultstring.") and u.isactive = 1";
					$db = Zend_Db_Table::getDefaultAdapter();
					$sqlRes = $db->query($qry);
					$userRes = $sqlRes->fetchAll();
	
					if(!empty($userRes))
					{
						foreach($userRes as $user)
						{
							$userResultArray[$user['id']]['userfullname'] = $user['userfullname'];
							$userResultArray[$user['id']]['employeeId'] = $user['employeeId'];
							$userResultArray[$user['id']]['previewImg'] = $user['profileimg'];
						}
					}
				}
				catch(Exception $e)
				{
					echo "Error Encountered - ".$e->getFile();
				}
			}
	    }
		return $userResultArray;
	}

	public function getUserLogManagerData($page=0,$perPage = 2,$where = 1)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['cc'=>'main_userloginlog'],['cc.*'])
		->order("cc.logindatetime DESC")
		->limitPage($page, $perPage);
			
		return $this->fetchAll($select)->toArray();
	}

	public function getUserLogManagerDataSearch($page=0,$perPage = 2,$where = 1)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['cc'=>'main_userloginlog'],['cc.*'])
		->where($where)
		->order("cc.logindatetime DESC")
		->limitPage($page, $perPage);
			
		return $this->fetchAll($select)->toArray();
	}

	public function getUserLogManagerSort($page=0,$perPage = 1,$sortfield = null,$order = null)
	{
		
		$by = 'cc.'.$sortfield;

		$db = Zend_Db_Table::getDefaultAdapter();
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['cc'=>'main_userloginlog'],['cc.*'])
		->order("$by $order")
		->limitPage($page, $perPage);

		

		return $this->fetchAll($select)->toArray();
	}

	public function getUserLogCount()
	{
		$qry = "select count(*) as count from main_userloginlog";
		$db = Zend_Db_Table::getDefaultAdapter();
		$sqlRes = $db->query($qry);
		$count = $sqlRes->fetchAll();
			
		return $count[0]['count'];
	}

	
	public function getgroupIdByString($searchString){
		$groupArray = [];
		$groupArray[0] = 0;
		if($searchString)
		{
			try
			{
				$qry = "select ob.group_id from ".GROUP_ARRAY['tablename']." ob
                                        where ob.".GROUP_ARRAY['colname']." LIKE '%".$searchString."%' and ob.".GROUP_ARRAY['colisactive']." = 'Y'";		

				$db = Zend_Db_Table::getDefaultAdapter();
				$sqlRes = $db->query($qry);
				$groupIds = $sqlRes->fetchAll();

				if(!empty($groupIds))
				{
					foreach($groupIds as $group)
					{
						$groupArray[]= $group['id'];
					}
				}
			}
			catch(Exception $e)
			{
				echo "Error Encountered - ".$e->getFile();
			}
		}
		return $groupArray;
	}

	public function getemproleIdByString($searchString){
		$empRoleArray = [];
		$empRoleArray[0] = 0;
		if($searchString)
		{
			try
			{
				$qry = "select ob.id from ".ROLE_ARRAY['tablename']." ob
                                        where ob.".ROLE_ARRAY['colname']." LIKE '%".$searchString."%' and ob.".ROLE_ARRAY['colisactive']." = 'Y'";		

				$db = Zend_Db_Table::getDefaultAdapter();
				$sqlRes = $db->query($qry);
				$emproleIds = $sqlRes->fetchAll();

				if(!empty($emproleIds))
				{
					foreach($emproleIds as $role)
					{
						$empRoleArray[]= $role['id'];
					}
				}
			}
			catch(Exception $e)
			{
				echo "Error Encountered - ".$e->getFile();
			}
		}
		return $empRoleArray;
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
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$p1,$p2,$p3,$p4,$p5)
	{
		$menu_model = new Default_Model_Menu();
		$user_model = new Default_Model_Users();
		$searchQuery = '';
		$searchArray = [];
		$data = [];

		if($searchData != '' && $searchData!='undefined')
		{
			$searchValues = json_decode($searchData);
			if(count($searchValues) >0)
			{
				foreach($searchValues as $key => $val)
				{
					if($key == 'last_modifieddate')
					{
						$searchQuery .= " ".$key." like '%".  sapp_Global::getGMTformatdate(urldecode($val))."%' AND ";
					}else
					$searchQuery .= " ".$key." like '%".$val."%' AND ";
					$searchArray[$key] = $val;
				}
				$searchQuery = rtrim($searchQuery," AND");
			}
		}
		$objName = 'logmanager';

		$tableFields = ['action'=>'Action',
		                     'menuName' => 'Menu Name',
		                     'id' => 'ID',
                             'userfullname' => 'Last Modified By',
		                     'profileimg'=>'Profile',
							 'employeeId' => 'Employee ID',                            
		                     'menuUrl' => 'Url',
                             'user_action'=>'Action',
		                     'key_flag' =>'Last Modified Record',		
                             'last_modifieddate' => 'Last Modified Date',

		];

		$tablecontent = $this->getActivitylogData($sort, $by, $pageNo, $perPage,$searchQuery);

		$menu_data = $menu_model->getMenusListForActivitylog();
		$menu_arr = [];

		foreach($menu_data as $gkey => $gdata)
		{
		 
			$menu_arr[$gdata['menuname']] = $gdata['menuname'];
		}
		$user_data = $user_model->getUserListForActivitylog();
		$user_arr = [];
		foreach($user_data as $gkey => $gdata)
		{
			$user_arr[$gdata['userfullname']] = $gdata['userfullname'];
		}
        $useractionArray = ['1' => 'Add','5'=>'Cancel','3'=> 'Delete','2' => 'Edit'];
		$dataTmp = [
                'sort' => $sort,
                'by' => $by,
                'pageNo' => $pageNo,
                'perPage' => $perPage,				
                'tablecontent' => $tablecontent,
                'objectname' => $objName,
		        'menuName'=>'Activity log',
                'extra' => [],
                'tableheader' => $tableFields,
                'jsGridFnName' => 'getAjaxgridData',
                'jsFillFnName' => '',
                'searchArray' => $searchArray,
                'call'=>$call,
                'search_filters' => [
                    'menuname' =>[
                        'type'=>'select',
                        'filter_data' => [''=>'All']+$menu_arr,
		             ],
					'userfullname' =>[
			                        'type'=>'select',
			                        'filter_data' => [''=>'All']+$user_arr,
		            ],
		            'last_modifieddate' =>['type'=>'datepicker'],
		            'user_action' =>[
			                        'type'=>'select',
			                        'filter_data' => [''=>'All']+$useractionArray,
		            ],	
		],
		];
		
		return $dataTmp;
	}

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
	public function getActivitylogData($sort, $by, $pageNo, $perPage,$searchQuery)
	{
		$str = '';
		$where = "r.is_active = 1 AND r.user_action != 4";

		if($searchQuery)
		$where .= " AND ".$searchQuery;
		
		if($by == 'user_action' && $sort == 'ASC')
		{
			$str = "case user_action
				when 1 then 1
				when 5 then 2
				when 3 then 3
				when 2 then 4 
				else 99 end";
		}
		else if($by == 'user_action' && $sort == 'DESC') {
			$str = "case user_action
				when 2 then 1
				when 3 then 2
				when 5 then 3
				when 1 then 4 
				else 99 end";
		}	
		
		if($by == 'user_action')
		{
			$activitylogData = $this->select()
			->setIntegrityCheck(false)
			->from(['r'=>$this->_name],['r.*',])
			->joinInner(['m'=>'main_menu'], "m.id = r.menuId and m.isactive = 1",['menuName'=>'m.menuName','menuUrl'=>'if(m.url="/addperk","/perk",if(m.url="/adddeductive","deductive",m.url))'])
			->joinInner(['u'=>'main_users'], "u.id = r.last_modifiedby and u.isactive = 1",['userfullname'=>'u.username','employeeId'=>'u.empid'])
			->where($where)			
			->order(new Zend_Db_Expr($str))
			->limitPage($pageNo, $perPage);		
		}else{
			$activitylogData = $this->select()
		->setIntegrityCheck(false)
		->from(['r'=>$this->_name],['r.*',])
		->joinInner(['m'=>'main_menu'], "m.id = r.menuId and m.isactive = 1",['menuName'=>'m.menuName','menuUrl'=>'if(m.url="/addperk","/perk",if(m.url="/adddeductive","deductive",m.url))'])
		->joinInner(['u'=>'main_users'], "u.id = r.last_modifiedby and u.isactive = 1",['userfullname'=>'u.username','employeeId'=>'u.empid'])
		->where($where)
		->order("$by $sort")
		->limitPage($pageNo, $perPage);
		//die($activitylogData);

		}
		
		
		
		return $activitylogData;
	}

}