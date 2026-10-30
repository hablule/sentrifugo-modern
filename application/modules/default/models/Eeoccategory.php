<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
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
 * EEOC Category Model
 *
 * Manages Equal Employment Opportunity compliance classifications.
 * Complies with SSOT v6.7.7 (Singular Naming, Zend_Db_Select, Structured Method Groups).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Eeoccategory extends Zend_Db_Table_Abstract
{
    /* --------------------------------------------------------------
     * Attributes (Visibility -> Alphabetical)
     * -------------------------------------------------------------- */

	/** @var string Primary table name (SSOT Singular) */
	protected $_name = 'main_eeoccategory';

	/** @var string Primary key */
	protected $_primary = 'id';

	/* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

	public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
	{
		$select = $this->select()->where('isactive != ?', 5);

		if ($activeStatus !== null) {
			$select->where('isactive = ?', (int)$activeStatus);
		}

		if (!empty($search_val) && !empty($search_str)) {
			$select->where("$search_val LIKE ?", '%' . trim((string)$search_str) . '%');
		}

		if ($limit !== null) {
			$select->limit((int)$limit, (int)$offset);
		}

		return $this->fetchAll($select)->toArray();
	}

	public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
	{
		$searchQuery = '';
		$searchArray = array();

		if (!empty($searchData) && $searchData !== 'undefined') {
			$searchValues = json_decode($searchData, true);
			if (is_array($searchValues)) {
				foreach ($searchValues as $key => $val) {
					if ($key === 'status_text') {
						$dbVal = ($val === 'Active') ? 1 : 0;
						$searchQuery .= " e.isactive = " . $dbVal . " AND ";
					} else {
						$searchQuery .= " e." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
					}
					$searchArray[$key] = $val;
				}
				$searchQuery = rtrim($searchQuery, " AND ");
			}
		}

		$tableHeaders = array(
			'action'      => 'Action',
			'code'        => 'Code',
			'name'        => 'EEOC Category Name',
			'description' => 'Description',
			'status_text' => 'Status'
		);

		$tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

		return array(
			'sort'           => $sort,
			'by'             => $by,
			'pageNo'         => $pageNo,
			'perPage'        => $perPage,
			'tablecontent'   => $tableContent,
			'objectname'     => 'eeoccategory',
			'extra'          => array(),
			'tableheader'    => $tableHeaders,
			'jsGridFnName'   => 'getAjaxgridData',
			'jsFillFnName'   => '',
			'searchArray'    => $searchArray,
			'menuName'       => 'EEOC Categories',
			'call'           => $call,
			'dashboardcall'  => $dashboardcall,
			'search_filters' => array(
				'status_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'))
			)
		);
	}

	public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
	{
		if ($by === 'status_text') {
			$by = 'e.isactive';
		}

		$select = $this->select()
			->setIntegrityCheck(false)
			->from(
				array('e' => $this->_name),
				array(
					'id' => 'e.id',
					'code',
					'name',
					'description',
					'status_text' => new Zend_Db_Expr("CASE WHEN e.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
				)
			)
			->where('e.isactive != ?', 5);

		if (!empty($searchQuery)) {
			$select->where($searchQuery);
		}

		$select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

		return $select;
	}

	public function getPairs($activeOnly = true)
	{
		$select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
		if ($activeOnly) {
			$select->where('isactive = ?', 1);
		}
		return $this->getAdapter()->fetchPairs($select);
	}

	public function getRecordById($id)
	{
		$select = $this->select()
			->where('id = ?', (int)$id)
			->where('isactive != ?', 5);

		$row = $this->fetchRow($select);
		return $row ? $row->toArray() : null;
	}

	/* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

	public function saveRecord(array $data, $id = null)
	{
		$auth = Zend_Auth::getInstance();
		$userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

		$currentDate = gmdate("Y-m-d H:i:s");

		if (!empty($id)) {
			$data['modifiedby']   = $userId;
			$data['modifieddate'] = $currentDate;
			$this->update($data, array('id = ?' => (int)$id));
			return 'update';
		}

		$data['createdby']   = $userId;
		$data['createddate'] = $currentDate;
		$this->insert($data);
		return (int) $this->getAdapter()->lastInsertId($this->_name);
	}

	/* =================================================================================
     * GROUP 4: REMOVAL
     * ================================================================================= */

	public function deleteRecord($id)
	{
		$auth = Zend_Auth::getInstance();
		$userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

		$data = array(
			'isactive'     => 5,
			'modifiedby'   => $userId,
			'modifieddate' => gmdate("Y-m-d H:i:s")
		);

		return $this->update($data, array('id = ?' => (int)$id));
	}

	public function toggleStatus($id, $status)
	{
		$auth = Zend_Auth::getInstance();
		$userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

		$data = array(
			'isactive'     => (int)$status,
			'modifiedby'   => $userId,
			'modifieddate' => gmdate("Y-m-d H:i:s")
		);

		return $this->update($data, array('id = ?' => (int)$id));
	}

	/* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

	public function checkDuplicate($field, $value, $excludeId = null)
	{
		$select = $this->select()
			->where("$field = ?", trim((string)$value))
			->where('isactive != ?', 5);

		if (!empty($excludeId)) {
			$select->where('id != ?', (int)$excludeId);
		}

		return $this->fetchRow($select);
	}

	public function checkInUse($id)
	{
		$db = $this->getAdapter();

		// Dependency check: Cannot delete if Job Titles use this EEOC category
		$jtCheck = $db->select()
			->from('main_jobtitle', array('id'))
			->where('eeoccategory_id = ?', (int)$id)
			->where('isactive != ?', 5);

		return (bool)$db->fetchRow($jtCheck);
	}
}
