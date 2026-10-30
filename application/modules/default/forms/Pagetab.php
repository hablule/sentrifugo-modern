<?php 
 
 
class Default_Form_Pagetab extends Zend_Form{ 
 
public function init(){ 
 
$this->setMethod("post");
$this->setAttrib("id","formid");
$this->setAttrib("name","formindex");
$id = new Zend_Form_Element_Hidden("id");
$Text0 = new Zend_Form_Element_Text("tabname");
$Text0->addValidator('NotEmpty',false,array('messages'=>'Name required'));
$Text0->setRequired(true);
$Text1 = new Zend_Form_Element_Text("tabcode");
$Text1->addValidator('NotEmpty',false,array('messages'=>'Code required'));
$Text1->setRequired(true);

$Text2 = new Zend_Form_Element_Text("description");
$Text2->setLabel('Description');
$CheckBox3 = new Zend_Form_Element_Checkbox("isactive");
$CheckBox3->setValue(1);


$parentpage_id = new Zend_Form_Element_Select("parentpage_id");
$parentpage_id->addValidator('NotEmpty',false,array('messages'=>'Field is required'));
$parentpage_id->setRequired(true);

$modelc = new Default_Model_Menu();
$addc = $modelc->getParentMenus();
$parentpage_id->addMultiOption(' ', 'Select...');
if (empty($addc)) {
    $msgarray['parentpage_id'] = 'Page  is not configured yet.';
} else {
    foreach ($addc as $addcs) {
        $parentpage_id->addMultiOptions(array($addcs['id'] => $addcs['menuName']));
    }
}
$parentpage_id->setRegisterInArrayValidator(false);

$Submit4 = new Zend_Form_Element_Submit("submit");
$Text1->addValidator(new Zend_Validate_Db_NoRecordExists(
    array('table'=>'main_pagetab',
              'field'=>'tabcode',
            'exclude'=>'pagetab_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
       ) )  
);
$groups = new Zend_Form_Element_Multiselect('groups');
$groupsModel = new Default_Model_Groups();
$groupData = $groupsModel->fetchAll('isactive="Y"')->toArray();

foreach ($groupData as $value) {
    $groups->addMultiOptions(array($value['group_id']=>$value['name']));
} 
$Text1->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
$Text1 ->setAttrib('maxlength', '100');
$Text0 ->setAttrib('maxlength', '100');
$this->addElements(array($id,$Text0,$Text1,$Text2,$CheckBox3,$parentpage_id,$groups,$Submit4));
$this->setElementDecorators(array('ViewHelper')); 
} 
 } 
 ?>