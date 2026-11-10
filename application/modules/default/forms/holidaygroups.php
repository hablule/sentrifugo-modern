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

class Default_Form_holidaygroups extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'holidaygroups');


        $id = new Zend_Form_Element_Hidden('id');
		
		
		$groupname = new Zend_Form_Element_Text('name');
        $groupname->setAttrib('maxLength', 60);
        $groupname->addFilter(new Zend_Filter_StringTrim());
        $groupname->setRequired(true);
        $groupname->addValidator('NotEmpty', false, ['messages' => 'Please enter group name.']);  
        $groupname->addValidator("regex",true,[                           
                           'pattern'=>'/^[a-zA-Z0-9.\- ?]+$/',
                           'messages'=>[
                               'regexNotMatch'=>'Please enter valid group name.'
                           ]
        	]);
		$groupname->addValidator(new Zend_Validate_Db_NoRecordExists(
	                                            [  'table'=>'main_holidaycategory',
	                                                     'field'=>'name',
	                                                     'exclude'=>'holidaycategory_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'" and isactive="Y"',    
	
	                                                      ] ) );

        $groupname->getValidator('Db_NoRecordExists')->setMessage('Category name already exists.'); 
		
		   	
		$description = new Zend_Form_Element_Textarea('description');
        $description->setAttrib('rows', 10);
        $description->setAttrib('cols', 50);
		$description ->setAttrib('maxlength', '200');
		$description->setLabel('Description');
		$isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		 $this->addElements([$id,$groupname,$description,$isactive,$submit]);
         $this->setElementDecorators(['ViewHelper']); 
	}
}