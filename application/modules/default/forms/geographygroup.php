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
 * Geography Group Lookup Form
 * 
 * Manages validation and structure for geographical cluster definitions 
 * (e.g., North America, EMEA, APAC).
 * Strictly adheres to SSOT v6.1: DB Schema parity, Regex validation, 
 * decorator clearing, and standard AJAX submission handlers.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Geographygroup extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

	/**
	 * Initializes the Geography Group form elements.
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
		$this->setAttrib('name', 'geographygroup');

		$request = Zend_Controller_Front::getInstance()->getRequest();
		$idVal   = (int)$request->getParam('id', 0);

		/* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
		$idElement = new Zend_Form_Element_Hidden('id');
		$idElement->setValue($idVal);

		/* --------------------------------------------------------------
         * 2. Text Elements (Alphabetical Order)
         * -------------------------------------------------------------- */

		// Group Code (Mandatory)
		$code = new Zend_Form_Element_Text('code');
		$code->setLabel('Group Code')
			->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., NA, EMEA, APAC'))
			->setRequired(true)
			->addFilter('StringTrim')
			->addFilter('StripTags')
			->addFilter('StringToUpper') // Force uppercase for standard DB storage
			->addValidator('NotEmpty', true, array('messages' => 'Please enter a group code.'))
			->addValidator('Regex', true, array(
				'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
				'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.')
			));

		$excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
		$codeVal = new Zend_Validate_Db_NoRecordExists(array(
			'table'   => 'main_geographygroup',
			'field'   => 'code',
			'exclude' => $excludeClauseCode
		));
		$codeVal->setMessage('This group code already exists.');
		$code->addValidator($codeVal);

		// Group Name (Mandatory)
		$name = new Zend_Form_Element_Text('name');
		$name->setLabel('Group Name')
			->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., North America, Europe & Middle East'))
			->setRequired(true)
			->addFilter('StringTrim')
			->addFilter('StripTags')
			->addValidator('NotEmpty', true, array('messages' => 'Please enter a group name.'))
			->addValidator('Regex', true, array(
				'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
				'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.')
			));

		$excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
		$nameVal = new Zend_Validate_Db_NoRecordExists(array(
			'table'   => 'main_geographygroup',
			'field'   => 'name',
			'exclude' => $excludeClauseName
		));
		$nameVal->setMessage('This group name already exists.');
		$name->addValidator($nameVal);

		/* --------------------------------------------------------------
         * 3. Textarea Elements
         * -------------------------------------------------------------- */

		// Description (Optional)
		$description = new Zend_Form_Element_Textarea('description');
		$description->setLabel('Description')
			->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
			->addFilter('StringTrim')
			->addFilter('StripTags');

		/* --------------------------------------------------------------
         * 4. Checkbox Elements
         * -------------------------------------------------------------- */

		// IsActive (State Mapping)
		$isactive = new Zend_Form_Element_Checkbox('isactive');
		$isactive->setLabel('Active')
			->setCheckedValue('1')
			->setUncheckedValue('0')
			->setValue('1');

		/* --------------------------------------------------------------
         * 5. Submit Elements
         * -------------------------------------------------------------- */

		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Save')
			->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

		// SSOT AJAX Submission Pattern (Strict Quoting)
		$urlAction  = "'geographygroup/save/format/json'";
		$jsFunction = "'redirecttocontroller(\"geographygroup\")'";
		$submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

		/* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

		$this->addElements(array(
			$idElement,
			$name,
			$code,
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
