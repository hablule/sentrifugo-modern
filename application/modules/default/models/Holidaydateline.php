<?php

/**
 * Holiday Date Line Model (Occurrence Discrete Lines)
 *
 * Manages the system-generated, immutable single-date helper records for fast O(1) queries.
 * Strict Rule: No line-level business logic or overrides.
 * Complies with SSOT v6.7.7.
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Holidaydateline extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_holiday_date_line';
    protected $_primary = 'id';

    /**
     * Inserts a single discrete line.
     * 
     * @param array $data
     * @return int Last insert ID
     */
    public function saveRecord(array $data)
    {
        $this->insert($data);
        return (int) $this->getAdapter()->lastInsertId($this->_name);
    }

    /**
     * Soft deletes all lines for a given header ID.
     * 
     * @param int $headerId
     * @param int $userId
     * @param string $currentDate
     * @return int
     */
    public function softDeleteLinesByHeaderId(int $headerId, int $userId, string $currentDate): int
    {
        return $this->update(
            array('isactive' => 5, 'modifieddate' => $currentDate),
            array('holiday_date_id = ?' => $headerId)
        );
    }

    /**
     * Hard deletes lines for regeneration/syncing.
     * 
     * @param int $headerId
     * @return int
     */
    public function hardDeleteLinesByHeaderId(int $headerId): int
    {
        return $this->delete(array('holiday_date_id = ?' => $headerId));
    }
}
