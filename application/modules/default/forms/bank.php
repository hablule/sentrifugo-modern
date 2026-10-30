<?php

class Default_Form_Bank extends Zend_Form
{
    public function __construct($isEdit = false)
    {
        $this->createfields($isEdit);
    }
    public function createfields($isEdit)
    {
        $this->setMethod('post');
        // $this->setAttrib('action',BASE_URL.'bankaccounttype/edit');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'bank');

        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setAttrib('maxLength', 50);
        $name->setLabel('Bank Name');
        $name->addValidator('NotEmpty', false, ['messages' => 'Please enter bank name.']);
        $name->addValidators([
            [
                'validator'   => 'Regex',
                'breakChainOnFailure' => true,
                'options'     => [

                    'pattern' => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9\-\s]*)$/',
                    'messages' => [
                        'regexNotMatch' => 'Please enter valid bank account type.'
                    ]
                ]
            ]
        ]);
        if (!$isEdit) {
            $name->addValidator(
                new Zend_Validate_Db_NoRecordExists(
                    [
                        'table' => 'main_bank',
                        'field' => 'name',
                    ]
                )
            );
            $name->getValidator('Db_NoRecordExists')->setMessage('Bank  already exists.');
            //  id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'" and 
        }
        $name->setRequired(true);

        $description = new Zend_Form_Element_Text('description');
        $description->setRequired(true);
        $description->addValidator('NotEmpty', false, ['messages' => 'Please enter bank description.']);
        $description->setLabel('Description');
        $description->setRequired(true);

        $routingno = new Zend_Form_Element_Text('routingno');
        $routingno->setRequired(true);
        $routingno->addValidator('NotEmpty', false, ['messages' => 'Please enter bank routing number.']);
        $routingno->setLabel('Routing Number');
        $routingno->setRequired(true);

        $swiftcode = new Zend_Form_Element_Text('swift_code');
        $swiftcode->setRequired(true);
        $swiftcode->addValidator('NotEmpty', false, ['messages' => 'Please enter bank swift code.']);
        $swiftcode->setLabel('Swift Code');
        $swiftcode->setRequired(true);

        $verification_class = new Zend_Form_Element_Text('verification_class');
        $verification_class->setRequired(true);
        $verification_class->addValidator('NotEmpty', false, ['messages' => 'Please enter bank verfication class.']);
        $verification_class->setLabel('Bank Verfication Class');
        $verification_class->setRequired(true);
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements([$id, $name, $description, $swiftcode, $routingno, $verification_class, $submit]);
        $this->setElementDecorators(['ViewHelper']);
    }
}
