<?php
class Default_Form_Menucreate extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'menucreate');


        $id = new Zend_Form_Element_Hidden('id');

        $name = new Zend_Form_Element_Text('menu_name');
        $name->setLabel("Menu Name");
        $name->setRequired(true);
        $name->addValidator('NotEmpty', false, ['messages' => 'Please Insert Menu Name']); 
      
          
        $url = new Zend_Form_Element_Text('url');
        $url->setLabel("URL");
        $url->setRequired(true);
        $url->addValidator('NotEmpty', false, ['messages' => 'Please Insert Url']); 
        $parent = new Zend_Form_Element_Select('parent');
        $parent->setLabel('Parent Menu');
        
        $module_name = new Zend_Form_Element_Select('module_name');
        $module_name->setLabel('Module');
        $module_name->setRequired(true);
        $module_name->addValidator('NotEmpty', false, ['messages' => 'Please select module.']); 
        $role = new Zend_Form_Element_Select('role');
        $role->setLabel('Role');
        $role->setRequired(true);
        $role->addValidator('NotEmpty', false, ['messages' => 'Please select Role.']); 

        $add = new Zend_Form_Element_Checkbox('add');
        $add->setLabel('Add');
     $edit = new Zend_Form_Element_Checkbox('edit');
        $edit->setLabel('Edit');

        $delete = new Zend_Form_Element_Checkbox('delete');
        $delete->setLabel('Delete');

        $view = new Zend_Form_Element_Checkbox('view');
        $view->setLabel('View');

        $viewatt = new Zend_Form_Element_Checkbox('viewatt');
        $viewatt->setLabel('View Attachments');

        $uploadatt = new Zend_Form_Element_Checkbox('uploadatt');
        $uploadatt->setLabel('Upload Attachments');
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Create');

        $this->addElements([
            $id, $name, $url, $parent, $submit, 
            $role, $add, $edit, $delete, $view, $viewatt, $uploadatt,$module_name,$isactive,
        ]);
        $this->setElementDecorators(['ViewHelper']);
    }
}
