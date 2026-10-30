<?php

/**
 * Holiday Category Form
 *
 * Manages data entry for Holiday lookup classifications (e.g., Religious, National).
 * Complies with SSOT v6.7.7 (Zend_Form, Strict Regex, Balanced Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Holidaycategory extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'holidaycategory');

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

        // SSOT Mandatory Regex: Must contain letters, no leading space, no special chars
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

        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Category Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., PUBLIC_HOLIDAY'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a category code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Unique Validation for Code
        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_holiday_category',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $codeVal->setMessage('This code is already assigned to another category.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Category Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Public Holiday'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a category name.'])
            ->addValidator('StringLength', false, ['max' => 100])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Unique Validation for Name
        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_holiday_category',
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

        // SSOT v6.7.7: Strict String Quoting for standard AJAX saveDetails()
        $urlAction  = "'holidaycategory/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'holidaycategory\\');'";
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

        // SSOT v6.7.7: Strip default Zend Decorators
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(['ViewHelper']);
        }
    }
}
