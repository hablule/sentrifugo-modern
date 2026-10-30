<?php

/**
 * Employment Type Form
 * 
 * Manages validation and structure for Employment Status / Contract Types.
 * Strictly adheres to SSOT v6.7.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Sentrifugo AI Co-Developer
 * @version    Sentrifugo v3.2
 */
class Default_Form_Employmenttype extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'employmenttype');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Text Elements
         * -------------------------------------------------------------- */
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., FT, CT, INT'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a code.'])
            ->addValidator('Regex', true, [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \-]*)$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or special characters.']
            ]);

        $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_employment_type',
            'field'   => 'code',
            'exclude' => $excludeClauseCode
        ]);
        $codeVal->setMessage('This code already exists in the system.');
        $code->addValidator($codeVal);

        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Employment Type Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Full-Time Permanent'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a name.'])
            ->addValidator('Regex', true, [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \-]*)$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or special characters.']
            ]);

        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_employment_type',
            'field'   => 'name',
            'exclude' => $excludeClauseName
        ]);
        $nameVal->setMessage('This name already exists in the system.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * Policy Switch Elements (Selects)
         * -------------------------------------------------------------- */
        $options = ['1' => 'Yes', '0' => 'No'];

        $is_benefits_eligible = new Zend_Form_Element_Select('is_benefits_eligible');
        $is_benefits_eligible->setLabel('Eligible for Benefits / Perks?')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions($options)
            ->setRequired(true);

        $is_leave_eligible = new Zend_Form_Element_Select('is_leave_eligible');
        $is_leave_eligible->setLabel('Accrues Paid Leaves?')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions($options)
            ->setRequired(true);

        $is_overtime_eligible = new Zend_Form_Element_Select('is_overtime_eligible');
        $is_overtime_eligible->setLabel('Eligible for Overtime Pay?')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(['0' => 'No', '1' => 'Yes'])
            ->setRequired(true);

        $requires_timesheet = new Zend_Form_Element_Select('requires_timesheet');
        $requires_timesheet->setLabel('Requires Timesheet Submission?')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions($options)
            ->setRequired(true);

        $has_fixed_term = new Zend_Form_Element_Select('has_fixed_term');
        $has_fixed_term->setLabel('Is a Fixed-Term Contract?')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(['0' => 'No (Permanent)', '1' => 'Yes (Has End Date)'])
            ->setRequired(true);

        /* --------------------------------------------------------------
         * Textarea & Active Status
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active Status')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * Submit Element (Strict Quoting)
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        $urlAction  = "'employmenttype/save/format/json'";
        $jsFunction = "'redirecttocontroller(\"employmenttype\")'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        $this->addElements([
            $idElement,
            $code,
            $name,
            $is_benefits_eligible,
            $is_leave_eligible,
            $is_overtime_eligible,
            $requires_timesheet,
            $has_fixed_term,
            $description,
            $isactive,
            $submit
        ]);

        // SSOT UI Enforcement
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
