<?php

class Schedule_AttendancereportController extends Zend_Controller_Action
{
    public function init()
    {
        require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
		require_once 'application/modules/default/library/utilClasses/Util.php';
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function indexAction()
    {
       
        try {
         
        $logreportModel = new Schedule_Model_Attendancereport();
        $call = $this->_getParam('call');
        if ($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();
            $view = Zend_Layout::getMvcInstance()->getView();

        $objname = $this->_getParam('objname');
        $refresh = $this->_getParam('refresh');
        $dashboardcall = $this->_getParam('dashboardcall');
        $data = [];
        $searchQuery = '';
        $searchArray = [];

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes')
                $perPage = DASHBOARD_PERPAGE;
            else
                $perPage = PERPAGE;
            $sort = 'DESC';
            $by = 'att.datetime';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $searchArray = [];
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'att.datetime';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else
                $perPage = $this->_getParam('per_page', PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            $searchData = $this->_getParam('searchData');
        }

        $dataTmp = $logreportModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
        //die(json_encode($dataTmp));
        array_push($data, $dataTmp);
        $this->view->dataArray = $data;
        $this->view->call = $call;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
           //code...
        } catch (\Throwable $th) {
          Zend_Debug::dump($th->getMessage());
        }
    }

    public function printreportAction()
    {
       try {
           //code...
     
        $attend_data = [];
        $leave_data = [];
        $absent_data = [];
        $late_data = [];
        $report_options = [];
        $attend_model = new Schedule_Model_Attendancereport();
        $form = new Schedule_Form_AttendanceReport();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
      
        $schedule_datas = $attend_model->getschedule();
      
        foreach ($schedule_datas as $schedule_data) {
            $form->schedule->addMultiOptions([$schedule_data['sch_id'] => $schedule_data['schedule_name']]);
        }
        // die($schedule_datas);
        // $day_datas = $attend_model->getweekdays();
        // foreach ($day_datas as $day_data) {
        //     $form->day->addMultiOptions(array($day_data['week_id'] => $day_data['week_name']));
        // }
        $dpt_datas = $attend_model->getdepartements();

        foreach ($dpt_datas as $dpt_data) {
            $form->dpt->addMultiOptions([$dpt_data['department_id'] => $dpt_data['name']]);
        }
     
        $sbunitdatas = $attend_model->getsbunits();
        $form->sbunit->addMultiOptions(['empty' => 'Select...']);
        foreach ($sbunitdatas as $sbunitdata) {
            $form->sbunit->addMultiOptions([$sbunitdata['sbunit_id'] => $sbunitdata['name']]);
        }
        $divdatas = $attend_model->getdivisions();
        $form->division->addMultiOptions(['empty' => 'Select...']);

        foreach ($divdatas as $divdata) {
            $form->division->addMultiOptions([$divdata['division_id'] => $divdata['name']]);
        }


        $secdatas = $attend_model->getsections();
        $form->section->addMultiOptions(['empty' => 'Select...']);

        foreach ($secdatas as $secdata) {
            $form->section->addMultiOptions([$secdata['section_id'] => $secdata['name']]);
        }
      

        $prodatas = $attend_model->getprojects();
        $form->project->addMultiOptions(['empty' => 'Select...']);

        foreach ($prodatas as $prodata) {
            $form->project->addMultiOptions([$prodata['project_id'] => $prodata['name']]);
        }
        $teamdatas = $attend_model->getteams();
        $form->team->addMultiOptions(['empty' => 'Select...']);

        foreach ($teamdatas as $teamdata) {
            $form->team->addMultiOptions([$teamdata['team_id'] => $teamdata['name']]);
        }

        $branches = $attend_model->getBranch();
        $form->branch->addMultiOptions(['empty' => 'Select...']);
        foreach ($branches as $branch) {
            $form->branch->addMultiOptions([$branch['id'] . "" => $branch['branch']]);
        }
        
        if ($this->_request->isPost()) {
            $searchby = $this->_request->getParam('search_by');
            $report_options = $this->_request->getParam('report_option');

            $day = '';
            $start_date = sapp_Global::change_date($this->_request->getParam('start_date'), 'database');
            $end_date = sapp_Global::change_date($this->_request->getParam('end_date'), 'database');
            $schedules = $this->_request->getParam('schedule');
            $sch = '';
            foreach($schedules AS $schedule){
                $sch = $sch . $schedule.',';
            }
            $schedule = trim($sch,',');
            $dpt = '';
            $branch = '';
            $sbunit = '';
            $division = '';
            $section = '';
            $project = '';
            $team = '';
            foreach ($searchby as $search) {
                if ($search == "Day") {
                    $day = $this->_request->getParam('day');
                } else if ($search == "Branch") {
                    $branch = $this->_request->getParam('branch');
                } else if ($search == "Department") {
                    $dpt = $this->_request->getParam('dpt');
                } 
                else if ($search == "Businessunit") {
                    $sbunit = $this->_request->getParam('sbunit');
                }  else if ($search == "Division") {
                    $division = $this->_request->getParam('division');
                }  else if ($search == "Section") {
                    $section = $this->_request->getParam('section');
                }  else if ($search == "Project") {
                    $project = $this->_request->getParam('project');
                }  else if ($search == "Team") {
                    $team = $this->_request->getParam('team');
                } 
            }
           
            foreach ($report_options as $report_option) {
                if ($report_option == "All") {
                    $attend_data = $attend_model->getAttendancedata($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team);
                    $absent_data = $attend_model->getAbsentAttend($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team);
                    $leave_data = $attend_model->getLeaveAttend($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team);
                    $late_data = $attend_model->getLateAttend($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team);
       
                } else if ($report_option == "Attendance") {
                    $attend_data = $attend_model->getAttendancedata($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team);
                } else if ($report_option == "Absent") {
                    $absent_data = $attend_model->getAbsentAttend($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team);
                } else if ($report_option == "Late") {
                    $late_data = $attend_model->getLateAttend($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team);
                } else if ($report_option == "Leave") {
                    $leave_data = $attend_model->getLeaveAttend($start_date, $end_date, $day, $schedule, $dpt, $branch,$sbunit,$division,$section,$project,$team);
                }
            }

        }
        $this->view->form = $form;
        $this->view->data = $attend_data;
        $this->view->absent_data = $absent_data;
        $this->view->leave_data = $leave_data;
        $this->view->late_data = $late_data;
        $this->view->report_options = $report_options;
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }
}
