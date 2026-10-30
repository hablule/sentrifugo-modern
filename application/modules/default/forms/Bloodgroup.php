<?php

/**
 * Blood Group Form
 *
 * Manages medical blood group classifications (e.g., A+, O-).
 * Complies with SSOT v6.7.7 (Zend_Form, Strict Regex, Balanced AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Bloodgroup extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'bloodgroup');

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

        // Blood Group Code: Supports alphanumeric and standard underscores/symbols (e.g., A_POS, A+)
        $codeRegex = array(
            'Regex',
            true,
            array(
                'pattern'  => '/^[a-zA-Z0-9_\+\-]+$/',
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Code must contain letters, numbers, or symbols (+, -, _).'
                )
            )
        );

        // Blood Group Name: Supports readable medical labels (e.g., "A Positive (A+)")
        $nameRegex = array(
            'Regex',
            true,
            array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \(\)\+\-_]*)$/',
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Name must contain letters and cannot start with a space.'
                )
            )
        );

        // Code (e.g., 'A_POS', 'O_NEG')
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Blood Group Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '10', 'placeholder' => 'e.g., A_POS, O_NEG'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a blood group code.'))
            ->addValidator('StringLength', false, array('max' => 10))
            ->addValidator($codeRegex[0], $codeRegex[1], $codeRegex[2]);

        // Unique Validation for Code
        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_bloodgroup',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $codeVal->setMessage('This blood group code already exists.');
        $code->addValidator($codeVal);

        // Name (e.g., 'A Positive (A+)')
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Blood Group Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '50', 'placeholder' => 'e.g., A Positive (A+)'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a blood group name.'))
            ->addValidator('StringLength', false, array('max' => 50))
            ->addValidator($nameRegex[0], $nameRegex[1], $nameRegex[2]);

        // Unique Validation for Name
        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_bloodgroup',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $nameVal->setMessage('This blood group name already exists.');
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

        // SSOT VI.9: Strict String Quoting for standard AJAX saveDetails()
        $urlAction  = "'bloodgroup/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'bloodgroup\\');'";
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

        // SSOT VI.7: Strip default Zend Decorators
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
