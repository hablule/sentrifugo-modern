<?php

/**
 * City Lookup Form
 * 
 * Manages validation and structure for Geographical City/Municipality entries.
 * Strictly adheres to SSOT v6.1: DB Schema parity, Regex validation, 
 * transient relational dropdowns, decorator clearing, and standard AJAX handlers.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_City extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initializes the City form elements.
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
        $this->setAttrib('name', 'city');

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

        // Country ID (Transient Field: Not saved to main_city, but required to filter States)
        $country_id = new Zend_Form_Element_Select('country_id');
        $country_id->setLabel('Country')
            ->setAttribs(array(
                'class'    => 'formDataElement',
                // Important: Add a trigger to fetch States via AJAX when changed
                'onchange' => 'getStatesByCountry(this.value)'
            ))
            ->setRegisterInArrayValidator(false) // Dynamic population in controller
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select a Country.'));

        // State ID (Foreign Key)
        $state_id = new Zend_Form_Element_Select('state_id');
        $state_id->setLabel('State/Province')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Dynamic population in controller or via AJAX
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select a State/Province.'));

        /* --------------------------------------------------------------
         * Text Elements (Alphabetical Order)
         * -------------------------------------------------------------- */

        // City Code (Optional, e.g., Airport Code or Zip Prefix)
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('City Code / Abbreviation')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., LAX, NYC'))
            ->setRequired(false) // Not all cities have standard codes
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('Regex', true, array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \-]*)$/',
                'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.')
            ));

        // City Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('City Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Los Angeles, Toronto'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a city name.'))
            ->addValidator('Regex', true, array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \-\'\.]*)$/', // Allowed hyphens, apostrophes, and periods (e.g., St. John's, Winston-Salem)
                'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.')
            ));

        // Note: Duplicate checking for City is complex because "Springfield" can exist 
        // in multiple states. We do NOT use Zend_Validate_Db_NoRecordExists here.
        // The Controller's saveAction will manually check checkDuplicateCity($stateId, $name).

        /* --------------------------------------------------------------
         * Multi-Select Elements (The Junction Linkers)
         * -------------------------------------------------------------- */

        // Mapped Geography Groups (Polymorphic Junction)
        $geographygroup_id = new Zend_Form_Element_Multiselect('geographygroup_id');
        $geographygroup_id->setLabel('Geography Groups (Clusters)')
            ->setAttribs(array(
                'class' => 'formDataElement', 
                'size'  => 5, 
                'title' => 'Select the policy groups this country belongs to (e.g., North America, EMEA).'
            ))
            ->setRegisterInArrayValidator(false) 
            ->setRequired(false);

        /* --------------------------------------------------------------
         * Textarea Elements
         * -------------------------------------------------------------- */

        // Description (Optional)
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
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
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // SSOT AJAX Submission Pattern (Strict Quoting & Escaping)
        $urlAction  = "'city/save/format/json'";
        $jsFunction = "'redirecttocontroller(\"city\")'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

        $this->addElements(array(
            $idElement,
            $country_id,
            $state_id,
            $name,
            $code,
            $geographygroup_id,
            $description,
            $isactive,
            $submit
        ));

        // Strip default Zend Decorators and enforce ViewHelper universally
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
