<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/
require_once 'application/modules/default/library/utilClasses/Util.php';
class Default_HolidaydatesController extends Zend_Controller_Action
{

    private $options;
	public function preDispatch()
	{
		 
		
	}
	
    public function init()
    {
        $this->_options= $this->getInvokeArg('bootstrap')->getOptions();
		
    }

    public function indexAction()
    {
		$holidaydatesmodel = new Default_Model_Holidaydates();	
        $call = $this->_getParam('call');
		if($call == 'ajaxcall')
				$this->_helper->layout->disableLayout();
		
		$view = Zend_Layout::getMvcInstance()->getView();		
		$objname = $this->_getParam('objname');
		$refresh = $this->_getParam('refresh');
		$dashboardcall = $this->_getParam('dashboardcall');
		$unitId = intval($this->_getParam('unitId'));
		  if(!isset($unitId))
		   $unitId = '';
		$data = array();
		$searchQuery = '';
		$searchArray = array();
		$tablecontent='';
		
		if($refresh == 'refresh')
		{
		    if($dashboardcall == 'Yes')
				$perPage = DASHBOARD_PERPAGE;
			else	
				$perPage = PERPAGE;
			$sort = 'DESC';$by = 'h.modifieddate';$pageNo = 1;$searchData = '';
		}
		else 
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'h.modifieddate';
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

		$objName = 'holidaydates';
		$dataTmp = $holidaydatesmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData,$call,$dashboardcall,$objName,$unitId);				

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
					$loginuserRole = $auth->getStorage()->read()->emprole;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$popConfigPermission = array();
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
			
