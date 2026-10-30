<?php 

class Default_Model_Cvadd extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_CV';
    protected $_primary = 'id';
    

   public function addcv($data)
   {
        
        //Zend_Debug::dump($test);
       //die();
      $this->insert($data);
       $this->getallcandidatedata($data['candidate_id']);
       $id=$this->getAdapter()->lastInsertId('main_CV');
       if($id != null)
       {
           return true;
       }
      
       
       return false;
   }

   public function getallcandidatedata($id){
    $db = Zend_Db_Table::getDefaultAdapter();
    $auth = Zend_Auth::getInstance();
    if($auth->hasIdentity())
    {
        $userLogin = $auth->getStorage()->read()->id;
    }
    $query = "SELECT * FROM  main_candtelephone mst
                INNER JOIN main_candpersonalprofile msp ON
                mst.candidate_id = msp.id
                INNER JOIN main_candnationality msw ON
                 msw.candidate_id = msp.id
                INNER JOIN 	main_candeducation esd ON
                esd.candidate_id = msp.id
                INNER JOIN main_CV mscv ON
                mscv.candidate_id = msp.id
                INNER JOIN main_candaddress msad ON
                msad.candidate_id = msp.id
                WHERE msp.id=".$id;
                    
    $res=$db->query($query);
    $candidatedata = $res->fetchAll();
    //Zend_Debug::dump($candidatedata);
    $date = date("Y-m-d H:i:s");
   // Zend_Debug::dump($date);
    $candidatedetaildata = array(
        'requisition_id' => 0,
        'candidate_firstname'=>$candidatedata[0]['first_name'],
        'candidate_lastname'=>$candidatedata[0]['last_name'],
        'candidate_name'=>$candidatedata[0]['first_name']." ".$candidatedata[0]['last_name'],
        'emailid' =>$candidatedata[0]['email'],
        'contact_number'=>$candidatedata[0]['mobile_number'],
        'qualification'=>" ",
        'cand_resume'=>$candidatedata[0]['cv'],
        'cand_status'=>'Not Scheduled',
        'backgroundchk_status'=>'Yet to start',
        'city'=>$candidatedata[0]['city'],
        'country'=>$candidatedata[0]['country'],
        'state'=>$candidatedata[0]['state'],
        'education_summary'=>"Institution: ".$candidatedata[0]['institution']."\n Education level: ".$candidatedata[0]['education_level']."\n Course: ".$candidatedata[0]['course'],
        'createdby'=>$userLogin,
        'createddate'=>$date,
        'isdifferent'=>1,
        'candidate_id'=>$candidatedata[0]['candidate_id'],
        'modifiedby'=>$userLogin,

    );
   Zend_Debug::dump($candidatedetaildata);
    //die();
    $config = array(
        'name'=>'main_candidatedetails',
        'primary'=>'id'
    );
    $ad = new Zend_Db_Table($config);
    $ad->insert($candidatedetaildata); 
    return $candidatedetaildata;
    
        # code...
        
    
    /* $candidatedetaildata = array(
        'candidate_firstname'=>$candidatedata['first_name'],
        'candidate_lastname'=>$candidatedata['last_name'],
        'candidate_name'=>$candidatedata['first_name']." ".$candidatedata['last_name'],
        'emailid' =>$candidatedata['email'],
        'contact_number'=>$candidatedata['mobile_number'],
        'cand_resume'=>$candidatedata['cv'],
        'cand_status'=>'Not Scheduled',
        'backgroundchk_status'=>'Yet to start',
        'city'=>$candidatedata['city'],
        'country'=>$candidatedata['country'],
        'state'=>$candidatedata['state'],
        'createdby'=>1,
        'modifiedby'=>1,

    );
    $config = array(
        'name'=>'main_candidatedetails',
        'primary'=>'id'
    );
    $ad = new Zend_Db_Table($config);
    $ad->insert($candidatedetaildata); */

   }

}



?>