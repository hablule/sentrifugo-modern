<?php 
 
 
class Default_Form_Disability extends Zend_Form{ 
 
public function init(){ 
 
$this->setMethod("post");
$this->setAttrib("id","formid");
$this->setAttrib("name","formindex");
$id = new Zend_Form_Element_Hidden("id");
$Text0 = new Zend_Form_Element_Text("name");
$Text0->addValidator('NotEmpty',false,array('messages'=>'Name required'));
$Text0->setRequired(true);
$Text1 = new Zend_Form_Element_Text("code");
$Text1->addValidator('NotEmpty',false,array('messages'=>'Code required'));
$Text1->setRequired(true);

$disabilitycategory_id = new Zend_Form_Element_Select("disabilitycategory_id");
$disabilitycategory_id->addValidator('NotEmpty',false,array('messages'=>'Field is required'));
$disabilitycategory_id->setRequired(true);
$modelc = new Default_Model_disabilitycategory();
$addc = $modelc->getallcategory();
$disabilitycategory_id->addMultiOption(' ', 'Select...');
if (empty($addc)) {
    $msgarray['disabilitycategory_id'] = 'Disability Category  is not configured yet.';
} else {
    foreach ($addc as $addcs) {
        $disabilitycategory_id->addMultiOptions(array($addcs['disabilitycategory_id'] => $addcs['name']));
    }
}
$disabilitycategory_id->setRegisterInArrayValidator(false);

$disabilitytype_id = new Zend_Form_Element_Select("disabilitytype_id");
$disabilitytype_id->addValidator('NotEmpty',false,array('messages'=>'Field is required'));
$disabilitytype_id->setRequired(true);
$modelb = new Default_Model_Disabilitytype();
$add = $modelb->getalltype();
$disabilitytype_id->addMultiOption(' ', 'Select...');
if (empty($add)) {
    $msgarray['disabilitytype_id'] = 'Disability Type  is not configured yet.';
} else {
    foreach ($add as $adds) {
        $disabilitytype_id->addMultiOptions(array($adds['disabilitytype_id'] => $adds['name']));
    }
}
$disabilitytype_id->setRegisterInArrayValidator(false);
$Text2 = new Zend_Form_Element_Text("description");
$Text2->setLabel('Description');
$CheckBox3 = new Zend_Form_Element_Checkbox("isactive");
$CheckBox3->setValue(1);
$Submit4 = new Zend_Form_Element_Submit("submit");
$Text1->addValidator(new Zend_Validate_Db_NoRecordExists(
    array('table'=>'main_disability',
              'field'=>'code',
            'exclude'=>'disability_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
       ) )  
);

$Text1->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
$Text1 ->setAttrib('maxlength', '25');
$Text0 ->setAttrib('maxlength', '60');
$this->addElements(array($id,$Text0,$Text1,$Text2,$CheckBox3,$disabilitytype_id,$disabilitycategory_id,$Submit4));
$this->setElementDecorators(array('ViewHelper')); 
} 
 } 
 ?>