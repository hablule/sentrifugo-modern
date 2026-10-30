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
            ->from(array('s' => 'main_skill'), array('id' => 's.skill_id', 'name' => 's.name', 'code' => 's.code', 'isactive' => 'if(s.isactive="Y","Yes","No")', 'issummary' => 'if(s.issummary="Y","Yes","No")', 'description' => 's.description'))
            ->joinLeft(array('sc' => 'main_skillcategory'), 's.skillcategory_id=sc.skillcategory_id', array('skillcategoryname' => 'sc.name'))
            ->joinLeft(array('m' => 'main_measurement'), 's.measurement_id=m.measurement_id', array('measurementname' => 'm.name'))
            ->joinLeft(array('ps' => 'main_skill'), 's.parentskill_id=ps.skill_id', array('parentskill' => 'ps.name'))

            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);

        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = array();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                $searchQuery .= "s. " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'skill';
        $tableFields = array(
            'action' => 'Action',
            'name' => 'Skill',
            'code' => 'Code',
            'issummary' => 'Summary',
            'parentskill' => 'Parent Skill',
            'description' => 'Description',
            'isactive' => 'Active',
        );

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery);
        $dataTmp = array(
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'extra' => array(),
            'add' => 'add',
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => array(
                'isactive' => array(
                    'type' => 'select',
                    'filter_data' => array('' => 'All', 'Y' => 'Yes', 'N' => 'No')
                ),
                'issummary' => array(
                    'type' => 'select',
                    'filter_data' => array('' => 'All', 'Y' => 'Yes', 'N' => 'No')
                ),
            )
        );
        return $dataTmp;
    }
    public function getskillsourcelist()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('sc' => 'main_measurement'), array('measurement_id' => 'sc.measurement_id', 'name' => 'sc.name'))
            ->where('sc.isactive="Y" ');


        return $this->fetchAll($select)->toArray();
    }
    public function getskilllist()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('s' => 'main_skill'), array('skill_id' => 's.skill_id', 'name' => 's.name'))
            ->where('s.isactive="Y" ');


        return $this->fetchAll($select)->toArray();
    }
    public function getskilllistnonsummary()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('s' => 'main_skill'), array('skill_id' => 's.skill_id', 'name' => 's.name'))
            ->where('s.isactive="Y" and s.issummary="N"');
        return $this->fetchAll($select)->toArray();
    }
    public function getskilllistsummary()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('s' => 'main_skill'), array('skill_id' => 's.skill_id', 'name' => 's.name'))
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
            ->from(array('m' => 'main_measurementindex'), array('measurementindex_id' => 'm.measurementindex_id', 'name' => 'm.description'))
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
            ->from(array('s' => 'main_skill'), array('id' => 's.skill_id', 'name' => 's.name', 'code' => 's.code', 'isactive' => 'if(s.isactive="Y","Yes","No")', 'issummary' => 'if(s.issummary="Y","Yes","No")', 'description' => 's.description'))
            ->joinLeft(array('sc' => 'main_skillcategory'), 's.skillcategory_id=sc.skillcategory_id', array('skillcategoryname' => 'sc.name'))
            ->joinLeft(array('m' => 'main_measurement'), 's.measurement_id=m.measurement_id', array('measurementname' => 'm.name'))
            ->joinLeft(array('ps' => 'main_skill'), 's.parentskill_id=ps.skill_id', array('parentskill' => 'ps.name'))
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
            ->from('main_skill', array('skill_id', 'name'))
            ->order('name ASC');

        if ($activeOnly) {
            $select->where('isactive = ?', 'Y');
        }

        return $this->getAdapter()->fetchPairs($select);
    }
}
