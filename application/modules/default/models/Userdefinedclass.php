<?php

class Default_Model_Userdefinedclass extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_users';
    protected $_primary = 'employeeId';
    public function getTypeMatchedFields($filedType,$tables,$colname)
    {
        if ($colname=='') {
            $colname = '';
        }
        else
        {
            $colcond=" AND cols.COLUMN_NAME LIKE '%".$colname."%'";
        }
        
        $where = "TABLE_SCHEMA='sentrifugo' AND DATA_TYPE = '" . $filedType . "' AND EXTRA <> 'auto_increment' 
        AND TABLE_NAME IN(".$tables.")".$colname;
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('cols' => 'information_schema.COLUMNS'), array('fieldName' => 'CONCAT(cols.TABLE_NAME,CONCAT(".",cols.COLUMN_NAME))'))
            ->where($where);
        //die($query);
        return $this->fetchAll($query)->toArray();
    }

    public function getUserDefinedFunction($table)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('t' => $table), array('t.classPath','t.method', 't.parameters'))
            ->where("t.type='Calculated'");
        //die($query);
        return $this->fetchAll($query)->toArray();
    }//FK_tm_emp_timesheets_employee, NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION

    public function getParameterValues($query)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $res = $db->query($query);
        return $res->fetchAll();
        // $query = $this->select()
        //     ->setIntegrityCheck(false)
        //     ->from($table,$parameter);
        //     return $this->fetchAll($query)->toArray();
    }

    public function getReferencedTables($tableName){
        $query = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t'=>'information_schema.KEY_COLUMN_USAGE'), 
                    array('table'=>'t.TABLE_NAME', 'column'=>'t.COLUMN_NAME',
                            'referenced_table'=>'t.REFERENCED_TABLE_NAME',
                            'referenced_column'=>'t.REFERENCED_COLUMN_NAME'))
                ->where("t.TABLE_NAME='".$tableName."' AND t.REFERENCED_TABLE_NAME!=''");
              //die($query); 
        return $this->fetchAll($query)->toArray();
    }
}
