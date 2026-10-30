<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
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

class Default_Model_Addperk extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_perk';
    protected $_primary = 'perk_id';

    public function getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $PerkData = $this->select()
            ->setIntegrityCheck(false)
            ->from(['p' => 'main_perk'], ['perk_id' => 'perk_id', 'name' => 'p.name', 'code' => 'p.code', 'description' => 'p.description', 'start' => 'DATE_FORMAT(p.start,"'.DATEFORMAT_MYSQL.'")', 'end' => 'DATE_FORMAT(p.end,"'.DATEFORMAT_MYSQL.'")', 'type' => 'p.type', 'isactive' => 'if(p.isactive="N","No","Yes")','auto_approved' => 'p.auto_approved'])
            ->joinInner(['pc' => 'main_perkcategory'], "pc.perkcategory_id = p.category ", ['category' => 'pc.name'])
            //    ->joinInner(array('pc'=>'main_perkcategory'), 's.id=c.state',array('state_name'=>'s.state_name'))	
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
        return $PerkData;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $p1, $p2, $p3, $p4)
    {
        $searchQuery = '';
        $searchArray = [];
        $data = [];
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                if (
                    $key == 'userfullname' || $key == 'emprole_name' || $key == 'emailaddress' ||
                    $key == 'backgroundchk_status' || $key == 'position_name' || $key == 'date_of_joining'
                ) {
                } else if ($key == 'code')
                    $searchQuery .= " p.code like  '%" . $val . "%' AND ";
                else if ($key == 'name')
                    $searchQuery .= " p." . $key . " like '%" . $val . "%' AND ";
                else if ($key == 'category')
                    $searchQuery .= " p.category like '%" . $val . "%' AND ";
                    else if ($key == 'start')
                    $searchQuery .= " p.start like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                    else if ($key == 'end')
                    $searchQuery .= " p.end like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                else
                    $searchQuery .= " p." . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }

            $searchQuery = rtrim($searchQuery, " AND");
        }

        $objName = 'addperk';
        $objId = 'add';

        $tableFields = [
            'action' => 'Action',
            'name' => 'Name',
            'code' => 'Code',
            'category' => 'Category',
            'description' => 'Description',
            'start' => 'Start_date',
            'end' => 'End date',
            'type' => 'Type',
            'isactive'=>'Active'
        ];

        $tablecontent = $this->getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery);
        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objId,
            'menuName' => 'Perk List',
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'add' => 'add',
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [

            'start' => ['type' => 'datepicker'],
                'end' => ['type' => 'datepicker'],
                    'isactive' => [
                        'type' => 'select',
                        'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
            ],
        ];
        return $dataTmp;
    }
    public function getsalary($id)
    {
        $result = $this->select()
        ->setIntegrityCheck(false)
        ->from(['s' => 'main_empsalarydetails'],['amount' => 's.amount'])
        ->where('s.emp_id='.$id.' AND s.isactive="Y"');
 //die($result);
            return $this->fetchAll($result)->toArray();
        }
    public function saveMultiEmpPerk($datas)
    {
        $config = [
            'name' => 'main_employeeperk',
            'primary' => 'employeeperk_id'
        ];
        $this->table = new Zend_Db_Table($config);

        foreach ($datas as $data) {
            $this->table->update($data,'emp_id='.$data['emp_id'].' AND perk_id='.$data['perk_id']);
            $query = $this->select()
                ->setIntegrityCheck(false)
                ->from(['e' => 'main_employeeperk'], 'e.employeeperk_id')
                ->where('e.emp_id = ' . $data['emp_id'] . ' AND e.perk_id = ' . $data['perk_id']);
            $result = $this->fetchAll($query)->toArray();
            if(count($result)<=0){
               
                $this->table->insert($data);
            } 
        }
        
    }

    public function getEmpId($id)
    {
        //     $config = array(
        //         'name' => 'main_employees',
        //         'primary' => 'id'
        //     );
        //     $this->table = new Zend_Db_Table($config);
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_employees'], 'e.user_id')
            ->where('e.id =' . $id);
        return $this->fetchAll($query)->toArray();
    }

    public function getEmpIdperk($id)
    {
        //     $config = array(
        //         'name' => 'main_employees',
        //         'primary' => 'id'
        //     );
        //     $this->table = new Zend_Db_Table($config);
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_employeeperk'], 'e.emp_id')
            ->where('e.perk_id =' . $id);
        return $this->fetchAll($query)->toArray();
    }
    public function getperkamount($id)
    {
            $config = [
                'name' => 'main_perk',
                'primary' => 'perk_id'
            ];
            $this->table = new Zend_Db_Table($config);
        $query =$this->table ->select()
            ->setIntegrityCheck(false)
            ->from(['mp' => 'main_perk'], 'mp.amount')
            ->where('mp.perk_id =' . $id);
        return $this->table ->fetchAll($query)->toArray();
    }
    public function getperktype($id)
    {
            $config = [
                'name' => 'main_perk',
                'primary' => 'perk_id'
            ];
            $this->table = new Zend_Db_Table($config);
        $query =$this->table ->select()
            ->setIntegrityCheck(false)
            ->from(['mp' => 'main_perk'], ['mp.type','mp.auto_approved'])
            ->where('mp.perk_id =' . $id);
            
        return $this->table ->fetchAll($query)->toArray();
    }
}
