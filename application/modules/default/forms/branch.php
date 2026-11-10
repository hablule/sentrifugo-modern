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

class Default_Form_branch extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('action',BASE_URL.'branch');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'branch');


        $id = new Zend_Form_Element_Hidden('id');
		
		$branch = new Zend_Form_Element_Text('branch');
        $branch->setAttrib('maxLength', 20);
        $branch->setRequired(true);
        $branch->addValidator('NotEmpty', false, ['messages' => 'Please enter branch.']);  
		$branch->addValidator(new Zend_Validate_Db_NoRecordExists(
			[
				'table'=>'main_emp_branch',
				'field'=>'branch'
			]
			));

			$branch->getValidator('Db_NoRecordExists')->setMessage('Branch already exists.');
			
		// $location->addValidator(new Zend_Validate_Db_NoRecordExists(
        //                                       array('table'=>'main_emp_location',
        //                                                 'location'=>'location',
        //                                               'exclude'=>'emp_location_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('emp_location_id').'" and isactive=1',    
        //                                          ) )  
        //                             );
        ///$location->getValidator('Db_NoRecordExists')->setMessage('Location already exists.'); 			
		
		$description = new Zend_Form_Element_Textarea('description');
		$description->setLabel("Description");
        $description->setAttrib('rows', 10);
        $description->setAttrib('cols', 50);
		$description ->setAttrib('maxlength', '200');
		$isactive=new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active');
        $submit = new Zend_Form_Element_Submit('submit');
		 //$submit->setAttrib('id', 'submitbutton');
		 $submit->setLabel('Save');

		// $url = "'gender/saveupdate/format/json'";
		// $dialogMsg = "''";
		// $toggleDivId = "''";
		// $jsFunction = "'redirecttocontroller(\'gender\');'";;

		 $this->addElements([$id,$branch,$description,$submit,$isactive]);
         $this->setElementDecorators(['ViewHelper']); 
	}
}