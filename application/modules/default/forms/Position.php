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
 * Position Form (The Structural Seat)
 *
 * Manages position codes, blueprint linkages (Job Title), FTE allocation,
 * and the Single Organizational Unit owner enforcement.
 * Complies with SSOT v6.7.7.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Position extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'position');

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

        $codeRegex = [
            'Regex',
            true,
            [
                'pattern'  => '/^[a-zA-Z0-9_\-]+$/',
                'messages' => [
                    Zend_Validate_Regex::NOT_MATCH => 'Code must contain only letters, numbers, hyphens, or underscores.'
                ]
            ]
        ];

        // Position Code (e.g., POS-ENG-001)
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Position Seat Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., POS-ENG-001'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a position code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($codeRegex[0], $codeRegex[1], $codeRegex[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_position',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $codeVal->setMessage('This position code is already assigned to another seat.');
        $code->addValidator($codeVal);

        // Position Name (Optional Override)
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Position Name (Optional Override)')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'Leave blank to inherit Job Title name'])
            ->setRequired(false) // Crucial: Must be false
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('StringLength', false, ['max' => 100])
            ->addValidator('Regex', true, [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \-_]*)$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Name must contain letters and cannot start with a space.']
            ]);

        // Max FTE (DECIMAL 4,2)
        $max_fte = new Zend_Form_Element_Text('max_fte');
        $max_fte->setLabel('Budgeted Headcount (FTE)')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '5', 'placeholder' => '1.00'])
            ->setRequired(true)
            ->setValue('1.00')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter max FTE.'])
            ->addValidator('Regex', true, [
                'pattern' => '/^\d+(\.\d{1,2})?$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid FTE (e.g., 1.00 or 0.50).']
            ]);

        /* --------------------------------------------------------------
         * 3. Select Elements (Blueprint & Status)
         * -------------------------------------------------------------- */

        // Job Title Blueprint Link
        $jobtitle_id = new Zend_Form_Element_Select('jobtitle_id');
        $jobtitle_id->setLabel('Job Title (Blueprint)')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select the Job Title blueprint for this seat.']);

        // Position Status
        $status = new Zend_Form_Element_Select('status');
        $status->setLabel('Position Status')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions([
                'Proposed'   => 'Proposed (Pending Approval / Budget)',
                'Active'     => 'Active (Available for Recruitment / Assignment)',
                'Frozen'     => 'Frozen (Hiring Suspended)',
                'Eliminated' => 'Eliminated (Retired Headcount)'
            ])
            ->setRequired(true)
            ->setValue('Proposed');

        /* --------------------------------------------------------------
         * New: Reporting Structure
         * -------------------------------------------------------------- */
        $reports_to_position_id = new Zend_Form_Element_Select('reports_to_position_id');
        $reports_to_position_id->setLabel('Reports To (Parent Position)')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated in Controller

        /* --------------------------------------------------------------
         * 4. Single Organizational Owner Controls
         * Enforces that the seat belongs to exactly one organizational unit.
         * -------------------------------------------------------------- */

        // Type Switcher (Client-side toggler)
        $org_type = new Zend_Form_Element_Select('org_type');
        $org_type->setLabel('Organizational Owner Level')
            ->setAttrib('class', 'formDataElement')
            ->setAttrib('onchange', 'toggleOrgOwnerDropdowns();')
            ->addMultiOptions([
                ''             => 'Select Unit Level...',
                'Department'   => 'Department',
                'Section'      => 'Section',
                'Team'         => 'Team',
                'Division'     => 'Division',
                'BusinessUnit' => 'Business Unit',
                'Company'      => 'Company (Top-Level)'
            ])
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select the organizational level that owns this seat.']);

        // Specific Organization Unit Dropdowns
        $company_id = new Zend_Form_Element_Select('company_id');
        $company_id->setLabel('Company')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $businessunit_id = new Zend_Form_Element_Select('businessunit_id');
        $businessunit_id->setLabel('Business Unit')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $division_id = new Zend_Form_Element_Select('division_id');
        $division_id->setLabel('Division')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $department_id = new Zend_Form_Element_Select('department_id');
        $department_id->setLabel('Department')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $section_id = new Zend_Form_Element_Select('section_id');
        $section_id->setLabel('Section')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $team_id = new Zend_Form_Element_Select('team_id');
        $team_id->setLabel('Team')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Seat Specific Notes')
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
        $submit->setLabel('Save Position')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        $urlAction  = "'position/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'position\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $code,
            $name,
            $jobtitle_id,
            $reports_to_position_id,
            $status,
            $max_fte,
            $org_type,
            $company_id,
            $businessunit_id,
            $division_id,
            $department_id,
            $section_id,
            $team_id,
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
