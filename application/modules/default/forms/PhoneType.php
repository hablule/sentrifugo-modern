<?php

/**
 * Phone Type Form
 *
 * Manages data entry for Phone Line Types (e.g., Mobile, Landline, Fax).
 * Complies with SSOT v6.6 (Zend_Form, Regex Validation, Balanced Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Phonetype extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'phonetype');

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
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a code.'])
            ->addValidator($lengthValidatorCode[0], $lengthValidatorCode[1], $lengthValidatorCode[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_phone_type',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $codeVal->setMessage('This code already exists.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Type Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a type name.'])
            ->addValidator($lengthValidatorName[0], $lengthValidatorName[1], $lengthValidatorName[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_phone_type',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $nameVal->setMessage('This name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * 3. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 4. Checkbox Elements
         * -------------------------------------------------------------- */
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

        // SSOT v6.6 "Balanced Approach": 
        // This is a simple form (no files, no complex JS validation), so we use the standard helper.
        $urlAction  = "'phonetype/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'phonetype\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $code,
            $name,
            $description,
            $isactive,
            $submit
        ]);

        // Strip default Zend Decorators per SSOT
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
