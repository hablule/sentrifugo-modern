<?php
class Default_Form_Empaddress extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'empaddress');
       

        $id = new Zend_Form_Element_Hidden('id');
		
        
        $name = new Zend_Form_Element_Text('name');
        $name->setAttrib('maxLength', 255);
        $name->setLabel('Name');      
        $name->addValidator('NotEmpty', false, array('messages' => 'Name is required')); 
        $name->setRequired('true');
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Code');
        $code->addValidator('NotEmpty',false,array('messages'=>'Code is required'));
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('Description');
        $postal = new Zend_Form_Element_Text('postal');
        $postal->setLabel('Postal');
        $location= new Zend_Form_Element_Text('location');
        $location->setLabel('Location');      
        $location->addValidator('NotEmpty', false, array('messages' => 'Please fill Location.')); 
        $location->setAttrib('readonly',true);
        $location->setRequired(true);
        $addresstype = new Zend_Form_Element_Select('address_type_id');
        $addresstype->setLabel('Address Type');
		$addresstype->setRegisterInArrayValidator(false);
		$addresstype->setRequired(true);
        $companyid = new Zend_Form_Element_Select('company_id');
        $companyid->setLabel('Company');
        $companyid->setAttrib('class','choseselect');
        $companyid->setAttrib('onchange','disableSelect(event)');
        $sbunit = new Zend_Form_Element_Select('sbunit_id');
        $permanent  = new Zend_Form_Element_Checkbox('permanent');
        $sbunit->setLabel('Bussiness unit');
        $sbunit->setAttrib('class','choseselect');
        $sbunit->setAttrib('onchange','disableSelect(event)');
        $divisionid = new Zend_Form_Element_Select('division_id');
        $divisionid->setLabel('Division');
        $divisionid->setAttrib('class','choseselect');
        $divisionid->setAttrib('onchange','disableSelect(event)');
        $departmentid = new Zend_Form_Element_Select('department_id');
        $departmentid->setLabel('Department');
        $departmentid->setAttrib('class','choseselect');
        $departmentid->setAttrib('onchange','disableSelect(event)');
        $sectionid = new Zend_Form_Element_Select('section_id');
        $sectionid->setLabel('Section');
        $sectionid->setAttrib('class','choseselect');
        $sectionid->setAttrib('onchange','disableSelect(event)');
        $projectid = new Zend_Form_Element_Select('project_id');
        $projectid->setLabel('Project');
        $projectid->setAttrib('class','choseselect');
        $projectid->setAttrib('onchange','disableSelect(event)');
        $iscurrent=new Zend_Form_Element_Checkbox('iscurrent');
        $iscurrent->setLabel('Is Current Address');
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active');
        $empid = new Zend_Form_Element_Select('emp_id');
        $empid->setLabel('Employee');
        $empid->setAttrib('class','choseselect');
        $empid->setAttrib('onchange','disableSelect(event)');
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(
            array('table'=>'main_address',
                      'field'=>'code',
                    'exclude'=>'address_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
               ) )  
  );

$code->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
$code ->setAttrib('maxlength', '60');
$name ->setAttrib('maxlength', '100');


        $this->addElements(array($id,$name,$code,$description,$location,$addresstype,$permanent,$iscurrent,$companyid,$sbunit,$divisionid,$departmentid,$divisionid,$sectionid,$projectid,$postal,$empid,$isactive,$submit));
        $this->setElementDecorators(array('ViewHelper')); 
    }
}
?>