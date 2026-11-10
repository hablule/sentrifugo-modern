<?php

class Default_Model_Deductive extends Zend_Db_Table_Abstract{
    protected $_name = 'main_load';
    protected $_id = 'load_id';

    public function addData($data){
      // die(json_encode($data));
        $this->insert($data);
        return $this->getAdapter()->lastInsertId();
    }
    public function addCategory($data){
        //die(json_encode($data));

        $config = [
            'name' => 'main_loadcategory',
            'primary' => 'load_category_id'
        ];

        $this->table = new Zend_Db_Table($config);
        $this->table->insert($data);
        return $this->getAdapter()->lastInsertId($config['name'],$config['primary']);
    }
    public function editDeductive($data,$where){
        $this->update($data,$where);
    }
    public function deleteDeductive($data,$where){
        //die(json_encode($data));

        $this->update($data,$where);
        
    }
    public function deleteded($id)
    {
        $config = [
            'name' => 'main_load',
            'primary' => 'load_id'
        ];
        $OT_schedule_table = new Zend_Db_Table($config);
        $data = [
            'isactive' => 'N'
        ];
        $this->update($data, 'load_id = ' . $id);
    }
    public function getCategory(){
        $query = $this->select()
                ->setIntegrityCheck(false)
                ->from(['pc'=>'main_loadcategory'])
                ->where('isactive="Y"');
        return $this->fetchAll($query)->toArray();
    }
     public function getDeductive(){
         $query = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(['p'=>'main_load'])
                    ->where('isactive="Y"');
        return $this->fetchAll($query)->toArray();
     }
     public function getIndividualLoad(){
        $query = $this->select()
                   ->setIntegrityCheck(false)
                   ->from(['l'=>'main_load'])
                   ->where('l.isactive="Y"  and l.autoapprove="N"');
       return $this->fetchAll($query)->toArray();
    }
     public function getSingleDeductive($id){
         $query = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(['p'=>'main_load'])
                        ->where('load_id='.$id);
        return $this->fetchAll($query)->toArray();
     }
     public function getEmpDeductive($emp_id){
        $result =  $this->select()
                     ->setIntegrityCheck(false) 	
                     ->from(['e'=>'main_employeeload'],['e.load_id','e.amount'])
                        ->where("e.emp_id = ".$emp_id);
        return $this->fetchAll($result)->toArray();
     }
     public function getDeductiveType($id){
        $query = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(['p'=>'main_load'],['p.*'])
                        ->where('p.load_id='.$id);
        return $this->fetchAll($query)->toArray();
    }
    
    public function AddEmpDeductiveData($data)
    {
       // die(json_encode($data));
        $config = [
            'name' => 'main_employeeload',
            'primary' => 'employeeload_id'
        ];

        $this->table = new Zend_Db_Table($config);
        $this->table->insert($data);
    }
    public function deleteEmpDeductiive($emp_id){
        $config = [
            'name'=>'main_employeeload',
            'primary'=>'employeeload_id'
        ];
        $this->table = new Zend_Db_Table($config) ;
        $this->table->delete('emp_id='.$emp_id);
    }
    public function getDeductiveDetail($ded_id){
        $result = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(['d'=>'main_load'],['deductive_code'=>'d.code','deductive_name'=>'d.name','deductive_description'=>'d.description','deductive_start'=>'d.start','deductive_end'=>'d.end','deductive_type'=>'d.type','ispenality'=>'d.ispenality','istax'=>'d.istax','autoapprove'=>'d.autoapprove','deductive_amount'=>'d.amount','deductive_active'=>'d.isactive','category_name'=>'dc.name','category_description'=>'dc.description'])
                        ->joinInner(['dc'=>'main_loadcategory'],'dc.load_category_id=d.load_category_id')
                        ->where("d.load_id=".$ded_id)
                        ->group('deductive_name');
        
        return $this->fetchAll($result)->toArray();
    }
    public function getDeductiveDetailview($ded_id){
        $result = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(['d'=>'main_load'],['deductive_code'=>'d.code','deductive_name'=>'d.name','deductive_description'=>'d.description','deductive_start'=>'d.start','deductive_end'=>'d.end','deductive_type'=>'d.type','ispenality'=>'if(d.ispenality="N","No","Yes")','istax'=>'if(d.istax="N","No","Yes")','autoapprove'=>'if(d.autoapprove="N","No","Yes")','deductive_amount'=>'d.amount','deductive_active'=>'if(d.isactive="N","No","Yes")','category_name'=>'dc.name','category_description'=>'dc.description'])
                        ->joinInner(['dc'=>'main_loadcategory'],'dc.load_category_id=d.load_category_id')
                        ->where("d.load_id=".$ded_id)
                        ->group('deductive_name');
        
        return $this->fetchAll($result)->toArray();
    }
    public function DeleteData($where)
	{

			$this->delete($where);
			return 'delete';
	
		
	}

}
?>