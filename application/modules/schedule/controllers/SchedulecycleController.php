<?php



class Schedule_SchedulecycleController extends Zend_Controller_Action
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
        $model = new Schedule_Model_Schedulecycle();

        $call = $this->_getParam('call');
        if ($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();
        $view = Zend_Layout::getMvcInstance()->getView();
        $objname = $this->_getParam('objname');
        $refresh = $this->_getParam('refresh');
        $data = array();
        $searchQuery = '';
        $searchArray = array();
        $tablecontent = '';
        $dashboardcall = $this->_getParam('dashboardcall');

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes')
                $perPage = DASHBOARD_PERPAGE;
            else
                $perPage = PERPAGE;
            $sort = 'DESC';
            $by = 'sc.modifieddate';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $searchArray = array();
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'sc.modifieddate  ';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else $perPage = $this->_getParam('per_page', PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            /** search from grid - START **/
            $searchData = $this->_getParam('searchData');

            /** search from grid - END **/
        }
        $dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');

        array_push($data, $dataTmp);
        $this->view->dataArray = $dataTmp;
        $this->view->call = $call;
        $this->view->dashboardcall = $dashboardcall;

        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }


    public function dayofexistsAction()
    {

        $dayof = $this->_getParam('dayof');
        $cycle = $this->_getParam('cycle');
        $cycleid = $this->_getParam('cycleid');
        if ($dayof != null) {
            $model = new Schedule_Model_Schedulecycle();
            $this->_helper->json($model->checkCycle($cycleid, $cycle, $dayof));
        }
        $this->_helper->json(false);
    }

    public function editAction()
    {
        try {
            //code...
            $form = new Schedule_Form_Schedulecycle();

            $form->dayofweek->setAttrib('onchange', 'checkweekday()');
            $form->dayofmonth->setAttrib('onchange', 'checkweekday()');
            $dmtmodel = new Schedule_Model_Schedulecycle();
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }
            $id = $this->getRequest()->getParam('id');
            $objName = 'schedulecycle';

            if ($id) {    //Edit Record...

                if (is_numeric($id) && $id > 0) {
                    $form->schedule_id->setAttrib("readonly", true);
                    $data = $dmtmodel->getschedulecyclebyid($id);

                    $this->view->data = $data;
                    if (!empty($data) && $data != "norows") {
                        $form->populate($data[0]);
                        $form->submit->setLabel('Update');
                        $form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'], 'form'));

                        $this->view->form = $form;
                        $this->view->controllername = $objName;
                        $this->view->id = $id;
                        $this->view->ermsg = '';
                        $this->view->inpage = 'Edit';
                    } else {
                        $this->view->ermsg = 'norecord';
                    }
                } else {
                    $this->view->ermsg = 'nodata';
                }
            } else {
                //Add Record...
                $this->view->ermsg = '';
                $this->view->form = $form;
                $this->view->inpage = 'Add';
            }
            if ($this->getRequest()->getPost()) {

                if ($form->isValid($this->_request->getPost())) {
                    $id = $this->_request->getParam('id');

                    $name = $this->_request->getParam('schedule_id');
                    /*   $startdate = $this->_request->getParam('startdate');
                    $enddate = $this->_request->getParam('enddate');

                    $code = $this->_request->getParam('code');
                    $cycle = $this->_request->getParam('cycle'); */

                    $description = $this->_request->getParam('description');

                    $date = gmdate("Y-m-d H:i:s");

                    $data = array(
                        'schedule_id' => $name,
                        'description' => $description,
                        'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    );
                    $cycle = '';
                    if ($this->_request->getParam('daily') != '') {
                        $data['daily'] = $this->_request->getParam('daily');
                        $cycle = 'daily';
                    } else if ($this->_request->getParam('dayofweek') != '') {
                        $data['dayofweek'] = $this->_request->getParam('dayofweek');
                        $cycle = 'dayofweek';
                    } else {
                        $data['dayofmonth'] = $this->_request->getParam('dayofmonth');
                        $cycle = 'dayofmonth';
                    }

                    if ($id != '') {

                        $data['modifiedby'] = $loginUserId;
                        $data['modifieddate'] = $date;
                        $where = array('schedule_id=?' => $id);
                    } else {
                        $data['createdby'] = $loginUserId;
                        $data['createddate'] = $date;
                        $where = '';
                    }
                    // die(json_encode($data));
                    $model = new Schedule_Model_Schedulecycle();

                    $exists = $model->checkCycle($name, $data[$cycle], $cycle);
                    if (!$exists) {
                        $Id = $dmtmodel->saveORupdate($data, $where);

                        if ($Id == 'update') {
                            $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Schedule updated successfully."));
                        } else {
                            $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Schedule added successfully."));
                        }

                        $this->_redirect('schedule/timeschedule');
                    } else {
                        $msgarray['schedule_id'] = 'Cycle exists';
                    }
                    //die(Zend_Debug::dump($data));
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

        try {
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
            $controllername = 'timeschedule';


            $form = new Schedule_Form_Schedulecycle();
            $form->setAction(BASE_URL . 'schedulecycle/addpopup' + $this->_getParam('id'));

            $dmtmodel = new Schedule_Model_Schedulecycle();
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }
            $id = $this->getRequest()->getParam('id');
            $objName = 'schedulecycle';

            if ($id) {    //Edit Record...

                if (is_numeric($id) && $id > 0) {
                    $data = $dmtmodel->getschedulecyclebyid($id);


                    if (!empty($data) && $data != "norows") {
                        $form->populate($data[0]);

                        $form->submit->setLabel('Update');
                        $form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'], 'form'));


                        $this->view->controllername = $objName;
                        $this->view->id = $id;
                        $this->view->ermsg = '';
                        $this->view->inpage = 'Edit';
                    } else {
                        $this->view->ermsg = 'norecord';
                    }
                } else {
                    $this->view->ermsg = 'nodata';
                }
            } else {
                //Add Record...
                $this->view->ermsg = '';

                $this->view->inpage = 'Add';
            }

            if ($this->getRequest()->getPost()) {

                if ($form->isValid($this->_request->getPost())) {
                    if ($this->_getParam('cycleshiftdata') != '') {
                        $id = $this->_request->getParam('id');

                        $name = $this->_request->getParam('name');
                        $startdate = $this->_request->getParam('startdate');
                        $enddate = $this->_request->getParam('enddate');

                        $code = $this->_request->getParam('code');
                        $cycle = $this->_request->getParam('cycle');

                        $description = $this->_request->getParam('description');

                        $date = gmdate("Y-m-d H:i:s");

                        /*  $data = array(
                                          'description' => $description,
                                          'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive')),

                                    ); */
                        $data = [];
                        $unpackeddata = [];
                        $daliy = [];
                        if ($this->_getParam('daily') != '') {
                            $data['daily'] = $this->_getParam('daily');
                            $data['description'] = $description;
                            $data['cycleshiftdata'] = json_decode($this->_getParam('cycleshiftdata'), true);
                            $daliy[$this->_getParam('daily')] = $data;
                            //$data['daily'] = $data;
                            //$data = json_encode($data);
                            $data = json_encode($daliy);
                        } else {
                            $unpackeddata = json_decode($this->_getParam('cycleshiftdata'), true);

                            if ($unpackeddata[0]['shift_id'] != null) {
                                array_pop($unpackeddata);
                            }
                            if ($this->_getParam('dayofweek') != ' ') {

                                if ($unpackeddata[$this->_getParam('dayofweek')] == null) {
                                    $info = array();
                                    $info['dayofweek'] = $this->_getParam('dayofweek');
                                    $info['description'] = $this->_getParam('description');
                                    $info['isactive'] = $this->_getParam('isactive');
                                    $info['cycleshiftdata'] = json_decode($this->_getParam('cycleshift'), true);
                                    $unpackeddata[$this->_getParam('dayofweek')] = $info;
                                }
                            } else if ($this->_getParam('dayofmonth') != ' ') {
                                if ($unpackeddata[$this->_getParam('dayofmonth')] == null) {
                                    $info = array();
                                    $info['dayofmonth'] = $this->_getParam('dayofmonth');
                                    $info['description'] = $this->_getParam('description');
                                    $info['isactive'] = $this->_getParam('isactive');
                                    $info['cycleshiftdata'] = json_decode($this->_getParam('cycleshift'), true);
                                    $unpackeddata[$this->_getParam('dayofmonth')] = $info;
                                }
                            }

                            $data = json_encode($unpackeddata);
                        }

                        /*  if($this->_getParam('dayily')!='')
                        {
                            $data['daily'] = $this->_getParam('dayily');
                        }
                        else if($this->_getParam('dayofmonth')!='')
                        {
                            $data['dayofmonth'] = $this->_getParam('dayofmonth');
                        }
                        else
                        {
                            $data['dayofweek'] = $this->_getParam('dayofweek');
                        } */
                        /* if($id!=''){

                            $data['modifiedby'] = $loginUserId;
                            $data['modifieddate'] = $date;
                            $where = array('schedule_id=?'=>$id);
                        }
                        else
                        {
                            $data['createdby'] = $loginUserId;
                            $data['createddate'] = $date;
                            $where = '';
                        } */

                        //die(json_encode($data));
                        $Id = '';
                        $this->view->cycledata = $data;

                        if ($this->_getParam('cycleshiftdata') != '' || $this->_getParam('cycleshiftdata') != null) {
                            $this->view->pop = 'close';
                            $this->view->eventact = "added";
                            $this->view->controllername = 'schedulecycle';

                            if ($Id == 'update') {
                                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Schedule updated successfully."));
                            } else {
                                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Schedule added successfully."));
                            }
                        } else {
                            $message = 'Schedule shift Required';
                            if ($this->_getParam('daily') != '') {
                                $msgarray['daily'] = $message;
                            } else if ($this->_getParam('dayofweek') != '') {
                                $msgarray['dayofweek'] = $message;
                            } else {
                                $msgarray['dayofmonth'] = $message;
                            }
                        }
                    } else {
                        $message = "need to add shift";
                        $msgarray['daily'] = $message;
                        $msgarray['dayofweek'] = $message;
                        $msgarray['dayofmonth'] = $message;
                    }

                    // $this->_redirect('schedule/timeschedule');



                } else {
                    $messages = $form->getMessages();
                    foreach ($messages as $key => $val) {
                        foreach ($val as $key2 => $val2) {
                            $msgarray[$key] = $val2;
                            break;
                        }
                    }
                }
            }
            $this->view->msgarray = $msgarray;
            $this->view->form = $form;
            $this->view->id = $id;
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function editpopupAction()
    {
        try {

            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
            $id = $this->_getParam('id');
            $cycleid = $this->_getParam('cycleid');
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginid = $auth->getStorage()->read()->id;
            }

            $form = new Schedule_Form_Schedulecycle();
            $model = new Schedule_Model_Schedulecycle();
            $ogdata = $model->getCycleShift($cycleid);
            $form->dayofweek->removeValidator('NotEmpty');
            $form->daily->removeValidator('NotEmpty');
            $form->dayofmonth->removeValidator('NotEmpty');
            $cycleids = array();
            if ($this->getRequest()->isPost()) {
                if ($form->isValid($this->getRequest()->getPost())) {
                    $cycledata = $this->_getParam('cycleshiftdata');
                    $cycledata = json_decode($cycledata, true);
                    $extraData = array();
                    if ($this->_getParam('statedata') != null or $this->_getParam('statedata') != '') {
                        if ($this->_getParam('dayofweek') != ' ') {
                            $extraData['dayofweek'] = $this->_getParam('dayofweek');
                            $extraData['description'] = $this->_getParam('description');
                            $extraData['isactive'] = $this->_getParam('isactive');
                            $extraData['cycle'] = $this->_getParam('dayofweek');
                            $extraData['schedule_id'] = $cycledata[0]['schedule_id'];
                            $extraData['cycleshiftdata'] = json_decode($this->_getParam('statedata'), true);
                        } elseif ($this->_getParam('dayofmonth') != ' ') {
                            $extraData['dayofmonth'] = $this->_getParam('dayofmonth');
                            $extraData['description'] = $this->_getParam('description');
                            $extraData['isactive'] = $this->_getParam('isactive');
                            $extraData['cycle'] = $this->_getParam('dayofmonth');
                            $extraData['schedule_id'] = $cycledata[0]['schedule_id'];
                            $extraData['cycleshiftdata'] = json_decode($this->_getParam('statedata'), true);
                        }
                        array_push($cycledata, $extraData);

                    }
                    foreach ($cycledata as  $value) {
                        $data = array(
                            'schedule_id' => $value['schedule_id'],
                            'description' => $this->_getParam('description'),
                            'isactive' => utlis::getcheckboxvalue($this->_getParam('isactive'), 'database')
                        );
                        if ($value['schedulecycle_id'] == null || $value['schedulecycle_id'] == '') {
                            $cycleshiftmodel = new Schedule_Model_Cycleshift();
                            $cycle = $this->_getParam('id');
                            if ($cycle == 'WEEKLY') {
                                $data['dayofweek'] = $value['dayofweek'];
                            } else if ($cycle == 'MONTHLY') {
                                $data['dayofmonth'] = $value['dayofmonth'];
                            }
                            $data['createdby'] = $loginid;
                            $data['createddate'] = date('Y-m-d H:i:s');
                            $Id = $model->saveORupdate($data, '');
                            $cycleshiftdata = $value['cycleshiftdata'];

                            foreach ($cycleshiftdata as $cycleshift) {
                                $shiftdata = $cycleshift;
                                $shiftdata['schedulecycle_id'] = $Id;
                                $cycleshiftmodel->SaveorUpdateData($shiftdata, '');
                            }
                        } else {
                            array_push($cycleids, $value['schedulecycle_id']);
                            $data['modifiedby'] = $loginid;
                            $data['modifieddate'] = date('Y-m-d H:i:s');
                            $model->saveORupdate($data, 'schedulecycle_id=' . $value['schedulecycle_id']);
                        }
                    }

                    $ogids = array();
                    $msgarray = [];
                    foreach ($ogdata as $og) {
                        array_push($ogids, $og['schedulecycle_id']);
                    }
                    if (count($cycleids) != 0) {
                        $deletearray = array_diff($ogids, $cycleids);
                        $this->view->pop = "close";
                    } else {

                        if ($this->_getParam('id') == 'WEEKLY') {
                            $msgarray['dayofweek'] = "need at least one shift";
                        } else if ($this->_getParam('id') == 'MONTHLY') {
                            $msgarray['dayofmonth'] = "need at least one shift";
                        } else {
                            $msgarray['daily'] = "need at least one shift";
                        }
                    }

                    foreach ($deletearray as  $value) {
                        $model->delete('schedulecycle_id=' . $value);
                    }
                    $this->view->controllername = 'schedulecycle';

                    $this->view->msgarray = $msgarray;
                }
            }
            $this->view->id = $id;
            $this->view->cycleid = $cycleid;

            $this->view->form = $form;
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
            die();
        }
    }

    public function getcycledataAction()
    {

        try {

            $model = new Schedule_Model_Schedulecycle();
            $id = $this->_getParam('id');
            if ($id != null) {
                $cycleData = $model->getCycleShift($id);
                $newcycle = array();
                foreach ($cycleData as $value) {
                    $cycle = '';
                    $newcycledata = $value;
                    if ($value['daily'] == null) {

                        if ($value['dayofweek'] != null) {
                            $cycle = $value['dayofweek'];
                        } else {
                            $cycle = $value['dayofmonth'];
                        }
                    } else {
                        $cycle = $value['daily'];
                    }
                    $newcycledata['cycle'] = $cycle;
                    array_push($newcycle, $newcycledata);
                }
                $this->_helper->json($newcycle);
            }
            $this->_helper->json(array());
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
}
