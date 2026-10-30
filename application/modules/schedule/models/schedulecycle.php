<?php


class Schedule_Model_Schedulecycle extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_schedulecycle';
    protected $_primary = 'schedulecycle_id';

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
                    if ($key == 'schedule')
                        $searchQuery .= "s.name like '%" . $val . "%' AND ";
                    else if ($key == 'isactive')
                        $searchQuery .= " sc.startdate like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                    else if ($key == 'dayily')
                        $searchQuery .= " sc.dayily like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                    else if ($key == 'dayofweek')
                        $searchQuery .= " sc.dayofweek like '%" . $val . "%' AND ";
                        else if ($key == 'cycle')
                        $searchQuery .= " s.cycle like '%" . $val . "%' AND ";
                        else if ($key == 'dayofmonth')
                        $searchQuery .= " sc.dayofmonth like '%" . $val . "%' AND ";
                        
                        else if ($key == 'description')
                        $searchQuery .= " sc.description like '%" . $val . "%' AND ";
             
                    else $searchQuery .= "sc." . $key . " like '%" . $val . "%' AND ";
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
                //die(json_encode($searchQuery));
            }
        }

        $objName = 'schedulecycle';
        $objID = 'schedulecycle';
        $tableFields = [
            'action' => 'Action',
            'schedule' => 'Schedule',
            'cycle'=>'Cycle',
            'daily' => 'Daily',
            'dayofweek' => 'Day of Week',
            'dayofmonth' => 'Day of Month',
            'active' => 'Active',
            'description'=>'description'
        ];

        $tablecontent = $this->getScheduleData($sort, $by, $pageNo, $perPage, $searchQuery);

      
        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objID,
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            // 'add' => 'add',
            'formgrid'=>'',
            'call' => $call,
            'menuName' => 'Schedule Cycle',
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'active' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Y' => 'Yes', 'N' => 'No']
                ],
                'cycle' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'DAILY' => 'DAILY', 'WEEKLY' => 'WEEKLY','MONTHLY'=>'MONTHLY']
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
            ->from(['sc' => 'main_schedulecycle'], ['id' => 'sc.schedulecycle_id','sc.*','active' => 'if(sc.isactive="Y","Yes","No")'])
            ->joinLeft(['s' => 'main_schedule'], 'sc.schedule_id = s.schedule_id', ['schedule' => 's.name','cycle'=>'s.cycle'])
            ->where($where)
            ->order("$sort_field $sort_order")
            ->limitPage($pageNo, $perPage);

        return $roleData;
    }


    public function getSingleSchedule($id)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['sch' => 'main_schedule'], ['sch.name','sch.code', 'sch.startdate', 'sch.enddate','sch.cycle','sch.isactive','sch.isverified'])
            ->where('sch.schedule_id=' . $id);
        //die($query);
        $data = $this->fetchAll($query)->toArray();
        if (count($data) == 0) {
            $query = $this->select()
                ->setIntegrityCheck(false)
                ->from(['sch' => 'main_schedule'], ['sch.name', 'sch.startdate', 'sch.enddate',])
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
        $config = [
            'name' => 'main_schedule',
            'primary' => 'schedule_id'
        ];
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


    public function checkCycle($id, $cycle, $dayof)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->where('schedule_id='.$id.' AND '.$dayof.'="'.$cycle.'"');
       //die($select);
        $cycleData = $this->fetchAll($select)->toArray();
        if(empty($cycleData))
        {
            return true;
        }
        return false;
    }


    

    

    public function saveORupdate($data, $where)
    {
        if ($where != '' && $where != 'undefind') {
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
        $config=[
            'name'=>$tablename,
            'primary'=>$id
        ];
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

    public function getCycleSchedule()
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where("isactive='Y'");
        return $this->fetchAll($select)->toArray();
    }

    public function getschedulecyclebyid($id)
    {
        $roleData =  $this->select()
            ->setIntegrityCheck(false)
            ->from(['sc' => 'main_schedulecycle'], ['id' => 'sc.schedulecycle_id','sc.*'])
            ->where('sc.schedulecycle_id='.$id);
        return $this->fetchAll($roleData)->toArray();
    }

    public function getCycleShift($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(['sc'=>'main_schedulecycle'])
                       ->where('schedule_id='.$id);
                       
        return $this->fetchAll($select)->toArray();
    }


}
