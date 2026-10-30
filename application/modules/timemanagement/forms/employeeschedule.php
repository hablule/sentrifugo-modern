<?php

class Timemanagement_Form_Employeeschedule extends Zend_Form{


    public function init()
    {
        $this->setAttrib('id','formid');
        $this->setAttrib('name','empschedule');
        $this->setAttrib('action',BASE_URL.'schedule');
        $this->setMethod('post');

        $id = new Zend_Form_Element_Hidden('id');
        $workdays = new Zend_Form_Element_Multiselect('workdays');
        
        
        $from = new Zend_Form_Element_Text('from');
        $to = new Zend_Form_Element_Text('to');
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttrib('id','submitBtn');
        $submit->setLabel('save');
        $this->addElements([$id,$workdays,$from,$to,$submit]);
        $this->setElementDecorators(['ViewHelper']); 
    }
}



?>