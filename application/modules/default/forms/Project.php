<?php

/**
 * Project Form
 *
 * Manages data entry for Projects.
 * Complies with SSOT v6.5 (Zend_Form, Regex Validation).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Project extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'project');

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

        $ssotRegexValidator = array(
            'Regex',
            true,
            array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.'
                )
            )
        );

        $lengthValidatorCode = array('StringLength', false, array('max' => 60));
        $lengthValidatorName = array('StringLength', false, array('max' => 100));

        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a code.'))
            ->addValidator($lengthValidatorCode[0], $lengthValidatorCode[1], $lengthValidatorCode[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_project',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $codeVal->setMessage('This code already exists.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Project Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a name.'))
            ->addValidator($lengthValidatorName[0], $lengthValidatorName[1], $lengthValidatorName[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_project',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $nameVal->setMessage('This name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        $representative_id = new Zend_Form_Element_Select('representative_id');
        $representative_id->setLabel('Project Manager / Representative')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a Project Manager.'));

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 6: Multi-Checkbox Elements for Participant Mapping
         * -------------------------------------------------------------- */
        
        // Company
        $participants_company = new Zend_Form_Element_Multiselect('participants_company');
        $participants_company->setLabel('Map Entire Company')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the Company associated with this Project.'
            ))
            ->setRegisterInArrayValidator(false);

        // Business Domains
        $participants_businessunit = new Zend_Form_Element_Multiselect('participants_businessunit');
        $participants_businessunit->setLabel('Business Domains')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the SBUs associated with this Project.'
            ))
            ->setRegisterInArrayValidator(false) 
            ->setRequired(false);

        // Division
        $participants_division = new Zend_Form_Element_Multiselect('participants_division');
        $participants_division->setLabel('Map Entire Division')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the Divisions associated with this Project.'
            ))
            ->setRegisterInArrayValidator(false);

        // Departmensts
        $participants_department = new Zend_Form_Element_Multiselect('participants_department');
        $participants_department->setLabel('Map Entire Departments')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the Departments associated with this Project.'
            ))
            ->setRegisterInArrayValidator(false);

        // Section
        $participants_section = new Zend_Form_Element_Multiselect('participants_section');
        $participants_section->setLabel('Map Entire Section')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the Sections associated with this Project.'
            ))
            ->setRegisterInArrayValidator(false);

        // Team
        $participants_team = new Zend_Form_Element_Multiselect('participants_team');
        $participants_team->setLabel('Map Entire Teams')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the Teams associated with this Project.'
            ))
            ->setRegisterInArrayValidator(false);

        // Employees
        $participants_employee = new Zend_Form_Element_Multiselect('participants_employee');
        $participants_employee->setLabel('Map Specific Employees')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the Employees associated with this Project.'
            ))
            ->setRegisterInArrayValidator(false);
        

        /* --------------------------------------------------------------
         * 7. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $code,
            $name,
            $representative_id,
            $description,
            $isactive,
            $participants_company,
            $participants_businessunit,
            $participants_division,
            $participants_department,
            $participants_section,
            $participants_team,
            $participants_employee,
            $submit
        ));

        // Strip default Zend Decorators per SSOT
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
