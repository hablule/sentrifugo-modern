<?php

class Default_EmpdeviceController extends Zend_Controller_Action
{
    protected $fingermap = array(
        0 => 'Thumb',
        1 => 'Index',
        2 => 'Middle',
        3 => 'Ring',
        4 => 'Pinky',
    );
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function preDispatch()
    {
        //$ajaxContext = $this->_helper->getHelper('AjaxContext');
        //$ajaxContext->addActionContext('filterdevice', 'html')->initContext();
    }

    public function indexAction()
    {
    }

    public function editAction()
    {

        try {

            if (defined('EMPTABCONFIGS')) {

                $empOrganisationTabs = explode(',', EMPTABCONFIGS);

                if (in_array('emp_device', $empOrganisationTabs)) {
                    $empOrganizationTabs = explode(",", EMPTABCONFIGS);

                    if (in_array('emp_device', $empOrganizationTabs)) {

                        $userID = "";
                        $auth = Zend_Auth::getInstance();
                        if ($auth->hasIdentity()) {
                            $loginUserId = $auth->getStorage()->read()->id;
                            $loginuserGroup = $auth->getStorage()->read()->group_id;
                            $loginUserRole = $auth->getStorage()->read()->emprole;
                        }

                        $conText = "";
                        $id = ($this->getRequest()->getParam('userid') != '') ? $this->getRequest()->getParam('userid') : $this->getRequest()->getParam('unitId');
                        $call = $this->_getParam('call');

                        $usersModel = new Default_Model_Users();
                        if ($call == 'ajaxcall') {
                            $this->_helper->layout->disableLayout();
                            $userID = ($this->_getParam('unitId') != '') ? $this->_getParam('unitId') : $this->_getParam('userid');
                            $conText = ($this->_getParam('context') != '') ? $this->_getParam('context') : $this->getRequest()->getParam('context');
                        }
                        if ($id == '')        $id = $userID;
                        $Uid = ($id) ? $id : $userID;
                        if ($Uid != "") {
                            $employeeData = $usersModel->getUserDetailsByIDandFlag($Uid);
                        }
                        $employeeleavesModel = new Default_Model_empdevice();
                        $employeeModal = new Default_Model_Employee();
                        $isrowexist = $employeeModal->getsingleEmployeeData($id);

                        if ($isrowexist == 'norows')
                            $this->view->rowexist = "norows";
                        else
                            $this->view->rowexist = "rows";
                        $empdata = $employeeModal->getActiveEmployeeData($id);

                        $this->view->empdata = $empdata;

                        try {
                            $objname = $this->_getParam('objname');
                            $refresh = $this->_getParam('refresh');
                            $dashboardcall = $this->_getParam('dashboardcall', null);
                            $data = array();
                            $searchQuery = '';
                            $searchArray = array();
                            $tablecontent = '';
                            if ($refresh == 'refresh') {
                                if ($dashboardcall == 'Yes')
                                    $perPage = DASHBOARD_PERPAGE;
                                else
                                    $perPage = PERPAGE;

                                $sort = 'DESC';
                                $by = 'md.device_id';
                                $pageNo = 1;
                                $searchData = '';
                                $searchQuery = '';
                                $searchArray = array();
                            } else {
                                $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
                                $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'md.device_id';
                                if ($dashboardcall == 'Yes')
                                    $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
                                else
                                    $perPage = $this->_getParam('per_page', PERPAGE);

                                $pageNo = $this->_getParam('page', 1);
                                $searchData = $this->_getParam('searchData');
                                $searchData = rtrim($searchData, ',');
                            }
                            try {
                                //code...
                                $dataTmp = $employeeleavesModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $Uid, $conText, 'edit');
                            } catch (\Throwable $th) {
                                Zend_Debug::dump($th->getMessage());
                            }

                            array_push($data, $dataTmp);
                            $permission = sapp_Global::_checkprivileges(EMPLOYEE, $loginuserGroup, $loginUserRole, 'edit');
                            //Zend_Debug::dump($permission);die;
                            $this->view->dataArray = $data;
                            $this->view->call = $call;
                            $this->view->id = $id;
                            $this->view->messages = $this->_helper->flashMessenger->getMessages();
                            if (!empty($employeeData))
                                $this->view->employeedata = $employeeData[0];
                            $this->view->usergroup = $loginuserGroup;
                            $this->view->permission = $permission;
                        } catch (Exception $e) {
                            $this->view->rowexist = "norows";
                        }
                    } else {
                        $this->_redirect('error');
                    }
                } else {
                    $this->_redirect('error');
                }
            }
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function editpopupAction()
    {
        try {
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
            $configForm = new Default_Form_empdevice();
            $deviceModel = new Schedule_Model_Device();
            $userDevice = new Default_Model_Deviceuser();
            $fingerPrint = new Default_Model_Fingerprint();
            $userConfigModel = new Default_Model_empdevice();
            $deviceUser = $userConfigModel->getUserConfig($this->_request->getParam('id'));
            $fingerPrintTemplates = $fingerPrint->getAllFingerPrintTemplate($this->_request->getParam('unitId'));
            $fingerIndex = 0;
            $finger = array();
           
            foreach ($fingerPrintTemplates as $value) {
                if ($value['finger'] >= 5) {
                    $fingerType = $this->fingermap[$value['finger'] - 5];
                    if (!isset($finger[$fingerType])) {
                        $finger[$fingerType] = array();
                    }
                    $finger[$fingerType]["left"] = str_replace('"', '', $value['fingerprint_template']);
                } else {
                    $fingerType = $this->fingermap[$value['finger']];
                    if (!isset($finger[$fingerType])) {
                        $finger[$fingerType] = array();
                    }
                    $finger[$fingerType]["right"] = str_replace('"', '', $value['fingerprint_template']);
                }

                // $finger = "Finger ".$fingerIndex;
                //$configForm->fingerprint_template->addMultiOption($value['id'], $finger);
                $fingerIndex++;
            }

            $this->view->hs = json_encode($finger);

            $deviceList = $deviceModel->getDevices();
            foreach ($deviceList as $key => $value) {
                $configForm->device_id->addMultiOption($value['device_id'], $value['device_model']);
                $configForm->device_to_scan->addMultiOption($value['device_id'], $value['device_model']);
            }

            $device_ids = $deviceUser['device_id'];
            $device_ids = explode(',', $device_ids);
            foreach ($device_ids as $key => $value) {

                $configForm->device_id->setValue($value);
            }
            //$configForm->device_id->setValue($deviceUser['device_id']);
            if ($deviceUser['num_of_fingers'] > 0 && $deviceUser <= 10) {
                $configForm->choose_auth_method->setValue(1);
                $configForm->num_of_fingers->setValue(count($fingerPrintTemplates));

                $configForm->id->setValue($deviceUser['id']);
                $configForm->finger_auth_mode->setValue($deviceUser['fingerprint_auth_mode']);
                if ($deviceUser['fingerprint_auth_mode'] == 1) {
                    $configForm->finger_auth_mode->setValue($deviceUser['fingerprint_auth_mode']);
                    $configForm->pin->setValue($deviceUser['pin']);
                }
            }


            if ($deviceUser['num_of_faces'] == 1) {
                $configForm->choose_auth_method->setValue(2);
                $configForm->num_of_faces->setValue($deviceUser['num_of_faces']);
                $configForm->face_auth_mode->setValue($deviceUser['face_auth_mode']);
                $this->view->hs = $deviceUser['face_template'];
                if ($deviceUser['face_auth_mode'] == 1) {
                    $configForm->face_auth_mode->setValue($deviceUser['fingerprint_auth_mode']);
                }
            }
            if ($deviceUser['num_of_card'] == 1) {
                $configForm->choose_auth_method->setValue(3);
                $configForm->num_of_cards->setValue($deviceUser['num_of_card']);
                $configForm->card_auth_mode->setValue($deviceUser['card_auth_mode']);
                $this->view->hs = $deviceUser['card_template'];
                if ($deviceUser['card_auth_mode'] == 1) {
                    $configForm->card_auth_mode->setValue($deviceUser['fingerprint_auth_mode']);
                }
            }
            $configForm->security_level->setValue($deviceUser['security_level']);
            $configForm->submit->setLabel('Update');
            if ($this->getRequest()->isPost()) {

                if ($this->_request->getParam('num_of_fingers') < 0 && $this->_request->getParam('num_of_fingers') > 10) {
                    $configForm->num_of_fingers->addError('Number of fingers should be between 1 and 10');
                } else {
                    if ($configForm->valid($this->_request->getPost())) {
                        $fingerPrint->delete('empid = ' . $this->_request->getParam('unitId'));
                        $device_ids = $this->_request->getParam('device_id');
                        $device_ids = implode(',', $device_ids);
                        $data = array(
                            'employee_id' => $this->_request->getParam('unitId'),
                            'security_level' => $this->_request->getParam('security_level'),
                            'num_of_fingers' => $this->_request->getParam('num_of_fingers'),
                            'device_id' => $device_ids
                        );
                    
                        $device_user = array(
                            'employee_id' => $this->_request->getParam('unitId'),
                            'device_id' =>  $device_ids,
                            'pin' => $this->_request->getParam('pin'),
                            'fingerprint_template' => $this->_request->getParam('fingerprint_template'),
                            'isDataSynced' => 0
                        );

                        foreach ($this->fingermap as $key => $value) {

                            $fingerprintData = json_decode($this->_request->getParam($value));
                            if ($fingerprintData != '') {
                                Zend_Debug::dump($fingerprintData);

                                if ($fingerprintData->left != '') {
                                    $fdata = array(
                                        'empid' => $this->_request->getParam('unitId'),
                                        'finger' => $key + 5,
                                        'fingerprint_template' => $fingerprintData->left,
                                        'inuse' => 1,
                                        'isactive' => 1
                                    );



                                    $fingerPrint->saveAndUpdateFingerPrintTemplate($fdata, '');
                                }
                                if ($fingerprintData->right != '') {

                                    $fdata = array(
                                        'empid' => $this->_request->getParam('unitId'),
                                        'finger' => $key,
                                        'fingerprint_template' => $fingerprintData->right,
                                        'inuse' => 1,
                                        'isactive' => 1
                                    );

                                    $fingerPrint->saveAndUpdateFingerPrintTemplate($fdata, '');
                                }
                            }
                        }

                        $userConfigModel->saveorupdate($data, $this->_request->getParam('id'));

                        $userDevice->saveAndUpdate($device_user, $this->_request->getParam('id'));
                        $this->view->popup = 'close';
                    }
                }
            }
            $this->view->form = $configForm;

            $this->view->userId = $this->_request->getParam('unitId');
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }


    public function addpopupAction()
    {
        try {
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
            $configForm = new Default_Form_empdevice();
            $deviceModel = new Schedule_Model_Device();
            $fingerPrintModel = new Default_Model_Fingerprint();
            $userConfigModel = new Default_Model_empdevice();
            $userDevice = new Default_Model_Deviceuser();
            $deviceList = $deviceModel->getDevices();
            foreach ($deviceList as $key => $value) {
                $configForm->device_id->addMultiOption($value['device_id'], $value['device_model']);
                $configForm->device_to_scan->addMultiOption($value['device_id'], $value['device_model']);
            }
            if ($this->getRequest()->isPost()) {
                if ($configForm->valid($this->getRequest()->getPost())) {
                    
                    foreach ($this->fingermap as $key => $value) {
                        $fingerprintData = json_decode($this->_request->getParam($value));
                        if ($fingerprintData != '') {
                            

                            if ($fingerprintData->left != '') {
                                $fdata = array(
                                    'empid' => $this->_request->getParam('unitId'),
                                    'finger' => $key + 5,
                                    'fingerprint_template' => $fingerprintData->left,
                                    'inuse' => 1,
                                    'isactive' => 1
                                );

                                $fingerPrintModel->saveAndUpdateFingerPrintTemplate($fdata, '');
                            }
                            if ($fingerprintData->right != '') {

                                $fdata = array(
                                    'empid' => $this->_request->getParam('unitId'),
                                    'finger' => $key,
                                    'fingerprint_template' => $fingerprintData->right,
                                    'inuse' => 1,
                                    'isactive' => 1
                                );

                                $fingerPrintModel->saveAndUpdateFingerPrintTemplate($fdata, '');
                            }
                        }
                    }
                    $device_ids = $this->_request->getParam('device_id');

                    $device_ids = implode(',', $device_ids);

                    $data = array(
                        'employee_id' => $this->_request->getParam('unitId'),
                        'security_level' => $this->_request->getParam('security_level'),
                        'device_id' => $device_ids
                    );
                    $device_user = array(
                        'employee_id' => $this->_request->getParam('unitId'),
                        'device_id' => $device_ids,
                    );

                    if ($this->_request->getParam('choose_auth_method') != null) {
                        $authChoose = $this->_request->getParam('choose_auth_method');
                        foreach ($authChoose as $value) {
                            if ($value == 1) {
                                $data['num_of_fingers'] = $this->_request->getParam('num_of_fingers');
                                
                                $data['fingerprint_auth_mode'] = $this->_request->getParam('finger_auth_mode');
                                if ($this->_request->getParam('finger_auth_mode') == 1) {
                                    $device_user['pin'] = $this->_request->getParam('pin');
                                }
                                $device_user['fingerprint_template'] = $this->_request->getParam('hs');
                            }
                            if ($value == 2) {
                                $data['num_of_faces'] = $this->_request->getParam('num_of_faces');

                                $device_user['face_template'] = $this->_request->getParam('hs');
                                if ($this->_request->getParam('face_auth_mode') == 1) {
                                    $device_user['pin'] = $this->_request->getParam('pin');
                                }
                                $data['id_auth_mode'] = $this->_request->getParam('id_auth_mode');
                            }
                            if ($value == 3) {
                                $data['num_of_card'] = $this->_request->getParam('num_of_cards');
                                $data['card_auth_mode'] = $this->_request->getParam('card_auth_mode');
                                if ($this->_request->getParam('card_auth_mode') == 1) {
                                    $device_user['pin'] = $this->_request->getParam('pin');
                                }
                            }
                        }
                    }
                    //$fingerPrintModel->saveAndUpdateFingerPrintTemplate($fingerprint_data, '');
                    $id = $userConfigModel->saveorupdate($data, '');
                    $controllername = 'empdevice';
                    $this->view->controllername = $controllername;
                    $this->view->popup = 'close';
                    $device_user['config_id'] = $id;
                    $userDevice->saveAndUpdate($device_user, '');
                }
            }
            $this->view->form = $configForm;
            $this->view->userId = $this->_request->getParam('unitId');
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }

    public function filterdeviceAction()
    {
        try {
            $authType = $this->_request->getParam('auth_type');
            $deviceModel = new Schedule_Model_Device();
            $deviceList = $deviceModel->filterDevice($authType);
            $opt = "";
            foreach ($deviceList as $value) {
                //$deviceForm->device_id->addMultiOption($value['device_id'], $value['device_model']);
                $opt = sapp_Global::selectOptionBuilder($value['device_id'], $value['device_model']);
            }
            echo $opt;

            die();
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
            die();
        }
    }

    public function delete()
    {
        try {
            $id = $this->_request->getParam('id');
            $empId = $this->_request->getParam('unitId');
            $userConfigModel = new Default_Model_empdevice();
            $fingerPrintModel = new Default_Model_Fingerprint();
            $userConfigModel->delete($id);
            $fingerPrintModel->delete('empid = ' . $empId);
            Zend_Debug::dump($empId);
            
            $option = [
                'http' => [
                    'header'=>'Content-type: application/json',
                    'method' => 'DELETE',
                    'content' => http_build_query($id)
                ]
                ];
            $context = stream_context_create($option);
            $result = file_get_contents(DEVICE_API_URL . 'ConnectDevice/deleteuser?empId=' . $id, false, $context);
            if($result)
            {
                $this->_helper->flashMessenger->addMessage('Record Deleted Successfully');
                $this->_redirect('/empdevice/edit/userid/' . $this->_request->getParam('unitId'));
            }
            else{
                $this->_helper->flashMessenger->addMessage('Record Not Deleted');
                $this->_redirect('/empdevice/edit/userid/' . $this->_request->getParam('unitId'));
            }
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
}
