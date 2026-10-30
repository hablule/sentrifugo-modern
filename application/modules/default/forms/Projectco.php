<?php 
 
 
class Default_Form_Projectco extends Zend_Form{ 
 
    public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'project');
		

      
        $id = new Zend_Form_Element_Hidden('id');
             
  
    
    
        
      
        $company_id = new Zend_Form_Element_Select('company_id');
        $company_id->setLabel('Company');     
        $company_id->setAttrib('class','choseselect');
        $company_id->setAttrib('onchange','disableSelect(event)'); 
    

        $sbunit_id = new Zend_Form_Element_Select('sbunit_id');
        $sbunit_id->setLabel('Strategic Bussiness Unit');    

        $sbunit_id->setAttrib('class','choseselect');
        $sbunit_id->setAttrib('onchange','disableSelect(event)'); 

		$division_id = new Zend_Form_Element_Select('division_id');
        $division_id->setLabel('Division');  
        $division_id->setAttrib('class','choseselect');
        $division_id->setAttrib('onchange','disableSelect(event)'); 
        // $sbunit_id->addValidator('NotEmpty', false, array('messages' => 'Please select field.')); 
        // $sbunit_id->setRequired(true);

        $departement_id  = new Zend_Form_Element_Select('departement_id ');
        $departement_id ->setLabel('Department');      
        $departement_id->setAttrib('class','choseselect');
        $departement_id->setAttrib('onchange','disableSelect(event)'); 
        $section_id = new Zend_Form_Element_Select('section_id');
        $section_id ->setLabel('Section');  
        $section_id->setAttrib('class','choseselect');
        $section_id->setAttrib('onchange','disableSelect(event)'); 
        $project_id = new Zend_Form_Element_Select('project_id');
        $project_id ->setLabel('project_id');   
        $project_id->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.')); 
        $project_id->setRequired(true);
            $project_id->addValidator(new Zend_Validate_Db_NoRecordExists(
                array(
                    'table'=>'main_projectco',
                    'field'=>'project_id'
                )
                ));
      $project_id->getValidator('Db_NoRecordExists')->setMessage('Project already exists.');
   
        $associateproject_id = new Zend_Form_Element_Select('associateproject_id');
        $associateproject_id ->setLabel('Parent Project');   
        $associateproject_id->setAttrib('class','choseselect');
        $associateproject_id->setAttrib('onchange','disableSelect(event)'); 
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");
      
                    $isactive = new Zend_Form_Element_Checkbox('isactive');
            $isactive->setLabel('Active');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($id,$description,$isactive,$company_id,$sbunit_id,$departement_id,$division_id,$submit,$section_id,$project_id,$associateproject_id));
        $this->setElementDecorators(array('ViewHelper')); 
    } 
 } 
 ?>
 