<?php

/**
 * Holiday Master Policy Form
 *
 * Manages data entry for core Holiday Policies, Recurrence Rules, and Criteria Payloads.
 * Complies with SSOT v6.7.7 (Zend_Form, Strict Regex, Custom AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Holiday extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'holiday');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        // Hidden JSON Container for the Phase 4B Criteria Engine
        $criteria_payload = new Zend_Form_Element_Hidden('criteria_payload');
        $criteria_payload->setAttrib('id', 'criteria_payload');

        /* --------------------------------------------------------------
         * 2. Text Elements
         * -------------------------------------------------------------- */

        $ssotRegexValidator = array(
            'Regex',
            true,
            array(
                'pattern'  => '/^(?=.*[a-zA-Z0-9])([^ ][a-zA-Z0-9 \-_]*)$/',
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Entry cannot start with a space or contain invalid special characters.'
                )
            )
        );

        // Policy Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Policy Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Thanksgiving Policy'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a policy name.'))
            ->addValidator('StringLength', false, array('max' => 100))
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_holiday',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $nameVal->setMessage('This policy name already exists.');
        $name->addValidator($nameVal);

        // Duration Days (DECIMAL 4,2)
        $duration_days = new Zend_Form_Element_Text('duration_days');
        $duration_days->setLabel('Duration (Days)')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '1.00'))
            ->setRequired(true)
            ->setValue('1.00')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter duration in days.'))
            ->addValidator('Regex', true, array(
                'pattern' => '/^\d+(\.\d{1,2})?$/',
                'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid duration (e.g., 1.00 or 0.50).')
            ));

        // Start Time & End Time (TIME)
        $start_time = new Zend_Form_Element_Text('start_time');
        $start_time->setLabel('Shift Start Time')
            ->setAttribs(array('class' => 'formDataElement timepicker', 'maxlength' => '8', 'placeholder' => '00:00:00'))
            ->setRequired(true)
            ->setValue('00:00:00')
            ->addFilter('StringTrim');

        $end_time = new Zend_Form_Element_Text('end_time');
        $end_time->setLabel('Shift End Time')
            ->setAttribs(array('class' => 'formDataElement timepicker', 'maxlength' => '8', 'placeholder' => '23:59:59'))
            ->setRequired(true)
            ->setValue('23:59:59')
            ->addFilter('StringTrim');

        // Payroll Multiplier (DECIMAL 4,2)
        $payroll_multiplier = new Zend_Form_Element_Text('payroll_multiplier');
        $payroll_multiplier->setLabel('Payroll Multiplier')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '1.00'))
            ->setRequired(true)
            ->setValue('1.00')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a payroll multiplier.'))
            ->addValidator('Regex', true, array(
                'pattern' => '/^\d+(\.\d{1,2})?$/',
                'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid multiplier (e.g., 1.50 or 2.00).')
            ));

        // Priority (INT)
        $priority = new Zend_Form_Element_Text('priority');
        $priority->setLabel('Conflict Priority')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '0'))
            ->setRequired(true)
            ->setValue('0')
            ->addFilter('StringTrim')
            ->addValidator('Digits', false, array('messages' => 'Priority must be a whole number.'));

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        // Category Lookup
        $category_id = new Zend_Form_Element_Select('category_id');
        $category_id->setLabel('Category')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a holiday category.'));

        // Tag Lookup
        $tag_id = new Zend_Form_Element_Select('tag_id');
        $tag_id->setLabel('Reporting Tag / Group')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a holiday tag.'));

        // Operational Work Policy
        $work_policy = new Zend_Form_Element_Select('work_policy');
        $work_policy->setLabel('Operational Policy')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                'OFF_DAY'              => 'Off Day (Facility Closed)',
                'OPTIONAL_WORK'        => 'Optional Work (Floating Holiday)',
                'PREMIUM_WORK'         => 'Premium Work (Standard Operation + Premium Pay)',
                'ESSENTIAL_STAFF_ONLY' => 'Essential Staff Only',
                'REDUCED_HOURS'        => 'Reduced Hours Operation',
                'HALF_DAY'             => 'Half Day Closure'
            ))
            ->setRequired(true)
            ->setValue('OFF_DAY');

        // Weekend Policy
        $weekend_policy = new Zend_Form_Element_Select('weekend_policy');
        $weekend_policy->setLabel('Weekend Collision Rule')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                'FIXED_DATE'            => 'Fixed Date (No Shift)',
                'NEXT_WORKING_DAY'      => 'Shift to Next Working Day',
                'PREVIOUS_WORKING_DAY'  => 'Shift to Previous Working Day',
                'NEXT_REST_DAY'         => 'Shift to Next Rest Day',
                'NO_SUBSTITUTION'       => 'No Substitution / Forfeited'
            ))
            ->setRequired(true)
            ->setValue('FIXED_DATE');

        // Recurrence Type (Phase 4C setup)
        $recurrence_type = new Zend_Form_Element_Select('recurrence_type');
        $recurrence_type->setLabel('Recurrence Engine')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                'MANUAL'        => 'Manual Creation (No Automatic Recurrence)',
                'FIXED_DATE'    => 'Fixed Date (e.g., Every December 25th)',
                'RELATIVE_DATE' => 'Relative Rule (e.g., 4th Thursday in November)'
            ))
            ->setRequired(true)
            ->setValue('MANUAL');

        // Recurrence Details (Months, Days, Weeks)
        $recur_month = new Zend_Form_Element_Select('recur_month');
        $recur_month->setLabel('Recurrence Month')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOption('', 'Select Month...')
            ->addMultiOptions(array(
                '1' => 'January',
                '2' => 'February',
                '3' => 'March',
                '4' => 'April',
                '5' => 'May',
                '6' => 'June',
                '7' => 'July',
                '8' => 'August',
                '9' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December'
            ));

        $recur_day = new Zend_Form_Element_Select('recur_day');
        $recur_day->setLabel('Recurrence Day of Month')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOption('', 'Select Day...');
        for ($i = 1; $i <= 31; $i++) {
            $recur_day->addMultiOption((string)$i, (string)$i);
        }

        $recur_week_num = new Zend_Form_Element_Select('recur_week_num');
        $recur_week_num->setLabel('Ordinal Week')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                ''   => 'Select Week...',
                '1'  => 'First (1st)',
                '2'  => 'Second (2nd)',
                '3'  => 'Third (3rd)',
                '4'  => 'Fourth (4th)',
                '-1' => 'Last'
            ));

        $recur_day_of_week = new Zend_Form_Element_Select('recur_day_of_week');
        $recur_day_of_week->setLabel('Weekday')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                ''  => 'Select Weekday...',
                '1' => 'Monday',
                '2' => 'Tuesday',
                '3' => 'Wednesday',
                '4' => 'Thursday',
                '5' => 'Friday',
                '6' => 'Saturday',
                '7' => 'Sunday'
            ));

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description / Policy Notes')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Checkbox Elements
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

        $auto_generate = new Zend_Form_Element_Checkbox('auto_generate');
        $auto_generate->setLabel('Auto-Generate Occurrences')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 6. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Policy')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $criteria_payload,
            $name,
            $category_id,
            $tag_id,
            $duration_days,
            $start_time,
            $end_time,
            $payroll_multiplier,
            $priority,
            $work_policy,
            $weekend_policy,
            $recurrence_type,
            $recur_month,
            $recur_day,
            $recur_week_num,
            $recur_day_of_week,
            $is_paid,
            $is_mandatory,
            $auto_generate,
            $description,
            $isactive,
            $submit
        ));

        // SSOT v6.7.7: Strip default Zend Decorators
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
