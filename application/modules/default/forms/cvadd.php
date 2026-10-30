<?php


class Default_Form_cvadd extends Zend_Form
{

    public function init()
    {
        
        $this->setMethod('post');
        $this->setAttrib('action',BASE_URL.'addcv/add');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','addcv');
        $id = new Zend_Form_Element_Hidden('id');
        
        //$coverletter = new Zend_Form_Element_Textarea('coverletter');
       
        
        //$sourcetracking = new Zend_Form_Element_Select('sourcetracking');
        //$employeenoticeperiod = new Zend_Form_Element_Select('employeenoticeperiod');
        $prevbtn = new Zend_Form_Element_Button('prevbtn');
        $prevbtn->setLabel('Prev');
        $prevbtn->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() .'/education/index' ."'");
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Next');
        $this->addElements(array($id,$prevbtn,$submit));
        $this->setElementDecorators(array('ViewHelper')); 

    }

}


?>