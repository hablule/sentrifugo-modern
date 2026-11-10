<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Feedforward Form
 * 
 * Simple generator form. Collects the target employee and the ad-hoc template.
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Pafeedforward extends Zend_Form
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
        $this->setAttrib('name', 'pafeedforward');

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

        // Target Colleague (The Evaluatee)
        // SSOT VI.2: Element name matches the intended column in main_pa_assessment
        $evaluatee_id = new Zend_Form_Element_Select('evaluatee_id');
        $evaluatee_id->setLabel('Select Colleague')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select the person you are giving feedback to.']);

        // Feedback Template (The Ad-hoc Rulebook)
        $rulebook_id = new Zend_Form_Element_Select('rulebook_id');
        $rulebook_id->setLabel('Feedback Template')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a feedback template.']);

        /* --------------------------------------------------------------
         * 4. Submit Elements (SSOT VI.8 & VI.9)
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Start Feedback')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        /* --------------------------------------------------------------
         * 5. Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $evaluatee_id,
            $rulebook_id,
            $submit
        ]);

        // Strip default Zend Decorators and enforce ViewHelper universally
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
