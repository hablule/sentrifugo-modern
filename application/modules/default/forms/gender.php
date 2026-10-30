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
 * Gender Lookup Form
 * 
 * Manages validation and structure for Gender entries.
 * Restored original Regex business logic and adheres strictly to SSOT v6.0.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Gender extends Zend_Form
{
	/**
	 * Initializes the Gender form elements.
	 * 
	 * @return void
	 */
	public function init()
	{
		/* --------------------------------------------------------------
         * Form Configuration (SSOT VI)
         * -------------------------------------------------------------- */
		$this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'gender');

		$request = Zend_Controller_Front::getInstance()->getRequest();
		$idVal   = (int)$request->getParam('id', 0);

		/* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
		$idElement = new Zend_Form_Element_Hidden('id');
		$idElement->setValue($idVal);

		/* --------------------------------------------------------------
         * 2. Text Elements (SSOT VI: DB Schema Parity)
         * -------------------------------------------------------------- */

		// Gender Name
		$name = new Zend_Form_Element_Text('name');
		$name->setLabel('Gender Name')
			->setAttribs(array('class' => 'formDataElement', 'maxlength' => '255', 'placeholder' => 'e.g., Male, Female, Non-Binary'))
			->setRequired(true)
			->addFilter('StringTrim')
			->addFilter('StripTags')
			->addValidator('NotEmpty', true, array('messages' => 'Please enter a gender name.'))
			->addValidator('Regex', true, array(
				'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
				'messages' => array(
					Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid gender name (Must contain letters, no leading spaces or special characters).'
				)
			));

		// Strict Check: Ignore current ID, check against all non-deleted records
		$excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
		$nameVal = new Zend_Validate_Db_NoRecordExists(array(
			'table'   => 'main_gender',
			'field'   => 'name',
			'exclude' => $excludeClauseName
		));
		$nameVal->setMessage('This gender name already exists.');
		$name->addValidator($nameVal);

		// Gender Code
		$code = new Zend_Form_Element_Text('code');
		$code->setLabel('Gender Code')
			->setAttribs(array('class' => 'formDataElement', 'maxlength' => '255', 'placeholder' => 'e.g., M, F, X'))
			->setRequired(true)
			->addFilter('StringTrim')
			->addFilter('StripTags')
			->addValidator('NotEmpty', true, array('messages' => 'Please enter a gender code.'))
			->addValidator('Regex', true, array(
				'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
				'messages' => array(
					Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid gender code (Must contain letters, no leading spaces or special characters).'
				)
			));

		// Strict Check: Ignore current ID, check against all non-deleted records
		$excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
		$codeVal = new Zend_Validate_Db_NoRecordExists(array(
			'table'   => 'main_gender',
			'field'   => 'code',
			'exclude' => $excludeClauseCode
		));
		$codeVal->setMessage('This gender code already exists.');
		$code->addValidator($codeVal);

		/* --------------------------------------------------------------
         * 3. Textarea Element
         * -------------------------------------------------------------- */
		$description = new Zend_Form_Element_Textarea('description');
		$description->setLabel('Description')
			->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
			->addFilter('StringTrim')
			->addFilter('StripTags');

		/* --------------------------------------------------------------
         * 4. Checkbox Element (SSOT II State Mapping)
         * -------------------------------------------------------------- */
		$isactive = new Zend_Form_Element_Checkbox('isactive');
		$isactive->setLabel('Active')
			->setCheckedValue('1')
			->setUncheckedValue('0')
			->setValue('1');

		/* --------------------------------------------------------------
         * 5. Submit Element (SSOT VI Standard AJAX Handler)
         * -------------------------------------------------------------- */
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Save')
			->setAttribs(array(
				'id'    => 'submitbutton',
				'class' => 'actbtn_btn'
			));

		// SSOT AJAX Submission Pattern (Strict Quoting, Empty 2nd Param, No return false)
		$urlAction  = "'gender/save/format/json'";
		$jsFunction = "'redirecttocontroller(\\'gender\\');'";
		$submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

		/* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI)
         * -------------------------------------------------------------- */
		$this->addElements(array(
			$idElement,
			$code,
			$name,
			$description,
			$isactive,
			$submit
		));

		// Strip default Zend Decorators and enforce ViewHelper universally
		$this->clearDecorators();
		foreach ($this->getElements() as $element) {
			$element->setDecorators(array('ViewHelper'));
		}
	}
}
