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
 * Requisition Form (The Merged Campaign Form)
 *
 * Manages the approval request header and encapsulates the child seats payload.
 * Complies with SSOT v6.7.7 (Master-Detail JSON, Explicit AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Requisition extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'requisition');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        // SSOT v6.7.7 The Merger: JSON Container for Child Positions
        $positions_payload = new Zend_Form_Element_Hidden('positions_payload');
        $positions_payload->setAttrib('id', 'positions_payload');

        /* --------------------------------------------------------------
         * 2. Text Elements
         * -------------------------------------------------------------- */

        $codeRegex = [
            'Regex',
            true,
            [
                'pattern'  => '/^[a-zA-Z0-9_\-\/]+$/',
                'messages' => [
                    Zend_Validate_Regex::NOT_MATCH => 'Code must contain only letters, numbers, hyphens, or slashes.'
                ]
            ]
        ];

        // Requisition Code (e.g., REQ-2026-004)
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Requisition Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., REQ-2026-001'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a requisition code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($codeRegex[0], $codeRegex[1], $codeRegex[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_requisition',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $codeVal->setMessage('This requisition code already exists.');
        $code->addValidator($codeVal);

        // Requested Total FTE Budget
        $requested_fte = new Zend_Form_Element_Text('requested_fte');
        $requested_fte->setLabel('Total Budgeted Headcount (FTE)')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '1.00'])
            ->setRequired(true)
            ->setValue('1.00')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter total requested FTE.'])
            ->addValidator('Regex', true, [
                'pattern'  => '/^\d+(\.\d{1,2})?$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid FTE (e.g., 1.00 or 3.50).']
            ]);

        /* --------------------------------------------------------------
         * 3. Select Elements (Business Roles & Campaign Approval)
         * -------------------------------------------------------------- */

        // Hiring Manager (Requested By)
        $requested_by = new Zend_Form_Element_Select('requested_by_employee_id');
        $requested_by->setLabel('Hiring Manager (Requested By)')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select the hiring manager.']);

        // Assigned Recruiter
        $assigned_recruiter = new Zend_Form_Element_Select('assigned_recruiter_id');
        $assigned_recruiter->setLabel('Assigned Recruiter (HR Contact)')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated in Controller

        // Campaign Approval Status
        $status = new Zend_Form_Element_Select('status');
        $status->setLabel('Requisition Status')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions([
                'Draft'            => 'Draft (Building Campaign)',
                'Pending Approval' => 'Pending Approval (Awaiting Sign-Off)',
                'Approved'         => 'Approved (Open for Recruitment)',
                'Rejected'         => 'Rejected (Budget Denied)',
                'Closed'           => 'Closed (Recruitment Complete)'
            ])
            ->setRequired(true)
            ->setValue('Draft');

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Business Justification / Campaign Notes')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Checkbox Elements
         * -------------------------------------------------------------- */

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active Campaign')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 6. Submit Element
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Requisition')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // Submissions handled via explicit AJAX in save.phtml per SSOT VII.10

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $positions_payload,
            $code,
            $requested_by,
            $assigned_recruiter,
            $requested_fte,
            $status,
            $description,
            $isactive,
            $submit
        ]);

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
