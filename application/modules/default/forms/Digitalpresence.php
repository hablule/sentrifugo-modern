<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Digital Presence Form
 * 
 * Strictly adheres to SSOT v6.2. 
 * Note: All organizational parent selects are setRequired(false) in the form object. 
 * The mandatory "exactly one parent" constraint is enforced strictly in the Controller 
 * per SSOT V.10.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Digitalpresence extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration (SSOT VI.5)
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'digitalpresence');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * Hidden Elements (SSOT VI.6)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Core Select Element
         * -------------------------------------------------------------- */
        $digitalmedia_id = new Zend_Form_Element_Select('digitalmedia_id');
        $digitalmedia_id->setLabel('Digital Media Platform')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Digital Media Platform.']);

        /* --------------------------------------------------------------
         * Polymorphic Parent Select Elements (All Required=False here, enforced in Controller)
         * -------------------------------------------------------------- */
        $company_id    = new Zend_Form_Element_Select('company_id');
        $company_id->setLabel('Company')->setAttrib('class', 'formDataElement')->setRequired(false);

        $sbunit_id     = new Zend_Form_Element_Select('sbunit_id');
        $sbunit_id->setLabel('Business Unit')->setAttrib('class', 'formDataElement')->setRequired(false);

        $division_id   = new Zend_Form_Element_Select('division_id');
        $division_id->setLabel('Division')->setAttrib('class', 'formDataElement')->setRequired(false);

        $department_id = new Zend_Form_Element_Select('department_id');
        $department_id->setLabel('Department')->setAttrib('class', 'formDataElement')->setRequired(false);

        $section_id    = new Zend_Form_Element_Select('section_id');
        $section_id->setLabel('Section')->setAttrib('class', 'formDataElement')->setRequired(false);

        $project_id    = new Zend_Form_Element_Select('project_id');
        $project_id->setLabel('Project')->setAttrib('class', 'formDataElement')->setRequired(false);

        $team_id       = new Zend_Form_Element_Select('team_id');
        $team_id->setLabel('Team')->setAttrib('class', 'formDataElement')->setRequired(false);

        $employee_id   = new Zend_Form_Element_Select('employee_id');
        $employee_id->setLabel('Employee')->setAttrib('class', 'formDataElement')->setRequired(false);

        /* --------------------------------------------------------------
         * Text Elements
         * -------------------------------------------------------------- */
        $digitaladdress = new Zend_Form_Element_Text('digitaladdress');
        $digitaladdress->setLabel('URL / Handle')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '255', 'placeholder' => 'e.g., https://linkedin.com/in/username or @handle'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter the URL or Handle.']);
        // Note: SSOT Regex omitted intentionally here as this field contains URLs/Handles with special characters.

        /* --------------------------------------------------------------
         * Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
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
         * Submit Elements (SSOT VI.8 & VI.9)
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        $urlAction  = "'digitalpresence/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'digitalpresence\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $digitalmedia_id,
            $company_id,
            $sbunit_id,
            $division_id,
            $department_id,
            $section_id,
            $project_id,
            $team_id,
            $employee_id,
            $digitaladdress,
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
