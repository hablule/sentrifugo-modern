<?php



class Default_Form_deductivecategory extends Zend_Form
{
    public function init()
    {
        $this->setAttrib('id','formid');
        $this->setAttrib('name','deductive_category');
        $this->setMethod('post');
        $this->setAttrib('action',BASE_URL.'deductivecategory');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name_cat');
        $name->addValidator('NotEmpty',false,array('messages'=>'please fill the category name'));
        $name->addValidator(new Zend_Validate_Db_NoRecordExists(
            array(
                'table'=>'main_loadcategory',
                'field'=>'name'
            )
            ));
        $name->getValidator('Db_NoRecordExists')->setMessage('Name already exists.');
        $name->setRequired(true);
        $name->setAttrib('maxlength',60);
        $description = new Zend_Form_Element_Textarea('description_cat');
        $description->setLabel("Description");
        $submit = new Zend_Form_Element_Submit('submit_cat');
        $submit->setLabel('Save');
        $this->addElements(array($id,$name,$description,$submit));
        $this->setElementDecorators(array('ViewHelper')); 	 

    }
}




?>