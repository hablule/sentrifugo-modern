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

class Default_Form_Dependencydetails extends Zend_Form
{ 
	public function init()
	{
		
		$this->setMethod('post');		
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name','dependencydetails');
       
		
        $id = new Zend_Form_Element_Hidden('id');
        $user_id = new Zend_Form_Element_Hidden('emp_id');
	   	
		
       //Dependent Name ... 
        
        $fname = new Zend_Form_Element_Text('firstname');
        $fname->addFilter(new Zend_Filter_StringTrim());
        $fname->setRequired(true);
		$fname->setAttrib("maxlength",50);
        $fname->addValidator('NotEmpty', false, ['messages' => 'Please enter dependent name.']);
		
		$fname->addValidators([
						 [
							 'validator'   => 'Regex',
							 'breakChainOnFailure' => true,
							 'options'     => [ 
							 'pattern' =>'/^[a-zA-Z\s]+$/i',
								 'messages' => [
										 'regexNotMatch'=>'Please enter only alphabets.'
								 ]
							 ]
						 ]
					 ]); 

$mname = new Zend_Form_Element_Text('middlename');
$mname->addFilter(new Zend_Filter_StringTrim());
$mname->setRequired(true);
$mname->setAttrib("maxlength",50);
$mname->addValidator('NotEmpty', false, ['messages' => 'Please enter middle name.']);

$mname->addValidators([
  [
	  'validator'   => 'Regex',
	  'breakChainOnFailure' => true,
	  'options'     => [ 
	  'pattern' =>'/^[a-zA-Z\s]+$/i',
		  'messages' => [
				  'regexNotMatch'=>'Please enter only alphabets.'
		  ]
	  ]
  ]
]); 





$lname = new Zend_Form_Element_Text('lastname');
$lname->addFilter(new Zend_Filter_StringTrim());
$lname->setRequired(true);
$lname->setAttrib("maxlength",50);
$lname->addValidator('NotEmpty', false, ['messages' => 'Please enter middle name.']);

$lname->addValidators([
  [
	  'validator'   => 'Regex',
	  'breakChainOnFailure' => true,
	  'options'     => [ 
	  'pattern' =>'/^[a-zA-Z\s]+$/i',
		  'messages' => [
				  'regexNotMatch'=>'Please enter only alphabets.'
		  ]
	  ]
  ]
]); 
		
		//Disablity Type 
		
		$dependent_relation = new Zend_Form_Element_Select('relationship_id');
       	$dependent_relation->setRequired(true)->addErrorMessage('Please select  relation.');
		$dependent_relation->addValidator('NotEmpty', false, ['messages' => 'Please select relation.']); 
		
		//dependent_custody....
		$dependent_custody = new Zend_Form_Element_Select('custody');
		$dependent_custody->addValidator('NotEmpty', false, ['messages' => 'Please select dependent custody code.']); 
		$dependent_custody->setRequired(true)->addErrorMessage('Please select dependent custody code.');
		$dependent_custody->addMultiOptions([''=>'Select Dependent Custody Code',
						        'BOTH PARENT'=>'Both Parents',
								'FORMER SPOUSE'=>'Former Spouse',
								'SUBSCRIBER ONLY'=>'Subscriber Only',
								'OTHER OR UNKOWN'=>'Other Or Unknown'
									]);
		//Dependent DOB...
        $dateofbirth = new ZendX_JQuery_Form_Element_DatePicker('dateofbirth');
		$dateofbirth->setOptions(['class' => 'brdr_none']);	
		$dateofbirth->setAttrib('onchange', 'calcDays("dateofbirth","",this,1)');
		$dateofbirth->setRequired(true);
		$dateofbirth->setAttrib('readonly', 'true');
		$dateofbirth->setAttrib('onfocus', 'this.blur()');
        $dateofbirth->addValidator('NotEmpty', false, ['messages' => 'Please select date.']);		
		

		
		
		$bloodgroup = new Zend_Form_Element_Select('bloodgroup');
		$bloodgroup->setRequired(true);
		$bloodgroup->addValidator('NotEmpty',false,['messages'=>'Please select blood group']);
		$bloodgroup->addMultiOptions(BLOOD_GROUP_ENUM);

		$isdependent = new Zend_Form_Element_Checkbox('isdependent');
		$isdependent->setAttrib('onclick','toggleSelect()');
		$isactive = new Zend_Form_Element_Checkbox('isactive');
		$gender = new Zend_Form_Element_Select('gender');
		
	
		
      
		//Form Submit....
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		$this->addElements([$id,$user_id,$bloodgroup,$isactive,$fname,$mname,$lname,$dependent_relation,$isdependent,$dependent_custody,$gender,$dateofbirth,$submit]);
		$this->setElementDecorators(['ViewHelper']); 
       $this->setElementDecorators([
                    'UiWidgetElement',
        ],['dateofbirth']);
		
        
	}
	
}
         