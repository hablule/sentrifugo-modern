<?php
class Schedule_Model_Attendancereport extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_attendance';
    protected $_primary = 'id';
    public function getLogData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = '1';
        if ($searchQuery) {
            $where .= " AND " . $searchQuery;
        }

        $roleData =  $this->select()
            ->setIntegrityCheck(false)
            ->from(['att' => 'main_attendance'], [
                'datetime' =>  'DATE_FORMAT(att.datetime,"'.DATEFORMAT_MYSQL.'")', 'shift_name' => 'if(tm.name !="",tm.name,att.start_status)', 'shift_start' => 'att.shift_start', 'user_start' => 'att.user_start',
                'start_status' => 'att.start_status', 'shift_end' => 'att.shift_end', 'user_end' => 'att.user_end', 'end_status' => 'att.end_status',
                'leave_type' => 'if(lt.leavetype !=""  ,lt.leavetype,"-")', 'userfullname' => 'em.userfullname'
            ])
            ->joinLeft(['tm' => 'main_time_table'], 'tm.time_id=att.shift_id')
            ->joinLeft(['lv' => 'main_leaverequest'], 'lv.id = att.leave_id')
            ->joinLeft(['lt' => 'main_employeeleavetypes'], 'lv.leavetypeid = lt.id')
            ->joinInner(['em' => 'main_employees'], 'att.emp_id = em.id')
            // ->joinInner(array('usr' => 'main_employees'), 'em.id = usr.id')
            ->where($where)
            ->order("$by $sort ")
            ->limitPage($pageNo, $perPage);
        return $roleData;
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
                if ($key == 'datetime')
                
                    $searchQuery .= "att. " . $key . " like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                         else if ($key == 'id')
                             $searchQuery .= " att." . $key . " like '%" . $val . "%' AND ";
                     else if ($key == 'shift_id')
                         $searchQuery .= " att.shift_id like '%" . $val . "%' AND ";
                     else if ($key == 'shift_name') {
                        $searchQuery .= " tm.name like '%" . $val . "%' AND ";
                    } else if ($key == 'userfullname') {
                            $searchQuery .= " em.userfullname like '%" . $val . "%' AND ";
                       } 
                else
           $searchQuery .= " att." . $key . " like '%" . $val . "%' AND ";
        $searchArray[$key] = $val;
            }

            $searchQuery = rtrim($searchQuery, " AND");
           // die(json_encode($searchQuery));
        }
       
        $formgrid = '';  

        $objName = 'attendancereport';
        $objId = 'attendancereport';
        $tableFields = [
            //'action' => 'Action',
            'datetime' => 'Attendance Date',
            'userfullname' => 'Employee Name',
            'shift_name' => 'Shift',
            'shift_start' => 'Shift Start',
            'user_start' => 'User Start',
            'start_status' => 'Start Status',
            'shift_end' => 'Shift End',
            'user_end' => 'User End',
            'end_status' => 'End Status',
            'leave_type' => 'Leave Type',
        ];

        $tablecontent = $this->getLogData($sort, $by, $pageNo, $perPage, $searchQuery);
        $group_model = new Schedule_Model_Timetable();
        $group_data = $group_model->getallshifts();

        $group_arr = [];

        foreach ($group_data as $gkey => $gdata) {
            $group_arr[$gdata['name']] = $gdata['name'];
        }
        $log_model = new Schedule_Model_Logreport();
        $emp_data = $log_model->getempLis();

        $emp_arr = [];

        foreach ($emp_data as $ekey => $edata) {
            $emp_arr[$edata['userfullname']] = $edata['userfullname'];
        }

        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objId,
            'menuName' => 'Attendance Report',
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'formgrid' => $formgrid,
            'add' => 'add',
            'call' => $call,
            'dashboardcall' => $dashboardcall,
          
            'search_filters' => [
                'datetime' => ['type' => 'datepicker'],
             

                'start_status' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'OverTime' => 'OverTime', 'Offshift' => 'Offshift', 'absent' => 'Absent', 'Early' => 'Early', 'Late' => 'Late', 'On Time'=>'On Time']
                ],
                'end_status' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'OverTime' => 'OverTime', 'Offshift' => 'Offshift', 'absent' => 'Absent', 'Early' => 'Early', 'Late' => 'Late', 'On Time'=>'On Time']
                ],
            ],


        ];
        return $dataTmp;
    }

    public function getAttendancedata($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team)
    {

        $sort_field = 'att.datetime';
        $order = 'ASC';
        $sort_field_2 = 'tt.name';
        $order_2 = 'DESC';
        $getdata = $this->select()
            ->setIntegrityCheck(false)
            ->from(['att' => 'main_attendance'], [
                'Date' => 'att.datetime', 'Schedule' => 'sch.schedule_name','Division'=>'COALESCE(div.name,"------------")','Section'=>'COALESCE(sec.name,"------------")','Project'=>'COALESCE(pro.name,"------------")','Team'=>'COALESCE(tm.name,"------------")',
                'Shift' => 'tt.name', 'Emp_ID' => 'emp.id', 'Employee Name' => 'usr.userfullname', 'Department' => 'COALESCE(d.name,"------------")','Businessunit'=>'COALESCE(bu.name,"------------")',
                'Branch' => 'b.branch', 'Start' => 'DATE_FORMAT(att.user_start,"%h:%i %p")', 'End' => 'DATE_FORMAT(att.user_end,"%h:%i %p")'
            ])
            ->joinInner(['emp' => 'main_employees'], 'att.emp_id = emp.id', '')
            ->joinInner(['usr' => 'main_employees'], 'usr.id = emp.id', '')

            ->joinInner(['ep' => 'main_empposition'], 'ep.employee_id = emp.id', '')
            ->joinLeft(['d' => 'main_department'], 'ep.department_id = d.department_id', '')
            ->joinLeft(['bu' => 'main_sbunit'], 'ep.sbunit_id = bu.sbunit_id', '')
            ->joinLeft(['div' => 'main_division'], 'ep.division_id = div.division_id', '')
            ->joinLeft(['sec' => 'main_section'], 'ep.section_id = sec.section_id', '')
            ->joinLeft(['pro' => 'main_project'], 'ep.project_id = pro.project_id', '')
            ->joinLeft(['tm' => 'main_team'], 'ep.team_id = tm.team_id', '')
            ->joinLeft(['tt' => 'main_time_table'], '(att.shift_id = tt.time_id)', '')
            ->joinInner(['empsch' => 'main_emp_schedule'], 'empsch.emp_id = emp.id AND empsch.sch_id IN('.$schedule.')', '')
            ->joinInner(['sch' => 'main_schedule'], 'sch.sch_id = empsch.sch_id ', '')
           
            ->joinInner(['b' => 'main_emp_branch'], 'b.emp_branch_id = emp.branch')
            ->order(["$sort_field $order", "$sort_field_2 $order_2"]);

        $where = 'att.id';

        if ($start_date != '' && $end_date != '') {
            $where =  $where . ' AND att.datetime BETWEEN "' . $start_date . '" AND "' . $end_date . '"';
        }
        if ($day != '') {
            $where = $where . ' AND DAYNAME(att.datetime)="' . $day . '"';
            //$getdata ->joinInner(array('ws' => 'main_work_shift'), 'ws.time_id = tt.time_id', '');
        }
        if ($schedule != '') {
            $where = $where . ' AND sch.sch_id IN (' . $schedule.')';
        }
        if ($branch != '') {
            $where = $where . ' AND emp.branch=' . $branch;
        }
        if ($dpt != '') {
            $where = $where . ' AND ep.department_id = ' . $dpt;
        }
        if ($sbunit != '') {
            $where = $where . ' AND ep.sbunit_id = ' . $sbunit;
        } if ($division != '') {
            $where = $where . ' AND ep.division_id = ' . $division;
        } if ($section != '') {
            $where = $where . ' AND ep.section_id = ' . $section;
        } if ($project != '') {
            $where = $where . ' AND ep.project_id = ' . $project;
        } if ($team != '') {
            $where = $where . ' AND ep.team_id = ' . $team;
        }
        $where = $where . " AND ((att.start_status!='absent' AND att.end_status!='absent')OR(att.start_status='Dayoff' OR att.end_status='Dayoff'))";
        $getdata->where($where);
        //die($getdata);
        return $this->fetchAll($getdata)->toArray();
    }

    public function getAbsentAttend($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team)
    {
        $sort_field = 'att.datetime';
        $order = 'ASC';
        $sort_field_2 = 'tt.name';
        $order_2 = 'DESC';

        $getdata = $this->select()
            ->setIntegrityCheck(false)
            ->from(['emp' => 'main_employees'], [
                'Date' => 'att.datetime', 'Schedule' => 'sch.schedule_name','Division'=>'COALESCE(div.name,"------------")','Section'=>'COALESCE(sec.name,"------------")','Project'=>'COALESCE(pro.name,"------------")','Team'=>'COALESCE(tm.name,"------------")',
                'Shift' => 'tt.name', 'Emp_ID' => 'emp.id', 'Employee Name' => 'usr.userfullname',
                'Department' => 'COALESCE(d.name,"------------")', 'Branch' => 'b.branch','Businessunit'=>'COALESCE(bu.name,"------------")',
                'Start' => 'if(att.start_status="Dayoff","",att.start_status)', 'End' => 'if(att.end_status="Dayoff","",att.end_status)'
            ])
            ->joinInner(['att' => 'main_attendance'], 'att.emp_id = emp.id', '')
            ->joinInner(['usr' => 'main_employees'], 'usr.id = emp.id', '')

            ->joinInner(['ep' => 'main_empposition'], 'ep.employee_id = emp.id', '')
            ->joinLeft(['d' => 'main_department'], 'ep.department_id = d.department_id', '')
            ->joinLeft(['bu' => 'main_sbunit'], 'ep.sbunit_id = bu.sbunit_id', '')
            ->joinLeft(['div' => 'main_division'], 'ep.division_id = div.division_id', '')
            ->joinLeft(['sec' => 'main_section'], 'ep.section_id = sec.section_id', '')
            ->joinLeft(['pro' => 'main_project'], 'ep.project_id = pro.project_id', '')
            ->joinLeft(['tm' => 'main_team'], 'ep.team_id = tm.team_id', '')
            ->joinInner(['tt' => 'main_time_table'], '(att.shift_id = tt.time_id)', '')
            ->joinInner(['empsch' => 'main_emp_schedule'], 'empsch.emp_id = emp.id', '')
            ->joinInner(['sch' => 'main_schedule'], 'sch.sch_id = empsch.sch_id', '')
           
            ->joinInner(['b' => 'main_emp_branch'], 'b.emp_branch_id = emp.branch')
            ->order(["$sort_field $order", "$sort_field_2 $order_2"]);

        $where = 'att.id';

        if ($start_date != '' && $end_date != '') {
            $where =  $where . ' AND att.datetime BETWEEN "' . $start_date . '" AND "' . $end_date . '"';
        }
        if ($day != '') {
            $where = $where . ' AND DAYNAME(att.datetime)="' . $day . '"';
            //$getdata ->joinInner(array('ws' => 'main_work_shift'), 'ws.time_id = tt.time_id', '');
        }
        if ($schedule != '') {
            $where = $where . ' AND sch.sch_id IN (' . $schedule.')';
        }
        if ($branch != '') {
            $where = $where . ' AND emp.branch=' . $branch;
        }
        if ($dpt != '') {
            $where = $where . ' AND ep.department_id = ' . $dpt;
        }
        if ($sbunit != '') {
            $where = $where . ' AND ep.sbunit_id = ' . $sbunit;
        } if ($division != '') {
            $where = $where . ' AND ep.division_id = ' . $division;
        } if ($section != '') {
            $where = $where . ' AND ep.section_id = ' . $section;
        } if ($project != '') {
            $where = $where . ' AND ep.project_id = ' . $project;
        } if ($team != '') {
            $where = $where . ' AND ep.team_id = ' . $team;
        }

        $where = $where . " AND ((att.start_status='absent' OR att.end_status='absent'))";

        $getdata->where($where);
    //    die($getdata);
        return $this->fetchAll($getdata)->toArray();
    }
    public function getLeaveAttend($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team)
    {
        $sort_field = 'att.datetime';
        $order = 'ASC';
        $sort_field_2 = 'tt.name';
        $order_2 = 'DESC';
        $getdata = $this->select()
            ->setIntegrityCheck(false)
            ->from(['att' => 'main_attendance'], [
                'Date' => 'att.datetime', 'Schedule' => 'sch.schedule_name','Businessunit'=>'COALESCE(bu.name,"------------")','Division'=>'COALESCE(div.name,"------------")','Section'=>'COALESCE(sec.name,"------------")','Project'=>'COALESCE(pro.name,"------------")','Team'=>'COALESCE(tm.name,"------------")',
                'Shift' => 'tt.name', 'Emp_ID' => 'emp.id', 'Employee Name' => 'usr.userfullname', 'Branch' => 'b.branch','Department' => 'COALESCE(d.name,"------------")', 'Start' => 'att.start_status', 'End' => 'att.end_status'
            ])
            ->joinInner(['emp' => 'main_employees'], 'att.emp_id = emp.id', '')
            ->joinInner(['usr' => 'main_employees'], 'usr.id = emp.id', '')


            ->joinInner(['ep' => 'main_empposition'], 'ep.employee_id = emp.id', '')
            ->joinLeft(['d' => 'main_department'], 'ep.department_id = d.department_id', '')
            ->joinLeft(['bu' => 'main_sbunit'], 'ep.sbunit_id = bu.sbunit_id', '')
            ->joinLeft(['div' => 'main_division'], 'ep.division_id = div.division_id', '')
            ->joinLeft(['sec' => 'main_section'], 'ep.section_id = sec.section_id', '')
            ->joinLeft(['pro' => 'main_project'], 'ep.project_id = pro.project_id', '')
            ->joinLeft(['tm' => 'main_team'], 'ep.team_id = tm.team_id', '')
            ->joinInner(['tt' => 'main_time_table'], '(att.shift_id = tt.time_id)', '')
            ->joinInner(['empsch' => 'main_emp_schedule'], 'empsch.emp_id = emp.id', '')
            ->joinInner(['sch' => 'main_schedule'], 'sch.sch_id = empsch.sch_id', '')
           
            ->joinInner(['b' => 'main_emp_branch'], 'b.emp_branch_id = emp.branch')
            ->order(["$sort_field $order", "$sort_field_2 $order_2"]);

        $where = 'att.id';

        if ($start_date != '' && $end_date != '') {
            $where =  $where . ' AND att.datetime BETWEEN "' . $start_date . '" AND "' . $end_date . '"';
        }
        if ($day != '') {
            $where = $where . ' AND DAYNAME(att.datetime)="' . $day . '"';
            //$getdata ->joinInner(array('ws' => 'main_work_shift'), 'ws.time_id = tt.time_id', '');
        }
        if ($schedule != '') {
            $where = $where . ' AND sch.sch_id IN (' . $schedule.')';
        }
        if ($branch != '') {
            $where = $where . ' AND emp.branch=' . $branch;
        }
        if ($dpt != '') {
            $where = $where . ' AND ep.department_id = ' . $dpt;
        }
        if ($sbunit != '') {
            $where = $where . ' AND ep.sbunit_id = ' . $sbunit;
        } if ($division != '') {
            $where = $where . ' AND ep.division_id = ' . $division;
        } if ($section != '') {
            $where = $where . ' AND ep.section_id = ' . $section;
        } if ($project != '') {
            $where = $where . ' AND ep.project_id = ' . $project;
        } if ($team != '') {
            $where = $where . ' AND ep.team_id = ' . $team;
        }
        $where = $where . " AND ((att.start_status='Leave' OR att.end_status='Leave'))";
        // die($getdata);
        $getdata->where($where);
        return $this->fetchAll($getdata)->toArray();
    }
    public function getLateAttend($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team)
    {
        $sort_field = 'att.datetime';
        $order = 'ASC';
        $sort_field_2 = 'tt.name';
        $order_2 = 'DESC';
        $getdata = $this->select()
            ->setIntegrityCheck(false)
            ->from(['att' => 'main_attendance'], [
                'Date' => 'att.datetime', 'Schedule' => 'sch.schedule_name','Division'=>'COALESCE(div.name,"------------")','Section'=>'COALESCE(sec.name,"------------")','Project'=>'COALESCE(pro.name,"------------")','Team'=>'COALESCE(tm.name,"------------")',
                'Shift' => 'tt.name', 'Emp_ID' => 'emp.id', 'Employee Name' => 'usr.userfullname','Department' => 'COALESCE(d.name,"------------")','Businessunit'=>'COALESCE(bu.name,"------------")',
                'Branch' => 'b.branch', 'Start' => 'if(att.start_status="Late",DATE_FORMAT(TIMEDIFF(att.shift_start,att.user_start),"%H:%i"),"")',
                'End' => 'if(att.end_status="Left Early",DATE_FORMAT(TIMEDIFF(att.user_end,att.shift_end),"%H:%i"),"")'
            ])
            ->joinInner(['emp' => 'main_employees'], 'att.emp_id = emp.id', '')
            ->joinInner(['usr' => 'main_employees'], 'usr.id = emp.id', '')

            ->joinInner(['ep' => 'main_empposition'], 'ep.employee_id = emp.id', '')
            ->joinLeft(['d' => 'main_department'], 'ep.department_id = d.department_id', '')
            ->joinLeft(['bu' => 'main_sbunit'], 'ep.sbunit_id = bu.sbunit_id', '')
            ->joinLeft(['div' => 'main_division'], 'ep.division_id = div.division_id', '')
            ->joinLeft(['sec' => 'main_section'], 'ep.section_id = sec.section_id', '')
            ->joinLeft(['pro' => 'main_project'], 'ep.project_id = pro.project_id', '')
            ->joinLeft(['tm' => 'main_team'], 'ep.team_id = tm.team_id', '')
            ->joinInner(['tt' => 'main_time_table'], '(att.shift_id = tt.time_id)', '')
            ->joinInner(['empsch' => 'main_emp_schedule'], 'empsch.emp_id = emp.id', '')
            ->joinInner(['sch' => 'main_schedule'], 'sch.sch_id IN(empsch.sch_id)', '')
            
            ->joinInner(['b' => 'main_emp_branch'], 'b.emp_branch_id = emp.branch')
            ->order(["$sort_field $order", "$sort_field_2 $order_2"]);

        $where = 'att.id';

        if ($start_date != '' && $end_date != '') {
            $where =  $where . ' AND att.datetime BETWEEN "' . $start_date . '" AND "' . $end_date . '"';
        }
        if ($day != '') {
            $where = $where . ' AND DAYNAME(att.datetime)="' . $day . '"';
            //$getdata ->joinInner(array('ws' => 'main_work_shift'), 'ws.time_id = tt.time_id', '');
        }
        if ($schedule != '') {
            $where = $where . ' AND sch.sch_id IN (' . $schedule.')';
        }
        if ($branch != '') {
            $where = $where . ' AND emp.branch=' . $branch;
        }
        if ($dpt != '') {
            $where = $where . ' AND ep.department_id = ' . $dpt;
        }
        if ($sbunit != '') {
            $where = $where . ' AND ep.sbunit_id = ' . $sbunit;
        } if ($division != '') {
            $where = $where . ' AND ep.division_id = ' . $division;
        } if ($section != '') {
            $where = $where . ' AND ep.section_id = ' . $section;
        } if ($project != '') {
            $where = $where . ' AND ep.project_id = ' . $project;
        } if ($team != '') {
            $where = $where . ' AND ep.team_id = ' . $team;
        }
        $where = $where . " AND ((att.start_status='Late' OR att.end_status='Left Early'))";

        $getdata->where($where);
        return $this->fetchAll($getdata)->toArray();
    }
    public function getschedule()
    {
        $config = [
            'name' => 'main_schedule',
            'primary' => 'sch_id'
        ];
        $db = new Zend_Db_Table($config);
        $main_schedule = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        // die($main_schedule);
        return $db->fetchAll($main_schedule)->toArray();
    }
    public function getweekdays()
    {
        $config = [
            'name' => 'tbl_weeks',
            'primary' => 'week_id'
        ];
        $db = new Zend_Db_Table($config);
        $main_schedule = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive=1');
        return $db->fetchAll($main_schedule)->toArray();
    }
    public function getdepartements()
    {
        $config = [
            'name' => 'main_department',
            'primary' => 'department_id'
        ];
        $db = new Zend_Db_Table($config);
        $main_schedule = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        return $db->fetchAll($main_schedule)->toArray();
    }

    public function getsbunits()
    {
        $config = [
            'name' => 'main_sbunit',
            'primary' => 'sbunit_id'
        ];
        $db = new Zend_Db_Table($config);
        $main_schedule = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        return $db->fetchAll($main_schedule)->toArray();
    }
    public function getdivisions()
    {
        $config = [
            'name' => 'main_division',
            'primary' => 'division_id'
        ];
        $db = new Zend_Db_Table($config);
        $main_schedule = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        return $db->fetchAll($main_schedule)->toArray();
    }
    public function getsections()
    {
        $config = [
            'name' => 'main_section',
            'primary' => 'section_id'
        ];
        $db = new Zend_Db_Table($config);
        $main_schedule = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        return $db->fetchAll($main_schedule)->toArray();
    }
    public function getprojects()
    {
        $config = [
            'name' => 'main_project',
            'primary' => 'project_id'
        ];
        $db = new Zend_Db_Table($config);
        $main_schedule = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        return $db->fetchAll($main_schedule)->toArray();
    }
    public function getteams()
    {
        $config = [
            'name' => 'main_team',
            'primary' => 'team_id'
        ];
        $db = new Zend_Db_Table($config);
        $main_schedule = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        return $db->fetchAll($main_schedule)->toArray();
    }

    public function getBranch()
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['b' => 'main_emp_branch'], ['id' => 'b.emp_branch_id', 'branch' => 'b.branch'])
            ->where('b.isactive = "Y"');

        return $this->fetchAll($query)->toArray();
    }
}
