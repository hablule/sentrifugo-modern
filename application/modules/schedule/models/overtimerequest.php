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

class Schedule_Model_Overtimerequest extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_overtime_approval';
    protected $_primary = 'OT_approval_id';

    public function getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery, $empids, $perm)
    {
        $where = " e.id in (" . $empids . ") ";
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $AdditionData = $this->select()
            ->setIntegrityCheck(false)
            ->from(['oa' => 'main_overtime_approval'], ['id' => 'oa.OT_approval_id', 'OT_id' => 'oa.OT_id', 'processed' => 'if(oa.processed="N","No","Yes")', 'start_time' => 'DATE_FORMAT(oa.start_time,"' . DATEFORMAT_MYSQL . '")', 'end_time' => 'DATE_FORMAT(oa.end_time,"' . DATEFORMAT_MYSQL . '")', 'earned_time' => 'oa.earned_time', 'amount_due' => 'oa.amount_due', 'approved' => 'if(oa.approved="N","Rejected",if(approved="Y","Approved","Pending"))'])
            ->joinInner(['mo' => 'main_overtime'], "mo.overtime_id = oa.OT_id ", ['name' => 'mo.name', 'base' => 'mo.base'])
            ->joinInner(['e' => 'main_employees'], "e.id = oa.emp_id ", ['emp_id' => 'e.id', 'userfullname' => 'e.userfullname', 'profileimg' => 'e.profileimg'])
            ->joinInner(['oc' => 'main_overtime_category'], "mo.OT_category_id = oc.OT_category_id ", ['c_name' => 'oc.name', 'c_description' => 'oc.description'])
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
        //die($AdditionData);
        return $AdditionData;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $empids, $perm)
    {
        $searchQuery = '';
        $searchArray = [];
        $data = [];
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                if ($key == 'base')
                    $searchQuery .= " mo.base like '%" . $val . "%' AND ";
                else if ($key == 'name')
                    $searchQuery .= " mo.name  like '%" . $val . "%' AND ";
                else if ($key == 'c_name')
                    $searchQuery .= " oc.name like '%" . $val . "%' AND ";
                else if ($key == 'c_description')
                    $searchQuery .= " oc.description like '%" . $val . "%' AND ";
                else if ($key == 'userfullname')
                    $searchQuery .= " e.userfullname like '%" . $val . "%' AND ";
                else if ($key == 'start_time')
                    $searchQuery .= " oa.start_time like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                else if ($key == 'end_time')
                    $searchQuery .= " oa.end_time like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                else if ($key == 'processed')
                    $searchQuery .= "  oa.processed like '%" . $val . "%' AND ";
                else
                    $searchQuery .= " oa." . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }

            $searchQuery = rtrim($searchQuery, " AND");
            // die(json_encode($searchQuery));
        }
        $formgrid = '';
        $objName = 'manageremployeeovertime';

        $objId = 'manageremployeeovertime';

        $tableFields = [
            'action' => 'Action',
            'name' => 'Overtime Name',
            'userfullname' => 'Employeee Name',

            'c_name' => 'Category',
            'c_description' => 'Description',
            'approved' => 'Status',
            'processed' => 'Proccessed',
            'start_time' => 'Start Date',
            'end_time' => 'End Date',
            'base' => 'Base',
            // 'earned_time'=> 'Earned Minute',
            'amount_due' => 'Payment Amount',


        ];
        $tablecontent = $this->getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery, $empids, $perm);
        $group_model = new Schedule_Model_Overtimerequest();
        $group_data = $group_model->getAllOTreguest();
        //die(json_encode($group_data));
        $group_arr = [];

        foreach ($group_data as $gkey => $gdata) {
            $group_arr[$gdata['name']] = $gdata['name'];
        }

        $group_arr[''] = 'All';
        $log_model = new Schedule_Model_Logreport();
        $emp_data = $log_model->getempLis();

        $emp_arr = [];

        foreach ($emp_data as $ekey => $edata) {
            $emp_arr[$edata['userfullname']] = $edata['userfullname'];
        }
        // die(json_encode($group_data));
        $over_model = new Schedule_Model_Overtime();
        $over_data = $over_model->getAllOTCategories();
        //die(json_encode($over_data));
        $over_arr = [];

        //code...

        foreach ($over_data as $ekey => $edata) {
            $over_arr[$edata['name']] = $edata['name'];
            //Zend_Debug::dump($over_arr);

        }
        $over_arr[''] = 'All';



        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objId,
            'menuName' => 'Employee Overtime Request List',
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'formgrid' => $formgrid,
            'add' => 'add',
            'perm' => $perm,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'start_time' => ['type' => 'datepicker'],
                'end_time' => ['type' => 'datepicker'],

                'name' => [
                    'type' => 'select',
                    'filter_data' => $group_arr
                ],
                'c_name' => [
                    'type' => 'select',
                    'filter_data' => $over_arr
                ],
                'base' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Time' => 'Time', 'Date' => 'Date', 'OffShift' => 'OffShift', 'Holidays' => 'Holidays', 'Day' => 'Day', 'Period' => 'Period']
                ],
                'processed' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Y' => 'Yes', 'N' => 'No']
                ],
                'approved' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Y' => 'Approved', 'N' => 'Rejected', 'P' => 'Pending']
                ],
            ],
        ];

        return $dataTmp;
    }
    public function DeleteData($where)
    {
        $this->delete($where);
        return 'delete';
    }

    public function getAllOTreguest()
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['oa' => 'main_overtime_approval'], 'oa.*')
            ->joinInner(['mo' => 'main_overtime'], "mo.overtime_id = oa.OT_id ", ['name' => 'mo.name'])
            ->joinInner(['oc' => 'main_overtime_category'], "mo.OT_category_id = oc.OT_category_id ", ['c_name' => 'oc.name'])
            ->where('mo.isActive="Y"');
        return $this->fetchAll($query)->toArray();
    }
    public function overtimedetail($id)
    {
        $result = $this->select()
            ->setIntegrityCheck(false)
            ->from(['oa' => 'main_overtime_approval'], ['OT_id' => 'oa.OT_id', 'processed' => 'oa.processed', 'emp_idb' => 'oa.emp_id', 'start_time' => 'oa.start_time', 'end_time' => 'oa.end_time', 'earned_time' => 'oa.earned_time', 'amount_due' => 'oa.amount_due', 'approved' => 'oa.approved', 'status' => 'if(oa.approved="N","No","Yes")', 'approved_amount' => 'oa.approved_amount'])
            ->joinInner(['mo' => 'main_overtime'], "mo.overtime_id = oa.OT_id ", ['name' => 'mo.name', 'base' => 'mo.base'])
            ->joinInner(['e' => 'main_employees'], "e.id = oa.emp_id ", ['emp_id' => 'e.id', 'userfullname' => 'e.userfullname'])
            ->joinInner(['oc' => 'main_overtime_category'], "mo.OT_category_id = oc.OT_category_id ", ['c_name' => 'oc.name', 'c_description' => 'oc.description'])
            ->where('oa.OT_approval_id=' . $id);
        return $this->fetchAll($result)->toArray();
    }
    public function editovertime($data, $where)
    {
        // die(json_encode(($data)));
        $this->update($data, $where);
    }
}
