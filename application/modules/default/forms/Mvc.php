<?php

class Default_Form_Mvc extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'mvc');

        $id = new Zend_Form_Element_Hidden('id');

        $newmodulename = new Zend_Form_Element_Text('new_module_name');
        $newmodulename->setLabel('New Module Name');
      

        $modulename = new Zend_Form_Element_Select('module_name');
        $modulename->setRequired(true);
        $modulename->setLabel('Module Name');

        $controllername = new Zend_Form_Element_Text('controller_name');
        $controllername->setRequired(true);
        $controllername->setLabel('Controller Name');

        $table_name = new Zend_Form_Element_Select('table_name');
        $table_name->setLabel('DB Table');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Create');
        $this->addElements([$id,$newmodulename,$modulename,$controllername, $table_name, $submit]);
        $this->setElementDecorators(['ViewHelper']); 
    }
}
