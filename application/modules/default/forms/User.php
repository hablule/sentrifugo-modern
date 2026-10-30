<?php

/**
 * User Form
 * Manages System Users.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_User extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'user');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');

        /* --------------------------------------------------------------
         * 2. Text Fields
         * -------------------------------------------------------------- */
        
        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('User Code')
             ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60'))
             ->setRequired(true)
             ->addFilter('StringTrim')
             ->addFilter('StripTags')
             ->addValidator('NotEmpty', true, array('messages' => 'Code is required.'));
        
        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_users',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $codeVal->setMessage('This code already exists.');
        $code->addValidator($codeVal);

        // Username
        $username = new Zend_Form_Element_Text('username');
        $username->setLabel('User Name')
                 ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '15'))
                 ->setRequired(true)
                 ->addFilter('StringTrim')
                 ->addFilter('StripTags')
                 ->addValidator('NotEmpty', true, array('messages' => 'Username is required.'));
        
        $userVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_users',
            'field'   => 'username',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $userVal->setMessage('This username already exists.');
        $username->addValidator($userVal);

        // ldapusername
        $ldapUserName = new Zend_Form_Element_Text('ldapUserName');
        $ldapUserName->setLabel('LDAP')
                 ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100'))
                 ->setRequired(false)
                 ->addFilter('StringTrim')
                 ->addFilter('StripTags');
        
        $userLdapVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_users',
            'field'   => 'ldapusername',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $userLdapVal->setMessage('This LDAP user already exists.');
        $ldapUserName->addValidator($userLdapVal);

        // Email
        $email = new Zend_Form_Element_Text('emailaddress');
        $email->setLabel('Email')
              ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '255'))
              ->setRequired(false)
              ->addFilter('StringTrim')
              ->addValidator('EmailAddress');

        $emailVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_users',
            'field'   => 'emailaddress',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $emailVal->setMessage('This email already exists.');
        $email->addValidator($emailVal);

        /* --------------------------------------------------------------
         * 3. Passwords (Required on Add, Optional on Edit)
         * -------------------------------------------------------------- */
        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('Password')
                 ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '15'))
                 ->addFilter('StringTrim');
        
        $confirm = new Zend_Form_Element_Password('confirmpassword');
        $confirm->setLabel('Confirm Password')
                ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '15'))
                ->addFilter('StringTrim');

        // Only require password if creating new user
        if ($idVal == 0) {
            $password->setRequired(true)
                     ->addValidator('NotEmpty', true, array('messages' => 'Password is required.'));
            $confirm->setRequired(true)
                    ->addValidator('NotEmpty', true, array('messages' => 'Confirm Password is required.'));
        }

        // Additional Regex/Length validators can be added here as per original code

        /* --------------------------------------------------------------
         * 4. Selects & Checks
         * -------------------------------------------------------------- */
        
        $employee_id = new Zend_Form_Element_Select('employee_id');
        $employee_id->setLabel('Employee')
                    ->setAttrib('class', 'formDataElement')
                    ->setRegisterInArrayValidator(false);
        
        $employeeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_users',
            'field'   => 'empid',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $employeeVal->setMessage('This Employee already have a user.');
        $employee_id->addValidator($employeeVal);

        // Description
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
                    ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
                    ->addFilter('StringTrim')
                    ->addFilter('StripTags');

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
                 ->setCheckedValue('1') // Users table uses int 1
                 ->setUncheckedValue('0') // Users table uses int 0
                 ->setValue('1');

        $isLdapUser = new Zend_Form_Element_Checkbox('isLdapUser');
        $isLdapUser->setLabel('Is LDAP User')
                 ->setCheckedValue('Y') // Users table uses int 1
                 ->setUncheckedValue('N') // Users table uses int 0
                 ->setValue('N');
        

        /* --------------------------------------------------------------
         * 5. Submit
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttribs(array(
                'id'        => 'submitbutton', 
                'class'     => 'actbtn_btn',
                'onclick' => 'save()'
                ))
               ->setLabel('Save');
        
    //    $url        = "'user/save/format/json'";
    //    $jsFunction = "'redirecttocontroller(\\'user\\');'";

    //    $submit->setAttrib('onclick', "saveDetails($url, '', '', $jsFunction);");

        $this->addElements(array(
            $idElement, $code, $username, $password, $confirm, 
            $email, $employee_id, $ldapUserName, $isLdapUser,    
            $description, $isactive, $submit
        ));

        $this->setElementDecorators(array('ViewHelper')); 
    }
}