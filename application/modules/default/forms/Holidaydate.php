<?php

/**
 * Holiday Date Form (Occurrence Header)
 *
 * Manages data entry and manual overrides for holiday occurrence blocks.
 * Captures the immutable snapshot fields (work policy, payroll multipliers, time boundaries).
 * Complies with SSOT v6.7.7 (Zend_Form, DatePickers, Balanced AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Holidaydate extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'holidaydate');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Select Elements
         * -------------------------------------------------------------- */

        // Parent Master Policy Lookup
        $holiday_id = new Zend_Form_Element_Select('holiday_id');
        $holiday_id->setLabel('Holiday Master Policy')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a parent holiday policy.']);

        // Freeze master holiday ID on edit to protect relational consistency
        if ($idVal > 0) {
            $holiday_id->setAttrib('disabled', 'disabled');
            $holiday_id_hidden = new Zend_Form_Element_Hidden('holiday_id_hidden');
            $this->addElement($holiday_id_hidden);
        }

        // Operational Work Policy (Snapshot)
        $work_policy = new Zend_Form_Element_Select('work_policy');
        $work_policy->setLabel('Operational Policy')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions([
                'OFF_DAY'              => 'Off Day (Facility Closed)',
                'OPTIONAL_WORK'        => 'Optional Work (Floating Holiday)',
                'PREMIUM_WORK'         => 'Premium Work (Standard Operation + Premium Pay)',
                'ESSENTIAL_STAFF_ONLY' => 'Essential Staff Only',
                'REDUCED_HOURS'        => 'Reduced Hours Operation',
                'HALF_DAY'             => 'Half Day Closure'
            ])
            ->setRequired(true)
            ->setValue('OFF_DAY');

        // Weekend Policy (Snapshot)
        $weekend_policy = new Zend_Form_Element_Select('weekend_policy');
        $weekend_policy->setLabel('Weekend Collision Rule')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions([
                'FIXED_DATE'            => 'Fixed Date (No Shift)',
                'NEXT_WORKING_DAY'      => 'Shift to Next Working Day',
                'PREVIOUS_WORKING_DAY'  => 'Shift to Previous Working Day',
                'NEXT_REST_DAY'         => 'Shift to Next Rest Day',
                'NO_SUBSTITUTION'       => 'No Substitution / Forfeited'
            ])
            ->setRequired(true)
            ->setValue('FIXED_DATE');

        /* --------------------------------------------------------------
         * 3. Date Elements (Linked Continuous Block)
         * -------------------------------------------------------------- */

        $start_date = new ZendX_JQuery_Form_Element_DatePicker('start_date');
        $start_date->setLabel('Start Date')
            ->setAttribs([
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ])
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Start Date.']);

        $end_date = new ZendX_JQuery_Form_Element_DatePicker('end_date');
        $end_date->setLabel('End Date')
            ->setAttribs([
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ])
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select an End Date.']);

        /* --------------------------------------------------------------
         * 4. Text Elements (Temporal & Financial Snapshots)
         * -------------------------------------------------------------- */

        // Duration Days (DECIMAL 4,2)
        $duration_days = new Zend_Form_Element_Text('duration_days');
        $duration_days->setLabel('Duration (Days)')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '1.00'])
            ->setRequired(true)
            ->setValue('1.00')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter duration in days.'])
            ->addValidator('Regex', true, [
                'pattern' => '/^\d+(\.\d{1,2})?$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid duration (e.g., 1.00 or 0.50).']
            ]);

        // Start Time & End Time (TIME)
        $start_time = new Zend_Form_Element_Text('start_time');
        $start_time->setLabel('Shift Start Time')
            ->setAttribs(['class' => 'formDataElement timepicker', 'maxlength' => '8', 'placeholder' => '00:00:00'])
            ->setRequired(true)
            ->setValue('00:00:00')
            ->addFilter('StringTrim');

        $end_time = new Zend_Form_Element_Text('end_time');
        $end_time->setLabel('Shift End Time')
            ->setAttribs(['class' => 'formDataElement timepicker', 'maxlength' => '8', 'placeholder' => '23:59:59'])
            ->setRequired(true)
            ->setValue('23:59:59')
            ->addFilter('StringTrim');

        // Payroll Multiplier (DECIMAL 4,2)
        $payroll_multiplier = new Zend_Form_Element_Text('payroll_multiplier');
        $payroll_multiplier->setLabel('Payroll Multiplier')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '1.00'])
            ->setRequired(true)
            ->setValue('1.00')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a payroll multiplier.'])
            ->addValidator('Regex', true, [
                'pattern' => '/^\d+(\.\d{1,2})?$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid multiplier (e.g., 1.50 or 2.00).']
            ]);

        // Priority (INT)
        $priority = new Zend_Form_Element_Text('priority');
        $priority->setLabel('Conflict Priority')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '0'])
            ->setRequired(true)
            ->setValue('0')
            ->addFilter('StringTrim')
            ->addValidator('Digits', false, ['messages' => 'Priority must be a whole number.']);

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Instance Notes / Override Reason')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Checkbox Elements
         * -------------------------------------------------------------- */
        $is_paid = new Zend_Form_Element_Checkbox('is_paid');
        $is_paid->setLabel('Paid Holiday')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        $is_mandatory = new Zend_Form_Element_Checkbox('is_mandatory');
        $is_mandatory->setLabel('Mandatory Observance')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 7. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Occurrence')
               ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // SSOT v6.7.7: Strict String Quoting for standard AJAX saveDetails()
        $urlAction  = "'holidaydate/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'holidaydate\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $holiday_id,
            $start_date,
            $end_date,
            $duration_days,
            $start_time,
            $end_time,
            $payroll_multiplier,
            $priority,
            $work_policy,
            $weekend_policy,
            $is_paid,
            $is_mandatory,
            $description,
            $isactive,
            $submit
        ]);

        // SSOT v6.7.7: Strip default Zend Decorators
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            if ($element instanceof ZendX_JQuery_Form_Element_DatePicker) {
                $element->setDecorators(['UiWidgetElement']);
            } else {
                $element->setDecorators(['ViewHelper']);
            }
        }
    }
}