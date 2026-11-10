<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Rating Option Form (Rating Values)
 * 
 * Strictly adheres to SSOT v6.2. Defines the form for adding specific
 * options (e.g., "Excellent", "Good") to a parent Rating Scale.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Paratingoption extends Zend_Form
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
        $this->setAttrib('name', 'paratingoption');

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

        // Parent Rating Scale
        $rating_id = new Zend_Form_Element_Select('rating_id');
        $rating_id->setLabel('Parent Rating Scale')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a parent scale.']);

        /* --------------------------------------------------------------
         * 4. Text Elements
         * -------------------------------------------------------------- */

        // Numeric Value (for calculation)
        $value = new Zend_Form_Element_Text('value');
        $value->setLabel('Numeric Value')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '6', 'placeholder' => 'e.g., 5.00 or 1.00'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a numeric value.'])
            ->addValidator('Float', true, [
                'locale'   => 'en_US', // Standardize float parsing
                'messages' => [Zend_Validate_Float::NOT_FLOAT => 'Value must be a valid number.']
            ]);

        // Read-Only Normalized Value Display (Auto-Calculated)
        $normalized_value = new Zend_Form_Element_Text('normalized_value');
        $normalized_value->setLabel('Auto-Calculated Weight (%)')
            ->setAttribs([
                'class' => 'formDataElement',
                'readonly' => 'readonly',
                'disabled' => 'disabled', // Prevents POST
                'placeholder' => 'System calculated on save'
            ]);

        // Text Label (for display)
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Text Label')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Excellent'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a text label.']);

        // SSOT VI.4: Standard Regex Validator
        $name->addValidator('Regex', true, [
            'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
            'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
        ]);

        // Icon Class (Optional, for UI)
        $icon_class = new Zend_Form_Element_Text('icon_class');
        $icon_class->setLabel('Icon Class (Optional)')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., fa-star or fa-smile-o'])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */

        // Description (Optional)
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
         * 7. Submit Elements (SSOT VI.8 & VI.9)
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // Strict JSON AJAX callback pattern
        $urlAction  = "'paratingoption/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'paratingoption\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $rating_id,
            $value,
            $normalized_value,
            $name,
            $icon_class,
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
