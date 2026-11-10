<?php 


class Default_Form_Resume extends Zend_Form{
    public function init()
    {

        $this->setMethod('post');
        $this->setAttrib('id','formid');
        //$this->setAttrib('action',BASE_URL.'selfcandidateadd/add');
        //$this->setAttrib('action',BASE_URL.'addpersonaldetail/add');
        $this->setAttrib('name','resume');
        $id = new Zend_Form_Element_Hidden('id');
        
        // $nationality = new Zend_Form_Element_Select("country");
        // $nationality->setRegisterInArrayValidator(false);
        // $nationality->setLabel("Country");		
        // $nationality->setAttrib("class", "formDataElement"); 
        // // $nationality->setAttrib('onchange', 'displayParticularState_normal(this,"","state","city")');
        // $nationality->setAttrib('title', 'Country');
        // $candidatename = new Zend_Form_Element_Text('candidatename');
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Next');
        $this->addElements([$id,$submit]);
        
    }
}





?>