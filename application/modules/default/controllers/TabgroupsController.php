<?php

use LDAP\Result;

class Default_TabgroupsController extends Zend_Controller_Action
{
    public function preDispatch()
    {
        $auth = Zend_Auth::getInstance();
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                echo Zend_Json::encode(['login' => 'failed']);
                die();
            } else {
                $this->_redirect('');
            }
        }
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getgroupmenugroup', 'html')->initContext();
    }
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }
    public function indexAction()
    {
        try {
            //code...
            $model = new Default_Model_Tabgroups();
            $call = $this->_getParam('call');

            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $refresh = $this->_getParam('refresh');
            $dashboradcall = $this->_getParam('dashboradcall');

            $data = [];

            if ($refresh == 'refresh') {
                if ($dashboradcall == 'Yes') {
                    $perPage = DASHBOARD_PERPAGE;
                } else {
                    $perPage = PERPAGE;
                }
                $sort = 'DESC';
                $by = 'name';
                $pageNo = 1;
                $searchData = '';
            } else {
                $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
                $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'name';
                if ($dashboradcall == 'Yes')
                    $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
                else
                    $perPage = $this->_getParam('per_page', PERPAGE);

                $pageNo = $this->_getParam('page', 1);
                $searchData = $this->_getParam('searchData');
                $searchData = rtrim($searchData, ',');
            }
            $dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboradcall);

            array_push($data, $dataTmp);
            $this->view->dataArray = $data;
            $this->view->call = $call;
            $this->view->messages = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    // public function addAction()
    // {
    //     try{
    //     $form = new Default_Form_Groups();
    //     $model = new Default_Model_Groups();
    //     $auth = Zend_Auth::getInstance();
    //     if ($auth->hasIdentity()) {
    //         $loginUserId = $auth->getStorage()->read()->id;
    //     }

    //     if ($this->getRequest()->isPost()) {
    //         if ($form->isValid($this->getRequest()->getPost())) {
    //             $data = array(
    //                 'name'=>$this->_request->getParam('name'),
    //                 'code'=>$this->_request->getParam('code'),
    //                 'description'=>$this->_request->getParam('description'),
    //                 'createdby'=>$loginUserId,
    //                 'created'=>gmdate('Y-m-d H:i:s')

    //             );
    //             $model->SaveorUpdateGroupsData($data,'');
    //             $this->_helper->getHelper("FlashMessenger")->addMessage(array('success'=>'Groups Added Successful'));
    //             $this->_redirect('groups');
    //         }
    //         $messages = $form->getMessages();
    //         foreach ($messages as $key => $val) {
    //             foreach ($val as $key2 => $val2) {
    //                 $msgArray[$key] = $val2;
    //                 break;
    //             }
    //         }
    //     }
    //     $this->view->msgarray = $msgArray;
    //     $this->view->form = $form; 
    // } catch (\Throwable $th) {
    //     Zend_Debug::dump($th->getMessage());
    // }
    // }

    public function addAction()
    {
        try {
            //code...
            $util = new utlis();

            $groups_model = new Default_Model_Groups();
            $role_model = new Default_Model_Roles();

            $group_arr = $groups_model->getGroupsListForRoles();

            $id = $this->getRequest()->getParam('id', null);
            $display_grp = "1";
            $total_role_cnt = 0;

            $callval = $this->getRequest()->getParam('call');
            if ($callval == 'ajaxcall')
                $this->_helper->layout->disableLayout();

            $form = new Default_Form_Tabgroups();
            $data = [];

            if ($id && is_numeric($id) && $id > 1) {

                try {
                    $id = (int) $id;
                    $id = abs($id);
                    $form->submit->setLabel('Update');
                    $data = $role_model->getGroupDataById($id);
                    // die(json_encode($data));            
                    if (count($data) > 0) {
                        foreach ($data as $key => $val) {
                            $data[$key] = htmlentities(addslashes($val), ENT_QUOTES, "UTF-8");
                        }
                        Zend_Debug::dump($data);
                        $form->populate($data);

                        Zend_Debug::dump($form->getValues());
                        $form->isactive->setValue($util::getcheckboxvalue($data['isactive'], 'form'));
                        //$form->levelid->setValue($data['levelid']);

                    }
                } catch (Exception $e) {
                    $this->view->nodata = "nodata";
                }
            } else if ($id == '') {
                // $total_role_cnt = $role_model->getTotalRolecnt();
                // foreach($group_arr as $gid => $gdata)
                // {
                //     if($gdata['cnt'] <5)
                //     {
                //         $display_grp = $gid;
                //         break;
                //     }
                // }

            } else {
                $this->view->nodata = "nodata";
            }

            //  Zend_Debug::dump($total_role_cnt);
            //  die();
            $this->view->form = $form;
            $this->view->group_arr = $group_arr;
            $this->view->data = $data;
            // die(json_encode($display_grp));
            // $this->view->display_grp = $display_grp;
            // $this->view->total_role_cnt = $total_role_cnt;
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function viewAction()
    {
        try {
            //code...

            $groups_model = new Default_Model_Groups();
            $group_arr = $groups_model->getGroupsListForRolesview();
            $id = (int)$this->getRequest()->getParam('id');
            $id = abs($id);
            $callval = $this->getRequest()->getParam('call');
            if ($callval == 'ajaxcall')
                $this->_helper->layout->disableLayout();
            $objName = 'groups';
            $form = new Default_Form_Roles();
            $form->removeElement("submit");
            $elements = $form->getElements();
            if (count($elements) > 0) {
                foreach ($elements as $key => $element) {
                    if (($key != "Cancel") && ($key != "Edit") && ($key != "Delete") && ($key != "Attachments")) {
                        $element->setAttrib("disabled", "disabled");
                    }
                }
            }
            $roles_model = new Default_Model_Roles();
            try {
                if ($id && is_numeric($id) && $id > 1) {
                    $data = $roles_model->getGroupDataById($id);
                    if (count($data) > 0) {
                        foreach ($data as $key => $val) {
                            $data[$key] = htmlentities(addslashes($val), ENT_QUOTES, "UTF-8");
                        }
                        $form->populate($data);
                        $this->view->data = $data;
                    } else {
                        $this->view->nodata = "nodata";
                    }
                } else {
                    $this->view->nodata = "nodata";
                }
            } catch (Exception $e) {
                $this->view->nodata = "nodata";
            }
            $this->view->controllername = $objName;
            $this->view->id = $id;
            $this->view->active = $data['activename'];

            $this->view->form = $form;
            $this->view->group_arr = $group_arr;
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function editAction()
    {
        try {
            //code...
            $util = new utlis();

            $groups_model = new Default_Model_Tabgroups();
            $role_model = new Default_Model_Roles();

            $group_arr = $groups_model->getGroupsListForRoles();

            $id = $this->getRequest()->getParam('id', null);
            $display_grp = "1";
            $total_role_cnt = 0;

            $callval = $this->getRequest()->getParam('call');
            if ($callval == 'ajaxcall')
                $this->_helper->layout->disableLayout();

            $form = new Default_Form_Tabgroups();
            $data = [];

            if ($id && is_numeric($id) && $id > 1) {

                try {
                    $id = (int) $id;
                    $id = abs($id);
                    $form->submit->setLabel('Update');
                    $data = $role_model->getGroupDataById($id);
                    // die(json_encode($data));            
                    if (count($data) > 0) {
                        foreach ($data as $key => $val) {
                            $data[$key] = htmlentities(addslashes($val), ENT_QUOTES, "UTF-8");
                        }
                        $form->populate($data);


                        $form->isactive->setValue($util::getcheckboxvalue($data['isactive'], 'form'));
                        //$form->levelid->setValue($data['levelid']);

                    }
                } catch (Exception $e) {
                    $this->view->nodata = "nodata";
                }
            } else if ($id == '') {
                // $total_role_cnt = $role_model->getTotalRolecnt();
                // foreach($group_arr as $gid => $gdata)
                // {
                //     if($gdata['cnt'] <5)
                //     {
                //         $display_grp = $gid;
                //         break;
                //     }
                // }

            } else {
                $this->view->nodata = "nodata";
            }

            //  Zend_Debug::dump($total_role_cnt);
            //  die();
            $this->view->form = $form;
            $this->view->group_arr = $group_arr;
            $this->view->data = $data;
            // die(json_encode($display_grp));
            // $this->view->display_grp = $display_grp;
            // $this->view->total_role_cnt = $total_role_cnt;
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }


    public function saveupdateAction()
    {
        try {
            //code...
            $util = new utlis();

            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
                $sess_values = $auth->getStorage()->read();
            }

            $role_form = new Default_Form_Tabgroups();
            $groups_model = new Default_Model_Tabgroups();
            $previlege_model = new Default_Model_Tabprivilage();
            $menumodel = new Default_Model_Menu();
            $messages = $role_form->getMessages();
            $actionflag = '';
            $tableid  = '';

            if ($this->getRequest()->getPost()) {
               
                if ($role_form->isValid($this->_request->getPost())) {
                    $trDb = Zend_Db_Table::getDefaultAdapter();
                    $trDb->beginTransaction();
                    try {

                        $id = $this->_request->getParam('id');


                        $group_id = $this->_request->getParam('hidgroup_value');
                        $chk_menu = $this->_request->getParam("chk_menu");
                        foreach ($chk_menu as $key => $value)
                            $chk_menu_val = $value;

                            $tableid = $id;

                            $menu_data_post = $this->_request->getParam('menu_data_post');

                                $add_arr = array_diff($chk_menu_val, $menu_data_post);
                                $del_arr = array_diff($menu_data_post, $chk_menu_val);
                                $update_arr =  array_diff($chk_menu_val, $add_arr);
                                foreach ($del_arr as $key => $value) {

                                    $delete_prev_data = [
                                        'modifiedby' => $loginUserId,
                                        'modifieddate' => gmdate("Y-m-d H:i:s"),
                                        'isactive' => 'N',
                                    ];
                                    $where_delete = "pagetabprivilage_id = " . $key;

                                    $previlege_model->SaveorUpdatePrivilegesData($delete_prev_data, $where_delete);
                                }
if($chk_menu_val==NULL  && $menu_data_post!=NULL){

    foreach ($menu_data_post as $key => $value) {
        $menu_data_data = [
            'modifiedby' => $loginUserId,
            'modifieddate' => gmdate("Y-m-d H:i:s"),
            'isactive' => 'N',
        ];
        $where_data = "pagetabprivilage_id = " . $key;
        $previlege_model->SaveorUpdatePrivilegesData($menu_data_data, $where_data);
    }

}
if($chk_menu_val!=NULL  && $menu_data_post==NULL){

    foreach ($chk_menu_val as $key => $value) {
      
        $prev_data = [
            'createdby' => $loginUserId,
            'modifiedby' => $loginUserId,
            'createddate' => gmdate("Y-m-d H:i:s"),
            'modifieddate' => gmdate("Y-m-d H:i:s"),
            'isactive' => 'Y',
            'canadd' => utlis::getYesno($this->_request->getParam('rd_addpermission' . $value)),
            'canedit' =>utlis::getYesno($this->_request->getParam('rd_editpermission' . $value)),
            'candelete' => utlis::getYesno($this->_request->getParam('rd_deletepermission' . $value)),
            'canview' => utlis::getYesno($this->_request->getParam('rd_viewpermission' . $value)),

        ];
        $update_where = "pagetabprivilage_id=".$value."";
        $previlege_model->SaveorUpdatePrivilegesData($prev_data,$update_where);
    }

}


                                foreach ($update_arr as $key => $value) {

                                    $prev_update_data = [
                                        'modifiedby' => $loginUserId,
                                        'modifieddate' => gmdate("Y-m-d H:i:s"),
                                        'canadd' => utlis::getYesno($this->_request->getParam('rd_addpermission' . $value)),
                                        'canedit' =>utlis::getYesno($this->_request->getParam('rd_editpermission' . $value)),
                                        'candelete' => utlis::getYesno($this->_request->getParam('rd_deletepermission' . $value)),
                                        'canview' => utlis::getYesno($this->_request->getParam('rd_viewpermission' . $value)),
                                    ];
                                    $update_where = "isactive ='Y' and pagetab_id = " . $value . " and  group_id= ".$id."";

                                     $previlege_model->SaveorUpdatePrivilegesData($prev_update_data, $update_where);
                                }
                                foreach ($add_arr as $key => $value) {
                                    $prev_data = [
                                        'createdby' => $loginUserId,
                                        'modifiedby' => $loginUserId,
                                        'createddate' => gmdate("Y-m-d H:i:s"),
                                        'modifieddate' => gmdate("Y-m-d H:i:s"),
                                        'isactive' => 'Y',
                                        'pagetab_id' => $value,
                                        'group_id' => $id,
                                        'canadd' => utlis::getYesno($this->_request->getParam('rd_addpermission' . $value)),
                                        'canedit' =>utlis::getYesno($this->_request->getParam('rd_editpermission' . $value)),
                                        'candelete' => utlis::getYesno($this->_request->getParam('rd_deletepermission' . $value)),
                                        'canview' => utlis::getYesno($this->_request->getParam('rd_viewpermission' . $value)),

                                    ];

                                    $previlege_model->SaveorUpdatePrivilegesData($prev_data, '');
                                }

                                //sapp_Global::generateAccessControl();

                          
                            //start of mailing 
                            //$this->mailing_function($sess_values,$name,'update',$groups_model);
                            //end of mailing
                       
                        // $objidArr = $menumodel->getMenuObjID('/tabgroups');
                        // $objID = $objidArr[0]['id'];
                        // $result = sapp_Global::logManager($objID, $actionflag, $loginUserId, $tableid);
                        $messages['result'] = 'saved';
                        $messages['nomessage'] = 'no';
                        if (0 == 1)
                            $messages['message'] = ' Group[ added successfully.';
                        else
                            $messages['message'] = 'Group updated successfully.';
                        $_SESSION['role_msg'] = $messages['message'];
                        $_SESSION['role_cls'] = 'success';
                        $trDb->commit();
                        $this->_helper->json($messages);
                        
                    } catch (Exception $e) {
                        Zend_Debug::dump($e->getFile());


                        $trDb->rollBack();

                        $messages['result'] = 'exception';
                        $_SESSION['role_cls'] = 'error';
                        $messages['message'] = 'Something went wrong, please try again later.';
                        $_SESSION['role_msg'] = $messages['message'];
                        $this->_helper->json($messages);
                    }
                    $this->_redirect('groups');
                } //end of valid if
                else {
                    $messages = $role_form->getMessages();
                    $messages['result'] = 'error';
                    $this->_helper->json($messages);
                }
            }
        } catch (\Throwable $th) {
            // Zend_Debug::dump($th->getMessage()); 
        }
    }
    public function getgroupmenugroupAction()
    {
      /*   try { */
            //code...
     
        $role_id = $this->_getParam('id', null);
        $group_id = $this->_getParam('group_id', null);
        $id = $this->_getParam('pri_val', null);
        $disabled = $this->_getParam('disabled', '');
        $menu_model = new Default_Model_Tab();
        $menu_data = $menu_model->getgroupmenuforgroup($id, $role_id, '');
        // die(json_encode($menu_data['permission_data']));
        $active_menus = $menu_model->getisactivemenus();
        $act_menus = [];
        if (!empty($active_menus)) {
            foreach ($active_menus as $act) {
                $act_menus[$act['pagetab_id']] = $act;
            }
        }
        // die(json_encode($menu_data['menu_data_post']));
        $this->view->act_menus = $act_menus;
        $this->view->menu_arr = $menu_data['tmpArr'];
        $this->view->menu_data_post = $menu_data['menu_data_post'];
        $this->view->menu_data = $menu_data['menu_data'];
        $this->view->permission_data = $menu_data['permission_data'];
        $this->view->group_level = $menu_data['group_data']['level'];
        $this->view->disabled = $disabled;
        $this->view->group_id = $group_id;
       
        // Zend_Debug::dump($menu_data);
    /* } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());        }
    }  */
}


}
