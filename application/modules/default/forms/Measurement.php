<?php

/*********************************************************************************
 *  This file is part of Sentrifugo-Modern.
 *  Copyright (C) 2026 Hablul.
 ********************************************************************************/

/**
 * Measurement Form (Merged Master-Detail)
 *
 * Defines validation, filters, and payload container for Measurements
 * and inline Measurement Index values.
 * Refactored to comply with SSOT v7.0.0.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution <https://hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo-Modern v4.0
 */
class Default_Form_Measurement extends Zend_Form
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
        $this->setAttrib('name', 'measurement');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 2. Hidden Elements (Alphabetical)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        $indexes_payload = new Zend_Form_Element_Hidden('indexes_payload');
        $indexes_payload->setAttrib('id', 'indexes_payload');

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */
        $value_type = new Zend_Form_Element_Select('value_type');
        $value_type->setLabel('Value Type')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addMultiOptions([
                'LIST'     => 'List (Pre-defined Index Values)',
                'SPECIFIC' => 'Specific (Direct Numeric / Free Entry)'
            ]);

        /* --------------------------------------------------------------
         * 4. Text Elements (Alphabetical)
         * -------------------------------------------------------------- */

        // Code Regex: Requires at least one letter; allows letters, numbers, underscores (_), and hyphens (-). No spaces.
        $codeRegexValidator = [
            'Regex',
            true,
            [
                'pattern'  => '/^(?=.*[a-zA-Z])[a-zA-Z0-9_\-]+$/',
                'messages' => [
                    Zend_Validate_Regex::NOT_MATCH => 'Code must contain letters and may only use numbers, underscores (_), or hyphens (-).'
                ]
            ]
        ];

        // Name Regex: Requires at least one letter; cannot start with a space; allows letters, numbers, spaces, and standard punctuation (- _ ( ) & . ,).
        $nameRegexValidator = [
            'Regex',
            true,
            [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \-_()&.,]*)$/',
                'messages' => [
                    Zend_Validate_Regex::NOT_MATCH => 'Name must contain letters, cannot start with a space, and may only use standard punctuation (- _ ( ) & . ,).'
                ]
            ]
        ];

        // Scale Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Measurement Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., PROF_5PT'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a measurement code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($codeRegexValidator[0], $codeRegexValidator[1], $codeRegexValidator[2]);

        $excludeCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeUnique = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_measurement',
            'field'   => 'code',
            'exclude' => $excludeCode
        ]);
        $codeUnique->setMessage('This measurement code already exists.');
        $code->addValidator($codeUnique);

        // Measurement Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Measurement Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., 5-Point Skill Proficiency Scale'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a measurement name.'])
            ->addValidator('StringLength', false, ['max' => 100])
            ->addValidator($nameRegexValidator[0], $nameRegexValidator[1], $nameRegexValidator[2]);

        $excludeName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameUnique = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_measurement',
            'field'   => 'name',
            'exclude' => $excludeName
        ]);
        $nameUnique->setMessage('This measurement name already exists.');
        $name->addValidator($nameUnique);

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4, 'maxlength' => '255'])
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('StringLength', false, ['max' => 255]);

        /* --------------------------------------------------------------
         * 6. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 7. Submit Elements (SSOT VI.8)
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $indexes_payload,
            $value_type,
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
