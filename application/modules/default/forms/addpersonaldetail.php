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

/**
 * This gives candidate details form.
 */
class Default_Form_addpersonaldetail extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'addpersonaldetail');

        $id = new Zend_Form_Element_Hidden('id');
    
        $selected_option = Zend_Controller_Front::getInstance()->getRequest()->getParam('selected_option',null);


        $firstname = new Zend_Form_Element_Text('firstname');
        $firstname->setAttrib('maxLength', 60);
        $firstname->setLabel('First Name');    
        $firstname->setRequired(true);
        $firstname->addValidator('NotEmpty', false, array('messages' => 'Please fill name.'));  
        $lastname = new Zend_Form_Element_Text('lastname');
        $lastname->setAttrib('maxLength', 60);
        $lastname->setLabel('Last Name');    
        $lastname->setRequired(true);
        $middlename = new Zend_Form_Element_Text('middlename');
        $middlename->setAttrib('maxLength', 60);
        $middlename->setLabel('Middle Name');    
        $middlename->setRequired(true);
        $middlename->addValidator('NotEmpty', false, array('messages' => 'Please fill name.'));  
        $address1 = new Zend_Form_Element_Text('address2');
        $address1->setAttrib('maxLength', 60);
        $address1->setLabel('Street Address One');    
        $address1->setRequired(true);
        $address2 = new Zend_Form_Element_Text('address2');
        $address2->setAttrib('maxLength', 60);
        $address2->setLabel('Street Address Two');    
       
        $email = new Zend_Form_Element_Text('email');
        $email->setLabel('Email');    
        $email->setRequired(true);    

        $country = new Zend_Form_Element_Select("country");
        $country->setRegisterInArrayValidator(false);
        $country->setLabel("Country");		
        $country->setAttrib("class", "formDataElement"); 
        $country->setAttrib('onchange', 'displayParticularState_normal(this,"","state","city")');
        $country->setAttrib('title', 'Country');
       
        if($selected_option == 'fill-up-form'){
        	$country->setRequired(true);
        }
        
        $country->addValidator('NotEmpty', false, array('messages' => 'Please select country.'));
        $country->setRequired(true);

        $state = new Zend_Form_Element_Select("state");
        $state->setRegisterInArrayValidator(false);
        
        if($selected_option == 'fill-up-form'){
			$state->setRequired(true);
        }
        
		$state->addValidator('NotEmpty', false, array('messages' => 'Please select state.')); 
        $state->addMultiOptions(array(''=>'Select State'));
        $state->setLabel("State");		
        $state->setAttrib("class", "formDataElement"); 
        $state->setAttrib('onchange', 'displayParticularCity_normal(this,"","city","")');
        $state->setAttrib('title', 'State'); 		
        $state->setRequired(true);

        
        $city = new Zend_Form_Element_Select("city");
        $city->setRegisterInArrayValidator(false);
        $city->addMultiOptions(array(''=>'Select City'));
        $city->setLabel("City");		
        $city->setAttrib("class", "formDataElement");         
        $city->setAttrib('title', 'City');
        
        if($selected_option == 'fill-up-form'){
			$city->setRequired(true);
        }
        
		$city->addValidator('NotEmpty', false, array('messages' => 'Please select city.'));
        $city->setRequired(true);
        $contactprefer = new Zend_Form_Element_Select('contactprefer');
        $contactprefer->addMultiOptions(array(
            ' ' => 'Select...',
            '1' => 'Home Number',
            '2' => 'Mobile Number',
        
        ));
      
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');
        
      
		
        $this->addElements(array($address1,$address2,$address3,$address4,$isactive,$postal,$country,$state,$city,$map_coordinate,$submit));
        $this->setElementDecorators(array('ViewHelper'));
    }
}