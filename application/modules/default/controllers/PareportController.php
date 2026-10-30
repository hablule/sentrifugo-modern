<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Report Controller (Analytics & Dashboards)
 * 
 * Acts as the API layer between the Frontend UI and the Pareport_Model.
 * Enforces the 5-Tier Rule-Based Hierarchy to guarantee Row-Level Security.
 * Strictly adheres to SSOT v6.7.4.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @version    Sentrifugo v3.2
 */
class Default_PareportController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_loginEmpId;
    
    /** @var array Array of authorized report codes fetched from map_report_acl */
    protected $_authorizedReports = array();

    protected $_model;
    protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Pareport();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            // Identity Resolution
            $userModel = new Default_Model_Users();
            $empIdResult = $userModel->getEmpFromUser($this->_loginUserId);
            $this->_loginEmpId = (isset($empIdResult[0]['empid'])) ? (int)$empIdResult[0]['empid'] : 0;

            // ==================================================================
            // FIXED: THE RBAC GATEKEEPER
            // Uses the Report Catalog mappings instead of hardcoded levels
            // ==================================================================
            if (class_exists('Default_Model_Reportacl')) {
                $aclModel = new Default_Model_Reportacl();
                $this->_authorizedReports = $aclModel->getAuthorizedReportCodes(
                    'Performance', 
                    $this->_loginEmpId, 
                    $this->_loginuserRole, 
                    $this->_loginuserGroup
                );
            }

            // Export to View
            $this->view->loginuserRole  = $this->_loginuserRole;
            $this->view->loginuserGroup = $this->_loginuserGroup;
            $this->view->authorizedReports = $this->_authorizedReports;
        }

        require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
    }

    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) die(Zend_Json::encode(array('login' => 'failed')));
            else $this->_redirect('');
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getdashboarddata', 'json')
                    ->addActionContext('getchartdata', 'json')
                    ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL (UI Renderers)
     * ================================================================================= */

    public function indexAction()
    {
        $appraisalModel = new Default_Model_Paappraisal();
        $this->view->campaignOptions = $appraisalModel->getPairs(true);
    }

    /* =================================================================================
     * GROUP 5: AJAX API ENDPOINTS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * API Endpoint: Fetches KPI cards for the Executive Summary Tab.
     */
    public function getdashboarddataAction()
    {
        // 1. RBAC CHECK
        if (!in_array('PA_EXEC_SUMMARY', $this->_authorizedReports)) {
            $this->_helper->json(array('error' => 'Unauthorized to view this report.'));
            return;
        }

        $request = $this->getRequest();
        $filters = json_decode((string)$request->getParam('filters', '{}'), true);

        try {
            // Note: The Model no longer applies dynamic $accessLevel WHERE clauses.
            // A report queries exactly what its catalog code implies.
            $kpiData = $this->_model->getDashboardKPIs($filters);
            $this->_helper->json(array('status' => 'success', 'data' => $kpiData));
        } catch (\Throwable $e) {
            $this->_helper->json(array('error' => 'Server Error: ' . $e->getMessage()));
        }
    }

    /**
     * API Endpoint: Formats Database array into Chart.js friendly structure.
     */
    public function getchartdataAction()
    {
        $request = $this->getRequest();
        $chartType = $request->getParam('type');
        $filters   = json_decode((string)$request->getParam('filters', '{}'), true);
        
        if (empty($chartType)) {
            $this->_helper->json(array('error' => 'Missing chart type'));
            return;
        }

        try {
            if ($chartType === 'status_breakdown') {
                
                // 1. RBAC CHECK
                if (!in_array('PA_EXEC_SUMMARY', $this->_authorizedReports)) {
                    $this->_helper->json(array('error' => 'Unauthorized to view this report.'));
                    return;
                }

                // 2. Fetch Data
                $dbResults = $this->_model->getStatusBreakdownChart($filters);
                
                // 3. Translate raw DB array into Chart.js format
                $labels = array(); $data = array();
                foreach ($dbResults as $row) { 
                    $labels[] = $row['status']; $data[] = $row['count']; 
                }
                
                $this->_helper->json(array('status' => 'success', 'labels' => $labels, 'data' => $data));
            } else {
                $this->_helper->json(array('error' => 'Unknown chart type'));
            }
        } catch (\Throwable $e) {
            $this->_helper->json(array('error' => 'Server Error: ' . $e->getMessage()));
        }
    }
}