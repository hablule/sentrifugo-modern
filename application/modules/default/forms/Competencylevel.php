<?php

/*********************************************************************************
 *  This file is part of Sentrifugo-Modern.
 *  Copyright (C) 2026 Hablul.
 ********************************************************************************/

/**
 * Competency Level Form
 *
 * Defines validation, filters, and AJAX submission for Competency Levels.
 * Refactored to comply with SSOT v7.0.0.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution <https://hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo-Modern v4.0
 */
class Default_Form_Competencylevel extends Zend_Form
{
    /**
     * Initializes form elements, validators, and decorators.
     *
     * @return void
     */
    public function init()
    {
        /* --------------------------------------------------------------
         * 1. Form Configuration (SSOT VI.5)
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'competencylevel');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 2. Hidden Elements (SSOT VI.6)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        // European Qualifications Framework (EQF 1-8)
        $eqf_level = new Zend_Form_Element_Select('eqf_level');
        $eqf_level->setLabel('EQF Level (1-8)')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(false)
            ->addMultiOptions([
                ''  => 'Select EQF Level (Optional)',
                '1' => 'Level 1 - Basic General Knowledge',
                '2' => 'Level 2 - Basic Factual Knowledge',
                '3' => 'Level 3 - Knowledge of Facts & Processes',
                '4' => 'Level 4 - Factual & Theoretical Knowledge',
                '5' => 'Level 5 - Comprehensive Specialized Knowledge',
                '6' => 'Level 6 - Advanced Critical Understanding',
                '7' => 'Level 7 - Highly Specialized Knowledge',
                '8' => 'Level 8 - Frontier Knowledge & Mastery'
            ]);

        /* --------------------------------------------------------------
         * 4. Text Elements (Alphabetical)
         * -------------------------------------------------------------- */

        
        // Code Regex: Requires at least one letter; allows letters, numbers, underscores (_), and hyphens (-). No spaces.
		$codeRegexValidator = array(
			'Regex',
			true,
			array(
				'pattern'  => '/^(?=.*[a-zA-Z])[a-zA-Z0-9_\-]+$/',
				'messages' => array(
					Zend_Validate_Regex::NOT_MATCH => 'Code must contain letters and may only use numbers, underscores (_), or hyphens (-).'
				)
			)
		);

		// Name Regex: Requires at least one letter; cannot start with a space; allows letters, numbers, spaces, and standard punctuation (- _ ( ) & . ,).
		$nameRegexValidator = array(
			'Regex',
			true,
			array(
				'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \-_()&.,]*)$/',
				'messages' => array(
					Zend_Validate_Regex::NOT_MATCH => 'Name must contain letters, cannot start with a space, and may only use standard punctuation (- _ ( ) & . ,).'
				)
			)
		);        

        // Competency Level Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Level Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., PRACTITIONER'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a competency level code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($codeRegexValidator[0], $codeRegexValidator[1], $codeRegexValidator[2]);

        $excludeCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeUnique = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_competency_level',
            'field'   => 'code',
            'exclude' => $excludeCode
        ]);
        $codeUnique->setMessage('This competency level code already exists.');
        $code->addValidator($codeUnique);

        // Competency Level Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Competency Level Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Autonomous Practitioner'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a competency level name.'])
            ->addValidator('StringLength', false, ['max' => 100])
            ->addValidator($nameRegexValidator[0], $nameRegexValidator[1], $nameRegexValidator[2]);

        $excludeName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameUnique = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_competency_level',
            'field'   => 'name',
            'exclude' => $excludeName
        ]);
        $nameUnique->setMessage('This competency level name already exists.');
        $name->addValidator($nameUnique);

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 7. Submit Elements (SSOT VI.8 & VI.9)
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        $urlAction  = "'competencylevel/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'competencylevel\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $eqf_level,
            $code,
            $name,
            $description,
            $isactive,
            $submit
        ]);

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
