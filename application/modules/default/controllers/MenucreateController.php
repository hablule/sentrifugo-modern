<?php
require_once 'application/modules/default/library/utilClasses/Util.php';
class Default_MenucreateController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...

            $loadmodel = new Default_Model_Menucreate();
            $inter_options = array();
            $call = $this->_getParam('call');
            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }
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
                $by = 'mm.idid';
                $pageNo = 1;
                $searchData = '';
                $searchQuery = '';
                $sort2 = 'DESC';
                $searchArray = array();
                $searchArray2 = array();
            } else {
                $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';

                $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'mm.id';
                if ($dashboardcall == 'Yes')
                    $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
                else $perPage = $this->_getParam('per_page', PERPAGE);
                $searchData = $this->_getParam('searchData');
                $pageNo = $this->_getParam('page', 1);
            }

            $dataTmp = $loadmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);
            $this->view->dataArray = $dataTmp;

            array_push($data, $dataTmp);
            $this->view->call = $call;
            $this->view->objectName = $objname;
            $this->view->messages = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function addAction()
    {
        $Menucreate_Form = new Default_Form_Menucreate();
        $Menucrete_Model = new Default_Model_Menucreate();
        $msgarray = [];
        $path = __DIR__;
        $path = substr($path, 0, strpos($path, 'default\\'));

        $modules = array_filter(glob($path . '*'), 'is_dir');
        if (!empty($modules)) {
            $Menucreate_Form->module_name->addMultiOption('', 'Select...');
            foreach ($modules as $module) {
                $module = str_replace($path, '', $module);
                $Menucreate_Form->module_name->addMultiOption($module, $module);
            }
        } else {
            $Menucreate_Form->module_name->addMultiOption('empty', 'Select...');
            $msgarray['module_name'] = 'No Module Found!';
        }

        $menus = $Menucrete_Model->getAllMenus();
        $roles = $Menucrete_Model->getroles();
        $Menucreate_Form->role->addMultiOption('', "Select...");
        $Menucreate_Form->parent->addMultiOption(0, "Select...");
        if (empty($menus)) {
            $msgarray['parent'] = "No Active Menu Found!";
        } else {
            foreach ($menus as $menu) {
                $Menucreate_Form->parent->addMultiOption($menu['id'], $menu['menuName']);
            }
        }

        if (empty($roles)) {
            $msgarray['role'] = "No Active Role Found!";
        } else {
            foreach ($roles as $role) {
                $Menucreate_Form->role->addMultiOption($role['id'], $role['name']);
            }
        }

        if ($this->getRequest()->getPost()) {
            $msgarray = $this->save($Menucreate_Form, 'add');
        }

        $this->view->form = $Menucreate_Form;
        $this->view->msgarray = $msgarray;
    }
    public function editAction()
    {
        try {

            $Menucreate_Form = new Default_Form_Menucreate();
            $menumodel = new Default_Model_Menucreate();
            $menus = $menumodel->getAllMenus();
            $msgarray = [];
            $Menucreate_Form->role->addMultiOption('', "Select...");
            $Menucreate_Form->parent->addMultiOption(0, "Select...");
            foreach ($menus as $menu) {
                $Menucreate_Form->parent->addMultiOption($menu['id'], $menu['menuName']);
            }
            $roles = $menumodel->getroles();
            $msgarray = [];
            foreach ($roles as $role) {
                $Menucreate_Form->role->addMultiOption($role['id'], $role['name']);
            }


            $Menucreate_Form->setAction('menucreate/edit/id/' . $this->_getParam('id'));
            $menu = $menumodel->getmenubyid($this->_getParam('id'));
            $Menucreate_Form->menu_name->setValue($menu['menuName']);
            $Menucreate_Form->url->setValue($menu['url']);
            $Menucreate_Form->isactive->setValue($menu['isactive']);

            $Menucreate_Form->parent->setValue($menu['parent']);
            $priv = $menumodel->getmenupriv($menu['id']);
            $Menucreate_Form->role->setValue($priv['role']);

            $Menucreate_Form->add->setValue(utlis::getcheckboxvalue($priv['addpermission'], 'longform'));
            $Menucreate_Form->edit->setValue(utlis::getcheckboxvalue($priv['editpermission'], 'longform'));
            $Menucreate_Form->delete->setValue(utlis::getcheckboxvalue($priv['deletepermission'], 'longform'));
            $Menucreate_Form->view->setValue(utlis::getcheckboxvalue($priv['viewpermission'], 'longform'));
            $Menucreate_Form->uploadatt->setValue(utlis::getcheckboxvalue($priv['uploadattachments'], 'longform'));
            $Menucreate_Form->viewatt->setValue(utlis::getcheckboxvalue($priv['viewattachments'], 'longform'));
            $Menucreate_Form->submit->setLabel('Update');
            if ($this->getRequest()->isPost()) {
                $msgarray = $this->save($Menucreate_Form, 'edit');
            }
            $this->view->form = $Menucreate_Form;
            $this->view->msgarray = $msgarray;
            //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function viewAction()
    {
        $Menucreate_Form = new Default_Form_Menucreate();
        $this->view->form = $Menucreate_Form;
    }


    public function save($Menucreate_Form, $type)
    {
        try {
          
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }

        if ($Menucreate_Form->isValid($this->_request->getPost())) {
            $id = $this->getRequest()->getParam('id');
            $Menucreate_Model = new Default_Model_Menucreate();
            $rolemodel = new Default_Model_Roles();

            $menuName = $this->getRequest()->getParam('menu_name');
            $modulename = $this->getRequest()->getParam('module_name');
            $role = $this->getRequest()->getParam('role');
            $groupdata = $rolemodel->getRoleDataById($role);
            $group_id = $groupdata['group_id'];

            $url = $this->getRequest()->getParam('url');
            if ($type == "edit") {
                $isactive = $this->getRequest()->getParam('isactive');
            } else {
                $isactive = 1;
            }

            $parent = $this->getRequest()->getParam('parent');
            if ($url != '') {

                if (strpos($url, '/') . "" == '') {
                    $url = "/" . $url;
                }
            } else {
                $url = "/#";
            }
            $data = [
                'menuName' => $menuName,
                'url' => $url,
                'parent' => $parent,
                'menuOrder' => 1,
                'isactive' => $isactive,
                'modulename' => $modulename,
                'segment_flag' => 2,
            ];

            $permission_data = [
                'role' => $this->getRequest()->getParam('role'),
                'addpermission' => utlis::getcheckboxvalue($this->getRequest()->getParam('add'), 'longdatabase'),
                'editpermission' => utlis::getcheckboxvalue($this->getRequest()->getParam('edit'), 'longdatabase'),
                'deletepermission' => utlis::getcheckboxvalue($this->getRequest()->getParam('delete'), 'longdatabase'),
                'viewpermission' => utlis::getcheckboxvalue($this->getRequest()->getParam('view'), 'longdatabase'),
                'viewattachments' => utlis::getcheckboxvalue($this->getRequest()->getParam('viewatt'), 'longdatabase'),
                'uploadattachments' => utlis::getcheckboxvalue($this->getRequest()->getParam('uploadatt'), 'longdatabase'),
                'createdby' => $loginUserId,
                'createddate' => gmdate("Y-m-d H:i:s")
            ];

            $group_data = [
                'group_id' => $group_id,
                'addpermission' => utlis::getcheckboxvalue($this->getRequest()->getParam('add'), 'longdatabase'),
                'editpermission' => utlis::getcheckboxvalue($this->getRequest()->getParam('edit'), 'longdatabase'),
                'deletepermission' => utlis::getcheckboxvalue($this->getRequest()->getParam('delete'), 'longdatabase'),
                'viewpermission' => utlis::getcheckboxvalue($this->getRequest()->getParam('view'), 'longdatabase'),
                'viewattachments' => utlis::getcheckboxvalue($this->getRequest()->getParam('viewatt'), 'longdatabase'),
                'uploadattachments' => utlis::getcheckboxvalue($this->getRequest()->getParam('uploadatt'), 'longdatabase'),
                'createdby' => $loginUserId,
                'createddate' => gmdate("Y-m-d H:i:s")
            ];


            if ($id) {
                $where = 'id=' . $id;
                $permission_where = 'object=' . $id;
                $group_where = 'object=' . $id;

                Zend_Debug::dump($where, 'menu where');
                Zend_Debug::dump($permission_where, 'permission where');
            } else {
                $where = '';
            }

            // die(json_encode($permission_data));
            $result = $Menucreate_Model->SaveorUpdateData($data, $where, $permission_data, $permission_where, $group_data, $group_where);
            if ($result == "update") {
                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Menu  updated successfully."));
            } else {
                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => "Menu  addedd successfully."));
            }

            $this->_redirect('menucreate');
        } else {
            $messages = $Menucreate_Form->getMessages();

            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgarray[$key] = $val2;
                    break;
                }
            }
            return $msgarray;
        }
        $this->_redirect('menucreate');
          //code...
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
        }
    }
    public function deleteAction()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }

        $id = $this->_request->getParam('objid');
        $deleteflag = $this->_request->getParam('deleteflag');
        $messages['message'] = '';
        $messages['msgtype'] = "";
        $actionflag = 3;
        if ($id) {
            $model = new Default_Model_Menucreate();

            $data = array('isactive' => 0);
            $where = array('id=?' => $id);
            $Id = $model->DeleteData($data, $where);
            if ($Id == 'update') {
                $messages['message'] = 'Menu deleted successfully.';
                $messages['msgtype'] = 'success';
            } else {
                $messages['message'] = 'Menu cannot be deleted.';
                $messages['msgtype'] = 'error';
            }
        } else {
            $messages['message'] = 'Menu be deleted.';
            $messages['msgtype'] = 'error';
        }
        if ($deleteflag == 1) {
            if ($messages['msgtype'] == 'error') {
                $this->_helper->getHelper("FlashMessenger")->addMessage(array("error" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
            }
            if ($messages['msgtype'] == 'success') {
                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag));
            }
        }
        $this->_helper->json($messages);
    } // end of delete action
}
