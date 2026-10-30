<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Assessment Form (The Transaction Envelope)
 * 
 * Manages the static header/footer fields of an assessment. 
 * Note: The actual questions are dynamic and rendered directly in the View, 
 * bypassing Zend_Form to accommodate varying data_types and ui_widgets.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Paassessment extends Zend_Form
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
        $this->setAttrib('name', 'paassessment');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 2. Hidden Elements (SSOT VI.6)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        // Security Payload: Ensures the submission matches the intended context
        $contextPayload = new Zend_Form_Element_Hidden('context');
        $contextPayload->setAttrib('id', 'context');

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        // Status Workflow 
        // Note: 'Pending' is the default DB state. 'In Progress' is for saving drafts.
        $status = new Zend_Form_Element_Select('status');
        $status->setLabel('Assessment Status')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addMultiOptions(array(
                'In Progress'  => 'Save as Draft (In Progress)',
                'Submitted'    => 'Submit as Final',
                'Completed'    => 'Mark as Completed (Manager Only)',
                'Acknowledged' => 'Acknowledge (Employee Final Sign-off)'
            ))
            ->addValidator('NotEmpty', true, array('messages' => 'Please select a status.'));

        /* --------------------------------------------------------------
         * 4. Text Elements
         * -------------------------------------------------------------- */

        // Consolidated Score
        // This field is auto-calculated in the backend and should be read-only in the form.
        $consolidated_score = new Zend_Form_Element_Text('consolidated_score');
        $consolidated_score->setLabel('Consolidated Final Score')
            ->setAttribs(array(
                'class' => 'formDataElement', 
                'maxlength' => '6', 
                'placeholder' => 'Auto-calculated...',
                'readonly' => 'readonly', // FIXED: Enforces read-only at the form level
                'style' => 'background-color: #f4f4f4;' // Visual cue that it's locked
            ))
            ->setRequired(false)
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */

        // Description / Closing Remarks
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Final Remarks / Closing Notes')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Checkbox Elements
         * -------------------------------------------------------------- */

        // IsActive (State Mapping)
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active in System')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 7. Submit Elements
         * -------------------------------------------------------------- */

        // SSOT VI.8: Standard Submit Button. NO inline JS onclick attribute.
        // The dynamic form requires custom JS payload packaging in the view.
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */

        $this->addElements(array(
            $idElement,
            $contextPayload,
            $status,
            $consolidated_score,
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