		$holidaydatesform = new Default_Form_holidaydates();
		$holidaydatesform->setAttrib('action',BASE_URL.'holidaydates/add');
		$msgarray= array();
		$holidaygroupsmodel = new Default_Model_Holidaygroups();
		$groupdataArr = $holidaygroupsmodel->getAllGroupData();
		    if(sizeof($groupdataArr) > 0)
            {
				foreach ($groupdataArr as $groupdatares){
					$holidaydatesform->groupid->addMultiOption($groupdatares['id'],utf8_encode($groupdatares['name']));
				}
		    }
			else
			{
				$msgarray['groupid'] = 'Holiday Category are not configured yet.';
			}
		if(sapp_Global::_checkprivileges(HOLIDAYGROUPS,$loginuserGroup,$loginuserRole,'add') == 'Yes'){
			array_push($popConfigPermission,'holidaygroups');
		}  
        $this->view->form = $holidaydatesform;
        $this->view->msgarray = $msgarray; 		
        if($this->getRequest()->getPost()){
		     $result = $this->save($holidaydatesform);		 
		     $this->view->msgarray = $result; 
        }  		
		$this->view->popConfigPermission = $popConfigPermission;
	}
	
	public function addpopupAction()
	{
	   Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
	  $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
		}
		
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
			
		$bunitid = $this->getRequest()->getParam('unitId');	
		$holidaydatesform = new Default_Form_holidaydates();
		$holidaydatesform->setAttrib('action',BASE_URL.'holidaydates/addpopup/unitId/'.$bunitid);
		$msgarray= array();
        $this->view->form = $holidaydatesform;
		$this->view->groupid = $bunitid;
		$this->view->controllername = 'holidaydates';
        $this->view->msgarray = $msgarray; 		
        if($this->getRequest()->getPost()){
		     $result = $this->popupsave($holidaydatesform);		 
		     $this->view->msgarray = $result; 
        }
	
	}

    public function viewAction()
	{	
	    $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
	    
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$objName = 'holidaydates';
		$holidaydatesform = new Default_Form_holidaydates();
		$holidaydatesmodel = new Default_Model_Holidaydates(); 	
		$holidaygroupsmodel = new Default_Model_Holidaygroups();
		$holidaydatesform->removeElement("submit");
		$holidaydatesform->removeElement('groupid');
		$groupval = '';$groupname = '';
		try
		{
			if($id)
			{
			    if(is_numeric($id) && $id>0)
				{
					$data = $holidaydatesmodel->getParticularHolidayDateData($id);
					if(!empty($data))
					{
						$data = $data[0];
						$groupdataArr = $holidaygroupsmodel->getParticularGroupData($data['holidaycategory_id']);
						if(sizeof($groupdataArr) > 0)
						{
							$groupname = $groupdataArr[0]['name'];
							$data['holidaycategory_id']= $groupdataArr[0]['name'];
						}
						$holidaydatesform->populate($data);					
						$start_date = sapp_Global::change_date($data['start_date'], 'view');
						$end_date = sapp_Global::change_date($data['end_date'], 'view');
						$holidaydatesform->start_date->setValue($start_date);
						$holidaydatesform->end_date->setValue($end_date);
						$this->view->rowexist = "";
						$groupval = $data['groupid'];
					}else
					{
					   $this->view->rowexist = "norows";
					}
				}else
				{
				   $this->view->rowexist = "norows";
				}	
			}else
			{
			   $this->view->rowexist = "norows";
			}
		}
        catch(Exception $e){
			    $this->view->rowexist = "norows";
		}  		
			$elements = $holidaydatesform->getElements();
			if(count($elements)>0)
			{
				foreach($elements as $key=>$element)
				{
					if(($key!="Cancel")&&($key!="Edit")&&($key!="Delete")&&($key!="Attachments")){
					$element->setAttrib("disabled", "disabled");
						}
				}
			}
			
		$permission = sapp_Global::_checkprivileges(HOLIDAYDATES,$loginuserGroup,$loginuserRole,'edit');	
		$this->view->groupname = $groupname;
		$this->view->groupval = $groupval;
		$this->view->editpermission = $permission;
		$this->view->controllername = $objName;
		$this->view->id = $id;
		$this->view->data = $data;
		$this->view->form = $holidaydatesform;
	}
	
	public function viewpopupAction()
	{	
	     Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/"); 
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		$objName = 'holidaydates';
		$unitid = $this->getRequest()->getParam('unitId');
		$holidaydatesform = new Default_Form_holidaydates();
		$holidaydatesmodel = new Default_Model_Holidaydates(); 	
		$holidaydatesform->removeElement("submit");
  			$elements = $holidaydatesform->getElements();
			if(count($elements)>0)
			{
				foreach($elements as $key=>$element)
				{
					if(($key!="Cancel")&&($key!="Edit")&&($key!="Delete")&&($key!="Attachments")){
					$element->setAttrib("disabled", "disabled");
						}
				}
			}
		if($id)
		{
			$data = $holidaydatesmodel->getParticularHolidayDateData($id);
			if(!empty($data))
			{
 			    $data = $data[0];
				$holidaydatesform->populate($data);				
				$start_date = sapp_Global::change_date($data['start_date'], 'view');
				$end_date = sapp_Global::change_date($data['end_date'], 'view');
				$holidaydatesform->start_date->setValue($start_date);
				$holidaydatesform->end_date->setValue($end_date);
			}else
			{
			   $this->view->rowexist = "rows";
			}
		}else
		{
		   $this->view->rowexist = "norows";
		}
				
		$this->view->controllername = $objName;
		$this->view->id = $id;
		$this->view->data = $data;
		$this->view->unitid = $unitid;
		$this->view->form = $holidaydatesform;
	}
	
	
	public function editAction()
	{	
	    $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		
		$holidaydatesform = new Default_Form_holidaydates();
		$holidaydatesmodel = new Default_Model_Holidaydates(); 	
		$holidaygroupsmodel = new Default_Model_Holidaygroups();
		$holidaydatesform->removeElement('groupid');
		$holidaydatesform->submit->setLabel('Update'); 
		try
		{
			if($id)
			{
			    if(is_numeric($id) && $id>0)
				{
					$data = $holidaydatesmodel->getParticularHolidayDateData($id);
					if(!empty($data))
					{
						$data = $data[0];
						$groupdataArr = $holidaygroupsmodel->getParticularGroupData($data['holidaycategory_id']);
						$groupname = '';
						if(sizeof($groupdataArr) > 0)
						{
							$groupname = $groupdataArr[0]['name'];
						}
						
						$holidaydatesform->populate($data);					
						$start_date = sapp_Global::change_date($data['start_date'], 'view');
						$end_date = sapp_Global::change_date($data['end_date'], 'view');
						$holidaydatesform->start_date->setValue($start_date);
						$holidaydatesform->end_date->setValue($end_date);
						$holidaydatesform->isactive->setValue(utlis::getcheckboxvalue($data['isactive'],'form'));
						$this->view->form = $holidaydatesform;
						$this->view->groupname = $groupname;
						$this->view->groupval = $data['holidaycategory_id'];
						$this->view->rowexist = "";
						$holidaydatesform->setAttrib('action',BASE_URL.'holidaydates/edit/id/'.$id);
					}else
					{
					   $this->view->rowexist = "norows";
					}
				}else
				{
				   $this->view->rowexist = "norows";
				}	
			}else
			{
			   $this->view->rowexist = "norows";
			}
		}
        catch(Exception $e){
			    $this->view->rowexist = "norows";
		} 		
		
		
		if($this->getRequest()->getPost()){
      		$result = $this->save($holidaydatesform);	
		    $this->view->msgarray = $result; 
		}
	}
	
	public function editpopupAction()
	{	
	     Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
	    $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->getRequest()->getParam('id');
		$bunitid = $this->getRequest()->getParam('unitId');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();
		
		$holidaydatesform = new Default_Form_holidaydates();
		$holidaydatesmodel = new Default_Model_Holidaydates(); 	
		$holidaygroupsmodel = new Default_Model_Holidaygroups();	
		$holidaydatesform->submit->setLabel('Update'); 
		if($id)
		{
			$data = $holidaydatesmodel->getParticularHolidayDateData($id);

			if(!empty($data))
			{
			    $data = $data[0];
				$groupdataArr = $holidaygroupsmodel->getParticularGroupData($data['groupid']);
				if(sizeof($groupdataArr) > 0)
				{
						$holidaydatesform->groupid->addMultiOption($groupdataArr[0]['id'],utf8_encode($groupdataArr[0]['groupname']));
				}
				$holidaydatesform->populate($data);			
				$start_date = sapp_Global::change_date($data['start_date'], 'view');
				$end_date = sapp_Global::change_date($data['end_date'], 'view');
				$holidaydatesform->start_date->setValue($start_date);
				$holidaydatesform->end_date->setValue($end_date);
				$holidaydatesform->setAttrib('action',BASE_URL.'holidaydates/editpopup/id/'.$id.'/unitId/'.$bunitid);
			}
			else
			{
			   $this->view->rowexist = "rows";
			}
		}else
		{
		   $this->view->rowexist = "norows";
		}
		
		$this->view->form = $holidaydatesform;
		$this->view->groupid = $bunitid;
		$this->view->controllername = 'holidaydates';
		if($this->getRequest()->getPost()){
		    $result = $this->popupsave($holidaydatesform);	
		    $this->view->msgarray = $result; 
      	     
		}
	}
	
	public function save($holidaydatesform)
	{
	  $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
		} 
	
			$holidaygroupsmodel = new Default_Model_Holidaygroups();
			$holidaydatesmodel = new Default_Model_Holidaydates();				
			$id = $this->_request->getParam('id'); 
			$holidayname = $this->_request->getParam('holidayname');
			$groupidArr = $this->_request->getParam('groupid');
			$isactive = $this->_request->getParam('isactive');	
			if(is_array($groupidArr))
			{
				$groupidstr = implode(",",$groupidArr);
			}else $groupidstr = $groupidArr;
			$start_date = $this->_request->getParam('start_date');
			$end_date = $this->_request->getParam('end_date');
			
			if($start_date != '')
			{				
				$start_date = sapp_Global::change_date($start_date, 'database');
			}
			if($end_date != '')
			{				
				$end_date = sapp_Global::change_date($end_date, 'database');
			}
			$holidayyear = date('Y',strtotime($start_date));
			$description = $this->_request->getParam('description');
			$errorflag = "true";
			$msgarray = array();
			
			if(preg_match('/^[a-zA-Z0-9.\- ?]+$/',$holidayname))
			{ 
				if(!is_array($groupidArr) && $groupidArr!= '')
				{
					$checkholidayname = $holidaydatesmodel->checkholidayname($holidayname,$groupidArr,$id,$holidayyear );		
					$count = $checkholidayname[0]['count'];
					if($count > 0){
						$msgarray['holidayname'] = $holidayname.' already added to this group.';
						$errorflag = 'false';
					}
				}else{
					for($j=0;$j<sizeof($groupidArr);$j++)	
					{
					 $isduplicateholiday = $holidaydatesmodel->checkholidayname($holidayname,$groupidArr[$j],'',$holidayyear);
					
					 $duplicatearr['count'] = $isduplicateholiday[0]['count'];
					} 
				}
			}
			if(!empty($duplicatearr))
			{
				if($duplicatearr['count'] > 0)
				{
				  $errorflag = "false";
				  $msgarray['groupid'] = "Duplicate entry for this group";
				  
				}else
				{ 					
					
					$errorflag = "true";
				}
			} 				
				
     		if($holidaydatesform->isValid($this->_request->getPost()) && $errorflag == "true"){
				$date = new Zend_Date();
				$actionflag = '';
				$tableid  = '';
				     $data = array( 'name'=>trim($holidayname),
									 'start_date'=>$start_date,
									 'end_date'=>$end_date,
									 'holidayyear'=>$holidayyear, 
									 'description'=>trim($description),
									 'isactive'=>utlis::getcheckboxvalue($isactive),
									 'modifiedby'=>$loginUserId,
									 'modifieddate'=>gmdate("Y-m-d H:i:s")
									 
							);
                	if($id!=''){
						$where = array('holiday_id=?'=>$id);  
						$actionflag = 2;
					}
					else
					{
					    $data['createdby'] = $loginUserId;
						$data['createddate'] = gmdate("Y-m-d H:i:s");
						$data['isactive'] = 'Y';
						$where = '';
						$actionflag = 1;
					}
			
					if(sizeof($groupidArr) > 0)
					{
						for($i=0;$i<sizeof($groupidArr);$i++)	
						{	
							$data['holidaycategory_id'] = $groupidArr[$i];	
							//die(json_encode($data));							
							$Id = $holidaydatesmodel->SaveorUpdateHolidayDates($data, $where);							   
						}
					}else{
						$Id = $holidaydatesmodel->SaveorUpdateHolidayDates($data, $where);
					}
					
					if($id)
					{
					   $tableid = $id;
					   $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Holiday date updated successfully."));
					}   
					else
					{
                        $tableid = $Id; 	
                        $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Holiday date added successfully."));					   
					}   
					$menuID = HOLIDAYDATES;
					$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
    			    $this->_redirect('holidaydates');		
			}else
			{
     			$messages = $holidaydatesform->getMessages();
				foreach ($messages as $key => $val)
					{
						foreach($val as $key2 => $val2)
						 {
							$msgarray[$key] = $val2;
							break;
						 }
					}
				return $msgarray;			
			}
	}
	
	public function popupsave($holidaydatesform)
	{
	    $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
		}
		$holidaydatesmodel = new Default_Model_Holidaydates(); 
		$holidayname = $this->_request->getParam('holidayname');
		$unitId = $this->_request->getParam('unitId');
		$groupid = $this->_request->getParam('groupid');
		$id = $this->_request->getParam('id'); 
		$checkholidayname = $holidaydatesmodel->checkholidayname($holidayname,$groupid,$id);		
		$count = $checkholidayname[0]['count'];
		$flag = 'true';
		if($count > 0){
			$msgarray['holidayname'] = $holidayname.' already added to this group.';
			$flag = 'false';
		}
		if($holidaydatesform->isValid($_POST) && $flag != 'false'){		       
	        	$id = $this->_request->getParam('id'); 
				$groupid = $this->_request->getParam('groupid');
			    $holidayname = $this->_request->getParam('holidayname');
				$start_date = $this->_request->getParam('start_date');
				$end_date = $this->_request->getParam('end_date');
				
                                $start_date = sapp_Global::change_date($start_date, 'database');
								$end_date = sapp_Global::change_date($end_date, 'database');
				$holidayyear = date('Y',strtotime($start_date));
				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$holidaydatesmodel = new Default_Model_Holidaydates(); 
				$actionflag = '';
				$tableid  = '';
				     $data = array( 'name'=>trim($holidayname),
					                 'holidaycategory_id'=>$groupid,
									 'start_date'=>$start_date,
									 'end_date'=>$end_date,
									 'holidayyear'=>$holidayyear, 
									 'description'=>trim($description),
									 'modifiedby'=>$loginUserId,
									 'modifieddate'=>gmdate("Y-m-d H:i:s")
							);
                	if($id!=''){
						$where = array('holiday_id=?'=>$id);  
						$actionflag = 2;
					}
					else
					{
					    $data['createdby'] = $loginUserId;
						$data['createddate'] = gmdate("Y-m-d H:i:s");
						$data['isactive'] = 'Y';
						$where = '';
						$actionflag = 1;
					}
					
                    $Id = $holidaydatesmodel->SaveorUpdateHolidayDates($data, $where);							   
					    
					if($id)
					{
					   $tableid = $id;
					   $this->view->successmessage = 'Holiday date updated successfully.';
					}   
					else
					{
                        $tableid = $Id; 	
                        $this->view->successmessage = 'Holiday date added successfully.';						
					}   
					$menuID = HOLIDAYDATES;
					$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
					$this->view->controllername = 'holidaydates/add';
    			    Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");		
			}else
			{
			
     			$messages = $holidaydatesform->getMessages();
				foreach ($messages as $key => $val)
					{
						foreach($val as $key2 => $val2)
						 {
							$msgarray[$key] = $val2;
							break;
						 }
					}
				return $msgarray;			
			}
	
	}
	
	public function deleteAction()
	{
	     $auth = Zend_Auth::getInstance();
     		if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
				}
		 $id = $this->_request->getParam('objid');
		 $deleteflag=$this->_request->getParam('deleteflag');
		 $messages['message'] = '';
		 $messages['msgtype'] = '';
		 $actionflag = 3;
		    if($id)
			{
			$holidaydatesmodel = new Default_Model_Holidaydates();
			  $data = array('isactive'=>'N','modifieddate'=>gmdate("Y-m-d H:i:s"));
			  $where = array('holiday_id=?'=>$id);
			  $Id = $holidaydatesmodel->SaveorUpdateHolidayDates($data, $where);
			    if($Id == 'update')
				{
				   $menuID = HOLIDAYDATES;
				   $result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$id); 
				   $messages['message'] = 'Holiday date deleted successfully.';
				   $messages['msgtype'] = 'success';
				}   
				else
				{
                   $messages['message'] = 'Holiday date cannot be deleted.';	
				   $messages['msgtype'] = 'error';
				}   
                    				   
			}
			else
			{ 
			 $messages['message'] = 'Holiday date cannot be deleted.';
			 $messages['msgtype'] = 'error';
			}
			// delete success message after delete in view
			if($deleteflag==1)
			{
				if(	$messages['msgtype'] == 'error')
				{
					$this->_helper->getHelper("FlashMessenger")->addMessage(array("error"=>$messages['message'],"msgtype"=>$messages['msgtype'] ,'deleteflag'=>$deleteflag));
				}
				if(	$messages['msgtype'] == 'success')
				{
					$this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>$messages['message'],"msgtype"=>$messages['msgtype'],'deleteflag'=>$deleteflag));
				}
			}
			$this->_helper->json($messages);
		
	}
	
	

}

