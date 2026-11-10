<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_Form_businessunits extends Zend_Form
{
	public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'businessunits');


        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Name');      
        $name->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $name->setRequired(true);
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Name');      
        $code->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $code->setRequired(true);
      
            $code->addValidator(new Zend_Validate_Db_NoRecordExists(
                [
                    'table'=>'main_sbunit',
                    'field'=>'code'
                ]
                ));
      $code->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
   
      $name->addValidators([
        [
            'validator'   => 'Regex',
            'breakChainOnFailure' => true,
            'options'     => [ 
            
            'pattern'=> '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9\-\s]*)$/',
                'messages' => [
                        'regexNotMatch'=>'Please enter valid Name.'
                ]
            ]
        ]
    ]); 	
    
        
        $representative_id = new Zend_Form_Element_Select('representative_id');
        $representative_id->setLabel('Representative');      
        $representative_id->addValidator('NotEmpty', false, ['messages' => 'Please select field.']); 
        $representative_id->setRequired(true);

        $company_id = new Zend_Form_Element_Select('company_id');
        $company_id->setLabel('Company');
        $company_id->setAttrib('class','choseselect');
        $company_id->setAttrib('onchange','disableSelect(event)');     

        // $company_id->addValidator('NotEmpty', false, array('messages' => 'Please select field.')); 
        // $company_id->setRequired(true);

        $parentsbu_id  = new Zend_Form_Element_Select('parentsbu_id ');
        $parentsbu_id ->setLabel('Parent Bussiness Unit');    
        $parentsbu_id->setAttrib('class','choseselect');
        $parentsbu_id->setAttrib('onchange','disableSelect(event)');
        // $sbunit_id->addValidator('NotEmpty', false, array('messages' => 'Please select field.')); 
        // $sbunit_id->setRequired(true);

     
        
        // $parentdivision_id->addValidator('NotEmpty', false, array('messages' => 'Please select field.')); 
        // $parentdivision_id->setRequired(true);

      

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");
      
                    $isactive = new Zend_Form_Element_Checkbox('isactive');
                    $isactive->setLabel('Active');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(
            ['table'=>'main_sbunit',
                      'field'=>'code',
                    'exclude'=>'sbunit_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
               ] )  
  );

$code->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
$code ->setAttrib('maxlength', '60');
$name ->setAttrib('maxlength', '100');
$description ->setAttrib('maxlength', '255');

        $this->addElements([$id,$code,$name,$description,$isactive,$representative_id,$company_id,$parentsbu_id,$submit]);
        $this->setElementDecorators(['ViewHelper']); 
    }
}
?>