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

class Default_Form_Educationdetails extends Zend_Form
{ 
	public function init()
	{
		
		$this->setMethod('post');		
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name','educationdetails');
        $this->setAttrib('action',BASE_URL.'educationdetails/addpopup/');
		
        $id = new Zend_Form_Element_Hidden('id');
		$user_id = new Zend_Form_Element_Hidden('user_id');
		
		
		
		$institution = new Zend_Form_Element_Select('institution');
	
		$institution->setAttrib('onchange','hideElement("institution_name","institution")');
		$description = new Zend_Form_Element_Textarea('description');
		$description->setLabel("Description");
        $status = new Zend_Form_Element_Select('status');
		$status->setRequired(true);
		$status->addMultiOptions(array(
			'Select'=>'Select',
			'Graduated'=>'Graduated',
			'Withdrawn'=>'Withdrawn',
			'Terminated'=>'Terminated',
			'Studing'=>'Studing'
		));
		$summaryofcourse = new Zend_Form_Element_Textarea('summaryofcourse');
		$summaryofcourse->setLabel("Summary of Course");
        $institution_name = new Zend_Form_Element_Text('institution_name');
        $institution_name->addFilter(new Zend_Filter_StringTrim());
		$institution_name->setAttrib("maxlength",50);
        $institution_name->addValidator('NotEmpty', false, array('messages' => 'Please enter institution name.'));
		$institution_name->setAttrib('onchange','hideElement("institution","institution_name")');
		$institution_name->addValidators(array(
						 array(
							 'validator'   => 'Regex',
							 'breakChainOnFailure' => true,
							 'options'     => array( 
							 'pattern' =>'/^[a-zA-Z\s]+$/i',
								 'messages' => array(
										 'regexNotMatch'=>'Please enter only alphabets.'
								 )
							 )
						 )
					 )); 
		
		//course ... 
        $studylevel = new Zend_Form_Element_Select('studylevel');
		$studyfield = new Zend_Form_Element_Select('studyfield');
		$studyfield_name = new Zend_Form_Element_Text('studyfield_name');
		$studyfield_name->setAttrib('onchange','hideElement("studyfield","studyfield_name")');
		$studyfield->setAttrib('onchange','hideElement("studyfield_name","studyfield")');
        $maxachscore = new Zend_Form_Element_Text('maxachscore');
        $maxachscore->addFilter(new Zend_Filter_StringTrim());
        $maxachscore->setRequired(true);
		$maxachscore->setAttrib("maxlength",3);
        $maxachscore->addValidator('NotEmpty', false, array('messages' => 'Please enter maxachscore name.'));
		
		//from_date..
        $from_date = new ZendX_JQuery_Form_Element_DatePicker('from_date');
		
		$from_date->setOptions(array('class' => 'brdr_none'));	
		$from_date->setRequired(true);
		$from_date->setAttrib('readonly', 'true');
		$from_date->setAttrib('onfocus', 'this.blur()');  
        $from_date->addValidator('NotEmpty', false, array('messages' => 'Please select from date.'));
        
        //to_date
        $to_date = new ZendX_JQuery_Form_Element_DatePicker('to_date');
		
		$to_date->setOptions(array('class' => 'brdr_none'));	
		$to_date->setRequired(true);
		$to_date->setAttrib('readonly', 'true');
		$to_date->setAttrib('onfocus', 'this.blur()');  
        $to_date->addValidator('NotEmpty', false, array('messages' => 'Please select to date.'));
        
        // percentage...
		
        $totalscore = new Zend_Form_Element_Text('totalscore');
        $totalscore->addFilter(new Zend_Filter_StringTrim());
        $totalscore->setRequired(true);
		$totalscore->setAttrib("maxlength",2);
        $totalscore->addValidator('NotEmpty', false, array('messages' => 'Please enter total score.'));
		
		
	   //Form Submit....
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		$this->addElements(array($id,$user_id,$summaryofcourse,$description,$studylevel,$studyfield,$studyfield_name,$institution,$status,$from_date,$to_date,$totalscore,$maxachscore,$institution_name,
							$submit));
       $this->setElementDecorators(array('ViewHelper')); 
		
		$this->setElementDecorators(array(
                    'UiWidgetElement',
        ),array('from_date','to_date'));
		
		
        
	}
	
}
         