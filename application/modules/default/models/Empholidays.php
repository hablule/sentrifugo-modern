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

class Default_Model_Empholidays extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_empholiday';
    protected $_primary = 'empholiday_id';
	
	public function getData($sort, $by, $pageNo, $perPage, $searchQuery,$id)
    {
		

		$where = " eh.emp_id = " . $id . " ";
		 //Zend_Debug::dump($searchQuery);

        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('eh' => 'main_empholiday'), array('id' => 'eh.empholiday_id', 'description' => 'eh.description','isactive'=>'if(eh.isactive="N","No","Yes")'))
        ->joinLeft(array('h' => 'main_holidaydates'), "h.holiday_id = eh.holiday_id ", array('holidayname' => 'h.name'))
        ->joinLeft(array('hc' => 'main_holidaycategory'), "hc.holidaycategory_id = eh.holidaycategory_id ", array('categoryname' => 'hc.name'))
        ->order("$by $sort")
        ->where($where)
        ->limitPage($pageNo, $perPage);
        return $bank;
				//code...
	

    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1 , $exParam2 = '', $exParam3 = '', $exParam4 = '')
    {
		$searchQuery = '';
		$searchArray = array();
		$data = array();
	

		if ($searchData != '' && $searchData != 'undefined') {
			$searchValues = json_decode($searchData);

			foreach ($searchValues as $key => $val) {
               
				if($key == 'holidayname'){
					$searchQuery.=" h.name like '%" . $val . "%' AND "; 
					$searchArray[$key] = $val;
				}else if($key == 'categoryname'){
					$searchQuery.=" hc.name like '%" . $val . "%' AND "; 
					$searchArray[$key] = $val;
				}
					else if($key == 'description'){
						$searchQuery.=" eh.description like '%" . $val . "%' AND "; 
						$searchArray[$key] = $val;
					}
					else if($key == 'isactive'){
						$searchQuery.=" eh.isactive like '%" . $val . "%' AND "; 
						$searchArray[$key] = $val;
					}
				else{
					$searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
					$searchArray[$key] = $val;
				}
			
			}
			$searchQuery = rtrim($searchQuery, " AND");
		}
        $objName = 'empholidays';
        $tableFields = array(
            'action' => 'Action',
            'holidayname' => 'Holiday Name',
            'categoryname' => 'Holiday Category',
            'description' => 'Description',
            'isactive' => 'Active ',


        );
        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery, $exParam1);
        $dataTmp = array(
            'userid' => $exParam1,
			'sort' => $sort,
			'by' => $by,
			'pageNo' => $pageNo,
			'perPage' => $perPage,
			'tablecontent' => $tablecontent,
			'objectname' => $objName,
			'extra' => array(),
			'tableheader' => $tableFields,
			'jsGridFnName' => 'getEmployeeAjaxgridData',
			'jsFillFnName' => '',
			'searchArray' => $searchArray,
			'add' => 'add',
			'menuName' => 'Employee Holidays',
			'formgrid' => 'true',
			'unitId' => $exParam1,
			'call' => $call,
			'userAction' => $exParam3,	

			'context' => $exParam2,	'search_filters' => array(
				'isactive' => array(
					'type' => 'select',
					'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
		),
        );
        return $dataTmp;
    }
	public function getEmpLeavesData($sort, $by, $pageNo, $perPage,$searchQuery,$id)
	{
		$where = " e.user_id = ".$id." AND e.isactive = 1 ";
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		
		$empskillsData = $this->select()
    					   ->setIntegrityCheck(false)	 
						   ->from(array('e' => 'main_employeeleaves'),array('id'=>'e.id','leavelimit'=>'e.emp_leave_limit','used_leaves'=>'e.used_leaves','e.alloted_year'))
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);
	
		return $empskillsData;       		
	}
	
	public function getsingleEmployeeleaveData($id)
	{
		$select = $this->select()
						->setIntegrityCheck(false)
						->from(array('el'=>'main_employeeleaves'),array('el.*'))
						->where('el.user_id='.$id.' AND el.isactive = 1 AND el.alloted_year = year(now())');
		
		return $this->fetchAll($select)->toArray();
	}
	public function getsingleholidayview($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
        ->from(array('eh' => 'main_empholiday'), array('id' => 'eh.empholiday_id', 'description' => 'eh.description','isactive'=>'if(eh.isactive="N","No","Yes")'))
        ->joinLeft(array('h' => 'main_holidaydates'), "h.holiday_id = eh.holiday_id ", array('holidayname' => 'h.name'))
        ->joinLeft(array('hc' => 'main_holidaycategory'), "hc.holidaycategory_id = eh.holidaycategory_id ", array('categoryname' => 'hc.name'))
        ->where('eh.empholiday_id='.$id);
		
		return $this->fetchAll($select)->toArray();
	}
	public function getsingleEmpleavesrow($id)
	{
		$row = $this->fetchRow("id = '".$id."'");
		if (!$row) {
			throw new Exception("Could not find row $id");
		}
		return $row->toArray();
	}
	

	public FUNCTION getsingleempholidaydatas($id)
	{
		$SELECT = $this->SELECT()
					   ->setIntegrityCheck(FALSE)
					   ->WHERE('empholiday_id='.$id.'');
		RETURN $this->fetchRow($SELECT)->toArray();
	}

	public function SaveorUpdateEmpLeaves($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);

			$id=$this->getAdapter()->lastInsertId('main_employeeleaves');
			return $id;
		}
		
	}
	public function getsingleholidaysummaryDatabyemp($id)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'empholiday_summary'),array('l.*'))
 	  				->where(" l.emp_id = ".$id);
    	return $this->fetchAll($result)->toArray();
	}
	
}
?>