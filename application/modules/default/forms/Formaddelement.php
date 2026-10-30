<?php

class Default_Form_Formaddelement extends Zend_Form{
    public function init()
    {
        
        $this->setMethod('post');
        $this->setAttrib('id','formid');
      
        $this->setAttrib('name','Formviewaddelement');

        $formName = new Zend_Form_Element_Select('formName');
        $formName->setLabel('Form Name');
        $formName->addValidator('NotEmpty', false, array('messages' => 'Please Select form Name.'));
        $formName->setRequired(true);
        $elementType = new Zend_Form_Element_Select('elementType');
        $elementType->setLabel('Element Type');
        $elementType->addValidator('NotEmpty', false, array('messages' => 'Please Select Element Type.'));
        $elementType->setRequired(true);
        $elementName = new Zend_Form_Element_Text('elementName');
        $elementName->setLabel('Element Name');
        $elementName->addValidator('NotEmpty', false, array('messages' => 'Please Insert Element Name.'));
        $elementName->setRequired(true);
        $elementLabel = new Zend_Form_Element_Text('elementLabel');
        $elementLabel->setLabel('Element Label');
        $elementLabel->addValidator('NotEmpty', false, array('messages' => 'Please Insert Element Lable.'));
        $elementLabel->setRequired(true);
        $elementTable = new Zend_Form_Element_Select('elementTable');
        $elementTable->setLabel('Element Database Table');
        // $elementTable->setRequired(true);
        // $elementTable->addValidator('NotEmpty', false, array('messages' => 'Please Select Database table.'));
        $startNewLine = new Zend_Form_Element_Select('startNewLine');
        $startNewLine->setLabel('Start Element At New Line');
        $startNewLine->setRequired(true);
        $startNewLine->addValidator('NotEmpty', false, array('messages' => 'Please Select the field'));
        $occupyWholeLine = new Zend_Form_Element_Select('occupyWholeLine');
        $occupyWholeLine->setLabel('Element Occupy Whole Line');
        $occupyWholeLine->setRequired(true);
        $occupyWholeLine->addValidator('NotEmpty', false, array('messages' => 'Please Select the field'));
        $getFrom = new Zend_Form_Element_Select('getFrom');
        $getFrom->setLabel('Populate Data from');
        $get = new Zend_Form_Element_Select('get');
        $get->setLabel('Populate');
        // $get->setRequired(true);
        // $get->addValidator('NotEmpty', false, array('messages' => 'Please Select the field'));
        $parent = new Zend_Form_Element_Select('parent');
        $parent->setLabel('Parent Element');
        $child = new Zend_Form_Element_Select('child');
        $child->setLabel('Child Element');
        $validators=new Zend_Form_Element_Multiselect('validators');
      
       $validators->setLabel("Validators");
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Add');
        $this->addElements(array($formName,$elementName,$elementType,$validators,$elementLabel, $elementTable, $startNewLine,$occupyWholeLine,$getFrom, $get, $parent, $child, $submit));
        $this->setElementDecorators(array('ViewHelper'));
    }
}

?>