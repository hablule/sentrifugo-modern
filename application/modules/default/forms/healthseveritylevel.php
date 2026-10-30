<?php


class Default_Form_healthseveritylevel extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','healthseveritylevel');
        $id = new Zend_Form_Element_Hidden('id');
        $healthseverity_id = new Zend_Form_Element_Select('healthseverity_id');
        $healthseverity_id->setRequired(true);
        $healthseverity_id->addValidator('NotEmpty',false,array('messages'=>'Severity Required'));
        $value = new Zend_Form_Element_Text('value');
        $value->setRequired(true);
        $value->addValidator('NotEmpty',false,array('messages'=>'Value Required'));
        $value->addValidator(new Zend_Validate_Db_NoRecordExists(array(
            'table'=>'main_healthseveritylevel',
            'field'=>'value'
        )));
        $value->getValidator('Db_NoRecordExists')->setMessage('Value already exists.');

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements(array($id,$healthseverity_id,$value,$description,$isactive,$submit));
        $this->setElementDecorators(array('ViewHelper'));
    }
}

?>