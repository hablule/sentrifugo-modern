<?php

class Payroll_Utitlites{

public function __construct()
{
    require_once 'application/modules/payroll/models/cronjob.php';
}
    public function checkaddundum($addundum)
    {
       
            if ($addundum['perk_id']!=null) {
                return 'perk';
            }
            elseif ($addundum['load_id']!=null) {
                return 'load';
            }
            else{
                return 'null';
            }
        
        
    }
    public function getdata($id,$table,$empid)
    {
        $model = new Payroll_Model_cronjob();
        
        if ($table=='overtime') {
            return $model->getovertimebyid($id,$empid);
        }
        else if($table=='addundum')
        {
            return $model->getaddundumbyid($id,$empid);
        }
        else{
            return $model->getmissedhoursbyid($id,$empid);
        }
    }
}



?>