<?php

/**
 * Project Participant Form
 *
 * Manages data entry for mapping projects to employees or organizational units.
 * Complies with SSOT v6.5.
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Projectparticipant extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'projectparticipant');

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $idVal   = (int)$request->getParam('id', 0);

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        $idElement = new Zend_Form_Element_Hidden('id');
        $idElement->setValue($idVal);

        /* --------------------------------------------------------------
         * 2. Select Elements (Core Mapping)
         * -------------------------------------------------------------- */

        $project_id = new Zend_Form_Element_Select('project_id');
        $project_id->setLabel('Project')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false) // Populated in Controller
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Project.']);

        // Freeze primary project ID on edit
        if ($idVal > 0) {
            $project_id->setAttrib('disabled', 'disabled');
            $project_id_hidden = new Zend_Form_Element_Hidden('project_id_hidden');
            $this->addElement($project_id_hidden);
        }

        /* --------------------------------------------------------------
         * 3. Select Elements (Poly-Participants)
         * -------------------------------------------------------------- */

        $employee_id = new Zend_Form_Element_Select('employee_id');
        $employee_id->setLabel('Map: Employee')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $company_id = new Zend_Form_Element_Select('company_id');
        $company_id->setLabel('Map: Entire Company')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $sbunit_id = new Zend_Form_Element_Select('sbunit_id');
        $sbunit_id->setLabel('Map: Entire Business Unit')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $division_id = new Zend_Form_Element_Select('division_id');
        $division_id->setLabel('Map: Entire Division')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $department_id = new Zend_Form_Element_Select('department_id');
        $department_id->setLabel('Map: Entire Department')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $section_id = new Zend_Form_Element_Select('section_id');
        $section_id->setLabel('Map: Entire Section')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        $team_id = new Zend_Form_Element_Select('team_id');
        $team_id->setLabel('Map: Entire Team')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false);

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description / Role in Project')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active Participant')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1');

        /* --------------------------------------------------------------
         * 6. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save Mapping')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $project_id,
            $employee_id,
            $company_id,
            $sbunit_id,
            $division_id,
            $department_id,
            $section_id,
            $team_id,
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
