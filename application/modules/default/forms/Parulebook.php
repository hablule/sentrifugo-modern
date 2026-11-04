<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Rulebook Form (Appraisal Templates)
 * 
 * Contains strictly the Header fields and the hidden payload containers 
 * for the Merged Form architecture.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Parulebook extends Zend_Form
{
    /**
     * Initialize the form elements, validators, and decorators.
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
        $this->setAttrib('name', 'parulebook');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 2. Hidden Elements (Header ID & Tab JSON Payloads)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        // Payload containers for the Merged Form Architecture
        $targetsPayload = new Zend_Form_Element_Hidden('targets_payload');
        $targetsPayload->setAttrib('id', 'targets_payload');

        $evaluatorsPayload = new Zend_Form_Element_Hidden('evaluators_payload');
        $evaluatorsPayload->setAttrib('id', 'evaluators_payload');

        $questionsPayload = new Zend_Form_Element_Hidden('questions_payload');
        $questionsPayload->setAttrib('id', 'questions_payload');

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        // Appraisal Frequency
        $frequency = new Zend_Form_Element_Select('frequency');
        $frequency->setLabel('Appraisal Frequency')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addMultiOptions([
                'Annual'    => 'Annual',
                'Biannual'  => 'Biannual',
                'Quarterly' => 'Quarterly',
                'Monthly'   => 'Monthly',
                'Ad-hoc'    => 'Ad-hoc (Continuous)'
            ])
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a frequency.']);

        // Trigger Type
        $trigger_type = new Zend_Form_Element_Select('trigger_type');
        $trigger_type->setLabel('Trigger Type')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addMultiOptions([
                'Manual'    => 'Manual',
                'Automatic' => 'Automatic'
            ])
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a trigger type.']);

        // Is Mandatory
        $is_mandatory = new Zend_Form_Element_Select('is_mandatory');
        $is_mandatory->setLabel('Is Mandatory?')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addMultiOptions([
                '1' => 'Yes',
                '0' => 'No'
            ])
            ->addValidator('NotEmpty', true, ['messages' => 'Please select if mandatory.']);

        /* --------------------------------------------------------------
         * 4. Text Elements
         * -------------------------------------------------------------- */

        // Rulebook Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Rulebook Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., ANNUAL_REVIEW'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a rulebook code.']);

        // Strict Regex for Codes (No spaces allowed)
        $code->addValidator('Regex', true, [
            'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9_]*)$/',
            'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Code must contain letters and cannot start with a space. Only letters, numbers, and underscores allowed.']
        ]);

        // DB Unique Check
        $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $code->addValidator('Db_NoRecordExists', true, [
            'table'    => 'main_pa_rulebook',
            'field'    => 'code',
            'exclude'  => $excludeClauseCode,
            'messages' => [Zend_Validate_Db_NoRecordExists::ERROR_RECORD_FOUND => 'This rulebook code already exists.']
        ]);

        // Rulebook Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Rulebook Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Standard Annual Review'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a rulebook name.']);

        // SSOT VI.4: Standard Regex Validator (Spaces allowed)
        $name->addValidator('Regex', true, [
            'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
            'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
        ]);

        // DB Unique Check
        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $name->addValidator('Db_NoRecordExists', true, [
            'table'    => 'main_pa_rulebook',
            'field'    => 'name',
            'exclude'  => $excludeClauseName,
            'messages' => [Zend_Validate_Db_NoRecordExists::ERROR_RECORD_FOUND => 'This rulebook name already exists.']
        ]);

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */

        // Description
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Checkbox Elements
         * -------------------------------------------------------------- */

        // IsActive
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 7. Submit Elements
         * -------------------------------------------------------------- */

        // CORRECTED: Explicit implementation of user requirement. 
        // Standard Zend_Form_Element_Submit. No inline JS onclick attribute.
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $targetsPayload,
            $evaluatorsPayload,
            $questionsPayload,
            $code,
            $name,
            $frequency,
            $trigger_type,
            $is_mandatory,
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
