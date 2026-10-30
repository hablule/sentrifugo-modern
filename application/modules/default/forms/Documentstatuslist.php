<?php 
 
 
class Default_Form_Documentstatuslist extends Zend_Form{ 
 
public function init(){ 
 
$this->setMethod("post");
$this->setAttrib("id","formid");
$this->setAttrib("name","formindex");
$id = new Zend_Form_Element_Hidden("id");
$iddocumentstatus_id = new Zend_Form_Element_Select("iddocumentstatus_id");
$iddocumentstatus_id->addValidator('NotEmpty',false,array('messages'=>'staus required'));
$iddocumentstatus_id->setRequired(true);
$iddocumentstatus_id->addMultiOption('','Select status ');
		$model = new Default_Model_iddocumentstatus();	
		$catData = $model->GetIdDocumentByvalue();
		foreach ($catData as $cat){
			$iddocumentstatus_id->addMultiOption($cat['iddocumentstatus_id'],utf8_encode($cat['name']));
	    }
		$iddocumentstatus_id->setRegisterInArrayValidator(false);


$Text1 = new Zend_Form_Element_Text("value");
$Text1->addValidator('NotEmpty',false,array('messages'=>'value required'));
$Text1->setRequired(true);

$Text2 = new Zend_Form_Element_Text("description");
$Text2->setLabel('Description');
$CheckBox3 = new Zend_Form_Element_Checkbox("isactive");
$CheckBox3->setValue(1);
$Submit4 = new Zend_Form_Element_Submit("submit");
$Text1->addValidator(new Zend_Validate_Db_NoRecordExists(
    array('table'=>'main_iddocumentstatuslist',
              'field'=>'value',
            'exclude'=>'iddocumentstatuslist_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
       ) )  
);

$Text1->getValidator('Db_NoRecordExists')->setMessage('value already exists.');
$Text1 ->setAttrib('maxlength', '25');
// $Text0 ->setAttrib('maxlength', '100');
$this->addElements(array($id,$iddocumentstatus_id,$Text1,$Text2,$CheckBox3,$Submit4));
$this->setElementDecorators(array('ViewHelper')); 
} 
 } 
 ?>