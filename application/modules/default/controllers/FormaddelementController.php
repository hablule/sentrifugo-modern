<?php

class Default_FormaddelementController extends Zend_Controller_Action{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    
    public function indexAction(){
        
        $addElementForm = new Default_Form_Formaddelement();
       
        $this->view->form = $addElementForm;
    
        }   
}
?>