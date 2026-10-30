<?php

/**
 * SBU Business Domain Form
 *
 * Manages the mapping interface between Business Units and Business Domains.
 * Refactored to comply with SSOT v6.4 (Zend_Form, Composite PK Handling).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_SbuBusinessdomain extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'sbubusinessdomain');

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

        $sbunit_id = new Zend_Form_Element_Select('sbunit_id');
        $sbunit_id->setLabel('Business Unit')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Options populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a Business Unit.'));

        // If editing an existing mapping, we usually freeze the primary keys
        if (!empty($idVal)) {
            $sbunit_id->setAttrib('disabled', 'disabled');
            // We must add a hidden field to carry the value through POST since disabled selects aren't posted
            $sbunit_id_hidden = new Zend_Form_Element_Hidden('sbunit_id_hidden');
            $this->addElement($sbunit_id_hidden);
        }

        $businessdomain_id = new Zend_Form_Element_Select('businessdomain_id');
        $businessdomain_id->setLabel('Business Domain')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Options populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a Business Domain.'));

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
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
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
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // SSOT v6.4 Strict Format for AJAX save binding
        $urlAction  = "'sbubusinessdomain/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'sbubusinessdomain\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $sbunit_id,
            $businessdomain_id,
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
