<?php


class Default_Form_recordaccess extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('action',BASE_URL.'recordaccess/edit');
        $this->setAttrib('name','recordaccess');
        $this->setAttrib('id','formid');
        $id = new Zend_Form_Element_Hidden('id');
        $user_id = new Zend_Form_Element_Select('user_id');
        $user_id->addValidator('NotEmpty',false,array('messages'=>'User required'));
        $user_id->setRequired(true);
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel("Description");
        $accesslevel = new Zend_Form_Element_Select('accesslevel');
        $accesslevel->addMultiOptions(array(
            ''=>'select',
            'SELF'=>'Self',
            'CHAIN'=>'Chain',
            'LINE'=>'Line',
            'GROUP'=>'Group',
            'INDIVIDUAL'=>'Individual',
            'ALL'=>'All'
        ));
        $accesslevel->addValidator('NotEmpty',false,array('messages'=>'Access level required'));
        $accesslevel->setRequired(true);
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $readwrite = new Zend_Form_Element_Checkbox('readwrite');
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');
        $this->addElements(array($id,$user_id,$description,$accesslevel,$isactive,$readwrite,$submit));
        $this->setElementDecorators(array('ViewHelper'));  
    }
}



?>