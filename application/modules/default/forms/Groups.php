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
 * Sentrifugo - Open Source HRMS
 *
 * @category   Sentrifugo
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @modified  Sentrifugo v3.2
 * @date	  June 2024
 */
class Default_Form_Groups extends Zend_Form
{
    /**
     * Initialize the Group Form
     */
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'formindex');

        // Request context for unique validation exclusions
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);
        $isTimeActive = $request->getParam('istimeactive');

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */

        // Primary Key (Schema: id)
        $idElement = new Zend_Form_Element_Hidden('id');

        // Privilege Count Check (Sentrifugo Specific Logic)
        $prev_cnt = new Zend_Form_Element_Hidden('prev_cnt');
        $prev_cnt->setRequired(true);

        $privMsg = ($isTimeActive)
            ? 'Please select privileges other than time management.'
            : 'Please select privileges.';

        $prev_cnt->addValidator('NotEmpty', false, array('messages' => $privMsg));

        /* --------------------------------------------------------------
         * 2. Text Elements
         * -------------------------------------------------------------- */

        // Group Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Group Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Name is required.'));

        // Logic for unique check (Exclude current ID if editing)
        $nameValidator = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_group',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $nameValidator->setMessage('This group name already exists.');
        $name->addValidator($nameValidator);

        // Group Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Group Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Code is required.'));

        // Unique Check for Code
        $codeValidator = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_group',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $codeValidator->setMessage('This group code already exists.');
        $code->addValidator($codeValidator);
        
        // Display Order        
        $displayorder = new Zend_Form_Element_Select('displayorder');
        $displayorder->setLabel('Display Order')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true);

        // Populate options 1 to 100
        $orderOptions = array();
        for ($i = 1; $i <= 100; $i++) {
            $orderOptions[$i] = $i;
        }
        $displayorder->addMultiOptions($orderOptions);

        /* --------------------------------------------------------------
         * 3. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array(
                'class' => 'formDataElement fck_editor_height',
                'rows'  => '4'
            ))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 4. Checkbox Elements
         * -------------------------------------------------------------- */

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('Y')
            ->setUncheckedValue('N')
            ->setValue('Y');

        /* --------------------------------------------------------------
         * 5. Submit Elements
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttrib('id', 'submitbutton')
            ->setAttrib('class', 'actbtn_btn')
            ->setLabel('Save');

        // Dynamic JS submission (Corrected for SSOT)
        // 1. Point to correct controller action (groups/save)
        // 2. Pass 'formid' to saveDetails so it can find the form
        // 3. Add 'return false;' to prevent double submission
        $url        = "'groups/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'groups\\');'"; // Escaped quotes for JS string

        $submit->setAttrib('onclick', "saveDetails($url, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */

        $this->addElements(array(
            $idElement,
            $prev_cnt,
            $name,
            $code,
            $displayorder,
            $description,
            $isactive,
            $submit
        ));

        // Set Standard Decorators
        $this->setElementDecorators(array('ViewHelper'));
    }
}
