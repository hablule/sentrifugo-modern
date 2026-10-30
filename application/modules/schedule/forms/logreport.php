<?php
class Schedudle_Form_Logreport extends Zend_Form
{
    public function init()
    {
     
        $this->setAttrib('action', BASE_URL . 'logreport');
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'logreport');

        $id = new Zend_Form_Element_Hidden('id');
        $uploadFile = new Zend_Form_Element_File('uploadFile');
        $submit = new Zend_Form_Element_Submit('submit');

        $this->addElements(array($id,$uploadFile,$submit));
         $this->setElementDecorators(array('ViewHelper'));
    }
}
?>
