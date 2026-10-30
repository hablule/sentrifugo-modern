<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Site Preference Controller
 *
 * Manages the Singleton Global Configuration for the HRMS.
 * Complies with SSOT v6.7 (Singleton Pattern, Atomic File Caching, Raw Array JSON).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_SitepreferenceController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    public function init()
    {
        $this->_model   = new Default_Model_Sitepreference();
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = (int)$user->id;
            $this->_loginuserRole  = (int)$user->emprole;
            $this->_loginuserGroup = (int)$user->group_id;

            // SSOT Mandatory View Assignments
            $this->view->loginuserRole  = $this->_loginuserRole;
            $this->view->loginuserGroup = $this->_loginuserGroup;
        }

        require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
    }

    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                echo Zend_Json::encode(array('login' => 'failed'));
                exit;
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
            ->initContext();
    }

    /* =================================================================================
     * GROUP 2 & 3: SINGLETON DISPLAY & MODIFICATION
     * ================================================================================= */

    /**
     * Index Action (The Singleton View)
     * Replaces standard Grid view. Directly loads the configuration form for ID 1.
     */
    public function indexAction()
    {
        // Define SITEPREFERENCE in constants.php (e.g., 131)
        if (sapp_Global::_checkprivileges(SITEPREFERENCE, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
            $this->_redirect('error/privilege');
        }

        $this->view->form     = $this->_prepareForm();
        $this->view->messages = $this->_helper->flashMessenger->getMessages();

        // Render the save.phtml view instead of index.phtml
        $this->render('save');
    }

    public function saveAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_redirect('sitepreference/index');
        }

        // Re-populate dropdowns for validation
        $form = $this->_prepareForm();

        if ($form->isValid($request->getPost())) {

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                // Ensure empty string selects convert to NULL for foreign keys
                $data = array(
                    'company_id'         => $request->getParam('company_id') ?: null,
                    'nationality_id'     => $request->getParam('nationality_id') ?: null,
                    'language_id'        => $request->getParam('language_id') ?: null,
                    'dateformat_id'      => $request->getParam('dateformat_id') ?: null,
                    'timeformat_id'      => $request->getParam('timeformat_id') ?: null,
                    'timezone_id'        => $request->getParam('timezone_id') ?: null,
                    'currency_id'        => $request->getParam('currency_id') ?: null,
                    'password_policy_id' => $request->getParam('password_policy_id') ?: null,
                    'week_start_day'     => trim((string) $request->getParam('week_start_day', 'Monday')),
                    'default_per_page'   => (int) $request->getParam('default_per_page', 20),
                    'description'        => trim((string) $request->getParam('description', '')),
                    'isactive'           => 1 // Hardcoded to 1 for global config
                );

                // Save and trigger Atomic Cache Rebuild
                $this->_model->saveSingletonRecord($data);

                // Logging (Always Edit Action = 2 for Singleton)
                sapp_Global::logManager(SITEPREFERENCE, 2, $this->_loginUserId, 1);

                $db->commit();

                // FlashMessenger for reload
                $this->_helper->flashMessenger->addMessage(array('success' => "Global Preferences updated successfully."));

                // SSOT Strict AJAX JSON Response (Success with Reload via saveDetails helper)
                $this->_helper->json(array(
                    'result'    => 'saved',
                    'nomessage' => 'yes'
                ));
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("SitePreference Save Error: " . $e->getMessage());

                $this->_helper->json(array(
                    'result'  => 'exception',
                    'message' => 'System error occurred. Check logs.',
                    'msgtype' => 'error'
                ));
            }
        } else {
            // Validation Error - Raw Array output directly
            echo Zend_Json::encode($form->getMessages());
            exit;
        }
    }

    /* =================================================================================
     * GROUP 5: INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Form Preparation Helper
     * Instantiates the form, populates dropdowns natively, and loads ID 1.
     * 
     * @return Default_Form_Sitepreference
     */
    private function _prepareForm()
    {
        $form = new Default_Form_Sitepreference();

        // 1. Populate Dropdowns natively using SSOT getPairs()
        $companyModel = new Default_Model_Company();
        $form->company_id->addMultiOption('', 'Select HQ Company');
        $form->company_id->addMultiOptions($companyModel->getPairs());

        $nationalityModel = new Default_Model_Nationality();
        $form->nationality_id->addMultiOption('', 'Select Default Nationality Context');
        $form->nationality_id->addMultiOptions($nationalityModel->getPairs());

        $languageModel = new Default_Model_Language();
        $form->language_id->addMultiOption('', 'Select System Locale');
    //    $form->language_id->addMultiOptions($languageModel->getPairs());

        $dateFormatModel = new Default_Model_Dateformat();
        $form->dateformat_id->addMultiOption('', 'Select Date Format');
    //    $form->dateformat_id->addMultiOptions($dateFormatModel->getPairs());

        $timeFormatModel = new Default_Model_Timeformat();
        $form->timeformat_id->addMultiOption('', 'Select Time Format');
    //    $form->timeformat_id->addMultiOptions($timeFormatModel->getPairs());

        $timeZoneModel = new Default_Model_Timezone();
        $form->timezone_id->addMultiOption('', 'Select Operating Timezone');
        $form->timezone_id->addMultiOptions($timeZoneModel->getPairs());

        $currencyModel = new Default_Model_Currency();
        $form->currency_id->addMultiOption('', 'Select Base Currency');
        $form->currency_id->addMultiOptions($currencyModel->getPairs());

        // Password Policy Model 
    //    $passwordModel = new Default_Model_Password();
    //    $form->password_policy_id->addMultiOption('', 'Select Password Policy');
   //     $form->password_policy_id->addMultiOptions($passwordModel->getPairs());

        // 2. Load the Singleton Record (ID 1)
        $data = $this->_model->getSingletonRecord();

        if ($data) {
            // XSS Prevention / Sanitation
            foreach ($data as $key => $val) {
                if ($val !== null) {
                    $data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
                }
            }
            $form->populate($data);
        }

        return $form;
    }
}
