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

class Default_Model_Empbenefits extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_employeeperk';
    protected $_primary = 'employeeperk_id';
  
    public function getData($sort, $by, $pageNo, $perPage, $searchQuery,$id)
    {
		$where = " ep.emp_id = " . $id."";
		 //Zend_Debug::dump($searchQuery);

        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(['ep' => 'main_employeeperk'], ['id'=>'ep.employeeperk_id','emp_id' => 'ep.emp_id', 'perk_id'=>'ep.perk_id','empamount'=>'ep.amount','approved'=>'if(ep.approved="N","No","Yes")','isactive'=>'if(ep.isactive="N","No","Yes")'])
            ->joinInner(['mp' => 'main_perk'], "mp.perk_id =ep.perk_id ", ['name' => 'mp.name','type' => 'mp.type','start' =>  'DATE_FORMAT(mp.start,"'.DATEFORMAT_MYSQL.'")','end' =>  'DATE_FORMAT(mp.end,"'.DATEFORMAT_MYSQL.'")','amount' => 'mp.amount'])
            ->joinInner(['e' => 'main_employees'], "e.id = ep.emp_id ", ['userfullname' => 'e.userfullname'])
            ->joinInner(['pc' => 'main_perkcategory'], "pc.perkcategory_id = mp.category ", ['c_name' => 'pc.name','c_description' => 'pc.description'])
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
                    $searchQuery .= " mp.type like '%" . $val . "%' AND ";
                else if ($key == 'name')
                    $searchQuery .= " mp.name  like '%" . $val . "%' AND ";
                    else if ($key == 'c_name')
                    $searchQuery .= " pc.name like '%" . $val . "%' AND ";
                    else if ($key == 'c_description')
                    $searchQuery .= " pc.description like '%" . $val . "%' AND ";
                    else if ($key == 'empamount')
                    $searchQuery .= " ep.amount like '%" . $val . "%' AND ";
                    else if ($key == 'isactive')
                    $searchQuery .= " ep.isactive like '%" . $val . "%' AND ";
                else if ($key == 'userfullname')
                    $searchQuery .= " e.userfullname like '%" . $val . "%' AND ";
                    else if ($key == 'start')
                    $searchQuery .= " mp.start like '%".  sapp_Global::change_date(urldecode($val),'database')."%' AND ";
                    else if ($key == 'end')
                    $searchQuery .= " mp.end like '%".  sapp_Global::change_date(urldecode($val),'database')."%' AND ";
                    elseif ($key == 'approved')
                    $searchQuery .= " ep.approved like'%".$val."%' AND ";
               
                $searchArray[$key] = $val;
				
			
			}
			$searchQuery = rtrim($searchQuery, " AND");
		}
        $objName = 'empbenefits';
        $tableFields = [
            'action' => 'Action',
            'name' => 'Perk Name',
            'isactive' => 'Active',
            'empamount' => 'Amount',
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
			'menuName' => 'Benefits',
			'formgrid' => 'true',
			'unitId' => $exParam1,
			'call' => $call,
			'userAction' => $exParam3,	

			'context' => $exParam2,	'search_filters' => [
				'iscurrent' => [
					'type' => 'select',
					'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
		],
        'search_filters' => [
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
    public function SaveorUpdatePerkData($data,$where)
    {
        $config = [
            'name' => 'main_employeeperk',
            'primary' => 'employeeperk_id'
        ];
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
