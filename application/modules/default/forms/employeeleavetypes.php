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

class Default_Form_employeeleavetypes extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('action',BASE_URL.'employeeleavetypes/edit');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'employeeleavetypes');


        $id = new Zend_Form_Element_Hidden('id');
		
		$name = new Zend_Form_Element_Text('name');
        $name->setAttrib('maxLength', 60);
        $name->setRequired(true);
        $name->addValidator('NotEmpty', false, ['messages' => 'Please enter leave category.']);
		$name->addValidators([
						 [
							 'validator'   => 'Regex',
							 'breakChainOnFailure' => true,
							 'options'     => [ 
							 
							 'pattern'=> '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9\-\s]*)$/',
								 'messages' => [
										 'regexNotMatch'=>'Please enter valid leave type.'
								 ]
							 ]
						 ]
					 ]);         
      
		
	
		$description = new Zend_Form_Element_Textarea('description');
        $description->setAttrib('rows', 10);
        $description->setAttrib('cols', 50);
		$description ->setAttrib('maxlength', '200');

	
        $submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		 $this->addElements([$id,$leavetype,$numberofdays,$leavecode,$leavepreallocated,$leavepredeductable,$description,$approvalrequired,$submit]);
         $this->setElementDecorators(['ViewHelper']); 
	}
}