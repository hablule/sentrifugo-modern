<?php
class Default_Form_Leave extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'leave');


        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Name');      
        $name->addValidator('NotEmpty', false, ['messages' => 'Please fill the field.']);
        $name->setAttrib('maxlength',5); 
        $name->setRequired(true);
        $name->addValidator(new Zend_Validate_Db_NoRecordExists(
            [
                'table'=>'main_leave',
                'field'=>'name'
            ]
            ));
      $name->getValidator('Db_NoRecordExists')->setMessage('Name already exists.');
      $name->addValidators([
        [
            'validator'   => 'Regex',
            'breakChainOnFailure' => true,
            'options'     => [ 
            
            'pattern'=> '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9\-\s]*)$/',
                'messages' => [
                        'regexNotMatch'=>'Please enter valid leave type.'
                ]
            ]
        ]
    ]); 	
        $allowday = new Zend_Form_Element_Text('allowday');
        $allowday->setLabel('Allowed Day ');      
    
        $leavecategory = new Zend_Form_Element_Select('leavecategory');
        $leavecategory->setLabel('Leave Category');      
        $leavecategory->addValidator('NotEmpty', false, ['messages' => 'Please select Leave Category.']); 
        $leavecategory->setRequired(true);
        $description = new Zend_Form_Element_Textarea('description');
     $description->setLabel("Description");
        $paytype = new Zend_Form_Element_Select('paytype');
        $paytype->addMultiOptions([' '=>'Select...',
        'Paid'=>'Paid',
        'Unpaid'=>'Unpaid',
        'Partial'=>'Partial',
            ]);
            $paytype->setLabel('Pay Type');      
            $paytype->addValidator('NotEmpty', false, ['messages' => 'Please select pay Type.']); 
            $paytype->setRequired(true);
            $duarationtype = new Zend_Form_Element_Select('duarationtype');
            $duarationtype->addMultiOptions([' '=>'Select...',
            'Request'=>'Request',
            'Individual'=>'Individual',
            'Fixed'=>'Fixed',
            'Calculated'=>'Calculated',
                ]);
                $duarationtype->setLabel('Duaration Type');      
                $duarationtype->addValidator('NotEmpty', false, ['messages' => 'Please select duaration Type.']); 
                $duarationtype->setRequired(true);
                $baseperiod = new Zend_Form_Element_Select('baseperiod');
                $baseperiod->addMultiOptions([' '=>'Select...',
                'Yearly'=>'Yearly',
                'Periodic'=>'Periodic',
                    ]);
                    $baseperiod->setLabel('Base Period');      
                    $baseperiod->addValidator('NotEmpty', false, ['messages' => 'Please select base period Type.']); 
                    $baseperiod->setRequired(true);
                    $approval=new Zend_Form_Element_Checkbox('approval');
                    $approval->setLabel('Is Approved Required');
                    // $approval->setValue(1);

                    $ispreset=new Zend_Form_Element_Checkbox('ispreset');
                    $ispreset->setLabel('Is Preset');
                    // $ispreset->setValue(0);
                    // $approval->setValue(1);

                    $isactive = new Zend_Form_Element_Checkbox('isactive');
                    $isactive->setLabel('Active');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements([$id,$paytype,$name,$leavecategory,$description,$isactive,
        $baseperiod,$duarationtype,$allowday,$ispreset,$submit,$approval]);
        $this->setElementDecorators(['ViewHelper']); 
    }
}
?>