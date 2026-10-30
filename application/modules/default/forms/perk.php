<?php
class Default_Form_perk extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'perk');

        $id = new Zend_Form_Element_Hidden('id');
        $code = new Zend_Form_Element_Text('code');
        $code->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.')); 
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(
            array(
                'table'=>'main_perk',
                'field'=>'code'
            )
            ));
      $code->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
        $code->setRequired(true);
      $code->setAttrib('maxlength',30);
        $name = new Zend_Form_Element_Text('name');
        $name->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.'));
        $name->addValidator("regex",true,array(
            'pattern'=>'/^[a-zA-Z.\-?]+$/',
            'messages'=>array(
                'regexNotMatch'=>'Name not valid'
            ) ));
        $name->addValidator(new Zend_Validate_Db_NoRecordExists(
            array(
                'table'=>'main_perk',
                'field'=>'name'
            )
            ));
      $name->getValidator('Db_NoRecordExists')->setMessage('Name already exists.');
        $name->setRequired(true);
        $category = new Zend_Form_Element_Select('category');
        $category->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.')); 
        $category->setRequired(true);
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");

        $start_date = new Zend_Form_Element_Text('start_date');
        $start_date->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.')); 
        $start_date->setRequired(true);
        $start_date->setAttrib('readonly', 'true');	
		$start_date->setAttrib('onfocus', 'this.blur()');
        $end_date = new Zend_Form_Element_Text('end_date');
        $end_date->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.')); 
        $end_date->setRequired(true);
        $end_date->setAttrib('readonly', 'true');	
		$end_date->setAttrib('onfocus', 'this.blur()');
        $type = new Zend_Form_Element_Select('type');
        $type->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.')); 
        $type->setRequired(true);
        $type->addMultiOptions(array(
            ' ' => 'Select...',
            'Individual' => 'Individual',
            'Percentage' => 'Percentage',
            'Fixed' => 'Fixed',
            'Calculated' => 'Calculated',
            'Spot' => 'Spot',
        ));
        $amount = new Zend_Form_Element_Text('amount');

        // $autoapproved = new Zend_Form_Element_Select('auto_approved');
        // $autoapproved->addMultiOptions(array('N'=>'No','Y'=>'Yes'));
        $autoapproved = new Zend_Form_Element_Checkbox('auto_approved');
        $autoapproved->setValue(0);
        $taxable = new Zend_Form_Element_Checkbox('taxable');
        $taxable->setValue(0);


        $category_name = new Zend_Form_Element_Text('category_name');
        $category_description = new Zend_Form_Element_Textarea('category_description');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');
       

        $back = new Zend_Form_Element_Button('back');
        $back->setLabel('Cancel');
        $back->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() . '/addperk' . "'");

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active');

        $this->addElements(array(
            $id, $code, $name, $category, $description, $start_date, $end_date,
            $type, $amount, $autoapproved, $taxable, $submit, $back,
            $isactive
        ));
        $this->addElements(array($category_name, $category_description));
        $this->setElementDecorators(array('ViewHelper'));
    }
}
