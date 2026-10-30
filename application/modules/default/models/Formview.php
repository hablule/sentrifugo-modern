<?php 

class Default_Model_Formview extends Zend_Db_Table_Abstract{
    public $_name = 'main_form_element';
    public $_primary = 'id';

    public function getAllElements($formName){
        if($formName == "ALL"){
            $elements = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('e'=>'main_form_element'),array('e.*'));
                        
            return  $this->fetchAll($elements)->toArray();
        }else{
            $elements = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('e'=>'main_form_element'),array('e.*'))
                            ->where('e.formName = "'.$formName.'"');
            return  $this->fetchAll($elements)->toArray();
        }
        
        
    }

    public function getActiveElements($formName){
        $elements = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('e'=>'main_form_element'),array('e.*'))
                        ->where('e.formName = "'.$formName.'" AND e.isActive=1');
        return  $this->fetchAll($elements)->toArray();

    }

   

    public function getAllFormNames(){
        $formNames = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('f'=>'main_formname'),array('f.*'))
                        ->where('f.isActive = 1');
        return $this->fetchAll($formNames)->toArray();
    }

    public function getAllElementTypes(){
        $elementTypes = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('e'=>'main_form_dataType'),array('e.*'))
                            ->where('e.isActive = 1');
        return $this->fetchAll($elementTypes)->toArray();
    }

    public function getAllTables(){
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "SHOW TABLES";
        $tables = $db->query($query);
        return $tables->fetchAll();
    }

    public function getAllColumns($tableName){
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "SHOW COLUMNS FROM ".$tableName;
        $columns = $db->query($query);
        return $columns->fetchAll();
    }

    public function getSelectData($tablename,$colname){
        $data = $this->select()->setIntegrityCheck(false)->from(array('e'=>$tablename),array('e.id','e.'.$colname));
        /*$db = Zend_Db_Table::getDefaltAdapter();
            $query = 'SELECT id,'.$colname .' FROM '.$tablename;
            $result=$db->query($query);
            return $result->fetchAll();
        */
        return $this->fetchAll($data)->toArray();
    }

    public function getFormElements($formName){
        $query = $this->select()
                     ->setIntegrityCheck(false)
                     ->from(array('f'=>'main_form_element'),array('f.*'))
                     ->where('f.formName = "'.$formName.'"');
                           
        return $this->fetchAll($query)->toArray();
    }
    public function getelmentvalidators(){
        $query = $this->select()
                     ->setIntegrityCheck(false)
                     ->from(array('v'=>'tbl_validator'),array('v.*'))
                     ->where('v.isActive = 1');
                           
        return $this->fetchAll($query)->toArray();
    }
    public function getRequestedData($tableName,$columnName,$value, $queryWith){
        
        $query = $this->select()
                     ->setIntegrityCheck(false)
                     ->from(array('t'=>$tableName), array('t.id','t.'.$columnName))
                     ->where('t.'.$queryWith.' = "'.$value.'"');
       
        return $this->fetchAll($query)->toArray();
    }

    public function getValidators($validators){
        $where = '';
        $validators = explode(',',$validators);
        foreach($validators AS $validator){
            if($where == ''){
                $where = 'v.id = '.$validator;
            }else{
                $where = $where.' OR v.id = '.$validator;
            }
        }
        $query = $this->select()
                      ->setIntegrityCheck(false)
                      ->from(array('v'=>'tbl_validator'),array('v.*'))
                      ->where($where);
                    
        return $this->fetchAll($query)->toArray();
    }
    
    
    public function addElement($data){
        $this->insert($data);
    }

    public function updateElements($id,$elementsData){
        for($i=0; $i<count($id); $i++){
             $this->update($elementsData[$i],'id='.$id[$i]);
        }
         
    }

    public function addElementColumnToTable($tableName,$columnName,$columnType){
        $db = Zend_Db_Table::getDefaultAdapter();
        
        $query = "ALTER TABLE ".$tableName." ADD ".$columnName." " .$columnType;
        try{
        $db->query($query);
        }catch(Exception $e)
        {
          
        }
    }
}
