<?php

class Formutil_Model_addform extends Zend_Db_Table
{
    public function geteletype()
    {
        $config = [
            'name'=>'main_form_datatype',
            'primary'=>'dataType'
        ];
        $table = new Zend_Db_Table($config);
        $data  = $table->select()
                       ->setIntegrityCheck(false)
                       ->where('isactive = 1');
        return $table->fetchAll($data)->toArray();
    }

    public function modules()
    {
        $config = [
            'name'=>'main_menu',
            'primary'=>'id'
        ];
        $table = new Zend_Db_Table($config);
        $data = $table->select()
                      ->setIntegrityCheck(false)
                      ->from($config['name'],'modulename')
                      ->distinct(true);
        return $table->fetchAll($data)->toArray();
    }
}


?>