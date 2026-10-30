<?php

class Default_themeController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/Util.php';

    }
    public function indexAction()
    {
		

        $model = new Default_Model_theme();
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
			$sort = 'DESC';$by = 'leavecategory_id';$pageNo = 1;$searchData = '';$searchQuery = '';$searchArray='';
		}
		else
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'t.theme_id';
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
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}

		$msgarray = [];
		$controllername = 'theme';
		$form = new Default_Form_theme();
		$model = new Default_Model_theme();
		$form->setAction(BASE_URL.'theme/add');
		if($this->getRequest()->getPost()){
			if($form->isValid($this->_request->getPost())){
				$name = $this->_request->getParam('name');
				$menucolor = $this->_request->getParam('menubgcolor');
				$menufgcolor = $this->_request->getParam('menufgcolor');
				$menuhocolor = $this->_request->getParam('menuhocolor');

					$headercolor = $this->_request->getParam('headerbgcolor');				
					$bodycolor = $this->_request->getParam('bodybgcolor');
					$tablebgcolor = $this->_request->getParam('tablebgcolor');
					$tablefgcolor = $this->_request->getParam('tablefgcolor');

					$sidebarcolor = $this->_request->getParam('sidebarbgcolor');
					$sidebarfgcolor = $this->_request->getParam('sidebarfgcolor');
					$sidebarhocolor = $this->_request->getParam('sidebarhocolor');

					$tableheadercolor = $this->_request->getParam('tableheaderbgcolor');
					$tableheaderfgcolor = $this->_request->getParam('tableheaderfgcolor');

                    $font = $this->_request->getParam('font');
					$menustyle = $this->_request->getParam('menustyle');
					$menuorentation = $this->_request->getParam('menuorentation');

				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				$data = ['theme_name'=>$name,
				 				'menu_color'=>$menucolor,
								 'menufg_color'=>$menufgcolor,
								 'menuho_color'=>$menuhocolor,
                                 'header_color'=>$headercolor,	       
				 				'body_color'=>$bodycolor,	       
				 				'tablebg_color'=>$tablebgcolor,	 
								 'tablefg_color'=>$tablefgcolor,	 

                                 'font_style'=>$font,
								 'menu_style'=>$menustyle,
								 'menu_orentation'=>$menuorentation,

								 'sidebar_color'=>$sidebarcolor,
								 'sidebarfg_color'=>$sidebarfgcolor,
								 'sidebarho_color'=>$sidebarhocolor,

								 'tableheader_color'=>$tableheadercolor, 
								 'tableheaderfg_color'=>$tableheaderfgcolor	 

				];

					$data['createdby'] = $loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");
					$data['isactive'] = "Y";
					$where = '';
					$actionflag = 1;
//die(json_encode($data));
				$Id = $model->SaveorUpdateData($data, $where);
				$tableid = $Id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Theme  added successfully."]);

					sapp_Global::logManager(THEME,$actionflag,$loginUserId,$tableid);
				$this->_redirect('theme');

			}else
			{
				$messages = $form->getMessages();
				foreach ($messages as $key => $val)
				{
					foreach($val as $key2 => $val2)
					{
						$msgarray[$key] = $val2;
                                                break;
					}
				}
				$this->view->msgarray = $msgarray;
			}
		}
		$this->view->controllername = $controllername;
		$this->view->form = $form;
		$this->view->ermsg = '';

	}
  
    public function editAction()
	{			
		$util=new utlis();	

		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = (int)$this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();
		$form = new Default_Form_theme();
		$form->name->removeValidator('Db_NoRecordExists');
		$model = new Default_Model_theme();
		$form->setAttrib('action', BASE_URL +'theme/edit/id/'.$id.'');
		try
		{
		
			if($id)
			{
				if(is_numeric($id) && $id>0)
				{
					$data = $model->getsingleData($id);
                    $form->font->setValue($data[0]['font']);
				$form->isactive->setValue(utlis::getcheckboxvalue($data[0]['isactivecheck'],'form'));
				$form->isdefault->setValue(utlis::getcheckboxvalue($data[0]['isdefaultcheck'],'form'));
				$form->menustyle->setValue($data[0]['menustyle']);
				$form->menuorentation->setValue($data[0]['menuorentation']);


$form->name->setValue($data[0]['name']);
					if(!empty($data) && $data != "norows")
					{
						// $form->populate($data[0]);
						$form->submit->setLabel('Update');
						$this->view->data = $data;
						$this->view->ermsg = '';
					}
					else
					{
						$this->view->ermsg = 'nodata';
					}
				}else
				{
					$this->view->ermsg = 'nodata';
				}
			}else
			{
				$this->view->ermsg = '';
			}
		}
		catch(Exception $e)
		{
			$this->view->ermsg = 'nodata';
		}

		$this->view->form = $form;
		if($this->getRequest()->getPost()){

			if($form->isValid($this->_request->getPost())){
				$name = $this->_request->getParam('name');
				$menucolor = $this->_request->getParam('menubgcolor');
				$menufgcolor = $this->_request->getParam('menufgcolor');
				$menuhocolor = $this->_request->getParam('menuhocolor');

				$headercolor = $this->_request->getParam('headerbgcolor');				
				$bodycolor = $this->_request->getParam('bodybgcolor');
				$tablebgcolor = $this->_request->getParam('tablebgcolor');
				$tablefgcolor = $this->_request->getParam('tablefgcolor');

                $font = $this->_request->getParam('font');
				$menustyle = $this->_request->getParam('menustyle');
				$menuorentation = $this->_request->getParam('menuorentation');

				$sidebarcolor = $this->_request->getParam('sidebarbgcolor');
				$sidebarfgcolor = $this->_request->getParam('sidebarfgcolor');
				$sidebarhocolor = $this->_request->getParam('sidebarhocolor');

				$tableheadercolor = $this->_request->getParam('tableheaderbgcolor');
				$tableheaderfgcolor= $this->_request->getParam('tableheaderfgcolor');
				$isdefault = (utlis::getcheckboxvalue($this->_request->getParam('isdefault'),'database'));

				$isactive = (utlis::getcheckboxvalue($this->_request->getParam('isactive'),'database'));
				$date = new Zend_Date();
				$actionflag = 2;
				$tableid  = '';
				$data = ['theme_name'=>$name,
                'menu_color'=>$menucolor,
				'menufg_color'=>$menufgcolor,
                'menuho_color'=>$menuhocolor,
                'header_color'=>$headercolor,	       
                'body_color'=>$bodycolor,	       
                'tablebg_color'=>$tablebgcolor,	
				'tablefg_color'=>$tablefgcolor,	

				'sidebar_color'=>$sidebarcolor,
				'sidebarfg_color'=>$sidebarfgcolor,
				'sidebarho_color'=>$sidebarhocolor,
				'tableheaderfg_color'=>$tableheaderfgcolor,
				'tableheader_color'=>$tableheadercolor,	
                'font_style'=>$font,	
				'modifiedby'=>$loginUserId,
				'isactive'=>$isactive,
				'isdefault'=>$isdefault,
				'menu_style'=>$menustyle,
				'menu_orentation'=>$menuorentation,

                'modifieddate'=>gmdate("Y-m-d H:i:s")
				];
				
					$where = 'theme_id='.$id;
                  // die(json_encode($data));
			$Id = $model->SaveorUpdateData($data, $where);
				if($Id == 'update')
				{
					$tableid = $id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Theme updated successfully."]);
				}
				else if($Id == 'exist')
				{
					$tableid = $id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["error"=>"Default Theme Alaredy exist."]);
				}
				else
				{
					$tableid = $Id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Theme added successfully."]);
				}
				sapp_Global::logManager(THEME,$actionflag,$loginUserId,$tableid);
				$this->_redirect('theme');
			}else
			{
				$messages = $form->getMessages();
				foreach ($messages as $key => $val)
				{
					foreach($val as $key2 => $val2)
					{
						$msgarray[$key] = $val2;
                                                break;
					}
				}
				$this->view->msgarray = $msgarray;
					
			}
		}
	}
    public function viewAction()
    {
        $theme_Form = new Default_Form_theme();
        $this->view->form = $theme_Form; 
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
			$model = new Default_Model_Leave();

		
			$where = ['leave_id=?' => $id];
			$Id = $model->DeleteData($where);
			if ($Id == 'delete') {
				$messages['message'] = 'Leave deleted successfully.';
				$messages['msgtype'] = 'success';
			} else {
				$messages['message'] = 'Leave Category cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		} else {
			$messages['message'] = 'Leave cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		if ($deleteflag == 1) {
			if ($messages['msgtype'] == 'error') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(["error" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag]);
			}
			if ($messages['msgtype'] == 'success') {
				$this->_helper->getHelper("FlashMessenger")->addMessage(["success" => $messages['message'], "msgtype" => $messages['msgtype'], 'deleteflag' => $deleteflag]);
			}
		}
		$this->_helper->json($messages);
	} // end of delete action
}
?>
