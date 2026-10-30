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

class Default_Form_jobtitles extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'jobtitles');


        $id = new Zend_Form_Element_Hidden('id');
		$emptyflag = new Zend_Form_Element_Hidden('emptyFlag');
		
		$code = new Zend_Form_Element_Text('code');
        $code->setAttrib('maxLength', 60);
        $code->setRequired(true);
        // $code->addValidator('NotEmpty', false, array('messages' => 'Please enter job title code.'));
		// $code->addValidator("regex",true,array(
        //                    'pattern'=>'/^[a-zA-Z][a-zA-Z0-9\s]*$/', 
        //                    'messages'=>array(
        //                        'regexNotMatch'=>'Please enter valid job title code.'
        //                    )
        // 	));
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(
                                              array('table'=>'main_jobtitle',
                                                        'field'=>'code',
                                                      'exclude'=>'jobtitle_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'" and isactive="Y"',    
                                                 ) )  
                                    );
        $code->getValidator('Db_NoRecordExists')->setMessage('Job title code already exists.'); 		
      
		$name = new Zend_Form_Element_Text('name');
        $name->setAttrib('maxLength', 50);
        $name->setRequired(true);
        $name->addValidator('NotEmpty', false, array('messages' => 'Please enter job title.'));  
		$name->addValidator("regex",true,array(
                           'pattern'=>'/^[a-zA-Z][a-zA-Z0-9\-\&\s]*$/', 
                           'messages'=>array(
                               'regexNotMatch'=>'Please enter valid job title.'
                           )
        	));
	
		$description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description');
     
		$isactive = new Zend_Form_Element_Checkbox('isactive');
    

        $submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Save');

		 $this->addElements(array($id,$code,$name,$description,$emptyflag,$isactive,$submit));
         $this->setElementDecorators(array('ViewHelper')); 
	}
}