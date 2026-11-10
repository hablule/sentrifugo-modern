<?php 
 
 
class Default_Form_Documentprovider extends Zend_Form{ 
 
public function init(){ 
 
$this->setMethod("post");
$this->setAttrib("id","formid");
$this->setAttrib("name","formindex");
$id = new Zend_Form_Element_Hidden("id");
$selected_option = Zend_Controller_Front::getInstance()->getRequest()->getParam('selected_option',null);

$country = new Zend_Form_Element_Select("country");
$country->addValidator('NotEmpty',false,['messages'=>'Select country']);
$country->setRegisterInArrayValidator(false);
$country->setLabel("Country");		
$country->setAttrib("class", "formDataElement"); 
$country->setAttrib('onchange', 'displayParticularState_normal(this,"","state","city")');
$country->setAttrib('title', 'Country');

if($selected_option == 'fill-up-form'){
    // $country->setRequired(true);
}
// $country->setRequired(true);
$country->addValidator('NotEmpty', false, ['messages' => 'Please select country.']);
$state = new Zend_Form_Element_Select("state");
$state->setRegisterInArrayValidator(false);

if($selected_option == 'fill-up-form'){
    // $state->setRequired(true);
}

$state->addValidator('NotEmpty', false, ['messages' => 'Please select state.']); 
$state->addMultiOptions([''=>'Select State']);
$state->setLabel("State");		
$state->setAttrib("class", "formDataElement"); 
$state->setAttrib('onchange', 'displayParticularCity_normal(this,"","city","")');
$state->setAttrib('title', 'State'); 		
// $state->setRequired(true);

$city = new Zend_Form_Element_Select("city");
$city->setRegisterInArrayValidator(false);
$city->addMultiOptions([''=>'Select City']);
$city->setLabel("City");		
$city->setAttrib("class", "formDataElement");         
$city->setAttrib('title', 'City');

if($selected_option == 'fill-up-form'){
    // $city->setRequired(true);
}

$city->addValidator('NotEmpty', false, ['messages' => 'Please select city.']);
$city->setRequired(true);
$Text1 = new Zend_Form_Element_Text("code");
$Text1->addValidator('NotEmpty',false,['messages'=>'value required']);
$Text1->setRequired(true);
$Text0= new Zend_Form_Element_Text("name");
$Text0->addValidator('NotEmpty',false,['messages'=>'name required']);
$Text0->setRequired(true);

$Text2 = new Zend_Form_Element_Text("description");
$Text2->setLabel('Description');
$CheckBox3 = new Zend_Form_Element_Checkbox("isactive");
$CheckBox3->setValue(1);
$Submit4 = new Zend_Form_Element_Submit("submit");
$Text1->addValidator(new Zend_Validate_Db_NoRecordExists(
    ['table'=>'main_iddocumentprovider',
              'field'=>'code',
            'exclude'=>'iddocumentprovider_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
       ] )  
);

$Text1->getValidator('Db_NoRecordExists')->setMessage('code already exists.');
$Text1 ->setAttrib('maxlength', '25');
$Text0 ->setAttrib('maxlength', '60');
$this->addElements([$id,$country,$state,$city,$Text0,$Text1,$Text2,$CheckBox3,$Submit4]);
$this->setElementDecorators(['ViewHelper']); 
} 
 } 
 ?>