<?php

/**
 * Period Form
 *
 * Manages data entry for specific time boundaries (Periods).
 * Implements the "Merged Form" paradigm for Period Control (Locking/Unlocking).
 * Complies with SSOT v6.8 (Zend_Form, Regex Validation, Custom AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Period extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'period');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Text Elements
         * -------------------------------------------------------------- */

        $ssotRegexValidator = [
            'Regex',
            true,
            [
                'pattern'  => '/^(?=.*[a-zA-Z0-9])([^ ][a-zA-Z0-9 \-:]*)$/',
                'messages' => [
                    Zend_Validate_Regex::NOT_MATCH => 'Entry cannot start with a space or contain invalid special characters.'
                ]
            ]
        ];

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Period Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '30', 'placeholder' => 'e.g., Period 1: Jan 2026'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a name for the period.'])
            ->addValidator('StringLength', false, ['max' => 30])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $period_sequence = new Zend_Form_Element_Text('period_sequence');
        $period_sequence->setLabel('Period Sequence / Order')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '10', 'placeholder' => 'e.g., 1, 2, 3'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a numerical sequence for this period.'])
            ->addValidator('Digits', true, ['messages' => 'Sequence must be a whole number.']);

        /* --------------------------------------------------------------
         * 3. Select Elements (Core)
         * -------------------------------------------------------------- */

        $fiscalyear_id  = new Zend_Form_Element_Select('fiscalyear_id');
        $fiscalyear_id->setLabel('Financial Year')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select the parent Year.']);

        // Freeze primary parent ID on edit to prevent shifting historical periods to other years
        if ($idVal > 0) {
            $fiscalyear_id->setAttrib('disabled', 'disabled');
            $fiscalyear_id_hidden = new Zend_Form_Element_Hidden('fiscalyear_id_hidden');
            $this->addElement($fiscalyear_id_hidden);
        }

        /* --------------------------------------------------------------
         * 4. Date Elements
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
         * 5. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Dynamic Elements (The Period Controls / State Machine)
         * -------------------------------------------------------------- */
        // Note: Because the number of business processes is dynamic (based on main_business_process),
        // we cannot hardcode these elements here. They are dynamically generated and injected 
        // into the Form object within the Controller's _prepareForm() method.

        /* --------------------------------------------------------------
         * 7. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active Period')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 8. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Period')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // Complex Form: We omit onclick and handle via explicit $.ajax in save.phtml 

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $fiscalyear_id,
            $name,
            $period_sequence,
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
