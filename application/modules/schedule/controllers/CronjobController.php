<?php

//use function PHPSTORM_META\type;

class Schedule_CronjobController extends Zend_Controller_Action
{
    public function init()
    {
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function indexAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        $date = new Zend_Date();

        $model = new Default_Model_Cronstatus();

        $activecon = $model->getActiveCron('General');
        $attendance_model = new Schedule_Model_attendance();
        $updatebm = array();
        $empids = $attendance_model->getempids();
        //die(json_encode($empids));
        for ($dumy = 15; $dumy < 17; $dumy++) {
            $currentdate = '2021-03-' . $dumy;
            // $this->calculateot($currentdate,$attendance_model);
            //die("Intensional DIE Operation AT Line 28");
            //$currentdate = '2021-03-17';
            $datetimestamp = strtotime($currentdate);

            $ids = str_replace(array('{', '}', '[', ']', '"', '"', ':', 'user_id'), '', json_encode($empids));
            // die(json_encode($ids));
            $bmevent = $attendance_model->getBmdata($currentdate);
            //$bmeventemp = $attendance_model->getBmdataemp($ids);

           // die(json_encode($bmeventemp));
            $schdata = array();
            foreach ($bmevent as $key => $value) {
                //Zend_Debug::dump($value);

                $where = 'bm_event_id = ' . $value['bm_event_id'];
                $day = date('D', $value['datetime']);
                $schdata = $attendance_model->findschedule($value['user_id'], $day, $currentdate);
                //die(json_encode($schdata));
                //  die(json_encode($schdata));
                if (count($schdata) == 0) {
                    if ($this->isholiday($currentdate, $value['user_id'])) {
                        $updatebm['shift_id'] = -1;
                        $updatebm['status'] = 'Holiday';
                        $updatebm['shift_flag'] = 'Holiday';
                        $attendance_model->saveandupdatebm($updatebm, $where);
                    } else {
                        $updatebm['shift_id'] = 0;
                        $updatebm['status'] = 'Offshift';
                        $updatebm['shift_flag'] = 'Offshift';
                        $attendance_model->saveandupdatebm($updatebm, $where);
                    }
                } else if ($this->isholiday($currentdate, $value['user_id'])) {
                    $updatebm['shift_id'] = -1;
                    $updatebm['status'] = 'Holiday';
                    $updatebm['shift_flag'] = 'Holiday';
                    $attendance_model->saveandupdatebm($updatebm, $where);
                } else {


                    $closest = null;
                    $timestamp = strtotime(date('g:i a', $value['datetime'] - 1 * 3600));

                    // $timestamp = $value['datetime'] + 18000;

                    // if((int)date("H",$timestamp) > 12){
                    //      $timestamp=$timestamp-43200; 

                    // }


                    Zend_Debug::dump(date('g:i a', $timestamp), 'original time stamp');
                    $sc = array();
                    $timeshifts = array();
                    $networkhour = 0;
                    $schtype = [];
                    $shiftsworkhour = 0;
                    $userclick = 0;

                    foreach ($schdata as $key => $schval) {
                        $timeshifts['start' . $schval['time_id']] = $schval['start_time'];
                        $timeshifts['end' . $schval['time_id']] = $schval['end_time'];
                        $timeshifts['start_tol' . $schval['time_id']] = $schval['start_tolerance'];
                        $timeshifts['end_tol' . $schval['time_id']] = $schval['end_tolerance'];
                        $schtype[] = $schval['sch_type'];
                        /* if ($schval['sch_type'] =='Flexible') {
                            $networkhour=$schval['end_time']-$schval['start_time'];
                            Zend_Debug::dump($networkhour,'network');

                    } */
                    }

                    /* foreach ($schtype as $typekey => $typevalue) {
                        if ($typevalue == 'Flexible') {
                            $shiftsworkhour=$this->calculatenetwork($timeshifts);
                            //Zend_Debug::dump($shiftsworkhour);
                            $this->calculateuserwork($value);


                            
                        }

                        else {
 */
                    if ($this->isflexable($schtype)) {  
                        $statu='';
                        $total=''; 
                        $missed='';
                        $overtime='';
                        $closest = null;
                        $shiftid = 0;
                        $status = '';
                        $tolkey = '';
                        $currenttol = '';
                        foreach ($timeshifts as $key => $value0) {
                            $time = strtotime($value0);
                            if (strpos($key, 'start_tol') !== false or strpos($key, 'end_tol') !== false) {
                                $currentot = $key;
                                continue;
                            }
                            //Zend_Debug::dump(date('g:i:s a',$time));
                            if ($closest === null || abs($timestamp - $closest) > abs($time - $timestamp)) {
                                $closest = $time;

                                if (strpos($key, 'start') !== false) {
                                    $shiftid = trim(str_replace('start', ' ', $key));
                                    $status = 'start';
                                } else {
                                    $shiftid = trim(str_replace('end', ' ', $key));
                                    $status = 'end';
                                }
                            }
                        }
                        
                       // Zend_Debug::dump(date('Y-m-d g:i a', $closest), 'CLOSEST');
                        $caltime = $timestamp - $closest;
                        $realtime = $caltime - 1 * 3600;
                        $starttol = $timeshifts['start_tol' . $shiftid];
                        $endtol = $timeshifts['end_tol' . $shiftid];
                        $starttolsec = $starttol * 60;
                        $endtolinsec = $endtol * 60;
                       // Zend_Debug::dump($starttolsec, 'tolinsec');
                        $realtime = date('H:i:s', $realtime);
                        $realtime = explode(':', $realtime);
                        $timeinsec = $realtime[0] * 3600 + $realtime[1] * 60 + $realtime[2];
                        $sch_ids = $schdata[0]['sch_id'];
                        //Zend_Debug::dump($timeinsec, 'late time in sec');
                        $shiftsworkhour = $this->calculatenetwork($timeshifts);
                        $missed=$this->missedhoursflex($currentdate, $starttol, $endtol, $sch_ids,$value['user_id']);
                        $overtime=$this->calculateotflex($currentdate, $attendance_model,$value['user_id']);
                        $total=$missed -$overtime;
                        if($total>0){
                  $status='Late';
                        }
else {
$status='over';
}
Zend_Debug::dump($shiftsworkhour,'network');
Zend_Debug::dump($missed,'missed');
 Zend_Debug::dump($overtime,'overtime');
 Zend_Debug::dump($total,'total');
 Zend_Debug::dump($status,'status');                                  
    //$this->calculateuserwork($value['user_id'],$currentdate);
       
    
                        //die();
                    } else {
                        $closest = null;
                        $shiftid = 0;
                        $status = '';
                        $tolkey = '';
                        $currenttol = '';
                        foreach ($timeshifts as $key => $value0) {
                            $time = strtotime($value0);
                            if (strpos($key, 'start_tol') !== false or strpos($key, 'end_tol') !== false) {
                                $currentot = $key;
                                continue;
                            }
                            //Zend_Debug::dump(date('g:i:s a',$time));
                            if ($closest === null || abs($timestamp - $closest) > abs($time - $timestamp)) {
                                $closest = $time;

                                if (strpos($key, 'start') !== false) {
                                    $shiftid = trim(str_replace('start', ' ', $key));
                                    $status = 'start';
                                } else {
                                    $shiftid = trim(str_replace('end', ' ', $key));
                                    $status = 'end';
                                }
                            }
                        }
                        
                        Zend_Debug::dump(date('Y-m-d g:i a', $closest), 'CLOSEST');
                        $caltime = $timestamp - $closest;
                        $realtime = $caltime - 1 * 3600;
                        $starttol = $timeshifts['start_tol' . $shiftid];
                        $endtol = $timeshifts['end_tol' . $shiftid];
                        $starttolsec = $starttol * 60;
                        $endtolinsec = $endtol * 60;
                        Zend_Debug::dump($starttolsec, 'tolinsec');
                        $realtime = date('H:i:s', $realtime);
                        $realtime = explode(':', $realtime);
                        $timeinsec = $realtime[0] * 3600 + $realtime[1] * 60 + $realtime[2];
                        Zend_Debug::dump($timeinsec, 'late time in sec');
                        if ($status == 'start' and $caltime > 0 and $timeinsec > $starttolsec) {
                            $updatebm['shift_id'] = $shiftid;
                            $updatebm['status'] = $status;
                            $updatebm['shift_flag'] = 'Late';
                        } elseif ($status == 'end' and $caltime < 0 and $timeinsec < $endtolinsec) {
                            $updatebm['shift_id'] = $shiftid;
                            $updatebm['shift_flag'] = 'Left Early';
                            $updatebm['status'] = $status;
                        } elseif ($status == 'start' and $caltime < 0) {
                            $updatebm['shift_id'] = $shiftid;
                            $updatebm['status'] = $status;
                            $updatebm['shift_flag'] = 'Early';
                        } else {
                            $updatebm['shift_id'] = $shiftid;
                            $updatebm['status'] = $status;
                            $updatebm['shift_flag'] = 'On Time';
                        }
                        Zend_Debug::dump($updatebm);

                        //$this->missedhours('',$starttol,$endtol,$schdata[0]['sch_id']);
                        $sch_ids = $schdata[0]['sch_id'];
                        $attendance_model->saveandupdatebm($updatebm, $where);
                    }
                }
                /* }
                } */
            }


            if (count($bmevent) != 0) {
               /// $this->takeattend($currentdate);
            // $this->missedhours($currentdate, $starttol, $endtol, $sch_ids);
                //$this->summerizeAttendance(strtotime($currentdate));
                // $this->calculateot($currentdate, $attendance_model);
            } else {

                $allEmpID = $attendance_model->getempids();
                foreach ($allEmpID as $value) {
                    $attendance['emp_id'] = $value['user_id'];
                    $attendance['datetime'] = $currentdate;
                    $attendance['shift_id'] = "-2";
                    $attendance['user_start'] = "";
                    $attendance['shift_start'] = "";
                    $attendance['user_end'] = "";
                    $attendance['shift_end'] = "";
                    $attendance['end_status'] = "Dayoff";
                    $attendance['start_status'] = "Dayoff";
                    Zend_Debug::dump($attendance);
                    $attendance_model->saveandupdateattend($attendance);
                }
            }
        }
    }

