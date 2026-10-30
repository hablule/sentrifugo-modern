<?php

/**
 * Requisition Position Model (The Pipeline Matrix)
 *
 * Manages the specific seats targeted by a requisition and logs lifecycle events.
 * Complies with SSOT v6.7.7 (Master-Detail JSON Sync, First-Class Junction).
 */
class Default_Model_Reqposition extends Zend_Db_Table_Abstract
{
    protected $_name = 'map_requisition_position';
    protected $_primary = 'id';

    public function getPositionsByRequisitionId(int $requisitionId): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('mrp' => $this->_name))
            ->joinInner(array('p' => 'main_position'), 'mrp.position_id = p.id', array('position_code' => 'p.code'))
            ->joinInner(array('jt' => 'main_jobtitle'), 'p.jobtitle_id = jt.id', array('jobtitle_name' => 'jt.name'))
            ->where('mrp.requisition_id = ?', $requisitionId)
            ->where('mrp.isactive != ?', 5);

        $results = $this->fetchAll($select)->toArray();

        foreach ($results as &$row) {
            $row['target_hire_date'] = !empty($row['target_hire_date']) ? sapp_Global::change_date($row['target_hire_date'], 'view') : '';
        }

        return $results;
    }

    public function getEventLogByRequisitionId(int $requisitionId): array
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('log' => 'log_requisition_position_status'))
            ->joinInner(array('mrp' => 'map_requisition_position'), 'log.map_req_pos_id = mrp.id', array())
            ->joinInner(array('p' => 'main_position'), 'mrp.position_id = p.id', array('position_code' => 'p.code'))
            ->joinLeft(array('rsr' => 'main_req_status_reason'), 'log.status_reason_id = rsr.id', array('reason_name' => 'rsr.name'))
            ->joinLeft(array('emp_a' => 'main_employee'), 'log.createdby = emp_a.id', array('actor_name' => new Zend_Db_Expr("COALESCE(NULLIF(emp_a.userfullname, ''), emp_a.firstname)")))
            ->joinLeft(array('emp_h' => 'main_employee'), 'log.filled_by_employee_id = emp_h.id', array('hired_name' => new Zend_Db_Expr("COALESCE(NULLIF(emp_h.userfullname, ''), emp_h.firstname)")))
            ->where('mrp.requisition_id = ?', $requisitionId)
            ->order('log.createddate DESC');

        $results = $this->fetchAll($select)->toArray();
        foreach ($results as &$row) {
            $row['createddate'] = sapp_Global::change_date($row['createddate'], 'view_time');
        }
        return $results;
    }

    /**
     * Surgically synchronizes the positions array submitted from the Requisition form.
     * Manages the Immutable Event Ledger (log_requisition_position_status).
     */
    public function syncPositions(int $requisitionId, array $positionsPayload): void
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;
        $currentDate = gmdate("Y-m-d H:i:s");
        $db = $this->getAdapter();

        foreach ($positionsPayload as $pos) {
            $mapId  = isset($pos['id']) ? (int)$pos['id'] : 0;
            // If mapId > 0, it is an existing record; default to 'update' unless explicitly marked 'delete'
            $action = isset($pos['action']) ? $pos['action'] : ($mapId > 0 ? 'update' : 'new');
            $status = isset($pos['status']) ? trim($pos['status']) : 'Targeted';

            $data = array(
                'requisition_id'   => $requisitionId,
                'position_id'      => (int)$pos['position_id'],
                'target_fte'       => (float)$pos['target_fte'],
                'target_hire_date' => !empty($pos['target_hire_date']) ? sapp_Global::change_date($pos['target_hire_date'], 'database') : null,
                'status'           => $status,
                'description'      => isset($pos['description']) ? trim((string)$pos['description']) : null,
                'isactive'         => 1
            );

            // 1. DELETE
            if ($action === 'delete' && $mapId > 0) {
                $this->update(array('isactive' => 5, 'modifiedby' => $userId, 'modifieddate' => $currentDate), array('id = ?' => $mapId));
            }
            // 2. UPDATE EXISTING RECORD (Only update if mapId > 0)
            elseif ($mapId > 0) {
                $oldRecord = $this->fetchRow(array('id = ?' => $mapId));

                $data['modifiedby']   = $userId;
                $data['modifieddate'] = $currentDate;
                $this->update($data, array('id = ?' => $mapId));

                // If status changed, write to immutable ledger
                if ($oldRecord && $oldRecord->status !== $status) {
                    $this->_logStatusEvent($mapId, $status, null, null, $userId, $currentDate);
                }
            }
            // 3. INSERT NEW RECORD (Only if mapId === 0)
            elseif ($mapId === 0) {
                $data['createdby']   = $userId;
                $data['createddate'] = $currentDate;
                $newMapId = (int) $this->insert($data);

                // Initial status log
                $this->_logStatusEvent($newMapId, 'Targeted', null, "Seat added to requisition.", $userId, $currentDate);
            }
        }
    }

    /**
     * Helper to write to the Immutable Event Ledger
     */
    protected function _logStatusEvent(int $mapId, string $status, ?int $reasonId, ?string $notes, int $userId, string $date)
    {
        $this->getAdapter()->insert('log_requisition_position_status', array(
            'map_req_pos_id'   => $mapId,
            'status'           => $status,
            'status_reason_id' => $reasonId,
            'notes'            => $notes,
            'createdby'        => $userId,
            'createddate'      => $date
        ));
    }
}
