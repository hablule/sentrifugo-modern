<?php

class Default_DeviceuserController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_option = $this->getInvokeArg('bootstrap')->getOptions();
    }
    
    public function indexAction()
    {
        
    }
}
?>