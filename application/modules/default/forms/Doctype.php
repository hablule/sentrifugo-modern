<?php
class Default_Form_Doctype extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'doctype');


        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->addValidator('NotEmpty',false,['messages'=>'Type required']);
        $name->setRequired(true);
        $name->setLabel("Document Type");
        $name->addValidator(new Zend_Validate_Db_NoRecordExists(
            [
                'table'=>'main_doctype',
                'field'=>'name'

            ]
            ));
        $name->getValidator("Db_NoRecordExists")->setMessage('Name already exists');
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel("Description");
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel("Active");
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel("Save");
		 $this->addElements([$id, $name,$isactive, $description,$submit]);
         $this->setElementDecorators(['ViewHelper']); 
    }
}
?>