<?php

/**
 * Calendar Form
 *
 * Manages data entry for Root Time-Grouping Entities (e.g., Fiscal Calendar).
 * Complies with SSOT v6.7 (Zend_Form, Regex Validation, Balanced Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Calendar extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'calendar');

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

        $lengthValidatorName = array('StringLength', false, array('max' => 30));

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Calendar Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '30', 'placeholder' => 'e.g., Fiscal Calendar, Standard'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a calendar name.'))
            ->addValidator($lengthValidatorName[0], $lengthValidatorName[1], $lengthValidatorName[2])
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_calendar',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $nameVal->setMessage('This calendar name already exists.');
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

        // SSOT v6.7 "Balanced Approach": 
        // This is a simple CRUD form with no files or complex JS validation.
        $urlAction  = "'calendar/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'calendar\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, 'formid', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $name,
            $description,
            $isactive,
            $submit
        ));

        // Strip default Zend Decorators per SSOT
        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