    public function takeattend($date)
    {
        Zend_Debug::dump('lllll');
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        $date = $date;

        $bmmodel = new Schedule_Model_attendance();
        $bmdata = $bmmodel->getBmdata($date);

        foreach ($bmdata as $value) {

            $attendance = array();
            $attendance['emp_id'] = $value['user_id'];
            $attendance['shift_id'] = $value['shift_id'];
            Zend_Debug::dump(date('H:i:s', $value['datetime']));
            Zend_Debug::dump($value['status']);

            if ($value['shift_id'] > 0) {

                $shiftdata = $bmmodel->getshiftbyid($attendance['shift_id']);

                $attendance['shift_start'] = sapp_Global::change_time($shiftdata['start_time'], 'database');
                $attendance['shift_end'] = sapp_Global::change_time($shiftdata['end_time'], 'database');
            } elseif ($value['shift_id'] == 0 or $value['shift_id'] = -1) {
                $attendance['shift_start'] = '0';
                $attendance['shift_end'] = '0';
            }

            Zend_Debug::dump($value['status']);
            if ($value['status'] === 'start') {
                $attendance['user_start'] = sapp_Global::change_time(date('H:i:s ', $value['datetime'] - 1 * 3600), 'database');
                $attendance['start_status'] = $value['shift_flag'];
                $attendance['user_end'] = '0';
                $attendance['end_status'] = 'absent';
                //die();
                // $attendance['end_status'] = 'absent';
            } elseif ($value['status'] == 'Offshift') {
                Zend_Debug::dump($value, 'bullshit');
                $user_status = $bmmodel->offshiftdata($value['user_id'], $date);

                if ($user_status == 'user_start') {
                    $attendance[$user_status] = sapp_Global::change_time(date('H:i:s ', $value['datetime'] - 1 * 3600), 'database');
                    $attendance['start_status'] = 'Offshift';
                    $attendance['user_end'] = '00:00:00';
                } else {
                    $attendance[$user_status] = sapp_Global::change_time(date('H:i:s ', $value['datetime'] - 1 * 3600), 'database');
                    $attendance['end_status'] = 'Offshift';
                    Zend_Debug::dump($attendance, 'Attendance');
                }
                //die($user_status);
            } elseif ($value['status'] == 'Holiday') {
                $holyday_status = $bmmodel->holydaydata($value['user_id'], $date);
                if ($holyday_status == 'user_start') {
                    $attendance[$holyday_status] = sapp_Global::change_time(date('H:i:s ', $value['datetime'] - 1 * 3600), 'database');
                    $attendance['start_status'] = 'Holiday';
                    $attendance['user_end'] = '00:00:00';
                } else {
                    $attendance[$holyday_status] = sapp_Global::change_time(date('H:i:s ', $value['datetime'] - 1 * 3600), 'database');
                    $attendance['end_status'] = 'Holiday';
                    Zend_Debug::dump($attendance, 'Attendance');
                }
            } else {
                $attendance['user_end'] = sapp_Global::change_time(date('H:i:s ', $value['datetime'] - 1 * 3600), 'database');
                $attendance['end_status'] = $value['shift_flag'];
                //$attendance['start_status'] = 'absent';
            }

            $bmmodel->saveandupdateattend($attendance);
            //     //Zend_Debug::dump($attendance);
            //     $this->isleave($value['user_id'], '2021-06-20');
        }
    }
    public function missedhours($currentdate, $starttol, $endtol, $sch_id)
    {
        $attend_model = new Schedule_Model_attendance();
        $lateemp = $attend_model->latedata($currentdate);
        foreach ($lateemp as $key => $value) {
            Zend_Debug::dump($value['user_start'], 'user start');
            Zend_Debug::dump($value['user_end'], 'user end');
            Zend_Debug::dump($value['shift_end'], 'shift end');
            $temp_shift = $value['shift_id'];
            $shift_id = 0;
            if ($shift_id === null or $shift_id != $temp_shift) {
                $shift_id = $temp_shift;
                $starttol = $attend_model->getshiftbyid($temp_shift)['start_tolerance'];
                $endtol =  $attend_model->getshiftbyid($temp_shift)['end_tolerance'];
            }
            $shiftstart = strtotime($value['shift_start']);
            $shiftend = strtotime($value['shift_end']);
            $userstart = strtotime($value['user_start']);
            $userend = strtotime($value['user_end']);
            $timestartdiff = abs($shiftstart - $userstart);
            $timeenddiff = abs($userend - $shiftend);
            $starttimeinmin = $timestartdiff / 60;
            $endtimeinmin = $timeenddiff / 60;
            $starttimemissed = abs($starttimeinmin - $starttol);
            $endtimemissed = abs($endtimeinmin - $endtol);
            $totaltimemissed = $starttimemissed + $endtimemissed;
            Zend_Debug::dump($starttimemissed, 'start late');
            Zend_Debug::dump($endtimemissed, 'end');
            Zend_Debug::dump($totaltimemissed);
            $data = array(
                "emp_id" => $value["emp_id"],
                "sch_id" => $sch_id,
                "shift_id" => $shift_id,
                "submitted_date" => $currentdate,
                "missed_hour" => $totaltimemissed
            );
            //die(Zend_Debug::dump($data));
            $attend_model->saveandupdatemissinghour($data, '');
        }
    }
    public function missedhoursflex($currentdate, $starttol, $endtol, $sch_id,$empid)
    {
        $attend_model = new Schedule_Model_attendance();
        $lateemp = $attend_model->latedata($currentdate,$empid);
        foreach ($lateemp as $key => $value) {
            // Zend_Debug::dump($value['user_start'], 'user start');
            // Zend_Debug::dump($value['user_end'], 'user end');
            // Zend_Debug::dump($value['shift_end'], 'shift end');
            $temp_shift = $value['shift_id'];
            $shift_id = 0;
            if ($shift_id === null or $shift_id != $temp_shift) {
                $shift_id = $temp_shift;
                $starttol = $attend_model->getshiftbyid($temp_shift)['start_tolerance'];
                $endtol =  $attend_model->getshiftbyid($temp_shift)['end_tolerance'];
            }
            $shiftstart = strtotime($value['shift_start']);
            $shiftend = strtotime($value['shift_end']);
            $userstart = strtotime($value['user_start']);
            $userend = strtotime($value['user_end']);
            $timestartdiff = abs($shiftstart - $userstart);
            $timeenddiff = abs($userend - $shiftend);
            $starttimeinmin = $timestartdiff / 60;
            $endtimeinmin = $timeenddiff / 60;
            $starttimemissed = abs($starttimeinmin - $starttol);
            $endtimemissed = abs($endtimeinmin - $endtol);
            $totaltimemissed = $starttimemissed + $endtimemissed;
            // Zend_Debug::dump($starttimemissed, 'start late');
            // Zend_Debug::dump($endtimemissed, 'end late');
            // Zend_Debug::dump($totaltimemissed,'missed');
            return $totaltimemissed;
            // $data = array(
            //     "emp_id" => $value["emp_id"],
            //     "sch_id" => $sch_id,
            //     "shift_id" => $shift_id,
            //     "submitted_date" => $currentdate,
            //     "missed_hour" => $totaltimemissed
            // );
            //die(Zend_Debug::dump($data));
          //  $attend_model->saveandupdatemissinghour($data, '');
        }
    }

   


