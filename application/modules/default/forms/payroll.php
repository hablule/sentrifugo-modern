<?php 
class Default_Form_Payroll extends Zend_Form{
    public function init(){
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('action',BASE_URL.'payroll/add');
        $this->setAttrib('name','payroll');

        $id = new Zend_Form_Element_Hidden('id');
        $docno = new Zend_Form_Element_Text('docno');
        $docno->addValidator('NotEmpty',false,array('messages'=>'Document no. required'));
        $docno->setRequired();
        $docno->setAttrib('maxlength',50); 
        $paydate = new Zend_Form_Element_Text('paydate');
        $paydate->setRequired();
        $paydate->addValidator('NotEmpty',false,array('messages'=>'Choose pay date'));
        $autogen=new Zend_Form_Element_Checkbox('autogen');
        $autogen->setValue(0);
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Submit');
        $this->addElements(array($id,$docno,$paydate,$autogen,$submit));
        $this->setElementDecorators(array('ViewHelper')); 
    }

    public function approvalform()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','payroll');
        $id = new Zend_Form_Element_Hidden('id');
        $approve = new Zend_Form_Element_Select('approve');
        $approve->addMultiOptions(array(''=>'Select','Approve'=>'Approve','Reject'=>'Reject'));
        $approve->setRequired();
        $approve->addValidator('NotEmpty',false,array('messages'=>'Field required'));
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $this->addElements(array($id,$approve,$submit));
        $this->setElementDecorators(array('ViewHelper')); 
        return $this;
    }
}
