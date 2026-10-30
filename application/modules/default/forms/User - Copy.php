<?php
class Default_Form_user extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'user');


        $id = new Zend_Form_Element_Hidden('id');
        $code = new Zend_Form_Element_Text('code');
        $code->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $code->setRequired(true);
        $username = new Zend_Form_Element_Text('username');
        $username->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $username->setRequired(true);
        $username->setAttrib('maxLength',15);  		
    	$username->addValidator('stringLength', true, ['min' => 5, 'max' => 15,
    	'messages' => [Zend_Validate_StringLength::TOO_LONG => 'The password cannot be more than 15 characters.',
                        Zend_Validate_StringLength::TOO_SHORT => 'New password should be atleast 6 characters long.'] ]);
                         
    	$username->addFilters(['StringTrim']);

          $password = new Zend_Form_Element_Password('password');
        $password->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $password->setRequired(true);
          $password->setAttrib('maxLength',15);  		
    	$password->addValidator('stringLength', true, ['min' => 6, 'max' => 15,
    	'messages' => [Zend_Validate_StringLength::TOO_LONG => 'The password cannot be more than 15 characters.',
                        Zend_Validate_StringLength::TOO_SHORT => 'New password should be atleast 6 characters long.'] ]);
                         
    	$password->addFilters(['StringTrim']);
        $confirmpassword = new Zend_Form_Element_Password('confirmpassword');
        $confirmpassword->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $confirmpassword->setRequired(true);
        $confirmpassword->setAttrib('maxLength',15);  		
    	$confirmpassword->addValidator('stringLength', true, ['min' => 6, 'max' => 15,
    	'messages' => [Zend_Validate_StringLength::TOO_LONG => 'The password cannot be more than 15 characters.',
                        Zend_Validate_StringLength::TOO_SHORT => 'New password should be atleast 6 characters long.'] ]);
                         
    	$confirmpassword->addFilters(['StringTrim']);
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel("Description");

        $isactive->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']); 
        $emailaddress = new Zend_Form_Element_Text("emailaddress");
        $emailaddress->addValidator('NotEmpty', false, ['messages' => 'Please enter email.']);
               
        $emailaddress->addValidator("regex",true,[
                   
                    'pattern'=>'/^(?!.*\.{2})[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/',                            
                   'messages'=>[
                       'regexNotMatch'=>'Please enter valid email.'
                   ]
        ]);
            
        $emailaddress->setLabel("Email");
        $emailaddress->setAttrib("class", "formDataElement");              
        // $emailaddress->addValidator(new Zend_Validate_Db_NoRecordExists(
        //                                                 array('table' => 'main_users',
        //                                                 'field' => 'emailaddress',
        //                                                 'exclude'=>'id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('user_id',0).'" ',
        //                                                 )));
        // $emailaddress->getValidator('Db_NoRecordExists')->setMessage('Email already exists.');
        
      
        $employee_id = new Zend_Form_Element_Select('employee_id');
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements([$id,$code,$username,$password,$confirmpassword,$emailaddress,$employee_id,$isactive,$description,
        $employee_id,$submit]);
        $this->setElementDecorators(['ViewHelper']); 
    }
}
?>