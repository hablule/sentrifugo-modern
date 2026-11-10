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

class Default_Form_Roles extends Zend_Form
{
    /* --------------------------------------------------------------
     * Methods (Alphabetically Ordered)
     * -------------------------------------------------------------- */

    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'formindex');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $id_val  = (int)$request->getParam('id', 0);
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

        $prev_cnt->addValidator('NotEmpty', false, ['messages' => $privMsg]);


        /* --------------------------------------------------------------
         * 2. Selection Elements
         * -------------------------------------------------------------- */

        // Group Selection.
        $group_id = new Zend_Form_Element_Select('group_id');
        $group_id->setLabel('User Group')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false)
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Group is required.']);

        // Level Selection.
        $levelid = new Zend_Form_Element_Select('levelid');
        $levelid->setLabel('Hierarchy Level')
            ->setAttribs(['class' => 'formDataElement', 'placeholder' => 'e.g. 1'])
            ->setRequired(true)
            ->addValidator('Digits', true, ['messages' => 'Level must be a whole number.'])
            ->addValidator('NotEmpty', true, ['messages' => 'Level is required.']);

        // Populate options 1 to 100
        $orderOptions = [];
        for ($i = 1; $i <= 100; $i++) {
            $orderOptions[$i] = $i;
        }
        $levelid->addMultiOptions($orderOptions);

         /* --------------------------------------------------------------
         * 3. Text Elements
         * -------------------------------------------------------------- */

        // Role Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Role Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Code is required.'])
            ->addValidator('Db_NoRecordExists', true, [
                'messages' => 'This code already exists.',
                'table' => 'main_role',
                'field' => 'code',
                'exclude' => ($id_val > 0) ? "id != $id_val" : null
            ]);       

        // Role Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Role Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Name is required.'])
            ->addValidator('Db_NoRecordExists', true, [
                'messages' => 'This name already exists.',
                'table' => 'main_role',
                'field' => 'name',
                'exclude' => ($id_val > 0) ? "id != $id_val" : null
            ]);

         /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */
        
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs([
                'class' => 'formDataElement fck_editor_height',
                'rows'  => '4'
            ])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

         /* --------------------------------------------------------------
         * 5. Checkbox Elements
         * -------------------------------------------------------------- */

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('Y')
            ->setUncheckedValue('N')
            ->setValue('Y');

        /* --------------------------------------------------------------
         * 6. Submit Elements
         * -------------------------------------------------------------- */
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttrib('id', 'submitbutton')
            ->setAttrib('class', 'actbtn_btn')
            ->setLabel('Save');

        // Dynamic JS submission (Corrected for SSOT)
        // 1. Point to correct controller action (roles/save)
        // 2. Pass 'formid' to saveDetails so it can find the form
        // 3. Add 'return false;' to prevent double submission
        $url        = "'roles/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'roles\\');'"; // Escaped quotes for JS string

        $submit->setAttrib('onclick', "saveDetails($url, '', '', $jsFunction);");

       
        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $prev_cnt,
            $group_id,
            $levelid,
            $code,
            $name,
            $description,
            $isactive,
            $submit
        ]);
        
        // Set Standard Decorators
        $this->setElementDecorators(['ViewHelper']);
    }
}
