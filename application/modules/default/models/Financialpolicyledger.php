<?php

/**
 * Financial Policy Ledger Model
 *
 * Manages the centralized, flat-structured event ledger for policy and financial mutations.
 * Serves as the broadcast bus for retroactive payroll adjustments (Arrears).
 * Complies with SSOT v6.7.7 (Flat Audit Structure, PHP 7.4 strict).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Financialpolicyledger extends Zend_Db_Table_Abstract
{
    /* --------------------------------------------------------------
     * Attributes (Visibility -> Alphabetical)
     * -------------------------------------------------------------- */

    /** @var string Primary table name (SSOT Singular) */
    protected $_name = 'sys_financial_policy_ledger';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL (SUBSCRIBER APIS)
     * ================================================================================= */

    /**
     * Fetches all audit entries for a specific module record.
     *
     * @param string $sourceModule e.g., 'HOLIDAY_DATE'
     * @param int $recordId
     * @return array
     */
    public function getChangesByRecord(string $sourceModule, int $recordId): array
    {
        $select = $this->select()
            ->where('source_module = ?', $sourceModule)
            ->where('record_id = ?', $recordId)
            ->order('modifieddate DESC');

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Fetches retroactive policy changes affecting a specific calendar date range.
     * Used by the Payroll Engine during payroll run initialization.
     *
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @param string|null $sinceTimestamp Only look for changes made after this datetime
     * @return array
     */
    public function getChangesByEffectiveDateRange(string $startDate, string $endDate, ?string $sinceTimestamp = null): array
    {
        $select = $this->select()
            ->where('effective_date >= ?', $startDate)
            ->where('effective_date <= ?', $endDate);

        if (!empty($sinceTimestamp)) {
            $select->where('modifieddate > ?', $sinceTimestamp);
        }

        $select->order('effective_date ASC');

        return $this->fetchAll($select)->toArray();
    }

    /* =================================================================================
     * GROUP 3: LOGGING & PERSISTENCE
     * ================================================================================= */

    /**
     * Writes a single flat change record to the ledger.
     *
     * @param string $sourceModule e.g., 'HOLIDAY_DATE'
     * @param int $recordId
     * @param string $actionType 'CREATED', 'UPDATED', 'DELETED'
     * @param string|null $effectiveDate The calendar day affected (Y-m-d)
     * @param string|null $fieldName Specific column changed (e.g. 'payroll_multiplier')
     * @param string|null $valueOld Previous value
     * @param string|null $valueNew New value
     * @param int $userId The admin user who made the change
     * @return int Insert ID
     */
    public function logChange(
        string $sourceModule,
        int $recordId,
        string $actionType,
        ?string $effectiveDate,
        ?string $fieldName,
        ?string $valueOld,
        ?string $valueNew,
        int $userId
    ): int {
        $data = [
            'source_module'  => $sourceModule,
            'record_id'      => $recordId,
            'action_type'    => $actionType,
            'effective_date' => $effectiveDate,
            'field_name'     => $fieldName,
            'value_old'      => $valueOld,
            'value_new'      => $valueNew,
            'modifiedby'     => $userId,
            'modifieddate'   => gmdate("Y-m-d H:i:s")
        ];

        $this->insert($data);
        return (int)$this->getAdapter()->lastInsertId($this->_name);
    }

    /**
     * Diffs an old record against an array of new values and writes flat change logs for every modified field.
     * Prevents "Phantom Deltas" by only logging if values genuinely changed.
     *
     * @param string $sourceModule
     * @param int $recordId
     * @param string|null $effectiveDate
     * @param array $oldRecord Full existing record from database
     * @param array $newValues Key-value array of new incoming values
     * @param array $trackedFields List of specific columns to track (e.g., ['payroll_multiplier', 'work_policy'])
     * @param int $userId
     * @return int Number of delta rows written
     */
    public function logBatchChanges(
        string $sourceModule,
        int $recordId,
        ?string $effectiveDate,
        array $oldRecord,
        array $newValues,
        array $trackedFields,
        int $userId
    ): int {
        $loggedCount = 0;

        foreach ($trackedFields as $field) {
            if (!array_key_exists($field, $newValues)) {
                continue;
            }

            $oldVal = isset($oldRecord[$field]) ? (string)$oldRecord[$field] : '';
            $newVal = (string)$newValues[$field];

            // Strict string comparison to detect genuine modifications
            if ($oldVal !== $newVal) {
                $this->logChange(
                    $sourceModule,
                    $recordId,
                    'UPDATED',
                    $effectiveDate,
                    $field,
                    $oldVal,
                    $newVal,
                    $userId
                );
                $loggedCount++;
            }
        }

        return $loggedCount;
    }
}
