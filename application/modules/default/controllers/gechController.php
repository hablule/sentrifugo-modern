<?php

class Default_gechController extends Zend_Controller_Action
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
        $gech_Form = new Default_Form_gech();
        $this->view->form = $gech_Form; 
    }
    public function editAction()
    {
        $gech_Form = new Default_Form_gech();
        $this->view->form = $gech_Form; 
    }
    public function viewAction()
    {
        $gech_Form = new Default_Form_gech();
        $this->view->form = $gech_Form; 
    }
}
