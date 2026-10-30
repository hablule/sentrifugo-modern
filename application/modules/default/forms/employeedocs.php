<?php
class Default_Form_Employeedocs extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'employeedocs');

        $id = new Zend_Form_Element_Hidden('id');
        $doctype = new Zend_Form_Element_Select('doctype');
        $doctype->setLabel('Document Type');

        $this->addElements(array($id, $doctype));
        $this->setElementDecorators(array('ViewHelper')); 
    }
}
?>