    public function isholiday($currentdate, $empid)
    {
        $holidaymodel = new Schedule_Model_attendance();
        $holidays = $holidaymodel->getHolidayDates($empid, $currentdate);
        Zend_Debug::dump($holidays);
        if (count($holidays) > 0) {
            return true;
        } else {
            return false;
        }
    }
    public function getschedule()
    {
        $model = new Schedule_Model_attendance();
        $schedule = $model->getschedulebyname();
        return $schedule;
    }
    // public function isleave($id, $currentdate)
    // {
    //     $model = new Schedule_Model_attendance();
    //     $data = $model->getleaveapproved(1);
    //     if (count($data) > 0) {
    //         foreach ($data as $value) {
    //             $from = strtotime($value['from_date']);
    //             $to = strtotime($value['to_date']);
    //             $currentdate = strtotime($currentdate);
    //             // Zend_Debug::dump($currentdate);
    //             if ($from <= $currentdate and $to >= $currentdate) {
    //                 //  Zend_Debug::dump('leave here');
    //             }
    //         }
    //     }
    // }




    public function summerizeAttendance($logtimestamp)
    {

        $day = date('D', $logtimestamp);
        $date = date('Y-m-d', $logtimestamp);
        $attendance_model = new Schedule_Model_attendance();
        /**
         * Get All Employees ID
         */
        $allEmpID = $attendance_model->getempids();

        foreach ($allEmpID as $empID) {
            /**
             * For each employee get all schedules
             */
            $empsechs = $attendance_model->getempsch($empID['user_id']);
            Zend_Debug::dump($empsechs, 'Employeeeeeee shcedule');

            //$empsech  = trim($empsech['sch_id'], ',');
            foreach ($empsechs as $empsech) {
                $empschdetails = $attendance_model->getempschdetail($day, $empsech['sch_id'], $date);
                Zend_Debug::dump($empschdetails, 'Employee today schedule ID ');

                if ($empschdetails != null) {
                    //$schedule = $attendance_model->getschedule($empTodaySchID['id']);
                    // $timeShifts = explode(',', trim($schedule['time'], ','));
                    // $shiftIDs = array();
                    // foreach ($timeShifts as $timeShift) {
                    //     $temp_shiftid = $attendance_model->getshiftid($timeShift);
                    //     array_push($shiftIDs, $temp_shiftid['id']);
                    // }
                    //die('here');
                    $date = date("Y-m-d", $logtimestamp);
                    foreach ($empschdetails as $empschdetail) {
                        Zend_Debug::dump($empschdetail, "User ID " . $empID['user_id']);
                        /**
                         * Check if employee attend for each shift of the schedules
                         */

                        $empShiftAttend = $attendance_model->getParticularAttend($empschdetail['shift_id'], $empID['user_id'], $date);
                        Zend_Debug::dump($empShiftAttend, 'Shift Attend');
                        if (count($empShiftAttend) > 0) {

                            Zend_Debug::dump($empschdetail['shift_id'], 'Presented for shift of');
                        } else {
                            Zend_Debug::dump('hererererererererer');
                            $approvedLeave = $attendance_model->getleaveapproved($empID['user_id'], $date);
                            //  Zend_Debug::dump($approvedLeave, 'Approved Leave');
                            // $from_date = new DateTime($approvedLeave[0]['from_date']);
                            // $today = new DateTime($date);
                            // $diff = $from_date->diff($today);
                            // $shiftdata = $attendance_model->getshiftbyid($shiftID);
                            //($diff->y <= 0  && $diff->m <= 0 && $diff->d <= 0) ||
                            if ($approvedLeave == NULL) {
                                $leaveData = array(
                                    'emp_id' => $empID['user_id'],
                                    'datetime' => $date,
                                    'shift_id' => $empschdetail['shift_id'],
                                    'shift_start' => $empschdetail['start_time'],
                                    'user_start' => '00:00:00',
                                    'start_status' => 'absent',
                                    'shift_end' => $empschdetail['end_time'],
                                    'user_end' => '00:00:00',
                                    'end_status' => 'absent'
                                );
                                $attendance_model->saveandupdateattend($leaveData);
                                //Zend_Debug::dump($leaveData, 'Absent');
                            } else {
                                $leaveData = array(
                                    'emp_id' => $empID['user_id'],
                                    'datetime' => $date,
                                    'shift_id' => $empschdetail['shift_id'],
                                    'shift_start' => $empschdetail['start_time'],
                                    'user_start' => '00:00:00',
                                    'start_status' => 'Leave',
                                    'shift_end' => $empschdetail['end_time'],
                                    'user_end' => '00:00:00',
                                    'end_status' => 'Leave',
                                    'leave_id' => $approvedLeave[0]['id']
                                );
                                //Zend_Debug::dump($leaveData, 'Leave');
                                $attendance_model->saveandupdateattend($leaveData);
                            }
                        }
                    }
                }
            }
        }
    }

