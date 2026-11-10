<?php

class ClassName extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    public function indexAction()
    {
    }
    public function addAction()
    {
        $ControllerName_Form = 'Form_Class';
        $this->view->form = $ControllerName_Form; 
    }
    public function editAction()
    {
        $ControllerName_Form = 'Form_Class';
        $this->view->form = $ControllerName_Form; 
    }
    public function viewAction()
    {
        $ControllerName_Form = 'Form_Class';
        $this->view->form = $ControllerName_Form; 
    }
}
