<?php

class Default_PagetabController extends Zend_Controller_Action
{
    public function init()
    {
        require_once 'application/modules/default/library/utilClasses/Util.php';

        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
        try {
            //code...
      
      $dmtmodel = new Default_Model_Pagetab();
      $call = $this->_getParam('call');

      if ($call == 'ajaxcall') {
          $this->_helper->layout->disableLayout();
      }

      $refresh = $this->_getParam('refresh');
      $dashboradcall = $this->_getParam('dashboradcall');

      $data = [];

      if ($refresh == 'refresh') {
          if ($dashboradcall == 'Yes') {
              $perPage = DASHBOARD_PERPAGE;
          }
          else{
              $perPage = PERPAGE;
          }
          $sort = 'DESC';
          $by = 'tabname';
          $pageNo = 1;
          $searchData = '';
      }
      else{
          $sort = ($this->_getParam('sort')!='') ? $this->_getParam('sort'):'DESC';
          $by = ($this->_getParam('by')!='') ? $this->_getParam('by'):'tabname';
          if($dashboradcall == 'Yes')
          $perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
          else
          $perPage = $this->_getParam('per_page',PERPAGE);

          $pageNo = $this->_getParam('page',1);
          $searchData = $this->_getParam('searchData');
          $searchData = rtrim($searchData,',');
      }
      $dataTmp = $dmtmodel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboradcall);

      array_push($data,$dataTmp);
      $this->view->dataArray = $data;
      $this->view->call = $call;
      $this->view->messages = $this->_helper->flashMessenger->getMessages();
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }

    
    public function editAction()
    {
        try {
            //code...
        $form = new Default_Form_Pagetab();
        $dmtmodel = new Default_Model_Pagetab();
        $privmodel = new Default_Model_Tabprivilage();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
		$id = $this->getRequest()->getParam('id');
		$objName = 'pagetab';

        if($id)
			{	//Edit Record...
				
				if(is_numeric($id) && $id>0)
				{
					$data = $dmtmodel->getpagetab($id);
                    $privdata = $privmodel->getPrivilagesById($id);
                    $groups =[];
                    $ids = [];
                    foreach ($privdata as  $value) {
                        if($value['isactive']=="Y"){
                        $groups[$value['group_id']] = $value['group_id'];
                        $ids[$value['group_id']] = $value['pagetabprivilage_id'];
                    }
                    }
                    $form->groups->setValue($groups);
                    
					if(!empty($data) && $data != "norows")
					{
						$form->populate($data);
						$form->submit->setLabel('Update');
                        $form->isactive->setValue(utlis::getcheckboxvalue($data['isactive'],'form'));
                        
						$this->view->form = $form;
						$this->view->controllername = $objName;
						$this->view->id = $id;
						$this->view->ermsg = '';
						$this->view->inpage = 'Edit';
						
					}
					else
					{
						$this->view->ermsg = 'norecord';
					}
				}
				else
				{
					$this->view->ermsg = 'nodata';
				}
			}
			else
			{	
                //Add Record...
				$this->view->ermsg = '';
				$this->view->form = $form;
				$this->view->inpage = 'Add';
			}
            if($this->getRequest()->getPost()){

                if($form->isValid($this->_request->getPost())){
                        $id = $this->_request->getParam('id');
                        
                        $tabname = $this->_request->getParam('tabname');
                        $tabcode = $this->_request->getParam('tabcode');
                        $parentpage_id = $this->_request->getParam('parentpage_id');

                        $description = $this->_request->getParam('description');
        
                        $date = gmdate("Y-m-d H:i:s");
        
                        $data = [
                                    'tabname'  => $tabname,
                                    'tabcode'  => $tabcode,
                                    'description'  => $description,
                                    'parentpage_id'  => $parentpage_id,
                                    'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                                    ];
                        if($id!=''){
        
                            $data['modifiedby'] = $loginUserId;
                            $data['modifieddate'] = $date;
                            $where = ['pagetab_id=?'=>$id];
                        }
                        else
                        {
                            $data['createdby'] = $loginUserId;
                            $data['createddate'] = $date;
                            $where = '';
                        }
                       
                    $Id = $dmtmodel->SaveorUpdateData($data, $where);
                    $i = 0;
                    $add_arr = array_diff($this->_getParam('groups'),$groups);
                    $del_arr = array_diff($groups,$this->_getParam('groups'));
                    $update_arr =  array_diff($this->_getParam('groups'), $add_arr);
                  Zend_Debug::dump($add_arr,'add');
                  Zend_Debug::dump($update_arr,'up');
                  Zend_Debug::dump($del_arr,'del');
                  Zend_Debug::dump($ids,'ids');
               
                     
                            $wherepriv = '';
                            if($Id == 'update')
                            {
                                
                               foreach($add_arr as $add)
                               {
                                    $data = [
                                        'pagetab_id'=>$id,
                                        'group_id'=>$add,
                                        'createddate'=>$date,
                                        'createdby'=>$loginUserId
    
                                    ];
                                    $privmodel->SaveorUpdatePrivilegesData($data,$wherepriv);
                               }
                               if(!empty($update_arr)){
                                
                               foreach($update_arr as $up)
                               {
                                    $data = [
                                        'group_id'=>$up,
                                        'modifieddate'=>$date,
                                        'modifiedby'=>$loginUserId

                                    ];
                                    $wherepriv = 'pagetabprivilage_id='.$ids[$up];
                               }
                               foreach($del_arr as $del)
                               {
                                    $privmodel->deleteprivilage($ids[$del]);
                               }
                            }
                            }
                            else{
                                foreach($this->_getParam('groups') as $value)
                                {
                                $data = [
                                    'pagetab_id'=>$Id,
                                    'group_id'=>$value,
                                    'createddate'=>$date,
                                    'createdby'=>$loginUserId
    
                                ];
                                $privmodel->SaveorUpdatePrivilegesData($data,$wherepriv);
                            }
                            
                               
                        }
                       
                        if($Id == 'update')
                        {
                            $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Tab  updated successfully."]);
                        }
                        else
                        {
                         $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"Tab added successfully."]);
                        }
                            
                        $this->_redirect('pagetab');
                    
                        
                            
                    }else{
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
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }
    
    // public function viewAction()
    // {
    //     $form = new Default_Form_Skillsource();
    //     $dmtmodel = new Default_Model_skillsource();
    //     $auth = Zend_Auth::getInstance();
    //     $id = $this->_getParam('id');
    //     $dmtdata=$dmtmodel->getskillsource($id);
    //     if ($auth->hasIdentity()) {
    //         $loginUserId = $auth->getStorage()->read()->id;
    //     }
    //     $form->populate($dmtdata);
       
     
    //     $form->isactive->setValue($dmtdata['isactive']);
    //     $this->view->form = $form; 
    //     $this->view->isactive = $dmtdata['isactive'];
    //     $this->view->form = $form;
    //     $this->view->id = $id;

    //     $this->view->controllername='skillcategory';
    // }
}
