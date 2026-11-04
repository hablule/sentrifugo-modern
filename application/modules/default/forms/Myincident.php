<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * My Incident Form
 * 
 * Limited form for Employees. Only exposes Appeal fields.
 * Strictly adheres to SSOT v6.2.
 */
class Default_Form_Myincident extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'myincident');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * Editable Fields (Employee Appeal)
         * -------------------------------------------------------------- */
        $employee_appeal = new Zend_Form_Element_Select('employee_appeal');
        $employee_appeal->setLabel('Do you wish to appeal this incident?')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(['2' => 'No', '1' => 'Yes'])
            ->setRequired(true);

        $employee_statement = new Zend_Form_Element_Textarea('employee_statement');
        $employee_statement->setLabel('Your Statement / Defense')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')->addFilter('StripTags');

        /* --------------------------------------------------------------
         * Submit
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Submit Appeal')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        $urlAction  = "'myincident/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'myincident\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        $this->addElements([
            $idElement,
            $employee_appeal,
            $employee_statement,
            $submit
        ]);

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
