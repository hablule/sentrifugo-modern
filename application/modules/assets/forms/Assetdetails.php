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
class Assets_Form_Assetdetails extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		// $this->setAttrib('action',BASE_URL.'assets/assets/edit');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'addOrEditAssets');
        
		$id = new Zend_Form_Element_Hidden('id'); 
		$id->setValue(Zend_Controller_Front::getInstance()->getRequest()->getParam("id"));
		$uomid = new Zend_Form_Element_Hidden('uomid');          
         
    
        $isinstant = new Zend_Form_Element_Text('isinstant');          

		
		$asset_id = new Zend_Form_Element_Select('asset_id');
        $asset_id->setAttrib('onchange', 'displayParticulardomain_normalinstant("asset_id","assetinstant_id","","uom_id","add","")');

		$asset_id->setLabel('Asset');
		$asset_id->addMultiOption('','Select Asset ');
		$AssetCategoriesModel = new Assets_Model_Assets();	
		$catData = $AssetCategoriesModel->fetchAll('isactive="Y" ','name');
		foreach ($catData as $cat){
			$asset_id->addMultiOption($cat['asset_id'],utf8_encode($cat['name']));
	    }
		$asset_id->setRegisterInArrayValidator(false);
      
		$assetinstant_id = new Zend_Form_Element_Select('assetinstant_id');
		$assetinstant_id->setLabel('Type');
		$assetinstant_id->setRequired(TRUE);
		$assetinstant_id->addMultiOption('','Select Asset Instant');
		$assetinstant_id->addValidator('NotEmpty', true, ['messages' => 'Please select Asset type.']); 
		// $AssetCategoriesModel2 = new Assets_Model_Assetinstant();	
		// $catData2 = $AssetCategoriesModel2->fetchAll('isactive="Y" ','serialidentification');
		// foreach ($catData2 as $cat2){
		// 	$assetinstant_id->addMultiOption($cat2['assetinstant_id'],utf8_encode($cat2['serialidentification']));
	    // }
		$assetinstant_id->setRegisterInArrayValidator(false);
		$uom_id = new Zend_Form_Element_Select('uom_id');
		$uom_id->setLabel('UOM');
		// $uom_id->setRequired(TRUE);
		$uom_id->setAttrib('readonly', 'true');	

		$uom_id->addMultiOption('','Select Uom');
		$uom_id->addValidator('NotEmpty', true, ['messages' => 'Please select Uom.']); 
		$AssetCategoriesModel3 = new Assets_Model_Uom();	
		$catData3 = $AssetCategoriesModel3->fetchAll('isactive="Y" ','name');
		foreach ($catData3 as $cat3){
			$uom_id->addMultiOption($cat3['uom_id'],utf8_encode($cat3['name']));
	    }
		$uom_id->setRegisterInArrayValidator(false);
        $returndate = new ZendX_JQuery_Form_Element_DatePicker('returndate');
		$returndate->setOptions(['class' => 'brdr_none']);	
		$returndate->setAttrib('readonly', 'true');	
		$returndate->setAttrib('onfocus', 'this.blur()');
        $issueddate = new ZendX_JQuery_Form_Element_DatePicker('issueddate');
		$issueddate->setOptions(['class' => 'brdr_none']);	
		$issueddate->setAttrib('readonly', 'true');	
		$issueddate->setAttrib('onfocus', 'this.blur()');
		$issueddate->setRequired(true);
        $issueddate->addValidator('NotEmpty', false, ['messages' => 'Please fill the date.']);
        $Text2 = new Zend_Form_Element_Text("description");
        $Text2->setLabel('Description');
        $isactive = new Zend_Form_Element_Checkbox("isactive");
        $isactive->setLabel("Active");
        $isactive->setValue(1);

        $isactive->setRequired(true);
		$quantity = new Zend_Form_Element_Text("quantity");
        $quantity->addValidator('NotEmpty', true, ['messages' => 'Please enter Precision.']); 
		$quantity->addValidator("regex",true,[                           
						'pattern'=>'/^[0-9]+$/',
                           'messages'=>[
                               'regexNotMatch'=>'Enter numbers only.'
                           ]
        			]);
		$quantity->setRequired(TRUE);	
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');

		$this->addElements(
						
								[$isinstant,$uomid,
								$id,$Text2, $asset_id,$assetinstant_id,$quantity,
								$isactive,$uom_id,$issueddate,$returndate,$submit
								]
							);
                            $this->setElementDecorators(['ViewHelper']);
                    
                             $this->setElementDecorators([
                                        'UiWidgetElement',
                            ],['issueddate','returndate']);	
	}
	
}