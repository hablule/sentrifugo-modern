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
class Assets_Form_Assets extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('action',BASE_URL.'assets/assets/edit');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'addOrEditAssets');
        
		$id = new Zend_Form_Element_Hidden('id');          
        
		
		$assetcategory_id = new Zend_Form_Element_Select('assetcategory_id');
		$assetcategory_id->setLabel('Category');
		$assetcategory_id->setRequired(TRUE);
		$assetcategory_id->addMultiOption('','Select Asset Category');
		$assetcategory_id->addValidator('NotEmpty', true, array('messages' => 'Please select Asset category.')); 
		$AssetCategoriesModel = new Assets_Model_Assetcategory();	
		$catData = $AssetCategoriesModel->fetchAll('isactive="Y" ','name');
		foreach ($catData as $cat){
			$assetcategory_id->addMultiOption($cat['assetcategory_id'],utf8_encode($cat['name']));
	    }
		$assetcategory_id->setRegisterInArrayValidator(false);
      
		$assettype_id = new Zend_Form_Element_Select('assettype_id');
		$assettype_id->setLabel('Type');
		$assettype_id->setRequired(TRUE);
		$assettype_id->addMultiOption('','Select Asset Type');
		$assettype_id->addValidator('NotEmpty', true, array('messages' => 'Please select Asset type.')); 
		$AssetCategoriesModel2 = new Assets_Model_Assettype();	
		$catData2 = $AssetCategoriesModel2->fetchAll('isactive="Y" ','name');
		foreach ($catData2 as $cat2){
			$assettype_id->addMultiOption($cat2['assettype_id'],utf8_encode($cat2['name']));
	    }
		$assettype_id->setRegisterInArrayValidator(false);
		$uom_id = new Zend_Form_Element_Select('uom_id');
		$uom_id->setLabel('UOM');
		$uom_id->setRequired(TRUE);
		$uom_id->addMultiOption('','Select Uom');
		$uom_id->addValidator('NotEmpty', true, array('messages' => 'Please select Uom.')); 
		$AssetCategoriesModel3 = new Assets_Model_Uom();	
		$catData3 = $AssetCategoriesModel3->fetchAll('isactive="Y" ','name');
		foreach ($catData3 as $cat3){
			$uom_id->addMultiOption($cat3['uom_id'],utf8_encode($cat3['name']));
	    }
		$uom_id->setRegisterInArrayValidator(false);
        $Text0 = new Zend_Form_Element_Text("name");
		$Text0->setLabel('Name');

        $Text0->addValidator('NotEmpty',false,array('messages'=>'Name required'));
        $Text0->setRequired(true);
        $Text1 = new Zend_Form_Element_Text("code");
		$Text1->setLabel('Code');

        $Text1->addValidator('NotEmpty',false,array('messages'=>'Code required'));
        $Text1->setRequired(true);
        
        $Text2 = new Zend_Form_Element_Text("description");
        $Text2->setLabel('Description');
        $isactive = new Zend_Form_Element_Checkbox("isactive");
        $isactive->setLabel("Active");
        $isactive->setValue(1);

        $isactive->setRequired(true);

        $Text1->addValidator(new Zend_Validate_Db_NoRecordExists(
            array('table'=>'main_asset',
                      'field'=>'code',
                    'exclude'=>'asset_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
               ) )  
        );
        
        $Text1->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
        $Text1 ->setAttrib('maxlength', '25');
        $Text0 ->setAttrib('maxlength', '60');
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		$this->addElements(
						array
								(
								$id,$Text0, $Text1,$Text2,
								$isactive,$assetcategory_id,$assettype_id,$uom_id,$submit
								)
							);
        $this->setElementDecorators(array('ViewHelper')); 
	
	}
	
}