<?php


class Schedule_Model_Timeschedule extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_schedule';
    protected $_primary = 'schedule_id';

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $p1, $p2, $p3, $p4)
    {
        $searchQuery = '';
        $searchArray = array();
        $data = array();
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);

            if (count($searchValues) > 0) {
                foreach ($searchValues as $key => $val) {
                    if ($key == 'name')
                        $searchQuery .= "name like '%" . $val . "%' AND ";
                    else if ($key == 'startdate')
                        $searchQuery .= " startdate like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                    else if ($key == 'enddate')
                        $searchQuery .= " end_date like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                    else if ($key == 'active')
                        $searchQuery .= " isactive like '%" . $val . "%' AND ";
                        else if ($key == 'verified')
                        $searchQuery .= " isverified like '%" . $val . "%' AND ";
                        
                        else if ($key == 'code')
                        $searchQuery .= " code like '%" . $val . "%' AND ";
                        else if ($key == 'cycle')
                        $searchQuery .= " cycle like '%" . $val . "%' AND ";
                    else $searchQuery .= "" . $key . " like '%" . $val . "%' AND ";
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
                //die(json_encode($searchQuery));
            }
        }

        $objName = 'timeschedule';
        $objID = 'timeschedule';
        $tableFields = array(
            'action' => 'Action',
            'name' => 'Name',
            'startdate' => 'Start Date',
            'enddate' => ' End Date',
            'cycle' => 'Cycle',
            'active' => 'Active',
            'verified'=>'verified'
        );

        $tablecontent = $this->getScheduleData($sort, $by, $pageNo, $perPage, $searchQuery);

        $group_model = new Schedule_Model_Timeschedule();
        $group_data = $group_model->getschedulelist();
        // die(json_encode($group_data));
        $group_arr = array();

        foreach ($group_data as $gkey => $gdata) {
            $group_arr[$gdata['schedule_name']] = $gdata['schedule_name'];
        }

        $dataTmp = array(
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objID,
            'extra' => array(),
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            // 'add' => 'add',
            'formgrid'=>'',
            'call' => $call,
            'menuName' => 'Schedule',
            'dashboardcall' => $dashboardcall,
            'search_filters' => array(
                'startdate' => array('type' => 'datepicker'),
                'enddate' => array('type' => 'datepicker'),
                'active' => array(
                    'type' => 'select',
                    'filter_data' => array('' => 'All', 'Y' => 'Yes', 'N' => 'No')
                ),
                'cycle' => array(
                    'type' => 'select',
                    'filter_data' => array('' => 'All', 'DAILY' => 'DAILY', 'WEEKLY' => 'WEEKLY','MONTHLY'=>'MONTHLY')
                ),
            ),


        );
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
            ->from(array('ms' => 'main_schedule'), array('id' => 'ms.schedule_id', 'name' => 'ms.name', 'code' => 'ms.code', 'startdate' =>  'DATE_FORMAT(ms.startdate,"'.DATEFORMAT_MYSQL.'")', 'enddate' =>  'DATE_FORMAT(ms.enddate,"'.DATEFORMAT_MYSQL.'")','cycle'=>'ms.cycle', 'active' => 'if(ms.isactive="Y","Yes","No")', 'verified' => 'if(ms.isverified="Y","Yes","No")'))
            ->where($where)
            ->order("$sort_field $sort_order")
            ->limitPage($pageNo, $perPage);

        return $roleData;
    }


    public function getSingleSchedule($id)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('sch' => 'main_schedule'), array('sch.name','sch.code', 'sch.startdate', 'sch.enddate','sch.cycle','sch.isactive','sch.isverified'))
            ->where('sch.schedule_id=' . $id);
        //die($query);
        $data = $this->fetchAll($query)->toArray();
        if (count($data) == 0) {
            $query = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('sch' => 'main_schedule'), array('sch.name', 'sch.startdate', 'sch.enddate',))
                ->where('sch.schedule_id=' . $id);
            $data = $this->fetchAll($query)->toArray();
        }
        return $data;
    }


    // public function getDaysofTheWeek()
    // {
    //     $config = array(
    //         'name' => 'tbl_weeks',
    //         'primary' => 'id'
    //     );
    //     $db = new Zend_Db_Table($config);
    //     $tblweeks = $db->select()
    //         ->setIntegrityCheck(false)
    //         ->where('isactive=1');
    //     return $db->fetchAll($tblweeks)->toArray();
    // }

    public function getschedulelist()
    {
        $config = array(
            'name' => 'main_schedule',
            'primary' => 'schedule_id'
        );
        $db = new Zend_Db_Table($config);
        $tblweeks = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        return $db->fetchAll($tblweeks)->toArray();
    }

    // public function getTimeshift()
    // {
    //     $config  = array(
    //         'name' => 'main_time_table',
    //         'primary' => 'time_id'
    //     );
    //     $db = new Zend_Db_Table($config);
    //     $tblweeks = $db->select()
    //         ->setIntegrityCheck(false)
    //         ->where('isactive="Y"');
    //     return $db->fetchAll($tblweeks)->toArray();
    // }

    

    public function saveORupdate($data, $where)
    {
        if ($where != '' && $where != 'undefined') {
            
            $this->update($data, $where);
            return 'update';
  
        } 
        else {
            
            $this->insert($data);
            $id=$this->getAdapter()->lastInsertId();
            return $id;
  
        }
  
    }
    public function deleteData($where,$tablename,$id)
    {
        $config=array(
            'name'=>$tablename,
            'primary'=>$id
        );
        $table = new Zend_Db_Table($config);
       
        return $table->delete($where);
    }
    // public function saveOrUpdateschworkday($where, $data)
    // {
    //     $config = array(
    //         'name' => 'main_sch_workday',
    //         'primary' => 'sch_workday_id'
    //     );
    //     $table = new Zend_Db_Table($config);

    //     if ($where != '' && $where != 'undefined' && $where != null) {

    //         $table->update($data, $where);
    //         $query = $table->select()
    //             ->from(array('wd' => 'main_sch_workday'), 'wd.sch_workday_id')
    //             ->where($where);
    //         $res = $table->fetchAll($query)->toArray();
    //         if (count($res) > 0) {
    //             return $res[0]['sch_workday_id'];
    //         } else {

    //             return $table->insert($data);
    //         }
    //     } else {
    //         return $table->insert($data);
    //     }
    // }
    // public function saveOrUpdateschworkshift($where, $data)
    // {
    //     $config = array(
    //         'name' => 'main_work_shift',
    //         'primary' => 'work_shift_id'
    //     );
    //     $table = new Zend_Db_Table($config);


    //     if ($where != '' && $where != 'undefined' && $where != null) {

    //         $table->update($data, $where);
    //         $query = $table->select()
    //             ->from(array('ws' => 'main_work_shift'), 'ws.work_shift_id')
    //             ->where($where);
    //         $res = $table->fetchAll($query)->toArray();
    //         if (count($res) > 0) {
    //         } else {
    //             //die(json_encode($data)."Schedule Workdy New Data");
    //             return $table->insert($data);
    //         }
    //     } else {
    //         return $table->insert($data);
    //     }
    // }

    public function getEmpSchedules($ids)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('m'=>'main_empschedule'), 'm.schedule_id')
                        ->where('m.schedule_id in('.$ids.')');
        return $this->fetchAll($select)->toArray();
    }

    public function getscheduledetail($id)
    {
        $roleData =  $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ms' => 'main_schedule'), array('id' => 'ms.schedule_id','ms.*', "ms.isactive as msactive"))
            ->join(array('sc' => 'main_schedulecycle'),'ms.schedule_id = sc.schedule_id')
            ->join(array('scs'=>'main_schedulecycleshift'),'sc.schedulecycle_id = scs.schedulecycle_id')
            ->join(array('s'=>'main_shift'), 's.shift_id = scs.shift_id',array('s.shift_id','s.name as shiftname','s.code as shiftcode','s.starttime','s.endtime'))
            ->join(array('tc'=>'main_timecategory'), 'tc.timecategory_id = s.timecategory_id',array('tc.lablecolor', 'tc.timecategory_id'))
            ->where('ms.schedule_id =' . $id);
          
        return $this->fetchAll($roleData)->toArray();
    }


    public function getScheduleCycle($id)
    {
        $roleData =  $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('sc'=>'main_schedule'),'sc.cycle')
                          ->where('sc.schedule_id='.$id);
        return $this->fetchRow($roleData)->toArray();
    }


    public function getscheduleByid($id)
    {
        $schedule_data = $this->select()
                              ->setIntegrityCheck(false)
                              ->from(array('s'=>'main_schedule'),array('s.startdate','s.enddate'))
                              ->where('s.schedule_id='.$id);
        return $this->fetchRow($schedule_data)->toArray();
    }

    // public function deactiveSchWorkday($sch_id)
    // {
    //     $config = array(
    //         'name' => 'main_sch_workday',
    //         'primary' => 'sch_workday_id'
    //     );
    //     $table = new Zend_Db_Table($config);
    //     $workday_id = $table->update(array('isactive' => 0), 'sch_id =' . $sch_id);
    //     $query = $table->select()
    //         ->from(array('wd' => 'main_sch_workday'), 'wd.sch_workday_id')
    //         ->where('wd.sch_id=' . $sch_id);
    //     //die($query);
    //     return $this->fetchAll($query)->toArray();
    // }

    // public function deactiveWorkShift($workday_id)
    // {
    //     $config = array(
    //         'name' => 'main_work_shift',
    //         'primary' => 'work_shift_id'
    //     );
    //     $table = new Zend_Db_Table($config);
    //     $table->update(array('isactive' => 0), 'workday_id=' . $workday_id);
    // }


    /****************************************
    * Below code is for Leave Request Purpose
    ****************************************/
    public function getEmpSchedule($emp_id)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('empsch' => 'main_emp_schedule'),array('sch_id'=>'sch.sch_id','name'=>'sch.schedule_name'))
            ->joinInner(array('sch' => 'main_schedule'), 'sch.sch_id = empsch.sch_id','')
            ->where('empsch.emp_id = ' . $emp_id . ' AND sch.isactive = 1');
           
            return $this->fetchAll($query)->toArray();
    }

    public function getScheduleShift($sch_id){
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('wd'=>'main_sch_workday'),'')
            ->joinInner(array('ws'=>'main_work_shift'),'ws.workday_id=wd.sch_workday_id','')
            ->joinInner(array('tm' => 'main_time_table'), 'tm.time_id = ws.time_id', array('name' => 'tm.name','id'=>'tm.time_id'))
            ->where('wd.sch_id='.$sch_id)
            ->group(array('tm.name','tm.time_id'));
            
            return $this->fetchAll($query)->toArray();
    }

}
