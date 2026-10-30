<?php 
class Default_Model_Fingerprint extends Zend_Db_Table_Abstract{
    protected $_name = 'main_fingerprint_template';
    protected $_primary = 'id';

    public function getFingerPrintTemplate($id){
        
        $where = "id = ".$id;
        $fingerPrint = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(['mft'=>'main_fingerprint_template'])
                    ->where($where);
        $res = $this->fetchRow($fingerPrint)->toArray();
       return $res;
    }

    public function getAllFingerPrintTemplate($empId)
    {
        $where = "empid = ".$empId ;
        $fingerPrintTemplates = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(['mft'=>'main_fingerprint_template'])
                    ->where($where);
        
        $res = $this->fetchAll($fingerPrintTemplates)->toArray();
        return $res;
    }

    public function getFingerPrintTemplateData($where){
        $fingerPrintTemplates = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(['mft'=>'main_fingerprint_template'])
                    ->where($where);
        
        $res = $this->fetchAll($fingerPrintTemplates)->toArray();
        return $res;
    }

    public function getFingerPrintTemplateInUse($empId){
        $where = "empid = ".$empId." and inuse = 1";
        $fingerPrintTemplates = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(['mft'=>'main_fingerprint_template'])
                    ->where($where);
        
        $res = $this->fetchAll($fingerPrintTemplates)->toArray();
        return $res;
    }

    public function saveAndUpdateFingerPrintTemplate($data, $where){
        
        if($where != ''){
            $this->update($data, $where);
        }else{
            $this->insert($data);
            $id = $this->getAdapter()->lastInsertId();
            return $id;
        }
    }



}
?>