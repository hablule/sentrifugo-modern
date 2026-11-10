<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 ********************************************************************************/

/**
 * PA Report Model (Analytics & Dashboards)
 * 
 * Executes complex SQL aggregations on the Performance Appraisal ledger.
 * Does not perform CRUD. Strictly Read-Only.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @version    Sentrifugo v3.2
 */
class Default_Model_Pareport extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_pa_assessment';
    protected $_primary = 'id';

    /* =================================================================================
     * REPORT QUERIES: EXECUTIVE SUMMARY (TAB 1)
     * ================================================================================= */

    /**
     * Fetches the macro KPIs for the top of the dashboard.
     * GLOBAL-FIRST: Accepts an array of filters rather than a hardcoded campaign ID.
     */
    public function getDashboardKPIs(array $filters, $accessLevel, $empId)
    {
        $db = $this->getAdapter();

        $select = $db->select()
            ->from(['ass' => 'main_pa_assessment'], [
                'total_assessments' => new Zend_Db_Expr('COUNT(ass.id)'),
                'completed_assessments' => new Zend_Db_Expr("SUM(CASE WHEN ass.status IN ('Completed', 'Acknowledged') THEN 1 ELSE 0 END)"),
                'average_score' => new Zend_Db_Expr("ROUND(AVG(ass.consolidated_score), 2)")
            ])
            ->where('ass.isactive = 1');

        $select = $this->_applyFilters($select, $filters);
        $select = $this->_applySecurityScope($select, $accessLevel, $empId);

        return $db->fetchRow($select);
    }

    /**
     * Data for the Pie Chart: Breakdown of Assessment Statuses
     */
    public function getStatusBreakdownChart(array $filters, $accessLevel, $empId)
    {
        $db = $this->getAdapter();

        $select = $db->select()
            ->from(['ass' => 'main_pa_assessment'], [
                'status',
                'count' => new Zend_Db_Expr('COUNT(ass.id)')
            ])
            ->where('ass.isactive = 1')
            ->group('ass.status');

        $select = $this->_applyFilters($select, $filters);
        $select = $this->_applySecurityScope($select, $accessLevel, $empId);

        return $db->fetchAll($select);
    }

    /* =================================================================================
     * INTERNAL HELPERS (Filters & Security)
     * ================================================================================= */

    /**
     * Dynamically appends WHERE clauses based on user-selected UI filters.
     */
    private function _applyFilters(Zend_Db_Select $select, array $filters)
    {
        // Example: Filter by a specific campaign
        if (!empty($filters['campaign_id'])) {
            $select->where('ass.appraisal_id = ?', (int)$filters['campaign_id']);
        }

        // Note: Future filters (Department, Date Range) will be added here!

        return $select;
    }

    /**
     * Appends the appropriate WHERE or JOIN clauses to enforce Row-Level Security.
     */
    private function _applySecurityScope(Zend_Db_Select $select, $accessLevel, $empId)
    {
        switch ($accessLevel) {
            case 1: // Employee (Self Only)
                $select->where('ass.evaluatee_id = ?', (int)$empId);
                break;

            case 2: // Manager (Direct Reports Only)
                $select->joinInner(['pos' => 'main_empposition'], 'ass.evaluatee_id = pos.employee_id', [])
                    ->where('pos.reportto_id = ?', (int)$empId)
                    ->where('pos.isactive = ?', 'Y');
                break;

            case 3: // Unit Head (Department Wide)
                $select->joinInner(['pos' => 'main_empposition'], 'ass.evaluatee_id = pos.employee_id', [])
                    ->where('pos.department_id IN (SELECT id FROM main_department WHERE representative_id = ?)', (int)$empId)
                    ->where('pos.isactive = ?', 'Y');
                break;

            case 4: // Executive (Company Wide)
            case 5: // HR / Auditor (Company Wide)
                break;
        }
        return $select;
    }
}
