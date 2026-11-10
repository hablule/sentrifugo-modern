<?php

class Default_Form_fingerprintconfig extends Zend_Form{
    public function init()
    {
        $this->setMethod('post');
        $this->setAction(BASE_URL.'fingerprintconfig/edit');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'fingerprintconfig');

        $id = new Zend_Form_Element_Hidden('id');
        $securityLevel = new Zend_Form_Element_Select('security_level');
        $securityLevel->setLabel('Security Level:');
        // $securityLevel->setRequired(true);
        $securityLevel->addMultiOption('1', 'Basic');
        $securityLevel->addMultiOption('2','High Secure');
        $securityLevel->addMultiOption('3','Most Secure');
        $fastMode = new Zend_Form_Element_Select('fast_mode');
        $fastMode->setLabel('Fast Mode:');
        // $fastMode->setRequired(true);
        $fastMode->addMultiOption('0','Automatic');
        $fastMode->addMultiOption('1','Basic');
        $fastMode->addMultiOption('2','High');
        $fastMode->addMultiOption('3','Very High');
        $sensitivity = new Zend_Form_Element_Select('sensitivity');
        $sensitivity->setLabel('Sensitivity:');
        // $sensitivity->setRequired(true);
        $sensitivity->addMultiOption('0', 'Lowest');
        $sensitivity->addMultiOption('1', 'Level1');
        $sensitivity->addMultiOption('2', 'Level2');
        $sensitivity->addMultiOption('3', 'Level3');
        $sensitivity->addMultiOption('4', 'Level4');
        $sensitivity->addMultiOption('5', 'Level5');
        $sensitivity->addMultiOption('6', 'Level6');
        $sensitivity->addMultiOption('7', 'Highest');
        $sensorMode = new Zend_Form_Element_Select('sensor_mode');
        $sensorMode->setLabel('Sensor Mode:');
        // $sensorMode->setRequired(true);
        $sensorMode->addMultiOption('0', 'Always');
        $sensorMode->addMultiOption('1', 'On Scan');
        $templateFormat = new Zend_Form_Element_Select('template_format');
        $templateFormat->setLabel('Template Format:');
        // $templateFormat->setRequired(true);
        $templateFormat->addMultiOption('0', 'Suprema');
        $templateFormat->addMultiOption('1', 'ISO');
        $templateFormat->addMultiOption('2', 'ANSI');
        $scanTimeout = new Zend_Form_Element_Text('scan_timeout');
        $scanTimeout->setLabel('Scan Timeout:');
        $showImage = new Zend_Form_Element_Checkbox('show_image');
        $showImage->setLabel('Show Image:');
        // $showImage->setRequired(true);
        $LFDLevel = new Zend_Form_Element_Select('ifd_level');
        $LFDLevel->setLabel('Live Fingerprint Detection:');
        // $LFDLevel->setRequired(true);
        $LFDLevel->addMultiOption('0', 'Not use');
        $LFDLevel->addMultiOption('2', 'Normal');
        $LFDLevel->addMultiOption('1', 'Strict');
        $LFDLevel->addMultiOption('3', 'Very Strict');
        $checkDuplicate = new Zend_Form_Element_Checkbox('check_duplicate');
        $checkDuplicate->setLabel('Check Duplicate:');
        //$checkDuplicate->setRequired(true);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttrib('id', 'submitbutton');
        $submit->setLabel('Save');
        $this->addElements([$id,$securityLevel,$fastMode,$sensitivity,$sensorMode,$templateFormat,$scanTimeout,$showImage,$LFDLevel,$checkDuplicate,$submit]);
        $this->setElementDecorators(['ViewHelper']);
        
    }
}
