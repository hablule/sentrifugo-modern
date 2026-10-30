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

class Assets_Model_Assetdetails extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_assetassignment';
	protected $_primary = 'assetassignment_id';

	public function getEmpSkillsData($sort, $by, $pageNo, $perPage,$searchQuery,$id)
	{
		$where = " ea.emp_id = ".$id." AND ea.isactive = 'Y' ";

		if($searchQuery)
		$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();

		$empskillsData = $this->select()
		->setIntegrityCheck(false)
		->from(array('ea' => 'main_assetassignment'),array('id'=>'ea.assetassignment_id','ea.*','returndate'=>'DATE_FORMAT(ea.returndate,"'.DATEFORMAT_MYSQL.'")','issueddate'=>'DATE_FORMAT(ea.issueddate,"'.DATEFORMAT_MYSQL.'")','isactive'=>'if(ea.isactive="Y","Yes","No")'))
		->joinLeft(array('a'=>'main_asset'),'ea.asset_id=a.asset_id',array('asset_name'=>'a.name'))
		->joinLeft(array('ai'=>'main_assetinstant'),'ea.assetinstant_id=ai.assetinstant_id',array('serial'=>'ai.serialidentification'))
		->joinLeft(array('u'=>'main_uom'),'ea.uom_id=u.uom_id',array('uom_name'=>'u.name'))
		->where($where)
		->order("$by $sort")
		->limitPage($pageNo, $perPage);
		return $empskillsData;
	}
	/*	Purpose:	TO get drop down for search filters.Getting all competency levels from main_competencylevel table.
		Modified Date	:	21/10/2013.
		Modified By:	Yamini.
		*/
	
	public function assetdetailview($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(array('ea' => 'main_assetassignment'),array('id'=>'ea.assetassignment_id','ea.*','returndate'=>'DATE_FORMAT(ea.returndate,"'.DATEFORMAT_MYSQL.'")','issueddate'=>'DATE_FORMAT(ea.issueddate,"'.DATEFORMAT_MYSQL.'")','isactive'=>'if(ea.isactive="Y","Yes","No")'))
		->joinLeft(array('a'=>'main_asset'),'ea.asset_id=a.asset_id',array('asset_name'=>'a.name'))
		->joinLeft(array('ai'=>'main_assetinstant'),'ea.assetinstant_id=ai.assetinstant_id',array('serial'=>'ai.serialidentification'))
		->joinLeft(array('u'=>'main_uom'),'ea.uom_id=u.uom_id',array('uom_name'=>'u.name'))
		->where($id)
		->where('ea.assetassignment_id='.$id.' ');
		return $this->fetchAll($select)->toArray();
	}
	public function getsingleassetData($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(array('ea'=>'main_assetassignment'),array('ea.*'))
		->where('ea.assetassignment_id='.$id.'');
		return $this->fetchAll($select)->toArray();
	}
	public function getsingleassetDatabyasset($id)
	{
		
		 $select = $this->select()
		 ->setIntegrityCheck(false)
		 ->from(array('ea'=>'main_assetassignment'),array('returndate'=>'CASE WHEN MAX(CASE WHEN returndate IS NULL THEN 1 ELSE 0 END) = 0 THEN MAX(returndate) END'))
		 ->where('ea.asset_id='.$id.' and ea.isactive="Y"')
		 ->group('ea.asset_id');
		return $this->fetchAll($select)->toArray();
	}
	public function getsingleassetajaxData($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(array('ea' => 'main_assetassignment'),array('id'=>'ea.assetassignment_id','ea.*','returndate'=>'DATE_FORMAT(ea.returndate,"'.DATEFORMAT_MYSQL.'")','issueddate'=>'DATE_FORMAT(ea.issueddate,"'.DATEFORMAT_MYSQL.'")','isactive'=>'if(ea.isactive="Y","Yes","No")'))
		->joinLeft(array('a'=>'main_asset'),'ea.asset_id=a.asset_id',array('asset_name'=>'a.name'))
		->joinLeft(array('ai'=>'main_assetinstant'),'ea.assetinstant_id=ai.assetinstant_id',array('assetinstant_id'=>'ai.assetinstant_id','serial'=>'ai.serialidentification'))
		->joinLeft(array('u'=>'main_uom'),'ea.uom_id=u.uom_id',array('uom_name'=>'u.name'))
		->where($id)
		->where('ea.assetassignment_id='.$id.' ');
		return $this->fetchAll($select)->toArray();

	}

	public function SaveorUpdate($data, $where)
	{
		if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {

			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_assetassignment');
			return $id;
		}

	}
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$a='',$b='',$c='',$exPadam4='')
	{
		$searchQuery = '';$tablecontent = '';
		$searchArray = array();$data = array(); $dataTmp = array();
		/** search from grid - START **/
		if($searchData != '' && $searchData!='undefined')
		{
			$searchValues = json_decode($searchData);
			foreach($searchValues as $key => $val)
			{
				if($key == 'issueddate')
				{
					$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
				}
                if($key == 'returndate')
				{
					$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
				}
				else if($key == 'asset_name')
				{
					$searchQuery .= " a.name like '%".$val."%' AND";
				}
				else if($key == 'serial')
				{
					$searchQuery .= " ai.serialidentification like '%".$val."%'  AND";
				}
				else if($key == 'uom_name')
				{
					$searchQuery .= " u.name like '%".$val."%' AND";
				}
				
				
				
				else
				$searchQuery .= " ".$key." like '%".$val."%' AND ";

				$searchArray[$key] = $val;
			}
			$searchQuery = rtrim($searchQuery," AND");
		}
		/** search from grid - END **/
		/*	Purpose:TO get drop down for search filters.Getting all competency levels from main_competencylevel table.
			Modified Date	:	21/10/2013.
			Modified By:	Yamini.
			*/
		// $empcompetencyLevelsArr = $this->empcompetencylevels($exParam1);
		// $levelsArr = array();
		// if(!empty($empcompetencyLevelsArr)){
		// 	for($i=0;$i<sizeof($empcompetencyLevelsArr);$i++)
		// 	{
		// 		$levelsArr[$empcompetencyLevelsArr[$i]['id']] = $empcompetencyLevelsArr[$i]['competencylevel'];
		// 	}
		// }
		$objName = 'assetdetails';

		$tableFields = array('action'=>'Action',
        'asset_name'=>'Asset',
		'serial'=>'Asset Instance',
		'uom_name'=>'UOM',
		'quantity'=>'Quantity',
		'issueddate'=>'Issued Date',
        'returndate'=>'Return Date',
		'isactive'=>'Active',
        'description'=>'Description',

	);
			
		$tablecontent = $this->getEmpSkillsData($sort,$by,$pageNo,$perPage,$searchQuery,$a);
		$dataTmp = array( 'sort' => $sort,
		'by' => $by,
		'pageNo' => $pageNo,
		'perPage' => $perPage,
		'tablecontent' => $tablecontent,
		'objectname' => $objName,
		'extra' => array(),
		'add' => 'add',
		'tableheader' => $tableFields,
		'jsGridFnName' => 'getEmployeeAjaxgridData',
		'jsFillFnName' => '',
		'userAction'=>$c,
		'unitId' => $a,
		'menuName' => 'Asset',

		'context'=>$b,
		'formgrid'=>'true',
		'searchArray' => $searchArray,
		'call' => $call,
		'dashboardcall' => $dashboardcall,
						'search_filters' => array(
							'isactive' => array(
								'type' => 'select',
								'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No'),
							),	
						'issueddate'=>array('type'=>'datepicker'),
                        'returndate'=>array('type'=>'datepicker')

		)
		);
		return $dataTmp;
	}


}
?>