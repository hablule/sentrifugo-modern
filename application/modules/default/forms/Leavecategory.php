<?php
class Default_Form_Leavecategory extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'leavecategory');


        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setAttrib('maxLength', 50);
        $name->setRequired(true);
        $name->addValidator('NotEmpty', false, ['messages' => 'Please enter leave type.']);
		$name->addValidators([
						 [
							 'validator'   => 'Regex',
							 'breakChainOnFailure' => true,
							 'options'     => [ 
							 
							 'pattern'=> '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9\-\s]*)$/',
								 'messages' => [
										 'regexNotMatch'=>'Please enter valid leave type.'
								 ]
							 ]
						 ]
					 ]); 
				
		$description = new Zend_Form_Element_Textarea('description');
		$description->setLabel("Description");
        $description->setAttrib('rows', 10);
        $description->setAttrib('cols', 50);
		$description ->setAttrib('maxlength', '200');

		$isactive = new Zend_Form_Element_Checkbox('isactive');
		$isactive->setLabel('Active');
        $submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Save');
		 $this->addElements([$id,$name,$description,$isactive,$submit]);
         $this->setElementDecorators(['ViewHelper']); 
    }
}
?>