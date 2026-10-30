<?php

/**
 * Department Form
 *
 * Manages data entry for Departments (Poly-Hierarchical).
 * Complies with SSOT v6.5 (Zend_Form, Regex Validation).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Department extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'department');

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

        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Code')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '60'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a code.'])
            ->addValidator('StringLength', false, ['max' => 60])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_department',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $codeVal->setMessage('This code already exists.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Name')
            ->setAttribs(['class' => 'formDataElement', 'maxlength' => '100'])
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, ['messages' => 'Please enter a name.'])
            ->addValidator('StringLength', false, ['max' => 100])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists([
            'table'   => 'main_department',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? ['field' => 'id', 'value' => $idVal] : null
        ]);
        $nameVal->setMessage('This name already exists.');
        $name->addValidator($nameVal);

        /* --------------------------------------------------------------
         * 3. Select Elements (Poly-Hierarchical Parents)
         * -------------------------------------------------------------- */

        $company_id = new Zend_Form_Element_Select('company_id');
        $company_id->setLabel('Parent: Company')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $sbunit_id = new Zend_Form_Element_Select('sbunit_id');
        $sbunit_id->setLabel('Parent: Business Unit')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $division_id = new Zend_Form_Element_Select('division_id');
        $division_id->setLabel('Parent: Division')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $parent_id = new Zend_Form_Element_Select('parent_id');
        $parent_id->setLabel('Parent: Department (Recursive)')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $representative_id = new Zend_Form_Element_Select('representative_id');
        $representative_id->setLabel('Representative / Head')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Representative.']);


        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 6. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $code,
            $name,
            $company_id,
            $sbunit_id,
            $division_id,
            $parent_id,
            $representative_id,
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
