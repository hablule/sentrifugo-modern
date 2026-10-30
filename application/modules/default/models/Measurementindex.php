<?php
/********************************************************************************* 

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

class Default_Model_Measurementindex extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_measurementindex';
	protected $_primary = 'measurementindex_id';


	public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['mi'=>'main_measurementindex'],['id'=>'mi.measurementindex_id','isactivee'=>'if(mi.isactive="Y","Yes","No")','mi.*'])
			->joinLeft(['m'=>'main_measurement'],'mi.measurement_id=m.measurement_id',['name'=>'m.name','valuetype'=>'m.valuetype'])
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
       
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = [];
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'measurementindex';
        $tableFields = [
            'action' => 'Action',
            'name' => 'Measurement',
            'valuetype' => 'Value Type',
			'value' => 'value',
            'description' => 'Description',
            'isactivee' => 'Active',
        ];

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery);
        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'extra' => [],
            'add'=>'add',
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'isactivee' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
					'valuetype' => [
						'type' => 'select',
						'filter_data' => [''=>'All','SPECIFIC'=>'SPECIFIC','LIST'=>'LIST']],
            ]
        ];
        return $dataTmp;
    }
    public function getskillsourcelist()
	{
	     $select = $this->select()
						->setIntegrityCheck(false)
						->from(['sc'=>'main_measurement'],['measurement_id'=>'sc.measurement_id','name'=>'sc.name'])
						->where('sc.isactive="Y" ');
						
		
		return $this->fetchAll($select)->toArray();
	
	}
	
	public function SaveorUpdateData($data, $where='')
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId($this->_name);
			return $id;
		}
		
	}

	public function getmeasurementlist()
	{
	     $select = $this->select()
						->setIntegrityCheck(false)
						->from(['m'=>'main_measurementindex'],['measurementindex_id'=>'m.measurementindex_id','name'=>'m.value'])
						->where('m.isactive="Y" ');
						
		
		return $this->fetchAll($select)->toArray();
	
	}
    public function getmeasurement($id)
    {
        $linecate = $this->select()
                         ->setIntegrityCheck(false)
                         ->where('measurement_id='.$id);
        return $this->fetchRow($linecate)->toArray();
    }

    public function getallsource()
    {
        $linec = $this->select()
                      ->setIntegrityCheck(false)
                      ->where('isactive="Y"');
        return $this->fetchAll($linec)->toArray();
    }
}
?>