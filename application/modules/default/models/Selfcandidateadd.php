<?php 

class Default_Model_Selfcandidateadd extends Zend_Db_Table_Abstract
{
  private $profile;
  private $address;
  private $nationality;
  private $nativelang;
  private $fluentlang;
  private $phonenumber;
  private $requistion;
//   public function __construct()
//   {
//     $config = array(
//         'name'      => 'main_selfcandidatepersonalprofile',
//         'primary'   => 'id'
//     );
//     $this->profile = new Zend_Db_Table($config);
//     $config = array(
//         'name'      => 'main_selfcandidateaddress',
//         'primary'   => 'id'
//     );
//     $this->address = new Zend_Db_Table($config);

//     $config = array(
//         'name' => 'main_selfcandidatenationality',
//         'primary' => 'id'
//     );
//     $this->nationality = new Zend_Db_Table($config);
//     $config = array(
//         'name' => 'main_selfcandidatenativelang',
//         'primary' => 'id'
//     );
//     $this->nativelang = new Zend_Db_Table($config);
//     $config = array(
//         'name' => 'main_selfcandidateotherlang',
//         'primary' => 'id'
//     );
//     $this->fluentlang = new Zend_Db_Table($config);

//     $config = array(
//         'name' => 'main_selfcandidatetelephone',
//         'primary' => 'id'
//     );
//     $this->phonenumber = new Zend_Db_Table($config);
   
//   }


  public function saveData($data, $table){
    
    $config = [
        'name' => $table,
        'primary' => 'id'
    ];
    $this->table = new Zend_Db_Table($config);
    $this->table->insert($data);
    $id = $this->table->getAdapter()->lastInsertId($table); 
    return $id;
  }
  public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall){

    $searchQuery = '';
    $searchArray = [];
    if($searchData != '' && $searchData!='undefined'){
      $searchValues = json_decode($searchData);

      if(count($searchValues) >0){
        foreach($searchValues as $key => $val)
        {
          if($key == 'onboard_date' || $key == 'r.createdon'){
            $searchQuery .= " date(".$key.") = '".  sapp_Global::change_date($val,'database')."' AND ";
          }
          else{
            $searchQuery .= " ".$key." like '%".$val."%' AND ";
          }
          $searchArray[$key] = $val;
        }
        $searchQuery = rtrim($searchQuery," AND");
      }
    }

    $tableFields = [

      'action'=>'Action',
      'full_name'=>'Candidate Name',
      'response'=> 'Previous Job Responsablity',
      'engag'=>'Previous Work Area',
      'edu_level'=>'Education',
      'course'=>'Graduated With',
      'gpa'=>"GPA"
      
    ];
    $objName = 'listcandidates';
  
    $tablecontent = $this->getProfileData($sort,$by,$pageNo,$perPage,$searchQuery);
   
    $dataTmp = [
      'sort' => $sort,
      'by' => $by,
      'pageNo' => $pageNo,
      'perPage' => $perPage,				
      'tablecontent' => $tablecontent,
      'objectname' => $objName,
      'extra' => [],
      'tableheader' => $tableFields,
      'jsGridFnName' => 'getAjaxgridData',
      'jsFillFnName' => '',
      'add' =>'add',
			'menuName' => 'View candidate',
      'searchArray' => $searchArray,
      'call'=>$call,
      'dashboardcall'=>$dashboardcall
    ];
    
    return $dataTmp;
  }

  public function getProfileData($sort, $by, $pageNo, $perPage,$searchQuery)
  {

    $config = [
      'name'=>'main_candpersonalprofile',
      'primary'=>'id'
    ];

   $table = new Zend_Db_Table($config);
   $tableData=$table->select()
                    ->setIntegrityCheck(false)
                    ->from(['p'=>'main_candpersonalprofile'],[
                      'full_name' => 'CONCAT(p.first_name," ",p.middle_name," ",p.last_name)',
                      'id'=>'p.id'
                      ])
                    ->joinInner(['w'=>'main_candworkexp'],'p.id = w.candidate_id',['response'=>'w.responsibilities'])
                    ->joinInner(['c'=>'main_career'],'c.id = w.engagment_area',['engag'=>'c.area_of_engagment'])
                    ->joinInner(['e'=>'main_candeducation'],'e.candidate_id = p.id',['gpa'=>'e.cgpa','course'=>'CONCAT(course_programe,"\n")'])
                    ->joinInner(['el'=>'main_educationlevelcode'],'el.id = e.education_level',['edu_level'=>'el.description'])
                    ->order("$by $sort")
                    ->limitPage($pageNo,$perPage);
   

  
   return $tableData;
  }
  public function getCandidateList($ids)
  {
    $ids = implode(",",$ids);
    $config = [
      'name'=>'main_candpersonalprofile',
      'primary'=>'id'
    ];
    $table = new Zend_Db_Table($config);
    $candidates = $table->select()
                        ->setIntegrityCheck(false)
                        ->from(['p'=>'main_candidatedetails'])
                        ->where("candidate_id IN ($ids)");
    return $table->_fetch($candidates);
    //return $this->fetchAll();

  }




  public function getRequistion()
  {
    $config = [
      'name'=>'main_requisition',
      'primary'=>'id'
    ];
    $table = new Zend_Db_Table($config);
    $requistion = $table->select()
                        ->setIntegrityCheck(false)
                        ->from(['r'=>'main_requisition'],['id'=>'r.id','req_code'=>'r.requisition_code'])
                        ->where('req_status = "Approved"');
                        
                        
  return $table->_fetch($requistion);
    //return $table->_fetch($requistion);
  }
    // public function saveprofiledata($data)
    // {
    //    $this->profile->insert($data);
    //    $id = $this->profile->getAdapter()->lastInsertId('main_selfcandidatepersonalprofile');
       
    //    return $id;
    // }
    // public function saveaddressdata($data)
    // {
    //     $this->address->insert($data);
    //     $id=$this->address->getAdapter()->lastInsertId('main_selfcandidateaddress');
    //     return $id;
    // }
    // public function savenationalitydata($data)
    // { 
    //     $this->nationality->insert($data);
    //     $id = $this->nationality->getAdapter()->lastInsertId('main_selfcandidatenationality');
    //     return $id;
    // }
    // public function savenativelangdata($data)
    // {
    //     $this->nativelang->insert($data);
    //     $id = $this->nativelang->getAdapter()->lastInsertId('main_selfcandidatenativelang');
    //     return $id;
    // }
    // public function savefluentlangdata($data)
    // {
    //     $this->fluentlang->insert($data);
    //     $id = $this->fluentlang->getAdapter()->lastInsertId('main_selfcandidatenativelang');
    //     return $id;
    // }
    // public function savephonedata($data)
    // {
    //     $this->phonenumber->insert($data);
    //     $id = $this->phonenumber->getAdapter()->lastInsertId('main_selfcandidatetelephone');
    //     return $id;
    // }
    public function getallrequistions()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = 'SELECT ns.id , j.name
                  FROM main_requisition ns
                    JOIN main_title j 
                    ON ns.jobtitle = j.id
                  WHERE ns.isactive = 1 AND ns.req_status = "Approved"';
        $res=$db->query($query);
        return $res->fetchAll();
    }
}
