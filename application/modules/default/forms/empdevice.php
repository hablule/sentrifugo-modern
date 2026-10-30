<?php

class Default_Form_EmpDevice extends Zend_Form
{

    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'empdevice');
        $this->setAttrib('class', 'form');
        $this->setAttrib('enctype', 'multipart/form-data');
        $id = new Zend_Form_Element_Hidden('id');
        $hs = new Zend_Form_Element_Hidden('hs');
        $chooseAuth = new Zend_Form_Element_Multiselect("choose_auth_method");
        $chooseAuth->addMultiOptions(array(
            '1' => 'Fingerprint',
            '2' => 'Face',
            '3' => 'Card'
            
        ));
        $chooseTempalte = new Zend_Form_Element_Multiselect("choose_template");
        $numoffinger = new Zend_Form_Element_Text('num_of_fingers');
        $numoffinger->setLabel('Number of Fingers')
            ->setAttrib('class', 'required')
            ->setAttrib('maxlength', '2')
            ->setAttrib('min',1)
            ->setAttrib('size', '10')
            ->setAttrib('autocomplete', 'off')
            ->setAttrib('onkeypress', 'return isNumberKey(event)')
            ->setAttrib('onblur', 'return isNumberKey(event)')
           
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter number of fingers'))
            ->addValidator('Digits', true, array('messages' => 'Please enter valid number of fingers'))
            ->addValidator('StringLength', true, array(1, 2, 'messages' => 'Please enter valid number of fingers'));
        $numofface = new Zend_Form_Element_Text('num_of_faces');
        $numofface->setLabel('Number of Faces')
            ->setAttrib('class', 'required')
            ->setAttrib('maxlength', '2')
            ->setAttrib('size', '10')
            ->setAttrib('autocomplete', 'off')
            ->setAttrib('onkeypress', 'return isNumberKey(event)')
            ->setAttrib('onblur', 'return isNumberKey(event)')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter number of faces'))
            ->addValidator('Digits', true, array('messages' => 'Please enter valid number of faces'))
            ->addValidator('StringLength', true, array(1, 2, 'messages' => 'Please enter valid number of faces'));

        $numofcard = new Zend_Form_Element_Text('num_of_cards');
        $numofcard->setLabel('Number of Cards')
            ->setAttrib('class', 'required')
            ->setAttrib('maxlength', '2')
            ->setAttrib('size', '10')
            ->setAttrib('autocomplete', 'off')
            ->setAttrib('onkeypress', 'return isNumberKey(event)')
            ->setAttrib('onblur', 'return isNumberKey(event)')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter number of cards'))
            ->addValidator('Digits', true, array('messages' => 'Please enter valid number of cards'))
            ->addValidator('StringLength', true, array(1, 2, 'messages' => 'Please enter valid number of cards'));


        $fingerauthmode = new Zend_Form_Element_Select('finger_auth_mode');
        $fingerauthmode->setLabel('Finger Authentication Mode')
            ->setAttrib('class', 'required')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select finger authentication mode'))
            ->addMultiOptions(array(
                '0' => 'Uses only fingerprint authentication',
                '1' => 'Uses fingerprint and PIN authentication',
                '254' => 'Cannot use',
                '255' => 'Undefined(Operates as defined in system)'
            ));

        $cardauthmode = new Zend_Form_Element_Select('card_auth_mode');
        $cardauthmode->setLabel('Face Authentication Mode')
            ->setAttrib('class', 'required')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select face authentication mode'))
            ->addMultiOptions(array(
                '2' => 'Uses only card authentication',
                '3' => 'Uses card and fingerprint authentication',
                '4' => 'Uses card and PIN authentication',
                '5' => 'Uses fingerprint or PIN after card authentication',
                '6' => 'Uses card, fingerprint, and PIN authentication',
                '254' => 'Cannot use',
                '255' => 'Undefined(Operates as defined in system)'
            ));




        $idauthmode = new Zend_Form_Element_Select('id_auth_mode');
        $idauthmode->setLabel('ID Authentication Mode')
            ->setAttrib('class', 'required')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select ID authentication mode'))
            ->addMultiOptions(array(
                '7' => 'Uses fingerprint authentication after entering user ID',
                '8' => 'Uses PIN authentication after entering user ID',
                '9' => 'Uses fingerprint or PIN authentication after entering user ID',
                '10' => 'Uses fingerprint and PIN authentication after entering user ID',
                '254' => 'Cannot use',
                '255' => 'Undefined(Operates as defined in system)'
            ));
        
        $pin = new Zend_Form_Element_Text('pin');
        $pin->setLabel('PIN')
            ->setAttrib('class', 'required')
            ->setAttrib('maxlength', '4')
            ->setAttrib('size', '10')
            ->setAttrib('autocomplete', 'off')
            ->setAttrib('onkeypress', 'return isNumberKey(event)')
            ->setAttrib('onblur', 'return isNumberKey(event)')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please enter PIN'))
            ->addValidator('Digits', true, array('messages' => 'Please enter valid PIN'))
            ->addValidator('StringLength', true, array(4, 4, 'messages' => 'Please enter valid PIN'));

        $securitylevel = new Zend_Form_Element_Select('security_level');
        $securitylevel->setLabel('Security Level')
            ->setAttrib('class', 'required')
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select security level'))
            ->addMultiOptions(array(
                '0' => 'Default',
                '1' => 'Lowest',
                '2' => 'Low',
                '3' => 'Normal',
                '4' => 'High security level',
                '5' => 'Highest security level'
            ));
        $deviceid = new Zend_Form_Element_Multiselect('device_id');
        $deviceid->addMultiOption('*', 'All Devices');
        $deviceid->setRequired(true);
        $devicetoscan = new Zend_Form_Element_Select('device_to_scan');
        $devicetoscan->setLabel('Device to Scan ');
        $devicetoscan->setRequired(true);
        $fingerprinttemplate = new Zend_Form_Element_Multiselect('fingerprint_template');
        $fingerprinttemplate->setLabel('Fingerprint Template');
        $fingerprinttemplate->setAttrib('hidden', 'hidden');
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($id, $hs, $chooseAuth, $pin, $numoffinger, $fingerprinttemplate, $numofface, $numofcard, $fingerauthmode, $cardauthmode, $idauthmode, $devicetoscan, $deviceid,$securitylevel, $submit));
        $this->setElementDecorators(array('ViewHelper'));
    }
}