    public function calculateot($date, $attendance_model)
    {
        $otmodel = new Schedule_Model_Overtime();

        $currentot = $otmodel->getOT($date);
        //$bmeventdata = $attendance_model->getBmdata($date);
        $todayAttend = $attendance_model->getAttendByDate($date);
        Zend_Debug::dump($todayAttend, "Today Attendance [Line 397]");
        Zend_Debug::dump($currentot, "Today OT [Line 398]");
        //die('as');
        foreach ($currentot as $key1 => $value) {
            echo var_dump($value);
            foreach ($todayAttend as $val) {
                $otsch = '';
                $otsch = $otmodel->getOTschedule($value['overtime_id']);
                //$particularAttendance = $attendance_model->getSingleAttendance($val['emp_id'], $val['shift_id'], $date);
                $particularAttendance = $val;
                Zend_Debug::dump($value['base'], 'mk');
                foreach ($otsch as $key => $otsch_value) {
                    //Zend_Debug::dump($otsch_value,'entering holiday');

                    if ($particularAttendance['start_status'] == 'Offshift') {
                        if ($value['base'] == 'OffShift') {
                            $this->offshift($particularAttendance, $otsch_value['OT_id'], $otsch_value['rate'], $value['autoapprove']);
                        }
                    } elseif ($particularAttendance['start_status'] == 'Holiday') {

                        if ($value['base'] == 'Holidays') {
                            Zend_Debug::dump('entering function');
                            $this->holydays($particularAttendance, $otsch_value['OT_id'], $otsch_value['rate'], $value['autoapprove']);
                        }
                    } else {
                        if ($value['base'] == 'Time') {
                            // $otsch = $otmodel->getOTschedule($value['overtime_id']);
                            Zend_Debug::dump($val['shift_id'], 'shifty-id');
                            Zend_Debug::dump($val['emp_id'], 'First User ID');
                            // $particularAttendance = $attendance_model->getSingleAttendance($val['user_id'], $val['shift_id'], $date);
                            // if($val['user_id']=="11"){
                            //     die(json_encode($particularAttendance));
                            // }

                            // $particularAttendance['user_start'] = DATE("H:i:s a", strtotime($particularAttendance['user_start']));
                            // $particularAttendance['user_end'] = DATE("H:i:s a", strtotime($particularAttendance['user_end']));
                            // Zend_Debug::dump($particularAttendance['user_start'] . '-' . $particularAttendance['user_end'], "Attend Time OF ID " . $particularAttendance['emp_id']);
                            //die($particularAttendance['user_start'].'---'. $particularAttendance['user_end']);
                            $US_OTS_diff = strtotime($particularAttendance['user_start']) - strtotime($otsch_value['start_time']);
                            $UE_OTE_diff = strtotime($particularAttendance['user_end']) - strtotime($otsch_value['end_time']);
                            $US_OTE_diff = strtotime($particularAttendance['user_start']) - strtotime($otsch_value['end_time']);
                            if ($US_OTE_diff >= 0) {
                                Zend_Debug::dump('No OT!!!!!');
                            } else {
                                if ($US_OTS_diff >= 0) {
                                    Zend_Debug::dump('User start time grater than OT start time');
                                    $app_start_time = date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_start']));
                                } else {
                                    Zend_Debug::dump('User start time less than OT start time');
                                    $app_start_time = date('Y-m-d H:i:s', strtotime($date . $otsch_value['start_time']));
                                }
                                if ($UE_OTE_diff <= 0) {
                                    Zend_Debug::dump('User end time less than OT end time');
                                    $app_end_time = date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_end']));
                                } else {
                                    Zend_Debug::dump('User end time grater than OT end time');
                                    $app_end_time = date('Y-m-d H:i:s', strtotime($date . $otsch_value['end_time']));
                                }
                            }
                            if (
                                $particularAttendance['start_status'] != 'absent' and $particularAttendance['end_status'] != 'absent'
                                && $particularAttendance['start_status'] != 'Leave' && $particularAttendance['end_status'] != 'Leave'
                            ) {
                                $approved = 'P';
                                if ((int)($value['autoapproved']) == 1) {
                                    $approved = 'Y';
                                }
                                $OT_app_data = array(
                                    'OT_id' => $otsch_value['OT_id'],
                                    'emp_id' => $particularAttendance['emp_id'],
                                    'submitted_date' => $date,
                                    'start_time' => $app_start_time,
                                    'end_time' => $app_end_time,
                                    'earned_time' => $this->getTime((strtotime($app_end_time) - strtotime($app_start_time))),
                                    'amount_due' => $otsch_value['rate'],
                                    'approved' => $approved
                                );

                                $otmodel->saveORupdateApproval($OT_app_data, '');
                                Zend_Debug::dump($OT_app_data, 'Overtime Approval Data');
                            }

                            // Zend_Debug::dump($val['emp_id'], 'Employee ID');



                            // Zend_Debug::dump($particularAttendance, 'Single Attendnce');
                            // Zend_Debug::dump($otsch_value['start_time'], 'OT Time Start');
                            // Zend_Debug::dump($otsch_value['end_time'], 'OT Time End');
                        } else if ($value['base'] == 'Period') {
                            // $otsch = '';
                            // $otsch = $otmodel->getOTschedule($value['overtime_id']);
                            // foreach ($otsch as $key => $otsch_value) {
                            $particularAttendance = $attendance_model->getSingleAttendance($val['emp_id'], $val['shift_id'], $date);
                            $attendance_date = date('Y-m-d H:i:s', strtotime($particularAttendance['datetime']));
                            $AD_OTSD_diff = strtotime($attendance_date) - strtotime($otsch_value['start_date']);
                            $AD_OTED_diff = strtotime($attendance_date) - strtotime($otsch_value['end_date']);
                            $US_OTS_diff = strtotime($particularAttendance['user_start']) - strtotime($otsch_value['start_time']);
                            $UE_OTE_diff = strtotime($particularAttendance['user_end']) - strtotime($otsch_value['end_time']);
                            $US_OTE_diff = strtotime($particularAttendance['user_start']) - strtotime($otsch_value['end_time']);
                            $attendance_date = DateTime::createFromFormat('Y-m-d', $attendance_date);
                            if ($AD_OTSD_diff <= 0 or $AD_OTED_diff >= 0) {
                                if ($US_OTE_diff >= 0) {
                                    Zend_Debug::dump('No OT!!!!!');
                                } else {
                                    if ($US_OTS_diff >= 0) {
                                        Zend_Debug::dump('User start time grater than OT start time');
                                        $app_start_time = date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_start']));
                                    } else {
                                        Zend_Debug::dump('User start time less than OT start time');
                                        $app_start_time = date('Y-m-d H:i:s', strtotime($date . $otsch_value['start_time']));
                                    }
                                    if ($UE_OTE_diff <= 0) {
                                        Zend_Debug::dump('User end time less than OT end time');
                                        $app_end_time = date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_end']));
                                    } else {
                                        Zend_Debug::dump('User end time grater than OT end time');
                                        $app_end_time = date('Y-m-d H:i:s', strtotime($date . $otsch_value['end_time']));
                                    }
                                }
                                if (
                                    $particularAttendance['start_status'] != 'absent' and $particularAttendance['end_status'] != 'absent'
                                    && $particularAttendance['start_status'] != 'Leave' && $particularAttendance['end_status'] != 'Leave'
                                ) {
                                    $approved = 'P';
                                    if ((int)($value['autoapproved']) == 1) {
                                        $approved = 'Y';
                                    }
                                    $OT_app_data = array(
                                        'OT_id' => $otsch_value['OT_id'],
                                        'emp_id' => $particularAttendance['emp_id'],
                                        'submitted_date' => $date,
                                        'start_time' => $app_start_time,
                                        'end_time' => $app_end_time,
                                        'earned_time' => $this->getTime((strtotime($app_end_time) - strtotime($app_start_time))),
                                        'amount_due' => $otsch_value['rate'],
                                        'approved' => $approved
                                    );
                                    Zend_Debug::dump($OT_app_data, 'OTOT');
                                    $otmodel->saveORupdateApproval($OT_app_data, '');
                                }
                            }
                            //die();
                            //}
                    
                        } else if ($value['base'] == 'Day') {
                            // $otsch = '';
                            // $otsch = $otmodel->getOTschedule($value['overtime_id']);
                            //foreach ($otsch as $key => $otsch_value) {
                            $log_day = date('D', $date);
                            $ot_day = substr($otsch_value['day'], 0, 3);

                            if ($log_day == $ot_day) {
                                $particularAttendance = $attendance_model->getSingleAttendance($val['emp_id'], $val['shift_id'], $date);
                                $user_start = strtotime($particularAttendance['user_start']);
                                $user_end = strtotime($particularAttendance['user_end']);
                                $earned_seconds = $user_end - $user_start;
                                $earned_minutes = $this->getTime($earned_seconds);
                                if (
                                    $particularAttendance['start_status'] != 'absent' and $particularAttendance['end_status'] != 'absent'
                                    && $particularAttendance['start_status'] != 'Leave' && $particularAttendance['end_status'] != 'Leave'
                                ) {
                                    $approved = 'P';
                                    if ((int)($value['autoapproved']) == 1) {
                                        $approved = 'Y';
                                    }
                                    $OT_app_data = array(
                                        'OT_id' => $otsch_value['OT_id'],
                                        'emp_id' => $particularAttendance['emp_id'],
                                        'submitted_date' => $date,
                                        'start_time' => date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_start'])),
                                        'end_time' => date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_end'])),
                                        'earned_time' => $earned_minutes,
                                        'amount_due' => $otsch_value['rate'],
                                        'approved' => $approved
                                    );
                                    Zend_Debug::dump($OT_app_data, 'OT data Day based');
                                    $otmodel->saveORupdateApproval($OT_app_data, '');
                                }
                            } else {
                                //Zend_Debug::dump('log day ' . $log_day . '\not day ' . $ot_day);
                            }
                            //}
                        } else if ($value['base'] == 'Date') {
                            //foreach ($otsch as $key => $otsch_value) {
                            $date_diff = strtotime($date) - strtotime($otsch_value['start_date']);
                            if ($date_diff == 0) {
                                $particularAttendance = $attendance_model->getSingleAttendance($val['emp_id'], $val['shift_id'], $date);
                                $user_start = strtotime($particularAttendance['user_start']);
                                $user_end = strtotime($particularAttendance['user_end']);
                                if (
                                    $particularAttendance['start_status'] != 'absent' && $particularAttendance['end_status'] != 'absent'
                                    && $particularAttendance['start_status'] != 'Leave' && $particularAttendance['end_status'] != 'Leave'
                                ) {
                                    $approved = 'P';
                                    if ((int)($value['autoapproved']) == 1) {
                                        $approved = 'Y';
                                    }
                                    $earned_seconds = $user_end - $user_start;
                                    $earned_minutes = $this->getTime($earned_seconds);
                                    $OT_app_data = array(
                                        'OT_id' => $otsch_value['OT_id'],
                                        'emp_id' => $particularAttendance['emp_id'],
                                        'submitted_date' => $date,
                                        'start_time' => date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_start'])),
                                        'end_time' => date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_end'])),
                                        'earned_time' => $earned_minutes,
                                        'amount_due' => $otsch_value['rate'],
                                        'approved' => $approved
                                    );
                                }
                                Zend_Debug::dump($OT_app_data, 'Date Based OT Data');
                                $otmodel->saveORupdateApproval($OT_app_data, '');
                            } else {
                                Zend_Debug::dump($date, 'No date based OT schdule');
                            }
                            //}
                        }
                    }
                }
            }
        }
    }

