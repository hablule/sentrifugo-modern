<?php

class Schedule_Form_Schedulecycleshift extends Zend_Form
{
    public function init()
    {
        $this->setAttrib('id','formid');
        $this->setAttrib('name','schedulecycleshift');
        $this->setMethod('post');
        $id = new Zend_Form_Element_Hidden('id');
        $shiftid = new Zend_Form_Element_Select('shiftid');
        $shiftid->addValidator('NotEmpty',false,array('messages'=>'Shift required'));
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $description = new Zend_Form_Element_Textarea('description');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements(array($id, $shiftid, $description, $isactive, $submit));
    }
}

?>