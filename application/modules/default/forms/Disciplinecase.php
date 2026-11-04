<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Discipline Case Form
 * 
 * Includes the unmapped `log_comment` field for timeline interaction.
 * Strictly adheres to SSOT v6.2.
 */
class Default_Form_Disciplinecase extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'disciplinecase');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        // Hidden ID
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Core Selects
         * -------------------------------------------------------------- */
        $employee_id = new Zend_Form_Element_Select('employee_id');
        $employee_id->setLabel('Employee Involved')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select an employee.']);

        $violation_id = new Zend_Form_Element_Select('violation_id');
        $violation_id->setLabel('Violation Type')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select the violation type.']);

        $action_id = new Zend_Form_Element_Select('action_id');
        $action_id->setLabel('Corrective Action')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(false);

        /* --------------------------------------------------------------
         * Status & Enums
         * -------------------------------------------------------------- */
        $case_status = new Zend_Form_Element_Select('case_status');
        $case_status->setLabel('Case Status')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(['Initiated' => 'Initiated', 'Appealed' => 'Appealed', 'Closed' => 'Closed'])
            ->setRequired(true);

        $cao_verdict = new Zend_Form_Element_Select('cao_verdict');
        $cao_verdict->setLabel('Verdict')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(['' => 'Pending / TBD', '1' => 'Guilty', '2' => 'Not Guilty'])
            ->setRequired(false);

        $employee_appeal = new Zend_Form_Element_Select('employee_appeal');
        $employee_appeal->setLabel('Employee Appealed?')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(['2' => 'No', '1' => 'Yes'])
            ->setRequired(true);

        /* --------------------------------------------------------------
         * Dates & Text
         * -------------------------------------------------------------- */
        $date_of_occurrence = new Zend_Form_Element_Text('date_of_occurrence');
        $date_of_occurrence->setLabel('Date of Incident')
            ->setAttribs(['class' => 'formDataElement', 'readonly' => 'readonly', 'id' => 'date_of_occurrence'])
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select the incident date.']);

        $violation_expiry = new Zend_Form_Element_Text('violation_expiry');
        $violation_expiry->setLabel('Action Expiry Date')
            ->setAttribs(['class' => 'formDataElement', 'readonly' => 'readonly', 'id' => 'violation_expiry'])
            ->setRequired(false);

        $action_text = new Zend_Form_Element_Text('action_text');
        $action_text->setLabel('Action Details (If Other)')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '255'])
            ->setRequired(false)
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * Statements & Logs
         * -------------------------------------------------------------- */
        $employer_statement = new Zend_Form_Element_Textarea('employer_statement');
        $employer_statement->setLabel('Employer Statement / Findings')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')->addFilter('StripTags');

        $employee_statement = new Zend_Form_Element_Textarea('employee_statement');
        $employee_statement->setLabel('Employee Statement')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')->addFilter('StripTags');

        // UNMAPPED FIELD FOR TIMELINE LOGGING
        $log_comment = new Zend_Form_Element_Textarea('log_comment');
        $log_comment->setLabel('Add Note to Timeline')
            ->setAttribs(['class' => 'formDataElement', 'rows' => 3, 'placeholder' => 'Explain any updates, status changes, or meetings here...'])
            ->setRequired(false)
            ->addFilter('StringTrim')->addFilter('StripTags');

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * Submit
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Case')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        $urlAction  = "'disciplinecase/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'disciplinecase\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        $this->addElements([
            $idElement,
            $employee_id,
            $violation_id,
            $date_of_occurrence,
            $case_status,
            $cao_verdict,
            $action_id,
            $action_text,
            $violation_expiry,
            $employer_statement,
            $employee_appeal,
            $employee_statement,
            $log_comment,
            $isactive,
            $submit
        ]);

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
