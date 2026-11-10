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

class Default_Form_Creditcarddetails extends Zend_Form
{ 
	public function init()
	{
       $this->setMethod('post');		
       $this->setAttrib('id', 'formid');
       $this->setAttrib('name','creditcarddetails');
       
		
        $id = new Zend_Form_Element_Hidden('id');
        $user_id = new Zend_Form_Element_Hidden('user_id');          
        //Card Type....(only alphabets)
                    
        
     
	         $cardnumber = new Zend_Form_Element_Text('cardnumber');
	         $cardnumber->addFilters(['StringTrim', 'StripTags']);
			 $cardnumber->setAttrib("maxlength",20);
			 $cardnumber->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
			 $cardnumber->setRequired(true);
		            
         $nameoncard = new Zend_Form_Element_Text('nameoncard');
	     $nameoncard->addFilter(new Zend_Filter_StringTrim());
		 $nameoncard->setAttrib('maxLength', 60);
		 $nameoncard->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
		 $nameoncard->setRequired(true);
        $expirydate = new ZendX_JQuery_Form_Element_DatePicker('expirydate');
		$expirydate->setOptions(['class' => 'brdr_none']);	
		$expirydate->setAttrib('readonly', 'true');
		$expirydate->setAttrib('onfocus', 'this.blur()'); 
		$expirydate->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
		$expirydate->setRequired(true);
		$issueddate = new ZendX_JQuery_Form_Element_DatePicker('issueddate');
		$issueddate->setOptions(['class' => 'brdr_none']);	
		$issueddate->setAttrib('readonly', 'true');
		$issueddate->setAttrib('onfocus', 'this.blur()');
		$issueddate->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
		$issueddate->setRequired(true); 
		// Expiration Date should be greater than today's date...
		// $card_expired_date->addValidator(new sapp_DateGreaterThanToday());
		         
         //Card Code ...
         $verficationcode = new Zend_Form_Element_Text('verficationcode');
	     $verficationcode->addFilter(new Zend_Filter_StringTrim());
		 $verficationcode->setAttrib('maxLength', 20);
		 $verficationcode->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
		 $verficationcode->setRequired(true); 
		 $paymentcard_id = new Zend_Form_Element_Select('paymentcard_id');
		 $paymentcard_id->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
		 $paymentcard_id->setRequired(true);
		 $description = new Zend_Form_Element_Textarea('description');
$description->setLabel("Description");
         // Form Submit ......... 
		 $isactive = new Zend_Form_Element_Checkbox('isactive');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttrib('id', 'submitbutton');
        $submit->setLabel('Save');

        $this->addElements([$id,$user_id,$cardnumber,$nameoncard,$isactive,$paymentcard_id,$expirydate,$description,$issueddate,$verficationcode,$submit]);
		$this->setElementDecorators(['ViewHelper']); 
		
		$this->setElementDecorators([
                    'UiWidgetElement',
        ],['issueddate','expirydate']);
		
        }
}
?>