<?php



class Default_Form_perkcategory extends Zend_Form
{
    public function init()
    {
        $this->setAttrib('id','formid');
        $this->setAttrib('name','perk_category');
        $this->setMethod('post');
        $this->setAttrib('action',BASE_URL.'perkcategory');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name_cat');
        $name->addValidator('NotEmpty',false,array('messages'=>'please fill category name'));
        $name->addValidator(new Zend_Validate_Db_NoRecordExists(
            array(
                'table'=>'main_perkcategory',
                'field'=>'name'
            )
            ));
        $name->getValidator("Db_NoRecordExists")->setMessage('Name already exists');
        $name->setAttrib('maxlength',60);
        $name->setRequired(true);
        $description = new Zend_Form_Element_Textarea('description_cat');
        $description->setLabel("Description");
        $submit = new Zend_Form_Element_Submit('submit_cat');
        $submit->setLabel('Save');
        $this->addElements(array($id,$name,$description,$submit));
        $this->setElementDecorators(array('ViewHelper'));

    }
}




?>