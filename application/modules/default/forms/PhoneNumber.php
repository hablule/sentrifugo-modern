<?php

/**
 * Phone Number Form
 *
 * Manages data entry for core Phone Number entities and their Owner mappings.
 * Complies with SSOT v6.6 (Zend_Form, Regex Validation, MultiCheckbox for Mappings).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Phonenumber extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'phonenumber');

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

        // Phone Number
        $number = new Zend_Form_Element_Text('number');
        $number->setLabel('Phone Number')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '50', 'placeholder' => 'e.g., +1 (800) 555-1234'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a phone number.'))
            ->addValidator('StringLength', false, array('max' => 50));

        $numberVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_phone_number',
            'field'   => 'number',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $numberVal->setMessage('This exact phone number already exists in the directory.');
        $number->addValidator($numberVal);

        /* --------------------------------------------------------------
         * 3. Select Elements (Classifiers)
         * -------------------------------------------------------------- */

        $type_id = new Zend_Form_Element_Select('type_id');
        $type_id->setLabel('Type')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a Phone Type.'));

        $category_id = new Zend_Form_Element_Select('category_id');
        $category_id->setLabel('Category')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a Phone Category.'));

        /* --------------------------------------------------------------
         * 4. Multi-Select Elements (Owner Mappings)
         * -------------------------------------------------------------- */

        $owners_company = new Zend_Form_Element_Multiselect('owners_company');
        $owners_company->setLabel('Assigned Companies')
            ->setAttrib('class', 'formDataElement multicheckbox')
            ->setRegisterInArrayValidator(false);

        $owners_sbunit = new Zend_Form_Element_Multiselect('owners_sbunit');
        $owners_sbunit->setLabel('Assigned Business Units')
            ->setAttrib('class', 'formDataElement multicheckbox')
            ->setRegisterInArrayValidator(false);

        $owners_division = new Zend_Form_Element_Multiselect('owners_division');
        $owners_division->setLabel('Assigned Divisions')
            ->setAttrib('class', 'formDataElement multicheckbox')
            ->setRegisterInArrayValidator(false);

        $owners_department = new Zend_Form_Element_Multiselect('owners_department');
        $owners_department->setLabel('Assigned Departments')
            ->setAttrib('class', 'formDataElement multicheckbox')
            ->setRegisterInArrayValidator(false);

        $owners_section = new Zend_Form_Element_Multiselect('owners_section');
        $owners_section->setLabel('Assigned Sections')
            ->setAttrib('class', 'formDataElement multicheckbox')
            ->setRegisterInArrayValidator(false);

        $owners_team = new Zend_Form_Element_Multiselect('owners_team');
        $owners_team->setLabel('Assigned Teams')
            ->setAttrib('class', 'formDataElement multicheckbox')
            ->setRegisterInArrayValidator(false);

        $owners_employee = new Zend_Form_Element_Multiselect('owners_employee');
        $owners_employee->setLabel('Assigned Employees')
            ->setAttrib('class', 'formDataElement multicheckbox')
            ->setRegisterInArrayValidator(false);

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
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
        $submit->setLabel('Save')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // This is a complex form with custom JS validation for mappings, so the onclick is handled
        // in the save.phtml script, NOT here.

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $number,
            $type_id,
            $category_id,
            $owners_company,
            $owners_sbunit,
            $owners_division,
            $owners_department,
            $owners_section,
            $owners_team,
            $owners_employee,
            $description,
            $isactive,
            $submit
        ));

        // Strip default Zend Decorators per SSOT
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
