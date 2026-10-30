<?php

/**
 * Language Form
 *
 * Manages data entry for system languages and ISO locale codes.
 * Complies with SSOT v6.7.7 (Zend_Form, Strict Regex, Balanced AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Language extends Zend_Form
{
	public function init()
	{
		/* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
		$this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'language');

		$request = Zend_Controller_Front::getInstance()->getRequest();
		$idVal   = (int)$request->getParam('id', 0);

		/* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
		$idElement = new Zend_Form_Element_Hidden('id');
		$idElement->setValue($idVal);

		/* --------------------------------------------------------------
         * 2. Text Elements
         * -------------------------------------------------------------- */

		// SSOT VI.4 Mandatory Regex: Must contain letters, no leading space, no special chars
		$ssotRegexValidator = array(
			'Regex',
			true,
			array(
				'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
				'messages' => array(
					Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.'
				)
			)
		);

		// Language Code (e.g., 'en', 'am', 'es')
		$code = new Zend_Form_Element_Text('code');
		$code->setLabel('Language Code')
			->setAttribs(array('class' => 'formDataElement', 'maxlength' => '10', 'placeholder' => 'e.g., en, am, fr'))
			->setRequired(true)
			->addFilter('StringTrim')
			->addFilter('StripTags')
			->addFilter('StringToLower')
			->addValidator('NotEmpty', false, array('messages' => 'Please enter a language code.'))
			->addValidator('StringLength', false, array('max' => 10))
			->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

		// Unique Validation for Code
		$codeVal = new Zend_Validate_Db_NoRecordExists(array(
			'table'   => 'main_language',
			'field'   => 'code',
			'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
		));
		$codeVal->setMessage('This language code already exists.');
		$code->addValidator($codeVal);

		// Language Name
		$name = new Zend_Form_Element_Text('name');
		$name->setLabel('Language Name')
			->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., English, Amharic'))
			->setRequired(true)
			->addFilter('StringTrim')
			->addFilter('StripTags')
			->addValidator('NotEmpty', false, array('messages' => 'Please enter a language name.'))
			->addValidator('StringLength', false, array('max' => 100))
			->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

		// Unique Validation for Name
		$nameVal = new Zend_Validate_Db_NoRecordExists(array(
			'table'   => 'main_language',
			'field'   => 'name',
			'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
		));
		$nameVal->setMessage('This language name already exists.');
		$name->addValidator($nameVal);

		/* --------------------------------------------------------------
         * 3. Textarea Elements
         * -------------------------------------------------------------- */
		$description = new Zend_Form_Element_Textarea('description');
		$description->setLabel('Description')
			->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
			->addFilter('StringTrim')
			->addFilter('StripTags');

		/* --------------------------------------------------------------
         * 4. Checkbox Elements
         * -------------------------------------------------------------- */
		$isactive = new Zend_Form_Element_Checkbox('isactive');
		$isactive->setLabel('Active')
			->setCheckedValue('1')
			->setUncheckedValue('0')
			->setValue('1');

		/* --------------------------------------------------------------
         * 5. Submit Element
         * -------------------------------------------------------------- */
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Save')
			->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

		// SSOT VI.9: Strict String Quoting for standard AJAX saveDetails()
		$urlAction  = "'language/save/format/json'";
		$jsFunction = "'redirecttocontroller(\\'language\\');'";
		$submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

		/* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
		$this->addElements(array(
			$idElement,
			$code,
			$name,
			$description,
			$isactive,
			$submit
		));

		// SSOT VI.7: Strip default Zend Decorators
		$this->clearDecorators();
		foreach ($this->getElements() as $element) {
			$element->setDecorators(array('ViewHelper'));
		}
	}
}
