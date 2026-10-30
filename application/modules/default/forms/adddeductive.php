<?php

class Default_Form_adddeductive extends Zend_Form{
    public function init(){
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'adddeductive');
        $this->setAttrib('action',BASE_URL.'adddeductive');

        $id = new Zend_Form_Element_Hidden('id');
        $submit = new Zend_Form_Element_Submit('submit');

        $submit->setLabel('Assign Deductive');
        $this->addElements([$id,$submit]);
        $this->setElementDecorators(['ViewHelper']);  
    }
}
?>