    public function calculateotflex($date, $attendance_model,$empid)
    {
        $otmodel = new Schedule_Model_Overtime();

        $currentot = $otmodel->getOT($date);
        //$bmeventdata = $attendance_model->getBmdata($date);
        $todayAttend = $attendance_model->getAttendByDateemp($date,$empid);
        //Zend_Debug::dump($todayAttend, "Today Attendance [Line 397]");
        //Zend_Debug::dump($currentot, "Today OT [Line 398]");
        //die('as');
        foreach ($currentot as $key1 => $value) {
            //echo var_dump($value);
            foreach ($todayAttend as $val) {
                $otsch = '';
                $otsch = $otmodel->getOTschedule($value['overtime_id']);
                //$particularAttendance = $attendance_model->getSingleAttendance($val['emp_id'], $val['shift_id'], $date);
                $particularAttendance = $val;
               // Zend_Debug::dump($value['base'], 'mk');
                foreach ($otsch as $key => $otsch_value) {
                    //Zend_Debug::dump($otsch_value,'entering holiday');

                    if ($particularAttendance['start_status'] == 'Offshift') {
                        if ($value['base'] == 'OffShift') {
                            $this->offshift($particularAttendance, $otsch_value['OT_id'], $otsch_value['rate'], $value['autoapprove']);
                        }
                    } elseif ($particularAttendance['start_status'] == 'Holiday') {

                        if ($value['base'] == 'Holidays') {
                           // Zend_Debug::dump('entering function');
                            $this->holydays($particularAttendance, $otsch_value['OT_id'], $otsch_value['rate'], $value['autoapprove']);
                        }
                    } else {

                        if ($value['base'] == 'Time') {
                            
                            // $otsch = $otmodel->getOTschedule($value['overtime_id']);

                           // Zend_Debug::dump($val['shift_id'], 'shifty-id');
                            //Zend_Debug::dump($val['emp_id'], 'First User ID');
                            // $particularAttendance = $attendance_model->getSingleAttendance($val['user_id'], $val['shift_id'], $date);
                            // if($val['user_id']=="11"){
                            //     die(json_encode($particularAttendance));
                            // }

                            // $particularAttendance['user_start'] = DATE("H:i:s a", strtotime($particularAttendance['user_start']));
                            // $particularAttendance['user_end'] = DATE("H:i:s a", strtotime($particularAttendance['user_end']));

                            // Zend_Debug::dump($particularAttendance['user_start'] . '-' . $particularAttendance['user_end'], "Attend Time OF ID " . $particularAttendance['emp_id']);
                            //die($particularAttendance['user_start'].'---'. $particularAttendance['user_end']);
                            $US_OTS_diff = strtotime($particularAttendance['user_start']) - strtotime($otsch_value['start_time']);
                            $UE_OTE_diff = strtotime($particularAttendance['user_end']) - strtotime($otsch_value['end_time']);
                            $US_OTE_diff = strtotime($particularAttendance['user_start']) - strtotime($otsch_value['end_time']);
                            if ($US_OTE_diff >= 0) {
                                //Zend_Debug::dump('No OT!!!!!');
                            } else {
                                if ($US_OTS_diff >= 0) {
                                   // Zend_Debug::dump('User start time grater than OT start time');
                                    $app_start_time = date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_start']));
                                } else {
                                    //Zend_Debug::dump('User start time less than OT start time');
                                    $app_start_time = date('Y-m-d H:i:s', strtotime($date . $otsch_value['start_time']));
                                }
                                if ($UE_OTE_diff <= 0) {
                                   // Zend_Debug::dump('User end time less than OT end time');
                                    $app_end_time = date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_end']));
                                } else {
                                   // Zend_Debug::dump('User end time grater than OT end time');
                                    $app_end_time = date('Y-m-d H:i:s', strtotime($date . $otsch_value['end_time']));
                                }
                            }
                            if (
                                $particularAttendance['start_status'] != 'absent' and $particularAttendance['end_status'] != 'absent'
                                && $particularAttendance['start_status'] != 'Leave' && $particularAttendance['end_status'] != 'Leave'
                            ) {
                                $approved = 'P';
                                if ((int)($value['autoapproved']) == 1) {
                                    $approved = 'Y';
                                }
                                $OT_app_data = array(
                                    'OT_id' => $otsch_value['OT_id'],
                                    'emp_id' => $particularAttendance['emp_id'],
                                    'submitted_date' => $date,
                                    'start_time' => $app_start_time,
                                    'end_time' => $app_end_time,
                                    'earned_time' => $this->getTime((strtotime($app_end_time) - strtotime($app_start_time))),
                                    'amount_due' => $otsch_value['rate'],
                                    'approved' => $approved
                                );

                                $otmodel->saveORupdateApproval($OT_app_data, '');
                            //Zend_Debug::dump($OT_app_data, 'Overtime Approval Data');
                            }

                            // Zend_Debug::dump($val['emp_id'], 'Employee ID');
                            return $this->getTime((strtotime($app_end_time) - strtotime($app_start_time)));
                            // Zend_Debug::dump($particularAttendance, 'Single Attendnce');
                            // Zend_Debug::dump($otsch_value['start_time'], 'OT Time Start');
                            // Zend_Debug::dump($otsch_value['end_time'], 'OT Time End');
                        } else if ($value['base'] == 'Period') {
                            // $otsch = '';
                            // $otsch = $otmodel->getOTschedule($value['overtime_id']);
                            // foreach ($otsch as $key => $otsch_value) {
                            $particularAttendance = $attendance_model->getSingleAttendance($val['emp_id'], $val['shift_id'], $date);
                            $attendance_date = date('Y-m-d H:i:s', strtotime($particularAttendance['datetime']));
                            $AD_OTSD_diff = strtotime($attendance_date) - strtotime($otsch_value['start_date']);
                            $AD_OTED_diff = strtotime($attendance_date) - strtotime($otsch_value['end_date']);
                            $US_OTS_diff = strtotime($particularAttendance['user_start']) - strtotime($otsch_value['start_time']);
                            $UE_OTE_diff = strtotime($particularAttendance['user_end']) - strtotime($otsch_value['end_time']);
                            $US_OTE_diff = strtotime($particularAttendance['user_start']) - strtotime($otsch_value['end_time']);
                            $attendance_date = DateTime::createFromFormat('Y-m-d', $attendance_date);
                            if ($AD_OTSD_diff <= 0 or $AD_OTED_diff >= 0) {
                                if ($US_OTE_diff >= 0) {
                                    Zend_Debug::dump('No OT!!!!!');
                                } else {
                                    if ($US_OTS_diff >= 0) {
                                        //Zend_Debug::dump('User start time grater than OT start time');
                                        $app_start_time = date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_start']));
                                    } else {
                                        //Zend_Debug::dump('User start time less than OT start time');
                                        $app_start_time = date('Y-m-d H:i:s', strtotime($date . $otsch_value['start_time']));
                                    }
                                    if ($UE_OTE_diff <= 0) {
                                      //  Zend_Debug::dump('User end time less than OT end time');
                                        $app_end_time = date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_end']));
                                    } else {
                                       // Zend_Debug::dump('User end time grater than OT end time');
                                        $app_end_time = date('Y-m-d H:i:s', strtotime($date . $otsch_value['end_time']));
                                    }
                                }
                                if (
                                    $particularAttendance['start_status'] != 'absent' and $particularAttendance['end_status'] != 'absent'
                                    && $particularAttendance['start_status'] != 'Leave' && $particularAttendance['end_status'] != 'Leave'
                                ) {
                                    $approved = 'P';
                                    if ((int)($value['autoapproved']) == 1) {
                                        $approved = 'Y';
                                    }
                                    $OT_app_data = array(
                                        'OT_id' => $otsch_value['OT_id'],
                                        'emp_id' => $particularAttendance['emp_id'],
                                        'submitted_date' => $date,
                                        'start_time' => $app_start_time,
                                        'end_time' => $app_end_time,
                                        'earned_time' => $this->getTime((strtotime($app_end_time) - strtotime($app_start_time))),
                                        'amount_due' => $otsch_value['rate'],
                                        'approved' => $approved
                                    );
                                    //Zend_Debug::dump($OT_app_data, 'OTOT');
                                    $otmodel->saveORupdateApproval($OT_app_data, '');
                                }
                            }
    return $this->getTime((strtotime($app_end_time) - strtotime($app_start_time)));

                            //die();
                            //}
                        } else if ($value['base'] == 'Day') {
                            // $otsch = '';
                            // $otsch = $otmodel->getOTschedule($value['overtime_id']);
                            //foreach ($otsch as $key => $otsch_value) {
                            $log_day = date('D', $date);
                            $ot_day = substr($otsch_value['day'], 0, 3);

                            if ($log_day == $ot_day) {
                                $particularAttendance = $attendance_model->getSingleAttendance($val['emp_id'], $val['shift_id'], $date);
                                $user_start = strtotime($particularAttendance['user_start']);
                                $user_end = strtotime($particularAttendance['user_end']);
                                $earned_seconds = $user_end - $user_start;
                                $earned_minutes = $this->getTime($earned_seconds);
                                if (
                                    $particularAttendance['start_status'] != 'absent' and $particularAttendance['end_status'] != 'absent'
                                    && $particularAttendance['start_status'] != 'Leave' && $particularAttendance['end_status'] != 'Leave'
                                ) {
                                    $approved = 'P';
                                    if ((int)($value['autoapproved']) == 1) {
                                        $approved = 'Y';
                                    }
                                    $OT_app_data = array(
                                        'OT_id' => $otsch_value['OT_id'],
                                        'emp_id' => $particularAttendance['emp_id'],
                                        'submitted_date' => $date,
                                        'start_time' => date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_start'])),
                                        'end_time' => date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_end'])),
                                        'earned_time' => $earned_minutes,
                                        'amount_due' => $otsch_value['rate'],
                                        'approved' => $approved
                                    );
                                   // Zend_Debug::dump($OT_app_data, 'OT data Day based');
                                    $otmodel->saveORupdateApproval($OT_app_data, '');
                                }
                            } else {
                                //Zend_Debug::dump('log day ' . $log_day . '\not day ' . $ot_day);
                            }
                        return $earned_minutes;
                            //}
                        } else if ($value['base'] == 'Date') {
                            //foreach ($otsch as $key => $otsch_value) {
                            $date_diff = strtotime($date) - strtotime($otsch_value['start_date']);
                            if ($date_diff == 0) {
                                $particularAttendance = $attendance_model->getSingleAttendance($val['emp_id'], $val['shift_id'], $date);
                                $user_start = strtotime($particularAttendance['user_start']);
                                $user_end = strtotime($particularAttendance['user_end']);
                                if (
                                    $particularAttendance['start_status'] != 'absent' && $particularAttendance['end_status'] != 'absent'
                                    && $particularAttendance['start_status'] != 'Leave' && $particularAttendance['end_status'] != 'Leave'
                                ) {
                                    $approved = 'P';
                                    if ((int)($value['autoapproved']) == 1) {
                                        $approved = 'Y';
                                    }
                                    $earned_seconds = $user_end - $user_start;
                                    $earned_minutes = $this->getTime($earned_seconds);
                                    $OT_app_data = array(
                                        'OT_id' => $otsch_value['OT_id'],
                                        'emp_id' => $particularAttendance['emp_id'],
                                        'submitted_date' => $date,
                                        'start_time' => date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_start'])),
                                        'end_time' => date('Y-m-d H:i:s', strtotime($date . $particularAttendance['user_end'])),
                                        'earned_time' => $earned_minutes,
                                        'amount_due' => $otsch_value['rate'],
                                        'approved' => $approved
                                    );
                                }
                               // Zend_Debug::dump($OT_app_data, 'Date Based OT Data');
                                $otmodel->saveORupdateApproval($OT_app_data, '');
                            } else {
                               // Zend_Debug::dump($date, 'No date based OT schdule');
                            }
                            //}
                            return $earned_minutes;
                        }
                    }
                }
            }
        }
    }


