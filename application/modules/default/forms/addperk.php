<?php

class Default_Form_addperk extends Zend_Form{
    public function init(){
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'frm_addperk');
        $this->setAttrib('action',BASE_URL.'addperk');

        $id = new Zend_Form_Element_Hidden('id');
        $submit = new Zend_Form_Element_Submit('submit');

        $submit->setLabel('Assign Perk');
        $this->addElements([$id,$submit]);
        $this->setElementDecorators(['ViewHelper']);  
    }
}
?>