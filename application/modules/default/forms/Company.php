<?php

/**
 * Company Form
 *
 * Manages data entry for top-level organizational Companies.
 * Refactored to comply with SSOT v6.4.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Company extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'company');
        // Crucial: Must be enabled for the logo file upload to work over POST/AJAX
        $this->setAttrib('enctype', 'multipart/form-data');

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

        $ssotRegexValidator = [
            'Regex',
            true,
            [
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => [
                    Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.'
                ]
            ]
        ];

        $lengthValidatorCode = ['StringLength', false, ['max' => 60]];
        $lengthValidatorName = ['StringLength', false, ['max' => 100]];

        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a company code.'])
            ->addValidator($lengthValidatorCode[0], $lengthValidatorCode[1], $lengthValidatorCode[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_company',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $codeVal->setMessage('This company code already exists.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Company Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a company name.'])
            ->addValidator($lengthValidatorName[0], $lengthValidatorName[1], $lengthValidatorName[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_company',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $nameVal->setMessage('This company name already exists.');
        $name->addValidator($nameVal);

        // Registration Number
        $registration_number = new Zend_Form_Element_Text('registration_number');
        $registration_number->setLabel('Registration Number')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '20'])
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('StringLength', false, ['max' => 20]);

        // Website
        $website = new Zend_Form_Element_Text('website');
        $website->setLabel('Website')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '20']) // Consider increasing max-length in schema
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('StringLength', false, ['max' => 20]);

        /* --------------------------------------------------------------
         * 3. Select / MultiSelect Elements (Context Mappings)
         * -------------------------------------------------------------- */

        $representative_id = new Zend_Form_Element_Select('representative_id');
        $representative_id->setLabel('Representative / Org Head')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Representative.']);

        /* --------------------------------------------------------------
         * Multi-Select Elements (The Junction Linkers)
         * -------------------------------------------------------------- */

        // Business Domains
        $businessdomain = new Zend_Form_Element_Multiselect('businessdomain');
        $businessdomain->setLabel('Business Domains')
            ->setAttribs([
                'class' => 'formDataElement',
                'size'  => 5, // Shows 5 rows in standard HTML multiselect
                'title' => 'Select the domains associated with this business unit.'
            ])
            ->setRegisterInArrayValidator(false) 
            ->setRequired(false);         

        /* --------------------------------------------------------------
         * 4. Date Elements
         * -------------------------------------------------------------- */

        $org_start_date = new ZendX_JQuery_Form_Element_DatePicker('org_start_date');
        $org_start_date->setLabel('Organization Start Date')
            ->setAttribs([
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ]);

        /* --------------------------------------------------------------
         * 5. File Upload Elements
         * -------------------------------------------------------------- */

        $org_image = new Zend_Form_Element_File('org_image');
        $org_image->setLabel('Company Logo')
            ->setAttrib('title', 'Upload a company logo. Recommended formats: PNG or JPG.')
            ->addValidator('Extension', false, ['jpg', 'png', 'jpeg', 'gif'])
            ->addValidator('Size', false, 5242880); // 5MB max

        $org_image->getValidator('Extension')->setMessage('Only image files (jpg, png, jpeg, gif) are allowed.');
        $org_image->getValidator('Size')->setMessage('Maximum file size allowed is 5MB.');

        // Safe Directory Creation (SSOT v6.4 Requirement)
        $uploadDir = realpath(APPLICATION_PATH . '/../public/uploads') . DIRECTORY_SEPARATOR . 'organization';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        if (is_dir($uploadDir) && is_writable($uploadDir)) {
            $org_image->setDestination($uploadDir);
        }
        
        /* --------------------------------------------------------------
         * 6. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 7. Checkbox Elements
         * -------------------------------------------------------------- */

        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 8. Submit Element
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // SSOT v6.4 Strict Format for AJAX save binding
        $urlAction  = "'company/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'company\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */

        $this->addElements([
            $idElement,
            $code,
            $name,
            $registration_number,
            $website,
            $representative_id,
            $businessdomain,
            $org_start_date,
            $org_image,
            $description,
            $isactive,
            $submit
        ]);

        // Strip default Zend Decorators per SSOT
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            if ($element instanceof Zend_Form_Element_File) {
                $element->setDecorators(['File']);
            } elseif ($element instanceof ZendX_JQuery_Form_Element_DatePicker) {
                $element->setDecorators(['UiWidgetElement']);
            } else {
                $element->setDecorators(['ViewHelper']);
            }
        }
    }
}