    public function offshift($data, $otid, $rate, $autoapp)
    {
        $otmodel = new Schedule_Model_Overtime();
        Zend_Debug::dump('here');
        $app_start_time =  date('Y-m-d H:i:s', strtotime($data['datetime'] . $data['user_start']));
        $app_end_time = date('Y-m-d H:i:s', strtotime($data['datetime'] . $data['user_end']));
        $earnedsec = strtotime($app_end_time) - strtotime($app_start_time);
        $earned_minutes = $this->getTime($earnedsec);
        $approved = 'P';
        if ((int)$autoapp == 1) {
            $approved = 'Y';
        }
        $OT_app_data = array(
            'OT_id' => $otid,
            'emp_id' => $data['emp_id'],
            'submitted_date' => $data['datetime'],
            'start_time' => date('Y-m-d H:i:s', strtotime($data['datetime'] . $data['user_start'])),
            'end_time' => date('Y-m-d H:i:s', strtotime($data['datetime'] . $data['user_end'])),
            'earned_time' => $earned_minutes,
            'amount_due' => $rate,
            'approved' => $approved
        );
        Zend_Debug::dump($OT_app_data, 'oof');
        $otmodel->saveORupdateApproval($OT_app_data, '');
    }

    public function holydays($data, $otid, $rate, $autoapp)
    {
        Zend_Debug::dump('holiday function');
        $otmodel = new Schedule_Model_Overtime();
        $app_start_time =  date('Y-m-d H:i:s', strtotime($data['datetime'] . $data['user_start']));
        $app_end_time = date('Y-m-d H:i:s', strtotime($data['datetime'] . $data['user_end']));
        $earnedsec = strtotime($app_end_time) - strtotime($app_start_time);
        $earned_minutes = $this->getTime($earnedsec);
        $approved = 'P';
        if ((int)$autoapp == 1) {
            $approved = 'Y';
        }
        $OT_app_data = array(
            'OT_id' => $otid,
            'emp_id' => $data['emp_id'],
            'submitted_date' => $data['datetime'],
            'start_time' => date('Y-m-d H:i:s', strtotime($data['datetime'] . $data['user_start'])),
            'end_time' => date('Y-m-d H:i:s', strtotime($data['datetime'] . $data['user_end'])),
            'earned_time' => $earned_minutes,
            'amount_due' => $rate,
            'approved' => $approved
        );
        Zend_Debug::dump($data, 'holiday');
        $otmodel->saveORupdateApproval($OT_app_data, '');

    }
    public function getTime($timeStamp)
    {
        $sec = $timeStamp % 60;
        $whole_minute = ($timeStamp - $sec) / 60;
        $minute = $whole_minute % 60;

        $hour = ($whole_minute - $minute) / 60;
        if ($sec >= 30) {
            $minute++;
            $whole_minute++;
        }
        return $whole_minute;
    }

