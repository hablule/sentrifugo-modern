<?php

class Default_Model_Deviceuser extends Zend_Db_Table_Abstract{
    protected $_name = "main_device_users";
    protected $_primary = "id";

    public function getDeviceUsers($sort, $by, $pageNo, $perPage,$searchQuery,$userid)
    {	
        $where = "user_id = ".$userid ;

        if($searchQuery)
            $where .= " AND ".$searchQuery;
        $db = Zend_Db_Table::getDefaultAdapter();		

        $dependencyData = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('d'=>'main_device_users'),array('user_id as id','firstname'=>'firstname','middlename'=>'middlename','relationship_id'=>'relationship_id','dateofbirth'=>'DATE_FORMAT(dateofbirth,"'.DATEFORMAT_MYSQL.'")'))
                        ->join(array('r'=>'main_relationship'),'r.relationship_id = d.relationship_id')
                       ->where($where)
                       ->order("$by $sort") 
                       ->limitPage($pageNo, $perPage);

        return $dependencyData;         		
    }
    public function saveAndUpdate($data, $where)
    {
        if ($where == '') {
            $this->insert($data);
           
            return $this->getAdapter()->lastInsertId();
        } else {
            $this->update($data, $where);
            return $where;
        }
    }
}

?>