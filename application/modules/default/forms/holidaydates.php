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

class Default_Form_holidaydates extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'holidaygroups');


        $id = new Zend_Form_Element_Hidden('id');
		
		
		$holidayname = new Zend_Form_Element_Text('holidayname');
        $holidayname->setAttrib('maxLength', 60);
        $holidayname->addFilter(new Zend_Filter_StringTrim());
        $holidayname->setRequired(true);
        $holidayname->addValidator('NotEmpty', false, ['messages' => 'Please enter holiday.']);  
        $holidayname->addValidator("regex",true,[                           
                           'pattern'=>'/^[a-zA-Z0-9.\- ?]+$/',
                           'messages'=>[
                               'regexNotMatch'=>'Please enter valid holiday.'
                           ]
        	]);
			
		$groupid = new Zend_Form_Element_Multiselect('groupid');
        $groupid->setAttrib('class', 'selectoption');
        $groupid->setRegisterInArrayValidator(false);
        $groupid->setRequired(true);
		$groupid->addValidator('NotEmpty', false, ['messages' => 'Please select holiday group.']);

        $start_date = new Zend_Form_Element_Text('start_date');
		$start_date->setAttrib('readonly', 'true');
		$start_date->setAttrib('onfocus', 'this.blur()');
		$start_date->setOptions(['class' => 'brdr_none']);	
		$start_date->setRequired(true);
        $start_date->addValidator('NotEmpty', false, ['messages' => 'Please select date.']);
        
        $end_date = new Zend_Form_Element_Text('end_date');
		$end_date->setAttrib('readonly', 'true');
		$end_date->setAttrib('onfocus', 'this.blur()');
		$end_date->setOptions(['class' => 'brdr_none']);	
		$end_date->setRequired(true);
        $end_date->addValidator('NotEmpty', false, ['messages' => 'Please select date.']);
		
		   	
		$description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");

        $description->setAttrib('rows', 10);
        $description->setAttrib('cols', 50);
		$description ->setAttrib('maxlength', '200');
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		 $this->addElements([$id,$holidayname,$groupid,$start_date, $end_date ,$description,$isactive,$submit]);
         $this->setElementDecorators(['ViewHelper']);
         $this->setElementDecorators([
                    'UiWidgetElement',
        ],['holidaydate']);		 
	}
}