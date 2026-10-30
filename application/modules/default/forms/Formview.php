<?php




class Default_Form_Formview extends Zend_Form
{

    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('action',BASE_URL.'formview');
        //formview/updateelement
        $this->setAttrib('name','Formview');
        $id = new Zend_Form_Element_Hidden('id');
      

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save and Next');
        $this->addElements([$id,$submit]);
        $this->setElementDecorators(['ViewHelper']);  

    }

}



?>