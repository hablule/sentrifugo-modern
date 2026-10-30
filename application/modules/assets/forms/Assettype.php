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
 * Create or Edit Clients Form.
 * @author Sagarsoft
 *
 */
class Assets_Form_Assettype extends Zend_Form
{
	public function init()
	{
		
		
        $this->setMethod('post');
		$this->setAttrib('action',BASE_URL.'assets/assettype/edit');
		$this->setAttrib('id', 'formid');
		$this->setAttrib('name', 'addOrEditAssets');


        $id = new Zend_Form_Element_Hidden('id');
        
        
        
        $Text0 = new Zend_Form_Element_Text("name");
        $Text0->addValidator('NotEmpty',false,array('messages'=>'Name required'));
        $Text0->setRequired(true);
        $Text1 = new Zend_Form_Element_Text("code");
        $Text1->addValidator('NotEmpty',false,array('messages'=>'Code required'));
        $Text1->setRequired(true);
        
        $Text2 = new Zend_Form_Element_Text("description");
        $Text2->setLabel('Description');
        $isactive = new Zend_Form_Element_Checkbox("isactive");

        $isactive->setRequired(true);
        $isactive->setValue(1);

        $Submit4 = new Zend_Form_Element_Submit("submit");
        $Text1->addValidator(new Zend_Validate_Db_NoRecordExists(
            array('table'=>'main_assettype',
                      'field'=>'code',
                    'exclude'=>'assettype_id!="'.Zend_Controller_Front::getInstance()->getRequest()->getParam('id').'"',    
               ) )  
        );
        
        $Text1->getValidator('Db_NoRecordExists')->setMessage('Code already exists.');
        $Text1 ->setAttrib('maxlength', '25');
        $Text0 ->setAttrib('maxlength', '60');
        $this->addElements(array($id,$Text0,$Text1,$Text2,$isactive,$Submit4));
        $this->setElementDecorators(array('ViewHelper')); 
        } 
         } 
         ?>