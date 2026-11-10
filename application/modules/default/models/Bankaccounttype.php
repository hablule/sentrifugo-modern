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

class Default_Model_Bankaccounttype extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_bankaccounttype';
    protected $_primary = 'id';
	
	public function getBankaccounttypeData($sort, $by, $pageNo, $perPage,$searchQuery)
	{
		$where ='1';
		
		if($searchQuery){
			$where .= " AND ".$searchQuery;
			
		}
			
		$db = Zend_Db_Table::getDefaultAdapter();		
		
		$bankaccounttypeData = $this->select()
    					   ->setIntegrityCheck(false)	    					
						   ->from($this->_name,['id','bankaccounttype','description','if(isactive="Y","Yes","No") as isactive'])
    					   ->order("$by $sort")
						   ->where($where) 
    					   ->limitPage($pageNo, $perPage);
		
		return $bankaccounttypeData;       		
	}
	public function getsingleBankAccountData($id)
	{
		
		$db = Zend_Db_Table::getDefaultAdapter();
		$bankAccntData = $db->query("SELECT * FROM main_bankaccounttype WHERE id = ".$id);
		$res = $bankAccntData->fetchAll();
		if (isset($res) && !empty($res)) 
		{	
			return $res;
		}
		else
			return 'norows';
	}
	
	public function getBankAccountList()
	{
	  $accountclassTypeData = $this->select()
    					   ->setIntegrityCheck(false)	
						   ->from(['b'=>'main_bankaccounttype'],['b.id','b.bankaccounttype'])
                           ->where('b.isactive = 1')
						   ->order('b.bankaccounttype');
      return $this->fetchAll($accountclassTypeData)->toArray();
	}
	
	public function SaveorUpdateBankAccountData($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_bankaccounttype');
			return $id;
		}
	}
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$exParam1='',$exParam2='',$exParam3='',$exParam4='')
	{		
        $searchQuery = '';$tablecontent = '';  $searchArray = [];$data = [];$id='';
        $dataTmp = [];
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
		$objName = 'bankaccounttype';
		
		$tableFields = ['action'=>'Action','bankaccounttype' => 'Bank Account Type','description' => 'Description','isactive'=>'Active'];
		
		$bool_arr = [''=>'All','Y'=>'Yes','N'=>'No'];	
		$tablecontent = $this->getBankaccounttypeData($sort, $by, $pageNo, $perPage,$searchQuery);     
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
			'call'=>$call,'dashboardcall'=>$dashboardcall,
			'search_filters' => [
				'isactive' => [
					'type' => 'select',
					'filter_data' => $bool_arr,
				]
                ]
		];		
			
		return $dataTmp;
	}
}