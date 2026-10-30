<?php



class Default_Form_iddocumentstatus extends Zend_Form{

    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','iddocumentstatus');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->addValidator('NotEmpty',false,array('messages'=>'Name required'));
        $code = new Zend_Form_Element_Text('code');
        $code->addValidator('NotEmpty', false, array('messages'=>'Code required'));
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(array(
            'table' => 'main_iddocumentstatus',
            'field' => 'code'
        )));
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");
        $valuetype = new Zend_Form_Element_Select('valuetype');
        $valuetype->addValidator('NotEmpty', false, array('messages'=>"Value type required"));
        $valuetype->addMultiOptions(array(''=>'Select Value type','LIST'=>'LIST','SPECIFIC'=>'SPECIFIC'));
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements(array($id,$code,$name,$description,$isactive,$submit,$valuetype));
        $this->setElementDecorators(array('ViewHelper')); 




    }
}


?>