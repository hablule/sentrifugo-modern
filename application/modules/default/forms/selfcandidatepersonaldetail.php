<?php

class Default_Form_selfcandidatepersonaldetail extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('action',BASE_URL.'addpersonaldetail/add');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','personaldetail');
        $id = new Zend_Form_Element_Hidden('id');
        $candidatefirstname = new Zend_Form_Element_Text('candidatefirstname');
       
        $candidatefirstname->addValidator('NotEmpty', false, array('messages' => 'Please enter candidate first Name'));
        $candidatelastname = new Zend_Form_Element_Text('candidatelastname');
        $candidatelastname->addValidator('NotEmpty', false, array('messages' => 'Please enter candidate last name'));
        $candidatemiddlename = new Zend_Form_Element_Text('candidatemiddlename');
        $candidatemiddlename->addValidator('NotEmpty', false, array('messages' => 'Please enter candidate middle name'));
        $streetaddress1 = new Zend_Form_Element_Text('streetaddress1');
        $streetaddress1->addValidator('NotEmpty', false, array('messages' => 'Please enter street address'));
        $streetaddress2 = new Zend_Form_Element_Text('streetaddress2');
        
        $country = new Zend_Form_Element_Select('country');
        $country->setAttrib('id','country');
        $country->setAttrib('onchange', 'displayParticularState_normal(this,"","state","city")');
        $state = new Zend_Form_Element_Select('state');
        $state->setAttrib('onchange', 'displayParticularCity_normal(this,"","city","")');

      
        $city = new Zend_Form_Element_Select('city');
        $city->setAttrib("id","city");

        $zipcode = new Zend_Form_Element_Text('zipcode');
        $zipcode->addValidator('NotEmpty', false, array('messages' => 'Please enter zip code'));
        $email = new Zend_Form_Element_Text('email');
        $email->addValidator('NotEmpty', false, array('messages' => 'Please enter email'));
        $contactpref = new Zend_Form_Element_Select('contactpref');
        $countrycode = new Zend_Form_Element_Select('countrycode');
        $mobilenumber = new Zend_Form_Element_Text('mobilenumber');
        $mobilenumber->addValidator('NotEmpty', false, array('messages' => 'Please enter mobile number'));
        $homenumber = new Zend_Form_Element_Text('homenumber');
        $nationality = new Zend_Form_Element_Select('nationality');
        $dualnationality = new Zend_Form_Element_Select('dualnationality');
        $nativelang= new Zend_Form_Element_Select('nativelang');
        $nativelangread= new Zend_Form_Element_Select('nativelangread');
        $nativelangwrite= new Zend_Form_Element_Select('nativelangwrite');
        $nativelanglisten= new Zend_Form_Element_Select('nativelanglisten');
        $nativelangspeak= new Zend_Form_Element_Select('nativelangspeak');

        $fluentlang = new Zend_Form_Element_Select('fluentlang');
        $fluentlangread = new Zend_Form_Element_Select('fluentlangread');
        $fluentlangwrite = new Zend_Form_Element_Select('fluentlangwrite');
        $fluentlanglisten = new Zend_Form_Element_Select('fluentlanglisten');
        $fluentlangspeak = new Zend_Form_Element_Select('fluentlangspeak');
        $prevbtn = new Zend_Form_Element_Button('prevsbtn');
        $prevbtn->setLabel('Prev');
        $prevbtn->setAttrib('onClick', "parent.location='" . Zend_Controller_Front::getInstance()->getBaseUrl() .'/addcv' ."'");
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save and Next');

        $this->addElements(array($id,$candidatefirstname,$candidatemiddlename,$candidatelastname,$streetaddress1,$streetaddress2,$country,$state,
                               $city,$zipcode,$email,$contactpref,$countrycode,$mobilenumber,$homenumber,
                               $nationality,$dualnationality,$nativelang,$nativelangread,$nativelangwrite,$nativelanglisten,$nativelangspeak,$fluentlang,$fluentlangread,$fluentlangwrite,$fluentlanglisten,$fluentlangspeak,$submit));
        //$this->addElements(array($id,$submit)); 
        
        //die(json_encode($this->_elements));
    }
}


?>
