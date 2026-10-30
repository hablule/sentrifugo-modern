<?php


class Default_Form_militarydischargetype extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','militarydischargetype');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->addValidator('NotEmpty',false,array('messages'=>'Name Required'));
        $code = new Zend_Form_Element_Text('code');
        $code->addValidator('NotEmpty',false,array('messages'=>'Code Required'));
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(array(
            'table'=>'main_militarydischargetype',
            'field'=>'code'
        )));
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");
        $isactve = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements(array($id,$name,$code,$description,$isactve,$submit));
        $this->setElementDecorators(array('ViewHelper'));

    }
}

?>