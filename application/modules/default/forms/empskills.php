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

class Default_Form_empskills extends Zend_Form
{
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('action',BASE_URL.'employee/add');
		$this->setAttrib('id', 'formid');

		$this->setAttrib('name', 'empskills');

		$id = new Zend_Form_Element_Hidden('id');
				
		$emp_id = new Zend_Form_Element_Hidden('emp_id');
				
		$skill_id = new Zend_Form_Element_Select('skill_id');

		$skillname = new Zend_Form_Element_Text('skillname');

		$skillsource_id = new Zend_Form_Element_Select('skillsource_id');
		$measurementindex_id = new Zend_Form_Element_Select('measurementindex_id');

		$measurementvalue = new Zend_Form_Element_Text('measurementvalue');

		$attestation_image_value = new Zend_Form_Element_Hidden('attestation_image_value');		
		$imgerr = new Zend_Form_Element_Hidden('imgerr');
		$imgerrmsg = new Zend_Form_Element_Hidden('imgerrmsg');
		$isactive = new Zend_Form_Element_Checkbox('isactive');

        
		
	

        $acquiredsince = new ZendX_JQuery_Form_Element_DatePicker('acquiredsince');
		$acquiredsince->setOptions(array('class' => 'brdr_none'));	
		$acquiredsince->setAttrib('readonly', 'true');	
		$acquiredsince->setAttrib('onfocus', 'this.blur()');
		$acquiredsince->setRequired(true);
        $acquiredsince->addValidator('NotEmpty', false, array('messages' => 'Please fill the date.'));
        
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$submit->setLabel('Save');
		$description = new Zend_Form_Element_Textarea('description');
		$description->setLabel("Description");
		$this->addElements(array($id,$emp_id,$skillname,$skill_id,$description,$isactive,$skillsource_id,$measurementindex_id,$measurementvalue,$attestation_image_value,$imgerr,$imgerrmsg,$acquiredsince,$submit));
        $this->setElementDecorators(array('ViewHelper'));
		$this->setElementDecorators(array('File'),array('org_image'));

         $this->setElementDecorators(array(
                    'UiWidgetElement',
        ),array('acquiredsince')); 		
	}
}