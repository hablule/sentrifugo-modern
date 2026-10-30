<?php
class Assets_Model_Assets extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_asset';
	protected $_primary = 'asset_id';
	 
	 	public function getAssetsData($sort, $by, $pageNo, $perPage,$searchQuery)
			{
				
				$where = " 1";
				if($searchQuery) 
				$where .= " AND ".$searchQuery;
				$db = Zend_Db_Table::getDefaultAdapter();
				
		   $AssetsData = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(['a'=>'main_asset'], ['a.*','id'=>'a.asset_id','category_name'=>'ac.name','type_name'=>'at.name','uom_name'=>'u.name','isactive'=>'if(a.isactive="Y","Yes","No")'])
                       ->joinLeft(['ac'=>'main_assetcategory'],"a.assetcategory_id = ac.assetcategory_id ",[])
					   ->joinLeft(['at'=>'main_assettype'],"a.assettype_id = at.assettype_id ",[])
					   ->joinleft(['u'=>'main_uom'],"a.uom_id = u.uom_id",[])
                       ->where($where)
                       ->order("$by $sort")
                       ->limitPage($pageNo, $perPage);
               return $AssetsData;

			}
	 
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$a='',$b='',$c='',$d='')
	{
		$searchQuery = '';
		$searchArray = [];
		$data = [];

		if($searchData != '' && $searchData!='undefined')
		{
			$searchValues = json_decode($searchData);
			foreach($searchValues as $key => $val)
			{
				$searchValues = json_decode($searchData);
				
				if($key == 'category_name'){
					$searchQuery .= " ac.name like '%".$val."%' AND ";
				}
				else if($key == 'type_name'){
				
					$searchQuery .= " at.name like '%".$val."%' AND ";
					
				}else if($key == 'uom_name'){
				
					$searchQuery .= " u.name like '%".$val."%' AND ";
					
				}else {
					
					$searchQuery .= " a.".$key." like '%".$val."%' AND ";
				}
				$searchArray[$key] = $val;
			}
			
			 $searchQuery = rtrim($searchQuery," AND");
		
		}
			
		$objName = 'assets';

		$tableFields = [
		
					'action'=>'Action',
							'name'  => 'Name',
							'code'  => 'Code',
							'category_name'  => 'Category',
							'type_name'  => 'Type',
							'uom_name'=>'UOM',		
							'description'  => 'Description',
				            'isactive'  => 'Active',
							
							//These Fields are not Displaye
							//'location'  =>	'location',
							//'vendor'  => 'vendor',   
							//'manufacturer'  => 'Manufacturer',
							//'allocated_to'  => 'Allocated to',
							//'responsible_technician'  => 'Responsible',
							
							/*'purchase_date'  => 'purchase_date',
							'invoice_number'  => 'invoice_number',
							'key_number'  => 'key_number',
							'warenty_status'  => 'warenty_status',
							'warenty_end_date'  => 'warenty_end_date',
							'notes'=>'notes' */
		];

		$tablecontent = $this->getAssetsData($sort, $by, $pageNo, $perPage,$searchQuery);

		$dataTmp = [
			'sort' => $sort,
			'by' => $by,
			'pageNo' => $pageNo,
			'perPage' => $perPage,				
			'tablecontent' => $tablecontent,
			'objectname' => $objName,
			'extra' => [],
			'tableheader' => $tableFields,
			'jsGridFnName' => 'getAjaxgridData',
			'jsFillFnName' => '',
			'searchArray' => $searchArray,
			'call'=>$call,
			'dashboardcall'=>$dashboardcall,
			'menuName' => 'Asset'
			];
			return $dataTmp;
	}
	
	public function getLocation()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select * from main_businessunits where isactive = 1 ";
		$result = $db->query($query)->fetchAll(); 
		return $result;
		
	}
	public function getvendorsname()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select name,id from main_vendors where isactive = 1 ";
		$result = $db->query($query)->fetchAll(); 
		return $result;
		
	}
	public function saveOrUpdateAssetsData($data, $where)
	{		
		if($where != '')
			{	
				  $this->update($data, $where);
				return 'update';
			} 
		else
			{
				
				$this->insert($data);
				$id=$this->getAdapter()->lastInsertId('assets');
				return $id;
				
			}
	}
	public function getAssetsDetailsById($id)
	{	
	
		 $select = $this->select()
					->setIntegrityCheck(false)
						->from(['a'=>'main_asset'],['a.*','id'=>'a.asset_id'])
						->where('a.asset_id='.$id.' '); 
		return $this->fetchAll($select)->toArray();	
	}
	public function getAssetsDetailsByIdview($id)
	{	
	
		 $select = $this->select()
		 ->setIntegrityCheck(false)
		 ->from(['a'=>'main_asset'], ['a.*','id'=>'a.asset_id','category_name'=>'ac.name','type_name'=>'at.name','uom_name'=>'u.name'])
		 ->joinLeft(['ac'=>'main_assetcategory'],"a.assetcategory_id = ac.assetcategory_id ",[])
		 ->joinLeft(['at'=>'main_assettype'],"a.assettype_id = at.assettype_id ",[])
		 ->joinleft(['u'=>'main_uom'],"a.uom_id = u.uom_id",[])
						->where('a.asset_id='.$id.' '); 
		return $this->fetchAll($select)->toArray();	
	}
	public function getActiveAssetData()
	{
		
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['c'=>$this->_name],['c.id','c.client_name'])
		->where('c.is_active = 1 ')
		->order('c.client_name');
		return $this->fetchAll($select)->toArray();
	}
	public function saveReceipts($data,$where)
	{
		
		if($where != ''){
			$this->update($data, $where);
			return 'update';
		} 
		else 
		{
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('assets');
			return $id;
		}
	}
	public function InsertToHistoryTable($data)
	{
	
		//$query = "insert into assets_history values("$data['id']","$data['allocated_to']")"
		$db = Zend_Db_Table::getDefaultAdapter();
		$db->insert('assets_history', $data);
		return $id = $db->lastInsertId();
			
	}
	public function getUsernameofAllocatedAsset($id)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select * from assets where isactive = 1 AND  $id= '.$id.'";
		$username = $db->query($query)->fetchAll(); 
		return $username;
		
	}
	public function getAssetHistory($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['c'=>'assets_history'],['c.*'])
		->joinLeft(['mc'=>'main_employees'],"mc.id = c.createdby ",['userfullname'=>'mc.userfullname'])
		->where('c.isactive = 1 and asset_id = '.$id);
		return $this->fetchAll($select)->toArray();
	}
public function	isAssetExistForSetting($id){
	
	$db = Zend_Db_Table::getDefaultAdapter();
	$query = "SELECT COUNT(*) as count FROM main_sd_requests_summary WHERE isactive =1 AND service_desk_id=".$id;
	$result = $db->query($query)->fetch();
	return $result['count'];
	
}
public function getAllAssetForCategory($assetcategory_id){
 $assetsData = $this->select()
					->setIntegrityCheck(false)
					->from(['c'=>$this->_name],['c.id','c.name'])
					->where('c.isactive = 1 AND c.category='.$assetcategory_id.' ');
		return $this->fetchAll($assetsData)->toArray();
 } 
}