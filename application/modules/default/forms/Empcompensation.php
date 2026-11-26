<?php

/**
 * Employee Compensation Form
 *
 * Manages effective-dated compensation profiles (Pay Group, Remuneration Basis, Currency, Base Amount).
 * Complies with SSOT v6.7.7 (Zend_Form, DatePickers, Balanced AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @version    Sentrifugo v3.2
 */
class Default_Form_Empcompensation extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'empcompensation');

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

        // Employee Selection
        $employee_id = new Zend_Form_Element_Select('employee_id');
        $employee_id->setLabel('Employee')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select an employee.'));

        // Freeze employee on Edit
        if ($idVal > 0) {
            $employee_id->setAttrib('disabled', 'disabled');
            $employee_id_hidden = new Zend_Form_Element_Hidden('employee_id_hidden');
            $this->addElement($employee_id_hidden);
        }

        // Pay Group
        $pay_group_id = new Zend_Form_Element_Select('pay_group_id');
        $pay_group_id->setLabel('Pay Group')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false)
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a Pay Group.'));

        // Remuneration Basis
        $remunerationbasis_id = new Zend_Form_Element_Select('remunerationbasis_id');
        $remunerationbasis_id->setLabel('Remuneration Basis')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false)
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a Remuneration Basis.'));

        // Currency
        $currency_id = new Zend_Form_Element_Select('currency_id');
        $currency_id->setLabel('Currency')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false)
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a Currency.'));

        /* --------------------------------------------------------------
         * 3. Text Elements (Base Amount)
         * -------------------------------------------------------------- */

        $base_amount = new Zend_Form_Element_Text('base_amount');
        $base_amount->setLabel('Base Amount / Rate')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '18', 'placeholder' => 'e.g., 75000.00 or 35.50'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter the base compensation amount.'))
            ->addValidator('Regex', true, array(
                'pattern'  => '/^\d+(\.\d{1,2})?$/',
                'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid positive monetary amount (up to 2 decimal places).')
            ))
            ->addValidator('GreaterThan', true, array('min' => 0, 'messages' => 'Base amount must be greater than 0.'));

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

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Compensation Notes / Package Terms')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
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
        $submit->setLabel('Save Compensation')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        $urlAction  = "'empcompensation/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'empcompensation\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $employee_id,
            $pay_group_id,
            $remunerationbasis_id,
            $currency_id,
            $base_amount,
            $start_date,
            $end_date,
            $description,
            $isactive,
            $submit
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
