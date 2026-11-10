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

class Default_Model_Branch extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_emp_branch';
    protected $_primary = 'emp_branch_id';
	
	public function getlocationData($sort, $by, $pageNo, $perPage,$searchQuery)
	{
		$where = "1";
		
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		$config = [
			'name'=>'main_emp_branch',
			'primary'=>'emp_branch_id'
		];
		$table = new Zend_Db_Table($config);
		$locationData = $table->select()
    					   ->setIntegrityCheck(false)
                           ->from(['l'=>'main_emp_branch'],['emp_branch_id'=>'l.emp_branch_id','branch'=>'l.branch','description'=>'l.description','isactive'=>'if(l.isactive="N","No","Yes")'])
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);
					  
		return $locationData;       		
	}
	
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$a='',$b='',$c='',$d='')
	{		
        $searchQuery = '';
        $searchArray = [];
        $data = [];
	
		if($searchData != '' && $searchData!='undefined')
			{
				$searchValues = json_decode($searchData);
				foreach ($searchValues as $key => $val) {
					if ($key == 'branch')
					$searchQuery .= " l." . $key . " like '%" . $val . "%' AND ";
					else if ($key == 'description')
						$searchQuery .= "l." . $key . " like '%" . $val . "%' AND ";
					else
						$searchQuery .= "l." . $key . " like '%" . $val . "%' AND ";
					$searchArray[$key] = $val;
				}
				$searchQuery = rtrim($searchQuery," AND");					
			}
		$objName = 'branch';
	
		$tableFields = ['action'=>'Action','branch' => 'branch','description' => 'description','isactive' => 'Active'];
		$tablecontent = $this->getlocationData($sort, $by, $pageNo, $perPage,$searchQuery);     
		
		$dataTmp = [
			'sort' => $sort,
			'by' => $by,
			'pageNo' => $pageNo,
			'perPage' => $perPage,				
			'tablecontent' => $tablecontent,
			'objectname' => $objName,
			'extra' => [],
			'tableheader' => $tableFields,
			'jsGridFnName' => 'getAjaxgridData',
			'jsFillFnName' => '',
			'searchArray' => $searchArray,
			'call'=>$call,
			'add'=>'add',

			'dashboardcall'=>$dashboardcall,
			'search_filters' => [
                'isactive' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
    ]
		];	
		return $dataTmp;
	}
	public function getsinglebranchData($id)
	{
		$select = $this->select()
						->setIntegrityCheck(false)
						->from(['b' => 'main_emp_branch'], ['emp_branch_id' => 'b.emp_branch_id', 'branch' => 'b.branch','description'=>'b.description','isactive'=>'if(b.isactive="N",0,1)','isactivename'=>'if(b.isactive="N","No","Yes")'])
						->where('b.emp_branch_id='.$id.' ');
		return $this->fetchAll($select)->toArray();
	}
	// public function getsinglebranchData($id)
	// {
	// 	$db = Zend_Db_Table::getDefaultAdapter();
	// 	$locationData = $db->query("SELECT * FROM main_emp_branch WHERE emp_branch_id = ".$id." ");
	// 	$res = $locationData->fetchAll();
	// 	if (isset($res) && !empty($res)) 
	// 	{	
	// 		return $res;
	// 	}
	// 	else
	// 		return 'norows';
	// }
	
	public function getbranchList()
	{
	    $select = $this->select()
						->setIntegrityCheck(false)
						->from(['l'=>'main_emp_branch'],['emp_branch_id'=>'l.emp_branch_id','branch'=>'l.branch'])
					    ->where('l.isactive = "Y"')
						 ->order('l.branch');
		return $this->fetchAll($select)->toArray();
	
	}
	
	public function SaveorUpdatebranchData($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_emp_branch');
			return $id;
		}
		
	
	}
}