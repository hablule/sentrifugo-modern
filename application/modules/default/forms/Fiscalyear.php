<?php

/**
 * Fiscal Year Form
 *
 * Manages data entry for Fiscal Year entities.
 * Includes a hidden payload element to capture the dynamic Period grid data.
 * Complies with SSOT v6.8 (Zend_Form, Regex Validation, JSON Payload).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Fiscalyear extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'fiscalyear');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        // SSOT v6.8 The Merger: JSON Payload Container for Periods
        $periods_payload = new Zend_Form_Element_Hidden('periods_payload');
        $periods_payload->setAttrib('id', 'periods_payload');
        // We do not add a strict 'Required' validator here because the user might
        // legitimately save a year without any periods (if they plan to add them later).

        /* --------------------------------------------------------------
         * 2. Text Elements
         * -------------------------------------------------------------- */

        $ssotRegexValidator = [
            'Regex',
            true,
            [
                'pattern'  => '/^(?=.*[a-zA-Z0-9])([^ ][a-zA-Z0-9 \-]*)$/',
                'messages' => [
                    Zend_Validate_Regex::NOT_MATCH => 'Entry cannot start with a space or contain invalid special characters.'
                ]
            ]
        ];

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Fiscal Year Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '30', 'placeholder' => 'e.g., FY 2026'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a name for the fiscal year.'])
            ->addValidator('StringLength', false, ['max' => 30])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_fiscalyear',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $nameVal->setMessage('This fiscal year name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        $calendar_id = new Zend_Form_Element_Select('calendar_id');
        $calendar_id->setLabel('Parent Calendar')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select the parent Calendar.']);

       // Freeze primary parent ID on edit
        if ($idVal > 0) {
            $calendar_id->setAttrib('disabled', 'disabled');
        }

        /* --------------------------------------------------------------
         * 4. Date Elements
         * -------------------------------------------------------------- */

        $start_date = new ZendX_JQuery_Form_Element_DatePicker('start_date');
        $start_date->setLabel('Year Start Date')
            ->setAttribs([
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ])
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Start Date.']);

        $end_date = new ZendX_JQuery_Form_Element_DatePicker('end_date');
        $end_date->setLabel('Year End Date')
            ->setAttribs([
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ])
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select an End Date.']);

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 7. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Fiscal Year & Periods')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // Complex Form: We omit onclick and handle via explicit $.ajax in save.phtml 
        // to properly handle the virtual grid payload compilation.

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $periods_payload, // Hidden JSON container
            $name,
            $calendar_id,
            $start_date,
            $end_date,
            $description,
            $isactive,
            $submit
        ]);

        // Strip default Zend Decorators per SSOT
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
