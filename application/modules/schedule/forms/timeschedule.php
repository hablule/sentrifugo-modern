<?php



class Schedule_Form_Timeschedule extends Zend_Form{
    


    public function init()
    {
        $this->setAttrib('name','timeschedule');
        $this->setAttrib('id','formid');
        // $this->setAttrib('action',BASE_URL.'schedule/timeschedule/add');
        $this->setMethod('post');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setRequired(true);
        $name->addValidator('NotEmpty',false,['messages'=>'Name Required']);
        $code = new Zend_Form_Element_Text('code');
        $code->setRequired(true);
        $code->addValidator('NotEmpty',false,['messages'=>' code Required']);
       /*  $code->addValidator(new Zend_Validate_Db_NoRecordExists(
            array(
                'table'=>'main_schedule',
                'field'=>'code',
                'exclude'=>'schedule_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    

            )
            ));
            $code->getValidator('Db_NoRecordExists')->setMessage('Code already exists'); */
       
        $startdate = new Zend_Form_Element_Text('startdate');
        $startdate->setRequired(true);
        $startdate->addValidator('NotEmpty',false,['messages'=>'Start date Required']);
        $startdate->setAttrib('readonly', 'true');	
        $startdate->setAttrib('onfocus', 'this.blur()');
        $enddate = new Zend_Form_Element_Text('enddate');
        $enddate->setRequired(true);
        $enddate->addValidator('NotEmpty',false,['messages'=>'End date Required']);
        $enddate->setAttrib('readonly', 'true');	
        $enddate->setAttrib('onfocus', 'this.blur()');
      
        $cycle = new Zend_Form_Element_Select('cycle');
        $cycle->setLabel('Cycle');
        $cycle->addValidator('NotEmpty',false,['messages'=>'Cycle Required']);
        $cycle->setRequired(true);
        $cycle->addMultiOptions([''=>'Select..','DAILY'=>'DAILY','WEEKLY'=>'WEEKLY','MONTHLY'=>'MONTHLY']);
       
        $isverified=new Zend_Form_Element_Checkbox('isverified');
      
        $isactive=new Zend_Form_Element_Checkbox('isactive');
        $isactive->setValue(1);
        $description=new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description');
        $test = new Zend_Form_Element_Textarea('test');
        $test2 = new Zend_Form_Element_Textarea('test2');
        $test2->setRequired(true);
        $test2->addValidator('NotEmpty', false, ['messages'=>'need to add cycle']);
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $back = new Zend_Form_Element_Button('back');
        $back->setLabel('Cancel');
        //back->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() .'/schedule' ."'");
        $this->addElements([$id, $test,$test2,$name,$back,$code,$startdate,$enddate,$submit,
                            $isactive,$isverified,$description,$cycle]);
        $this->setElementDecorators(['ViewHelper']);
        
    }
}


?>