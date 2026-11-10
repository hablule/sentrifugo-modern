<?php


class Default_Form_iddocument extends Zend_Form
{
    public function init()
    {
        try {
          
        $this->setMethod('post');
        $this->setAttrib('id','formid');
        $this->setAttrib('name','iddocument');
        $id = new Zend_Form_Element_Hidden('id');
        $name = new Zend_Form_Element_Text('name');
        $name->addValidator('NotEmpty',false,["messages"=>"Name required"]);
        $code = new Zend_Form_Element_Text('code');
        $code->addValidator('NotEmpty',false,['messages'=>'Code required']);
        $description = new Zend_Form_Element_Textarea('description');
        $isnumberd = new Zend_Form_Element_Checkbox('isnumberd');
        $isphotoid = new Zend_Form_Element_Checkbox('isphotoid');
        $iddocumentpurpose_id = new Zend_Form_Element_Select('iddocumentpurpose_id');
        $iddocumentpurpose_id->addValidator('NotEmpty',false,['messages'=>'Document purpose required']);
        $iddocumentcategory = new Zend_Form_Element_Select('iddocumentcategory_id');
        $iddocumentcategory->addMultiOption('','Select category');
        $model = new Default_Model_Documentcategory();	
		$catData = $model->getallcategory();
		foreach ($catData as $cat){
			$iddocumentcategory->addMultiOption($cat['iddocumentcategory_id'],$cat['name']);
	    }

        $model = new Default_Model_Documentpurpose();	
		$catData = $model->getallpurpose();
        $iddocumentpurpose_id->addMultiOption('','Select Type');
		foreach ($catData as $cat){
			$iddocumentpurpose_id->addMultiOption($cat['iddocumentpurpose_id'],$cat['name']);
	    }
        $iddocumenttype_id = new Zend_Form_Element_Select('iddocumenttype_id');
        $iddocumenttype_id->addMultiOption('','Select Type');
        $model = new Default_Model_Documenttype();	
		$catData = $model->getalltype();
		foreach ($catData as $cat){
			$iddocumenttype_id->addMultiOption($cat['iddocumenttype_id'],$cat['name']);
	    }
        $status = new Zend_Form_Element_Select('iddocumentstatus_id');
        $model = new Default_Model_iddocumentstatus();	
		$catData = $model->getallstatus();
        $status->addMultiOption('','Select status');
		foreach ($catData as $cat){
			$status->addMultiOption($cat['iddocumentstatus_id'],utf8_encode($cat['name']));
	    }

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $submit = new Zend_Form_Element_Submit('submit');
       $this->addElements([$id,$name,$code,$description,$iddocumentcategory,$iddocumentpurpose_id,$iddocumenttype_id,$isactive,$isnumberd,$isphotoid,$submit,$status]);
       $this->setElementDecorators(['ViewHelper']); 
          //code...
        } catch (\Throwable $th) {
           Zend_Debug::dump($th->getMessage());
        }
    }
    
    
}



?>