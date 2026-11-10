<?php



class Schedule_OvertimeCategoryController extends Zend_Controller_Action
{
    public function init()
    {
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
        require_once 'application/modules/default/library/utilClasses/Util.php';
    }

    public function indexAction()
    {

            
       
        $timeschedulemodel = new Schedule_Model_OvertimeCategory();
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
            $by = 'modifiedby';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $searchArray = [];
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
        
        $dataTmp = $timeschedulemodel->getOTcategoryGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
    
        array_push($data, $dataTmp);
        $this->view->dataArray = $dataTmp;
        $this->view->call = $call;
        $this->view->dashboardcall = $dashboardcall;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
   
        
    }
    public function addAction()
    {
      
        $otcategory = new Schedule_Form_Overtimecategory();
        $otcategorymodel = new Schedule_Model_OvertimeCategory();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $login = $auth->getStorage()->read()->id;
        }

        if ($this->getRequest()->isPost()) {
      
            if ($otcategory->isValid($this->getRequest()->getPost())) {

                $data = [
                    'name' => $this->_request->getParam('name_cat'),
                    'description' => $this->_request->getParam('description_cat'),   
                    'isActive' => 'Y',   

                ];
                 $actionflag = 1;
                 $menuID = OVERTIMECATEGORY;
                 $objId=$otcategorymodel->saveORupdateCategory($data, '');
                 sapp_Global::logManager($menuID,$actionflag,$login,$objId);
                 $this->_helper->getHelper('FlashMessenger')->addMessage(['messages'=>'Added Successfully']);
                 $this->_redirect('schedule/overtimecategory');
                
    }
}
$messages = $otcategory->getMessages();

		foreach ($messages as $key => $val) {
			foreach ($val as $key2 => $val2) {
				$msgarray[$key] = $val2;
				break;
			}
		}
        $this->view->msgarray = $msgarray;
$this->view->form = $otcategory;
}
    public function editAction()
    {
        $timecategoryForm = new Schedule_Form_Overtimecategory();
        $timecategoryForm->name_cat->removeValidator('Db_NoRecordExists');

        $timecategorymodel = new Schedule_Model_OvertimeCategory();
        $catdata = $timecategorymodel->getSingleCategory($this->_request->getParam('id'));
        $timecategoryForm->name_cat->setValue($catdata['name']);
        $timecategoryForm->setAction(BASE_URL.'schedule/overtimecategory/edit/id/'.$this->_request->getParam('id'));
        $timecategoryForm->description_cat->setValue($catdata['description']);
        $newelement = new addformelement();
        $auth = Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            $login = $auth->getStorage()->read()->id;
        }
        $timecategoryForm->addElement($newelement->addelement('checkbox','isactive',$catdata,'isactive'));
        if ($this->getRequest()->isPost()) {

            if ($timecategoryForm->isValid($this->getRequest()->getPost())) {
                
                $data = [
                    'name' => $this->_request->getParam('name_cat'),
                    'description' => $this->_request->getParam('description_cat'),
                    'isactive' => utlis::getcheckboxvalue($this->_request->getParam('isactive'))      

                ];
  
                $where = 'OT_category_id = '.$this->_request->getParam('id');
                $actionflag = 2;
                $menuID = OVERTIMECATEGORY;
                 $timecategorymodel->saveORupdateCategory($data, $where);
                 sapp_Global::logManager($menuID,$actionflag,$login,$this->_request->getParam('id'));
                 $this->_helper->getHelper('FlashMessenger')->addMessage(['messages'=>'Updated Successfully']);
                 $this->_redirect('schedule/overtimecategory');
        
    }
    else{
        $messages = $timecategoryForm->getMessages();
            foreach ($messages as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    $msgArray[$key] = $val2;
                    break;
                }
            }
    }
}

        $this->view->msgarray = $msgArray;
        $this->view->form = $timecategoryForm;
    }

    public function deleteAction()
    {
        $OTcategorymodel = new Schedule_Model_Timeschedule();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $login = $auth->getStorage()->read()->id;   
        }

		$id = $this->_request->getParam('objid');
 
		$where = 'OT_category_id =' . (int)$id;
		$data = [
			'isactive' => 'N'
		];
		//die(json_encode($where));
        $actionflag = 3;
        $menuID = OVERTIMECATEGORY;
        $res = $OTcategorymodel->deleteData($where,'main_overtime_category','OT_category_id');
        sapp_Global::logManager($menuID,$actionflag,$login,$id);
		//$res = $OTcategorymodel->saveORupdateCategory($data, $where);
		if ($res > 0)
			return $this->_helper->json(['flagtype' => 'process', 'message' => 'Deleted']);
		die();
    }
}
