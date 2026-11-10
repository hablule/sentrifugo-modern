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
/**
 * 
 * Create or Edit Assets Form.
 * @author Sagarsoft
 *
 */
class Assets_Form_Assetinstant extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('action',BASE_URL.'assets/assetinstant/edit');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'addOrEditAssets');
        
		$id = new Zend_Form_Element_Hidden('id');          
        
		
		$asset_id = new Zend_Form_Element_Select('asset_id');
		$asset_id->setLabel('Asset');
		$asset_id->addMultiOption('','Select Asset ');
		$AssetCategoriesModel = new Assets_Model_Assets();	
		$catData = $AssetCategoriesModel->fetchAll('isactive="Y" ','name');
		foreach ($catData as $cat){
			$asset_id->addMultiOption($cat['asset_id'],utf8_encode($cat['name']));
	    }
		$asset_id->setRegisterInArrayValidator(false);
      
	
       
        $serialidentification = new Zend_Form_Element_Text("serialidentification");
		$serialidentification->setLabel('Serial Identification');

        $serialidentification->addValidator('NotEmpty',false,['messages'=>'serialidentification required']);
        $serialidentification->setRequired(true);
        
        $Text2 = new Zend_Form_Element_Text("description");
        $Text2->setLabel('Description');
        $isactive = new Zend_Form_Element_Checkbox("isactive");
        $isactive->setLabel("Active");

        $isactive->setRequired(true);
        $isactive->setValue(1);

        $serialidentification->addValidator(new Zend_Validate_Db_NoRecordExists(
            ['table'=>'main_assetinstant',
                      'field'=>'serialidentification',
                    'exclude'=>'assetinstant_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
               ] )  
        );
        
        $serialidentification->getValidator('Db_NoRecordExists')->setMessage('serialidentification already exists.');
        $serialidentification ->setAttrib('maxlength', '200');
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		$this->addElements(
						
								[
								$id,$serialidentification ,$Text2,
								$isactive,$asset_id,$submit
								]
							);
        $this->setElementDecorators(['ViewHelper']); 
	
	}
	
}