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

class Default_Model_Skill extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_skill';
    protected $_primary = 'skill_id';


    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['s' => 'main_skill'], ['id' => 's.skill_id', 'name' => 's.name', 'code' => 's.code', 'isactive' => 'if(s.isactive="Y","Yes","No")', 'issummary' => 'if(s.issummary="Y","Yes","No")', 'description' => 's.description'])
            ->joinLeft(['sc' => 'main_skillcategory'], 's.skillcategory_id=sc.skillcategory_id', ['skillcategoryname' => 'sc.name'])
            ->joinLeft(['m' => 'main_measurement'], 's.measurement_id=m.measurement_id', ['measurementname' => 'm.name'])
            ->joinLeft(['ps' => 'main_skill'], 's.parentskill_id=ps.skill_id', ['parentskill' => 'ps.name'])

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
                $searchQuery .= "s. " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'skill';
        $tableFields = [
            'action' => 'Action',
            'name' => 'Skill',
            'code' => 'Code',
            'issummary' => 'Summary',
            'parentskill' => 'Parent Skill',
            'description' => 'Description',
            'isactive' => 'Active',
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
            'add' => 'add',
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'isactive' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Y' => 'Yes', 'N' => 'No']
                ],
                'issummary' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Y' => 'Yes', 'N' => 'No']
                ],
            ]
        ];
        return $dataTmp;
    }
    public function getskillsourcelist()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['sc' => 'main_measurement'], ['measurement_id' => 'sc.measurement_id', 'name' => 'sc.name'])
            ->where('sc.isactive="Y" ');


        return $this->fetchAll($select)->toArray();
    }
    public function getskilllist()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['s' => 'main_skill'], ['skill_id' => 's.skill_id', 'name' => 's.name'])
            ->where('s.isactive="Y" ');


        return $this->fetchAll($select)->toArray();
    }
    public function getskilllistnonsummary()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['s' => 'main_skill'], ['skill_id' => 's.skill_id', 'name' => 's.name'])
            ->where('s.isactive="Y" and s.issummary="N"');
        return $this->fetchAll($select)->toArray();
    }
    public function getskilllistsummary()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['s' => 'main_skill'], ['skill_id' => 's.skill_id', 'name' => 's.name'])
            ->where('s.isactive="Y" and s.issummary="Y"');
        return $this->fetchAll($select)->toArray();
    }
    public function SaveorUpdateData($data, $where = '')
    {
        if ($where != '') {
            $this->update($data, $where);
            return 'update';
        } else {
            $this->insert($data);
            $id = $this->getAdapter()->lastInsertId($this->_name);
            return $id;
        }
    }

    public function getmeasurementlist()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['m' => 'main_measurementindex'], ['measurementindex_id' => 'm.measurementindex_id', 'name' => 'm.description'])
            ->where('m.isactive="Y" ');


        return $this->fetchAll($select)->toArray();
    }
    public function getskill($id)
    {
        $linecate = $this->select()
            ->setIntegrityCheck(false)
            ->where('skill_id=' . $id);
        return $this->fetchRow($linecate)->toArray();
    }
    public function getskillforview($id)
    {
        $linecate = $this->select()
            ->setIntegrityCheck(false)
            ->from(['s' => 'main_skill'], ['id' => 's.skill_id', 'name' => 's.name', 'code' => 's.code', 'isactive' => 'if(s.isactive="Y","Yes","No")', 'issummary' => 'if(s.issummary="Y","Yes","No")', 'description' => 's.description'])
            ->joinLeft(['sc' => 'main_skillcategory'], 's.skillcategory_id=sc.skillcategory_id', ['skillcategoryname' => 'sc.name'])
            ->joinLeft(['m' => 'main_measurement'], 's.measurement_id=m.measurement_id', ['measurementname' => 'm.name'])
            ->joinLeft(['ps' => 'main_skill'], 's.parentskill_id=ps.skill_id', ['parentskill' => 'ps.name'])
            ->where('s.skill_id=' . $id);
        return $this->fetchRow($linecate)->toArray();
    }




    public function getallsource()
    {
        $linec = $this->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        return $this->fetchAll($linec)->toArray();
    }

    /**
     * Standard Pairs Retrieval for Skills.
     * 
     * @param bool $activeOnly
     * @return array [id => name]
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->from('main_skill', ['skill_id', 'name'])
            ->order('name ASC');

        if ($activeOnly) {
            $select->where('isactive = ?', 'Y');
        }

        return $this->getAdapter()->fetchPairs($select);
    }
}
