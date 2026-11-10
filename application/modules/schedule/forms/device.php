<?php

class Schedule_Form_Device extends Zend_Form{
    public function init(){
        $this->setAttrib('action', BASE_URL . 'device');
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'device');
        $id = new Zend_Form_Element_Hidden('id');
        $device_name = new Zend_Form_Element_Text('device_name');
        $device_name->setLabel('Device Name')
                ->setAttrib('class', 'form-control')
                ->setAttrib('placeholder', 'Device Name')
                ->setRequired(true)
                ->addValidator('NotEmpty', true)
                ->addValidator('StringLength', false, [1, 50])
                ->addFilter('StripTags')
                ->addFilter('StringTrim');
        $device_ip = new Zend_Form_Element_Text('device_ip');
        $device_ip->setLabel('Device IP')
                ->setAttrib('class', 'form-control')
                ->setAttrib('placeholder', 'Device IP')
                ->setRequired(true)
                ->addValidator('NotEmpty', true)
                ->addValidator('StringLength', false, [1, 50])
                ->addFilter('StripTags')
                ->addFilter('StringTrim');
        $device_port = new Zend_Form_Element_Text('device_port');
        $device_port->setLabel('Device Port')
                ->setAttrib('class', 'form-control')
                ->setAttrib('placeholder', 'Device Port')
                ->setRequired(true)
                ->addValidator('NotEmpty', true)
                ->addValidator('StringLength', false, [1, 50])
                ->addFilter('StripTags')
                ->addFilter('StringTrim');
        $connection_mode = new Zend_Form_Element_Text('connection_mode');
        $connection_mode->setLabel('Connection mode')
                ->setAttrib('class', 'form-control')
                ->setAttrib('readonly', 'readonly');
        $device_status = new Zend_Form_Element_Select('device_status');
        $device_status->setLabel('Device Status')
                ->setAttrib('class', 'form-control')
                ->setAttrib('placeholder', 'Device Status')
                ->setRequired(true)
                ->addValidator('NotEmpty', true)
                ->addValidator('StringLength', false, [1, 50])
                ->addFilter('StripTags')
                ->addFilter('StringTrim')
                ->addMultiOptions(['' => 'Select Device Status', '1' => 'Active', '2' => 'Inactive']);
     
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements([$id, $device_name, $device_ip, $device_port, $connection_mode, $device_status, $submit]);
        $this->setElementDecorators(['ViewHelper']);

    }
}


?>