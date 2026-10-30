<?php




class Default_Form_empschedule extends Zend_Form
{

    public function init()
    {

        $this->setMethod('post');
        $this->setAttrib('id','formid');
        // $this->setAttrib('action',BASE_URL.'empschedule/edit');
        $this->setAttrib('name','empschedule');
        $id = new Zend_Form_Element_Hidden('id');
        $sch_name = new Zend_Form_Element_Select('sch_name');
        $shdmodel = new Schedule_Model_Timeschedule();
        $shdData = $shdmodel->getschedulelist();
        $sch_name->addMultiOption('','Select');
        foreach($shdData as $value)
        {
            $sch_name->addMultiOption($value['schedule_id'],$value['name']);
        }
        // $sch_name->addValidator(new Zend_Validate_Db_NoRecordExists(array(
        //     'table'=>'main_emp_schedule',
        //     'field'=>'sch_id',
        //     'exclude'=>'emp_id!='.Zend_Controller_Front::getInstance()->getRequest()->getParam('unitId')
        // )));
       
        // $sch_name->getValidator('Db_NoRecordExists')->setMessage('Schedule already Assigned.');
        $sch_name->setRequired(true);
        $sch_name->addValidator('NotEmpty',false,['messages'=>'Please fill schedule name']);
        $description = new Zend_Form_Element_Text('description');
        $start_date = new Zend_Form_Element_Text('start_date');
        $end_date = new Zend_Form_Element_Text('end_date');

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
        $back = new Zend_Form_Element_Button('back');
        $back->setLabel('Cancel');
        $back->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() .'/employee/add' ."'");

        $this->addElements([$id,$sch_name,$back,$isactive,$description,$start_date,$end_date,$submit]);
        $this->setElementDecorators(['ViewHelper']);
    }


}



?>