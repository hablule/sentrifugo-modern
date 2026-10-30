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
 * Nationality Context Lookup Form
 * 
 * Manages validation and structure for Nationality Context Code entries.
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
class Default_Form_NationalityContext extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initializes the Nationality Context form elements.
     * 
     * @return void
     */
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'nationalitycontext');

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
        
        // Code (Optional, but highly recommended for standardizing data)
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Context Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., P_CITIZEN, TAX_RES'])
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('Regex', true, [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 _\-]*)$/', // Allowing _ and - for code constants
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
            ]);

        $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_nationality_context',
            'field'   => 'code',
            'exclude' => $excludeClauseCode
        ]);
        $codeVal->setMessage('This context code already exists.');
        $code->addValidator($codeVal);

        // Name (Mandatory)
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Context Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '255', 'placeholder' => 'e.g., Primary Citizenship, Tax Residency'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a context name.'])
            ->addValidator('Regex', true, [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
            ]);

        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_nationality_context',
            'field'   => 'name',
            'exclude' => $excludeClauseName
        ]);
        $nameVal->setMessage('This context name already exists.');
        $name->addValidator($nameVal);

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
        // Ensure this matches the controller mapping URL exactly
        $urlAction  = "'nationalitycontext/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'nationalitycontext\\');'"; 
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */
        
        // Render in logical UI order
        $this->addElements([
            $idElement, $name, $code, $description, $isactive, $submit
        ]);

        // Strip default Zend Decorators and enforce ViewHelper universally
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
