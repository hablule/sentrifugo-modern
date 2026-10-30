<?php 
 
 
class Default_Form_Documentpurpose extends Zend_Form{ 
 
public function init(){ 
 
$this->setMethod("post");
$this->setAttrib("id","formid");
$this->setAttrib("name","formindex");
$id = new Zend_Form_Element_Hidden("id");
$Text0 = new Zend_Form_Element_Text("name");
$Text0->addValidator('NotEmpty',false,['messages'=>'Name required']);
$Text0->setRequired(true);
$Text1 = new Zend_Form_Element_Text("code");
$Text1->addValidator('NotEmpty',false,['messages'=>'Code required']);
$Text1->setRequired(true);

$Text2 = new Zend_Form_Element_Text("description");
$Text2->setLabel('Description');
$CheckBox3 = new Zend_Form_Element_Checkbox("isactive");
$Submit4 = new Zend_Form_Element_Submit("submit");
$Text1->addValidator(new Zend_Validate_Db_NoRecordExists(
    ['table'=>'main_iddocumentpurpose',
              'field'=>'code',
            'exclude'=>'iddocumentpurpose_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
       ] )  
);

$Text1->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
$Text1 ->setAttrib('maxlength', '25');
$Text0 ->setAttrib('maxlength', '60');
$this->addElements([$id,$Text0,$Text1,$Text2,$CheckBox3,$Submit4]);
$this->setElementDecorators(['ViewHelper']); 
} 
 } 
 ?>