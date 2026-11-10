<?php 
 
 
class Default_Form_emprole extends Zend_Form{ 
 
public function init(){ 
 
$this->setMethod("post");
$this->setAttrib("id","formid");
$this->setAttrib("name","formindex");
$id = new Zend_Form_Element_Hidden("id");
$CheckBox0 = new Zend_Form_Element_Checkbox("isactive");
$Text1 = new Zend_Form_Element_Text("description");
$Text1->setLabel('Description');      

$employee_id = new Zend_Form_Element_Select("employee_id ");
$employee_id->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
$employee_id->setRequired(true);

$role_id = new Zend_Form_Element_Select('role_id');
$role_id->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
$role_id->setRequired(true);
// $position_id->addValidator(new Zend_Validate_Db_NoRecordExists(
//     array(
//         'table'=>'main_empposition',
//         'field'=>'id'
//     )
//     ));
// $position_id->getValidator('Db_NoRecordExists')->setMessage('Position already exists.');
$startdate = new Zend_Form_Element_Text('datefrom');
$startdate->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
$startdate->setRequired(true);
$enddate = new Zend_Form_Element_Text('dateto');
$enddate->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
$enddate->setRequired(true);


$Submit11 = new Zend_Form_Element_Submit("submit");
$this->addElements([$id,$CheckBox0,$Text1,$employee_id,$role_id,$startdate,$enddate,$Submit11]);
$this->setElementDecorators(['ViewHelper']); 
} 
 } 
 ?>