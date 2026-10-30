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

class Default_Form_title extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('action',BASE_URL.'title/edit');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'title');


        $id = new Zend_Form_Element_Hidden('id');
		
		$code = new Zend_Form_Element_Text('code');
        $code->setLabel('Code');
        $code->setAttrib('maxLength', 5);
        $code->setRequired(true);
        $code->addValidator('NotEmpty', false, ['messages' => 'Please enter Title code.']);
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(
            [
                'table'=>'main_title',
                'field'=>'code'
            ]
        ));
        
        $code->getValidator('Db_NoRecordExists')->setMessage('Code already exists');
        $name = new Zend_Form_Element_Text('name');
        $name->setAttrib('maxLength', 60);
        $name->setRequired(true);
        $name->addValidator('NotEmpty', false, ['messages' => 'Please enter Title Name.']);  
      	$name->addValidator(new Zend_Validate_Db_NoRecordExists(
              [
                  'table'=>'main_title',
                  'field'=>'name'
              ]
              ));
        $name->getValidator('Db_NoRecordExists')->setMessage('Name already exists.');
        $name->addValidator("regex",true,[
            'pattern'=>'/^[a-zA-Z.\-?]+$/',
            'messages'=>[
                'regexNotMatch'=>'Name not valid'
            ]
            ]);	
		$description = new Zend_Form_Element_Textarea('description');
        $description->setAttrib('rows', 10);
        $description->setAttrib('cols', 50);
		$description ->setAttrib('maxlength', '255');
        $description->setLabel('Description');
        $isprefix = new Zend_Form_Element_Checkbox('isprefix');
        $issuffix = new Zend_Form_Element_Checkbox('issuffix');      
        $isactive = new Zend_Form_Element_Checkbox('isactive');   
        $submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		$this->addElements([$id,$code,$description,$name,$isprefix,$issuffix,$isactive,$submit]);
        $this->setElementDecorators(['ViewHelper']); 
	}
}