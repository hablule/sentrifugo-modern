<?php 
 
class Default_Form_sbuengagment extends Zend_Form{ 
public function init(){ 
$this->setMethod("post");
$this->setAttrib("id","formid");
$this->setAttrib("name","sbuengagment");
$id = new Zend_Form_Element_Hidden("id");
$sbunit_id = new Zend_Form_Element_Select("sbunit_id");
$sbunit_id->setRequired(true);

$sbunit_id->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.'));
$sbunit_id->setAttrib("class", "formDataElement"); 
$sbunit_id->setAttrib('onchange', 'displayParticulardomain_normal(this,"businessdomain_id","businessdomain_id","")');
$businessdomain_id = new Zend_Form_Element_Multiselect("businessdomain_id");
$businessdomain_id->setRequired(true);
$businessdomain_id->setAttrib("class", "formDataElement");         

$businessdomain_id->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.'));
$businessdomain_id->addValidator(new Zend_Validate_Db_NoRecordExists(
    array(
        'table'=>'main_sbuengagment',
        'field'=>'sbunit_id','businessdomain_id'
    )
    ));
$businessdomain_id->getValidator('Db_NoRecordExists')->setMessage('Data already exists.');
$businessdomain_id2 = new Zend_Form_Element_Select("businessdomain_id2");
$businessdomain_id2->setRequired(true);
$businessdomain_id2->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.'));
$businessdomain_id2->addValidator(new Zend_Validate_Db_NoRecordExists(
    array(
        'table'=>'main_sbuengagment',
        'field'=>'sbunit_id','businessdomain_id'

    )
    ));
$businessdomain_id2->getValidator('Db_NoRecordExists')->setMessage('Data already exists.');
$Submit = new Zend_Form_Element_Submit("submit");
$this->addElements(array($id,$sbunit_id,$businessdomain_id,$businessdomain_id2,$Submit));
} 
 } 
 ?>
