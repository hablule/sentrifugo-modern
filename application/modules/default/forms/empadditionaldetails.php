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

class Default_Form_empadditionaldetails extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'empadditionaldetails');
		
		$id = new Zend_Form_Element_Hidden('id');
				
		$userid = new Zend_Form_Element_Hidden('user_id');
				
		

        $countries_served = new Zend_Form_Element_Select('countries_served');
		$countries_served->setLabel('Country');
        $countries_served->setRegisterInArrayValidator(false);
		
		$branch_service = new Zend_Form_Element_Select('branch_service');
		$branch_service->addValidator('NotEmpty',false,['messages'=>'Branch Required']);
		$branch_service->setRequired(true);
		$branch_service->setLabel('Branch of Service');
			
			
		$rank_achieved = new Zend_Form_Element_Select('rank_achieved');
		$rank_achieved->setRequired(true);
		$rank_achieved->addValidator('NotEmpty',false,['messages'=>'Rank Required']);
		$rank_achieved->setLabel('Rank Achieved');
		
			
		$from_date = new Zend_Form_Element_Text('from_date');
		$from_date->setLabel('From');
        $from_date->setAttrib('readonly', 'true');
		$from_date->setAttrib('onfocus', 'this.blur()');

        $to_date = new Zend_Form_Element_Text('to_date');
		$to_date->setLabel('To');
        $to_date->setAttrib('readonly', 'true');
		
		$to_date->setAttrib('onfocus', 'this.blur()');		
			
		$discharge_status = new Zend_Form_Element_Select('discharge_status');
		$discharge_status->setLabel('Status of Discharge');
        $discharge_status->setRegisterInArrayValidator(false);
		
		$service_number = new Zend_Form_Element_Text('service_number');
		$service_number->setAttrib('maxlength',30);
		$service_number->setLabel('Service Number');
		$service_number->addValidator("regex",true,[
                            'pattern'=>'/^[a-zA-Z0-9\-\. ]*$/', 
                           
                           'messages'=>[
                               'regexNotMatch'=>'Please enter valid service number.'
                           ]
        	]);
		$service_number->addFilters(['StringTrim']);	
			
	
			
					
			
		$military_servicetype = new Zend_Form_Element_Select('military_servicetype');
		$military_servicetype->setLabel('Military Service Type');
        $military_servicetype->setRegisterInArrayValidator(false);

        $veteran_status = new Zend_Form_Element_Select('veteran_status');
		$veteran_status->setLabel('Veteran Status');
        $veteran_status->setRegisterInArrayValidator(false);

        $special_training = new Zend_Form_Element_Textarea('special_training');
		$special_training->setLabel('Special Trainings');
        
		$special_training ->setAttrib('maxlength', '400');
		$special_training->addFilters(['StringTrim']);
		
        $awards = new Zend_Form_Element_Textarea('awards');
		$awards->setLabel('Awards/ Honors Received');
        $awards->setAttrib('rows', 10);
        $awards->setAttrib('cols', 50);
		$awards ->setAttrib('maxlength', '400');
		$awards->addFilters(['StringTrim']);
					
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');
		
		$this->addElements([$id,$userid,$countries_served,$branch_service,$rank_achieved,$from_date,$to_date,$discharge_status,$service_number,$military_servicetype,$veteran_status,$special_training,$awards,$submit]);
        $this->setElementDecorators(['ViewHelper']); 
 		 
	}
}