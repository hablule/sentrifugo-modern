<?php

/**
 * Holiday Criteria Model
 *
 * Manages targeting rules and demographic/structural eligibility for Holiday Master Policies.
 * Implements the Group Homogeneity DNF pattern.
 * Complies with SSOT v6.7.7.
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Holidaycriteria extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_holiday_criteria';
    protected $_primary = 'id';

    /**
     * Fetches all criteria rules for a specific holiday master policy.
     *
     * @param int $holidayId
     * @return array
     */
    public function getCriteriaByHolidayId(int $holidayId): array
    {
        $select = $this->select()
            ->where('holiday_id = ?', $holidayId)
            ->where('isactive != ?', 5)
            ->order(array('group_id ASC', 'criteria_type ASC'));

        return $this->fetchAll($select)->toArray();
    }

    /**
     * Surgically synchronizes the criteria array submitted from the Master Policy form.
     *
     * @param int $holidayId
     * @param array $criteriaPayload Array of rules: [['group_id' => 1, 'criteria_type' => 'Department', 'criteria_value' => '5', 'match_type' => 'MANDATORY', 'action' => 'new'|'delete'], ...]
     * @return void
     */
    public function syncCriteria(int $holidayId, array $criteriaPayload): void
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        foreach ($criteriaPayload as $rule) {
            $action = isset($rule['action']) ? $rule['action'] : 'new';
            $ruleId = isset($rule['id']) ? (int)$rule['id'] : 0;

            if ($action === 'delete' && $ruleId > 0) {
                // Hard delete is safe for configuration criteria mapping per SSOT II.8
                $this->delete(array('id = ?' => $ruleId));
            } elseif ($action === 'new' || $ruleId === 0) {
                // Prevent empty criteria values
                if (empty($rule['criteria_value'])) {
                    continue;
                }

                $this->insert(array(
                    'holiday_id'     => $holidayId,
                    'group_id'       => (int)$rule['group_id'],
                    'criteria_type'  => trim((string)$rule['criteria_type']),
                    'criteria_value' => trim((string)$rule['criteria_value']),
                    'match_type'     => trim((string)$rule['match_type']),
                    'isactive'       => 1,
                    'createdby'      => $userId,
                    'createddate'    => $currentDate
                ));
            }
        }
    }

    public function checkInUse($id)
    {
        return false; // Criteria is a leaf config mapping; can be deleted freely
    }
}
