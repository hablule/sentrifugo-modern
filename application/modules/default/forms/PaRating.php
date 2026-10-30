<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Rating Form
 * 
 * Strictly adheres to SSOT v6.2 (Form Element Grouping & Alphabetical Ordering).
 */
class Default_Form_Parating extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'parating');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * TYPE: HIDDEN (Alphabetical)
         * -------------------------------------------------------------- */

        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        $options_payload = new Zend_Form_Element_Hidden('options_payload');
        $options_payload->setAttrib('id', 'options_payload');

        /* --------------------------------------------------------------
         * TYPE: SELECT (Alphabetical)
         * -------------------------------------------------------------- */

        $data_type = new Zend_Form_Element_Select('data_type');
        $data_type->setLabel('Data Type')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addMultiOptions(array('Structured' => 'Structured (Pre-defined Options)', 'Unstructured' => 'Unstructured (Free Text)'));

        $ui_widget = new Zend_Form_Element_Select('ui_widget');
        $ui_widget->setLabel('UI Widget')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addMultiOptions(array('Dropdown' => 'Dropdown', 'Radio' => 'Radio Buttons', 'Slider' => 'Slider', 'Stars' => 'Stars', 'Textarea' => 'Textarea', 'Textbox' => 'Textbox'));

        // Step Value (Only applies to Sliders)
        $step_value = new Zend_Form_Element_Text('step_value');
        $step_value->setLabel('Slider Increment Step')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => 'e.g., 0.5 or 1.0'))
            ->setRequired(false)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('Float', true, array(
                'locale'   => 'en_US', 
                'messages' => array(Zend_Validate_Float::NOT_FLOAT => 'Step value must be a valid number.')
            ));

        /* --------------------------------------------------------------
         * TYPE: TEXT (Alphabetical)
         * -------------------------------------------------------------- */

        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Scale Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., STANDARD_1_5'))
            ->setRequired(true)
            ->addFilter('StringTrim')->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a scale code.'))
            ->addValidator('Regex', true, array('pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9_]*)$/', 'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Code must contain letters, no spaces allowed.')));

        $codeVal = new Zend_Validate_Db_NoRecordExists(array('table' => 'main_pa_rating', 'field' => 'code', 'exclude' => ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5"));
        $codeVal->setMessage('This scale code already exists.');
        $code->addValidator($codeVal);

        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Scale Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100'))
            ->setRequired(true)
            ->addFilter('StringTrim')->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a scale name.'))
            ->addValidator('Regex', true, array('pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/', 'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters.')));

        /* --------------------------------------------------------------
         * TYPE: TEXTAREA (Alphabetical)
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')->addFilter('StripTags');

        /* --------------------------------------------------------------
         * TYPE: CHECKBOX (Alphabetical)
         * -------------------------------------------------------------- */

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')->setCheckedValue('1')->setUncheckedValue('0')->setValue('1');

        /* --------------------------------------------------------------
         * TYPE: SUBMIT (Alphabetical)
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        /* --------------------------------------------------------------
         * BUILD & DECORATE
         * -------------------------------------------------------------- */

        $this->addElements(array(
            $idElement,
            $options_payload, // Hidden
            $data_type,
            $ui_widget,       // Select
            $code,
            $name,                 // Text
            $step_value,                // Text
            $description,                 // Textarea
            $isactive,                    // Checkbox
            $submit                       // Submit
        ));

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
