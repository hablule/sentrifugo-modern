<?php 



class Default_Form_empiddocument extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','empiddoc');
        $id = new Zend_Form_Element_Hidden('id');
        $empid = new Zend_Form_Element_Hidden('empid');
        $documentid = new Zend_Form_Element_Select('iddocument_id');
        $documentid->setAttrib('onchange','isnumbered(event)');
        $documentmodel = new Default_Model_iddocument();
        $documentdata = $documentmodel->getalldocument();
        $documentid->addMultiOption('','Select');
        $documentid->addValidator('NotEmpty',false,array('messages'=>'Document required'));
        $documentid->setRequired(true);
        foreach ($documentdata as  $value) {
            $documentid->addMultiOption($value['iddocument_id'],$value['name']);
        }
        $idnumber = new Zend_Form_Element_Text('idnumber');
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");
        $documentprovider = new Zend_Form_Element_Select('iddocumentprovider_id');
        $documentprovider->addValidator('NotEmpty',false,array('messages'=>'Provider required'));
        $documentprovider->setRequired(true);
        $providermodel = new Default_Model_Documentprovider();
        $providerdata = $providermodel->getallprovider();
        $documentprovider->addMultiOption('','Select');
        foreach ($providerdata as  $value) {
            $documentprovider->addMultiOption($value['iddocumentprovider_id'],$value['name']);
        }
        $statuslist = new Zend_Form_Element_Select('iddocumentstatuslist_id');

        $statuslist->addValidator('NotEmpty',false,array('messages'=>'Status list required'));
        $statuslist->setAttrib('onchange','hideselectortext("iddocumentstatuslist_id","iddocumentstatus")');
        $statuslistmodel = new Default_Model_Documentstatuslist();
        $statuslistdata = $statuslistmodel->getallstatuslist();
        $statuslist->addMultiOption('','Select');
        foreach ($statuslistdata as  $value) {
            $statuslist->addMultiOption($value['iddocumentstatuslist_id'],$value['value']);
        }
        $status = new Zend_Form_Element_Text('iddocumentstatus');
        $status->setAttrib('onchange','hideselectortext("iddocumentstatuslist_id","iddocumentstatus")');
        
        $status->addValidator('NotEmpty',false,array('messages'=>'Status required'));
        $issuedate = new Zend_Form_Element_Text('issuedate');
        $issuedate->addValidator('NotEmpty',false,array('messages'=>'Issue date required'));
        $issuedate->setRequired(true);
        $expiredate = new Zend_Form_Element_Text('expirydate');
        $expiredate->setRequired(true);
        $expiredate->addValidator('NotEmpty',false,array('messages'=>'Expiry date required'));
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');

        $this->addElements(array($id,$empid,$documentid,$idnumber,$description,$documentid,$documentprovider,$status,$statuslist,$issuedate,$expiredate,$isactive,$submit));
        $this->setElementDecorators(array('ViewHelper'));
    }
}

?>