<?php

/**
 * Business Unit Form
 *
 * Manages data entry for organizational Business Units.
 * Refactored to comply with SSOT v6.4.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Businessunit extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'businessunit');

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

        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '50'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a code.'))
            ->addValidator('StringLength', false, array('max' => 50))
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_businessunit',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $codeVal->setMessage('This code already exists.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '255'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a name.'))
            ->addValidator('StringLength', false, array('max' => 255))
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_businessunit',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $nameVal->setMessage('This name already exists.');
        $name->addValidator($nameVal);

        // Address 1, 2, 3
        $address1 = new Zend_Form_Element_Textarea('address1');
        $address1->setLabel('Address Line 1')
            ->setAttribs(array('class' => 'formDataElement', 'rows' => 2))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        $address2 = new Zend_Form_Element_Textarea('address2');
        $address2->setLabel('Address Line 2')
            ->setAttribs(array('class' => 'formDataElement', 'rows' => 2))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        $address3 = new Zend_Form_Element_Textarea('address3');
        $address3->setLabel('Address Line 3')
            ->setAttribs(array('class' => 'formDataElement', 'rows' => 2))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 3. Select Elements (Context Mappings)
         * -------------------------------------------------------------- */

        $country = new Zend_Form_Element_Select('country');
        $country->setLabel('Country')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated in Controller

        $state = new Zend_Form_Element_Select('state');
        $state->setLabel('State / Province')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated via AJAX/Controller

        $city = new Zend_Form_Element_Select('city');
        $city->setLabel('City')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated via AJAX/Controller

        $timezone = new Zend_Form_Element_Select('timezone');
        $timezone->setLabel('Timezone')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated in Controller

        $unithead_id = new Zend_Form_Element_Select('unithead_id');
        $unithead_id->setLabel('Unit Head')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated in Controller
        
        $company_id = new Zend_Form_Element_Select('company_id');
        $company_id->setLabel('Parent: Company')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated in Controller

        $parent_id = new Zend_Form_Element_Select('parent_id');
        $parent_id->setLabel('Parent: Business Unit')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated in Controller

        /* --------------------------------------------------------------
         * Multi-Select Elements (The Junction Linkers)
         * -------------------------------------------------------------- */        

        // Business Domains
        $businessdomain = new Zend_Form_Element_Multiselect('businessdomain');
        $businessdomain->setLabel('Business Domains')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the domains associated with this business unit.'
            ))
            ->setRegisterInArrayValidator(false) // Required when populating dynamically in the controller
            ->setRequired(false); // A nationality might not have a specific country yet

        /* --------------------------------------------------------------
         * 4. Date Elements
         * -------------------------------------------------------------- */
        $startdate = new ZendX_JQuery_Form_Element_DatePicker('startdate');
        $startdate->setLabel('Start Date')
            ->setAttribs(array(
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ))
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a start date.'));

        /* --------------------------------------------------------------
         * 5. Textarea / Config Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        // Service Desk Flag (1=BU wise, 0=Dept wise)
        $service_desk_flag = new Zend_Form_Element_Select('service_desk_flag');
        $service_desk_flag->setLabel('Service Desk Configuration')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                '1' => 'Business Unit Wise',
                '0' => 'Department Wise'
            ));

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 6. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // SSOT v6.4 Strict Format for AJAX save binding
        $urlAction  = "'businessunit/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'businessunit\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $code,
            $name,
            $startdate,
            $unithead_id,
            $company_id,
            $parent_id,
            $businessdomain,
            $country,
            $state,
            $city,
            $address1,
            $address2,
            $address3,
            $timezone,
            $service_desk_flag,
            $description,
            $isactive,
            $submit
        ));

        // Strip default Zend Decorators per SSOT
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
