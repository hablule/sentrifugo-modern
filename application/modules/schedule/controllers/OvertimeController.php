<?php
class Schedule_OvertimeController extends Zend_Controller_Action
{
    public function init()
    {
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }

    public function indexAction()
    {
        $form = new Schedule_Form_Overtime();
        $form->setAttrib('action', BASE_URL . 'overtime');
        $overtimemodel = new Schedule_Model_Overtime();
        $empmodel = new Default_Model_Emplist();
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
            $by = 'ot.overtime_id';
            $sort2 = 'DESC';
            $by2 = 'e.user_id';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $searchArray = [];
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'ot.overtime_id';
            $by2 = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'e.user_id';
            $sort2 = 'DESC';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else $perPage = $this->_getParam('per_page', PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            /** search from grid - START **/
            $searchData = $this->_getParam('searchData');

            /** search from grid - END **/
        }

        if ($call == 'ajaxcall') {
            // if ($objname == 'addperk') {
            $dataTmp = $overtimemodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
            $this->view->dataArray = $dataTmp;
            // } else if ($objname == 'schedule') {
            //     $dataTmp2 = $empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
            //     $this->view->dataArray2 = $dataTmp2;
            // }
        } else {

            $dataTmp = $overtimemodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
            //$dataTmp2 = $empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');

            //die(json_encode($dataTmp2));
            $this->view->dataArray = $dataTmp;
            //$this->view->dataArray2 = $dataTmp2;


            if ($this->_request->isPost()) {

                if (isset($_POST['OT_ids'])) {
                    $OT =  $_POST['OT_ids'];
                    //die($perk);
                    $empid = $overtimemodel->getEmpIDPerOT($OT);
                    // die(json_encode($empid));
                }
            }
        }



        //$dataTmp = $overtimemode->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
        array_push($data, $dataTmp);
        //die(json_encode($dataTmp));
        //$this->view->dataArray = $data;
        $this->view->form = $form;
        $this->view->call = $call;
        $this->view->dashboardcall = $dashboardcall;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    public function addAction()
    {
        $overtimemodel = new Schedule_Model_Overtime();
        $auth = Zend_Auth::getInstance();

        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        
          $temp_id = $_POST['emp_ids'];
        $temp_ot_id = $_POST['OT_ids'];
        $datas = [];
        foreach ($temp_id as $id) {

            foreach ($temp_ot_id as $ot_id) {
                $data = [];
                //$emp_id = $payrollmodel->getEmpId($id);

                //$data['emp_id'] = (int)$emp_id[0]['user_id'];
                $data['emp_id'] = (int) $id;
                $data['OT_id'] = (int)$ot_id;
                $data['datetime'] = sapp_Global::change_date('Jun-02-2021', 'database');
                $data['from_time'] = sapp_Global::change_time('00:00', 'database');
                $data['to_time'] =  sapp_Global::change_time('00:00', 'database');

                array_push($datas, $data);
            }
        }
        //die(json_encode($data));

        $overtimemodel->saveEmpOT($data);
       
    }
    public function editAction()
    {

              $auth = Zend_Auth::getInstance();

        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $categorypermission = '';
        $categorypermission = sapp_Global::_checkprivileges(OVERTIMECATEGORY,$loginuserGroup,$loginuserRole,'add');
        $this->view->categorypermission = $categorypermission;
        $id = $this->_request->getParam('id');
        $form = new Schedule_Form_Overtime();
        $categoryForm = new Schedule_Form_OvertimeCategory();
        $overtimemodel = new Schedule_Model_Overtime();
        $form->setAttrib('action', BASE_URL + 'schedule/overtime/edit/id/:' . $id);
        $errmsg = [];
        $newelement = new addformelement();
        $OTcategories = $overtimemodel->getAllOTCategories();
        if (empty($OTcategories)) {
            $errmsg['category'] = 'Category not configured!';
        } else {
            $form->category->addMultiOptions(['' => 'Select...']);
            foreach ($OTcategories as $category) {
                $form->category->addMultiOptions([$category['OT_category_id'] => $category['name']]);
            }
        }

        $singleOTdata = $overtimemodel->getSingleOT($id);
   

        $form->addElement($newelement->addelement('checkbox', 'isactive', $singleOTdata[0], 'isactivename'));
        $form->OT_id->setValue($id);
        $form->name->setValue($singleOTdata[0]['name']);
        $form->base->setValue($singleOTdata[0]['base']);
        $form->category->setValue($singleOTdata[0]['category']);
        $form->description->setValue($singleOTdata[0]['description']);
        $form->begining->setValue(sapp_Global::change_date($singleOTdata[0]['begining'], ''));
        $form->ending->setValue(sapp_Global::change_date($singleOTdata[0]['ending'], ''));
        $form->autoapv->setValue(utlis::getcheckboxvalue($singleOTdata[0]['autoapprove'], 'form'));


        $base = $singleOTdata[0]['base'];
        if ($base == 'Time') {
            $form->base_start_time->setValue($singleOTdata[0]['s_start_time']);
            $form->base_end_time->setValue($singleOTdata[0]['s_end_time']);
        } else if ($base == 'Day') {
            $form->base_day->setValue($singleOTdata[0]['s_day']);
        } else if ($base == 'Date') {
            $form->base_start_date->setValue($singleOTdata[0]['s_start_date']);
        } else if ($base == 'Period') {
            $form->base_start_date->setValue(sapp_Global::change_date($singleOTdata[0]['s_start_date'], ''));
            $form->base_end_date->setValue(sapp_Global::change_date($singleOTdata[0]['s_end_date'], ''));
            $form->base_start_time->setValue($singleOTdata[0]['s_start_time']);
            $form->base_end_time->setValue($singleOTdata[0]['s_end_time']);
        }
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginuserid = $auth->getStorage()->read()->id;
        }
        if ($this->_request->isPost()) {

            $where = 'overtime_id=' . $id;
            $where_schedule = 'OT_id=' . $id;
            $data = [
                'name' => $this->_request->getParam('name'),
                'base' => $this->_request->getParam('base'),
                'OT_category_id' => $this->_request->getParam('category'),
                'description' => $this->_request->getParam('description', ''),
                'begining' => sapp_Global::change_date($this->_request->getParam('begining', '00:00:0000'), 'database'),
                'ending' => sapp_Global::change_date($this->_request->getParam('ending', '00:00:0000'), 'database'),
                'modifiedby' => $loginuserid,
                'modifieddate' => date('Y-m-d H:i:s'),
                'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                'autoapprove' => utlis::getcheckboxvalue($this->_request->getParam('autoapv'), 'database'),
            ];
            $base = $this->_request->getParam('base');
            $data_schedule = [];
            if ($base == 'Time') {
                $data_schedule['start_time'] = sapp_Global::change_time($this->_request->getParam('base_start_time'), 'database');
                $data_schedule['end_time'] = sapp_Global::change_time($this->_request->getParam('base_end_time'), 'database');
                $data_schedule['day'] = '';
                $data_schedule['start_date'] = '';
                $data_schedule['end_date'] = '';
            } else if ($base == 'Day') {
                $data_schedule['day'] = $this->_request->getParam('base_day');
                $data_schedule['start_time'] = '';
                $data_schedule['end_time'] = '';
                $data_schedule['start_date'] = '';
                $data_schedule['end_date'] = '';
            } else if ($base == 'Date') {
                $data_schedule['start_date'] = $this->_request->getParam('base_start_date');
                $data_schedule['end_date'] = '';
                $data_schedule['start_time'] = '';
                $data_schedule['end_time'] = '';
                $data_schedule['day'] = '';
            } else if ($base == 'Period') {
                $data_schedule['start_date'] = sapp_Global::change_date($this->_request->getParam('base_start_date'), 'database');
                $data_schedule['end_date'] = sapp_Global::change_date($this->_request->getParam('base_end_date'), 'database');
                $data_schedule['start_time'] = sapp_Global::change_time($this->_request->getParam('base_start_time'), 'database');
                $data_schedule['end_time'] = sapp_Global::change_time($this->_request->getParam('base_end_time'), 'database');
                $data_schedule['day'] = '';
            }
            $data_schedule['rate'] = $this->_request->getParam('rate') / 60;
            $overtimemodel->saveORupdate($data, $where, $data_schedule, $where_schedule);
            $actionflag = 2;
            $menuID = OVERTIME;
            sapp_Global::logManager($menuID,$actionflag,$loginuserid,$id);
            $this->_helper->getHelper("FlashMessenger")->addMessage(["success" => "Overtime Update Successfully."]);
            $this->_redirect(BASE_URL . 'schedule/overtime');
        }

        $this->view->form = $form;
        $this->view->categoryForm = $categoryForm;
        $this->view->msgarray = $errmsg;
        $this->view->data = $singleOTdata;
    }
    public function viewAction()
    {
        $overtimemodel = new Schedule_Model_Overtime();
        $form = new Schedule_Form_Overtime();
        $id = $this->_request->getParam('id');
        $singleOTdata = $overtimemodel->getSingleOT($id);
        $form->back->setLabel('Back');
        $this->view->data = $singleOTdata;
        $this->view->form = $form;
    }
    public function deleteAction()
    {
        if (isset($_POST['objid'])) {
            $objId = (int)$_POST['objid'];
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginuserid = $auth->getStorage()->read()->id;
            }
            $overtimemodel = new Schedule_Model_Overtime();
            $overtimemodel->deleteOT($objId);
            $actionflag = 3;
            $menuID = OVERTIME;
            sapp_Global::logManager($menuID,$actionflag,$loginuserid,$objId);
            $response = [
                'message' => 'Overtime Successfully Removed.',
                'msgtype' => 'success'
            ];

            //  die(json_encode($response));
        } else {
            //$data = array('fe'=>'fa');
            //die(json_encode($data));
        }
    }
    public function addnewotAction()
    {
        $auth = Zend_Auth::getInstance();

        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserRole = $auth->getStorage()->read()->emprole;
            $loginuserGroup = $auth->getStorage()->read()->group_id;            }
        $categorypermission = '';
        $categorypermission = sapp_Global::_checkprivileges(OVERTIMECATEGORY,$loginuserGroup,$loginuserRole,'add');
        $this->view->categorypermission = $categorypermission;
        $form = new Schedule_Form_Overtime();
        $categoryForm = new Schedule_Form_Overtimecategory();
        $overtimemodel = new Schedule_Model_Overtime();
        $msgArray = [];
        $errmsg = [];
        $OTcategories = $overtimemodel->getAllOTCategories();
        if (empty($OTcategories)) {
            $errmsg['category'] = 'Category not configured!';
        } else {
            $form->category->addMultiOptions(['empty' => 'Select...']);
            foreach ($OTcategories as $category) {
                $form->category->addMultiOptions([$category['OT_category_id'] => $category['name']]);
            }
        }
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginuserid = $auth->getStorage()->read()->id;
        }
        if ($this->getRequest()->isPost()) {

            if ($form->isValid($this->getRequest()->getPost())) {

                $data = [
                    'name' => $this->_request->getParam('name'),
                    'base' => $this->_request->getParam('base'),
                    'OT_category_id' => $this->_request->getParam('category'),
                    'description' => $this->_request->getParam('description', ''),
                    'begining' => sapp_Global::change_date($this->_request->getParam('begining', '00:00:0000'), 'database'),
                    'ending' => sapp_Global::change_date($this->_request->getParam('ending', '00:00:0000'), 'database'),
                    'autoapprove' => utlis::getcheckboxvalue($this->_request->getParam('autoapv'), 'database'),
                    // 'autoapprove'=>$this->_request->getParams('autoapv'),
                    'createdby' => $loginuserid,
                    'isactive' => 'Y',
                    'modifiedby' => $loginuserid,
                    'createddate' => date('Y-m-d H:i:s'),
                    'modifieddate' => date('Y-m-d H:i:s')
                ];
                $base = $this->_request->getParam('base');
                $data_schedule = [];
                if ($base == 'Time') {
                    $data_schedule['start_time'] = sapp_Global::change_time($this->_request->getParam('base_start_time'), 'database');
                    $data_schedule['end_time'] = sapp_Global::change_time($this->_request->getParam('base_end_time'), 'database');
                } else if ($base == 'Day') {
                    $data_schedule['day'] = $this->_request->getParam('base_day');
                } else if ($base == 'Date') {
                    $data_schedule['start_date'] = $this->_request->getParam('base_start');
                } else if ($base == 'Period') {
                    $data_schedule['start_date'] = sapp_Global::change_date($this->_request->getParam('base_start_date'), 'database');
                    $data_schedule['end_date'] = ($this->_request->getParam('base_end_date', 'database'));
                    $data_schedule['start_time'] = sapp_Global::change_time($this->_request->getParam('base_start_time'), 'database');
                    $data_schedule['end_time'] = sapp_Global::change_time($this->_request->getParam('base_end_time'), 'database');
                }
                $data_schedule['rate'] = $this->_request->getParam('rate') / 60;
                // Zend_Debug::dump($data);
                // Zend_Debug::dump($data_schedule);
                // die();
               $id = $overtimemodel->saveORupdate($data, '', $data_schedule, '');
                $actionflag = 1;
                $menuID = OVERTIME;
                sapp_Global::logManager($menuID,$actionflag,$loginuserid,$id);
                $this->_helper->getHelper("FlashMessenger")->addMessage(["success" => "Overtime Added Successfully."]);
                $this->_redirect('schedule/overtime');
            }

            $messages = $categoryForm->getMessages();

            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->msgarray = $msgArray;
        $this->view->form = $form;
        $this->view->categoryForm = $categoryForm;
        $this->msgarray = $errmsg;
    }
    public function  addcategoryAction()
    {
        $categoryForm = new Schedule_Form_Overtimecategory();
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $formModel = new Schedule_Model_Overtime();
        $categoryForm->setAction(BASE_URL . 'schedule/overtime/addcategory');
        if ($this->getRequest()->isPost()) {
            if ($categoryForm->isValid($this->getRequest()->getPost())) {
                $data = [
                    'name' => $this->_request->getParam('name_cat'),
                    'description' => $this->_request->getParam('description_cat'),
                ];
                
                $formModel->saveORupdateCategory($data, '');
                $categoryData = $formModel->getAllOTCategories();
                $opt = '';
                foreach ($categoryData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['OT_category_id'], $record['name']);
                }
                $this->view->categoryData = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
            }
            $messages = $categoryForm->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }

        $controllername = 'overtime';
        $this->view->msgarray = $msgArray;
        $this->view->categoryForm = $categoryForm;
        $this->view->controllername = $controllername;
    }

    public function checktimeAction()
    {
        $overtimemodel = new Schedule_Model_Overtime();
        if (isset($_POST['begining_date'])) {
            $start_date = sapp_Global::change_date($_POST['start_date'], 'database');
            $start_date = sapp_Global::change_date($_POST['end_date'], 'database');
            $start_time = sapp_Global::change_time($_POST['start_time'],'database');
            $end_time = sapp_Global::change_time($_POST['end_time'],'database');
            $data = $overtimemodel->getAllTime($start_date,$start_date,$start_time,$end_time);
            //die($end_date);
        } else {
            die('data not found');
        }
    }
}
