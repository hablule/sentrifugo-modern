<?php

class Schedule_TimecategoryController extends Zend_Controller_Action
{
    public function init()
    {
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }

    public function indexAction()
    {
        $model = new Schedule_Model_timeCategory();
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
            $by = 'modifiedby';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $searchArray = array();
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'modifiedby';
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
    public function editAction()
    {
        try {
            //code...
            $form = new Schedule_Form_timecategory();
            $dmtmodel = new Schedule_Model_timecategory();
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }
            $id = $this->getRequest()->getParam('id');
            $objName = 'timecategory';

            if ($id) {    //Edit Record...

                if (is_numeric($id) && $id > 0) {
                    $data = $dmtmodel->getSingleCategory($id);


                    if (!empty($data) && $data != "norows") {
                        $form->populate($data[0]);
                        $form->submit->setLabel('Update');
                        $form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'], 'form'));
                        $this->view->color = $data[0]['lablecolor'];
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

                    $name = $this->_request->getParam('name');
                    $code = $this->_request->getParam('code');
                    $lablecolor = $this->_request->getParam('lablecolor');

                    $description = $this->_request->getParam('description');

                    $date = gmdate("Y-m-d H:i:s");

                    $data = array(
                        'name'  => $name,
                        'code'  => $code,
                        'description'  => $description,
                        'lablecolor'  => $lablecolor,
                        'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive')),

                    );
                    if ($id != '') {

                        $data['modifiedby'] = $loginUserId;
                        $data['modifieddate'] = $date;
                        $where = array('timecategory_id=?' => $id);
                    } else {
                        $data['createdby'] = $loginUserId;
                        $data['createddate'] = $date;
                        $where = '';
                    }

                    $Id = $dmtmodel->saveORupdateCategory($data, $where);

                    if ($Id == 'update') {
                        $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Time Category updated successfully."));
                    } else {
                        $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Time Category added successfully."));
                    }

                    $this->_redirect('schedule/timecategory');
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
    //     public function addAction()
    //     {

    //         $otcategory = new Schedule_Form_Overtimecategory();
    //         $otcategorymodel = new Schedule_Model_OvertimeCategory();
    //         $auth = Zend_Auth::getInstance();
    //         if ($auth->hasIdentity()) {
    //             $login = $auth->getStorage()->read()->id;
    //         }

    //         if ($this->getRequest()->isPost()) {

    //             if ($otcategory->isValid($this->getRequest()->getPost())) {

    //                 $data = array(
    //                     'name' => $this->_request->getParam('name_cat'),
    //                     'description' => $this->_request->getParam('description_cat'),
    //                     'isActive' => 'Y',

    //                 );
    //                  $actionflag = 1;
    //                  $menuID = OVERTIMECATEGORY;
    //                  $objId=$otcategorymodel->saveORupdateCategory($data, '');
    //                  sapp_Global::logManager($menuID,$actionflag,$login,$objId);
    //                  $this->_helper->getHelper('FlashMessenger')->addMessage(array('messages'=>'Added Successfully'));
    //                  $this->_redirect('schedule/overtimecategory');

    //     }
    // }
    // $messages = $otcategory->getMessages();

    // 		foreach ($messages as $key => $val) {
    // 			foreach ($val as $key2 => $val2) {
    // 				$msgarray[$key] = $val2;
    // 				break;
    // 			}
    // 		}
    //         $this->view->msgarray = $msgarray;
    // $this->view->form = $otcategory;
    // }
    //     public function editAction()
    //     {
    //         $timecategoryForm = new Schedule_Form_Overtimecategory();
    //         $timecategoryForm->name_cat->removeValidator('Db_NoRecordExists');

    //         $timecategorymodel = new Schedule_Model_OvertimeCategory();
    //         $catdata = $timecategorymodel->getSingleCategory($this->_request->getParam('id'));
    //         $timecategoryForm->name_cat->setValue($catdata['name']);
    //         $timecategoryForm->setAction(BASE_URL.'schedule/overtimecategory/edit/id/'.$this->_request->getParam('id'));
    //         $timecategoryForm->description_cat->setValue($catdata['description']);
    //         $newelement = new addformelement();
    //         $auth = Zend_Auth::getInstance();

    //         if ($auth->hasIdentity()) {
    //             $login = $auth->getStorage()->read()->id;
    //         }
    //         $timecategoryForm->addElement($newelement->addelement('checkbox','isactive',$catdata,'isactive'));
    //         if ($this->getRequest()->isPost()) {

    //             if ($timecategoryForm->isValid($this->getRequest()->getPost())) {

    //                 $data = array(
    //                     'name' => $this->_request->getParam('name_cat'),
    //                     'description' => $this->_request->getParam('description_cat'),
    //                     'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive'))

    //                 );

    //                 $where = 'OT_category_id = '.$this->_request->getParam('id');
    //                 $actionflag = 2;
    //                 $menuID = OVERTIMECATEGORY;
    //                  $timecategorymodel->saveORupdateCategory($data, $where);
    //                  sapp_Global::logManager($menuID,$actionflag,$login,$this->_request->getParam('id'));
    //                  $this->_helper->getHelper('FlashMessenger')->addMessage(array('messages'=>'Updated Successfully'));
    //                  $this->_redirect('schedule/overtimecategory');

    //     }
    //     else{
    //         $messages = $timecategoryForm->getMessages();
    //             foreach ($messages as $key => $val) {
    //                 foreach ($val as $key2 => $val2) {
    //                     $msgArray[$key] = $val2;
    //                     break;
    //                 }
    //             }
    //     }
    // }

    //         $this->view->msgarray = $msgArray;
    //         $this->view->form = $timecategoryForm;
    //     }

    public function deleteAction()
    {
        $OTcategorymodel = new Schedule_Model_timecategory();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $login = $auth->getStorage()->read()->id;
        }

        $id = $this->_request->getParam('objid');

        $where = 'timecategory_id =' . (int)$id;
        $data = array(
            'isactive' => 'N'
        );
        //die(json_encode($where));
        $actionflag = 3;
        $menuID = TIMECATEGORY;
        $res = $OTcategorymodel->deleteData($where, 'main_timecategory', 'timecategory_id');
        sapp_Global::logManager($menuID, $actionflag, $login, $id);
        //$res = $OTcategorymodel->saveORupdateCategory($data, $where);
        if ($res > 0)
            return $this->_helper->json(array('flagtype' => 'time category', 'message' => 'Deleted'));
        die();
    }

    public function addpopupAction()
    {
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $form = new Schedule_Form_timecategory();
        $dmtmodel = new Schedule_Model_timecategory();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $id = $this->getRequest()->getParam('id');
        $objName = 'timecategory';

        if ($id) {    //Edit Record...

            if (is_numeric($id) && $id > 0) {
                $data = $dmtmodel->getSingleCategory($id);


                if (!empty($data) && $data != "norows") {
                    $form->populate($data[0]);
                    $form->submit->setLabel('Update');
                    $form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactive'], 'form'));
                    $this->view->color = $data[0]['lablecolor'];
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

                $name = $this->_request->getParam('name');
                $code = $this->_request->getParam('code');
                $lablecolor = $this->_request->getParam('lablecolor');

                $description = $this->_request->getParam('description');

                $date = gmdate("Y-m-d H:i:s");

                $data = array(
                    'name'  => $name,
                    'code'  => $code,
                    'description'  => $description,
                    'lablecolor'  => $lablecolor,
                    'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive')),

                );
                if ($id != '') {

                    $data['modifiedby'] = $loginUserId;
                    $data['modifieddate'] = $date;
                    $where = array('timecategory_id=?' => $id);
                } else {
                    $data['createdby'] = $loginUserId;
                    $data['createddate'] = $date;
                    $where = '';
                }

                $Id = $dmtmodel->saveORupdateCategory($data, $where);

                if ($Id == 'update') {
                    $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Time Category updated successfully."));
                } else {
                    $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Time Category added successfully."));
                    $this->view->pop = 'close';
                    $this->view->eventact = "added";
                    $this->view->controllername = 'schedule';
                }
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
        $this->view->form = $form;
    }

    public function viewAction()
    {
        $id = $this->_getParam('id');
        $timeCategoryModel = new Schedule_Model_timecategory();

        $timeCategoryData = $timeCategoryModel->getSingleCategory($id);

        $this->view->data = $timeCategoryData;
    }

    public function isavalibleAction()
    {
        try {

            $model = new Schedule_Model_timecategory();
            $shifts = $this->_getParam('category_id');
            $data = $model->getCategory($shifts);

            $this->_helper->json($data);
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
}
