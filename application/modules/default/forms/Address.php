<?php

/**
 * Address Form
 *
 * Manages data entry for core Physical Addresses.
 * Replaces the legacy, separated Location/Address forms.
 * Complies with SSOT v6.6 (Zend_Form, Regex Validation, MultiCheckbox for Mappings).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Address extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'address');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Text Elements (Core Identity)
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
        $code->setLabel('Address Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., HQ-NY-01'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter an address code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_address',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $codeVal->setMessage('This address code already exists.');
        $code->addValidator($codeVal);

        // Name (Label)
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Address Label / Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., New York Headquarters'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter an address label.'])
            ->addValidator('StringLength', false, ['max' => 100])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Address Type
        $address_type_id = new Zend_Form_Element_Select('address_type_id');
        $address_type_id->setLabel('Address Type')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->addValidator('NotEmpty', false, ['messages' => 'Please select an Address Type.']);

        /* --------------------------------------------------------------
         * 3. Text Elements (Physical Street Data)
         * -------------------------------------------------------------- */

        $address_line_1 = new Zend_Form_Element_Text('address_line_1');
        $address_line_1->setLabel('Street Address 1')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '255'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Street Address is required.']);

        $address_line_2 = new Zend_Form_Element_Text('address_line_2');
        $address_line_2->setLabel('Street Address 2')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '255'])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        $address_line_3 = new Zend_Form_Element_Text('address_line_3');
        $address_line_3->setLabel('Street Address 3')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '255'])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 4. Select Elements (Cascading Geography Hierarchy)
         * -------------------------------------------------------------- */

        $country_id = new Zend_Form_Element_Select('country_id');
        $country_id->setLabel('Country')
            ->setAttribs([
                'class'    => 'formDataElement',
                'onchange' => 'getStatesByCountry(this.value);'
            ])
            ->setRequired(true)
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Country.']);

        $state_id = new Zend_Form_Element_Select('state_id');
        $state_id->setLabel('State / Province')
            ->setAttribs([
                'class'    => 'formDataElement',
                'onchange' => 'getCitiesByState(this.value);'
            ])
            ->setRequired(true)
            ->setRegisterInArrayValidator(false) // Populated via AJAX/Controller
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a State.']);

        $city_id = new Zend_Form_Element_Select('city_id');
        $city_id->setLabel('City')
            ->setAttribs([
                'class'    => 'formDataElement',
                'onchange' => 'getPostalCodesByCity(this.value);'
            ])
            ->setRequired(true)
            ->setRegisterInArrayValidator(false) // Populated via AJAX/Controller
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a City.']);

        $postal_code_id = new Zend_Form_Element_Select('postal_code_id');
        $postal_code_id->setLabel('Postal / ZIP Code')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated via AJAX/Controller

        $map_coordinate = new Zend_Form_Element_Text('map_coordinate');
        $map_coordinate->setLabel('GPS Coordinates')
            ->setAttribs(['class' => 'formDataElement', 'placeholder' => 'e.g., 40.7128, -74.0060'])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Multi-Select Elements (Owner Mappings)
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
         * 6. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description / Notes')
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
        $submit->setLabel('Save Address')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // This is a complex form with cascading logic and explicit AJAX submission.
        // The onclick is handled in the save.phtml script, NOT here.

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $code,
            $name,
            $address_type_id,
            $address_line_1,
            $address_line_2,
            $address_line_3,
            $country_id,
            $state_id,
            $city_id,
            $postal_code_id,
            $map_coordinate,

            // Polymorphic Mappings
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
        ]);

        // Strip default Zend Decorators per SSOT
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
