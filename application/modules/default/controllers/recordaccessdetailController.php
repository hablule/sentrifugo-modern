<?php

class Default_recordaccessdetailController extends Zend_Controller_Action
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
        $recordaccessdetail_Form = new Default_Form_recordaccessdetail();
        $this->view->form = $recordaccessdetail_Form; 
    }
    public function editAction()
    {
        $recordaccessdetail_Form = new Default_Form_recordaccessdetail();
        $this->view->form = $recordaccessdetail_Form; 
    }
    public function viewAction()
    {
        $recordaccessdetail_Form = new Default_Form_recordaccessdetail();
        $this->view->form = $recordaccessdetail_Form; 
    }
}
