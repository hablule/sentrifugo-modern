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

class Default_MyholidaycalendarController extends Zend_Controller_Action
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
		try {
		
	    $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
		} 
		$call = $this->_getParam('call');
		if($call == 'ajaxcall')
				$this->_helper->layout->disableLayout();
				
		$employeesModel = new Default_Model_Employees();
      
				
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
					$sort = 'DESC';$by = 'h.holidaydate';$perPage = 10;$pageNo = 1;$searchData = '';
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
				$holidaydatesmodel = new Default_Model_Holidaydates();		
				$objName = 'myholidaycalendar';
								    
				$dataTmp = $holidaydatesmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData,$call,$dashboardcall,$objName,$unitId);				
							
				array_push($data,$dataTmp);
				$this->view->dataArray = $data;
				$this->view->call = $call ;
		
       
        $this->view->controllergrid = 'myholidaycalendar'; 		
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
			//code...
		} catch (\Throwable $th) {
		Zend_Debug::dump($th->getMessage());
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
		$objName = 'myholidaycalander';
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
						$groupdataArr = $holidaygroupsmodel->getParticularGroupData($data['groupid']);
						if(sizeof($groupdataArr) > 0)
						{
							$groupname = $groupdataArr[0]['groupname'];
							$data['groupid']= $groupdataArr[0]['groupname'];
						}
						$holidaydatesform->populate($data);					
						$start_date = sapp_Global::change_date($data['start_date'], 'view');
						$end_date = sapp_Global::change_date($data['end_date'], 'view');
						$holidaydatesform->start_date->setValue($start_date);
						$holidaydatesform->end_date->setValue($end_date);
						die("kkkk");

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
	
	
	public function save($holidaygroupsform)
	{
	  $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
		} 
     		if($holidaygroupsform->isValid($this->_request->getPost())){
			    $id = $this->_request->getParam('id'); 
			    $groupname = $this->_request->getParam('groupname');
				$description = $this->_request->getParam('description');
				$date = new Zend_Date();
				$holidaygroupsmodel = new Default_Model_Holidaygroups(); 
				$actionflag = '';
				$tableid  = ''; 
				   $data = array( 'groupname'=>$groupname,
				                  'description'=>trim($description),
								  'modifiedby'=>$loginUserId,
								  'modifieddate'=>$date->get('yyyy-MM-dd HH:mm:ss')
						);
					if($id!=''){
						$where = array('id=?'=>$id);  
						$actionflag = 2;
					}
					else
					{
					    $data['createdby'] = $loginUserId;
						$data['createddate'] = $date->get('yyyy-MM-dd HH:mm:ss');
						$data['isactive'] = 1;
						$where = '';
						$actionflag = 1;
					}
					$Id = $holidaygroupsmodel->SaveorUpdateGroupData($data, $where);
					if($Id == 'update')
					{
					   $tableid = $id;
					   $this->_helper->getHelper("FlashMessenger")->addMessage("Holiday Group updated successfully.");
					}   
					else
					{
                       $tableid = $Id; 	
                        $this->_helper->getHelper("FlashMessenger")->addMessage("Holiday Group added successfully.");					   
					}   
					$menuID = HOLIDAYGROUPS;
					$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
    			    $this->_redirect('holidaygroups');		
			}else
			{
     			$messages = $$holidaygroupsform->getMessages();
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
		 $messages['message'] = '';$messages['msgtype'] = '';
		 $actionflag = 3;
		    if($id)
			{
			$holidaygroupsmodel = new Default_Model_Holidaygroups(); 
			  $data = array('isactive'=>0);
			  $where = array('id=?'=>$id);
			  $Id = $holidaygroupsmodel->SaveorUpdateGroupData($data, $where);
			    if($Id == 'update')
				{
				   $menuID = HOLIDAYGROUPS;
				   $result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$id); 
				   $messages['message'] = 'Holiday group deleted successfully.';
				   $messages['msgtype'] = 'success';
				}   
				else{
                   $messages['message'] = 'Holiday group cannot be deleted.';				
				   $messages['msgtype'] = 'error';
				   }
			}
			else
			{ 
			 $messages['message'] = 'Holiday group cannot be deleted.';
			 $messages['msgtype'] = 'error';
			}
			$this->_helper->json($messages);
		
	}
	
	

}

