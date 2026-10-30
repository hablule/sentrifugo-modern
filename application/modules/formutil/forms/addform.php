<?php

class Formutil_Form_Addform extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','formindex');
        $id = new Zend_Form_Element_Hidden('id');
        $modules = new Zend_Form_Element_Text('modules');
        $formname = new Zend_Form_Element_Text('formname');
        $noofele = new Zend_Form_Element_Text('noofele');
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElements([$id,$modules,$formname,$noofele,$submit]);
        $this->setElementDecorators(['ViewHelper']);

    }

    public function moreelements($noofele,$form)
    {
      $ele = [];
      $eletype = [];
      
      
            /* $elename = new Zend_Form_Element_Text('elementname'.$noofele); */
            for ($i=0; $i < $noofele ; $i++) 
                {
                    $select = new Zend_Form_Element_Select('elementtype'.$i);
                    $select->setLabel('element type');
                    $select->addValidator('NotEmpty',false,['please fill data']);
                    $select->setRequired(true);
                    $select = $this->populateselectoption($select);
                    $ele['type'.$i] = $select;
                    $name = new Zend_Form_Element_Text('elename'.$i);
                    $name->addValidator('NotEmpty',false,['please fill data']);
                    $name->setRequired(true);
                    $name->setLabel('element name');
                    $ele['name'.$i] = $name;
                      
                }
        return $ele;
    }

    public function populateselectoption($select)
    {
        $formmodel = new Formutil_Model_addform();
        $data = $formmodel->geteletype();
        
        foreach ($data as $value) {
            $select->addMultiOptions([$value['dataType']=>$value['description']]);
        }
        return $select;
    }
}


?>