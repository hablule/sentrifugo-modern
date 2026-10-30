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

class Default_Model_Disabilitydetails extends Zend_Db_Table_Abstract
{	
    protected $_name = 'main_empdisability';
    protected $_primary = 'empdisability_id';
	
       
    public function getempDisabilitydetails($sort, $by, $pageNo, $perPage,$searchQuery,$id)
	{  
		$where = " ed.emp_id = ".$id;

		if($searchQuery)
		$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();

		$empskillsData = $this->select()
		->setIntegrityCheck(false)
		->from(array('ed' => 'main_empdisability'),array('id'=>'ed.empdisability_id','ed.*','onsetdate'=>'DATE_FORMAT(ed.onsetdate,"'.DATEFORMAT_MYSQL.'")','isactive'=>'if(ed.isactive="Y","Yes","No")'))
		->joinLeft(array('md'=>'main_disability'),'ed.disability_id=md.disability_id',array('disabilityname'=>'md.name'))
		->joinLeft(array('mdc'=>'main_disabilitycategory'),'md.disabilitycategory_id=mdc.disabilitycategory_id',array('categoryname'=>'mdc.name'))
		->joinLeft(array('mdt'=>'main_disabilitytype'),'md.disabilitytype_id=mdt.disabilitytype_id',array('typename'=>'mdt.name'))
		->where($where)
		->order("$by $sort")
		->limitPage($pageNo, $perPage);
		return $empskillsData;		
	}
    
    public function SaveorUpdateEmpdisabilityDetails($data, $where)
    {
	    if($where != '')
        {
            $this->update($data, $where);
			return 'update';
        }
        else
        {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_empdisability');
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
				if($key == 'onsetdate')
				{
					$searchQuery .= "ed.onsetdate like '%".  sapp_Global::change_date($val,'database')."%' AND ";
				}
				else if($key == 'disabilityname')
				{
					$searchQuery .= " md.name like '%".$val."%' or es.skillname like '%".$val."%' AND";
				}
				else if($key == 'categoryname')
				{
					$searchQuery .= " mdc.name like '%".$val."%' or es.measurementvalue like '%".$val."%' AND";
				}
				else if($key == 'typename')
				{
					$searchQuery .= " mdt.name like '%".$val."%' AND";
				}
			
				else if($key == 'isactive')
				{
					$searchQuery .= " ed.isactive like '%".$val."%' AND";
				}
				
				else
				$searchQuery .= "ed. ".$key." like '%".$val."%' AND ";

				$searchArray[$key] = $val;
			}
			$searchQuery = rtrim($searchQuery," AND");
		}

		$objName = 'disabilitydetails';

		$tableFields = array('action'=>'Action',
		
		'disabilityname'=>'Disability',
		'categoryname'=>'Category',
		'typename'=>'Type',
		'isactive'=>'Active',
		'onsetdate'=>'Onset Date',
		

		
	);
			
		$tablecontent = $this->getempDisabilitydetails($sort,$by,$pageNo,$perPage,$searchQuery,$a);
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
		'menuName' => 'Disability',

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
						'onsetday'=>array('type'=>'datepicker')
		)
		);
		return $dataTmp;
	}
	public function getdisabilitydetails($id)
    {
        $linecate = $this->select()
                         ->setIntegrityCheck(false)
                         ->where('empdisability_id='.$id);
                         return $this->fetchAll($linecate)->toArray();
                        }

	public function getdisabilitydetailsview($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(array('ed' => 'main_empdisability'),array('id'=>'ed.empdisability_id','ed.*','onsetdate'=>'DATE_FORMAT(ed.onsetdate,"'.DATEFORMAT_MYSQL.'")','isactive'=>'if(ed.isactive="Y","Yes","No")'))
		->joinLeft(array('md'=>'main_disability'),'ed.disability_id=md.disability_id',array('disabilityname'=>'md.name'))
		->joinLeft(array('mdc'=>'main_disabilitycategory'),'md.disabilitycategory_id=mdc.disabilitycategory_id',array('categoryname'=>'mdc.name'))
		->joinLeft(array('mdt'=>'main_disabilitytype'),'md.disabilitytype_id=mdt.disabilitytype_id',array('typename'=>'mdt.name'))
		->where('ed.empdisability_id='.$id);
		return $this->fetchAll($select)->toArray();
	}
}