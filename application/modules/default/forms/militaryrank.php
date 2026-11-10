<?php


class Default_Form_militaryrank extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','militaryrank');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->addValidator('NotEmpty',false,['messages'=>'Name Required']);
        $code = new Zend_Form_Element_Text('code');
        $code->addValidator('NotEmpty',false,['messages'=>'Code Required']);
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(
            [
                'table'=>'main_militaryrank',
                'field'=>'code'
            ]
            ));
        $natocode = new Zend_Form_Element_Text('natocode');
        $natocode->setAttrib('maxlength',5);
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $name->setRequired(true);
        $code->setRequired(true);
        $this->addElements([$id,$name,$code,$description,$natocode,$isactive,$submit]);
        $this->setElementDecorators(['ViewHelper']);
    }
}


?>