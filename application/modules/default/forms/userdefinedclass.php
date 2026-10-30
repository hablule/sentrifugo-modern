<?php


class Default_Form_userdefinedclass extends Zend_Form
{
    public function __construct($number=0,$iscreated=false)
    {   
        $this->createfields($number,$iscreated);
    }
    public function createfields($number,$iscreated)
    {
        $fields = [];
        $namearray = [];
        $id = new Zend_Form_Element_Hidden('id');
        $names = new Zend_Form_Element_Hidden('names');
        $id->setValue($number);
        
        for ($i=0; $i < $number; $i++) { 
            $text = new Zend_Form_Element_Text('tst'.$i);
            $fields[] = $text;
            $namearray[]='tst'.$i;
            if(($i+1)==$number){
                $submit = new Zend_Form_Element_Submit('submit');
                $fields[] = $submit;
                $namearray[]='submit';
            }
        }
        if (!$iscreated) {
            $classfunname = new Zend_Form_Element_Select('classfun');
            $classfunname->setLabel('Method');
            $parameters = new Zend_Form_Element_Text('parameters');
            $classPath = new Zend_Form_Element_Text('classPath');
          
            $fields[] = $parameters;  
            $fields[] = $classfunname;  
            $fields[] = $classPath;
        }
       
       $names->setValue(json_encode($namearray));
        $fields[] = $id;
        $fields[] = $names;
           
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','userdefined');
        $this->addElements($fields);

    }
}

?>