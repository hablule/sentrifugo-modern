<?php

/**
 * Pay Group Form
 *
 * Manages operational payroll cohorts, linking populations to mathematical cadences and payment lags.
 * Complies with SSOT v6.7.7 (Zend_Form, Strict Regex, Balanced AJAX Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Paygroup extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'paygroup');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Text Elements (Code & Name)
         * -------------------------------------------------------------- */

        $ssotRegexValidator = array(
            'Regex',
            true,
            array(
                'pattern'  => '/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 _\-]*)$/',
                'messages' => array(
                    Zend_Validate_Regex::NOT_MATCH => 'Entry must contain letters and cannot start with a space or contain special characters.'
                )
            )
        );

        // Code
        $code = new Zend_Form_Element_Text('code');
        $code->setLabel('Pay Group Code')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '60', 'placeholder' => 'e.g., US_HRLY, UK_SAL'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addFilter('StringToUpper')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a Pay Group code.'))
            ->addValidator('StringLength', false, array('max' => 60))
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $codeVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_pay_group',
            'field'   => 'code',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $codeVal->setMessage('This Pay Group code already exists.');
        $code->addValidator($codeVal);

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Pay Group Name')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '100', 'placeholder' => 'e.g., US Hourly Factory, UK Salaried Execs'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter a Pay Group name.'))
            ->addValidator('StringLength', false, array('max' => 100))
            ->addValidator($ssotRegexValidator[0], $ssotRegexValidator[1], $ssotRegexValidator[2]);

        $nameVal = new Zend_Validate_Db_NoRecordExists(array(
            'table'   => 'main_pay_group',
            'field'   => 'name',
            'exclude' => ($idVal > 0) ? array('field' => 'id', 'value' => $idVal) : null
        ));
        $nameVal->setMessage('This Pay Group name already exists.');
        $name->addValidator($nameVal);

        // Payment Lag Days (The operational delay)
        $payment_lag_days = new Zend_Form_Element_Text('payment_lag_days');
        $payment_lag_days->setLabel('Payment Lag (Days)')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '4', 'placeholder' => 'e.g., 5 or 0'))
            ->setRequired(true)
            ->setValue('0')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter the payment lag days.'))
            ->addValidator('Digits', true, array('messages' => 'Lag days must be a whole number (0 or greater).'));

        /* --------------------------------------------------------------
         * 3. Select Elements
         * -------------------------------------------------------------- */

        // Pay Frequency (The Mathematical Blueprint)
        $payfrequency_id = new Zend_Form_Element_Select('payfrequency_id');
        $payfrequency_id->setLabel('Base Pay Frequency')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select the underlying Pay Frequency for this group.'));

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description / Notes')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
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
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        $urlAction  = "'paygroup/save/format/json'";
        $jsFunction = "'redirecttocontroller(\\'paygroup\\');'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $code,
            $name,
            $payfrequency_id,
            $payment_lag_days,
            $description,
            $isactive,
            $submit
        ));

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
