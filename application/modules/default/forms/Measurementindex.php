<?php 
 
 
class Default_Form_Measurementindex extends Zend_Form{ 
 
public function init(){ 
 
$this->setMethod("post");
$this->setAttrib("id","formid");
$this->setAttrib("name","formindex");
$id = new Zend_Form_Element_Hidden("id");
$Text0 = new Zend_Form_Element_Text("value");
$Text0->addValidator('NotEmpty',false,array('messages'=>'Value required'));
$Text0->setRequired(true);

$Text2 = new Zend_Form_Element_Text("description");
$Text2->setLabel('Description');
$CheckBox3 = new Zend_Form_Element_Checkbox("isactive");
$Submit4 = new Zend_Form_Element_Submit("submit");
$Text0 ->setAttrib('maxlength', '60');
$measurement_id = new Zend_Form_Element_Select('measurement_id');

$this->addElements(array($id,$Text0,$Text2,$measurement_id,$CheckBox3,$Submit4));
$this->setElementDecorators(array('ViewHelper')); 
} 
 } 
 ?>