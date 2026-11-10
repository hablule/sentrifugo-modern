<?php



class Timemanagement_EmployeescheduleController extends Zend_Controller_Action{

    public function init()
    {
        $this->options = $this->getInvokeArg('bootstrap')->getOptions();
    }


    public function indexAction(){
        $form = new Timemanagement_Form_Employeeschedule();
        $model = new Timemanagement_Model_Emptimesheets();
        $days = $model->daysOftheweek();
        foreach ($days as $day) {
            $form->workdays->addMultiOptions([
                $day['id']=>$day['weekname']
            ]);
        }
        $this->view->form = $form;
    }

}


?>