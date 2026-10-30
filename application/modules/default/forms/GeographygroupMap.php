<?php

/**
 * Geography Group Mapping Form
 * 
 * Manages validation and structure for mapping specific geographical entities 
 * (Countries, States, Cities, Postal Codes) to a parent Geography Group.
 * Strictly adheres to SSOT v6.1: DB Schema parity, polymorphic routing, 
 * transient relational dropdowns, decorator clearing, and standard AJAX handlers.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_GeographygroupMap extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initializes the Mapping form elements.
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
        $this->setAttrib('name', 'geographygroupmap');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * Hidden Elements
         * -------------------------------------------------------------- */

        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);        

        // The final, polymorphic ID that gets saved to the database.
        // This is populated dynamically via JS based on the lowest level selected.
        $entity_id = new Zend_Form_Element_Hidden('entity_id');

        /* --------------------------------------------------------------
         * Select Elements (The Polymorphic Router & Cascades)
         * -------------------------------------------------------------- */

        // Parent Group Selection
        $geographygroup_id = new Zend_Form_Element_Select('geographygroup_id');
        $geographygroup_id->setLabel('Target Geography Group')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in controller
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select the target Geography Group.'));

        // Entity Type (Determines which dropdowns are shown/required)
        $entity_type = new Zend_Form_Element_Select('entity_type');
        $entity_type->setLabel('Mapping Level')
            ->setAttribs(array(
                'class'    => 'formDataElement',
                'onchange' => 'toggleEntityFields(this.value)'
            ))
            ->addMultiOptions(array(
                ''           => 'Select Level',
                'Country'    => 'Entire Country',
                'State'      => 'Specific State/Province',
                'City'       => 'Specific City',
                'PostalCode' => 'Specific Postal/ZIP Code'
            ))
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select the mapping level.'));

        // Transient: Country ID
        $country_id = new Zend_Form_Element_Select('country_id');
        $country_id->setLabel('Country')
            ->setAttribs(array(
                'class'    => 'formDataElement',
                'onchange' => 'handleEntitySelection("Country", this.value); getStatesByCountry(this.value);'
            ))
            ->setRegisterInArrayValidator(false) // Dynamic population in controller
            ->setRequired(false);

        // Transient: State ID
        $state_id = new Zend_Form_Element_Select('state_id');
        $state_id->setLabel('State/Province')
            ->setAttribs(array(
                'class'    => 'formDataElement',
                'onchange' => 'handleEntitySelection("State", this.value); getCitiesByState(this.value);'
            ))
            ->setRegisterInArrayValidator(false) // Dynamic population in controller or via AJAX
            ->setRequired(false);

        // Transient: City ID
        $city_id = new Zend_Form_Element_Select('city_id');
        $city_id->setLabel('City')
            ->setAttribs(array(
                'class'    => 'formDataElement',
                'onchange' => 'handleEntitySelection("City", this.value); getPostalCodesByCity(this.value);'
            ))
            ->setRegisterInArrayValidator(false) // Dynamic population in controller or via AJAX
            ->setRequired(false);

        // Transient: Postal Code ID
        $postalcode_id = new Zend_Form_Element_Select('postalcode_id');
        $postalcode_id->setLabel('Postal / ZIP Code')
            ->setAttribs(array(
                'class'    => 'formDataElement',
                'onchange' => 'handleEntitySelection("PostalCode", this.value);'
            ))
            ->setRegisterInArrayValidator(false) // Dynamic population in controller or via AJAX
            ->setRequired(false);

        /* --------------------------------------------------------------
         * Textarea Elements
         * -------------------------------------------------------------- */

        // Description (Optional)
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Mapping Notes')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * Checkbox Elements
         * -------------------------------------------------------------- */

        // IsActive
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active Mapping')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * Submit Elements
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Mapping')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // SSOT AJAX Submission Pattern (Strict Quoting & Escaping)
        $urlAction  = "'geographygroupmap/save/format/json'";

        // Note: Upon saving the mapping, we redirect the user back to the Parent Group's view page.
        $parentGroupId = (int)$request->getParam('geographygroup_id', 0);
        $jsFunction = "'window.location.href = base_url + \"/geographygroup/view/id/$parentGroupId\";'";

        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

        $this->addElements(array(
            $idElement,
            $geographygroup_id,
            $entity_id,
            $entity_type,
            $country_id,
            $state_id,
            $city_id,
            $postalcode_id,
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
