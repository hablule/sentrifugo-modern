<?php




class Default_Form_workexp extends Zend_Form
{

    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('action',BASE_URL.'workexp/next');
        $this->setAttrib('name','workexp');
        $id = new Zend_Form_Element_Hidden('id');
        // $jobtitle = new Zend_Form_Element_Select('jobtitle');
        // $jobtitle->setRequired(true);

        // $jobtitle->addValidator('NotEmpty', false, array('messages' => 'Please select job title.'));
        // $jobtitle->setRequired(true);

        // $employer = new Zend_Form_Element_Text('employer');
        // $employer->addValidator('NotEmpty', false, array('messages' => 'Please insert employer.'));
        // $employer->addValidators(array(
        //     array(
        //         'validator'   => 'Regex',
        //         'breakChainOnFailure' => true,
        //         'options'     => array( 
        //         'pattern' =>'/^[a-zA-Z][a-zA-Z0-9\-\,\.\&\:\"\'\s]+$/i',
        //             'messages' => array(
        //                     'regexNotMatch'=>'Please start with alphabets.'
        //             )
        //         )
        //     )
        // ));
        // $employer->setRequired(true);

        // $startdate = new Zend_Form_Element_Text('startdate');
      
        

        // $enddate = new Zend_Form_Element_Text('enddate');
        // $enddate ->setAttrib('title', 'Due Date.');  
        // $enddate->setAttrib('maxLength', 10);
        // $enddate->setAttrib('readonly', 'readonly');
        // $enddate->setAttrib('onfocus', 'this.blur()');
        // $enddate->addValidator('NotEmpty', false, array('messages' => 'Please select start date.'));
        // $enddate->setRequired(true);

        // $jobposition = new Zend_Form_Element_Select('jobpostion');
        // $jobposition->addValidator('NotEmpty', false, array('messages' => 'Please select start date.'));
        // $jobposition->setRequired(true);

        // $comment = new Zend_Form_Element_Textarea('comment');
        // $leavingreason->setLabel('comment');
        // $leavingreason->setAttrib('rows', 10);
        // $leavingreason->setAttrib('cols', 170);
        // $leavingreason->addValidator('NotEmpty', false, array('messages' => 'Please insert leaving reason.'));
        // $leavingreason->setRequired(true);

        $prevbtn = new Zend_Form_Element_Button('prevbtn');
        $prevbtn->setLabel('Prev');
        $prevbtn->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() .'/addpersonaldetail' ."'");
        $submit = new Zend_Form_Element_Submit('submit');

        $submit->setLabel('Save and Next');
        $this->addElements(array($id,$prevbtn,$submit));
        $this->setElementDecorators(array('ViewHelper'));  

    }

}



?>