<?php


class Schedule_Form_Timetable extends Zend_Form
{
    public function init()
    {
        $this->setAttrib('id','formid');
        $this->setAttrib('action',BASE_URL.'schedule/timetable/addtime');
        $this->setAttrib('name','timetable');
        $this->setMethod('post');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setRequired(true);
        $name->addValidator('NotEmpty',true,array('messages'=>'Please fill name'));
       $name->addValidator(new Zend_Validate_Db_NoRecordExists(
           array(
               'table'=>'main_shift',
               'field'=>'name',
            
           )
           ));
        $name->getValidator('Db_NoRecordExists')->setMessage('Name already exists');
        $description = new Zend_Form_Element_Textarea('description');
        $code = new Zend_Form_Element_Text('code');
        $timecategory = new Zend_Form_Element_Select('timecategory');
        $timeCategoryModel = new Schedule_Model_timecategory();
        $timeCategoryData = $timeCategoryModel->getTimeCategory();
        $timecategory->addMultiOption('','Select');
        foreach ($timeCategoryData as  $value) {
            $timecategory->addMultiOption($value['timecategory_id'], $value['name']);
        }
        $isflexible = new Zend_Form_Element_Checkbox('isflexible');
        $minimumduration = new Zend_Form_Element_Text('minimumduration');
        $allowautocheckin = new Zend_Form_Element_Checkbox("allowautocheckin");
        $allowautocheckout = new Zend_Form_Element_Checkbox('allowautocheckout');
        $time_in = new Zend_Form_Element_Text('time_in');
        $time_in->setRequired(true);
        $time_in->addValidator('NotEmpty',false,array('messages'=>'fill time in'));
        $time_in->addValidator(new Zend_Validate_Db_NoRecordExists(
            array(
                'table'=>'main_shift',
                'field'=>'starttime',
            )
            ));
            $time_in->setAttrib('readonly', 'true');	
            $time_in->setAttrib('onfocus', 'this.blur()');
        $time_in->getValidator('Db_NoRecordExists')->setMessage('time already exists');
        $time_out = new Zend_Form_Element_Text('time_out');
        $time_out->setRequired(true);
        $time_out->setAttrib('readonly', 'true');	
        $time_out->setAttrib('onfocus', 'this.blur()');
        $time_out->addValidator('NotEmpty',false,array('messages'=>'fill time out'));
        $time_in_tolerance = new Zend_Form_Element_Select('time_in_tolerance');
        $time_in_tolerance->setRequired(true);
        $time_in_tolerance->addMultiOptions(array("0"=>"0 Minute","5"=>"5 Minutes","10"=>"10 Minutes","15"=>"15 Minutes","20"=>"20 Minutes","25"=>"25 Minutes","30"=>"30 Minutes"));
        $time_in_tolerance->setValue("0");
        $time_out_tolerance = new Zend_Form_Element_Select('time_out_tolerance');
        $time_out_tolerance->setRequired(true);
        $time_out_tolerance->addMultiOptions(array("0"=>"0 Minute","5"=>"5 Minutes","10"=>"10 Minutes","15"=>"15 Minutes","20"=>"20 Minutes","25"=>"25 Minutes","30"=>"30 Minutes"));
        $time_out_tolerance->setValue("0");
        $test = new Zend_Form_Element_Text('test');
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $this->addElements(array($id, $test,$name,$description,$time_in,$time_out,$time_in_tolerance,$code,$time_out_tolerance,$allowautocheckin,$allowautocheckout,$minimumduration,$isflexible,$timecategory,$submit));
        $this->setElementDecorators(array('ViewHelper')); 
    }
}


?>