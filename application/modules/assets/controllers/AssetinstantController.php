<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you canyou can redistribute it and/or modify
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

?>
<?php

class Assets_AssetinstantController extends Zend_Controller_Action
{
	public function init()
    {
        require_once 'application/modules/default/library/utilClasses/Util.php';

        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
	//To Load Grid
	public function indexAction()
		{
			try {
				//code...
			
			$assetsmodel = new Assets_Model_Assetinstant();
			$call = $this->_getParam('call');
			if($call == 'ajaxcall')
			$this->_helper->layout->disableLayout();
			$view = Zend_Layout::getMvcInstance()->getView();
			$objname = $this->_getParam('objname');
			$refresh = $this->_getParam('refresh');
			
			$dashboardcall = $this->_getParam('dashboardcall',null);
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

					$sort = 'DESC';$by = 'modifieddate';$pageNo = 1;$searchData = '';$searchQuery = '';
					$searchArray = array();
				
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
					$searchData = $this->_getParam('searchData');
					$searchData = rtrim($searchData,',');
				}
			$dataTmp = $assetsmodel->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall);
			array_push($data,$dataTmp);
			$this->view->dataArray = $data;
			$this->view->call = $call ;
			$this->view->messages = $this->_helper->flashMessenger->getMessages();
		} catch (\Throwable $th) {
Zend_Debug::dump($th->getMessage());		}
		}
		
	
	
	//Add or Edit The Record from the grid
	public function editAction()
		{
			try
			{
			$auth = Zend_Auth::getInstance();
			if($auth->hasIdentity())
				{
					$loginUserId = $auth->getStorage()->read()->id;
				}
			$objName = 'assets';
			$id = $this->getRequest()->getParam('id');
			$callval = $this->getRequest()->getParam('call');
			if($callval == 'ajaxcall')
			$this->_helper->layout->disableLayout();

		$popConfigPermission = array();
	    $assetsForm = new Assets_Form_Assetinstant();
		$assetsModel = new Assets_Model_Assetinstant();
	
		$msgarray = array();
		array_push($popConfigPermission,'assets');
		
		$this->view->popConfigPermission = $popConfigPermission; 
		
				
				if($id)
				{	

				//To Edit
				if(is_numeric($id) && $id>0)
						{

						$data_array = $assetsModel->getAssetinstantDetailsById($id);
				
					//Permenant Image Deletion on update
					
						
						if(!empty($data_array) && $data_array != "norows")
						{	
							$assetsModel = new Assets_Model_Assetinstant();
							$assetsForm = new Assets_Form_Assetinstant();
							
							
							$assetsForm->populate($data_array[0]);	
							$assetsForm->isactive->setValue(utlis::getcheckboxvalue($data_array[0]['isactive'],'form'));
				
							$assetsForm->submit->setLabel('Update');
							$this->view->form = $assetsForm;
							$this->view->controllername = $objName;
							$this->view->id = $id;
							$this->view->ermsg = '';
							$this->view->inpage = 'Edit';
							$this->view->data_array = $data_array;

						
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
					//To Add
					$this->view->ermsg = '';
					$this->view->form = $assetsForm;
					$this->view->inpage = 'Add';
				  
					
				}
			
		if($this->getRequest()->getPost()){
			
				if($assetsForm->isValid($this->_request->getPost())){
					$id = $this->_request->getParam('id');
					$asset_id	= $this->_request->getParam('asset_id');
					$serialidentification	= $this->_request->getParam('serialidentification');
					$description = $this->_request->getParam('description');
				$date = gmdate("Y-m-d H:i:s");		
				$data = array( 
								'asset_id' => $asset_id,
								'serialidentification'=> $serialidentification,
								'description'=>$description,
								'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),

							);	
				//ToCheckThe EncrpName
				if($id!='')
					{
						$data['modifiedby'] = $loginUserId;
						$data['modifieddate'] = $date;
						$where = array('assetinstant_id=?'=>$id);
					}
				else
					{
						$data['createdby'] = $loginUserId;
						$data['createddate'] = 	$date;
						$data['modifieddate'] = $date;
						$data['modifiedby'] = $loginUserId;
						$where = '';
					}
				
					$Id = $assetsModel->saveOrUpdateAssetsData($data, $where); 
	
				if($Id == 'update')
				{
					$this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Asset Instant updated successfully."));
					//insert asset history
					
				}
				else
				{	
					
					
					$this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Asset Instant added successfully."));
					
				}
					
				$this->_redirect('assets/assetinstant');
			}else
			{
				$messages = $assetsForm->getMessages();
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
			
		 catch(Exception $e)
		{
			Zend_Debug::dump($e->getMessage());
			$this->view->ermsg = 'nodata';
		} 
	}
	
	public function deleteAction()
	{ 
		
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->_request->getParam('objid');
		$deleteflag= $this->_request->getParam('deleteflag');
		$messages['message'] = ''; $messages['msgtype'] = '';
		$messages['flagtype'] = '';
		$actionflag = 3;
		if($id)
		{
			
			$assetsModel = new Assets_Model_Assets();
			$isexist = $assetsModel->isAssetExistForSetting($id);
			if($isexist==0 )
			{
				$data = array('isactive'=>0,'modified'=>gmdate("Y-m-d H:i:s"));
				$data['modified_by'] = $loginUserId;
				$where = array('id=?'=>$id);
				$id = $assetsModel->saveOrUpdateAssetsData($data, $where);
				if($id == 'update')
				{
					$messages['message'] = 'Asset has deleted successfully.';
					$messages['msgtype'] = 'success';
					
				}
				else
				{
					$messages['message'] = 'Asset cannot be deleted.';
					$messages['msgtype'] = 'error';
				}
			}else
				{
						
					$messages['message'] = 'Asset cannot be deleted.';
					$messages['msgtype'] = 'error';
				}

			}
		else
		{
			$messages['message'] = 'Asset cannot be deleted.';$messages['msgtype'] = 'error';
		}
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
	

	public function uploadsaveAction()
	{
			
		$assetsModel = new Assets_Model_Assets();
		$user_id = sapp_Global::_readSession('id');
		$image;
        $filedata = array();
    	// Validate file with size greater than default(Upload Max Filesize)limit
        if ($_FILES["myfile"]["size"] == 0 || $_FILES["myfile"]["size"] > (2*1024*1024)) 
			{
				$this->_helper->json(array('error' => 'filesize'));
			}
		else if(isset($_FILES["myfile"])) {
            $fileName = $_FILES["myfile"]["name"];
			$image = $fileName;
            $fileName = preg_replace('/[^a-zA-Z0-9.\']/', '_', $fileName);			  	
            $newName  = time().'_'.$user_id.'_'.str_replace(' ', '_', $fileName); 
            $filedata['original_name'] = $fileName;
            $filedata['new_name'] = $newName;
			$file_type_array = explode('.',$filedata['original_name']);
			$file_type = $file_type_array[1];
			
			
			move_uploaded_file($_FILES["myfile"]["tmp_name"],ASSETS_IMAGES_TEMP_PATH.$newName);
        	   
			$this->_helper->json(array('filedata' => $filedata));
			//echo $newName;die;
			
        } 
	}
	
    public function uploaddeleteAction()
    {	
	
    	 if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['doc_new_name']))
        {
        	$filePath = "";
        	if(isset($_POST["doc_id"]) && $_POST["doc_id"] != ''){
        		// Update attachments field in database by removing deleted attachment
        		$assetsModel = new Assets_Model_Assets();
				$assetImages = $assetsModel->getAssetsDetailsById('id',$_POST["doc_id"]);
				if($empDocuments[0]['attachments']){
					$attData = json_decode($empDocuments[0]['attachments'],true);
					//echo "It is here while deleting ??";exit;
					foreach ($attData as $key => $attachment) {
						if ($attachment['new_name'] == $_POST['doc_new_name']) {
							unset($attData[$key]);
							break;
						}
					}
					
					$data = array('attachments'=>(count($attData)>0)?json_encode($attData):null);
					$where = array('id=?'=>$_POST["doc_id"]);
					$empDocuModel->SaveorUpdateEmpDocuments($data, $where);
				}
				$filePath = ASSETS_IMAGES_TEMP_PATH.$_POST['doc_new_name'];	
				
				// Remove attachment files from upload folder.
	            if (file_exists($filePath)) {
	            	 unlink($filePath);
	            }
				
	            // Update photo gallery with removed attachment.
	            $this->view->path = CA_FILES_PATH;
				$this->view->attachments = $attData;
				$this->view->doc_id = $_POST["doc_id"];				
							
        	}else{
				
        		$filePath =ASSETS_IMAGES_TEMP_PATH.$_POST['doc_new_name'];
	            if (file_exists($filePath)) {
	                unlink($filePath);
	            }
	            
	            $this->_helper->json(array());
        	}
        }
    }
	
	public function viewAction()
	{
		try {
		
		$ImageName=array();
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$objName = 'assetinstant';
				$id = $this->getRequest()->getParam('id');
				$callval = $this->getRequest()->getParam('call');
				$assetsForm = new Assets_Form_Assetinstant();
				$assetsModel = new Assets_Model_Assetinstant();
				$data = $assetsModel->getAssetinstantDetailsByIdview($id);

				if($id)
					{
						$this->view->data= $data;
						 $this->view->controllername=$objName;
					}
						//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}	
	
	public function getsubcategoriesAction()
	{
		$cnval = $this->_getParam('cnval');
		$opt= '<option value=\'\'>Select Sub Category</option>';
		if($cnval!='')
		{
			$SubCategoriesModel = new Assets_Model_AssetCategories();
			$SubCategoriesData = $SubCategoriesModel->getAssetSubCategoriesDetailsById($cnval);
			foreach($SubCategoriesData as $SubCategories)
			{
				$opt.="<option value='".$SubCategories['id']."'>".$SubCategories['name']."</option>";
			}
		}
			
		$this->_helper->json(array('options'=>utf8_encode($opt)));
		
	}
	public function deleteimageAction()
	{
		$filePath = "";
		$doc_new_name = $this->_getParam('doc_new_name');
	    $filePath = ASSETS_IMAGES_TEMP_PATH.$doc_new_name;	
			// Remove attachment files from upload folder.
	          if (file_exists($filePath)) 
					{
	            	 unlink($filePath);
					}
				 $this->_helper->json(array());
	} 

	public function downloadimageAction()
	{
		
		$this->view->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$image_names=$this->_getParam('image_names');
		$imgNames=explode(',',$image_names);
		$zip_file_name='myfile.zip';
		$file_path=DOMAIN.'public/uploads/assets_images_temp/';
		//print_r($imgNames);die;
		if(sizeof($imgNames) > 1){
			//zip
			$this->zipFilesDownload($imgNames);
		}else{
			
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$imgNames[0].'"');
		//	echo $imgNames[0];die;
			readfile($file_path.$imgNames[0]);
		}
		//die; 
		
	}
	function zipFilesDownload($imgNames){
		$file_path = ASSETS_IMAGES_TEMP_PATH;
		$archive_file_name = time().rand(8,8).'.zip';
	
		$zip = new ZipArchive();
		if ($zip->open($file_path.$archive_file_name, ZIPARCHIVE::CREATE )!==TRUE) {
			exit("cannot open <$archive_file_name>\n");
	
		}
	
		foreach($imgNames as $files){
			$zip->addFile($file_path.$files,$files);
		}
		$zip->close();
	
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$archive_file_name.'"');
		readfile($file_path.$archive_file_name);
	}
  public	function getemployeesdataAction(){
	    $ajaxContext = $this->_helper->getHelper('AjaxContext');
	    $ajaxContext->addActionContext('getemployeesdata', 'html')->initContext();
		$bunit_id=$this->_getParam('bunitid');
		$employeemodel = new Default_Model_Employee();
		$employeeData = $employeemodel->getEmployeesForServiceDesk($bunit_id);
		$this->view->employeeData=$employeeData;
		
	
	}
	public function addpopupAction()
    {
        try {
            Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");       //code...
      
        $form = new Assets_Form_Assetdetails();
        $dmtmodel = new Assets_Model_Assetinstant();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        	 
		 $isfrompopup = $this->getRequest()->getParam('isfrompopup');
         $isfrompopup = !empty($isfrompopup)?$this->getRequest()->getParam('isfrompopup'):'';
         $this->view->isfrompopup=$isfrompopup;
 
        $id = $this->_getParam('id');
        $form->setAction(BASE_URL.'assets/assetinstant/addpopup/id/'.$id);
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $data = array(
                    'asset_id'=>$this->_request->getParam('asset_id'),
                    'serialidentification'=>$this->_request->getParam('serialidentification'),
                    'description'=>$this->_request->getParam('description'),
					'isactive'=>utlis::getcheckboxvalue($this->_request->getParam('isactive')),
                    'createdby'=>$loginUserId,
                    'createddate'=>gmdate('Y-m-d H:i:s')
                );
                $Id= $dmtmodel->saveOrUpdateAssetsData($data,'');
                $tableid = $Id;
                $actionflag = 1;
                $result = sapp_Global::logManager(344,$actionflag,$loginUserId,$tableid);
               $this->view->popup = 'close';
               $linecategoryData = $dmtmodel->fetchAll('isactive = "Y"','name')->toArray();
				$opt ='';
				foreach($linecategoryData as $record){
					$opt .= sapp_Global::selectOptionBuilder($record['assetinstant_id'], $record['serialidentification']);
				}
                if ($id==1) {
                    $this->view->nested=true;
                }
                else{
                    $this->view->nested = false;
                }
				$this->view->assetinstant = $opt;
               $this->view->controllername = 'assetinstant';
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
    } catch (\Throwable $th) {
        Zend_Debug::dump($th->getMessage());
    }
    }
}
