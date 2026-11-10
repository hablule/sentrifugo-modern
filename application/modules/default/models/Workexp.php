<?php 

class Default_Model_Workexp extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_candworkexp';
    protected $primary = 'id';
    public function saveworkexp($data)
    {
       $this->insert($data);
       $id= $this->getAdapter()->lastInsertId('main_candworkexp');
    
       return $id;
    }
    public function getjobtitles()
    {
        $db =  Zend_Db_Table::getDefaultAdapter();
        $query = "SELECT j.id, j.jobtitlename
                  FROM main_requisition r
                 INNER JOIN main_jobtitles j 
                 ON j.id = r.jobtitle
                WHERE r.isactive = 1 AND r.req_status='Approved'";
        $result=$db->query($query);
        return $result->fetchAll(); 
    }
  
}



?>