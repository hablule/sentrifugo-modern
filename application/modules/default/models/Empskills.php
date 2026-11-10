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

class Default_Model_Empskills extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_empskill';
	protected $_primary = 'empskill_id';

	public function getEmpSkillsData($sort, $by, $pageNo, $perPage,$searchQuery,$id)
	{
		$where = " es.emp_id = ".$id." AND es.isactive = 'Y' ";

		if($searchQuery)
		$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();

		$empskillsData = $this->select()
		->setIntegrityCheck(false)
		->from(['es' => 'main_empskill'],['id'=>'es.empskill_id','es.*','acquiredsince'=>'DATE_FORMAT(es.acquiredsince,"'.DATEFORMAT_MYSQL.'")','isactive'=>'if(es.isactive="Y","Yes","No")'])
		->joinLeft(['s'=>'main_skill'],'es.skill_id=s.skill_id',['skillname'=>'COALESCE(s.name,es.skillname)'])
		->joinLeft(['sc'=>'main_skillcategory'],'s.skillcategory_id=sc.skillcategory_id',['skillcategoryname'=>'sc.name'])

		->joinLeft(['ss'=>'main_skillsource'],'es.skillsource_id=ss.skillsource_id',['skillsourcename'=>'ss.name'])
		->joinLeft(['mi'=>'main_measurementindex'],'es.measurementindex_id=mi.measurementindex_id',['measurementvalue'=>'COALESCE(mi.value,measurementvalue)'])
		->joinLeft(['m'=>'main_measurement'],'mi.measurement_id=m.measurement_id',['measurementname'=>'m.name'])

		->where($where)
		->order("$by $sort")
		->limitPage($pageNo, $perPage);
		//die($empskillsData);
		return $empskillsData;
	}
	/*	Purpose:	TO get drop down for search filters.Getting all competency levels from main_competencylevel table.
		Modified Date	:	21/10/2013.
		Modified By:	Yamini.
		*/
	public function empcompetencylevels($userId)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$Data = $db->query("select c.id,c.competencylevel
								from main_competencylevel as c
								where c.isactive=1 ");	
		$data = $Data->fetchAll();
		
		return $data;
	}
	public function empskilldetailview($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['es' => 'main_empskill'],['id'=>'es.empskill_id','es.*','acquiredsince'=>'DATE_FORMAT(es.acquiredsince,"'.DATEFORMAT_MYSQL.'")','isactive'=>'if(es.isactive="Y","Yes","No")'])
		->joinLeft(['s'=>'main_skill'],'es.skill_id=s.skill_id',['skillname'=>'COALESCE(s.name,es.skillname)'])
		->joinLeft(['sc'=>'main_skillcategory'],'s.skillcategory_id=sc.skillcategory_id',['skillcategoryname'=>'sc.name'])
		->joinLeft(['ss'=>'main_skillsource'],'es.skillsource_id=ss.skillsource_id',['skillsourcename'=>'ss.name'])
		->joinLeft(['mi'=>'main_measurementindex'],'es.measurementindex_id=mi.measurementindex_id',['measurementvalue'=>'COALESCE(mi.value,es.measurementvalue)'])
		->joinLeft(['m'=>'main_measurement'],'mi.measurement_id=m.measurement_id',['measurementname'=>'m.name'])
		->where('es.empskill_id='.$id.' AND es.isactive = "Y"');
		return $this->fetchAll($select)->toArray();
	}
	public function getsingleEmpSkillsData($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['es'=>'main_empskill'],['es.*'])
		->where('es.empskill_id='.$id.' AND es.isactive = "Y"');
			
		return $this->fetchAll($select)->toArray();
	}

	public function SaveorUpdateEmpSkills($data, $where)
	{
		if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {

			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_empskill');
			return $id;
		}

	}
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$a='',$b='',$c='',$exPadam4='')
	{
		$searchQuery = '';$tablecontent = '';
		$searchArray = [];$data = []; $dataTmp = [];
		/** search from grid - START **/
		if($searchData != '' && $searchData!='undefined')
		{
			$searchValues = json_decode($searchData);
			foreach($searchValues as $key => $val)
			{
				if($key == 'acquiredsince')
				{
					$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
				}
				else if($key == 'skillname')
				{
					$searchQuery .= " s.name like '%".$val."%' or es.skillname like '%".$val."%' AND";
				}
				else if($key == 'measurementvalue')
				{
					$searchQuery .= " mi.value like '%".$val."%' or es.measurementvalue like '%".$val."%' AND";
				}
				else if($key == 'skillcategoryname')
				{
					$searchQuery .= " sc.name like '%".$val."%' AND";
				}
				else if($key == 'measurementname')
				{
					$searchQuery .= " m.name like '%".$val."%' AND";
				}
				else if($key == 'isactive')
				{
					$searchQuery .= " m.isactive like '%".$val."%' AND";
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
		$objName = 'empskills';

		$tableFields = ['action'=>'Action','skillname'=>'Skill',
		'skillcategoryname'=>'Skill Category',
		'skillsourcename'=>'Skill Source',
		'measurementname'=>'Measurement Name',
		'measurementvalue'=>'Measurement Value',
		'isactive'=>'Active',
		'acquiredsince'=>'Acquired Since',
		

		
	];
			
		$tablecontent = $this->getEmpSkillsData($sort,$by,$pageNo,$perPage,$searchQuery,$a);
		$dataTmp = [ 'sort' => $sort,
		'by' => $by,
		'pageNo' => $pageNo,
		'perPage' => $perPage,
		'tablecontent' => $tablecontent,
		'objectname' => $objName,
		'extra' => [],
		'add' => 'add',
		'tableheader' => $tableFields,
		'jsGridFnName' => 'getEmployeeAjaxgridData',
		'jsFillFnName' => '',
		'userAction'=>$c,
		'unitId' => $a,
		'menuName' => 'Employee Skill',

		'context'=>$b,
		'formgrid'=>'true',
		'searchArray' => $searchArray,
		'call' => $call,
		'dashboardcall' => $dashboardcall,
						'search_filters' => [
							'isactive' => [
								'type' => 'select',
								'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No'],
							],	
						'acquiredsince'=>['type'=>'datepicker']
		]
		];
		return $dataTmp;
	}


}
?>