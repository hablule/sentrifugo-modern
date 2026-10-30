<?php
class Default_DeductivecategoryController extends Zend_Controller_Action
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

		$dcatModel = new Default_Model_Deductivecategory();
		$call = $this->_getParam('call');

		if ($call == 'ajaxcall') {
			$this->_helper->layout->disableLayout();
		}

		$view = Zend_Layout::getMvcInstance()->getView();
		$objname = $this->_getParam('objname');
		$refresh = $this->_getParam('refresh');
		$dashboardcall = $this->_getParam('dashboardcall');

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

		$dataTmp = $dcatModel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);

		array_push($data, $dataTmp);
		$this->view->dataArray = $data;
		$this->view->call = $call;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}

   
    public function  addAction()
    {       $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
		}
        
        $form = new Default_Form_deductivecategory();
        $formModel = new Default_Model_Deductivecategory();
        $form->setAction(BASE_URL . 'deductivecategory/add');
        if ($this->getRequest()->isPost()) {

            if ($form->isValid($this->getRequest()->getPost())) {

                $data = array(
                    'name' => $this->_request->getParam('name_cat'),
                    'description' => $this->_request->getParam('description_cat'),   
                    'isactive' => 'Y', 
                    'createdby' => $loginUserId,
                    'createddate' => gmdate("Y-m-d H:i:s"),  

                );
             //  die(json_encode($data));
                $tableid=$formModel->addCategory($data);
                    $menuid = DEDUCTIVECATEGORY;
                    $actionflag = 1;
                 $this->view->eventact = 'added';
                 $close = 'close';
                 $this->view->popup=$close;
                 $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Load Category Added successfully."));
				sapp_Global::logManager($menuid,$actionflag,$loginUserId,$tableid);
                 $this->_redirect('deductivecategory');
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
    {        $auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
		}
        
        $id = $this->getRequest()->getParam('id');
        $form = new Default_Form_deductivecategory();
        $formModel = new Default_Model_Deductivecategory();
        $form->setAttrib('action', BASE_URL . 'deductivecategory/edit/id/'.$id);
        $dedData = $formModel->getSingleDeductivecategory($id);
        $form->name_cat->setValue($dedData[0]['name']);
        $form->description_cat->setValue($dedData[0]['description']);
        $form->submit_cat->setLabel('Update');
        $newelement = new addformelement();
        $form->addElement($newelement->addelement('checkbox','isactive',$dedData[0],'isactive'));
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->form = $form;


        if ($this->getRequest()->getPost()) {
            //die(json_encode($this->getRequest()->getPost()));
            $where = array('load_category_id=?' => $this->getRequest()->getParam('id'));
            $data = array(
                'name' => $this->getRequest()->getParam('name_cat'),
                'description' => $this->getRequest()->getParam('description_cat'),
                'modifiedby' => $loginUserId,
                'modifieddate' => gmdate("Y-m-d H:i:s"),
                'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive'),'database')
            );

            $formModel->editDeductivecategory($data, $where);
            $menuid = DEDUCTIVECATEGORY;
            $actionflag = 2;
            $tableid = $id;
            sapp_Global::logManager($menuid,$actionflag,$loginUserId,$tableid);
			$this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Load Category updated successfully."));
				
            $this->_redirect('deductivecategory');
        }
        
    }
    public function deleteAction()
    {
        $formModel = new Schedule_Model_Timeschedule();

		$id = $this->_request->getParam('objid');
		$where = 'load_category_id =' . (int)$id;
		$data = array(
			'isactive' => 'N'
		);
        $res = $formModel->deleteData($where,'main_loadcategory','load_category_id');
		//die(json_encode($where));
        //$res=$formModel->editDeductivecategory($data, $where);

		if ($res > 0)
			return $this->_helper->json(array('flagtype' => 'process', 'message' => 'Deleted'));
		die();
    }
    public function viewAction(){
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
