<?php

class Default_Model_Mvc extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_employees';
    protected $_primary = 'id';

    public function getAllTables()
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['t' => 'information_schema.TABLES'], ['t.TABLE_NAME'])
            ->where('t.TABLE_SCHEMA="sentrifugo"');

        return $this->fetchAll($query)->toArray();
    }

    public function getTablePK($table_name)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['c' => 'information_schema.COLUMNS'], ['c.COLUMN_NAME'])
            ->where('c.TABLE_SCHEMA="sentrifugo" AND c.TABLE_NAME="' . $table_name . '" AND c.EXTRA="auto_increment"');
        return $this->fetchAll($query)->toArray();
    }
}
