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
 * Currency Converter Controller
 * 
 * Manages the CRUD and Grid logic for Currency Exchange Rates.
 * Strictly adheres to SSOT v6.2: PHP 7.4/8.0+ safe, transactional, 
 * strict JSON responses, date transformations, and decoupled form logic.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_CurrencyconverterController extends Zend_Controller_Action
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
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Currencyconverter();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();

            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            // SSOT V: Explicitly assign context to view for UI-level ACL checks
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
                die(Zend_Json::encode(array('login' => 'failed')));
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
            ->addActionContext('delete', 'json')
            ->addActionContext('makeactiveinactive', 'json')
            ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);

            // SSOT VIII: Strict string casting
            $searchData    = (string)$request->getParam('searchData', '');

            // SSOT V: Default sort is DESC by start_date to show newest rates first
            $sort    = $request->getParam('sort', 'DESC');
            $by      = $request->getParam('by', 'start_date');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'DESC';
                $by   = 'start_date';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) { // SSOT VIII
            error_log("CurrencyConverter Index Error: " . $e->getMessage());
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'currencyconverter';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, false);
        $this->render('save');
    }

    public function saveAction()
    {
        $form = new Default_Form_Currencyconverter();
        $request = $this->getRequest();

        if ($request->isPost()) {
            // Must populate dropdowns before isValid() so 'InArray' validator passes
            $currencyModel = new Default_Model_Currency();
            $currencies = $currencyModel->getPairs(true);
            $form->base_currency_id->addMultiOption('', 'Select Base Currency')->addMultiOptions($currencies);
            $form->target_currency_id->addMultiOption('', 'Select Target Currency')->addMultiOptions($currencies);

            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                // Format dates for DB
                $startDateDb = sapp_Global::change_date(trim($request->getParam('start_date')), 'database');
                $endDateRaw  = trim($request->getParam('end_date'));
                $endDateDb   = !empty($endDateRaw) ? sapp_Global::change_date($endDateRaw, 'database') : null;

                $baseId   = (int)$request->getParam('base_currency_id');
                $targetId = (int)$request->getParam('target_currency_id');

                // Custom Database Constraint Check: Unique Period
                $duplicate = $this->_model->checkDuplicatePeriod($baseId, $targetId, $startDateDb, $id);
                if ($duplicate) {
                    $this->_helper->json(array('start_date' => 'A conversion rate already exists for this exact currency pair and start date.'));
                    return;
                }

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction(); // SSOT V: Transactions Mandatory

                try {
                    $data = array(
                        'base_currency_id'   => $baseId,
                        'target_currency_id' => $targetId,
                        'exchange_rate'      => (float)$request->getParam('exchange_rate'),
                        'start_date'         => $startDateDb,
                        'end_date'           => $endDateDb,
                        'description'        => trim($request->getParam('description')),
                        'isactive'           => (int)$request->getParam('isactive', 1)
                    );

                    $actionFlag = ($id > 0) ? 2 : 1; // 2=Edit, 1=Add
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    // SSOT I: Dynamic Menu ID logging (111 = Currency Conversions)
                    $menuId = defined('CURRENCYCONVERTER') ? CURRENCYCONVERTER : 111;
                    sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

                    $db->commit();

                    // Set Flash message for the NEXT page load
                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(array('success' => "Conversion rate $msg successfully."));

                    // SSOT V: Strict JSON Response for Global Redirect Helper
                    $this->_helper->json(array(
                        'result'    => 'saved',
                        'nomessage' => 'yes'
                    ));
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("CurrencyConverter Save Error: " . $e->getMessage());
                    $this->_helper->json(array('result' => 'exception', 'message' => 'System error occurred while saving the record.'));
                }
            } else {
                // SSOT V: Extract and flatten Zend Form error messages
                $msgarray = array();
                foreach ($form->getMessages() as $field => $errors) {
                    $msgarray[$field] = current($errors);
                }
                $this->_helper->json($msgarray);
            }
        }
    }

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                $this->_model->deleteRecord($id);

                $menuId = defined('CURRENCYCONVERTER') ? CURRENCYCONVERTER : 111;
                sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);

                $this->_helper->json(array('msgtype' => 'success', 'message' => 'Conversion rate deleted successfully.'));
            } catch (\Throwable $e) {
                $this->_helper->json(array('msgtype' => 'error', 'message' => $e->getMessage()));
            }
        } else {
            $this->_helper->json(array('msgtype' => 'error', 'message' => 'Invalid Request.'));
        }
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');

            $this->_model->toggleStatus($id, $status);

            $menuId = defined('CURRENCYCONVERTER') ? CURRENCYCONVERTER : 111;
            sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);

            $this->_helper->json(array('result' => 'yes'));
        } catch (\Throwable $e) {
            $this->_helper->json(array('result' => 'no'));
        }
    }

    /* =================================================================================
     * GROUP 5: AJAX & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Form Preparation Helper
     * 
     * @param int|null $id
     * @param bool     $readonly
     * @return Default_Form_Currencyconverter
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Currencyconverter();
        $currencyModel = new Default_Model_Currency();

        // SSOT VI: Strict Native Dropdown Population
        $currencies = $currencyModel->getPairs(true);
        $form->base_currency_id->addMultiOption('', 'Select Base Currency')->addMultiOptions($currencies);
        $form->target_currency_id->addMultiOption('', 'Select Target Currency')->addMultiOptions($currencies);

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                // Convert DB dates to UI format before populating
                if (!empty($data['start_date'])) {
                    $data['start_date'] = sapp_Global::change_date($data['start_date'], 'view');
                }
                if (!empty($data['end_date'])) {
                    $data['end_date'] = sapp_Global::change_date($data['end_date'], 'view');
                }

                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
            }
        }

        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }
}
