<?php
class Default_PerkcategoryController extends Zend_Controller_Action
{
    public function preDispatch()
    {
    }
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }

    public function indexAction()
    {

        $pcatModel = new Default_Model_Perkcategory();
        $call = $this->_getParam('call');

        if ($call == 'ajaxcall') {
            $this->_helper->layout->disableLayout();
        }

        $view = Zend_Layout::getMvcInstance()->getView();
        $objname = $this->_getParam('objname');
        $refresh = $this->_getParam('refresh');
        $dashboardcall = $this->_getParam('dashboardcall');

        $data = [];
        $searchQuery = '';
        $searchArray = [];
        $tablecontent = '';

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes')
                $perPage = DASHBOARD_PERPAGE;
            else
                $perPage = PERPAGE;
            $sort = 'DESC';
            $by = 'name';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $searchArray = '';
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'name';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else
                $perPage = $this->_getParam('per_page', PERPAGE);

            $pageNo = $this->_getParam('page', 1);

            $searchData = $this->_getParam('searchData');
            $searchData = rtrim($searchData, ',');
        }

        $dataTmp = $pcatModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);

        array_push($data, $dataTmp);
        $this->view->dataArray = $data;
        $this->view->call = $call;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }


    public function  addAction()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }

        $form = new Default_Form_perkcategory();
        $formModel = new Default_Model_Perkcategory();
        $form->setAction(BASE_URL . 'perkcategory/add');
        if ($this->getRequest()->isPost()) {

            if ($form->isValid($this->getRequest()->getPost())) {

                $data = [
                    'name' => $this->_request->getParam('name_cat'),
                    'description' => $this->_request->getParam('description_cat'),
                    'isactive' => 'Yes',
                    'createdby' => $loginUserId,
                    'createddate' => gmdate("Y-m-d H:i:s"),

                ];
                //  die(json_encode($data));
                $tableid = $formModel->addCategory($data);
                $result = sapp_Global::logManager(PERKCATEGORY, 1, $loginUserId, $tableid);
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->_helper->getHelper("FlashMessenger")->addMessage(["success" => "Perk category added successfully."]);
                $this->_redirect('perkcategory');
            }
            $messages = $form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        }

        $this->view->msgarray = $msgArray;
        $this->view->form = $form;
    }

    public function editAction()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }

        $id = $this->getRequest()->getParam('id');
        $form = new Default_Form_perkcategory();
        $formModel = new Default_Model_Perkcategory();
        $form->setAttrib('action', BASE_URL . 'perkcategory/edit/id/' . $id);
        $perkData = $formModel->getSinglePerkcategory($id);
        $form->name_cat->setValue($perkData[0]['name']);
        $form->description_cat->setValue($perkData[0]['description']);
        $form->submit_cat->setLabel('Update');
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->form = $form;
        $newelement = new addformelement();
        $actionflag = 2;
        $form->addElement($newelement->addelement('checkbox', 'isactive', $perkData[0], 'isactive'));
        if ($this->getRequest()->getPost()) {
            //die(json_encode($this->getRequest()->getPost()));
            $where = ['perkcategory_id=?' => $this->getRequest()->getParam('id')];
            $data = [
                'name' => $this->getRequest()->getParam('name_cat'),
                'description' => $this->getRequest()->getParam('description_cat'),
                'modifiedby' => $loginUserId,
                'modifieddate' => gmdate("Y-m-d H:i:s"),
                'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive'), 'database')
            ];
            $tableid = $this->_getParam('id');
            $formModel->editPerkcategory($data, $where);
            $menuID = PERKCATEGORY;
            $result = sapp_Global::logManager($menuID, $actionflag, $loginUserId, $tableid);
            $this->_helper->getHelper("FlashMessenger")->addMessage(["success" => "Perk Category Updated successfully."]);

            $this->_redirect('perkcategory');
        }
    }
    public function deleteAction()
    {
        $formModel = new Schedule_Model_Timeschedule();

        $id = $this->_request->getParam('objid');
        $where = 'perkcategory_id =' . (int)$id;
        $data = [
            'isactive' => 'N'
        ];
        //die(json_encode($where));
        $res = $formModel->deleteData($where, 'main_perkcategory', 'perkcategory_id');

        //$res=$formModel->editPerkcategory($data, $where);

        if ($res > 0)
            return $this->_helper->json(['flagtype' => 'process', 'message' => 'Deleted']);
        die();
    }

    public function viewAction()
    {
        // $id = (int)$this->getRequest()->getParam('perk_id');
        // $form = new Default_Form_perk();
        // $formModel = new Default_Model_Perk();
        // $form->setAttrib('action', BASE_URL . 'addperk');
        // $perk = $formModel->getPerkDetail($id);
        // $form->back->setLabel('Back');
        // $this->view->perks = $perk;
        // $this->view->form = $form;



    }
}
