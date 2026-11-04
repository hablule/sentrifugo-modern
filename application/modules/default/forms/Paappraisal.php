<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Appraisal Form (The Campaign Header)
 * 
 * Strictly adheres to SSOT v6.2. 
 * Formats the primary header data and provides hidden input arrays 
 * for the Merged Form UI (Phase 3: Campaign Generator).
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Paappraisal extends Zend_Form
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
        $this->setAttrib('name', 'paappraisal');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 2. Hidden Elements (SSOT VI.6)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        // Payload containers for the Merged Form Architecture (Audience & Questions)
        $audiencePayload = new Zend_Form_Element_Hidden('audience_payload');
        $audiencePayload->setAttrib('id', 'audience_payload');

        $questionsPayload = new Zend_Form_Element_Hidden('questions_payload');
        $questionsPayload->setAttrib('id', 'questions_payload');

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        // Parent Rulebook (The Template)
        $rulebook_id = new Zend_Form_Element_Select('rulebook_id');
        $rulebook_id->setLabel('Rulebook Template')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Rulebook Template.']);
        // Options populated in Controller via getPairs()

        // Sentrifugo Core Period (e.g., 2026 Q1)
        $period_id = new Zend_Form_Element_Select('period_id');
        $period_id->setLabel('Financial Period (Optional)')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(false);
        // Nullable to support "Ad-Hoc" Campaigns

        /* --------------------------------------------------------------
         * 4. Text & Date Elements
         * -------------------------------------------------------------- */

        // Campaign Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Campaign Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '255', 'placeholder' => 'e.g., 2026 Q1 Marketing Review'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a Campaign name.']);

        // SSOT VI.4: Standard Regex Validator (Spaces allowed)
        $name->addValidator('Regex', true, [
            'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
            'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
        ]);

        // Start Date
        $start_date = new Zend_Form_Element_Text('start_date');
        $start_date->setLabel('Start Date')
            ->setAttribs(['class' => 'formDataElement', 'readonly' => 'readonly', 'id' => 'start_date'])
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a start date.']);

        // End Date
        $end_date = new Zend_Form_Element_Text('end_date');
        $end_date->setLabel('End Date')
            ->setAttribs(['class' => 'formDataElement', 'readonly' => 'readonly', 'id' => 'end_date'])
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select an end date.']);

        // Cross-Field Validation: End Date must be >= Start Date
        $end_date->addValidator('Callback', true, [
            'callback' => function ($value, $context) {
                if (empty($value) || empty($context['start_date'])) return true; // Pass if empty
                $start = strtotime(sapp_Global::change_date($context['start_date'], 'database'));
                $end   = strtotime(sapp_Global::change_date($value, 'database'));
                return $end >= $start;
            },
            'messages' => [
                Zend_Validate_Callback::INVALID_VALUE => 'End Date must be greater than or equal to the Start Date.'
            ]
        ]);

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */

        // Description
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description / Instructions')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Checkbox Elements
         * -------------------------------------------------------------- */

        // IsActive
        // NOTE: The 'status' column (Draft/Active/Closed) is handled by the Controller logic, 
        // but 'isactive' (1/0) remains as the standard SSOT soft-delete visibility toggle.
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 7. Submit Elements
         * -------------------------------------------------------------- */

        // Standard Submit Button. NO inline JS onclick attribute.
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Draft')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $audiencePayload,
            $questionsPayload,
            $rulebook_id,
            $period_id,
            $name,
            $start_date,
            $end_date,
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
