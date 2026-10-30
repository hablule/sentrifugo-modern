<?php 
class Default_Form_Payroll extends Zend_Form{
    public function init(){
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('action',BASE_URL.'payroll/add');
        $this->setAttrib('name','payroll');

        $id = new Zend_Form_Element_Hidden('id');
        $docno = new Zend_Form_Element_Text('docno');
        $docno->addValidator('NotEmpty',false,['messages'=>'Document no. required']);
        $docno->setRequired();
        $docno->setAttrib('maxlength',50); 
        $paydate = new Zend_Form_Element_Text('paydate');
        $paydate->setRequired();
        $paydate->addValidator('NotEmpty',false,['messages'=>'Choose pay date']);
        $autogen=new Zend_Form_Element_Checkbox('autogen');
        $autogen->setValue(0);
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Submit');
        $this->addElements([$id,$docno,$paydate,$autogen,$submit]);
        $this->setElementDecorators(['ViewHelper']); 
    }

    public function approvalform()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','payroll');
        $id = new Zend_Form_Element_Hidden('id');
        $approve = new Zend_Form_Element_Select('approve');
        $approve->addMultiOptions([''=>'Select','Approve'=>'Approve','Reject'=>'Reject']);
        $approve->setRequired();
        $approve->addValidator('NotEmpty',false,['messages'=>'Field required']);
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $this->addElements([$id,$approve,$submit]);
        $this->setElementDecorators(['ViewHelper']); 
        return $this;
    }
}
