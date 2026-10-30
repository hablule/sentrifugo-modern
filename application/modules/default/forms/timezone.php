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
 * Timezone Lookup Form
 * 
 * Manages validation and structure for Timezone entries.
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
class Default_Form_Timezone extends Zend_Form
{
    /**
     * Initializes the Timezone form elements.
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
        $this->setAttrib('name', 'timezone');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Text Elements (SSOT VI: DB Schema Parity & Regex Rules)
         * -------------------------------------------------------------- */
        
        // Timezone Name (e.g., America/New_York)
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Timezone Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '255', 'placeholder' => 'e.g., America/New_York'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a timezone name.'))
            // SSOT VI: Mandatory Regex for primary string descriptors
            ->addValidator('Regex', true, array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 _\-\/]*)$/', // Allowed / and _ for standard TZ format
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid timezone name (Cannot start with a space or be purely numeric).'
                )
            ));

        // Strict Check: Ignore current ID, check against all non-deleted records
        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_timezone',
            'field'   => 'name',
            'exclude' => $excludeClauseName
        ));
        $nameVal->setMessage('This timezone name already exists.');
        $name->addValidator($nameVal);

        // Timezone Code / Abbreviation (e.g., EST)
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Abbreviation')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '10', 'placeholder' => 'e.g., EST'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a timezone abbreviation.'))
            ->addValidator('Regex', true, array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Please enter a valid abbreviation (Must contain letters, no leading spaces or special characters).'
                )
            ));

        $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_timezone',
            'field'   => 'code',
            'exclude' => $excludeClauseCode
        ));
        $codeVal->setMessage('This abbreviation already exists.');
        $code->addValidator($codeVal);

        // Offset Value (e.g., UTC -05:00)
        $offset_value = new Zend_Form_Element_Text('offset_value');
        $offset_value->setLabel('UTC Offset')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., UTC -05:00'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a UTC offset value.'));

        /* --------------------------------------------------------------
         * 3. Textarea Element
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 4. Checkbox Element (SSOT II State Mapping)
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 5. Submit Element (SSOT VI Standard AJAX Handler)
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(array(
                'id'    => 'submitbutton',
                'class' => 'actbtn_btn'
            ));

        // SSOT AJAX Submission Pattern (Strict Quoting, Empty 2nd Param, No return false)
        $urlAction  = "'timezone/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'timezone\\')'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI)
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement, $name, $code, $offset_value, $description, $isactive, $submit
        ));

        // Strip default Zend Decorators and enforce ViewHelper universally
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}