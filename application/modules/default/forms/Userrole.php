<?php

/**
 * User Role Controller
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 **/
class Default_Form_Userrole extends Zend_Form
{
    public function init()
    {
       
        /* --------------------------------------------------------------
         * Form setup
         * -------------------------------------------------------------- */
        $this->setMethod('post')
            ->setAttrib('id', 'userrole_form')
            ->setAttrib('name', 'userrole');

        // Request context (used for edit exclusions)
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $id      = (int)$request->getParam('id');

        /* --------------------------------------------------------------
         * Hidden: Primary Key
         * -------------------------------------------------------------- */
        $userrole_id = new Zend_Form_Element_Hidden('userrole_id');

        /* --------------------------------------------------------------
         * User Select
         * -------------------------------------------------------------- */
        $user_id = new Zend_Form_Element_Select('user_id');
        $user_id->setLabel('User')
            ->addMultiOption('', 'Select User')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false)
            ->setRequired(true)
            ->addValidator(
                'NotEmpty',
                false,
                array('messages' => 'Please select a user.')
            );

        /* 
        // Prevent duplicate assignments (edit-safe)
        $user_id->addValidator(
            new Zend_Validate_Db_NoRecordExists(array(
                'table'   => 'main_userrole',
                'field'   => 'user_id',
                'exclude' => $id
                    ? array('field' => 'userrole_id', 'value' => $id)
                    : null
            ))
        );
        $user_id->getValidator('Db_NoRecordExists')
            ->setMessage('A role has already been assigned to this user.'); 
        */

        /* --------------------------------------------------------------
         * Role Select
         * -------------------------------------------------------------- */
        $role_id = new Zend_Form_Element_Select('role_id');
        $role_id->setLabel('Role')
            ->addMultiOption('', 'Select Role')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->addValidator(
                'NotEmpty',
                false,
                array('messages' => 'Please select a role.')
            );

        /* --------------------------------------------------------------
         * Date From (Mandatory)
         * -------------------------------------------------------------- */
        $datefrom = new ZendX_JQuery_Form_Element_DatePicker('datefrom');
        $datefrom->setLabel('Valid From')
            ->setAttribs(array(
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ))
            ->setOptions(array('class' => 'formDataElement'))
            ->setRequired(true)
            ->addValidator(
                'NotEmpty',
                false,
                array('messages' => 'Please select a start date.')
            );

        /* --------------------------------------------------------------
         * Date To (Optional)
         * -------------------------------------------------------------- */
        $dateto = new ZendX_JQuery_Form_Element_DatePicker('dateto');
        $dateto->setLabel('Valid To')
            ->setAttribs(array(
                'class'    => 'brdr_none formDataElement',
                'readonly' => 'true',
                'onfocus'  => 'this.blur()'
            ));

        /* --------------------------------------------------------------
         * Description
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
            ->setAttribs(array(
                'rows'  => 3,
                'class' => 'formDataElement'
            ))
            ->addFilter(new Zend_Filter_StringTrim())
            ->addValidator(
                'Regex',
                true,
                array(
                    'pattern'  => '/^[a-zA-Z0-9\s\.\,\-]+$/',
                    'messages' => array(
                        Zend_Validate_Regex::NOT_MATCH =>
                        'Description contains invalid characters.'
                    )
                )
            );

        /* --------------------------------------------------------------
         * Status
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Active')
            ->setCheckedValue('Y')
            ->setUncheckedValue('N')
            ->setValue('Y'); // Default Active

        /* --------------------------------------------------------------
         * Submit
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttribs(array(
            'id'      => 'submitbutton',
            'class'   => 'actbtn_btn',
            'onclick' => 'saveUserRole()'
        ))
            ->setLabel('Save Role Assignment');

        /* --------------------------------------------------------------
         * Add elements
         * -------------------------------------------------------------- */
        $this->addElements(array(
            $userrole_id,
            $user_id,
            $role_id,
            $datefrom,
            $dateto,
            $description,
            $isactive,
            $submit
        ));

        /* --------------------------------------------------------------
         * Decorators (Sentrifugo standard)
         * -------------------------------------------------------------- */
        $this->setElementDecorators(array('ViewHelper'));

        // DatePicker widgets
        $this->setElementDecorators(
            array('UiWidgetElement'),
            array('datefrom', 'dateto')
        );
    }
}
