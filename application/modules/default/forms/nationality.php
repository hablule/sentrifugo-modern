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
 * Nationality Lookup Form
 * 
 * Manages validation and structure for Legal Nationality entries.
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
class Default_Form_Nationality extends Zend_Form
{
    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    /**
     * Initializes the Nationality form elements.
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
        $this->setAttrib('name', 'nationality');

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

        // Citizenship Flag (State Mapping for Rights Context)
        $citizenship = new Zend_Form_Element_Select('citizenship');
        $citizenship->setLabel('Confers Citizenship')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                '1' => 'Yes (Grants full rights/passport)',
                '0' => 'No (National or territory status only)'
            ))
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select whether this status confers full citizenship.'));

        /* --------------------------------------------------------------
         * Multi-Select Elements (The Junction Linkers)
         * -------------------------------------------------------------- */

        // Mapped Countries (Geography Pillar)
        $mapped_countries = new Zend_Form_Element_Multiselect('mapped_countries');
        $mapped_countries->setLabel('Associated Countries (Geography)')
            ->setAttribs(array(
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the geographical countries associated with this legal status. The first country selected will be marked as Primary.'
            ))
            ->setRegisterInArrayValidator(false) // Required when populating dynamically in the controller
            ->setRequired(false); // A nationality might not have a specific country yet

        /* --------------------------------------------------------------
         * Text Elements (Alphabetical Order)
         * -------------------------------------------------------------- */

        // Code (ISO Alpha-3 equivalent)
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Nationality Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'ISO Alpha-3 equivalent e.g., USA, GBR'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper') // Force uppercase for standard DB storage
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter the nationality code.'))
            ->addValidator('Regex', true, array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.')
            ));

        $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_nationality',
            'field'   => 'code',
            'exclude' => $excludeClauseCode
        ));
        $codeVal->setMessage('This code already exists in the system.');
        $code->addValidator($codeVal);

        // Name (Legal Entity Name)
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Nationality Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., British Citizen, US National'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a nationality name.'))
            ->addValidator('Regex', true, array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.')
            ));

        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_nationality',
            'field'   => 'name',
            'exclude' => $excludeClauseName
        ));
        $nameVal->setMessage('This nationality name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * Textarea Elements
         * -------------------------------------------------------------- */

        // Description (Optional)
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description / Notes')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
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
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // SSOT AJAX Submission Pattern (Strict Quoting)
        $urlAction  = "'nationality/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'nationality\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */

        $this->addElements(array(
            $idElement,
            $name,
            $code,
            $citizenship,
            $mapped_countries,
            $description,
            $isactive,
            $submit
        ));

        // Strip default Zend Decorators and enforce ViewHelper universally
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
