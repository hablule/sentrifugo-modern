<?php



class Schedule_Model_attendance extends Zend_Db_Table_Abstract
{

    public function getBmdata($date)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "SELECT * FROM bm_event AS bm  WHERE FROM_UNIXTIME(datetime) LIKE '$date%' AND bm.event_type_id IN (55,58,59)";
        //Zend_Debug::dump($query);
        $res = $db->query($query);
        //Zend_Debug::dump($res->fetchAll());
       //die($query);
        return $res->fetchAll();
    }
    public function getBmdataemp($userid,$date)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "SELECT * FROM bm_event AS bm  WHERE  FROM_UNIXTIME(datetime) LIKE '$date%' and bm.user_id=$userid AND bm.event_type_id IN (55,58,59)";
       // Zend_Debug::dump($query);
        $res = $db->query($query);
        //Zend_Debug::dump($res->fetchAll());
       //die($query);
        return $res->fetchAll();
    }

    public function getempsch($id)
    {
        $config = [
            'name' => 'main_emp_schedule',
            'primary' => 'id'
        ];
        $empschtable = new Zend_Db_Table($config);
        $data = $empschtable->select()
            ->setIntegrityCheck(false)
            ->where('emp_id = ?', $id);
        $empschdata = $empschtable->fetchAll($data)->toArray();
        Zend_Debug::dump($id);
        return $empschdata;
    }
    public function getschedule($schid)
    {
        $config = [
            'name' => 'main_schedule',
            'primary' => 'id'
        ];

        $schtable = new Zend_Db_Table($config);
        $data = $schtable->select()
            ->setIntegrityCheck(false)
            ->where('id = ' . $schid);

        return $schtable->fetchRow($data)->toArray();
    }
    public function getschedulebyname()
    {
        $config = [
            'name' => 'main_schedule',
            'primary' => 'id'
        ];

        $schtable = new Zend_Db_Table($config);
        $data = $schtable->select()
            ->setIntegrityCheck(false)
            ->where('schedule_name = "overtime"');
        //Zend_Debug::dump($schtable->fetchRow($data)->toArray());

        return $schtable->fetchRow($data)->toArray();
    }
    public function getempids()
    {
        $config = [
            'name' => 'main_employees',
            'primary' => 'id'
        ];
        $emptable = new Zend_Db_Table($config);
        $data = $emptable->select()
            ->setIntegrityCheck(false)
            ->from($config['name'], 'user_id')
            ->where('isactive=1');
        return $emptable->fetchAll($data)->toArray();
    }
    public function getempschdetail($weekname, $id, $date)
    {

        /* $config = array(
            'name' => 'tbl_weeks',
            'primary' => 'id'
        );
        $weektable = new Zend_Db_Table($config);
        $data = $weektable->select()
            ->setIntegrityCheck(false)
            ->from($config['name'], 'id')
            ->where('week_name LIKE ?', $weekname . '%');
        $weekid = $weektable->fetchRow($data)->toArray(); */
        //Zend_Debug::dump($weekid);
        $config = [
            'name' => 'main_schedule',
            'primary' => 'sch_id'
        ];
        $scheduletable = new Zend_Db_Table($config);

        $schdata = $scheduletable->select()
            ->setIntegrityCheck(false)
            ->from(['sch'=>'main_schedule'], '')
            ->joinInner(['wd'=>'main_sch_workday'],'wd.sch_id = sch.sch_id AND wd.workday like "'.$weekname.'%" AND wd.isactive=1','')
            ->joinInner(['ws'=>'main_work_shift'],'ws.workday_id = wd.sch_workday_id AND ws.isactive=1','')
            ->joinInner(['tm'=>'main_time_table'],'tm.time_id = ws.time_id AND tm.isactive=1',['shift_id'=>'tm.time_id','tm.start_time','tm.end_time'])
            ->where('"'.$date.'" BETWEEN sch.start_date AND sch.end_date AND sch.isactive=1');
            
        $array = $scheduletable->fetchAll($schdata)->toArray();

        if (count($array) != 0) {
            return $scheduletable->fetchAll($schdata)->toArray();
        } else {
            return null;
        }



        //return $scheduletable->fetchRow($schdata)->toArray();
    }
    public function getleaveapproved($id,$date)
    {
        $config = [
            'name' => 'main_leaverequest',
            'primary' => 'id'
        ];
        $leavetable = new Zend_Db_Table($config);
        $data = $leavetable->select()
            ->setIntegrityCheck(false)
            ->where('user_id = ' . $id . ' AND leavestatus = "Approved" AND isactive = 1 AND "'.$date.'" BETWEEN from_date AND to_date');
        $result = $leavetable->fetchAll($data)->toArray();
        return $result;
    }
    public function getshiftid($time)
    {

        $config = [
            'name' => 'main_time_table',
            'primary' => 'id'
        ];
        Zend_Debug::dump($time);
        $timetable = new Zend_Db_Table($config);
        $data = $timetable->select()
            ->setIntegrityCheck(false)
            ->from($config['name'], 'id')
            ->where('time = ?', $time);
        // die($data);
        return $timetable->fetchRow($data)->toArray();
    }
    public function getshiftbyid($id)
    {
        $config = [
            'name' => 'main_time_table',
            'primary' => 'time_id'
        ];
        $timetable = new Zend_Db_Table($config);
        $data = $timetable->select()
            ->setIntegrityCheck(false)
            ->from($config['name'])
            ->where('time_id = ?', $id);

        return $timetable->fetchRow($data)->toArray();
    }

    public function getSingleAttendance($emp_id, $shift_id, $datetime)
    {
        $config = [
            'name' => 'main_attendance',
            'primary' => 'id'
        ];

        $table = new Zend_Db_Table($config);
        $query = $table->select()
            ->setIntegrityCheck(false)
            ->from(['att' => 'main_attendance'], 'att.*')
            ->where('att.datetime="' . $datetime . '" AND att.emp_id=' . $emp_id . ' AND att.shift_id = ' . $shift_id);
        //  if($emp_id== "11"){
        //      die($query);
        //  }  
        //Zend_Debug::dump($this->fetchRow($query)->toArray());
        return $table->fetchRow($query)->toArray();
    }
    public function saveandupdatemissinghour($data,$where)
    {
        $config = [
            'name'=>'main_missed_hours',
            'primary'=>'missed_hours_id'
        ];
        $missedHtable = new Zend_Db_Table($config);
        if ($where != '') {
            $missedHtable->update($data,$where);
        }
        else{
            $missedHtable->insert($data);
        }
    }
    public function findschedule($empid,$day,$date)
    {
        $config = [
            'name'=>'main_emp_schedule',
            'primary'=>'id'
        ];
        $schtable = new Zend_Db_Table($config);
        $schres = $schtable->select()
                           ->setIntegrityCheck(false)
                           ->from(['esh'=>'main_emp_schedule'])
                           ->joinInner(['ms'=>'main_schedule'],'esh.sch_id = ms.sch_id AND ms.isactive=1')
                           ->joinInner(['msw'=>'main_sch_workday'],'(ms.sch_id = msw.sch_id AND msw.isactive=1)')
                           ->joinInner(['mws'=>'main_work_shift'],'(msw.sch_workday_id=mws.workday_id AND mws.isactive=1 )')
                           ->joinInner(['mtt'=>'main_time_table'],'(mtt.time_id = mws.time_id AND mtt.isactive=1)')
                           ->where('esh.emp_id = '.$empid.' AND  msw.workday LIKE "'.$day.'%" AND msw.isactive = 1 AND ("'.$date.'" BETWEEN ms.start_date AND ms.end_date) ');
        
                      //die($schres);
                   /*  Zend_Debug::dump($schtable->fetchAll($schres)->toArray());
                    die(); */
                    return $schtable->fetchAll($schres)->toArray();

    }
    public function latedata($currentdate)
    {
        $config = [
            'name'=>'main_attendance',
            'primary'=>'id'
        ];
        $attendtable = new Zend_Db_Table($config);
        $data = $attendtable->select()
                            ->setIntegrityCheck(false)
                            ->from($config['name'],['emp_id','shift_id','shift_start','user_start','shift_end','user_end'])
                            ->where('(start_status = "Late" OR end_status = "Left Early") AND datetime = '."'".$currentdate."' AND start_status!='absent' AND end_status!='absent'");
            
        return $attendtable->fetchAll($data)->toArray();
    }
    public function latedataemp($currentdate,$empid)
    {
        $config = [
            'name'=>'main_attendance',
            'primary'=>'id'
        ];
        $attendtable = new Zend_Db_Table($config);
        $data = $attendtable->select()
                            ->setIntegrityCheck(false)
                            ->from($config['name'],['emp_id','shift_id','shift_start','user_start','shift_end','user_end'])
                            ->where('(start_status = "Late" OR end_status = "Left Early") AND datetime = '."'".$currentdate."' AND emp_id=".$empid." start_status!='absent' AND end_status!='absent'");
            
        return $attendtable->fetchAll($data)->toArray();
    }
    
    public function saveandupdatebm($data, $where)
    {
        $config = [
            'name' => 'bm_event',
            'primary' => 'bm_event_id'
        ];
        $bmtable = new Zend_Db_Table($config);
        if ($where != '') {

            $bmtable->update($data, $where);
        } else {
            $bmtable->insert($data);
        }
    }
    public function getoffshift($date)
    {
        $config = [
            'name' => 'main_attendance',
            'primary' => 'id'
        ];
        $takeattend = new Zend_Db_Table($config);
        $res = $takeattend->select()
            ->setIntegrityCheck(false)
            ->where('shift_id = 0 AND datetime = "' . $date . '"');
        $res = $takeattend->fetchAll($res)->toArray();
        return $res;
    }
    public function offshiftdata($empid, $date)
    {
        $config = [
            'name' => 'main_attendance',
            'primary' => 'id'
        ];
        $takeattend = new Zend_Db_Table($config);
        $res = $takeattend->select()
            ->setIntegrityCheck(false)
            ->where('shift_id = 0 AND emp_id = ' . $empid . ' AND datetime = "' . $date . '"');
        $res = $takeattend->fetchAll($res)->toArray();

        if (count($res) == 0) {
            return 'user_start';
        } else {
            return 'user_end';
        }
    }
    public function holydaydata($empid, $date)
    {
        $config = [
            'name' => 'main_attendance',
            'primary' => 'id'
        ];
        $takeattend = new Zend_Db_Table($config);
        $res = $takeattend->select()
            ->setIntegrityCheck(false)
            ->where('shift_id = -1 AND emp_id = ' . $empid . ' AND datetime = "' . $date . '"');
        $res = $takeattend->fetchAll($res)->toArray();

        if (count($res) == 0) {
            return 'user_start';
        } else {
            return 'user_end';
        }
    }
    public function saveandupdateattend($data)
    {
        $config = [
            'name' => 'main_attendance',
            'primary' => 'id'
        ];
        $takeattend = new Zend_Db_Table($config);
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "UPDATE 
        main_attendance
    SET
        user_end = '" . $data['user_end'] . "',
        end_status = '" . $data['end_status'] . "'
    WHERE
        emp_id = " . $data['emp_id'] . " AND datetime = '" . $data['datetime'] . "' AND shift_id = " . $data['shift_id'];

        
   
     
        
        $res = $db->query($query);
        $affectedrows = $res->rowCount();
        
        if ($affectedrows == 0) {

            $result = $takeattend->select()
                ->where("emp_id = " . $data['emp_id'] . " AND datetime = '" . $data['datetime'] . "' AND shift_id = " . $data['shift_id']);
               
            if (count($takeattend->fetchAll($result)->toArray()) == 0) {
                if ($data['end_status'] != 'absent' and $data['end_status'] != null and $data['end_status']!='Leave' and $data['end_status']!= 'Dayoff') {

                    $data['start_status'] = 'late';
                    $data['user_start'] = $data['user_end'];
                    $data['user_end'] = "00:00:00";
                    $data['end_status'] = "absent";
                }
                Zend_Debug::dump($data, 'tell me what you hold');
                Zend_Debug::dump($data);
                //die(json_encode($data));
                $takeattend->insert($data);
                
            }
        }
        // $x=$res->fetchAll();

    }

    public function getHolidayDates($emp_id, $current_date)
    {
        $config = [
            'name' => 'main_empholidays',
            'primary' => 'id'
        ];
        $table = new Zend_Db_Table($config);
        $hgroup = $table->select()
            ->setIntegrityCheck(false)
            ->from($config['name'], 'holiday_group_id')
            ->where('user_id = ?', $emp_id);

        $hgroupres = $table->fetchAll($hgroup)->toArray();
        if (count($hgroupres) != 0) {
            $hgjson = json_encode($hgroupres);
            $hg = str_replace(['[', '{', '}', ']', ':', '"', 'holiday_group_id'], '', $hgjson);
            $config = [
                'name' => 'main_holidaydates',
                'primary' => 'id'
            ];
            $table = new Zend_Db_Table($config);
            $holidays = $table->select()
                ->setIntegrityCheck(false)
                ->where('groupid IN (' . $hg . ") AND holidaydate LIKE ?", $current_date);
            return $table->fetchAll($holidays)->toArray();
        } else {
            return [];
        }
    }

    public function getParticularAttend($shiftID, $userID, $date)
    {
        $config = [
            'name' => 'main_attendance',
            'primary' => 'id'
        ];
        $table = new Zend_Db_Table($config);

        $isAttend = $table->select()
            ->setIntegrityCheck(false)
            ->from(['att' => 'main_attendance'], 'att.id')
            ->where('att.emp_id = ' . $userID . ' AND att.shift_id = ' . $shiftID . ' AND att.datetime ="' . $date . '"');
       // die($isAttend);
        return $table->fetchAll($isAttend)->toArray();
    }

    public function getAttendByDate($date)
    {
        $config = [
            'name' => 'main_attendance',
            'primary' => 'id'
        ];
        $table = new Zend_Db_Table($config);
        $query = $table->select()
            ->setIntegrityCheck(false)
            ->from(['att' => 'main_attendance'], 'att.*')
            ->where('att.datetime = "' . $date . '"  AND att.start_status NOT IN ("absent","Leave") AND att.end_status NOT IN("absent","Leave")');
        return $this->fetchAll($query)->toArray();
    }
    public function getAttendByDateemp($date,$empid)
    {
        $config = [
            'name' => 'main_attendance',
            'primary' => 'id'
        ];
        $table = new Zend_Db_Table($config);
        $query = $table->select()
            ->setIntegrityCheck(false)
            ->from(['att' => 'main_attendance'], 'att.*')
            ->where('att.datetime = "' . $date . '" AND  att.emp_id = "' . $empid . '"  AND att.start_status NOT IN ("absent","Leave") AND att.end_status NOT IN("absent","Leave")');
        return $this->fetchAll($query)->toArray();
    }
}