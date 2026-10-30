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

class Default_Form_empholidays extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'empholidays');	
		$userid = new Zend_Form_Element_Hidden('user_id');
		
		$id = new Zend_Form_Element_Hidden('id');
		
		
		$holiday_id = new Zend_Form_Element_Select('holiday_id');
		$holiday_id->setAttrib('class','choseselect');
        $holiday_id->setAttrib('onchange','disableSelect(event)');
		$holidaycategory_id = new Zend_Form_Element_Select('holidaycategory_id');
		$holidaycategory_id->setAttrib('class','choseselect');
        $holidaycategory_id->setAttrib('onchange','disableSelect(event)');
		$description = new Zend_Form_Element_Textarea('description');
        $description->setAttrib('rows', 10);
        $description->setAttrib('cols', 50);
		$description ->setAttrib('maxlength', '200');
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Save');

		 $this->addElements(array($id,$holiday_id,$holidaycategory_id,$description,$isactive,$submit));
        $this->setElementDecorators(array('ViewHelper'));
     		
	}
}