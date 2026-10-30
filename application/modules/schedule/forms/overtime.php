<?php 
class Schedule_Form_Overtime extends Zend_Form {
public function init(){
    $this->setAttrib('name','overtime');
    $this->setAttrib('id','formid');
    $this->setAttrib('action',BASE_URL.'schedule/overtime/addnewot');
    $this->setMethod('post');

    $id = new Zend_Form_Element_Hidden('OT_id');
    $name = new Zend_Form_Element_Text('name');
    $name->addValidator('NotEmpty',false,array('messages'=>'Name Required'));
    $name->setRequired(true);
    $name->setLabel('Name');
    $name->addValidator("regex",true,array(
        'pattern'=>'/^[a-zA-Z.\-?]+$/',
        'messages'=>array(
            'regexNotMatch'=>'Please enter valid Name.'
        )

        
        ));
        $name->addValidator(new Zend_Validate_Db_NoRecordExists(
            array(
                'table'=>'main_overtime',
                'field'=>'name',
             
            )
            ));
            $name->getValidator('Db_NoRecordExists')->setMessage('Name already exists');
    $name->setAttrib('maxlength',60);
    $base = new Zend_Form_Element_Select('base');
    $base->addMultiOptions(array('empty'=>'Select...','Time'=>'Time','Day'=>'Day','Date'=>'Date', 'Period'=>'Period','Off-Shift'=>'Off-Shift','Holidays'=>'Holidays'));
    $base->setLabel('Base');
    $base_start_time = new Zend_Form_Element_Text('base_start_time');
    $base_start_time->setAttrib('readonly','readonly');
    $base_start_time->setLabel('Start Time');
    $base_end_time = new Zend_Form_Element_Text('base_end_time');
    $base_end_time->setAttrib('readonly','readonly');
    $base_end_time->setLabel('End Time');
    $base_start_date = new Zend_Form_Element_Text('base_start_date');
    $base_start_date->setAttrib('readonly','readonly');
    $base_start_date->setLabel('Start Date');
    $base_end_date = new Zend_Form_Element_Text('base_end_date');
    $base_end_date->setAttrib('readonly','readonly');
    $base_end_date->setLabel('End Date');
    $base_day = new Zend_Form_Element_Select('base_day');
    $base_day->addMultiOptions(array('empty'=>'Select...','Monday'=>'Monday','Tuseday'=>'Tuseday','Wendsday'=>'Wendsday','Thursday'=>'Thursday','Friday'=>'Friday','Saturday'=>'Saturday','Sunday'=>'Sunday'));
    $category = new Zend_Form_Element_Select('category');
    $category->setLabel('Category');
    $category->addValidator('NotEmpty',false,array('messages'=>'Category Required'));
    $category->setRequired(true);
    $description = new Zend_Form_Element_Textarea('description');
    $description->setLabel('Description');
    $begining = new Zend_Form_Element_Text('begining');
    $begining->setAttrib('readonly','readonly');
    $begining->addValidator('NotEmpty',false,array('messages'=>'Start time Required'));
    $begining->setRequired(true);
    $ending = new Zend_Form_Element_Text('ending');
    $ending->addValidator('NotEmpty',false,array('messages'=>'End time Required'));
    $ending->setRequired(true);
    $ending->setAttrib('readonly','readonly');
    $rate = new Zend_Form_Element_Text('rate');
    $rate->setLabel('Rate');
    $autoapv = new Zend_Form_Element_Checkbox('autoapv');
    $autoapv->setLabel('Auto Approve');
    $autoapv->setRequired(true);
    $autoapv->addValidator('NotEmpty',false,array('messages'=>'Select Auto Approval'));
    $submit = new Zend_Form_Element_Submit('submit');
    $submit->setLabel('Save');
    $back = new Zend_Form_Element_Button('back');
    $back->setLabel('Cancel');
    $back->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() .'/schedule/overtime' ."'");

    $this->addElements(array($id,$name,$base,$base_start_time,$base_end_time,$base_start_date,$base_end_date,$base_day,$category,$description,$begining,$autoapv,$ending,$rate,$submit,$back));
    $this->setElementDecorators(array('ViewHelper'));
}
}
?>