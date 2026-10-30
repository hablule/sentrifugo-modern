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

class Default_Model_Empadditionaldetails extends Zend_Db_Table_Abstract
{	
    protected $_name = 'main_empadditionaldetails';
    protected $_primary = 'id';
	
	public function getEmpAdditionalsData($sort, $by, $pageNo, $perPage,$searchQuery,$id)
	{
		$where = " e.emp_id = ".$id." AND e.isactive = 1 ";

		if($searchQuery)
		$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();

		$empadditionaldetailsData = $this->select()
		->setIntegrityCheck(false)
		->from(['e' => 'main_empadditionaldetails'],['e.*',
		                                               'from_date'=>'DATE_FORMAT(e.from_date,"'.DATEFORMAT_MYSQL.'")',
													   'to_date'=>'DATE_FORMAT(e.to_date,"'.DATEFORMAT_MYSQL.'")'])
		->joinLeft(['ms'=>'main_militaryservicebranch'],'e.branch_service=ms.servicebranch_id',['ms.name as branchname'])
		->joinLeft(['v'=>'main_veteranstatus'],'e.veteranstatus=v.id AND v.isactive = 1',['veteran_status'=>'v.veteranstatus'])
		->joinLeft(['m'=>'main_militaryservice'],'e.military_servicetype=m.id AND m.isactive = 1',['military_servicetype'=>'m.militaryservicetype'])
		->where($where)
		->order("$by $sort")
		->limitPage($pageNo, $perPage);
		
		return $empadditionaldetailsData;
	}
	
	public function getsingleEmpAdditionalDetailsData($id)
	{
		$select = $this->select()
						->setIntegrityCheck(false)
						->from(['ea'=>'main_empadditionaldetails'],['ea.*'])
						->where('ea.user_id='.$id.' AND ea.isactive = 1');
					
		return $this->fetchAll($select)->toArray();
	}
	
	public function getEmpAdditionalDetailsData($id)
	{
		try {
			//code...
		
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['es'=>'main_empadditionaldetails'],['es.*'])
		->joinLeft(['msb'=>'main_militaryservicebranch'],'msb.servicebranch_id = es.branch_service',['msb.name as branchname'])
		->joinLeft(['mr'=>'main_militaryrank'],'mr.militaryrank_id=es.rank_achieved',['mr.name as rankname'])
		->where('es.id='.$id.' AND es.isactive = 1');
			
		return $this->fetchAll($select)->toArray();
	} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
	}
	}
	
	public function SaveorUpdateEmpAdditionalData($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_empadditionaldetails');
			return $id;
		}
		
	}
	
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$exParam1='',$exParam2='',$exParam3='',$exParam4='')
	{
		$searchQuery = '';
		$tablecontent = '';
		$searchArray = [];
		$data = []; 
		$dataTmp = [];
		/** search from grid - START **/
		if($searchData != '' && $searchData!='undefined')
		{
			$searchValues = json_decode($searchData);
			foreach($searchValues as $key => $val)
			{
				if($key == 'from_date')
				{
					$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
				}
				else if($key == 'to_date')
				{
					$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
				}
				else
				$searchQuery .= " ".$key." like '%".$val."%' AND ";

				$searchArray[$key] = $val;
			}
			$searchQuery = rtrim($searchQuery," AND");
		}
		
		$objName = 'empadditionaldetails';

		$tableFields = ['action'=>'Action','branchname'=>'Branch of Service','from_date'=>'From','to_date'=>'To','veteranstatus'=>'Veteran Status','military_servicetype'=>'Service Type'];
			
		$tablecontent = $this->getEmpAdditionalsData($sort,$by,$pageNo,$perPage,$searchQuery,$exParam1);

		$bool_arr = ['' => 'All',1 => 'Yes',2 => 'No'];
		
		$empVeteranStatusArr = $this->empveteranstatus($exParam1);
		$veteranstatusArr = [];
		if(!empty($empVeteranStatusArr)){
			for($i=0;$i<sizeof($empVeteranStatusArr);$i++)
			{
				$veteranstatusArr[$empVeteranStatusArr[$i]['id']] = $empVeteranStatusArr[$i]['veteranstatus'];
			}
		}
		
		$empMiltitaryStatusArr = $this->empmilitarystatus($exParam1);
		$militarystatusArr = [];
		if(!empty($empMiltitaryStatusArr)){
			for($i=0;$i<sizeof($empMiltitaryStatusArr);$i++)
			{
				$militarystatusArr[$empMiltitaryStatusArr[$i]['id']] = $empMiltitaryStatusArr[$i]['militaryservicetype'];
			}
		}
		
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
						'add'=>'add',
						'menuName'=>'Military service',
						'formgrid'=>'true',
						'unitId'=>$exParam1,
						'userAction' => $exParam3,	

						'dashboardcall'=>$dashboardcall,
						'call'=>$call,
						'context'=>$exParam2,
						'search_filters' => [
						'military_status' => [
                                'type' => 'select',
                                'filter_data' => $bool_arr,
                            ],
						'military_servicetype' => ['type'=>'select',
													'filter_data'=>[''=>'All']+$militarystatusArr],
						'veteran_status' => ['type'=>'select',
													'filter_data'=>[''=>'All']+$veteranstatusArr],							
						'from_date'=>['type'=>'datepicker','yearrange'=>'yearrange'],
						'to_date'=>['type'=>'datepicker','yearrange'=>'yearrange']
		]
		];
		return $dataTmp;
	}
	
	public function empveteranstatus($userId)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$Data = $db->query("select v.id,v.veteranstatus
								from main_veteranstatus as v
								where v.isactive=1 ");	
		$data = $Data->fetchAll();
		
		return $data;
	}
	
	public function empmilitarystatus($userId)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$Data = $db->query("select m.id,m.militaryservicetype
								from main_militaryservice as m
								where m.isactive=1 ");	
		$data = $Data->fetchAll();
		
		return $data;
	}
	
}