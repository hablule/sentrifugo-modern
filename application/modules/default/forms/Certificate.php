<?php 
 
 
class Default_Form_Certificate extends Zend_Form{ 
 
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

$certificatecategory_id = new Zend_Form_Element_Select("certificatecategory_id");
$certificatecategory_id->addValidator('NotEmpty',false,['messages'=>'Field is required']);
$certificatecategory_id->setRequired(true);

$modelc = new Default_Model_Certificatecategory();
$addc = $modelc->getallcategory();
$certificatecategory_id->addMultiOption(' ', 'Select...');
if (empty($addc)) {
    $msgarray['certificatecategory_id'] = 'Certification Category  is not configured yet.';
} else {
    foreach ($addc as $addcs) {
        $certificatecategory_id->addMultiOptions([$addcs['certificatecategory_id'] => $addcs['name']]);
    }
}
$certificatecategory_id->setRegisterInArrayValidator(false);


$certprovider_id = new Zend_Form_Element_Select("certprovider_id");
$certprovider_id->addMultiOption(' ', 'Select...');

$modelb = new Default_Model_Certprovider();
$add = $modelb->getallcategory();
if (empty($add)) {
  $msgarray['certprovider_id'] = 'Certification Provider  is not configured yet.';
} else {
  foreach ($add as $adds) {
      $certprovider_id->addMultiOptions([$adds['certprovider_id'] => $adds['name']]);
  }
}

$certprovider_id->setRegisterInArrayValidator(false);
$Text2 = new Zend_Form_Element_Text("description");
$Text2->setLabel('Description');
$CheckBox3 = new Zend_Form_Element_Checkbox("isactive");
$CheckBox3->setValue(1);
$Submit4 = new Zend_Form_Element_Submit("submit");
$Text1->addValidator(new Zend_Validate_Db_NoRecordExists(
    ['table'=>'main_certificate',
              'field'=>'code',
            'exclude'=>'certificate_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
       ] )  
);

$Text1->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
$Text1 ->setAttrib('maxlength', '25');
$Text0 ->setAttrib('maxlength', '60');
$this->addElements([$id,$Text0,$Text1,$Text2,$CheckBox3,$certprovider_id,$certificatecategory_id,$Submit4]);
$this->setElementDecorators(['ViewHelper']); 
} 
 } 
 ?>