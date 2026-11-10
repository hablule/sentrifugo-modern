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

class Default_Form_empsalarydetails extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');

		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'empsalarydetails');

		$id = new Zend_Form_Element_Hidden('id');

		$userid = new Zend_Form_Element_Hidden('user_id');

		$currencyid = new Zend_Form_Element_Select('currencyid');
		$currencyid->setLabel('Currency');
		$currencyid->setRegisterInArrayValidator(false);
		$currencyid->setRequired(true);

		$salarytype = new Zend_Form_Element_Select('salarytype');
		$salarytype->setLabel("Pay Frequency");
		$salarytype->setAttrib('id', 'jobpayfrequency');
		//$salarytype->setAttrib('onchange', 'changesalarytext(this)');
		$salarytype->setRegisterInArrayValidator(false);
		$salarytype->setRequired(true);
		/*$salarytype->setMultiOptions(array(	
        					'' => 'Select Salary Type',						
							'1'=>'Yearly' ,
							'2'=>'Hourly',
							));*/

		$salary = new Zend_Form_Element_Text('salary');
		$salary->setLabel("Salary");
		$salary->setAttrib('maxLength', 8);
		$salary->addFilter(new Zend_Filter_StringTrim());
		$salary->setRequired(true);

		$salary->addValidators([
			[
				'validator'   => 'Regex',
				'breakChainOnFailure' => true,
				'options'     => [

					'pattern' => '/^[0-9\.]*$/',
					'messages' => [
						'regexNotMatch' => 'Please enter only numbers.'
					]
				]
			]
		]);


		$bank_account = new Zend_Form_Element_Select('bank_account');
		$bank_account->setLabel('Bank Account');
		$bank_account->setRegisterInArrayValidator(false);

		$description = new Zend_Form_Element_Text('description');
		$description->setLabel('Description');

		$start_date =  new Zend_Form_Element_Text('start_date');
		$start_date->setAttrib('readonly', 'readonly');
		$start_date->setLabel('From');
		$start_date->setRequired(true);

		$end_date =  new Zend_Form_Element_Text('end_date');
		$end_date->setAttrib('readonly', 'readonly');
		$end_date->setLabel('To');
		$end_date->setRequired(true);

		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		$isactive = new Zend_Form_Element_Checkbox('isactive');
		$isactive->setLabel('Active');

		$this->addElements([
			$id, $userid, $currencyid, $salarytype, $salary,
			$description, $bank_account,
			$start_date, $end_date,  $submit,
			$isactive
		]);
		$this->setElementDecorators(['ViewHelper']);
		$this->setElementDecorators([
			'UiWidgetElement',
		], ['accountholding']);
	}
}
