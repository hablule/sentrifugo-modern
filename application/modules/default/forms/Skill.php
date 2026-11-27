<?php

/*********************************************************************************
 *  This file is part of Sentrifugo-Modern.
 *  Copyright (C) 2026 Hablul.
 ********************************************************************************/

/**
 * Skill Form
 *
 * Defines validation, filters, and AJAX submission for N-level hierarchical Skills.
 * Refactored to comply with SSOT v7.0.0.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution <https://hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo-Modern v4.0
 */
class Default_Form_Skill extends Zend_Form
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
        $this->setAttrib('name', 'skill');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 2. Hidden Elements (SSOT VI.6)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 3. Select Elements (Alphabetical)
         * -------------------------------------------------------------- */

        $is_summary = new Zend_Form_Element_Select('is_summary');
        $is_summary->setLabel('Node Type')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addMultiOptions([
                '0' => 'Leaf Skill (Assignable to Employees / Questions)',
                '1' => 'Summary Group (Taxonomy Folder Node)'
            ])
            ->setValue('0');

        $measurement_id = new Zend_Form_Element_Select('measurement_id');
        $measurement_id->setLabel('Measurement Scale')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a measurement scale.']);

        $parent_id = new Zend_Form_Element_Select('parent_id');
        $parent_id->setLabel('Parent Skill Group')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(false);

        $skill_category_id = new Zend_Form_Element_Select('skill_category_id');
        $skill_category_id->setLabel('Skill Category')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a skill category.']);

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

        // Taxonomy Code Regex: Allows ESCO, O*NET, or SFIA identifiers (letters, numbers, dots, underscores, hyphens, colons, slashes).
        $taxonomyRegexValidator = [
            'Regex',
            true,
            [
                'pattern'  => '/^[a-zA-Z0-9._\-:\/]+$/',
                'messages' => [
                    Zend_Validate_Regex::NOT_MATCH => 'Taxonomy code may only contain letters, numbers, dots, underscores, hyphens, colons, or slashes.'
                ]
            ]
        ];

        // Skill Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Skill Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., PHP8_ZF'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a skill code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($codeRegexValidator[0], $codeRegexValidator[1], $codeRegexValidator[2]);

        $excludeCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeUnique = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_skill',
            'field'   => 'code',
            'exclude' => $excludeCode
        ]);
        $codeUnique->setMessage('This skill code already exists.');
        $code->addValidator($codeUnique);

        // Skill Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Skill Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., PHP 8.3 and Zend Framework'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a skill name.'])
            ->addValidator('StringLength', false, ['max' => 100])
            ->addValidator($nameRegexValidator[0], $nameRegexValidator[1], $nameRegexValidator[2]);

        // Display Sort Order within Parent Branch
        $sort_order = new Zend_Form_Element_Text('sort_order');
        $sort_order->setLabel('Display Order')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => 'e.g., 1'])
            ->setRequired(true)
            ->setValue('1')
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a display order.'])
            ->addValidator('Digits', true, ['messages' => [Zend_Validate_Digits::NOT_DIGITS => 'Display order must be a positive integer.']])
            ->addValidator('GreaterThan', true, ['min' => 0, 'messages' => [Zend_Validate_GreaterThan::NOT_GREATER => 'Display order must be at least 1.']]);

        // ESCO / O*NET / SFIA Taxonomy Code
        $taxonomy_code = new Zend_Form_Element_Text('taxonomy_code');
        $taxonomy_code->setLabel('Taxonomy Code (ESCO / SFIA / O*NET)')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., SFIA-PROG'])
            ->setRequired(false)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($taxonomyRegexValidator[0], $taxonomyRegexValidator[1], $taxonomyRegexValidator[2]);

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
         * 7. Submit Elements (SSOT VI.8 & VI.9)
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        $urlAction  = "'skill/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'skill\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $is_summary,
            $measurement_id,
            $parent_id,
            $skill_category_id,
            $code,
            $name,
            $sort_order,
            $taxonomy_code,
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
