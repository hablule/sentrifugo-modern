<?php 

class Default_ValidatorController {
    public function createValidator($type){
        $validator = null;
        if($type == 'NotEmpty'){
            $validator = new Zend_Validate_NotEmpty();
        }else if($type == 'EmaileAddress'){
            $validator = new Zend_Validate_EmailAddress();
        }
        return $validator;
    }
} 
?>