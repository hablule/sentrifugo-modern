<?php 
 
 
class Default_Form_Skill extends Zend_Form{ 
 
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
$CheckBox4 = new Zend_Form_Element_Checkbox("issummary");
$CheckBox4->setAttrib('onclick','toggleSelect()');
$Submit4 = new Zend_Form_Element_Submit("submit");
$Text1->addValidator(new Zend_Validate_Db_NoRecordExists(
    ['table'=>'main_skill',
              'field'=>'code',
            'exclude'=>'skill_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
       ] )  
);

$Text1->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
$Text1 ->setAttrib('maxlength', '25');
$Text0 ->setAttrib('maxlength', '80');
$skillcategory_id = new Zend_Form_Element_Select("skillcategory_id");
$skillcategory_id->addValidator('NotEmpty',false,['messages'=>'please select the field']);
$skillcategory_id->setRequired(true);
$measurement_id = new Zend_Form_Element_Select("measurement_id");
$measurement_id->addValidator('NotEmpty',false,['messages'=>'please select the field']);
$measurement_id->setRequired(true);
$parentskill_id = new Zend_Form_Element_Select("parentskill_id");
$this->addElements([$id,$Text1,$Text0,$Text2,$CheckBox4,$CheckBox3,$skillcategory_id,$measurement_id,$parentskill_id,$Submit4]);
$this->setElementDecorators(['ViewHelper']); 
} 
 } 
 ?>