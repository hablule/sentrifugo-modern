<?php



class Schedule_TimescheduleController extends Zend_Controller_Action
{


    private $actionadd = 1;
    private $actionupdate = 2;
    private $actiondelete = 3;

    public function init()
    {
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }

    public function indexAction()
    {
        try {

            $timeschedulemodel = new Schedule_Model_Timeschedule();
            
            $call = $this->_getParam('call');
            if ($call == 'ajaxcall')
                $this->_helper->layout->disableLayout();
            $view = Zend_Layout::getMvcInstance()->getView();
            $objname = $this->_getParam('objname');
            $refresh = $this->_getParam('refresh');
            $data = [];
            $searchQuery = '';
            $searchArray = [];
            $tablecontent = '';
            $dashboardcall = $this->_getParam('dashboardcall');

            if ($refresh == 'refresh') {
                if ($dashboardcall == 'Yes')
                    $perPage = DASHBOARD_PERPAGE;
                else
                    $perPage = PERPAGE;
                $sort = 'DESC';
                $by = 'ms.modifieddate';
                $pageNo = 1;
                $searchData = '';
                $searchQuery = '';
                $searchArray = [];
            } else {
                $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
                $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ms.modifieddate  ';
                if ($dashboardcall == 'Yes')
                    $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
                else $perPage = $this->_getParam('per_page', PERPAGE);
                $pageNo = $this->_getParam('page', 1);
                /** search from grid - START **/
                $searchData = $this->_getParam('searchData');

                /** search from grid - END **/
            }
            $dataTmp = $timeschedulemodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');

            array_push($data, $dataTmp);
            $this->view->dataArray = $dataTmp;
            $this->view->call = $call;
            $this->view->dashboardcall = $dashboardcall;

            $this->view->messages = $this->_helper->flashMessenger->getMessages();
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function getschedulesAction()
    {
        try {

            $ids = $this->_getParam('ids');
            $model = new Schedule_Model_Timeschedule();
            $empschData = $model->getEmpSchedules($ids);
            $this->_helper->json($empschData);
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function getscheduleAction()
    {
        try {

            $id = $this->_getParam('id');
            $model = new Schedule_Model_Timeschedule();
            $data = $model->getscheduleByid($id);
            $this->_helper->json($data);
            $this->_redirect('timeshchedule/time');
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function getschedulecycleAction()
    {
        $id = $this->_getParam('id');
        $model = new Schedule_Model_Timeschedule();
        if ($id != null) {
            $data = $model->getScheduleCycle($id);
            $this->_helper->json($data);
        }
        $this->_helper->json(null);
    }

    public function getreportAction()
    {
        $id = $this->_getParam('id');
        $model = new Schedule_Model_Timeschedule();
        $data = $model->getscheduledetail($id);
        $this->_helper->json($data);
    }

    public function editAction()
    {
        try {

            $form = new Schedule_Form_Timeschedule();
            $dmtmodel = new Schedule_Model_Timeschedule();
            $empschmodel = new Default_Model_Empschedule();
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }
            $id = $this->getRequest()->getParam('id');
            $objName = 'timeschedule';


            if ($id) {    //Edit Record...
                if ($this->_getParam('test') == 'true') {
                    $form->test2->addValidator("NotEmpty", false, ['messages' => "need to add cycle"]);
                    $form->test2->setRequired(true);
                } else {
                    $form->test2->removeValidator("NotEmpty");
                    $form->test2->setRequired(false);
                }
                $empschdata = $empschmodel->getEmpScheduleBySchedule($id);
                if (!empty($empschdata))
                    $this->_redirect('schedule/timeschedule');

                if (is_numeric($id) && $id > 0) {
                    $data = $dmtmodel->getscheduledetail($id);

                    //die(Zend_Debug::dump($data));
                    if (!empty($data) && $data != "norows") {
                        $form->populate($data[0]);
                        // $form->test2->setValue($this->_getParam('test2'));

                        $form->submit->setLabel('Update');
                        $form->isactive->setValue(utlis::getcheckboxvalue($data[0]['msactive'], 'form'));
                        $form->isverified->setValue(utlis::getcheckboxvalue($data[0]['isverified'], 'form'));
                        $this->view->form = $form;
                        $this->view->controllername = $objName;
                        $this->view->id = $id;
                        $this->view->ermsg = '';
                        $this->view->inpage = 'Edit';
                        if ($this->_getParam('code') == $data['code']) {
                            $form->code->removeValidator('Db_NoRecordExists');
                        }
                    } else {
                        $this->view->ermsg = 'norecord';
                    }
                    $this->view->id = $id;
                } else {
                    $this->view->ermsg = 'nodata';
                }
            } else {
                //Add Record...
                $this->view->ermsg = '';
                $this->view->form = $form;
                $this->view->inpage = 'Add';
            }

            /* if ($this->_getParam('test2') == "" and $this->_getParam('test')=="false") {
                $this->_setParam('test2', '3');
                $form->test2->setValue('3');
            } */

            //die(Zend_Debug::dump($this->_getParam("test2")));
            if ($this->getRequest()->getPost()) {


                if ($form->isValid($this->_request->getPost())) {

                    $id = $this->_request->getParam('id');
                    $name = $this->_request->getParam('name');
                    $startdate = $this->_request->getParam('startdate');
                    $enddate = $this->_request->getParam('enddate');
                    $code = $this->_request->getParam('code');
                    $cycle = $this->_request->getParam('cycle');
                    $description = $this->_request->getParam('description');
                    $date = gmdate("Y-m-d H:i:s");
                    $data = [
                        'name' => $name,
                        'code' => $code,
                        'description' => $description,
                        'startdate' => sapp_Global::change_date($startdate, 'database'),
                        'enddate' => sapp_Global::change_date($enddate, 'database'),
                        'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive'), 'database'),
                        'isverified' => utlis::getcheckboxvalue($this->_request->getParam('isverified'), 'database'),
                        'cycle' => $cycle,

                    ];

                    if ($id != '') {

                        $data['modifiedby'] = $loginUserId;
                        $data['modifieddate'] = $date;
                        $where = ['schedule_id=?' => $id];
                    } else {
                        $data['createdby'] = $loginUserId;
                        $data['createddate'] = $date;
                        $where = '';
                    }
                    //  die(Zend_Debug::dump($this->_getParam('test2')));

                    $Id = $dmtmodel->saveORupdate($data, $where);
                    if ($Id == 'update') {
                        if ($this->_getParam('test') == 'true') {
                            if ($this->_getParam('test2') != '' || $this->_getParam('test2') != null) {
                                $model = new Schedule_Model_Schedulecycle();
                                $shiftmodel = new Schedule_Model_Cycleshift();
                                $cycleData = $model->getCycleShift($id);
                                $newscheduleData = json_decode($this->_getParam('test2'), true);
                                $schedulecycleids = [];
                                foreach ($cycleData as $value) {
                                    array_push($schedulecycleids, $value['schedulecycle_id']);
                                }

                                $schedule_cycleids = implode(',', $schedulecycleids);
                                $schedule_cycleids = trim($schedule_cycleids, ',');
                                $cycleshifts = $shiftmodel->getCycleShifts($schedule_cycleids);

                                foreach ($cycleshifts as $value) {
                                    $shiftmodel->deleteCycleshift($value['shift_id'], $value['schedulecycle_id']);
                                }

                                array_shift($cycleData[0]);
                                $model->deleteData('schedule_id=' . $id, 'main_schedulecycle', 'schedulecycle_id');

                                foreach ($newscheduleData as  $sch) {
                                    $cycleShiftData = array_pop($sch);
                                    $newschdata = $sch;
                                    $newschdata['schedule_id'] = $cycleData[0]['schedule_id'];
                                    $cycleid = $model->saveORupdate($newschdata, '');

                                    foreach ($cycleShiftData as  $value) {
                                        $value['schedulecycle_id'] = $cycleid;
                                        $shiftmodel->SaveorUpdateData($value, '');
                                    }
                                }
                            } else {
                                $msgarray['cycle'] = "Schedule cycle and Schedule cycle shift";
                            }
                        }
                        $this->_helper->getHelper("FlashMessenger")->addMessage(["success" => "Schedule updated successfully."]);
                    } else {

                        $schedule = json_decode($this->_getParam('test2'), true);
                        foreach ($schedule as  $value) {
                            $cycleshiftData = array_pop($value);
                            $value['schedule_id'] = $Id;
                            $cycleModel = new Schedule_Model_Schedulecycle();
                            $cycleId = $cycleModel->saveORupdate($value, '');
                            foreach ($cycleshiftData as $shift) {
                                $shift['schedulecycle_id'] = $cycleId;
                                $cycleShiftModel = new Schedule_Model_Cycleshift();
                                $cycleShiftId = $cycleShiftModel->SaveorUpdateData($shift, '');
                            }
                        }

                        $this->_helper->getHelper("FlashMessenger")->addMessage(["success" => "Schedule added successfully."]);
                    }

                    $this->_redirect('schedule/timeschedule');
                } else {
                    $messages = $form->getMessages();

                    foreach ($messages as $key => $val) {
                        foreach ($val as $key2 => $val2) {
                            $msgarray[$key] = $val2;
                            break;
                        }
                    }

                    $this->view->msgarray = $msgarray;
                }
            }
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }


    public function viewAction()
    {


        try {
            $form = new Schedule_Form_Timeschedule();
            $form->setAttrib('action', BASE_URL . 'timeschedule');
            $form->submit->setLabel('Back');
            $timeschmodel = new Schedule_Model_Timeschedule();
            $id = $this->_request->getParam('id');
            $schedule = $timeschmodel->getscheduledetail($id);
            $form->back->setLabel('Back');
            $this->view->schedules = $schedule[0];
            $this->view->form = $form;
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    // public function deleteAction()
    // {
    //     $timeschmodel = new Schedule_Model_Timeschedule();
    //     $auth = Zend_Auth::getInstance();
    //     if ($auth->hasIdentity()) {
    //         $loginid = $auth->getStorage()->read()->id;
    //     }
    //     $id = $this->_request->getParam('objid');
    //     $where = array('sch_id=?' => $id);
    //     $data = array(
    //         'isactive' => 0
    //     );
    //     $actionflag = 3;
    //     $menuID = SCHEDULE;
    //     $res = $timeschmodel->deleteData($where,'main_schedule','sch_id');
    //     sapp_Global::logManager($menuID,$actionflag,$loginid,$id);
    //     if ($res == true)
    //         return $this->_helper->json(array('flagtype' => 'process', 'message' => 'Deleted'));
    // }
    public function addpopupAction()
    {
        $msgarray = [];
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $controllername = 'timeschedule';
        $timetableform = new Schedule_Form_Timetable();
        $timetablemodel = new Schedule_Model_Timetable();
        $timetableform->setAction(BASE_URL . 'timetable/popup');
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $login = $auth->getStorage()->read()->id;
        }

        if ($this->_request->isPost()) {
            if ($timetableform->isValid($this->getRequest()->getPost())) {
                $desc = $this->_request->getParam('description');
                $time_in = $this->_request->getParam('time_in');
                $time_out = $this->_request->getParam('time_out');
                $data = [
                    'name' => $desc,
                    'time' => $time_in . '-' . $time_out,
                    'start_time' => sapp_Global::change_time($time_in, 'database'),
                    'end_time' => sapp_Global::change_time($time_out, 'database'),
                    'created_by' => $login,
                    'modified' => $login
                ];

                $actionflag = 1;
                $menuID = SCHEDULE;
                $id = $timetablemodel->saveandupdate('', $data);
                sapp_Global::logManager($menuID, $actionflag, $login, $id);
            } else {
                $messages = $timetableform->getMessages();

                foreach ($messages as $key => $val) {
                    foreach ($val as $key2 => $val2) {
                        $msgarray[$key] = $val2;
                        break;
                    }
                }
            }
        }

        $this->view->msgarray = $msgarray;
        $this->view->controllername = $controllername;
        $this->view->form = $timetableform;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }

    public function getAllShifts()
    {
        $new_data = [];
        if ($this->_request->getParam('mon_check') == 1) {
            $temp_arr = [];
            $shifts = $this->_request->getParam('mon_shift');
            foreach ($shifts as $shift) {
                array_push($temp_arr, $shift);
            }
            $new_data['Monday'] = $temp_arr;
        }
        if ($this->_request->getParam('tus_check') == 1) {
            $temp_arr = [];
            $shifts = $this->_request->getParam('tus_shift');
            foreach ($shifts as $shift) {
                array_push($temp_arr, $shift);
            }
            $new_data['Tuesday'] = $temp_arr;
        }
        if ($this->_request->getParam('wen_check') == 1) {
            $temp_arr = [];
            $shifts = $this->_request->getParam('wen_shift');
            foreach ($shifts as $shift) {
                array_push($temp_arr, $shift);
            }
            $new_data['Wednesday'] = $temp_arr;
        }
        if ($this->_request->getParam('thu_check') == 1) {
            $temp_arr = [];
            $shifts = $this->_request->getParam('thu_shift');
            foreach ($shifts as $shift) {
                array_push($temp_arr, $shift);
            }
            $new_data['Thursday'] = $temp_arr;
        }
        if ($this->_request->getParam('fri_check') == 1) {
            $temp_arr = [];
            $shifts = $this->_request->getParam('fri_shift');
            foreach ($shifts as $shift) {
                array_push($temp_arr, $shift);
            }
            $new_data['Friday'] = $temp_arr;
        }
        if ($this->_request->getParam('sat_check') == 1) {
            $temp_arr = [];
            $shifts = $this->_request->getParam('sat_shift');
            foreach ($shifts as $shift) {
                array_push($temp_arr, $shift);
            }
            $new_data['Saturday'] = $temp_arr;
        }
        if ($this->_request->getParam('sun_check') == 1) {
            $temp_arr = [];
            $shifts = $this->_request->getParam('sun_shift');
            foreach ($shifts as $shift) {
                array_push($temp_arr, $shift);
            }
            $new_data['Sunday'] = $temp_arr;
        }

        return $new_data;
    }

    public function deleteAction()
    {
        try {
            $id = $this->_getParam('objid');
            $timeschemodel = new Schedule_Model_Timeschedule();
            $timeschemodel->delete("schedule_id=" . $id);
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }

        $this->_helper->json([]);
    }
}


//calculater
