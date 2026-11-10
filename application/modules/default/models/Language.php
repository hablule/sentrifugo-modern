<?php

/**
 * Language Model
 *
 * Manages system-recognized languages and locales.
 * Complies with SSOT v6.7.7 (Singular Naming, Zend_Db_Select, Structured Method Groups).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Language extends Zend_Db_Table_Abstract
{
    /* --------------------------------------------------------------
     * Attributes (Visibility -> Alphabetical)
     * -------------------------------------------------------------- */

	/** @var string Primary table name (SSOT Singular) */
	protected $_name = 'main_language';

	/** @var string Primary key */
	protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

	/**
	 * Fetch all records with optional filtering and pagination.
	 * 
	 * @param int|null $limit
	 * @param int|null $offset
	 * @param string|null $search_val
	 * @param string|null $search_str
	 * @param int|string|null $activeStatus
	 * @return array
	 */
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

	/**
	 * Builds the Grid configuration array for the UI.
	 * SSOT v6.7.7: 'action' MUST be first. No 'add' flag passed.
	 * 
	 * @param string $sort
	 * @param string $by
	 * @param int $perPage
	 * @param int $pageNo
	 * @param string $searchData
	 * @param string $call
	 * @param string $dashboardcall
	 * @return array
	 */
	public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
	{
		$searchQuery = '';
		$searchArray = [];

		if (!empty($searchData) && $searchData !== 'undefined') {
			$searchValues = json_decode($searchData, true);
			if (is_array($searchValues)) {
				foreach ($searchValues as $key => $val) {
					if ($key === 'status_text') {
						$dbVal = ($val === 'Active') ? 1 : 0;
						$searchQuery .= " l.isactive = " . $dbVal . " AND ";
					} else {
						$searchQuery .= " l." . $key . " LIKE '%" . trim((string)$val) . "%' AND ";
					}
					$searchArray[$key] = $val;
				}
				$searchQuery = rtrim($searchQuery, " AND ");
			}
		}

		// SSOT Mandatory: 'action' => 'Action' must be the first element
		$tableHeaders = [
			'action'      => 'Action',
			'code'        => 'Language Code',
			'name'        => 'Language Name',
			'description' => 'Description',
			'status_text' => 'Status'
		];

		$tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

		return [
			'sort'           => $sort,
			'by'             => $by,
			'pageNo'         => $pageNo,
			'perPage'        => $perPage,
			'tablecontent'   => $tableContent,
			'objectname'     => 'language',
			'extra'          => [],
			'tableheader'    => $tableHeaders,
			'jsGridFnName'   => 'getAjaxgridData',
			'jsFillFnName'   => '',
			'searchArray'    => $searchArray,
			'menuName'       => 'Languages',
			'call'           => $call,
			'dashboardcall'  => $dashboardcall,
			'search_filters' => [
				'status_text' => ['type' => 'select', 'filter_data' => ['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive']]
			]
		];
	}

	/**
	 * Returns the raw Zend_Db_Select object for the Grid.
	 * 
	 * @param string $sort
	 * @param string $by
	 * @param int $pageNo
	 * @param int $perPage
	 * @param string $searchQuery
	 * @return Zend_Db_Select
	 */
	public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
	{
		if ($by === 'status_text') {
			$by = 'l.isactive';
		}

		$select = $this->select()
			->setIntegrityCheck(false)
			->from(
				['l' => $this->_name],
				[
					'id' => 'l.id',
					'code',
					'name',
					'description',
					'status_text' => new Zend_Db_Expr("CASE WHEN l.isactive = 1 THEN 'Active' ELSE 'Inactive' END")
				]
			)
			->where('l.isactive != ?', 5);

		if (!empty($searchQuery)) {
			$select->where($searchQuery);
		}

		$select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

		return $select;
	}

	/**
	 * Generates a flat ID => Name array for Select dropdowns.
	 * 
	 * @param bool $activeOnly
	 * @return array
	 */
	public function getPairs($activeOnly = true)
	{
		$select = $this->select()->from($this->_name, ['id', 'name'])->order('name ASC');
		if ($activeOnly) {
			$select->where('isactive = ?', 1);
		}
		return $this->getAdapter()->fetchPairs($select);
	}

	/**
	 * Fetch a single record by Primary Key.
	 * 
	 * @param int $id
	 * @return array|null
	 */
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

	/**
	 * Standard persistence method handling Add and Edit.
	 * 
	 * @param array $data
	 * @param int|null $id
	 * @return int|string Last Insert ID or 'update'
	 */
	public function saveRecord(array $data, $id = null)
	{
		$auth = Zend_Auth::getInstance();
		$userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

		$currentDate = gmdate("Y-m-d H:i:s");

		if (!empty($id)) {
			$data['modifiedby']   = $userId;
			$data['modifieddate'] = $currentDate;
			$this->update($data, ['id = ?' => (int)$id]);
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

	/**
	 * Safely soft-deletes a record.
	 * 
	 * @param int $id
	 * @return int Number of rows updated
	 */
	public function deleteRecord($id)
	{
		$auth = Zend_Auth::getInstance();
		$userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

		$data = [
			'isactive'     => 5,
			'modifiedby'   => $userId,
			'modifieddate' => gmdate("Y-m-d H:i:s")
		];

		return $this->update($data, ['id = ?' => (int)$id]);
	}

	/**
	 * Toggles the Active (1) / Inactive (0) status.
	 * 
	 * @param int $id
	 * @param int|string $status
	 * @return int Number of rows updated
	 */
	public function toggleStatus($id, $status)
	{
		$auth = Zend_Auth::getInstance();
		$userId = $auth->hasIdentity() ? (int)$auth->getStorage()->read()->id : 0;

		$data = [
			'isactive'     => (int)$status,
			'modifiedby'   => $userId,
			'modifieddate' => gmdate("Y-m-d H:i:s")
		];

		return $this->update($data, ['id = ?' => (int)$id]);
	}

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

	/**
	 * Validate against duplicate entries.
	 * 
	 * @param string $field
	 * @param string $value
	 * @param int|null $excludeId
	 * @return Zend_Db_Table_Row_Abstract|null
	 */
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

	/**
	 * Dependency Check prior to deletion.
	 * Evaluates if Language is linked to Employee Personal Details, Site Preferences, or Candidates.
	 * 
	 * @param int $id
	 * @return bool True if record is actively used and cannot be deleted
	 */
	public function checkInUse($id)
	{
		$db = $this->getAdapter();

		// 1. Check Employee Personal Details
		$empCheck = $db->select()
			->from('main_emppersonaldetails', ['id'])
			->where('languageid = ?', (int)$id)
			->where('isactive != ?', '5');
		if ($db->fetchRow($empCheck)) return true;

		// 2. Check Site Preferences (Global Default Locale)
		$prefCheck = $db->select()
			->from('main_sitepreference', ['id'])
			->where('language_id = ?', (int)$id)
			->where('isactive != ?', 5);
		if ($db->fetchRow($prefCheck)) return true;

		// 3. Check Candidate Language Skills
		$candCheck = $db->select()
			->from('main_candlang', ['id'])
			->where('native_language = ?', (int)$id);
		if ($db->fetchRow($candCheck)) return true;

		return false;
	}
}
