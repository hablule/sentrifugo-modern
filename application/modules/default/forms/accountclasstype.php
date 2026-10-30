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
 * Account Class Type Form
 *
 * Manages the lookup data entry for Account Class Types.
 * Refactored to comply with SSOT v6.3.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Accountclasstype extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'accountclasstype');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Text Elements
         * -------------------------------------------------------------- */
        
        $ssotRegexValidator = array(
            'Regex', true, array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.'
                )
            )
        );

        $lengthValidatorCode = array(
            'StringLength', false, array(
                'max' => 60,
                'messages' => array(
                    Zend_Validate_StringLength::TOO_LONG  => 'Entry must contain at most %max% characters.'
                )
            )
        );

        $lengthValidatorName = array(
            'StringLength', false, array(
                'max' => 255,
                'messages' => array(
                    Zend_Validate_StringLength::TOO_LONG  => 'Entry must contain at most %max% characters.'
                )
            )
        );

        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a code.'))
            ->addValidator($lengthValidatorCode[0], $lengthValidatorCode[1], $lengthValidatorCode[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_accountclasstype',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $codeVal->setMessage('This code already exists.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '255'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a name.'))
            ->addValidator($lengthValidatorName[0], $lengthValidatorName[1], $lengthValidatorName[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_accountclasstype',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $nameVal->setMessage('This name already exists.');
        $name->addValidator($nameVal);

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
        $urlAction  = "'accountclasstype/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'accountclasstype\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $code,
            $name,
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