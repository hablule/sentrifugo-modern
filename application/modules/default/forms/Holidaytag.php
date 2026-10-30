<?php

/**
 * Holiday Tag Form
 *
 * Manages data entry for Holiday lookup tags (e.g., Christmas, Thanksgiving).
 * Complies with SSOT v6.7.7 (Zend_Form, Strict Regex, Balanced Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Holidaytag extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'holidaytag');

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
        $ssotRegexValidator = array(
            'Regex',
            true,
            array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 ]*)$/',
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.'
                )
            )
        );

        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Tag Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., CHRSTMAS_DAY'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a tag code.'))
            ->addValidator('StringLength', false, array('max' => 60))
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Unique Validation for Code
        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_holiday_tag',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $codeVal->setMessage('This code is already assigned to another tag.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Tag Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., Christmas Day'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a tag name.'))
            ->addValidator('StringLength', false, array('max' => 100))
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        // Unique Validation for Name
        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_holiday_tag',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $nameVal->setMessage('This name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * 3. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
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
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // SSOT v6.7.7: Strict String Quoting for standard AJAX saveDetails()
        $urlAction  = "'holidaytag/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'holidaytag\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $code,
            $name,
            $description,
            $isactive,
            $submit
        ));

        // SSOT v6.7.7: Strip default Zend Decorators
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
