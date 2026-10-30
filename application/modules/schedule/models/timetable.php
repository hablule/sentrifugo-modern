<?php


class Schedule_Model_Timetable extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_shift';
    protected $_primary = 'shift_id';

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                if ($key == 'name')
                    $searchQuery .= "name like '%" . $val . "%' AND ";
                else if ($key == 'starttime')
                    $searchQuery .= "starttime like '%" . $val . "%' AND ";
                else if ($key == 'endtime')
                    $searchQuery .= "endtime like '%" . $val . "%' AND ";
                else
                    $searchQuery .= " " . $key . " like '%" . $val . "%' AND";
                $searchArray[$key] = $val;
            }

            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'timetable';
        $objID = 'timetable';
        $tableFields = array(
            'action' => 'Action',
            'name' => 'Name',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'isactive' => 'Active'
        );

        $tablecontent = $this->getTimetable($sort, $by, $pageNo, $perPage, $searchQuery);
        $group_model = new Schedule_Model_Timetable();
        $group_data = $group_model->getallshifts();

        $group_arr = array();

        foreach ($group_data as $gkey => $gdata) {
            $group_arr[$gdata['name']] = $gdata['name'];
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
            'formgrid' => '',
            'add' => 'addtime',
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'menuName' => 'Shift Table',
            'search_filters' => array(
                'isactive' => array(
                    'type' => 'select',
                    'filter_data' => array('' => 'All', 'Y' => 'Yes', 'N' => 'No')
                ),

            ),


        );

        return $dataTmp;
    }

    public function getTimetable($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";
        if ($searchQuery)
            $where .= ' AND ' . $searchQuery;

        $timetable = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('tm' => 'main_shift'), array(
                'id' => 'tm.shift_id', 'name' => 'tm.name', 'start_time' => 'DATE_FORMAT(tm.starttime,"%h:%i %p")', 'isactive' => 'if(tm.isactive="Y","Yes","No")', 'end_time' => 'DATE_FORMAT(tm.endtime,"%h:%i %p")'
            ))
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);

        //sapp_Global::export_data_to_excel($this->fetchAll($timetable)->toArray(),array('name','start_time','end_time','time','id','created_by','modified','isactive'),'mike.xls');

        return $timetable;
    }

    public function getTimetableData($id)
    {
        $timetable = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('mt'=>'main_shift'))
            ->join(array('tc'=>'main_timecategory'),'mt.timecategory_id = tc.timecategory_id', array('tcname'=>'tc.name', 'tccode'=>'tc.code','tcstolerance'=>'mt.starttolerence', 'tcetolerance'=>'mt.endtolerance'))
            ->where('shift_id = ' . $id);
       
        return $this->fetchAll($timetable)->toArray();
    }

    public function getShifts($shifts)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('main_shift'), array('starttime', 'endtime'))
            ->where('shift_id in (' . $shifts . ')');

        return $this->fetchAll($select)->toArray();
    }

    public function getallshifts()
    {
        $timetable = $this->select()
            ->setIntegrityCheck(false)
            ->where("isactive='Y'");
        return $this->fetchAll($timetable)->toArray();
    }

    public function saveandupdate($where, $data)
    {
        if ($where != '') {   //$this->updateSchedule($where['time_id=?'],$data);
            return $this->update($data, $where);
        } else {
            $this->insert($data);
            return $this->getAdapter()->lastInsertId();
        }
    }

    public function updateSchedule($where, $data)
    {
        $config = array(
            'name' => 'main_schedule',
            'primary' => 'sch_id'
        );
        $table = new Zend_Db_Table($config);

        $time = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'main_time_table'), 'e.time')
            ->where('id = ' . $where);
        $res = $this->fetchRow($time)->toArray()['time'];

        $sch = $this->select()
            ->setIntegrityCheck(false)
            ->from('main_schedule')
            ->where('time LIKE "%' . $res . '%"');

        $schres = $this->fetchAll($sch)->toArray();
        foreach ($schres as $key => $value) {
            $time = array('time' => str_replace($res, $data['time'], $value['time']));
            $table->update($time, 'id = ' . $value['id']);
        }
    }

    public function getschshifts($shiftid)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('m' => 'main_shift'), 'm.shift_id')
            ->join(array('n' => 'main_schedulecycleshift'), 'm.shift_id = n.shift_id')
            ->join(array('l' => 'main_schedulecycle'), 'l.schedulecycle_id = n.schedulecycle_id')
            ->join(array('ms' =>'main_schedule'),'ms.schedule_id = l.schedule_id')
            ->join(array('es' => 'main_empschedule'), 'es.schedule_id = ms.schedule_id')
            ->where('m.shift_id in (' . $shiftid . ')');
       
        return $this->fetchAll($select)->toArray();
    }

    public function getShiftName($id)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('m'=>'main_shift'), 'm.name')
                        ->where('m.shift_id='.$id);
        return $this->fetchRow($select)->toArray();
    }

}
