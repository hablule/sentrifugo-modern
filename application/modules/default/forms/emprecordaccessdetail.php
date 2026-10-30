<?php
class Default_Form_emprecordaccessdetail extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'user');


        $id = new Zend_Form_Element_Hidden('id');
        $emprecordaccess_id = new Zend_Form_Element_Multiselect('emprecordaccess_id');
        $emprecordaccess_id->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $emprecordaccess_id->setRequired(true);
        $emprecordaccess_id2 = new Zend_Form_Element_Select('emprecordaccess_id2');
        $emprecordaccess_id2->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $emprecordaccess_id2->setRequired(true);
        $emp_id = new Zend_Form_Element_Select('emp_id');
        $emp_id->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $emp_id->setRequired(true);

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel("Description");

        $isactive->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
                   
        $emprecordaccess_id->addValidator(new Zend_Validate_Db_NoRecordExists(
            ['table'=>'main_emprecordacessdetail',
                      'field'=> 'emp_id',
                    'exclude'=>'emprecordaccess_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
               ] )  
    );
  

$emprecordaccess_id->getValidator('Db_NoRecordExists')->setMessage('Record Access already exists.');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements([$id,$emprecordaccess_id,$emprecordaccess_id2,$emp_id,$isactive,$description,
        $emp_id,$submit]);
        $this->setElementDecorators(['ViewHelper']); 
    }
}
?>