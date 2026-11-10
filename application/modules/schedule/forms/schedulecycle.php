<?php



class Schedule_Form_Schedulecycle extends Zend_Form
{



    public function init()
    {
        $this->setAttrib('name', 'schedulecycle');
        $this->setAttrib('id', 'formid');
        $this->setAttrib("name", "formindex");
        //$this->setAttrib('onload','chooseOne("dayily","dayofweek","dayofmonth",'. Zend_Controller_Front::getInstance()->getRequest()->getParam('id') .')');
         //$this->setAttrib('action',BASE_URL.'schedulecycle/addpopup/id/1');
        $this->setMethod('post');
        $id = new Zend_Form_Element_Hidden('id');



        $schedule_id = new Zend_Form_Element_Select('schedule_id');
        $schedule_id->setAttrib('onchange', 'hideElementofthree("dayily","dayofweek","dayofmonth","schedule_id",this)');



       // $schedule_id->setRequired(true);
       // $schedule_id->addValidator('NotEmpty', false, array('messages' => 'Schedule Required'));
        $modelc = new Schedule_Model_Timeschedule();
        $addc = $modelc->getschedulelist();
        $schedule_id->addMultiOption(' ', 'Select...');
        if (empty($addc)) {
            $msgarray['schedule_id'] = 'Schedule   is not configured yet.';
        } else {
            foreach ($addc as $addcs) {
                $schedule_id->addMultiOptions([$addcs['schedule_id'] => $addcs['name']]);
            }
        }
        $schedule_id->setRegisterInArrayValidator(false);

        $daily = new Zend_Form_Element_Select('daily');
        // $cycle->addValidator('NotEmpty',false,array('messages'=>'Cycle Required'));
        // $cycle->setRequired(true);
        $daily->addMultiOptions([
            '' => 'Select..',
            'ANYDAY' => 'ANYDAY',
        ]);
        //$daily->addValidator('NotEmpty', false, array('messages' => 'Please Select'));

        $dayofweek = new Zend_Form_Element_Select('dayofweek');
        $dayofweek->addMultiOptions([
            ' ' => 'Select..',
            'Monday' => 'Monday',
            'Tuesday' => 'Tuesday',
            'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday',
            'Friday' => 'Friday',
            'Saturday' => 'Saturday',
            'Sunday' => 'Sunday',
        ]);
        //$dayofweek->addValidator('NotEmpty', false, array('messages' => 'Please Select'));

        $dayofweek->setRegisterInArrayValidator(false);

        $dayofmonth = new Zend_Form_Element_Select('dayofmonth');
        $dayofmonth->addMultiOption('', 'Select...');
        //$dayofmonth->addValidator('NotEmpty', false, array('messages' => 'Please Select'));
        $dayofmonth->setRequired(false);
        for ($i = 1; $i <= 31; $i++) {
            $dayofmonth->addMultiOptions([$i => $i]);
        }
        $dayofmonth->setRegisterInArrayValidator(false);

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setValue(1);
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description');
        $submit = new Zend_Form_Element_Submit('submit');
        $back = new Zend_Form_Element_Button('back');
        $submit->setLabel('save');
        $back->setLabel('Cancel');
        // $back->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() .'/schedule' ."'");
        $this->addElements([
            $id, $schedule_id, $back, $daily, $dayofmonth, $dayofweek, $submit,
            $isactive, $description
        ]);

        $this->setElementDecorators(['ViewHelper']);
    }
   
}
