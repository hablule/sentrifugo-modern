<?php

class Default_Model_Candidateeducation extends Zend_Db_Table_Abstract{
    protected $_name = "main_candeducation";
    protected $_primary = "id";


    public function saveEducationData($data){
        $this->insert($data);
    }
    
    public function getCandidateEducationDetail($sort_order, $sort_field, $pageNo, $perPage,$candidate_id){
        $where = "c.candidate_id = ".$candidate_id;
        $roleData = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('ce'=>$this->_name),
                            array('id'=>'ce.id',
                            'institution'=>'ce.institution',
                            'course'=>'ce.course',
                            'education_level'=>'ce.education_leve',
                            'start_date'=>'ce.start_date',
                            'end_date'=>'ce.end_date',
                            ))
                        ->joinInner(array('c'=>'tbl_countries'), "c.id=ce.country", array('country'=>'c.country_name'))
                        ->joinInner(array('s'=>'tbl_states'),"s.id = ce.state", array('state'=>'s.state_name'))
                        ->joinInner(array('ci'=>'tbl_cities'), "ci.id = ce.city",array('city'=>'ci.city_name'))
                        ->where($where)
                        ->order("$sort_field $sort_order") 
                        ->limitPage($pageNo, $perPage); 
            return $this->fetchAll($roleData)->toArray();
    }
   
}
