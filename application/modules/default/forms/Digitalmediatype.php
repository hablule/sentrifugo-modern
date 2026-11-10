<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Digital Media Type Form
 * 
 * Strictly adheres to SSOT v6.2: Exact column matching, strict AJAX quoting, 
 * standard regex validators, and cleared decorators.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Digitalmediatype extends Zend_Form
{
  /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

  public function init()
  {
    /* --------------------------------------------------------------
         * Form Configuration (SSOT VI.5)
         * -------------------------------------------------------------- */
    $this->setMethod('post');
    $this->setAttrib('id', 'formid');
    $this->setAttrib('name', 'digitalmediatype');

    $request = Zend_Controller_Front::getInstance()->getRequest();
    $idVal   = (int)$request->getParam('id', 0);

    /* --------------------------------------------------------------
         * Hidden Elements (SSOT VI.6)
         * -------------------------------------------------------------- */
    $idElement = new Zend_Form_Element_Hidden('id');
    $idElement->setValue($idVal);

    /* --------------------------------------------------------------
         * Text Elements
         * -------------------------------------------------------------- */

    // Type Code
    $code = new Zend_Form_Element_Text('code');
    $code->setLabel('Type Code')
      ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., SKYPE'])
      ->setRequired(true)
      ->addFilter('StringTrim')
      ->addFilter('StripTags')
      ->addValidator('NotEmpty', true, ['messages' => 'Please enter a type code.']);

    // SSOT VI.4: Standard Regex Validator
    $code->addValidator('Regex', true, [
      'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9_]*)$/',
      'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Code must contain letters and cannot start with a space. Only letters, numbers, and underscores allowed.']
    ]);

    $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
    $codeVal = new Zend_Validate_Db_NoRecordExists([
      'table'   => 'main_digitalmediatype',
      'field'   => 'code',
      'exclude' => $excludeClauseCode
    ]);
    $codeVal->setMessage('This type code already exists.');
    $code->addValidator($codeVal);

    // Type Name
    $name = new Zend_Form_Element_Text('name');
    $name->setLabel('Type Name')
      ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Skype'])
      ->setRequired(true)
      ->addFilter('StringTrim')
      ->addFilter('StripTags')
      ->addValidator('NotEmpty', true, ['messages' => 'Please enter a type name.']);

    // SSOT VI.4: Standard Regex Validator
    $name->addValidator('Regex', true, [
      'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
      'messages' => [Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.']
    ]);

    $excludeClauseName = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
    $nameVal = new Zend_Validate_Db_NoRecordExists([
      'table'   => 'main_digitalmediatype',
      'field'   => 'name',
      'exclude' => $excludeClauseName
    ]);
    $nameVal->setMessage('This type name already exists.');
    $name->addValidator($nameVal);

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
         * Submit Elements (SSOT VI.8 & VI.9)
         * -------------------------------------------------------------- */

    $submit = new Zend_Form_Element_Submit('submit');
    $submit->setLabel('Save')
      ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

    // Strict JSON AJAX callback pattern
    $urlAction  = "'digitalmediatype/save/format/json'";
    $jsFunction = "'redirecttocontroller(\\'digitalmediatype\\');'";
    $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

    /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */

    $this->addElements([
      $idElement,
      $code,
      $name,
      $description,
      $isactive,
      $submit
    ]);

    $this->clearDecorators();
    foreach ($this->getElements() as $element) {
      $element->setDecorators(['ViewHelper']);
    }
  }
}
