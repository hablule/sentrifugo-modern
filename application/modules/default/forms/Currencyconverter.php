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
 * Currency Converter Form
 * 
 * Manages validation and structure for Currency Conversion rates.
 * Strictly adheres to SSOT v6.2: DB Schema parity, stripped decorators,
 * robust Callback validations for cross-field logic, and standard AJAX submission handlers.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Currencyconverter extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration (SSOT VI)
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'currencyconverter');

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

        // Base Currency
        $base_currency_id = new Zend_Form_Element_Select('base_currency_id');
        $base_currency_id->setLabel('Base Currency')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Base Currency.']);
        // Options populated in Controller via getPairs()

        // Target Currency
        $target_currency_id = new Zend_Form_Element_Select('target_currency_id');
        $target_currency_id->setLabel('Target Currency')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Target Currency.']);

        // Cross-Field Validation: Target cannot equal Base
        $target_currency_id->addValidator('Callback', true, [
            'callback' => function ($value, $context) {
                if (empty($value) || empty($context['base_currency_id'])) return true;
                return $value !== $context['base_currency_id'];
            },
            'messages' => [
                Zend_Validate_Callback::INVALID_VALUE => 'Target Currency cannot be the same as Base Currency.'
            ]
        ]);

        /* --------------------------------------------------------------
         * Text / Date Elements
         * -------------------------------------------------------------- */

        // Exchange Rate
        $exchange_rate = new Zend_Form_Element_Text('exchange_rate');
        $exchange_rate->setLabel('Exchange Rate')
            ->setAttribs(['class' => 'formDataElement', 'placeholder' => 'e.g., 1.2500'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter the exchange rate.'])
            ->addValidator('Float', true, [
                'locale'   => 'en_US', // Standardize float parsing
                'messages' => [Zend_Validate_Float::NOT_FLOAT => 'Exchange rate must be a valid number.']
            ]);

        // Start Date
        $start_date = new Zend_Form_Element_Text('start_date');
        $start_date->setLabel('Start Date')
            ->setAttribs(['class' => 'formDataElement', 'readonly' => 'readonly', 'id' => 'start_date'])
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a start date.']);

        // End Date
        $end_date = new Zend_Form_Element_Text('end_date');
        $end_date->setLabel('End Date')
            ->setAttribs(['class' => 'formDataElement', 'readonly' => 'readonly', 'id' => 'end_date'])
            ->setRequired(false);

        // Cross-Field Validation: End Date must be >= Start Date
        $end_date->addValidator('Callback', true, [
            'callback' => function ($value, $context) {
                if (empty($value) || empty($context['start_date'])) return true; // Pass if empty
                // Convert UI format back to timestamps for safe comparison
                $start = strtotime(sapp_Global::change_date($context['start_date'], 'database'));
                $end   = strtotime(sapp_Global::change_date($value, 'database'));
                return $end >= $start;
            },
            'messages' => [
                Zend_Validate_Callback::INVALID_VALUE => 'End Date must be greater than or equal to the Start Date.'
            ]
        ]);

        /* --------------------------------------------------------------
         * Textarea Elements
         * -------------------------------------------------------------- */

        // Description
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
        $urlAction  = "'currencyconverter/save/format/json'";
        $jsFunction = "'redirecttocontroller(\"currencyconverter\")'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $base_currency_id,
            $target_currency_id,
            $exchange_rate,
            $start_date,
            $end_date,
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