    public function calculatenetwork($timeshifts)
    {
        $sumworkstart = 0;
        $sumworkstart_tol = 0;
        $sumworkend = 0;
        $sumworkend_tol = 0;
        $total_tol = 0;
        $total_work = 0;
        $network = 0;
        $networkminutes = 0;
        foreach ($timeshifts as $shifts => $shift) {
            //$shift['start'];
            if (strpos($shifts, 'start') !== false) {

                $sumworkstart = $sumworkstart + strtotime($timeshifts[$shifts]);
            }
            if (strpos($shifts, 'start_tol') !== false) {

                $sumworkstart_tol = $sumworkstart_tol + strtotime($timeshifts[$shifts]);
            }
            if (strpos($shifts, 'end') !== false) {

                $sumworkend = $sumworkend + strtotime($timeshifts[$shifts]);
            }
            if (strpos($shifts, 'end_tol') !== false) {

                $sumworkend_tol = $sumworkend_tol + strtotime($timeshifts[$shifts]);
            }
        }
        //Zend_Debug::dump($timeshifts);
        $total_tol = $sumworkend_tol + $sumworkstart_tol;
        $total_work = $sumworkend - $sumworkstart;
        $network = $total_work - $total_tol;
        $networkminutes = $this->getTime($network);

        //Zend_Debug::dump($networkminutes);
        // Zend_Debug::dump($sumworkstart);
        // Zend_Debug::dump($sumworkend);
        return $networkminutes;
    }

