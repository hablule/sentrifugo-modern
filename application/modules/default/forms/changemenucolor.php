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

class Default_Form_changemenucolor extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAction(BASE_URL.'dashboard/changemenucolor');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'changemenucolor');


        $id = new Zend_Form_Element_Hidden('id');
         $submit = new Zend_Form_Element_Submit('submit');
		 $default = new Zend_Form_Element_Button('default');
		 $default->setLabel('Reset');
		 $theme = new Zend_Form_Element_Select('theme');
		 $theme->addMultiOptions([
            ' ' => 'Select...',
      
        ]);
		$theme->addValidator('NotEmpty', false, ['messages' => 'Please Select Theme.']); 
        $theme->setRequired(true);
		 $default->setAttrib('onClick' ,"parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() . '/dashboard/reset' . "'");

		 $this->addElements([$id,$submit,$theme,$default]);
         $this->setElementDecorators(['ViewHelper']); 
	}
}