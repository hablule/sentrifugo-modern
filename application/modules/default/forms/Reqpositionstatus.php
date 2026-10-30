<?php

/**
 * Requisition Position Status Transition Form
 *
 * Manages the modal workflow for moving a specific seat through the pipeline 
 * (e.g. from In Process to Filled or Cancelled) and logging the event reason.
 * Complies with SSOT v6.7.7.
 */
class Default_Form_Reqpositionstatus extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'reqpositionstatus_formid');
        $this->setAttrib('name', 'reqpositionstatus');

        $map_req_pos_id = new Zend_Form_Element_Hidden('map_req_pos_id');
        $map_req_pos_id->setRequired(true);

        // New Status
        $status = new Zend_Form_Element_Select('status');
        $status->setLabel('New Pipeline Status')
            ->setAttrib('class', 'formDataElement')
            ->setAttrib('onchange', 'toggleFilledEmployeeField();')
            ->addMultiOptions(array(
                'In Process' => 'In Process (Active Interviews)',
                'Filled'     => 'Filled (Candidate Hired)',
                'On Hold'    => 'On Hold (Search Paused)',
                'Cancelled'  => 'Cancelled (Seat Revoked)',
                'Re-Opened'  => 'Re-Opened'
            ))
            ->setRequired(true);

        // Status Reason (From main_req_status_reason)
        $status_reason_id = new Zend_Form_Element_Select('status_reason_id');
        $status_reason_id->setLabel('Transition Reason')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false)
            ->setRequired(true)
            ->addValidator('NotEmpty', false, array('messages' => 'Please select a reason for this status change.'));

        // Filled By Employee (Shown only when status = 'Filled')
        $filled_by_employee_id = new Zend_Form_Element_Select('filled_by_employee_id');
        $filled_by_employee_id->setLabel('Hired Employee')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        // Notes
        $notes = new Zend_Form_Element_Textarea('notes');
        $notes->setLabel('Audit Notes')
            ->setAttribs(array('class' => 'formDataElement fck_editor_height', 'rows' => 3))
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        $submit = new Zend_Form_Element_Submit('btn_update_status');
        $submit->setLabel('Update Seat Status')
            ->setAttribs(array('id' => 'btn_update_status', 'class' => 'actbtn_btn'));

        $this->addElements(array(
            $map_req_pos_id,
            $status,
            $status_reason_id,
            $filled_by_employee_id,
            $notes,
            $submit
        ));

        $this->clearDecorators();
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper'));
        }
    }
}
