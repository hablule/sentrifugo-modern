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

class Default_Form_addundum extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'addundum');


        $id = new Zend_Form_Element_Hidden('id');
		$managerstatus = new Zend_Form_Element_Select('managerstatus');
		$managerstatus->setLabel("Approve or Reject or Cancel");
        $managerstatus->setRegisterInArrayValidator(false);
        $managerstatus->setMultiOptions(array(							
							'Y'=>'Approve' ,
							'N'=>'Reject',
							));
							$approvedamount = new Zend_Form_Element_Text('approvedamount');
							$approvedamount->setLabel("Comments");
							$approvedamount->setAttrib('rows', 10);
							$approvedamount->setAttrib('cols', 50);
							$approvedamount ->setAttrib('maxlength', '50');
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');
		$submit->setAttrib('action',BASE_URL.'addundum');

		$this->addElements(array($id,$managerstatus,$approvedamount,$submit));
        $this->setElementDecorators(array('ViewHelper'));
	}
}