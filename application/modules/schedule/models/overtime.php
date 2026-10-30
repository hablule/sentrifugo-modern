<?php

class Schedule_Model_Overtime extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_overtime';
    protected $_primary = 'overtime_id';

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $p1, $p2, $p3, $p4)
    {
        $searchQuery = '';
        $searchArray = [];
        $data = [];
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);

            if (count($searchValues) > 0) {
                foreach ($searchValues as $key => $val) {
                    if ($key == 'category')
                        $searchQuery .= " oc." . 'name' . " like '%" . $val . "%' AND ";
                    elseif ($key == 'begining')
                        $searchQuery .= " ot.begining like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                    elseif ($key == 'ending')
                        $searchQuery .= " ot.ending like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                    else
                        $searchQuery .= "ot." . $key . " like '%" . $val . "%' AND ";
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }

        $objName = 'overtime';
        $objID = 'overtime';
        $tableFields = [
            'action' => 'Action',
            'name' => 'Name',
            'base' => 'Base',
            'category' => 'Category',
            'description' => 'Description',
            'begining' => 'Begining Date',
            'ending' => 'Ending Date',
            'isActive' => 'Active'
        ];

        $tablecontent = $this->getScheduleData($sort, $by, $pageNo, $perPage, $searchQuery);
        $group_model = new Schedule_Model_Overtime();
        $group_data = $group_model->getAllOTCategories();
        // die(json_encode($group_data));
        $group_arr = [];

        foreach ($group_data as $gkey => $gdata) {
            $group_arr[$gdata['name']] = $gdata['name'];
        }
        $over_model = new Schedule_Model_Overtime();
        $over_data = $over_model->getAllOT();
        // die(json_encode($group_data));
        $over_arr = [];

        foreach ($over_data as $ekey => $edata) {
            $over_arr[$edata['name']] = $edata['name'];
        }



        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objID,
            'menuName' => 'Over Time List',
            'formgrid'=>'',
            
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'add' => 'addnewot',
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'begining' => ['type' => 'datepicker'],
                'ending' => ['type' => 'datepicker'],
                'category' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All'] + $group_arr,
                ],
                // 'name' => array(
                //     'type' => 'select',
                //     'filter_data' => array('' => 'All') + $over_arr,
                // ),
                'base' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Time' => 'Time', 'Date' => 'Date', 'OffShift' => 'OffShift', 'Holidays' => 'Holidays', 'Day' => 'Day', 'Period' => 'Period']
                ],
                'isActive' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', '1' => 'Yes', '0' => 'No']
                ],

            ],


        ];
        return $dataTmp;
    }


    public function getScheduleData($sort_order, $sort_field, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";
        if ($searchQuery) {
            $where .= " AND " . $searchQuery;
        }

        $roleData =  $this->select()
            ->setIntegrityCheck(false)
            ->from(['ot' => 'main_overtime'], [
                'overtime_id' => 'ot.overtime_id', 'name' => 'ot.name', 'base' => 'ot.base',
                'description' => 'ot.description', 'begining' =>  'DATE_FORMAT(ot.begining,"'.DATEFORMAT_MYSQL.'")', 'ending' =>  'DATE_FORMAT(ot.ending,"'.DATEFORMAT_MYSQL.'")', 'isActive' => 'if(ot.isActive="Y","Yes","No")'
            ])
            ->joinInner(['oc' => 'main_overtime_category'], 'oc.OT_category_id = ot.OT_category_id', ['category' => 'oc.name'])
            ->where($where)
            ->order("$sort_field $sort_order")
            ->limitPage($pageNo, $perPage);

        return $roleData;
    }




    public function getAlltime($begining_date, $ending_date, $start_time, $end_time)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ot' => 'main_overtime'], [
                'start_date' => 'if("' . $begining_date . '" between ot.begining AND ot.ending,"Yes","No")',
                'end_date' => 'if("' . $ending_date . '" between ot.begining AND ot.ending,"Yes","No")',
                'start_time' => 'if("' . $start_time . '" between os.start_time AND os.end_time,"Yes","No")',
                'end_time' => 'if("' . $end_time . '" between os.start_time AND os.end_time,"Yes","No")'
            ])
            ->joinInner(['os' => 'main_overtime_schedule'], 'os.OT_id = ot.overtime_id', '')
            ->where('(("' . $begining_date . '" between ot.begining AND ot.ending) OR ("' . $ending_date . '" between ot.begining AND ot.ending))
                    AND (("' . $start_time . '" between os.start_time AND os.end_time) OR ("' . $end_time . '" between os.start_time AND os.end_time))');

        return $this->fetchAll($query)->toArray();
    }


    public function saveEmpOT($data)
    {
        $config = [
            'name' => 'main_overtime_approval',
            'primary' => 'OT_approval_id'
        ];
        $table = new Zend_Db_Table($config);
        //die(json_encode($data));
        $table->insert($data);
        return $table->getAdapter()->lastInsertId();
    }
    public function saveORupdate($data, $where, $data_schedule, $where_schedule)
    {
        $config = [
            'name' => 'main_overtime_schedule',
            'primary' => 'OT_schedule_id'
        ];
        $table = new Zend_Db_Table($config);
        if ($where != '' && $where != 'undefined') {
                   //  die(json_encode($data_schedule).$where_schedule.json_encode($data).$where);

            $this->update($data, $where);
            $table->update($data_schedule, $where_schedule);
        } else {
            $id = $this->insert($data);
            $id = $this->getAdapter()->lastInsertId();
            $data_schedule['OT_id'] = $id;
            $table->insert($data_schedule);
          
            return $id;
        }
    }

    public function saveORupdateCategory($data, $where)
    {

        $config = [
            'name' => 'main_overtime_category',
            'primary' => 'OT_category_id'
        ];
        $table = new Zend_Db_Table($config);
        if ($where != '' && $where != 'undefind') {

            $table->update($data, $where);
        } else {
            $table->insert($data);
        }
    }

    public function saveORupdateApproval($data, $where)
    {
        $config = [
            'name' => 'main_overtime_approval',
            'primary' => 'OT_approval_id'
        ];
        $table =  new Zend_Db_Table($config);
        // $db = Zend_Db_Table::getDefaultAdapter();
        // $query = "UPDATE main_overtime_approval
        //           SET
        //             start_time = '" . $data['start_time'] . "' ,
        //             end_time = '" . $data['end_time'] . "',
        //             amount_due = '".$data['amount_due']."',
        //             earned_time = ".$data['earned_time']."
        //           WHERE
        //             emp_id = " . $data['emp_id'] . " AND submitted_date = '" . $data['submitted_date'] . "' AND OT_id = " . $data['OT_id'];

        // $res = $db->query($query);
        // $affectedrows = $res->rowCount();
        // if ($affectedrows == 0) {

        //     $result = $table->select()
        //         ->where("emp_id = " . $data['emp_id'] . " AND submitted_date = '" . $data['submitted_date'] . "' AND OT_id = " . $data['OT_id']);
        //if (count($table->fetchAll($result)->toArray()) == 0) {
        // Zend_Debug::dump($data, 'DAta');
        if($where != ''){
           $table->update($data,$where);
        }else{
            $table->insert($data);
        }
        
        //}
        // }
    }
    public function getEmpIDPerOT($id)
    {
        //     $config = array(
        //         'name' => 'main_employees',
        //         'primary' => 'id'
        //     );
        //     $this->table = new Zend_Db_Table($config);
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ot' => 'main_overtime_approval'], 'ot.emp_id')
            ->where('ot.OT_id =' . $id);
        return $this->fetchAll($query)->toArray();
    }

    public function getAllOTCategories()
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['oc' => 'main_overtime_category'], 'oc.*');
           // die($query);
        return $this->fetchAll($query)->toArray();
    }
    public function getAllOT()
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['oc' => 'main_overtime'], 'oc.*')
            ->where('oc.isActive=1');
        return $this->fetchAll($query)->toArray();
    }

    public function getSingleOT($id)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ot' => 'main_overtime'], [
                'name' => 'ot.name', 'description' => 'ot.description', 'base' => 'ot.base', 'begining' => 'ot.begining',
                'ending' => 'ot.ending', 'category' => 'ot.OT_category_id','autoapprove'=>'ot.autoapprove','isactivename'=>'ot.isactive'
            ])
            ->joinInner(['oc' => 'main_overtime_category'], 'oc.OT_category_id = ot.OT_category_id', ['category_name' => 'oc.name'])
            ->joinInner(['os' => 'main_overtime_schedule'], 'os.OT_id = ot.overtime_id', [
                's_start_time' => 'DATE_FORMAT(os.start_time,"%h:%i %p")', 's_end_time' => 'DATE_FORMAT(os.end_time,"%h:%i %p")',
                's_day' => 'os.day', 's_start_date' => 'os.start_date', 's_end_date' => 'os.end_date', 's_rate' => 'os.rate'
            ])
            ->where('ot.overtime_id =' . $id);
                
        return $this->fetchAll($query)->toArray();
    }

    public function deleteOT($id)
    {
        $config = [
            'name' => 'main_overtime_schedule',
            'primary' => 'OT_schedule_id'
        ];
        $OT_schedule_table = new Zend_Db_Table($config);
        $OT_schedule_table->delete('OT_id=' . $id);
        $data = [
            'isActive' => 0
        ];
        $this->update($data, 'overtime_id = ' . $id);
    }

    public function getOT($currentdate)
    {
        $ottable = $this->select()
            ->setIntegrityCheck(false)
            ->from('main_overtime')
            ->where('"' . $currentdate . '" BETWEEN begining AND ending');
        return $this->fetchAll($ottable)->toArray();
    }

    public function getOTschedule($id)
    {
        $otsch = $this->select()
            ->setIntegrityCheck(false)
            ->from('main_overtime_schedule')
            ->where('OT_id = ' . $id);
        return $this->fetchAll($otsch)->toArray();
    }
}
