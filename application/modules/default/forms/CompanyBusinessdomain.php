<?php

/**
 * Company Business Domain Form
 *
 * Manages the mapping interface between Companies and Business Domains.
 * Refactored to comply with SSOT v6.4 (Zend_Form, Composite PK Handling).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_CompanyBusinessdomain extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'companybusinessdomain');

        $request = Zend_Controller_Front::getInstance()->getRequest();

        // This is a composite ID string (e.g., "1_5")
        $idVal   = (string)$request->getParam('id', '');

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Select Elements (Core Mapping Keys)
         * -------------------------------------------------------------- */

        $company_id = new Zend_Form_Element_Select('company_id');
        $company_id->setLabel('Company')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Options populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Company.']);

        // If editing an existing mapping, we freeze the primary keys
        if (!empty($idVal)) {
            $company_id->setAttrib('disabled', 'disabled');
            // Add a hidden field to carry the value through POST since disabled selects aren't posted
            $company_id_hidden = new Zend_Form_Element_Hidden('company_id_hidden');
            $this->addElement($company_id_hidden);
        }

        $businessdomain_id = new Zend_Form_Element_Select('businessdomain_id');
        $businessdomain_id->setLabel('Business Domain')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Options populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Business Domain.']);

        // Freeze primary keys on edit
        if (!empty($idVal)) {
            $businessdomain_id->setAttrib('disabled', 'disabled');
            $businessdomain_id_hidden = new Zend_Form_Element_Hidden('businessdomain_id_hidden');
            $this->addElement($businessdomain_id_hidden);
        }

        /* --------------------------------------------------------------
         * 3. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 4. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 5. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // SSOT v6.4 Strict Format for AJAX save binding
        $urlAction  = "'companybusinessdomain/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'companybusinessdomain\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $company_id,
            $businessdomain_id,
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
