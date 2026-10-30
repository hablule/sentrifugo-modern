<?php


class Schedule_Form_Cycleshift extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('name','cycleshift');
        $this->setAttrib('id','formid');
        
        $id = new Zend_Form_Element_Hidden('id');
        $controller = Zend_Controller_Front::getInstance();
        $request = $controller->getRequest();
        /* if($request->getParam('action')=='edit')
        {
            $shiftid = new Zend_Form_Element_Select('shiftid');
        }
        else{
            $shiftid = new Zend_Form_Element_Multiselect('shiftid');
        } */
        $shiftid = new Zend_Form_Element_Multiselect('shiftid');
        $shiftid->addMultiOption('','Select');
        $shiftid->setRequired(true);
        $shiftid->addValidator('NotEmpty', false, array('messages'=>'Shift Required'));
        $shiftmodel = new Schedule_Model_Timetable();
        $shiftData = $shiftmodel->getallshifts();
        foreach($shiftData as $value)
        {
            $shiftid->addMultiOption($value['shift_id'],$value['name']);
        }
        $schedulecycle_id = new Zend_Form_Element_Select('schedulecycle_id');
        $cyclemodel = new Schedule_Model_Schedulecycle();
        $cycleData = $cyclemodel->getCycleSchedule();
       // die(Zend_Debug::dump($cycleData));
        $schedulecycle_id->addMultiOption('','Select');
      
        foreach ($cycleData as $value) {
            $schedulecycle_id->addMultiOption($value['schedulecycle_id'],$value['description']);
        }
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('Description');
      
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setValue(1);
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $this->addElements(array($id, $shiftid, $description, $schedulecycle_id, $submit, $isactive));
        $this->setElementDecorators(array('ViewHelper'));
    }
}

?>