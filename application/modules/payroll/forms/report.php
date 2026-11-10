<?php


class Payroll_Form_report extends Zend_Form
{
    public function init()
    {
        $this->setAttrib('id','formid');
        $this->setAttrib('name','report');
        $this->setAttrib('action',BASE_URL.'view/id/');
        $this->setMethod('POST');

        $id = new Zend_Form_Element_Hidden('id');
        $empid = new Zend_Form_Element_Select('empid');
        $empid->setRequired();
        $empid->addValidator('NotEmpty',false,['messages'=>'need parameter']);
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements([$id,$empid,$submit]);
    }
}


?>