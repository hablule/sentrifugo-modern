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

class Default_Model_Empdeductive extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_employeeload';
    protected $_primary = 'employeeload_id';
    public function getData($sort, $by, $pageNo, $perPage, $searchQuery,$id)
    {
		$where = " el.emp_id = " . $id;

        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(['el' => 'main_employeeload'], ['id'=>'el.employeeload_id','emp_id' => 'el.emp_id','empamount' => 'el.amount', 'load_id'=>'el.load_id','approved'=>'if(el.approved="N","No","Yes")','isactive'=>'if(el.isactive="Y","Yes","No")'])
            ->joinInner(['ml' => 'main_load'], "ml.load_id =el.load_id ", ['name' => 'ml.name','type' => 'ml.type','start' =>  'DATE_FORMAT(ml.start,"'.DATEFORMAT_MYSQL.'")','end' => 'DATE_FORMAT(ml.end,"'.DATEFORMAT_MYSQL.'")','amount' => 'ml.amount'])
            ->joinLeft(['e' => 'main_employees'], "e.id = el.emp_id ", ['userfullname' => 'e.userfullname'])
            ->joinInner(['lc' => 'main_loadcategory'], "lc.load_category_id = ml.load_category_id ", ['c_name' => 'lc.name','c_description' => 'lc.description'])
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1 = '', $exParam2 = '', $exParam3 = '', $exParam4 = '')
    {
		$searchQuery = '';
		$searchArray = [];
		$data = [];

		if ($searchData != '' && $searchData != 'undefined') {
			$searchValues = json_decode($searchData);

			foreach ($searchValues as $key => $val) {
                if ($key == 'type')
                $searchQuery .= " ml.type like '%" . $val . "%' AND ";
            else if ($key == 'name')
                $searchQuery .= " ml.name  like '%" . $val . "%' AND ";
                else if ($key == 'c_name')
                $searchQuery .= " lc.name like '%" . $val . "%' AND ";
                else if ($key == 'c_description')
                $searchQuery .= " lc.description like '%" . $val . "%' AND ";
                else if ($key == 'empamount')
                $searchQuery .= " el.amount like '%" . $val . "%' AND ";
                else if ($key == 'isactive')
                $searchQuery .= " el.isactive like '%" . $val . "%' AND ";
            else if ($key == 'userfullname')
                $searchQuery .= " e.userfullname like '%" . $val . "%' AND ";
                else if ($key == 'start')
                $searchQuery .= " ml.start like '%".  sapp_Global::change_date(urldecode($val),'database')."%' AND ";
                else if ($key == 'end')
                $searchQuery .= " ml.end like '%".  sapp_Global::change_date(urldecode($val),'database')."%' AND ";
                elseif ($key == 'approved')
                $searchQuery .= " el.approved like'%".$val."%' AND ";
           
            $searchArray[$key] = $val;
				
			
			}
			$searchQuery = rtrim($searchQuery, " AND");
		}
        $objName = 'empdeductive';
        $tableFields = [
            'action' => 'Action',
            'name' => 'Load Name',
            'empamount' => 'Amount',
            'isactive' => 'Active'

        ];

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery, $exParam1);

        $dataTmp = [
            'userid' => $exParam1,
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
			'add' => 'add',
			'menuName' => 'Deductive',
			'formgrid' => 'true',
			'unitId' => $exParam1,
			'call' => $call,
			'userAction' => $exParam3,	
			'context' => $exParam2,	  'search_filters' => [
                'start' =>['type'=>'datepicker'],
                'end' =>['type'=>'datepicker'],
                'isactive' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
                    'approved' => [
                        'type' => 'select',
                        'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
            ]

        ];
        return $dataTmp;
    }
    public function SaveorUpdateLoadkData($data,$where)
    {
        $config = [
            'name' => 'main_employeeload',
            'primary' => 'employeeload_id'
        ];
        Zend_Debug::dump($where);
        $table = new Zend_Db_Table($config);
        if($where != ''){
			$table->update($data, $where);
			return 'update';
		} else {
			$table->insert($data);
			$id=$table->getAdapter()->lastInsertId('main_address');
			return $id;
		}  
     }

    public function getPerkType($id){
        $query = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(['p'=>'main_perk'],['p.*'])
                        ->where('p.perk_id='.$id);
        return $this->fetchAll($query)->toArray();
    }

    public function deleteEmpPerk($emp_id){
        $config = [
            'name'=>'main_employeeperk',
            'primary'=>'employeeperk_id'
        ];
        $this->table = new Zend_Db_Table($config) ;
        $this->table->delete('emp_id='.$emp_id);
    }
}
