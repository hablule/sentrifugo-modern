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
 * Currency Lookup Form
 * 
 * Manages validation and structure for Currency entries.
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
class Default_Form_Currency extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initializes the Currency form elements.
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
        $this->setAttrib('name', 'currency');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Text Elements (Alphabetical Order)
         * -------------------------------------------------------------- */

        // Currency Code (ISO 4217)
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('ISO Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '3', 'placeholder' => 'e.g., USD, EUR'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper') // ISO codes are always uppercase
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter the 3-letter ISO code.'])
            ->addValidator('Alpha', true, ['messages' => [Zend_Validate_Alpha::NOT_ALPHA => 'The code must contain only letters.']])
            ->addValidator('StringLength', true, [
                'min' => 3,
                'max' => 3,
                'messages' => [
                    Zend_Validate_StringLength::TOO_SHORT => 'The ISO code must be exactly 3 letters.',
                    Zend_Validate_StringLength::TOO_LONG  => 'The ISO code must be exactly 3 letters.'
                ]
            ]);

        $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_currency',
            'field'   => 'code',
            'exclude' => $excludeClauseCode
        ]);
        $codeVal->setMessage('This ISO code already exists.');
        $code->addValidator($codeVal);

        // Currency Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Currency Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., United States Dollar'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a currency name.'])
            ->addValidator('Regex', true, [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
            ]);

        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_currency',
            'field'   => 'name',
            'exclude' => $excludeClauseName
        ]);
        $nameVal->setMessage('This currency name already exists.');
        $name->addValidator($nameVal);

        // Currency Symbol
        $symbol = new Zend_Form_Element_Text('symbol');
        $symbol->setLabel('Symbol')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '10', 'placeholder' => 'e.g., $, €'])
            ->setRequired(false) // Not all currencies have a simple symbol
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

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

        // SSOT AJAX Submission Pattern (Strict Quoting & Escaping)
        $urlAction  = "'currency/save/format/json'";
        $jsFunction = "'redirecttocontroller(\"currency\")'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $code,
            $name,
            $symbol,
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
