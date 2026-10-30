<?php

/**
 * State Lookup Form
 * 
 * Manages validation and structure for Geographical State/Province entries.
 * Strictly adheres to SSOT v6.1: DB Schema parity, Regex validation, 
 * decorator clearing, and standard AJAX submission handlers.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_State extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initializes the State form elements.
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
        $this->setAttrib('name', 'state');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Select Elements
         * -------------------------------------------------------------- */

        // Country ID (Foreign Key)
        $country_id = new Zend_Form_Element_Select('country_id');
        $country_id->setLabel('Country')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Dynamic population in controller
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Country.']);

        /* --------------------------------------------------------------
         * Text Elements (Alphabetical Order)
         * -------------------------------------------------------------- */

        // State/Province Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('State Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., CA, ON'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a state code.'])
            ->addValidator('Regex', true, [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
            ]);

        $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_state',
            'field'   => 'code',
            'exclude' => $excludeClauseCode
        ]);
        $codeVal->setMessage('This state code already exists.');
        $code->addValidator($codeVal);

        // State/Province Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('State/Province Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., California, Ontario'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a state/province name.'])
            ->addValidator('Regex', true, [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
            ]);

        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_state',
            'field'   => 'name',
            'exclude' => $excludeClauseName
        ]);
        $nameVal->setMessage('This state name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * Multi-Select Elements (The Junction Linkers)
         * -------------------------------------------------------------- */

        // Mapped Geography Groups (Polymorphic Junction)
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

        // SSOT AJAX Submission Pattern (Correct Controller Target)
        $urlAction  = "'state/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'state\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $country_id,
            $name,
            $code,
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
