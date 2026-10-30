<?php

/**
 * Menu Form
 * 
 * Manages the properties of application navigation menus.
 * Privilege assignment is intentionally excluded (handled by the Roles module).
 * 
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 */
class Default_Form_Menu extends Zend_Form
{
    /**
     * Initialize the Menu Form
     */
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'menu');

        // Request context for unique validation exclusions
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */

        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Text Elements
         * -------------------------------------------------------------- */

        // Menu Name
        $name = new Zend_Form_Element_Text('menuName');
        $name->setLabel('Menu Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a Menu Name.']);

        // URL Path
        $url = new Zend_Form_Element_Text('url');
        $url->setLabel('URL Path')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '255'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Please enter a URL.']);

        // Unique URL Validator (Prevent routing conflicts)
        // Note: '/#' is often used as a placeholder for parent menus, so we allow duplicates of '/#'
        $urlValidator = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_menu',
            'field'   => 'url',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $urlValidator->setMessage('This URL is already assigned to another menu.');

        // Custom logic to bypass unique check if URL is exactly '/#'
        if (trim($request->getParam('url')) !== '/#') {
            $url->addValidator($urlValidator);
        }

        // Description
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        // Help Text
        $helpText = new Zend_Form_Element_Textarea('helpText');
        $helpText->setLabel('Help Text')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');
        
        // Tooltip
        $toolTip = new Zend_Form_Element_Text('toolTip');
        $toolTip->setLabel('Tooltip')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '255', 'rows' => 1])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        // Menu Order`
        $menuOrder = new Zend_Form_Element_Text('menuOrder');
        $menuOrder->setLabel('Menu Order')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '5'])
            ->addValidator('Digits', true, ['messages' => 'Please enter numbers only.']);

        // Segment Flag
        $segmentFlag = new Zend_Form_Element_Select('segment_flag');
        $segmentFlag->setLabel('Segment Flag')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(['1' => '1', '2' => '2']); // Assuming these are the standard Sentrifugo options

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        // Parent Menu
        $parent = new Zend_Form_Element_Select('parent');
        $parent->setLabel('Parent Menu')
            ->setAttrib('class', 'formDataElement');
        // Options populated in Controller        

        // Menu Group (Visual Dashboard Category)
        $menugroup_id = new Zend_Form_Element_Select('menugroup_id');
        $menugroup_id->setLabel('Menu Group (Dashboard Category)')
            ->setRequired(true)
            ->setAttrib('class', 'formDataElement')
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Menu Group.']);
        // Options populated in Controller

        // Module Name (Physical Code Location)
        $module_name = new Zend_Form_Element_Select('modulename');
        $module_name->setLabel('Module (Code Location)')
            ->setRequired(true)
            ->setAttrib('class', 'formDataElement')
            ->addValidator('NotEmpty', true, ['messages' => 'Please select a Module.']);
        // Options populated in Controller

        /* --------------------------------------------------------------
         * 4. Checkbox Elements
         * -------------------------------------------------------------- */

        // Active Status
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        // Default Status
        $isdefault = new Zend_Form_Element_Checkbox('isdefault');
        $isdefault->setLabel('Default')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');



        /* --------------------------------------------------------------
         * 5. File Upload Element
         * -------------------------------------------------------------- */

        // Icon Path (File Upload - SSOT Standard)
        $iconPath = new Zend_Form_Element_File('iconPath');
        $iconPath->setLabel('Icon Image')
            ->setAttrib('title', 'Upload a small icon image.')
            ->addValidator('Extension', false, ['jpg', 'png', 'gif', 'svg'])
            ->addValidator('Size', false, 2048000); // 2MB max

        $uploadDir = realpath(APPLICATION_PATH . '/../public/uploads') . DIRECTORY_SEPARATOR . 'menuIcons';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
        if (is_dir($uploadDir) && is_writable($uploadDir)) {
            $iconPath->setDestination($uploadDir);
        }
        $iconPath->getValidator('Extension')->setMessage('Only image files (jpg, png, gif, svg) allowed.');
        $iconPath->getValidator('Size')->setMessage('Maximum file size allowed is 2MB.');

        /* --------------------------------------------------------------
         * 6. Submit Elements
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs([
                'id' => 'submitbutton',
                'class' => 'actbtn_btn'
            ]);

        // SSOT AJAX Submission Pattern
        $urlAction  = "'menu/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'menu\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");



        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $name,
            $url,
            $parent,
            $menugroup_id,
            $module_name,
            $description,
            $helpText,
            $toolTip,
            $iconPath,
            $menuOrder,
            $segmentFlag, 
            $isactive,
            $isdefault,
            $submit
        ]);

        // Ensure File Decorator is applied to the upload field
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            if (!$element instanceof Zend_Form_Element_File) {
                $element->setDecorators(['ViewHelper']);
            }
        }
        
        $iconPath->setDecorators(['File']);
    }
}
