<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * Report Catalog Form
 * 
 * Manages the Report Catalog definition and provides the payload 
 * containers for the Polymorphic ACL mappings.
 * Strictly adheres to SSOT v6.7.4.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Reportcatalog extends Zend_Form
{
    /**
     * Initialize the form elements, validators, and decorators.
     * 
     * @return void
     */
    public function init()
    {
        /* --------------------------------------------------------------
         * 1. Form Configuration (SSOT VI.5)
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'reportcatalog');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 2. Hidden Elements (SSOT VI.6 & Complex Form Payloads)
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        $groupsPayload = new Zend_Form_Element_Hidden('groups_payload');
        $groupsPayload->setAttrib('id', 'groups_payload');

        $rolesPayload = new Zend_Form_Element_Hidden('roles_payload');
        $rolesPayload->setAttrib('id', 'roles_payload');

        $employeesPayload = new Zend_Form_Element_Hidden('employees_payload');
        $employeesPayload->setAttrib('id', 'employees_payload');

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        // Visual Menu Group
        $menugroup_id = new Zend_Form_Element_Select('menugroup_id');
        $menugroup_id->setLabel('Visual Dashboard (Menu Group)')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select a visual dashboard group.'));

        $category_id = new Zend_Form_Element_Select('category_id');
        $category_id->setLabel('Report Category')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select a report category.'));
        // Options populated in Controller

        /* --------------------------------------------------------------
         * 4. Text Elements
         * -------------------------------------------------------------- */

        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Report Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., CAMP_SUMMARY'))
            ->setRequired(true)
            ->addFilter('StringTrim')->addFilter('StripTags')->addFilter('StringToUpper')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a report code.'))
            ->addValidator('Regex', true, array('pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9_]*)$/', 'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Code must contain letters and cannot start with a space. Only letters, numbers, and underscores allowed.')));

        $excludeClauseCode = ($idVal > 0) ? "id != $idVal AND isactive != 5" : "isactive != 5";
        $code->addValidator('Db_NoRecordExists', true, array(
            'table' => 'main_report_catalog',
            'field' => 'code',
            'exclude' => $excludeClauseCode,
            'messages' => array(Zend_Validate_Db_NoRecordExists::ERROR_RECORD_FOUND => 'This report code already exists.')
        ));

        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Report Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Campaign Summary Dashboard'))
            ->setRequired(true)
            ->addFilter('StringTrim')->addFilter('StripTags')
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter a report name.'))
            ->addValidator('Regex', true, array('pattern'  => '/^(?=.*[a-zA-Z])[A-Za-z0-9](.*[A-Za-z0-9])?$/', 'messages' => array(Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.')));

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description / Purpose')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Checkbox Elements
         * -------------------------------------------------------------- */

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')->setUncheckedValue('0')->setValue('1');

        /* --------------------------------------------------------------
         * 7. Submit Elements (SSOT VII.10.2)
         * -------------------------------------------------------------- */

        // Use Button type to bypass default saveDetails() for complex FormData payload
        $submit = new Zend_Form_Element_Submit('submit_btn');
        $submit->setLabel('Save')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI.7)
         * -------------------------------------------------------------- */

        $this->addElements(array(
            $idElement,
            $groupsPayload,
            $rolesPayload,
            $employeesPayload,
            $menugroup_id, 
            $category_id,
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
