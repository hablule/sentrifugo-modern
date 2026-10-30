<?php

class Default_Form_empbenefits extends Zend_Form{
    public function init(){
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','empbenefits');

        $id = new Zend_Form_Element_Hidden('id');
        $perk = new Zend_Form_Element_Select('perk');
		$perk->setLabel('Perk');
        $perk->addValidator('NotEmpty', false, array('messages' => 'Please select Perk.')); 
        $perk->addValidator(new Zend_Validate_Db_NoRecordExists(array(
            'table'=>'main_employeeperk',
            'field'=>'perk_id',
            'exclude'=>'emp_id='.Zend_Controller_Front::getInstance()->getRequest()->getParam('id')
        )));
        $perk->getValidator('Db_NoRecordExists')->setMessage('Perk already Assigned.');
        $perk->setRequired('true');
       
        $isactive= new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active');
        $amount = new Zend_Form_Element_Text('amount');
        $amount->setLabel('Amount');
        $amount->setRequired(true);

        $amount->addValidator('NotEmpty', false, array('messages' => 'Please enter Amount.')); 
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($id,$perk,$amount,$submit,$isactive));
        $this->setElementDecorators(array('ViewHelper')); 

    }
}
?>