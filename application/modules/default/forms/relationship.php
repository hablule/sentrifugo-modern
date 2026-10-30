<?php


class Default_Form_relationship extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','relationship');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setRequired(true);
        $name->addValidator('NotEmpty',false,['messages'=>'Name Required']);
        $code = new Zend_Form_Element_Text('code');
        $code->setRequired(true);
        $code->addValidator('NotEmpty',false,['messages'=>'Code Required']);
        $relation_type = new Zend_Form_Element_Select('relationtype');
        $relation_type->addMultiOptions([''=>'Select relation type']);
        $relation_type->addMultiOptions(RELATION_TYPE_ENUM);
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements([$id,$name,$code,$description,$isactive,$relation_type,$submit]);
        $this->setElementDecorators(['ViewHelper']);  
    }
}


?>