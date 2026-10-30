<?php
class Schedule_Form_AddConnection extends Zend_Form
{
    public function init()
    {
        $this->setAttrib('action', BASE_URL . 'device');
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'device');
        $id = new Zend_Form_Element_Hidden('id');
        $device_ip = new Zend_Form_Element_Text('ip_address');
        $device_ip->setLabel('IP Address')
                ->setAttrib('class', 'form-control')
                ->setAttrib('placeholder', 'xxx.xxx.xxx')
                ->setRequired(true)
                ->addValidator('NotEmpty', true)
                ->addFilter('StripTags')
                ->addFilter('StringTrim');
        $device_port = new Zend_Form_Element_Text('port');
        $device_port->setLabel('Port')
                ->setAttrib('class', 'form-control')
                ->setAttrib('placeholder', '51211')
                ->setRequired(true)
                ->addValidator('NotEmpty', true)
                ->addValidator('StringLength', false, array(1, 5))
                ->addFilter('StripTags')
                ->addFilter('StringTrim');

        $submit = new Zend_Form_Element_Button('Search');
        $submit->setLabel('Search');
        $submit->setAttrib('class', 'search-btn');
        $submit->setAttrib('style', 'margin-right:10px;');
        $submit->setAttrib('onclick', 'searchDevice();');
        $this->addElements(array($id, $device_ip, $device_port, $submit));
        $this->setElementDecorators(array('ViewHelper'));
    }
}

?>