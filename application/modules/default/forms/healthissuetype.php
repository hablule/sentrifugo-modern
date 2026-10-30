<?php


class Default_Form_healthissuetype extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','healthissuetype');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->addValidator('NotEmpty',false,array('messages'=>'Name Required'));
        $name->setRequired(true);
        $code = new Zend_Form_Element_Text('code');
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(array(
            'table'=>'main_healthissuetype',
            'field'=>'code'
        )));
        $code->setRequired(true);
        $code->addValidator('NotEmpty',false,array('messages'=>'Code Required'));
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements(array($id,$name,$code,$description,$isactive,$submit));
        $this->setElementDecorators(array('ViewHelper'));
        
    }
}


?>