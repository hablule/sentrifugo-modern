<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Question Form
 * 
 * Strictly adheres to SSOT v6.2. Implements Multiselect for Skills and disables 
 * restrictive regex on the prompt text to allow natural punctuation.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Paquestion extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * 1. Form Configuration (SSOT VI.5)
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'paquestion');

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

        $category_id = new Zend_Form_Element_Select('category_id');
        $category_id->setLabel('Question Category')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select a Category.'));

        $rating_id = new Zend_Form_Element_Select('rating_id');
        $rating_id->setLabel('Rating Scale / Input Type')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select a Rating Scale.'));

        // Weight (Importance) of the Question
        $weight = new Zend_Form_Element_Text('weight');
        $weight->setLabel('Question Weight (Importance)')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'maxlength' => '5',
                'placeholder' => 'e.g., 1.00 (Default)'
            ))
            ->setRequired(true)
            ->setValue('1.00') // Default weight
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a weight.'))
            ->addValidator('Float', true, array(
                'locale'   => 'en_US',
                'messages' => array(Zend_Validate_Float::NOT_FLOAT => 'Weight must be a valid number.')
            ))
            ->addValidator('Between', true, array(
                'min' => 0.1,
                'max' => 10.0,
                'messages' => array(
                    Zend_Validate_Between::NOT_BETWEEN => 'Weight must be between 0.1 and 10.0.'
                )
            ));

        // SSOT VIII.5: Multiselect Field Mapping
        $skill_ids = new Zend_Form_Element_Multiselect('skill_ids');
        $skill_ids->setLabel('Associated Skills (Optional)')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5,
                'title' => 'Select one or more Applicable Skills.'
            ))
            ->setRegisterInArrayValidator(false) // Required for dynamically injected UI options
            ->setRequired(false);

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */

        // Question Prompt (Note: Omitted strict alphanumeric regex to allow ?, ", ! etc.)
        $name = new Zend_Form_Element_Textarea('name');
        $name->setLabel('Question Prompt')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 3, 'placeholder' => 'e.g., How effectively does this employee communicate?'))
            ->setRequired(true)
            ->addFilter('StringTrim')->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter the question text.'));

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Help Text (Optional)')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 2, 'placeholder' => 'Additional context for the evaluator.'))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

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
            // CORRECTED: Remove onclick for custom JS handler in view
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */

        $this->addElements(array(
            $idElement,
            $category_id,
            $rating_id,
            $weight,
            $skill_ids,
            $name,
            $description,
            $isactive,
            $submit
        ));

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
