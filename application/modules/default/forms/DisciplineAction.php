<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Discipline Action Form
 * 
 * Strictly adheres to SSOT v6.2.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Disciplineaction extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'disciplineaction');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Text Elements
         * -------------------------------------------------------------- */
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Action Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '255', 'placeholder' => 'e.g., Suspension Without Pay'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter an action name.'));

        // SSOT VI.4: Standard Regex Validator
        $name->addValidator('Regex', true, array(
            'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
            'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.')
        ));

        $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_discipline_action',
            'field'   => 'name',
            'exclude' => $excludeClauseName
        ));
        $nameVal->setMessage('This action name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * Submit Elements (SSOT VI.9)
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        $urlAction  = "'disciplineaction/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'disciplineaction\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
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
