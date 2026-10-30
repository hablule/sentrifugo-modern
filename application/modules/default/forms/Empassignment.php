<?php

/**
 * Employee Assignment Form
 *
 * Manages data entry for seat assignments, contract types, FTE allocations, and effective dates.
 * Complies with SSOT v6.7.7 (Zend_Form, DatePickers, Balanced AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Empassignment extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'empassignment');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Select Elements (Core Lookups & Context)
         * -------------------------------------------------------------- */

        // Employee Selection
        $employee_id = new Zend_Form_Element_Select('employee_id');
        $employee_id->setLabel('Employee')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select an employee.'));

        // Freeze employee dropdown on edit to prevent accidental human re-assignment
        if ($idVal > 0) {
            $employee_id->setAttrib('disabled', 'disabled');
            $employee_id_hidden = new Zend_Form_Element_Hidden('employee_id_hidden');
            $this->addElement($employee_id_hidden);
        }

        // Position (The Structural Seat)
        $position_id = new Zend_Form_Element_Select('position_id');
        $position_id->setLabel('Position (Seat)')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a position seat.'));

        // Assignment Type
        $assignment_type = new Zend_Form_Element_Select('assignment_type');
        $assignment_type->setLabel('Assignment Type')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                'Primary'    => 'Primary Role (Full Time / Main Seat)',
                'Secondary'  => 'Secondary Role (Concurrent / Part-Time)',
                'Acting'     => 'Acting / Interim Role',
                'Secondment' => 'Secondment (Temporary Loan)'
            ))
            ->setRequired(true)
            ->setValue('Primary');

        // Employment Type (Contractual Reality)
        $employment_type_id = new Zend_Form_Element_Select('employment_type_id');
        $employment_type_id->setLabel('Employment Contract Type')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select an employment contract type.'));

        // Assignment Reason (Vocabulary of Change)
        $assignment_reason_id = new Zend_Form_Element_Select('assignment_reason_id');
        $assignment_reason_id->setLabel('Assignment Event Reason')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select the reason for this assignment.'));

        /* --------------------------------------------------------------
         * 3. Text Elements (FTE Math)
         * -------------------------------------------------------------- */

        $allocated_fte = new Zend_Form_Element_Text('allocated_fte');
        $allocated_fte->setLabel('Allocated Headcount (FTE)')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '1.00'))
            ->setRequired(true)
            ->setValue('1.00')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter allocated FTE.'))
            ->addValidator('Regex', true, array(
                'pattern'  => '/^\d+(\.\d{1,2})?$/',
                'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid FTE decimal (e.g., 1.00 or 0.50).')
            ));

        /* --------------------------------------------------------------
         * 4. Date Elements (Effective Boundaries)
         * -------------------------------------------------------------- */

        $start_date = new ZendX_JQuery_Form_Element_DatePicker('start_date');
        $start_date->setLabel('Effective Start Date')
            ->setAttribs(array(
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ))
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select the effective start date.'));

        $end_date = new ZendX_JQuery_Form_Element_DatePicker('end_date');
        $end_date->setLabel('Effective End Date (Optional)')
            ->setAttribs(array(
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ));
        // Note: end_date is optional. NULL indicates an active, ongoing assignment.

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Assignment Terms / Notes')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Checkbox Elements
         * -------------------------------------------------------------- */

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active Record')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 7. Submit Element
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Assignment')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // SSOT VI.9: Strict String Quoting for standard AJAX saveDetails()
        $urlAction  = "'empassignment/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'empassignment\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $employee_id,
            $position_id,
            $assignment_type,
            $employment_type_id,
            $assignment_reason_id,
            $allocated_fte,
            $start_date,
            $end_date,
            $description,
            $isactive,
            $submit
        ));

        // SSOT VI.7: Strip default Zend Decorators
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
