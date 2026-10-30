<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Category Form
 * 
 * Strictly adheres to SSOT v6.2: Exact column matching, strict AJAX quoting, 
 * standard regex validators, and cleared decorators.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Pacategory extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * 1. Form Configuration (SSOT VI.5)
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'pacategory');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 2. Hidden Elements (SSOT VI.6)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 3. Text Elements
         * -------------------------------------------------------------- */

        // Category Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Category Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., LEADERSHIP'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a category code.'));

        // SSOT VI.4: Standard Regex Validator (No Spaces)
        $code->addValidator('Regex', true, array(
            'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9_]*)$/',
            'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Code must contain letters and cannot start with a space. Only letters, numbers, and underscores allowed.')
        ));

        // DB Unique Check
        $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_pa_category',
            'field'   => 'code',
            'exclude' => $excludeClauseCode
        ));
        $codeVal->setMessage('This category code already exists.');
        $code->addValidator($codeVal);

        // Category Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Category Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Core Leadership Skills'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a category name.'));

        // SSOT VI.4: Standard Regex Validator (Spaces allowed)
        $name->addValidator('Regex', true, array(
            'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
            'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.')
        ));

        // DB Unique Check
        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_pa_category',
            'field'   => 'name',
            'exclude' => $excludeClauseName
        ));
        $nameVal->setMessage('This category name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Checkbox Elements
         * -------------------------------------------------------------- */

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 6. Submit Elements (SSOT VI.8 & VI.9)
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // Strict JSON AJAX callback pattern
        $urlAction  = "'pacategory/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'pacategory\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */

        $this->addElements(array(
            $idElement,
            $code,
            $name,
            $description,
            $isactive,
            $submit
        ));

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
