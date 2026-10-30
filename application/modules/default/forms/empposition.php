<?php 
 
 
class Default_Form_empposition extends Zend_Form{ 
 
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
$position_id = new Zend_Form_Element_Select('position_id');
$position_id->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
$position_id->setRequired(true);

$jobtitle_id = new Zend_Form_Element_Select('jobtitle_id');
$startdate = new Zend_Form_Element_Text('datefrom');
$startdate->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
$startdate->setRequired(true);

$enddate = new Zend_Form_Element_Text('dateto');
$enddate->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
$enddate->setRequired(true);
$Select4 = new Zend_Form_Element_Select("company_id");
$Select4->setAttrib('class','choseselect');
$Select4->setAttrib('onchange','hideSelect(event)');
     
$Select5 = new Zend_Form_Element_Select("sbunitid");
$Select5->setAttrib('class','choseselect');

$Select5->setAttrib('onchange','hideSelect(event)');
$Select6 = new Zend_Form_Element_Select("division_id");
$Select6->setAttrib('class','choseselect');
$Text1->addValidator('NotEmpty',false,['messages'=>'Division required']);
$Select6->setAttrib('onchange','hideSelect(event)');
$Select7 = new Zend_Form_Element_Select("department_id");
$Select7->setAttrib('class','choseselect');
$Select7->setAttrib('onchange','hideSelect(event)');
$Select8 = new Zend_Form_Element_Select("section_id");
$Select8->setAttrib('class','choseselect');
$Select8->setAttrib('onchange','hideSelect(event)');
$Select9 = new Zend_Form_Element_Select("project_id");
$Select9->setAttrib('class','choseselect');
$Select9->setAttrib('onchange','hideSelect(event)');
$Select11 = new Zend_Form_Element_Select("team_id");
$Select11->setAttrib('class','choseselect');
$Select11->setAttrib('onchange','hideSelect(event)');
$report = new Zend_Form_Element_Select("reportto_id");
$report->setRequired(true);
$report->addValidator('NotEmpty', false, ['messages' => 'Please Select Reporting Manager.']);
$Submit11 = new Zend_Form_Element_Submit("submit");
$this->addElements([$id,$CheckBox0,$Text1,$employee_id,$jobtitle_id,$report,$position_id,$startdate,$enddate,$Select4,$Select5,$Select6,$Select7,$Select8,$Select9,$Select11,$Submit11]);
$this->setElementDecorators(['ViewHelper']); 
} 
 } 
 ?>