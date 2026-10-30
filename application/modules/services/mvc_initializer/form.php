<?php
class ClassName extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'ControllerName');


        $id = new Zend_Form_Element_Hidden('id');
        
		 $this->addElements(array($id));
         $this->setElementDecorators(array('ViewHelper')); 
    }
}
?>