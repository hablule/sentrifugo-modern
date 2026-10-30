<?php

/**
 * Postal Code Lookup Form
 * 
 * Manages validation and structure for Geographical Postal/ZIP Code entries.
 * Strictly adheres to SSOT v6.1: DB Schema parity, decorator clearing, 
 * standard AJAX handlers, and JS hooks for cascading dropdowns.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Postalcode extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initializes the Postal Code form elements.
     * 
     * @return void
     */
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration (SSOT VI)
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'postalcode');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Select Elements (Relational Cascade)
         * -------------------------------------------------------------- */

        // Country ID (Top-level filter for States)
        $country_id = new Zend_Form_Element_Select('country_id');
        $country_id->setLabel('Country')
            ->setAttribs([
                'class'    => 'formDataElement',
                // Important: Add a trigger to fetch States via AJAX when changed
                'onchange' => 'getStatesByCountry(this.value)'
            ])
            ->setRegisterInArrayValidator(false) // Dynamic population in controller
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Country.']);

        // State ID (Filter for Cities)
        $state_id = new Zend_Form_Element_Select('state_id');
        $state_id->setLabel('State/Province')
            ->setAttribs([
                'class'    => 'formDataElement',
                // Important: Add a trigger to fetch Cities via AJAX when changed
                'onchange' => 'getCitiesByState(this.value)'
            ])
            ->setRegisterInArrayValidator(false) // Dynamic population in controller or via AJAX
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a State/Province.']);

        // City ID (Foreign Key)
        $city_id = new Zend_Form_Element_Select('city_id');
        $city_id->setLabel('City')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Dynamic population in controller or via AJAX
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a City.']);

        /* --------------------------------------------------------------
         * Text Elements
         * -------------------------------------------------------------- */

        // Postal/ZIP Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Postal / ZIP Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '20', 'placeholder' => 'e.g., 90210, SW1A 0AA'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a postal code.']);

        // Note: Duplicate validation for 'code' is composite (city_id + code) and MUST be
        // handled manually in the Controller's saveAction, not via Zend_Validate_Db_NoRecordExists.

        /* --------------------------------------------------------------
         * Textarea Elements
         * -------------------------------------------------------------- */

        // Description (Optional)
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * Checkbox Elements
         * -------------------------------------------------------------- */

        // IsActive
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * Submit Elements
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // SSOT AJAX Submission Pattern (Strict Quoting)
        $urlAction  = "'postalcode/save/format/json'";
        $jsFunction = "'redirecttocontroller(\"postalcode\")'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $country_id,
            $state_id,
            $city_id,
            $code,
            $description,
            $isactive,
            $submit
        ]);

        // Strip default Zend Decorators and enforce ViewHelper universally
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
