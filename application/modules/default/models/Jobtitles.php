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

class Default_Model_Jobtitles extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_jobtitle';
    protected $_primary = 'jobtitle_id';
	
	public function getJobtitlesData($sort, $by, $pageNo, $perPage,$searchQuery)
	{
		$where = "j.isactive ='Y'";
		
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		
		$JobtitlesData = $this->select()
    					   ->setIntegrityCheck(false)	 
						   ->from(['j' => 'main_jobtitle'],['jobtitle_id'=>'j.jobtitle_id','code'=>'j.code','name'=>'j.name','isactive'=>'if(j.isactive="Y","Yes","No")','description'=>'j.description'])
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);
		return $JobtitlesData;       		
	}
	public function getsingleJobTitleData($id)
	{
		
		$db = Zend_Db_Table::getDefaultAdapter();
		$jobTitleData = $db->query("SELECT * FROM main_jobtitle WHERE isactive='Y' and jobtitle_id = ".$id);
		$res = $jobTitleData->fetchAll();
		if (isset($res) && !empty($res)) 
		{	
			return $res;
		}
		else
			return 'norows';
	}
	
	public function SaveorUpdateJobTitleData($data, $where)
	{
		try {

	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			//die(Zend_Debug::dump($data));
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_jobtitle');
			return $id;
		}
				//code...
			} catch (\Throwable $th) {
				Zend_Debug::dump($th->getMessage());
			}
		
	
	}
	
	public function getJobTitleList()
	{
		
		$where = "j.isactive ='Y'";
	
	$JobtitlesData = $this->select()
				   ->setIntegrityCheck(false)	 
				   ->from(['j' => 'main_jobtitle'],['j.*'])
				   ->where($where)
				   ->order('j.name');
	
	return $this->fetchAll($JobtitlesData)->toArray();
	
	}
	
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$exParam1='',$exParam2='',$exParam3='',$exParam4='')
	{		
        $searchQuery = '';$tablecontent = '';  $searchArray = [];$data = []; $dataTmp = [];
		if($searchData != '' && $searchData!='undefined')
		{
			$searchValues = json_decode($searchData);
			foreach($searchValues as $key => $val)
			{
				$searchQuery .= " ".$key." like '%".$val."%' AND ";
				$searchArray[$key] = $val;
			}
			$searchQuery = rtrim($searchQuery," AND");					
		}
		/** search from grid - END **/
		$objName = 'jobtitles';
		
		$tableFields = ['action'=>'Action','code' => 'Job Title Code','name' =>'Job Title','description' => 'Job Description','isactive'=>'Active'];
		
		$tablecontent = $this->getJobtitlesData($sort, $by,$pageNo,$perPage,$searchQuery);     
		
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
		],
		];		
			
		return $dataTmp;
	}

	
        
}