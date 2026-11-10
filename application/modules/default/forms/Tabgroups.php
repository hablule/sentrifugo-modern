<?php 
 
 
class Default_Form_Tabgroups extends Zend_Form{ 
 
public function init(){ 
 
$this->setMethod("post");
$this->setAttrib("id","formid");
$this->setAttrib("name","formindex");
$id = new Zend_Form_Element_Hidden("id");
$Text0 = new Zend_Form_Element_Text("name");


$Text1 = new Zend_Form_Element_Text("code");

$istimeActive = Zend_Controller_Front::getInstance()->getRequest()->getParam('istimeactive');

// $Text2 = new Zend_Form_Element_Text("description");
// $Text2->setLabel('Description');
$CheckBox3 = new Zend_Form_Element_Checkbox("isactive");
$Text0->setAttrib("disabled", "disabled");
$Text1->setAttrib("disabled", "disabled");
$CheckBox3->setAttrib("disabled", "disabled");


// $prev_cnt = new Zend_Form_Element_Hidden('prev_cnt');
// $prev_cnt->setRequired(true);

$submit = new Zend_Form_Element_Submit('submit');

$submit->setAttrib('id', 'submitbutton');
$submit->setLabel('Save');

$url = "'tabgroups/saveupdate/format/json'";
$dialogMsg = "''";
$toggleDivId = "''";
$jsFunction = "'redirecttocontroller(\'tabgroups\');'";;
       
$submit->setOptions(['onclick' => "saveDetails($url,$dialogMsg,$toggleDivId,$jsFunction);"
                ]);$this->addElements([$id,$Text0,$Text1,$CheckBox3,$submit,$prev_cnt]);
$this->setElementDecorators(['ViewHelper']); 
} 
 } 
 ?>