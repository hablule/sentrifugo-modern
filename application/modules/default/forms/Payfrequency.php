<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/


/**
 * Pay Frequency Form
 *
 * Manages payroll cadence definitions (Fixed Intervals and Calendar Anchors).
 * Complies with SSOT v6.7.7 (Zend_Form, DatePicker, Balanced AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Payfrequency extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'payfrequency');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        // JSON payload container for dynamic anchor days (e.g. [1, 15])
        $calendar_anchors_payload = new Zend_Form_Element_Hidden('calendar_anchors_payload');
        $calendar_anchors_payload->setAttrib('id', 'calendar_anchors_payload');

        /* --------------------------------------------------------------
         * 2. Text Elements (Code & Name)
         * -------------------------------------------------------------- */

        $ssotRegexValidator = [
            'Regex',
            true,
            [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => [
                    Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.'
                ]
            ]
        ];

        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Frequency Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., MTH, BIW, SEMI'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a frequency code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_payfrequency',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $codeVal->setMessage('This frequency code already exists.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Frequency Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Monthly, Bi-Weekly'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a frequency name.'])
            ->addValidator('StringLength', false, ['max' => 100])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_payfrequency',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $nameVal->setMessage('This frequency name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * 3. Cadence Type Router
         * -------------------------------------------------------------- */

        $frequency_type = new Zend_Form_Element_Select('frequency_type');
        $frequency_type->setLabel('Cadence Type')
            ->setAttrib('class', 'formDataElement')
            ->setAttrib('onchange', 'toggleCadenceFields();')
            ->addMultiOptions([
                'FIXED_INTERVAL' => 'Fixed Rolling Interval (e.g., Every 2 Weeks, Every 10 Days)',
                'CALENDAR_BASED' => 'Calendar Anchored (e.g., 1st & 15th, End of Month)'
            ])
            ->setRequired(true)
            ->setValue('FIXED_INTERVAL');

        /* --------------------------------------------------------------
         * 4. Fixed Interval Elements (Dynamic Wrapper 1)
         * -------------------------------------------------------------- */

        $interval_count = new Zend_Form_Element_Text('interval_count');
        $interval_count->setLabel('Interval Step Count')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => 'e.g., 1, 2, 14'])
            ->addFilter('StringTrim')
            ->addValidator('Digits', true, ['messages' => 'Interval count must be a positive whole number.']);

        $interval_unit = new Zend_Form_Element_Select('interval_unit');
        $interval_unit->setLabel('Interval Unit')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions([
                ''      => 'Select Unit...',
                'DAY'   => 'Day(s)',
                'WEEK'  => 'Week(s)',
                'MONTH' => 'Month(s)'
            ]);

        // Weekly Anchor Day (Used if interval_unit = WEEK)
        $weekly_anchor_day = new Zend_Form_Element_Select('weekly_anchor_day');
        $weekly_anchor_day->setLabel('Week Cycle Start Day')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions([
                ''          => 'Select Day...',
                'Monday'    => 'Monday',
                'Tuesday'   => 'Tuesday',
                'Wednesday' => 'Wednesday',
                'Thursday'  => 'Thursday',
                'Friday'    => 'Friday',
                'Saturday'  => 'Saturday',
                'Sunday'    => 'Sunday'
            ]);

        // Monthly Anchor Day (Used if interval_unit = MONTH)
        $monthly_anchor_day = new Zend_Form_Element_Text('monthly_anchor_day');
        $monthly_anchor_day->setLabel('Month Cycle Start Day (1 - 31)')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '2', 'placeholder' => 'e.g., 1 or 26'])
            ->addFilter('StringTrim')
            ->addValidator('Between', true, ['min' => 1, 'max' => 31, 'messages' => 'Day must be between 1 and 31.']);        

        $global_reference_date = new ZendX_JQuery_Form_Element_DatePicker('global_reference_date');
        $global_reference_date->setLabel('Origin Point / Reference Start Date')
            ->setAttribs([
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()',
                'placeholder' => 'Anchor date for rolling cycle'
            ]);

        /* --------------------------------------------------------------
         * 5. Calendar Based Elements (Dynamic Wrapper 2)
         * -------------------------------------------------------------- */

        $missing_date_policy = new Zend_Form_Element_Select('missing_date_policy');
        $missing_date_policy->setLabel('Missing Date Fallback Policy')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions([
                'PREVIOUS_VALID_DAY' => 'Previous Valid Day (Roll back e.g., to Feb 28th)',
                'NEXT_VALID_DAY'     => 'Next Valid Day (Roll forward e.g., to Mar 1st)'
            ])
            ->setValue('PREVIOUS_VALID_DAY');

        /* --------------------------------------------------------------
         * 6. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 7. Checkbox Elements
         * -------------------------------------------------------------- */

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 8. Submit Element
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        $urlAction  = "'payfrequency/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'payfrequency\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $calendar_anchors_payload,
            $code,
            $name,
            $frequency_type,
            $interval_count,
            $interval_unit,
            $weekly_anchor_day,
            $monthly_anchor_day,
            $global_reference_date,
            $missing_date_policy,
            $description,
            $isactive,
            $submit
        ]);

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
