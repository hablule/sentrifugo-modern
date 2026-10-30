<?php

/**
 * Site Preference Form
 *
 * Manages data entry for Global System Defaults.
 * Complies with SSOT v6.7 (Singleton Pattern, Balanced Submission).
 *
 * @category   Application
 * @package    Default
 * @subpackage Forms
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Form_Sitepreference extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'formid');
        $this->setAttrib('name', 'sitepreference');

        /* --------------------------------------------------------------
         * 1. Hidden Elements
         * -------------------------------------------------------------- */
        // We force ID to 1 in the Controller, but keeping element for ZF1 standard structure
        $idElement = new Zend_Form_Element_Hidden('id');

        /* --------------------------------------------------------------
         * 2. Select Elements (Core Lookups)
         * -------------------------------------------------------------- */

        $company_id = new Zend_Form_Element_Select('company_id');
        $company_id->setLabel('Default HQ / Primary Company')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Populated in Controller

        $nationality_id = new Zend_Form_Element_Select('nationality_id');
        $nationality_id->setLabel('Default Legal/Jurisdictional Context')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a default Nationality context.']);

        $language_id = new Zend_Form_Element_Select('language_id');
        $language_id->setLabel('Default System Locale (Language)')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a default Language.']);

        $dateformat_id = new Zend_Form_Element_Select('dateformat_id');
        $dateformat_id->setLabel('Global Date Format')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Date Format.']);

        $timeformat_id = new Zend_Form_Element_Select('timeformat_id');
        $timeformat_id->setLabel('Global Time Format')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Time Format.']);

        $timezone_id = new Zend_Form_Element_Select('timezone_id');
        $timezone_id->setLabel('Global Operating Timezone')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select an Operating Timezone.']);

        $currency_id = new Zend_Form_Element_Select('currency_id');
        $currency_id->setLabel('Base Operating Currency')
            ->setAttrib('class', 'formDataElement')
            ->setRequired(true)
            ->setRegisterInArrayValidator(false)
            ->addValidator('NotEmpty', false, ['messages' => 'Please select a Base Currency.']);

        $password_policy_id = new Zend_Form_Element_Select('password_policy_id');
        $password_policy_id->setLabel('Global Password Policy')
            ->setAttrib('class', 'formDataElement')
            ->setRegisterInArrayValidator(false); // Optional for now

        /* --------------------------------------------------------------
         * 3. Select Elements (Enums / Hardcoded)
         * -------------------------------------------------------------- */

        $week_start_day = new Zend_Form_Element_Select('week_start_day');
        $week_start_day->setLabel('Start of Week (For Timesheets & Calendars)')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions([
                'Monday'   => 'Monday',
                'Sunday'   => 'Sunday',
                'Saturday' => 'Saturday'
            ])
            ->setRequired(true);

        $default_per_page = new Zend_Form_Element_Select('default_per_page');
        $default_per_page->setLabel('Default Grid Pagination (Rows per Page)')
            ->setAttrib('class', 'formDataElement')
            ->addMultiOptions([
                '10'  => '10 Rows',
                '20'  => '20 Rows',
                '25'  => '25 Rows',
                '50'  => '50 Rows',
                '100' => '100 Rows'
            ])
            ->setRequired(true);

        /* --------------------------------------------------------------
         * 4. Textarea Elements
         * -------------------------------------------------------------- */
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('System Configuration Notes')
            ->setAttribs(['class' => 'formDataElement fck_editor_height', 'rows' => 4])
            ->addFilter('StringTrim')
            ->addFilter('StripTags');

        /* --------------------------------------------------------------
         * 5. Checkbox Elements
         * -------------------------------------------------------------- */
        $isactive = new Zend_Form_Element_Checkbox('isactive');
        $isactive->setLabel('Configuration Active')
            ->setCheckedValue('1')
            ->setUncheckedValue('0')
            ->setValue('1')
            ->setAttrib('readonly', 'readonly'); // A global config must always be active

        /* --------------------------------------------------------------
         * 6. Submit Element
         * -------------------------------------------------------------- */
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Update Preferences')
            ->setAttribs(['id' => 'submitbutton', 'class' => 'actbtn_btn']);

        // SSOT v6.7 "Balanced Approach": 
        // Because this is a Singleton, on success we simply reload the current page.
        $urlAction  = "'sitepreference/save/format/json'";
        $jsFunction = "'window.location.reload();'";
        $submit->setAttrib('onclick', "saveDetails($urlAction, 'formid', '', $jsFunction);");

        /* --------------------------------------------------------------
         * Build Form
         * -------------------------------------------------------------- */
        $this->addElements([
            $idElement,
            $company_id,
            $nationality_id,
            $language_id,
            $dateformat_id,
            $timeformat_id,
            $timezone_id,
            $currency_id,
            $password_policy_id,
            $week_start_day,
            $default_per_page,
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
