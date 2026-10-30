<?php
class Default_Form_testr extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'testr');


        $id = new Zend_Form_Element_Hidden('id');
        
		 $this->addElements([$id]);
         $this->setElementDecorators(['ViewHelper']); 
    }
}
?>