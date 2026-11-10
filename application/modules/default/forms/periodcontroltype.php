<?php


class Default_Form_periodcontroltype extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('action',BASE_URL.'periodcontroltype/edit');
        $this->setAttrib('name','periodcontroltype');
        $id = new Zend_Form_Element_Hidden('id');
        $period = new Zend_Form_Element_Select('period');
        $period->addMultiOptions([''=>'Select period']);
        $period->addValidator('NotEmpty',false,['messages'=>'Please select period']);
        $period->setRequired(true);

        $controltype = new Zend_Form_Element_Select('controltype');
        $controltype->addMultiOptions([''=>'Select ...','APPRAISAL'=>'Appraisal','REMUNERATION'=>'Remuneration']);
        $controltype->addValidator('NotEmpty',false,['messages'=>'select control type']);
      
        $controltype->setRequired(true);
        
        $isauto = new Zend_Form_Element_Checkbox('isauto');
        //$isauto->addMultiOptions(array(''=>'Select ...','Y'=>'Yes','N'=>'No'));
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('submit');
        $this->addElements([$id,$period,$controltype,$isauto,$submit]);
        $this->setElementDecorators(['ViewHelper']);
    }
}

?>