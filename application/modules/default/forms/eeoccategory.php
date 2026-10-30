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
 * EEOC Category Form
 *
 * Manages data entry for Equal Employment Opportunity compliance classifications.
 * Complies with SSOT v6.7.7 (Zend_Form, Strict Regex, Balanced AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Eeoccategory extends Zend_Form
{
	public function init()
	{
		/* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
		$this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'eeoccategory');

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

		// Code
		$code = new Zend_Form_Element_Text('code');
		$code->setLabel('Category Code')
			->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., PROF, TECH'))
			->setRequired(true)
			->addFilter('StringTrim')
			->addFilter('StripTags')
			->addFilter('StringToUpper')
			->addValidator('NotEmpty', false, array('messages' => 'Please enter a category code.'))
			->addValidator('StringLength', false, array('max' => 60))
			->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

		$codeVal = new Zend_Validate_Db_NoRecordExists(array(
			'table'   => 'main_eeoccategory',
			'field'   => 'code',
			'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
		));
		$codeVal->setMessage('This code is already assigned to another EEOC category.');
		$code->addValidator($codeVal);

		// Name
		$name = new Zend_Form_Element_Text('name');
		$name->setLabel('Category Name')
			->setAttribs(array('class' => 'formDataElement', 'maxlength' => '255', 'placeholder' => 'e.g., Professionals, Technicians'))
			->setRequired(true)
			->addFilter('StringTrim')
			->addFilter('StripTags')
			->addValidator('NotEmpty', false, array('messages' => 'Please enter a category name.'))
			->addValidator('StringLength', false, array('max' => 255))
			->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

		$nameVal = new Zend_Validate_Db_NoRecordExists(array(
			'table'   => 'main_eeoccategory',
			'field'   => 'name',
			'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
		));
		$nameVal->setMessage('This name already exists.');
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

		$urlAction  = "'eeoccategory/save/format/json'";
		$jsFunction = "'redirecttocontroller(\\'eeoccategory\\');'";
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

		$this->clearDecorators();
		foreach ($this->getElements() as $element) {
			$element->setDecorators(array('ViewHelper'));
		}
	}
}
