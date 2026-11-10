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

class Default_Model_Experiencedetails extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_empexperiancedetails';
    protected $_primary = 'id';
	
	public function getexperiencedetailsData($sort, $by, $pageNo, $perPage,$searchQuery,$userid)
	{	
		$where = "isactive = 'Y'  AND emp_id = ".$userid." " ;
		
		if($searchQuery != '')
			$where .= " AND ".$searchQuery." ";
		$db = Zend_Db_Table::getDefaultAdapter();		
		
		$expData = $this->select()
					->from(['ex'=>'main_empexperiancedetails'],['id'=>'id','comp_name'=>'comp_name','comp_website'=>'comp_website','designation'=>'designation','from_date'=>'DATE_FORMAT(from_date,"'.DATEFORMAT_MYSQL.'")','to_date'=>'DATE_FORMAT(to_date,"'.DATEFORMAT_MYSQL.'")','reason_for_leaving'=>'reason_for_leaving','responsibility'=>'responsibility'])
    					   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo,$perPage);
		
		return $expData;         		
	}
	public function getexperiencedetailsRecord($id=0)
	{  
		$empdependencyDetailsArr="";$where = "";
		$db = Zend_Db_Table::getDefaultAdapter();		
		if($id != 0)
		{
			$where = "id =".$id;
			$empdependencyDetails = $this->select()
									->from(['e'=>'main_empexperiancedetails'])
									->where($where);
		
			
			$empdependencyDetailsArr = $this->fetchAll($empdependencyDetails)->toArray(); 
        }
		return $empdependencyDetailsArr;       		
	}
    
	public function SaveorUpdateEmployeeexperienceData($data, $where)
	{
		
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_empexperiancedetails');
			return $id;
		}
		
	}
	public function getGrid($sort,$by,$pageNo,$perPage,$searchData,$call,$dashboardcall,$exParam1='',$exParam2='',$exParam3='',$exParam4='')
	{		
        $searchQuery = '';$tablecontent = '';
        $searchArray = [];$data = [];$id='';
        $dataTmp = [];
        
		if($searchData != '' && $searchData!='undefined')
		{
			$searchValues = json_decode($searchData);
			foreach($searchValues as $key => $val)
			{
				if($key == 'from_date' || $key == 'to_date')
				{
					$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
				}
				else
					$searchQuery .= " ".$key." like '%".$val."%' AND ";
				$searchArray[$key] = $val;
			}
			 $searchQuery = rtrim($searchQuery," AND");					
		}
		/** search from grid - END **/
		$objName = 'experiencedetails';
		$tableFields = ['action'=>'Action','comp_name'=>'Company Name','comp_website'=>'Company Website','designation'=>'Designation','from_date'=>'From','to_date'=>'To'];
					
		$tablecontent = $this->getexperiencedetailsData($sort, $by, $pageNo, $perPage,$searchQuery,$exParam1);     
		
		$dataTmp = ['userid'=>$exParam1,
						'sort' => $sort,
						'by' => $by,
						'pageNo' => $pageNo,
						'perPage' => $perPage,				
						'tablecontent' => $tablecontent,
						'objectname' => $objName,
						'extra' => [],
						'tableheader' => $tableFields,
						'jsGridFnName' => 'getEmployeeAjaxgridData',
						'jsFillFnName' => '',
						'searchArray' => $searchArray,
						'dashboardcall'=>$dashboardcall,
						'add'=>'add',
						'userAction' => $exParam3,	

						'menuName'=>'Experience',
						'formgrid' => 'true',
						'unitId'=>$exParam1,
						'call'=>$call,
						'context'=>$exParam2,
						'search_filters' => [
						'from_date' =>['type'=>'datepicker'],
						'to_date' =>['type'=>'datepicker']											
									]									
						];	
		return $dataTmp;
	}
	
	
}
?>