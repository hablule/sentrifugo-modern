<?php

/**
 * Country Lookup Form
 * 
 * Manages validation and structure for geographical Country entries.
 * Strictly adheres to SSOT v6.1: DB Schema parity, Regex validation, 
 * strict ISO formatting, decorator clearing, and standard AJAX handlers.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Country extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initializes the Country form elements.
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
        $this->setAttrib('name', 'country');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Text Elements
         * -------------------------------------------------------------- */

        // Country Name (Geography)
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Country Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., United States, Great Britain'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a country name.'])
            ->addValidator('Regex', true, [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \-]*)$/', // Allowed hyphens for countries like Guinea-Bissau
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
            ]);

        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_country',
            'field'   => 'name',
            'exclude' => $excludeClauseName
        ]);
        $nameVal->setMessage('This country name already exists.');
        $name->addValidator($nameVal);

        // ISO Alpha-2 Code
        $iso_alpha2 = new Zend_Form_Element_Text('iso_alpha2');
        $iso_alpha2->setLabel('ISO Alpha-2 Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '2', 'placeholder' => 'e.g., US, GB'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper') // Force uppercase
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter the 2-letter ISO code.'])
            ->addValidator('Alpha', true, ['messages' => [Zend_Validate_Alpha::NOT_ALPHA => 'The code must contain only letters.']])
            ->addValidator('StringLength', true, [
                'min' => 2,
                'max' => 2,
                'messages' => [
                    Zend_Validate_StringLength::TOO_SHORT => 'The Alpha-2 code must be exactly 2 letters.',
                    Zend_Validate_StringLength::TOO_LONG  => 'The Alpha-2 code must be exactly 2 letters.'
                ]
            ]);

        $excludeClauseIso2 = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $iso2Val = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_country',
            'field'   => 'iso_alpha2',
            'exclude' => $excludeClauseIso2
        ]);
        $iso2Val->setMessage('This Alpha-2 code already exists in the system.');
        $iso_alpha2->addValidator($iso2Val);

        // ISO Alpha-3 Code
        $iso_alpha3 = new Zend_Form_Element_Text('iso_alpha3');
        $iso_alpha3->setLabel('ISO Alpha-3 Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '3', 'placeholder' => 'e.g., USA, GBR'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper') // Force uppercase
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter the 3-letter ISO code.'])
            ->addValidator('Alpha', true, ['messages' => [Zend_Validate_Alpha::NOT_ALPHA => 'The code must contain only letters.']])
            ->addValidator('StringLength', true, [
                'min' => 3,
                'max' => 3,
                'messages' => [
                    Zend_Validate_StringLength::TOO_SHORT => 'The Alpha-3 code must be exactly 3 letters.',
                    Zend_Validate_StringLength::TOO_LONG  => 'The Alpha-3 code must be exactly 3 letters.'
                ]
            ]);

        $excludeClauseIso3 = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $iso3Val = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_country',
            'field'   => 'iso_alpha3',
            'exclude' => $excludeClauseIso3
        ]);
        $iso3Val->setMessage('This Alpha-3 code already exists in the system.');
        $iso_alpha3->addValidator($iso3Val);

        // Phone Dialing Code
        $phone_code = new Zend_Form_Element_Text('phone_code');
        $phone_code->setLabel('Dialing Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '10', 'placeholder' => 'e.g., +1, +44'])
            ->setRequired(false) // Optional
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('Regex', true, [
                'pattern'  => '/^\+?[0-9\s\-]+$/', // Allows optional +, numbers, spaces, and hyphens
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid international dialing code (e.g., +1).']
            ]);

        /* --------------------------------------------------------------
         * Multi-Select Elements (The Junction Linkers)
         * -------------------------------------------------------------- */

        // Mapped Nationalities (Jurisprudence Pillar)
        $mapped_nationalities = new Zend_Form_Element_Multiselect('mapped_nationalities');
        $mapped_nationalities->setLabel('Associated Nationalities (Legal Statuses)')
            ->setAttribs([
                'class' => 'formDataElement',
                'size'  => 5,
                'title' => 'Select the legal statuses associated with this physical country. The first nationality selected will be marked as Primary.'
            ])
            ->setRegisterInArrayValidator(false) // Required when populating dynamically in the controller
            ->setRequired(false);

        // Mapped Geography Groups
        $geographygroup_id = new Zend_Form_Element_Multiselect('geographygroup_id');
        $geographygroup_id->setLabel('Geography Groups (Clusters)')
            ->setAttribs([
                'class' => 'formDataElement',
                'size'  => 5,
                'title' => 'Select the policy groups this country belongs to (e.g., North America, EMEA).'
            ])
            ->setRegisterInArrayValidator(false)
            ->setRequired(false);

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

        // IsActive (State Mapping)
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
        $urlAction  = "'country/save/format/json'";
        $jsFunction = "'redirecttocontroller(\"country\")'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $name,
            $iso_alpha2,
            $iso_alpha3,
            $phone_code,
            $mapped_nationalities,
            $geographygroup_id,
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
