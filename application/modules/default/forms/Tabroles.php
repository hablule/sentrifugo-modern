<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_Form_Tabroles extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'tabroles');

        $id = new Zend_Form_Element_Hidden('id');
		
        $name = new Zend_Form_Element_Text('name');
        $name->setAttrib('maxLength', 50);
        $name->setAttrib('title', 'Role name'); 
        $name->setAttrib("disabled","disabled");   
	    $code = new Zend_Form_Element_Text('code');
        $code->setAttrib("disabled","disabled");   

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setAttrib("disabled","disabled");   


        $levelid = new Zend_Form_Element_Hidden('levelid');
                    
    //     $levelid->addFilter(new Zend_Filter_StringTrim());
    //    // $levelid->setRequired(true);
    //     $levelid->addValidator('NotEmpty', false, array('messages' => 'Please select level.'));          
        
    //     $istimeActive = Zend_Controller_Front::getInstance()->getRequest()->getParam('istimeactive');
    //     $prev_cnt = new Zend_Form_Element_Hidden('prev_cnt');
    //     $prev_cnt->setRequired(true);
    //     if($istimeActive)
    //     	$prev_cnt->addValidator('NotEmpty', false, array('messages' => 'Please select privileges other than time management.'));
    //     else	
    //     	$prev_cnt->addValidator('NotEmpty', false, array('messages' => 'Please select privileges.'));  
        
        $submit = new Zend_Form_Element_Submit('submit');
        
        $submit->setAttrib('id', 'submitbutton');
        $submit->setLabel('Save');

        $url = "'tabroles/saveupdate/format/json'";
        $dialogMsg = "''";
        $toggleDivId = "''";
        $jsFunction = "'redirecttocontroller(\'tabroles\');'";;
		 
        $submit->setOptions(['onclick' => "saveDetails($url,$dialogMsg,$toggleDivId,$jsFunction);"
                        ]);

        $this->addElements([$id,$name,$code,$levelid,$isactive,$submit,$prev_cnt]);
        $this->setElementDecorators(['ViewHelper']); 
    }
}