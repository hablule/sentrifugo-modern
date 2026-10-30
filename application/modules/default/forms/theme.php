<?php
class Default_Form_theme extends Zend_Form{
    public function init(){
        $this->setMethod('post');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'theme');
        $id = new Zend_Form_Element_Hidden('id');
        $submit = new Zend_Form_Element_Submit('submit');
        $name = new Zend_Form_Element_Text('name');
        $name->addValidator('NotEmpty', false, array('messages' => 'Please fill the Theme Name.')); 
        $name->setRequired(true);
        $name->addValidator(new Zend_Validate_Db_NoRecordExists(
            array(
                'table'=>'main_theme',
                'field'=>'theme_name'
            )
            ));
      $name->getValidator('Db_NoRecordExists')->setMessage('Name already exists.');
      $name->addValidators(array(
        array(
            'validator'   => 'Regex',
            'breakChainOnFailure' => true,
            'options'     => array( 
            
            'pattern'=> '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9\-\s]*)$/',
                'messages' => array(
                        'regexNotMatch'=>'Please enter valid leave type.'
                )
            )
        )
    )); 
        $font=new Zend_Form_Element_Select('font');
     
$font->addMultiOptions(array(
    " "=> "Select...",
    'Roboto Regular' => "Roboto Regular",
    "nexalight"=>"nexalight",
     "Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif'" => 'Impact Haettenschweiler',
    "Arial, Helvetica, sans-serif" => "Arial Helvetica",
    "'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif" => "Trebuchet MS",
"-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif"=>"-apple-system  BlinkMacSystemFont",
"Cambria, Cochin, Georgia, Times, 'Times New Roman', serif"=>"Cambria Cochin",
"'Courier New', Courier, monospace"=>"Courier New",
"'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif"=>"Gill Sans",
));
        $font->addValidator('NotEmpty', false, array('messages' => 'Please Select Font.')); 
        $font->setRequired(true);
        $menustyle=new Zend_Form_Element_Select('menustyle');
        $menustyle->addMultiOptions(array(
            ' ' => 'Select...',
            'top_left' => 'Top Left Menu',
            'bottom_left' => 'Bottom Left Menu',
            'top_right' => 'Top Right Menu',
            'bottom_right' => 'Bottom Right Menu',
            'flexmenu' => 'Flex Menu',
            'style1'=>'style 1',
            'style2'=>'style 2',
            'style3'=>'style 3',
          
        ));
       $menustyle->addValidator('NotEmpty', false, array('messages' => 'Please Menu Style.')); 
       $menustyle->setRequired(true);
       $menuorentation=new Zend_Form_Element_Select('menuorentation');
       $menuorentation->addMultiOptions(array(
           ' ' => 'Select...',
           'menu_only' => 'Menu Name Only',
           'icon_only' => 'Icon Only',
           'both_menu_icon' => 'Both',
          
         
       ));
       $menuorentation->addValidator('NotEmpty', false, array('messages' => 'Please fill the field.')); 
       $menuorentation->setRequired(true);
       $submenudirection=new Zend_Form_Element_Select('submenudirection');
       $submenudirection->addMultiOptions(array(
           ' ' => 'Select...',
           'left' => 'left',
           'right' => 'right',

       ));
      $submenudirection->addValidator('NotEmpty', false, array('messages' => 'Please Sub Menu Direction.')); 
      $submenudirection->setRequired(true);
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isdefault = new Zend_Form_Element_Checkbox('isdefault');
		 $this->addElements(array($id,$name,$submit,$font,$menustyle,$isactive,$isdefault,$menuorentation));
         $this->setElementDecorators(array('ViewHelper')); 
    }
}
?>