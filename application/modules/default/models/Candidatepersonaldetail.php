<?php 


class Default_Model_Candidatepersonaldetail extends Zend_Db_Table_Abstract
{


    protected $_name = 'main_candpersonalprofile';
    protected $_primary = 'id';

    public function Savepersonalinfo($data)
    {
        $this->insert($data);
        $id = $this->getAdapter()->lastInsertId('main_candpersonalprofile');
        return $id;
    }


}



?>