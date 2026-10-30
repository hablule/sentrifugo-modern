<?php

class Default_Model_Eulalaname extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_states';
    protected $_primary = 'id';

    public function getName()
    {
        $where = "s.isactive = 1";
        $data = $this
                    ->select()
                    ->setIntegrityCheck(false)
                    ->from(array('s' => 'main_states'), array('s.*'))
                    ->where($where);
                   
        return $this->fetchAll($data)->toArray();

        // $id=$this->getAdapter()->lastInsertId('main_states');
        // return $id;
    }
}
