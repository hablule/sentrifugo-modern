<?php

class Default_Form_healthissue extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','healthissue');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setRequired(true);
        $name->addValidator('NotEmpty',false,array('messages'=>'Name Required'));
        $code = new Zend_Form_Element_Text('code');
        $code->setRequired(true);
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(array(
            'table'=>'main_healthissue',
            'field'=>'code'
        )));
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");

        $healthseverity_id = new Zend_Form_Element_Select('healthseverity_id');
        $healthseverity_id->setRequired(true);
        $healthseverity_id->addValidator('NotEmpty',false,array('messages'=>'Health Issue required'));
        $issuetype = new Zend_Form_Element_Select('issuetype_id');
        $issuetype->setRequired(true);
        $issuetype->addValidator('NotEmpty',false,array('messages'=>'Issue Type Required'));
        $issuecategory_id = new Zend_Form_Element_Select('issuecategory_id');
        $issuecategory_id->addValidator('NotEmpty',false,array('messages'=>'Category Required'));
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements(array($id,$name,$code,$description,$isactive,$issuecategory_id,$issuetype,$healthseverity_id,$submit));
        $this->setElementDecorators(array('ViewHelper'));
    }
}

?>