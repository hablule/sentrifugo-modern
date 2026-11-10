<?php

class Default_DoctypeController extends Zend_Controller_Action
{
    private $actionadd = 1;
    private $actionupdate = 2;
    private $actiondelete = 3;
    private $messages;
   
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';

    }
    public function indexAction()
    {
        $doctype_model = new Default_Model_Doctype();
        $call = $this->_getParam('call');
        if ($call == 'ajaxcall') {
            $this->_helper->layout->disableLayout();
        }
        $dashboardcall = $this->_getParam('dashboardcall');
        $refresh = $this->_getParam('refresh');
        $data = [];

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes') {
                $perPage = DASHBOARD_PERPAGE;
            } else {
                $perPage = PERPAGE;
            }
            $sort = 'ASC';
            $by = 'dt.name';
            $pageNo = 1;
            $searchData = '';
           
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'ASC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'dt.name';
            if ($dashboardcall == 'Yes') {
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            } else {
                $perPage = $this->_getParam('per_page', PERPAGE);
            }
            $pageNo = $this->_getParam('page', 1);
            $searchData = $this->_getParam('searchData');
           
            $searchData = rtrim($searchData, ',');
           
        }

        $dataTmp = $doctype_model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);
        array_push($data, $dataTmp);

        $this->view->dataArray = $data;

        $this->view->call = $call;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    public function addAction()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginid = $auth->getStorage()->read()->id;
        }
        $Doctype_Form = new Default_Form_Doctype();
        $msgarray = [];
        if ($this->getRequest()->getPost()) {
            $msgarray = $this->save($Doctype_Form);
            
        }
        $this->view->form = $Doctype_Form;
        $this->view->msgarray = $msgarray;
    }
    public function editAction()
    {

        $Doctype_Form = new Default_Form_Doctype();
        $Doctype_Model = new Default_Model_Doctype();
        $id = $this->getRequest()->getParam('id');
        $Doctype_data = $Doctype_Model->getSingleDoctype($id);
        $Doctype_Form->name->removeValidator('Db_NoRecordExists');

        foreach($Doctype_data as $data){
            $Doctype_Form->name->setValue($data['name']);
            $Doctype_Form->description->setValue($data['description']);
            $Doctype_Form->isactive->setValue(utlis::getcheckboxvalue($data['isactive'],'form'));

        }

        if($this->getRequest()->isPost())
        {
            if ($Doctype_Form->isValid($this->getRequest()->getPost())) {

                $this->save($Doctype_Form);
            }
        }
        $this->view->form = $Doctype_Form;
    }
    public function viewAction()
    {
        $Doctype_Form = new Default_Form_Doctype();
        $this->view->form = $Doctype_Form;
    }
    public function deleteAction(){
        if (isset($_POST['objid'])) {
            $objId = (int)$_POST['objid'];
            $Doctype_Model = new Schedule_Model_Timeschedule();
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }
            $flag = $this->actiondelete;
            $data['isactive'] = 'N';
            $where = "doctype_id=". $objId;
            $Doctype_Model->deleteData($where,'main_doctype','doctype_id');
            //$Doctype_Model->SaveorUpdateData($data,$where);
            $menuID = PERIODCONTROL;

            sapp_Global::logManager($menuID,$flag,$loginUserId,$objId);
            $response = [
                'message' => 'Document Type Successfully Removed.',
                'msgtype' => 'success'
            ];
            die(json_encode($response));
        } else {
            
        }
        
    }

    public function save($Doctype_Form)
    {
        try {
            //code...
            $util = new utlis();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        if ($Doctype_Form->isValid($this->_request->getPost())) {
            $Doctype_Model = new Default_Model_Doctype();
            $id = $this->getRequest()->getParam('id');
            $data = [];
            $where = '';
            $data['name'] = $this->getRequest()->getParam('name');
            $data['description'] = $this->getRequest()->getParam('description');
            $data['isactive'] =$util::getcheckboxvalue($this->_request->getParam('isactive'),'database');

            if ($id) {
                $data['modifieddate'] = gmdate("Y-m-d H:i:s");
                $data['modifiedby'] = $loginUserId;
                $where = "doctype_id=".$id;
            } else {
                $data['createddate'] = gmdate("Y-m-d H:i:s");
                $data['createdby'] = $loginUserId;
            }

            $Id = $Doctype_Model->SaveorUpdateData($data,$where);
            if($Id == 'update')
			{
                $flag = $this->actionupdate ;
				$tableid = $id;

				$this->messages = '';
                $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Document type updated successfully."]);
			}
			else
			{
                $flag = $this->actionadd;
				$tableid = $Id;
				$this->messages = '';	
                $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Document type added successfully."]);		}
            $menuID = DOCUMENTTYPE;
            sapp_Global::logManager($menuID,$flag,$loginUserId,$tableid);
            $this->_redirect('doctype/index');
        } else {
            $messages = $Doctype_Form->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgarray[$key] = $val2;
                    break;
                }
            }
            return $msgarray;
        }
    } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage()) ;   }
    }

    
}
