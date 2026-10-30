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

class Default_Form_Trainingandcertificationdetails extends Zend_Form
{ 
	public function init()
	{
		
		$this->setMethod('post');		
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name','trainingandcertificationdetails');
        
		
        $id = new Zend_Form_Element_Hidden('id');
		$user_id = new Zend_Form_Element_Hidden('user_id');
		
		
        //course_name ... 
        
        $trainingname = new Zend_Form_Element_Text('trainingname');
        $trainingname->addFilter(new Zend_Filter_StringTrim());
		$trainingname->setAttrib('maxLength', 25);
		$offeredby = new Zend_Form_Element_Text('offeredby');
        $offeredby->addFilter(new Zend_Filter_StringTrim());
		$offeredby->setAttrib('maxLength', 25);
		$summaryofcourse = new Zend_Form_Element_Textarea('summaryofcourse');
		$summaryofcourse->setLabel('Summary of Course');

		$certificate_id = new Zend_Form_Element_Select('certificate_id');
		$certificate_id->setAttrib('onchange','hideElementoftwo("trainingname","certprovider_id","certificate_id",this)');

		$certprovider_id = new Zend_Form_Element_Select('certprovider_id');
		$certprovider_id->setAttrib('onchange','hideElementoftwocert("offeredby","certificate_id","certprovider_id",this)');

		$isactive = new Zend_Form_Element_Checkbox('isactive');
        $issueddate = new ZendX_JQuery_Form_Element_DatePicker('issueddate');
		$issueddate->setOptions(array('class' => 'brdr_none'));	
		$issueddate->setAttrib('readonly', 'true');
        $issueddate->setAttrib('onfocus', 'this.blur()');		
		$expirydate = new ZendX_JQuery_Form_Element_DatePicker('expirydate');
		$expirydate->setOptions(array('class' => 'brdr_none'));	
		$expirydate->setAttrib('readonly', 'true');
        $expirydate->setAttrib('onfocus', 'this.blur()');	
        // description ....
		$description = new Zend_Form_Element_Textarea('description');
        $description->setAttrib('rows', 10);
        $description->setAttrib('cols', 50);	
		$description->setLabel('Description');	
		$isactive = new Zend_Form_Element_Checkbox('isactive');

		$attestation_image_value = new Zend_Form_Element_Hidden('attestation_image_value');		
		$imgerr = new Zend_Form_Element_Hidden('imgerr');
		$imgerrmsg = new Zend_Form_Element_Hidden('imgerrmsg');
	   //Form Submit....
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		$this->addElements(array($id,$user_id,$certificate_id,$certprovider_id,$isactive,$description,$isactive,$issueddate,$expirydate,$trainingname,$offeredby,$summaryofcourse,$attestation_image_value,$imgerr,$imgerrmsg,$submit));
       $this->setElementDecorators(array('ViewHelper')); 
		
		$this->setElementDecorators(array(
                    'UiWidgetElement',
        ),array('issueddate','expirydate'));
		
		
        
	}
	
}
         