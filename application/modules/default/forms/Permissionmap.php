<?php

/**
 * Permission Map Form
 * 
 * Manages the editing of specific action-to-permission mappings.
 * Strictly adheres to SSOT v4.5 for form construction, decorators, state mapping,
 * and standard Sentrifugo AJAX submission handlers.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Permissionmap extends Zend_Form
{
    /**
     * Initializes the Permission Mapping form elements.
     * 
     * @return void
     */
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration (SSOT VI)
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'permissionmap');

        // Request context for ID retrieval
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Text Elements (Read-Only Context)
         * -------------------------------------------------------------- */
        $displayModule = new Zend_Form_Element_Text('display_module');
        $displayModule->setLabel('Module')
            ->setAttribs(array('disabled' => 'disabled', 'class' => 'formDataElement'));

        $displayController = new Zend_Form_Element_Text('display_controller');
        $displayController->setLabel('Controller')
            ->setAttribs(array('disabled' => 'disabled', 'class' => 'formDataElement'));

        $displayAction = new Zend_Form_Element_Text('display_action');
        $displayAction->setLabel('Action (Method)')
            ->setAttribs(array('disabled' => 'disabled', 'class' => 'formDataElement'));

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */
        $permission = new Zend_Form_Element_Select('permission');
        $permission->setLabel('Required Privilege')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                'view'       => 'View',
                'add'        => 'Add',
                'edit'       => 'Edit',
                'delete'     => 'Delete',
                'upload'     => 'Upload',
                'viewupload' => 'View Upload'
            ))
            ->setRequired(true)
            ->addValidator('NotEmpty', true, array('messages' => 'Please select a required privilege.'));

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Checkbox Elements (SSOT IX State Mapping)
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 6. Submit Element (SSOT VI Standard AJAX Handler)
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(array(
                'id'      => 'submitbutton',
                'class'   => 'actbtn_btn'
            ));

        // Define Sentrifugo Standard AJAX Callback
        $urlAction  = "'permissionmap/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'permissionmap\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form & Apply Decorators (SSOT VI)
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $displayModule,
            $displayController,
            $displayAction,
            $permission,
            $description,
            $isactive,
            $submit
        ));

        // Strip default Zend Decorators and enforce ViewHelper universally
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
