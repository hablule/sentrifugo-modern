<?php

class Default_Form_Empbankaccount extends Zend_Form
{

	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'empbankaccount');
		$id = new Zend_Form_Element_Hidden('id');

		$bankname = new Zend_Form_Element_Select('bankname');
		$bankname->setLabel('Bank Name');
		$bankname->setRequired(true);


		// $accountholder_name = new Zend_Form_Element_Text('accountholder_name');

		// $accountholder_name->setAttrib('maxlength', 40);
		// $accountholder_name->setLabel('Account Holder Name');
		// $accountholder_name->addFilters(array('StringTrim'));
		// $accountholder_name->addValidators(array(
		// 	array(
		// 		'validator'   => 'Regex',
		// 		'breakChainOnFailure' => true,
		// 		'options'     => array(
		// 			'pattern' => '/^[a-zA-Z\s]+$/i',
		// 			'messages' => array(
		// 				'regexNotMatch' => 'Please enter only alphabets.'
		// 			)
		// 		)
		// 	)
		// ));
		// $accountholder_name->setRequired(true);
		

		// $accountholding = new Zend_Form_Element_Text('accountholding');
		// $accountholding->setLabel('Account Holding Since');
		// $accountholding->setAttrib('readonly', 'true');
		// $accountholding->setAttrib('onfocus', 'this.blur()');
		// $accountholding->setOptions(array('class' => 'brdr_none'));
		// $accountholding->setRequired(true);

		$accountclasstypeid = new Zend_Form_Element_Select('accountclasstypeid');
		$accountclasstypeid->setLabel('Account Class Type');
		$accountclasstypeid->setRegisterInArrayValidator(false);
		$accountclasstypeid->setRequired(true);


		// $bankaccountid = new Zend_Form_Element_Select('bankaccountid');
		// $bankaccountid->setLabel('Account Type');
		// $bankaccountid->setRegisterInArrayValidator(false);
		$location = new Zend_Form_Element_Text('location');
		$location->setLabel('Location');
		$location->setAttrib('readonly',true);
		$location->setRequired(true);

		$isdefault = new Zend_Form_Element_Checkbox('isdefault');
		$isdefault->setLabel('Default');
	
		// $isdefault->setRegisterInArrayValidator(false);
		// $isdefault->setRequired(true);

		$description = new Zend_Form_Element_Text('description');
		$description->setLabel('Description');
		$description->setRequired(true);

		$bban = new Zend_Form_Element_Text('bban');
		$bban->setLabel('BBAN');
		$bban->setRequired(true);


		$iban = new Zend_Form_Element_Text('iban');
		$iban->setLabel('IBAN');
		$iban->setRequired(true);


		$accountnumber = new Zend_Form_Element_Text('accountnumber');
		$accountnumber->setAttrib('maxlength', 20);
		$accountnumber->setLabel('Account Number');
		$accountnumber->addFilters(array('StringTrim'));
		$accountnumber->addValidator("regex", true, array(
			'pattern' => '/^[0-9 ]*$/',

			'messages' => array(
				'regexNotMatch' => 'Please enter only numeric characters.'
			)
		));
		$accountnumber->setRequired(true);

		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('submit');

		$isactive = new Zend_Form_Element_Checkbox('isactive');
		$isactive->setLabel('Active');

		$this->addElements(array(
			$id, $bankname,/*  $accountholder_name, $accountholding, */ $accountclasstypeid,
			// $bankaccountid,
			$accountnumber, $description, $iban, $bban, $isdefault, $location, $submit,
			$isactive
		));
		$this->setElementDecorators(array('ViewHelper'));
	}
}
