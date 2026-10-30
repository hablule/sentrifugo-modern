<?php
class Default_Form_deductive extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'deductive');

        $id = new Zend_Form_Element_Hidden('id');
        $code = new Zend_Form_Element_Text('code');
        $code->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(
            [
                'table'=>'main_load',
                'field'=>'code'
            ]
            ));
      $code->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
        $code->setRequired(true);
        $code->setAttrib('maxlength',30);
        $name = new Zend_Form_Element_Text('name');
        $name->setAttrib('maxlength',60);
        $name->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']);
        $name->addValidator("regex",true,[
            'pattern'=>'/^[a-zA-Z.\-?]+$/',
            'messages'=>[
                'regexNotMatch'=>'Name not valid'
            ] ]);
        $name->addValidator(new Zend_Validate_Db_NoRecordExists(
            [
                'table'=>'main_load',
                'field'=>'name'
            ]
            ));
      $name->getValidator('Db_NoRecordExists')->setMessage('Name already exists.');
        $name->setRequired(true);
        $category = new Zend_Form_Element_Select('category');
        $category->addMultiOptions([
            ' ' => 'Select...',
            
        ]);
        $category->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $category->setRequired(true);
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");

        $start_date = new Zend_Form_Element_Text('start_date');
        $start_date->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $start_date->setRequired(true);
        $start_date->setAttrib('readonly', 'true');	
		$start_date->setAttrib('onfocus', 'this.blur()');
        $end_date = new Zend_Form_Element_Text('end_date');
        $end_date->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $end_date->setRequired(true);
        $end_date->setAttrib('readonly', 'true');	
		$end_date->setAttrib('onfocus', 'this.blur()');
        $type = new Zend_Form_Element_Select('type');
        $type->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $type->setRequired(true);
        $type->addMultiOptions([
            ' ' => 'Select...',
            'Individual' => 'Individual',
            'Percentage' => 'Percentage',
            'Fixed' => 'Fixed',
            'Calculated' => 'Calculated',
            'Spot' => 'Spot',
        ]);

      

        $amount = new Zend_Form_Element_Text('amount');
        $ispenality = new Zend_Form_Element_Checkbox('ispenality');
        $ispenality->setValue(0);
        $istax = new Zend_Form_Element_Checkbox('istax');
        $istax->setValue(0);
        $autoapprove = new Zend_Form_Element_Checkbox('autoapprove');

        $category_name = new Zend_Form_Element_Text('category_name');
    

        $category_description = new Zend_Form_Element_Textarea('category_description');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $back = new Zend_Form_Element_Button('back');
        $back->setLabel('Cancel');
        $back->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() . '/adddeductive' . "'");

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active');
        $this->addElements([
            $id, $code, $name, $category, $description,
            $ispenality, $istax, $autoapprove, $start_date, $end_date,
            $type, $amount, $submit, $back,
            $isactive
        ]);
        $this->addElements([$category_name, $category_description]);
        $this->setElementDecorators(['ViewHelper']);
    }
}
