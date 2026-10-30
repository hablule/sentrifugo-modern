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

class Default_Form_Disabilitydetails extends Zend_Form
{ 
	public function init()
	{
		
		$this->setMethod('post');		
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name','disabilitydetails');
        $this->setAttrib('action',BASE_URL.'disabilitydetails/add/');
		
        $id = new Zend_Form_Element_Hidden('id');
        $user_id = new Zend_Form_Element_Hidden('user_id');
	  
        
	   $disability_id = new Zend_Form_Element_Select('disability_id');
	   $disability_id->setRequired(true);
	   $disability_id->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']);
		
		// $disability_name->addValidators(array(
		// 				 array(
		// 					 'validator'   => 'Regex',
		// 					 'breakChainOnFailure' => true,
		// 					 'options'     => array( 
		// 					 'pattern' =>'/^[a-zA-Z][a-zA-Z0-9\-\s]+$/i',
		// 						 'messages' => array(
		// 								 'regexNotMatch'=>'Please enter valid disability name.'
		// 						 )
		// 					 )
		// 				 )
		// 			 )); 
		$isactive = new Zend_Form_Element_Checkbox('isactive');
        $onsetdate = new ZendX_JQuery_Form_Element_DatePicker('onsetdate');
		$onsetdate->setOptions(['class' => 'brdr_none']);	
		$onsetdate->setAttrib('readonly', 'true');	
		$onsetdate->setAttrib('onfocus', 'this.blur()');
		// $onsetdate->setRequired(true);
        // $onsetdate->addValidator('NotEmpty', false, array('messages' => 'Please fill the date.'));
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');
		$description = new Zend_Form_Element_Textarea('description');
		$description->setLabel("Description");
		$this->addElements([$id,$user_id,$disability_id,
								$isactive,$onsetdate,$description,
							$submit]);
       $this->setElementDecorators(['ViewHelper']); 
	   $this->setElementDecorators([
		'UiWidgetElement',
],['onsetdate']); 
		
        
	}
	
}
         