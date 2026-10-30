<?php

class Default_Form_empdeductive extends Zend_Form{
    public function init(){
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','empdeductive');

        $id = new Zend_Form_Element_Hidden('id');
        $dedeductive = new Zend_Form_Element_Select('deductive');
		$dedeductive->setLabel('Load');
        $dedeductive->addValidator('NotEmpty', false, ['messages' => 'Please select Load.']); 
        $dedeductive->addValidator(new Zend_Validate_Db_NoRecordExists([
            'table'=>'main_employeeload',
            'field'=>'load_id',
            'exclude'=>'emp_id='.Zend_Controller_Front::getInstance()->getRequest()->getParam('id')
        ]));
        $dedeductive->getValidator('Db_NoRecordExists')->setMessage('Load already Assigned.');
        $dedeductive->setRequired('true');
       
        $isactive= new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active');
        $amount = new Zend_Form_Element_Text('amount');
        $amount->setLabel('Amount');
        $amount->setRequired(true);
        
        $amount->addValidator('NotEmpty', false, ['messages' => 'Please enter Amount.']); 
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements([$id,$dedeductive,$amount,$submit,$isactive]);
        $this->setElementDecorators(['ViewHelper']); 

    }
}
?>