    public function isflexable($schtype)
    {
        foreach ($schtype as $typekey => $typevalue) {

            if ($typevalue == 'Flexible') {
                return true;
            } else {
                return false;
            }
        }
    }

    public function calculateuserwork($values,$currentdate)
    { 
        $attendance_model = new Schedule_Model_attendance();

$bmeventemp = $attendance_model->getBmdataemp($values,$currentdate);
        Zend_Debug::dump($bmeventemp);
        //         $sumworkstart = 0;
        //         $sumworkstart_tol = 0;
        //         $sumworkend = 0;
        //         $sumworkend_tol = 0;
        //         $total_tol = 0;
        //         $total_work = 0;
        //         $network = 0;
        // $networkminutes=0;


        //         foreach ($timeshifts as $shifts => $shift) {
        //             //$shift['start'];
        //             if (strpos($shifts, 'start') !== false) {

        //                 $sumworkstart = $sumworkstart + strtotime($timeshifts[$shifts]);
        //             }
        //             if (strpos($shifts, 'start_tol') !== false) {

        //                 $sumworkstart_tol = $sumworkstart_tol + strtotime($timeshifts[$shifts]);
        //             }
        //             if (strpos($shifts, 'end') !== false) {

        //                 $sumworkend = $sumworkend + strtotime($timeshifts[$shifts]);
        //             }
        //             if (strpos($shifts, 'end_tol') !== false) {

        //                 $sumworkend_tol = $sumworkend_tol + strtotime($timeshifts[$shifts]);
        //             }
        //         }

        //         //Zend_Debug::dump($timeshifts);
        // $total_tol=$sumworkend_tol+ $sumworkstart_tol;
        // $total_work=$sumworkend-$sumworkstart;
        // $network=$total_work-$total_tol;
        //     $networkminutes=$this->getTime($network);

        //         //Zend_Debug::dump($networkminutes);
        //         // Zend_Debug::dump($sumworkstart);
        //         // Zend_Debug::dump($sumworkend);
        // return $networkminutes;
    }
}




/* SELECT
    att.datetime,
    sch.schedule_name,
    shi.name AS Shift,
    empsch.schedule,
    emps.userfullname,
    att.user_start,
    att.user_end
FROM
    main_employees AS em
INNER JOIN employees_summary AS emps
ON
    emps.user_id = em.user_id
LEFT JOIN main_attendance AS att
ON
    att.emp_id = em.user_id
LEFT JOIN main_emp_schedule empsch ON
    empsch.emp_id = em.user_id
LEFT JOIN main_schedule AS sch
ON
    sch.id IN(empsch.schedule)
LEFT JOIN main_time_table AS shi
ON
    shi.id = att.shift_id
    
   */