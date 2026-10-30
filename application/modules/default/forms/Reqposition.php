<?php

/**
 * Requisition Position Form (Child Seat Mapping)
 *
 * Manages the targeting of a specific position seat within a requisition campaign.
 * * Uses prefixed element names (seat_*) to prevent collisions with Master Form fields.
 * Complies with SSOT v6.7.7.
 */

class Default_Form_Reqposition extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'reqposition_formid');
        $this->setAttrib('name', 'reqposition');

        // Prefixed to avoid collision with master form 'id'
        $idElement = new Zend_Form_Element_Hidden('seat_id');

        // Target Position (The Seat)
        $position_id = new Zend_Form_Element_Select('seat_position_id');
        $position_id->setLabel('Target Position Seat')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false)
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a position seat to target.'));

        // Target FTE
        $target_fte = new Zend_Form_Element_Text('seat_target_fte');
        $target_fte->setLabel('Target FTE')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '1.00'))
            ->setRequired(true)
            ->setValue('1.00')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter target FTE.'))
            ->addValidator('Regex', true, array(
                'pattern'  => '/^\d+(\.\d{1,2})?$/',
                'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid FTE decimal.')
            ));

        // Target Hire Date (DatePicker)
        $target_hire_date = new ZendX_JQuery_Form_Element_DatePicker('seat_target_hire_date');
        $target_hire_date->setLabel('Target Onboard Date')
            ->setAttribs(array(
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ))
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a target hire date.'));

        // Prefixed to avoid collision with master form 'status'
        $status = new Zend_Form_Element_Select('seat_pipeline_status');
        $status->setLabel('Initial Pipeline Status')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                'Targeted'   => 'Targeted (Open for Sourcing)',
                'In Process' => 'In Process (Active Interviews)',
                'On Hold'    => 'On Hold'
            ))
            ->setRequired(true)
            ->setValue('Targeted');

        // Prefixed to avoid collision with master form 'description'
        $description = new Zend_Form_Element_Text('seat_description');
        $description->setLabel('Seat Specific Notes')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '255', 'placeholder' => 'e.g., Needs Node.js experience'))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        $this->addElements(array(
            $idElement,
            $position_id,
            $target_fte,
            $target_hire_date,
            $status,
            $description
        ));

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            if ($element instanceof ZendX_JQuery_Form_Element_DatePicker) {
                $element->setDecorators(array('UiWidgetElement'));
            } else {
                $element->setDecorators(array('ViewHelper'));
            }
        }
    }
}
