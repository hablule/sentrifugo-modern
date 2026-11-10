<?php

class Default_testrController extends Zend_Controller_Action
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
        $testr_Form = new Default_Form_testr();
        $this->view->form = $testr_Form; 
    }
    public function editAction()
    {
        $testr_Form = new Default_Form_testr();
        $this->view->form = $testr_Form; 
    }
    public function viewAction()
    {
        $testr_Form = new Default_Form_testr();
        $this->view->form = $testr_Form; 
    }
}
