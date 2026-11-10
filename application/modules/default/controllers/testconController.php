<?php

class Default_testconController extends Zend_Controller_Action
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
        $testcon_Form = new Default_Form_testcon();
        $this->view->form = $testcon_Form; 
    }
    public function editAction()
    {
        $testcon_Form = new Default_Form_testcon();
        $this->view->form = $testcon_Form; 
    }
    public function viewAction()
    {
        $testcon_Form = new Default_Form_testcon();
        $this->view->form = $testcon_Form; 
    }
}
