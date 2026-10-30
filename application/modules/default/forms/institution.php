<?php

class Default_form_institution extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','institution');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setRequired(true);
        $name->addValidator('NotEmpty',false,array('messages'=>'Name Required'));
        $code = new Zend_Form_Element_Text('code');
        $code->setRequired(true);
        $code->addValidator('NotEmpty',false,array('messages'=>'Code Required'));
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(array(
            'table'=>'main_healthseverity',
            'field'=>'code'
        )));
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(array(
            'table'=>'main_institution',
            'field'=>'code'
        )));
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements(array($id,$name,$code,$description,$isactive,$submit));
        $this->setElementDecorators(array('ViewHelper'));
    }
}

?>