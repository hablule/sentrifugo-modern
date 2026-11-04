<?php

/**
 * Country-Nationality Mapping Form
 * 
 * Manages the UI structure for mapping Geographical Countries (Pillar 1) 
 * to Legal Nationalities (Pillar 2).
 * Strictly adheres to SSOT v6.1: DB Schema parity, dynamic dropdown population, 
 * decorator clearing, and standard AJAX submission handlers.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_CountryNationality extends Zend_Form
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
        $this->setAttrib('name', 'countrynationality');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Select Elements (The Junction Linkers)
         * -------------------------------------------------------------- */

        // Country ID (Geography Pillar)
        $country_id = new Zend_Form_Element_Select('country_id');
        $country_id->setLabel('Country (Geography)')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Country.']);

        // Nationality ID (Jurisprudence Pillar)
        $nationality_id = new Zend_Form_Element_Select('nationality_id');
        $nationality_id->setLabel('Nationality (Legal Status)')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Legal Status/Nationality.']);

        // Primary Checkbox (Is this the default nationality for this country?)
        $is_default = new Zend_Form_Element_Checkbox('is_default');
        $is_default->setLabel('Set as Primary Nationality for this Country')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('0'); // Default to No

        /* --------------------------------------------------------------
         * Textarea Elements
         * -------------------------------------------------------------- */

        // Description
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Mapping Notes / Context')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
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
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // SSOT AJAX Submission Pattern (Strict Quoting)
        $urlAction  = "'countrynationality/save/format/json'";
        $jsFunction = "'redirecttocontroller(\"countrynationality\")'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $country_id,
            $nationality_id,
            $is_default,
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
