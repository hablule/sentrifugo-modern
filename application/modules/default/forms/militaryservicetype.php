<?php



class Default_Form_militaryservicetype extends Zend_Form
{


    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','militaryservicetype');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setRequired(true);
        $name->addValidator('NotEmpty',false,array('messages'=>'Name Required'));
        $code = new Zend_Form_Element_Text('code');
        $code->setRequired(true);
        $code->addValidator('NotEmpty',false,array(
            'table'=>'main_militaryservicetype',
            'field'=>'code'
        ));
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements(array($id,$name,$code,$description,$isactive,$submit));
        $this->setElementDecorators(array('ViewHelper'));

    }

}


?>