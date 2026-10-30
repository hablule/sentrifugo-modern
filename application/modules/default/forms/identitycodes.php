<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Identity Codes Form
 *
 * Manages configuration prefixes/codes.
 * Refactored to comply with SSOT v6.2.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Identitycodes extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'identitycodes');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Text Elements (Mapped precisely to DB Columns)
         * -------------------------------------------------------------- */
        
        $ssotRegexValidator = array(
            'Regex', true, array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.'
                )
            )
        );

        $lengthValidator = array(
            'StringLength', false, array(
                'min' => 1, 'max' => 5,
                'messages' => array(
                    Zend_Validate_StringLength::TOO_LONG  => 'Code must contain at most %max% characters.',
                    Zend_Validate_StringLength::TOO_SHORT => 'Code must contain at least %min% characters.'
                )
            )
        );

        // Background Agency Code
        $backgroundagency_code = new Zend_Form_Element_Text('backgroundagency_code');
        $backgroundagency_code->setLabel('Background Agency Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter background agency code.'))
            ->addValidator($lengthValidator[0], $lengthValidator[1], $lengthValidator[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Employee Code
        $employee_code = new Zend_Form_Element_Text('employee_code');
        $employee_code->setLabel('Employee Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter employee code.'))
            ->addValidator($lengthValidator[0], $lengthValidator[1], $lengthValidator[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Requisition Code
        $requisition_code = new Zend_Form_Element_Text('requisition_code');
        $requisition_code->setLabel('Requisition Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter requisition code.'))
            ->addValidator($lengthValidator[0], $lengthValidator[1], $lengthValidator[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Staffing Code
        $staffing_code = new Zend_Form_Element_Text('staffing_code');
        $staffing_code->setLabel('Staffing Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter staffing code.'))
            ->addValidator($lengthValidator[0], $lengthValidator[1], $lengthValidator[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Users Code
        $users_code = new Zend_Form_Element_Text('users_code');
        $users_code->setLabel('Users Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter users code.'))
            ->addValidator($lengthValidator[0], $lengthValidator[1], $lengthValidator[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Vendors Code
        $vendors_code = new Zend_Form_Element_Text('vendors_code');
        $vendors_code->setLabel('Vendor Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '5'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter vendor code.'))
            ->addValidator($lengthValidator[0], $lengthValidator[1], $lengthValidator[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

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

        // SSOT v6.2 Strict Format for AJAX save binding
        $urlAction  = "'identitycodes/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'identitycodes\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $backgroundagency_code,
            $employee_code,
            $requisition_code,
            $staffing_code,
            $users_code,
            $vendors_code,
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