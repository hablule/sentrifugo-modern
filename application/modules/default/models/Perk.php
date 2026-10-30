<?php

class Default_Model_Perk extends Zend_Db_Table_Abstract{
    protected $_name = 'main_perk';
    protected $_id = 'perk_id';

    public function addData($data){
        //die(json_encode($data));
        $this->insert($data);
        return $this->getAdapter()->lastInsertId();
    }
    public function adddatas($data){
        $config = array(
            'name' => 'main_perkcategory',
            'primary' => 'perkcategory_id'
        );
        $this->table = new Zend_Db_Table($config);
        $this->table->insert($data);
    }
    public function addCategory($data){
        $config = array(
            'name' => 'main_perkcategory',
            'primary' => 'perkcategory_id'
        );
        $this->table = new Zend_Db_Table($config);
        $this->table->insert($data);
    }
    public function editPerk($data,$where){
        $this->update($data,$where);
    }
    public function deleteperk($id)
    {
        $config = array(
            'name' => 'main_perk',
            'primary' => 'perk_id'
        );
        $OT_schedule_table = new Zend_Db_Table($config);
        $data = array(
            'isactive' => 'N'
        );
        $this->update($data, 'perk_id = ' . $id);
    }
    public function getCategory(){
        $query = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('pc'=>'main_perkcategory'))
                ->where('isactive="Y"');
        return $this->fetchAll($query)->toArray();
    }
     public function getPerk(){
         $query = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p'=>'main_perk'))
                    ->where('isactive="Y"');
        return $this->fetchAll($query)->toArray();
     }
     public function getIndividualPerk(){
        $query = $this->select()
                   ->setIntegrityCheck(false)
                   ->from(array('p'=>'main_perk'))
                   ->where('p.isactive="Y" and p.type="Individual" and p.auto_approved="N"');
       return $this->fetchAll($query)->toArray();
    }
     public function getSinglePerk($id){
         $query = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('p'=>'main_perk'))
                        ->where('perk_id='.$id);
        return $this->fetchAll($query)->toArray();
     }
     public function getPerkDetail($id){
        $result = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('p'=>'main_perk'),array('perk_id'=>'p.perk_id','code'=>'p.code','name'=>'p.name','description'=>'p.description','start'=>'p.start','end'=>'p.end','type'=>'p.type','amount'=>'p.amount','active'=>'p.isactive','classPath'=>'p.classPath','classfun'=>'p.method','parameters'=>'p.parameters'))
                        ->joinInner(array('pc'=>'main_perkcategory'),'pc.perkcategory_id=p.category',array('cname'=>'pc.name'))
                        ->where("p.perk_id=".$id);
        //die($result);
        return $this->fetchAll($result)->toArray();
    }
    public function getPerkDetailview($id){
        $result = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('p'=>'main_perk'),array('perk_id'=>'p.perk_id','code'=>'p.code','name'=>'p.name','description'=>'p.description','start'=>'p.start','end'=>'p.end','type'=>'p.type','amount'=>'p.amount','active'=>'if(p.isactive="Y","Yes","No")','classPath'=>'p.classPath','classfun'=>'p.method','parameters'=>'p.parameters'))
                        ->joinInner(array('pc'=>'main_perkcategory'),'pc.perkcategory_id=p.category',array('cname'=>'pc.name'))
                        ->where("p.perk_id=".$id);
        //die($result);
        return $this->fetchAll($result)->toArray();
    }
    // public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$p1,$p2,$p3,$p4)
    // {
    //     $searchQuery = '';
    //     $searchArray = array();
    //     $data = array();
    //     $db = Zend_Db_Table::getDefaultAdapter();
    //     if($searchData != '' && $searchData!='undefined')
    //     {
    //         $searchValues = json_decode($searchData);

    //         if(count($searchValues) >0)
    //         {                   
    //             foreach($searchValues as $key => $val)
    //             {
    //                 $searchQuery .= " ".$key." like '%".$val."%' AND ";
    //                 $searchArray[$key] = $val;
    //             }
    //             $searchQuery = rtrim($searchQuery," AND");					
    //         }
    //     }
        
    //     $objName = 'main_perk';

    //     $tableFields = array('action'=>'Action',
    //                          'code' => 'Code',
	// 						 'name' => 'name',
    //                          'category' => 'category ',
    //     		            'description' => 'Status',
    //     					 'start' => 'start',
							 
    //                          'end' => 'end',
    //                          'type' => 'type ', 
        		              
    //                         );

     

    //     $dataTmp = array(
    //             'sort' => $sort,
    //             'by' => $by,
    //             'pageNo' => $pageNo,
    //             'perPage' => $perPage,				
    //             'tablecontent' => $tablecontent,
    //             'objectname' => $objName,
    //             'extra' => array(),
    //             'tableheader' => $tableFields,
    //             'jsGridFnName' => 'getAjaxgridData',
    //             'jsFillFnName' => '',
    //             'searchArray' => $searchArray,
    //             'add' => 'add',
    //             'call'=>$call,
	// 			'dashboardcall'=>$dashboardcall,
    //             'search_filters' => array(
    //                 'cand_status' => array(
    //                     'type' => 'select',
    //                     'filter_data' => $cand_status_opt,
    //                 ),
    //             ),
    //     );
    //     return $dataTmp;
    // }
    public function DeleteData($where)
	{

			$this->delete($where);
			return 'delete';
	
		
	}
}
?>