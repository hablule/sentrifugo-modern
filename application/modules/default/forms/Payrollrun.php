<?php

/**
 * Payroll Run Form (Master Control Ticket)
 *
 * Provides manual control for ad-hoc runs or modifying Scheduled run metadata.
 * Complies with SSOT v6.7.7 (Zend_Form, DatePicker, State Machine Locks).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Payrollrun extends Zend_Form
{
    public function init()
    {
        /* --------------------------------------------------------------
         * Form Configuration
         * -------------------------------------------------------------- */
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'payrollrun');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Select Elements (Core Lookups & State)
         * -------------------------------------------------------------- */

        // Pay Group Target
        $pay_group_id = new Zend_Form_Element_Select('pay_group_id');
        $pay_group_id->setLabel('Target Pay Group')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a Pay Group for this run.'));

        // Run Status (The State Machine)
        $run_status = new Zend_Form_Element_Select('run_status');
        $run_status->setLabel('Run Status')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions(array(
                'Scheduled' => 'Scheduled (Awaiting Initiation)',
                'Initiated' => 'Initiated (Processing)',
                'Review'    => 'Review (Awaiting Approval)',
                'Locked'    => 'Locked (Approved & Frozen)',
                'Closed'    => 'Closed (GL Posted)',
                'Cancelled' => 'Cancelled (Aborted)'
            ))
            ->setRequired(true)
            ->setValue('Scheduled');

        /* --------------------------------------------------------------
         * 3. Text Elements (Tracking)
         * -------------------------------------------------------------- */

        $cycle_sequence = new Zend_Form_Element_Text('cycle_sequence');
        $cycle_sequence->setLabel('Cycle Sequence (e.g., Pay Period 1)')
            ->setAttribs(array('class' => 'formDataElement', 'maxlength' => '3', 'placeholder' => 'e.g., 1 or 26'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', false, array('messages' => 'Please enter the cycle sequence number.'))
            ->addValidator('Digits', true, array('messages' => 'Sequence must be a whole number.'));

        /* --------------------------------------------------------------
         * 4. Date Elements (Boundaries & Execution)
         * -------------------------------------------------------------- */

        $cycle_start_date = new ZendX_JQuery_Form_Element_DatePicker('cycle_start_date');
        $cycle_start_date->setLabel('T&A Cycle Start Date')
            ->setAttribs(array(
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ))
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select the cycle start date.'));

        $cycle_end_date = new ZendX_JQuery_Form_Element_DatePicker('cycle_end_date');
        $cycle_end_date->setLabel('T&A Cycle End Date')
            ->setAttribs(array(
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ))
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select the cycle end date.'));

        $pay_date = new ZendX_JQuery_Form_Element_DatePicker('pay_date');
        $pay_date->setLabel('Target Pay Date (Bank Execution)')
            ->setAttribs(array(
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ))
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select the target pay date.'));

        /* --------------------------------------------------------------
         * 5. Textarea Elements
         * -------------------------------------------------------------- */

        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Run Notes / Remarks')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 4))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 6. Submit Element
         * -------------------------------------------------------------- */

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Run')
            ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));

        // Ad-hoc run validation relies on custom AJAX to bypass global helpers
        // (Handled explicitly in save.phtml due to the strict DB Unique checks needed)

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $idElement,
            $pay_group_id,
            $cycle_sequence,
            $cycle_start_date,
            $cycle_end_date,
            $pay_date,
            $run_status,
            $description,
            $submit
        ));

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            if ($element instanceof ZendX_JQuery_Form_Element_DatePicker) {
                $element->setDecorators(array('UiWidgetElement'));
            } else {
                $element->setDecorators(array('ViewHelper'));
            }
        }
    }
}
