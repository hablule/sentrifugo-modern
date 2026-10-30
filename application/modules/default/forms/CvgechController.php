<?php



class Default_CvgechController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }


    public function indexAction()
    {
        $cvfileform = new Default_Form_cvgech();
        $this->view->form = $cvfileform;
    }
    public function addcvAction()
    {

    }
}


?>