<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
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
 * Sentrifugo - Open Source HRMS
 *
 * @category   Sentrifugo
 * @package    Default
 * @subpackage Forms
 * @version    Sentrifugo v3.2
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @modified  Sentrifugo v3.2
 * @date	  June 2024
 */
class Default_Form_Menugroup extends Zend_Form
{
    /**
     * Initialize the Menu Group Form
     */
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'menugroup');
        // Critical: Required for file uploads to work
        $this->setAttrib('enctype', 'multipart/form-data');

        // Request context for unique validation exclusions
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */

        $idElement = new Zend_Form_Element_Hidden('id');

        /* --------------------------------------------------------------
         * 2. Text & Color Elements
         * -------------------------------------------------------------- */

        // Group Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Group Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Group Name is required.']);

        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_menugroup',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $nameVal->setMessage('This Group Name already exists.');
        $name->addValidator($nameVal);

        // Group Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Group Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', true, ['messages' => 'Group Code is required.']);

        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_menugroup',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $codeVal->setMessage('This Group Code already exists.');
        $code->addValidator($codeVal);

        // Background Color (We will use JS to convert this to an HTML5 color picker)
        $color = new Zend_Form_Element_Text('backgroundcolor');
        $color->setLabel('Theme Color')
            ->setAttrib('title', 'Choose a color to represent this menu group. This will be used for visual grouping in the UI.')
            ->setAttribs([
                'class' => 'formDataElement',
                'id'    => 'color_picker_input', // Added specific ID
                'style' => 'height: 35px; padding: 0 5px; width: 60px; cursor: pointer;'
            ])
            ->setValue('#26a69a') // Default color
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 3. File Upload Element
         * -------------------------------------------------------------- */       

        // Icon Upload
        $icon = new Zend_Form_Element_File('iconpath');
        $icon->setLabel('Icon Image')
            ->setAttrib('title', 'Upload a small square image (e.g., 50x50px). Recommended formats: PNG or SVG with a transparent background.')
            // Allow images only
            ->addValidator('Extension', false, ['jpg', 'png', 'gif', 'jpeg', 'svg'])
            // Restrict to 2MB to prevent abuse
            ->addValidator('Size', false, 2048000);

        
        // Ensure directory exists
        $uploadDir = realpath(APPLICATION_PATH . '/../public/uploads') . DIRECTORY_SEPARATOR . 'menuGroupIcons';

        // If the public/uploads directory exists but menuGroupIcons doesn't, create it
        if (!is_dir($uploadDir)) {
            // @ suppresses warnings if permissions block creation
            @mkdir($uploadDir, 0777, true);
        }

        // Only set destination if the directory was successfully verified/created
        // Otherwise, Zend will throw a fatal exception. The controller will handle
        // the failure later if the directory is truly unwritable.
        if (is_dir($uploadDir) && is_writable($uploadDir)) {
            $icon->setDestination($uploadDir);
        }

        // Change error messages for better UX
        $icon->getValidator('Extension')->setMessage('Only image files (jpg, png, gif, svg) are allowed.');
        $icon->getValidator('Size')->setMessage('Maximum file size allowed is 2MB.');

        /* --------------------------------------------------------------
         * 4. Textarea & Checkbox Elements
         * -------------------------------------------------------------- */

        // Description
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        // Status
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 5. Submit Element
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // SSOT AJAX Submission pattern
        // $url        = "'menugroup/save/format/json'";
        // $jsFunction = "'redirecttocontroller(\\'menugroup\\');'";

        // Use 'formid' and 'return false;' to trigger Sentrifugo script cleanly
        // $submit->setAttrib('onclick', "saveDetails($url, 'formid', '', $jsFunction); return false;");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $name,
            $code,
            $color,
            $icon,
            $description,
            $isactive,
            $submit
        ]);

        // 1. Clear default decorators for all elements first
        $this->clearDecorators();

        // 2. Set standard 'ViewHelper' for all elements EXCEPT the File upload
        foreach ($this->getElements() as $element) {
            if (!$element instanceof Zend_Form_Element_File) {
                $element->setDecorators(['ViewHelper']);
            }
        }

        // 3. Set the specific 'File' decorator for the icon upload
        $icon->setDecorators([
            'File'
        ]);

        // Note: The form tags themselves (like <dt>, <dd>, <label>) are handled 
        // by the custom HTML structure in save.phtml, so we only need the base 
        // input tags from Zend (ViewHelper/File).
    }
}
