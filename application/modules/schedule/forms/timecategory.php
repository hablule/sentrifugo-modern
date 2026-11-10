<?php



class Schedule_Form_timecategory extends Zend_Form
{
    public function init()
    {
        $this->setAttrib('id','formid');
        $this->setAttrib('name','timecategory');
        $this->setMethod('post');
        // $this->setAttrib('action',BASE_URL.'schedule/timecategory/edit');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->addValidator('NotEmpty',false,['messages'=>'please fill name']);
        $name->setRequired(true);
        $name->setAttrib('maxlength',60);
        $code = new Zend_Form_Element_Text('code');
        $code->addValidator('NotEmpty',false,['messages'=>'please fill code']);
        $code->setRequired(true);
        $code->setAttrib('maxlength',25);
       
        $code->addValidator(new Zend_Validate_Db_NoRecordExists(
            [
                'table'=>'main_timecategory',
                'field'=>'code',
                'exclude'=>'timecategory_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    

            ]
            ));
         $code->getValidator('Db_NoRecordExists')->setMessage('Code already exists');
         $isactive = new Zend_Form_Element_Checkbox('isactive');
$isactive->setValue(1);
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel("Description");
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');
 
    
        $this->addElements([$id,$name,$code,$description,$isactive,$submit]);
        $this->setElementDecorators(['ViewHelper']);

    }
}




?>