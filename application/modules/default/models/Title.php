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

class Default_Model_Title extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_title';
    protected $_primary = 'id';
	
	public function getTitleData($sort, $by, $pageNo, $perPage,$searchQuery)
	{
		$where = "1";
		
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		
		
		$titleData = $this->select()
    					   ->setIntegrityCheck(false)	
						   ->from('main_title',['id','name','code','description','IF(isprefix="Y","Yes","No") as isprefix','IF(issuffix="Y","Yes","No") as issuffix,IF(isactive="Y","Yes","No") as isactive'])    					
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);
						
		//die($titleData);
		return $titleData;       		
	}
	
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$a='',$b='',$c='',$d='')
	{		
        $searchQuery = '';
        $searchArray = [];
        $data = [];
		
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
		$objName = 'title';
		
        $tableFields = ['action'=>'Action','code' => 'Title Code','name' => 'Title Name','description' => 'Description','isprefix' => 'Prefix','issuffix' => 'Suffix','isactive' => 'Active'];		
        $tablecontent = $this->getTitleData($sort, $by, $pageNo, $perPage,$searchQuery);     
		
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
			'search_filters'=>[
				'isprefix'=>[
					'type'=>'select',
					'filter_data'=>[''=>'All','Y'=>'Yes','N'=>'No']
				],
				'issuffix'=>[
					'type'=>'select',
					'filter_data'=>[''=>'All','Y'=>'Yes','N'=>'No']
				],
				'isactive'=>[
					'type'=>'select',
					'filter_data'=>[''=>'All','Y'=>'Yes','N'=>'No']
				]
			]
		];	
		return $dataTmp;
	}
	
	// public function getsingletitleData($id)
	// {
		
	// 	$db = Zend_Db_Table::getDefaultAdapter();
	// 	$prefixData = $db->query("SELECT * FROM main_prefix WHERE id = ".$id." AND isactive=1");
	// 	$res = $prefixData->fetchAll();
	// 	if (isset($res) && !empty($res)) 
	// 	{	
	// 		return $res;
	// 	}
	// 	else
	// 		return 'norows';
	// }
	
	public function getsingletitleData($id)
	{
	    $select = $this->select()
						->setIntegrityCheck(false)
						->from('main_title',['id','name','code','description','IF(isprefix="Y","1","0") as isprefix','IF(issuffix="Y","1","0") as issuffix,IF(isactive="Y","1","0") as isactive'])    					
					    ->where('id='.$id);
		return $this->fetchAll($select)->toArray();
	
	}
	public function getsingletitleDataview($id)
	{
	    $select = $this->select()
						->setIntegrityCheck(false)
						->from('main_title',['id','name','code','description','IF(isprefix="Y","Yes","No") as isprefix','IF(issuffix="Y","Yes","No") as issuffix,IF(isactive="Y","Yes","No") as isactive'])    					
					    ->where('id='.$id);
		return $this->fetchAll($select)->toArray();
	
	}
	// public function getsingletitleData($id)
	// {
	// 	$db = Zend_Db_Table::getDefaultAdapter();
	// 	$titleData = $db->query("SELECT * FROM main_title WHERE id = ".$id." AND isactive='Y'");
	// 	$res = $titleData->fetchAll();
	// 	if (isset($res) && !empty($res)) 
	// 	{	
	// 		return $res;
	// 	}
	// 	else
	// 		return 'norows';
	// }
	
	public function SaveorUpdatetitleData($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			//die(json_encode($data));
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_title');
			return $id;
		}
		
	
	}
    public function gettitleList()
	{
	    $select = $this->select()
						->setIntegrityCheck(false)
						->from(['t'=>'main_title'],['id'=>'t.id','title'=>'t.name'])
					    ->where("t.isactive = 'Y'")
						 ->order('t.name');
		return $this->fetchAll($select)->toArray();
	
	}
}