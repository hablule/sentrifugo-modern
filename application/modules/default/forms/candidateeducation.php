<?php

class Default_Form_Candidateeducation extends Zend_Form{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('action',BASE_URL.'education/next');
        $this->setAttrib('name','candidateeducation');

         $id = new Zend_Form_Element_Hidden('id');
//         $institution = new Zend_Form_Element_Text('institution');
//         //$institution->addValidator('NotEmpty', false, array('messages' => 'Please enter institution name.'));
        
//         $institution->addValidator("regex",true,array(
                           
//             'pattern'=>'/[a-zA-Z][a-zA-Z ]+[a-zA-Z]$/',                            
//            'messages'=>array(
//                'regexNotMatch'=>'Please enter valid institution name.'
//            )
// ));
        
//         $course = new Zend_Form_Element_Text('course');
//         $course->addValidator('NotEmpty', false, array('messages' => 'Please enter course/programe'));
//         $course->setAttrib('required',true);
    	 
//         $education_level = new Zend_Form_Element_Select('education_level');
//         $education_level->addValidator('NotEmpty', false, array('messages' => 'Please select education level')) ;
//         $education_level->setAttrib('required', true);
  
//         $start_date = new Zend_Form_Element_Text('start_date');
//         $start_date->addValidator('NotEmpty', false, array('messages' => 'Please enter start date.'));
//     	$start_date->setAttrib('required',true);

//         $end_date = new Zend_Form_Element_Text('end_date');
//         $end_date->addValidator('NotEmpty', false, array('messages' => 'Please enter end date.'));
//     	$end_date->setAttrib('required', true);

//         $country = new Zend_Form_Element_Select('country');
//         $country->setAttrib('onchange', 'displayParticularState_normal(this,"","state","city")');
//         $country->addValidator('NotEmpty', false, array('messages' => 'Please select country.'));
//         $country->setAttrib('required', true);

//         $state = new Zend_Form_Element_Select('state');
//         $state->setAttrib('onchange', 'displayParticularCity_normal(this,"","city","")');
//         $state->addValidator('NotEmpty', false, array('messages' => 'Please select state.'));
//         $state->setAttrib('required',true);

//         $city = new Zend_Form_Element_Select('city');
//         $city->addValidator('NotEmpty', false, array('messages' => 'Please select city.'));
//         $city->setAttrib('required',true);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save and next');
        $prevbtn = new Zend_Form_Element_Button('prevsbtn');
        $prevbtn->setLabel('Prev');
        $prevbtn->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() .'/workexp' ."'");

        // $start_date->setAttrib('title', 'Due Date.');
        // $start_date->setAttrib('maxLength', 10);
        // $start_date->setAttrib('readonly','readonly');
        // $start_date->setAttrib('onfocus', 'this.blur()');

        // $end_date->setAttrib('title', 'Due Date.');
        // $end_date->setAttrib('maxLength', 10);
        // $end_date->setAttrib('readonly','readonly');
        // $end_date->setAttrib('onfocus', 'this.blur()');
        // $this->addElements(array($id, $institution, $course, $education_level, $start_date, 
        //                      $end_date, $country, $state, $city,$prevbtn,$submit));
        $this->addElements([$id,$prevbtn,$submit]);
        $this->setElementDecorators(['ViewHelper']); 

    }
}
?>