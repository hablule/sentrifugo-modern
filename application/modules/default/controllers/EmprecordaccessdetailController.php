<?php

class Default_EmprecordaccessdetailController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';

    }
    public function indexAction()
    {
        try {
            //code...
       
        $model = new Default_Model_emprecordaccessdetail();
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
			$sort = 'DESC';$by = 'ad.emp_id';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
		}
		else
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'ad.emp_id';
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
    } catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());    }

    }
    public function addAction()
    {
        try{
        $form = new Default_Form_emprecordaccessdetail();
        $model = new Default_Model_emprecordaccessdetail();
        $modelrecord = new Default_Model_recordaccess();
        $form->removeElement('isactive');
        $form->removeElement('emprecordaccess_id2');
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $modelemp=new Default_Model_Employee();
        $empdatas=$modelemp->getEmplist();
        $recorddatas=$modelrecord->getrecordlistList();
      


                $util = new utlis();
                $form->emp_id = $util->populate_select($form->emp_id,$empdatas,'id','userfullname','Select');
                $form->emprecordaccess_id = $util->populate_select($form->emprecordaccess_id,$recorddatas,'id','name','Select');   
   
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $emprecordaccess_id=$this->_request->getParam('emprecordaccess_id');
                for($i=0;$i<sizeof($emprecordaccess_id);$i++)
				{   
                $data = [
                    'emprecordaccess_id'=>$emprecordaccess_id[$i],
                    'description'=>$this->_request->getParam('description'),
                    'emp_id'=>$this->_request->getParam('emp_id'),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                ];
                // die(json_encode($data));

                $model->SaveorUpdateData($data,'');
            }
                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Record Access Added Successful']);
                $this->_redirect('emprecordaccessdetail');
            
            
          
        }
            
            $messages = $form->getMessages();
            //die(json_encode($messages));

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

 catch (\Throwable $th) {
    $code=$th->getCode();
    if($code=2300){
        $msgArray['emprecordaccess_id']='Data already exist ';
        $this->view->msgarray = $msgarray;
    }	else{
        $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Record Access Added Successful']);
        $this->_redirect('emprecordaccessdetail');
            }					
}
$this->view->msgarray = $msgArray;
        $this->view->form = $form; 

    }
    public function editAction()
    {
        try{
        $form = new Default_Form_emprecordaccessdetail();
        $model = new Default_Model_emprecordaccessdetail();
        $modelrecord = new Default_Model_recordaccess();
        $form->removeElement('emprecordaccess_id');
        $form->emprecordaccess_id2->setAttrib('readonly',true);
        $form->emp_id->setAttrib('readonly',true);

        $form->emprecordaccess_id2->setRequired(false);
        $form->emp_id->setRequired(false);

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $modelemp=new Default_Model_Employee();
        $empdatas=$modelemp->getEmplist();
        $recorddatas=$modelrecord->getrecordlistList();
        $id = $this->_getParam('id');

         $datas=$model->getdetails($id);


                $util = new utlis();
                $form->emp_id = $util->populate_select($form->emp_id,$empdatas,'id','userfullname','Select');
                $form->emprecordaccess_id2 = $util->populate_select($form->emprecordaccess_id2,$recorddatas,'id','name','Select');   
   $form->populate($datas);
   $form->isactive->setValue($util::getcheckboxvalue($datas['isactive'],'form'));
   $form->emprecordaccess_id2->setValue($datas['emprecordaccess_id']);

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                              
                $data = [
                    // 'emprecordaccess_id'=>$this->_request->getParam('emprecordaccess_id2'),
                    'description'=>$this->_request->getParam('description'),
                    // 'emp_id'=>$this->_request->getParam('emp_id'),
                    'isactive'=>$util::getcheckboxvalue($this->_getParam('isactive')),
                    'modifiedby'=> $loginUserId,
                    'modifieddate'=> gmdate("Y-m-d H:i:s")
                ];
                // die(json_encode($data));

                $model->SaveorUpdateData($data,'emprecordacessdetail_id='.$id);
               
                $this->_helper->getHelper("FlashMessenger")->addMessage(['success'=>'Record Access Edited Successful']);
                $this->_redirect('emprecordaccessdetail');
            
            
          
        }
            
            $messages = $form->getMessages();
            //die(json_encode($messages));

            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
        
    }
		$this->view->msgarray = $msgArray;
        $this->view->form = $form; 
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    } 
    // public function viewAction()
	// {
	// 	$id = $this->getRequest()->getParam('id');
	// 	$callval = $this->getRequest()->getParam('call');
	// 	if ($callval == 'ajaxcall')
	// 		$this->_helper->layout->disableLayout();
	// 	$objName = 'emprecordaccessdetail';
	// 	$form = new Default_Form_emprecordaccessdetail();

	// 	$form->removeElement("submit");


	// 	$model = new Default_Model_emprecordaccessdetail();
	// 	try {
	// 		if ($id) {
	// 			if (is_numeric($id) && $id > 0) {

	// 				$data = $model->getdetails($id);

	// 				//die(json_encode($data));
	// 				if (!empty($data) && $data != "norows") {
	// 					$form->populate($data);
	// 					$this->view->form = $form;
	// 					$this->view->controllername = $objName;
	// 					$this->view->data = $data;
	// 					$this->view->id = $id;
	// 					$this->view->ermsg = '';
	// 				} else {

	// 					$this->view->ermsg = 'nodata';
	// 				}
	// 			} else {
	// 				$this->view->ermsg = 'nodata';
	// 			}
	// 		} else {
	// 			$this->view->ermsg = 'nodata';
	// 		}
	// 		$this->view->controllername='emprecordaccessdetail';

	// 	} catch (Exception $e) {
	// 		$this->view->ermsg = 'nodata';
	// 	}
	// }
}
