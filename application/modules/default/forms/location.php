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
class Default_Form_location extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'location');

        $id = new Zend_Form_Element_Hidden('id');
    
        $selected_option = Zend_Controller_Front::getInstance()->getRequest()->getParam('selected_option',null);


        $address1 = new Zend_Form_Element_Text('address1');
        $address1->setAttrib('maxLength', 60);
        $address1->setLabel('Address One');    
        $address1->setRequired(true);
        $address1->addValidator('NotEmpty', false, ['messages' => 'Please the Address.']);  
        $address2 = new Zend_Form_Element_Text('address2');
        $address2->setAttrib('maxLength', 60);
        $address2->setLabel('Address Two');    
        $address3 = new Zend_Form_Element_Text('address3');
        $address3->setAttrib('maxLength', 60);
        $address3->setLabel('Address Three');    
        $address4 = new Zend_Form_Element_Text('address4');
        $address4->setAttrib('maxLength', 60);
        $address4->setLabel('Address Four');    
        $postal = new Zend_Form_Element_Text('postal');
        $postal->setAttrib('maxLength', 10);
        $postal->setLabel('Postal');  
  
        $country = new Zend_Form_Element_Select("country");
        $country->addValidator('NotEmpty',false,['messages'=>'Select country']);
        $country->setRegisterInArrayValidator(false);
        $country->setLabel("Country");		
        $country->setAttrib("class", "formDataElement"); 
        $country->setAttrib('onchange', 'displayParticularState_normal(this,"","state","city")');
        $country->setAttrib('title', 'Country');
       
        if($selected_option == 'fill-up-form'){
        	$country->setRequired(true);
        }
        $country->setRequired(true);
        $country->addValidator('NotEmpty', false, ['messages' => 'Please select country.']);
        $state = new Zend_Form_Element_Select("state");
        $state->setRegisterInArrayValidator(false);
        
        if($selected_option == 'fill-up-form'){
			$state->setRequired(true);
        }
        
		$state->addValidator('NotEmpty', false, ['messages' => 'Please select state.']); 
        $state->addMultiOptions([''=>'Select State']);
        $state->setLabel("State");		
        $state->setAttrib("class", "formDataElement"); 
        $state->setAttrib('onchange', 'displayParticularCity_normal(this,"","city","")');
        $state->setAttrib('title', 'State'); 		
        $state->setRequired(true);
        
        $city = new Zend_Form_Element_Select("city");
        $city->setRegisterInArrayValidator(false);
        $city->addMultiOptions([''=>'Select City']);
        $city->setLabel("City");		
        $city->setAttrib("class", "formDataElement");         
        $city->setAttrib('title', 'City');
        
        if($selected_option == 'fill-up-form'){
			$city->setRequired(true);
        }
        
		$city->addValidator('NotEmpty', false, ['messages' => 'Please select city.']);
        $city->setRequired(true);

        $map_coordinate = new Zend_Form_Element_Text('map_coordinate');
        $map_coordinate->setAttrib('maxLength', 250);

        $map_coordinate->setLabel('map_coordinate');      
        $isactive = new Zend_Form_Element_Checkbox('isactive');
                    $isactive->setLabel('Active');
        $map_coordinate->addValidator('NotEmpty', false, ['messages' => 'Please enter map coordinate.']); 
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');
        
      
		
        $this->addElements([$address1,$address2,$address3,$address4,$isactive,$postal,$country,$state,$city,$map_coordinate,$submit]);
        $this->setElementDecorators(['ViewHelper']);
    }
}