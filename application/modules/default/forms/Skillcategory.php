<?php

/*********************************************************************************
 *  This file is part of Sentrifugo-Modern.
 *  Copyright (C) 2026 Hablul.
 ********************************************************************************/

/**
 * Skill Category Form
 *
 * Defines validation, filters, and AJAX submission for Skill Categories.
 * Refactored to comply with SSOT v7.0.0.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution <https://hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo-Modern v4.0
 */
class Default_Form_Skillcategory extends Zend_Form
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
        $this->setAttrib('name', 'skillcategory');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 2. Hidden Elements (SSOT VI.6)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 3. Text Elements (Alphabetical)
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

        // Skill Category Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Category Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., TECH_ENG'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a skill category code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($codeRegexValidator[0], $codeRegexValidator[1], $codeRegexValidator[2]);

        $excludeCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeUnique = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_skill_category',
            'field'   => 'code',
            'exclude' => $excludeCode
        ]);
        $codeUnique->setMessage('This skill category code already exists.');
        $code->addValidator($codeUnique);

        // Skill Category Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Category Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Technology and Engineering'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a skill category name.'])
            ->addValidator('StringLength', false, ['max' => 100])
            ->addValidator($nameRegexValidator[0], $nameRegexValidator[1], $nameRegexValidator[2]);

        $excludeName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameUnique = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_skill_category',
            'field'   => 'name',
            'exclude' => $excludeName
        ]);
        $nameUnique->setMessage('This skill category name already exists.');
        $name->addValidator($nameUnique);

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4, 'maxlength' => '255'])
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('StringLength', false, ['max' => 255]);

        /* --------------------------------------------------------------
         * 5. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 6. Submit Elements (SSOT VI.8 & VI.9)
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        $urlAction  = "'skillcategory/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'skillcategory\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
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
