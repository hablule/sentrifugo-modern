<?php
session_start();
class Default_BankController extends Zend_Controller_Action
{
    private $actionadd = 1;
    private $actionupdate = 2;
    private $actiondelete = 3;
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }

    public function indexAction()
	{
		$model = new Default_Model_Bank();

		$call = $this->_getParam('call');
		if($call == 'ajaxcall')
		$this->_helper->layout->disableLayout();

		$view = Zend_Layout::getMvcInstance()->getView();
		$objname = $this->_getParam('objname');
		$refresh = $this->_getParam('refresh');
		$dashboardcall = $this->_getParam('dashboardcall');

		$data = [];
		$searchQuery = '';
		$searchArray = [];
		$tablecontent='';

		if($refresh == 'refresh')
		{
			if($dashboardcall == 'Yes')
			$perPage = DASHBOARD_PERPAGE;
			else
			$perPage = PERPAGE;
			$sort = 'DESC';$by = 'modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
		}
		else
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'modifieddate';
			if($dashboardcall == 'Yes')
			$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
			else
			$perPage = $this->_getParam('per_page',PERPAGE);
			$pageNo = $this->_getParam('page', 1);
			/** search from grid - START **/
			$searchData = $this->_getParam('searchData');
			$searchData = rtrim($searchData,',');
			/** search from grid - END **/
		}
		$dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData,$call,$dashboardcall);
		
		array_push($data,$dataTmp);
		$this->view->dataArray = $data;
		$this->view->call = $call ;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}
    public function addAction()
    {

        $bank_form = new Default_Form_Bank();
        $bank_form->setAttrib('action', BASE_URL . 'bank/add');
        if ($this->getRequest()->getPost()) {
            if ($bank_form->isValid($this->getRequest()->getPost())) {
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $model = new Default_Model_Bank();
                $data = [
                    'name' => $this->getRequest()->getParam('name'),
                    'description' => $this->getRequest()->getParam('description'),
                    'routingno' => $this->getRequest()->getParam('routingno'),
                    'swift_code' => $this->getRequest()->getParam('swift_code'),
                    'bank_verification_class' => $this->getRequest()->getParam('verification_class'),
                    'isactive' => 'Y',
                    'createddate' => gmdate("Y-m-d H:i:s"),
                    'createdby' => $loginUserId,
                    'modifieddate' => gmdate("Y-m-d H:i:s"),
                    'modifiedby' => $loginUserId
                ];

                $tableid=$model->SaveorUpdateBankData($data, '');
                $menuID = BANK;
                sapp_Global::logManager($menuID,$this->actionadd,$loginUserId,$tableid);
                $successmessagestr  = "Bank added successfully.";
                $this->_helper->getHelper("FlashMessenger")->addMessage(['messages' => $successmessagestr]);

                $this->_redirect('bank');
            }
            $messages = $bank_form->getMessages();
            foreach ($messages as $key1 => $value) {
                foreach ($value as $key2 => $value2) {
                    $msgArray[$key1] = $value2;
                    break;
                }
            }
        }
        $this->view->form = $bank_form;
        $this->view->msgarray = $msgArray;
    }
    public function addpopupAction()
    {

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $bank_form = new Default_Form_Bank();
        $bank_form->setAttrib('action', BASE_URL . 'bank/addpopup');
        if ($this->getRequest()->getPost()) {
            if ($bank_form->isValid($this->getRequest()->getPost())) {
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserId = $auth->getStorage()->read()->id;
                }
                $model = new Default_Model_Bank();
                $data = [
                    'name' => $this->getRequest()->getParam('name'),
                    'description' => $this->getRequest()->getParam('description'),
                    'routingno' => $this->getRequest()->getParam('routingno'),
                    'swift_code' => $this->getRequest()->getParam('swift_code'),
                    'bank_verification_class' => $this->getRequest()->getParam('verification_class'),
                    'isactive' => 'Y',
                    'createddate' => gmdate("Y-m-d H:i:s"),
                    'createdby' => $loginUserId,
                    'modifieddate' => gmdate("Y-m-d H:i:s"),
                    'modifiedby' => $loginUserId
                ];

                $tableid=$model->SaveorUpdateBankData($data, '');
                $menuID = BANK;
                sapp_Global::logManager($menuID,$this->actionadd,$loginUserId,$tableid);
                $successmessagestr  = "Bank added successfully.";
                // $this->_helper->getHelper("FlashMessenger")->addMessage(array('messages' => $successmessagestr));
                $currencyData = $model->fetchAll('isactive = "Y"', 'name')->toArray();
                $opt = '';
                foreach ($currencyData as $record) {
                    $opt .= sapp_Global::selectOptionBuilder($record['bank_id'], $record['name']);
                }
                $this->view->bankData = $opt;
                $this->view->eventact = 'added';
                $close = 'close';
                $this->view->popup = $close;
                $this->view->controllername = "bank";
            }
            $messages = $bank_form->getMessages();
            foreach ($messages as $key1 => $value) {
                foreach ($value as $key2 => $value2) {
                    $msgArray[$key1] = $value2;
                    break;
                }
            }
        }
        $this->view->form = $bank_form;
        $this->view->msgarray = $msgArray;
    }
    public function editAction()
    {
        $bank_form = new Default_Form_Bank(true);
        $bank_model = new Default_Model_Bank();
        $newelement = new addformelement();
        $id = $this->_request->getParam('id');
        $bank_form->setAttrib('action', BASE_URL . 'bank/edit/id/' . $id);
        $data = $bank_model->getSingleBank($id);
        // die(json_encode($data[0]['swift_code']));
        $bank_form->name->removeValidator('Db_NoRecordExists');

        $bank_form->name->setValue($data[0]['name']);
        $bank_form->swift_code->setValue($data[0]['swift_code']);
        $bank_form->description->setValue($data[0]['description']);
        $bank_form->routingno->setValue($data[0]['routingno']);
        $bank_form->verification_class->setValue($data[0]['bank_verification_class']);
        $bank_form->addElement($newelement->addelement('checkbox', 'isactive', $data[0], 'isactive'));
        if ($this->getRequest()->getPost()) {
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }
            if ($bank_form->isValid($this->getRequest()->getPost())) {
                $id = $this->_request->getParam('id');
                $where = 'bank_id=' . $id;
                $data = [
                    'name' => $this->getRequest()->getParam('name'),
                    'description' => $this->getRequest()->getParam('description'),
                    'routingno' => $this->getRequest()->getParam('routingno'),
                    'swift_code' => $this->getRequest()->getParam('swift_code'),
                    'bank_verification_class' => $this->getRequest()->getParam('verification_class'),
                    'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'modifieddate' => gmdate("Y-m-d H:i:s"),
                    'modifiedby' => $loginUserId
                ];

                $bank_model->SaveorUpdateBankData($data, $where);
                $menuID=BANK;
                sapp_Global::logManager($menuID,$this->actionupdate,$loginUserId,$id);
                $successmessagestr  = "Bank updated successfully.";
                $this->_helper->getHelper("FlashMessenger")->addMessage(['messages' => $successmessagestr]);
                $this->_redirect('bank');
            }
            $messages = $bank_form->getMessages();
            
            foreach ($messages as $key1 => $value) {
                foreach ($value as $key2 => $value2) {
                    $msgArray[$key1] = $value2;
                    break;
                }
            }
        }
        $this->view->form = $bank_form;
        $this->view->msgarray = $msgArray;
    }
    public function viewAction()
    {
        die("Bank View");
    }
    public function deleteAction()
    {
        try {
            //code...

            //$bank_model = new Default_Model_Bank();
            $bank_model = new Schedule_Model_Timeschedule();
            $id = $this->getRequest()->getParam('objid');
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $loginUserId = $auth->getStorage()->read()->id;
            }
            $data = [
                'isactive' => 'N'
            ];
            $where = ['bank_id=?' => $id];
            $bank_model->deleteData($where, 'main_bank', 'bank_id');
            // die();
            $menuID = BANK;
            sapp_Global::logManager($menuID,$this->actionupdate,$loginUserId,$id);
            return $this->_helper->json(['flagtype' => 'process', 'message' => 'Deleted']);
            // $bank_model->SaveorUpdateBankData($data, $where);
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
            die();
        }
        die();
    }
}
