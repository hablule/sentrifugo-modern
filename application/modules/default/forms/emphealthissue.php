<?php


class Default_Form_emphealthissue extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','emphealthissue');
        $id = new Zend_Form_Element_Hidden('id');
        $healthissue_id = new Zend_Form_Element_Select('healthissue_id');
        $healthissue_id->setLabel('Health Issue');

        $severity_id = new Zend_Form_Element_Select('severitylevel_id');
        $severity_id->setAttrib('onchange','hideElement("severitylevel","severitylevel_id")');
        $severity_id->setLabel('Health Severity Level');
        $severitylevel = new Zend_Form_Element_Text('severitylevel');
        $severitylevel->setAttrib('onchange','hideElement("severitylevel_id","severitylevel")');
        $severitylevel->setLabel('Health Severity Level');
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description');
        $diagnosedon = new Zend_Form_Element_Text('diagnosedon');
        $diagnosedon->setAttrib('readonly','');
        $diagnosedon->setLabel('Diagnosed On');
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements([$id,$healthissue_id,$severity_id,$severitylevel,$description,$diagnosedon,$isactive,$submit]);
        $this->setElementDecorators(['ViewHelper']); 
    }
}

?>