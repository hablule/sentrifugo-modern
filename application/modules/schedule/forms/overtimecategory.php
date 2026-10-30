<?php



class Schedule_Form_Overtimecategory extends Zend_Form
{
    public function init()
    {
        $this->setAttrib('id','formid');
        $this->setAttrib('name','overtime_category');
        $this->setMethod('post');
        $this->setAttrib('action',BASE_URL.'schedule/overtimecategory/add');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name_cat');
        $name->addValidator('NotEmpty',false,array('messages'=>'please fill name'));
        $name->setRequired(true);
        $name->setAttrib('maxlength',60);
        $description = new Zend_Form_Element_Textarea('description_cat');
        $description->setLabel("Description");
        $submit = new Zend_Form_Element_Submit('submit_cat');
        $submit->setLabel('Save');
        $name->addValidator("regex",true,array(
            'pattern'=>'/^[a-zA-Z.\-?]+$/',
            'messages'=>array(
                'regexNotMatch'=>'Please enter valid Name.'
            )
    
            
            ));
            $name->addValidator(new Zend_Validate_Db_NoRecordExists(
                array(
                    'table'=>'main_overtime_category',
                    'field'=>'name',
                 
                )
                ));
             $name->getValidator('Db_NoRecordExists')->setMessage('Name already exists');
    
        $this->addElements(array($id,$name,$description,$submit));
        $this->setElementDecorators(array('ViewHelper'));

    }
}




?>