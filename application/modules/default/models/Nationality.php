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
 * Nationality Lookup Model
 * 
 * Manages legal nationality statuses (e.g., British Citizen, US National) used in 
 * employee profiles and right-to-work compliance.
 * Strictly adheres to SSOT v6.1: Zend_Db_Select, dependency checks, 
 * standardized grid formatting, and grouped method architecture.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Nationality extends Zend_Db_Table_Abstract
{
    /** @var string Table name */
    protected $_name = 'main_nationality';

    /** @var string Primary key */
    protected $_primary = 'id';

    /* =================================================================================
     * GROUP 1: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    /**
     * Standard retrieval of all records.
     * 
     * @param int|null    $limit
     * @param int|null    $offset
     * @param string|null $search_val
     * @param string|null $search_str
     * @param int|null    $activeStatus
     * @return array
     */
    public function getAllRecords($limit = null, $offset = null, $search_val = null, $search_str = null, $activeStatus = null)
    {
        $select = $this->select()->where('isactive != 5');

        if ($activeStatus !== null) {
            $select->where('isactive = ?', (int)$activeStatus);
        }

        if (!empty($search_val) && !empty($search_str)) {
            $select->where("$search_val LIKE ?", '%' . $search_str . '%');
        }

        $select->order('name ASC');

        if ($limit !== null) {
            $select->limit($limit, (int)$offset);
        }

        return $this->fetchAll($select)->toArray();
    }

    /**
     * SSOT Standard Grid Configuration.
     * Generates the matrix properties for the Sentrifugo Grid Helper.
     * 
     * @param string $sort
     * @param string $by
     * @param int    $perPage
     * @param int    $pageNo
     * @param string $searchData
     * @param string $call
     * @param string $dashboardcall
     * @return array
     */
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData, true);
            foreach ($searchValues as $key => $val) {
                if ($key == 'status_text') {
                    // Safely translate UI Status Text to DB Integers
                    if ($val === 'Active') {
                        $searchQuery .= " n.isactive = 1 AND ";
                    } elseif ($val === 'Inactive') {
                        $searchQuery .= " n.isactive = 0 AND ";
                    }
                } elseif ($key == 'citizenship_text') {
                    // Safely translate Citizenship Flag to DB Integers
                    if ($val === 'Yes') {
                        $searchQuery .= " n.citizenship = 1 AND ";
                    } elseif ($val === 'No') {
                        $searchQuery .= " n.citizenship = 0 AND ";
                    }
                } else {
                    $searchQuery .= " n.$key LIKE '%$val%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }

        // Must match exact column aliases defined in getGridData()
        $tableHeaders = array(
            'action'           => 'Action',
            'name'             => 'Nationality (Legal Status)',
            'code'             => 'Code',
            'citizenship_text' => 'Confers Citizenship',
            'description'      => 'Description',
            'status_text'      => 'Status'
        );

        $tableContent = $this->getGridData($sort, $by, $pageNo, $perPage, $searchQuery);

        return array(
            'sort'           => $sort,
            'by'             => $by,
            'pageNo'         => $pageNo,
            'perPage'        => $perPage,
            'tablecontent'   => $tableContent,
            'objectname'     => 'nationality',
            'tableheader'    => $tableHeaders,
            'extra'          => array(),
            'jsGridFnName'   => 'getAjaxgridData',
            'jsFillFnName'   => '',
            'searchArray'    => $searchArray,
            // SSOT IV: No 'add' flag injected here. Handled by core ACL.
            'menuName'       => 'Nationalities',
            'call'           => $call,
            'dashboardcall'  => $dashboardcall,
            'search_filters' => array(
                'status_text'      => array('type' => 'select', 'filter_data' => array('' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive')),
                'citizenship_text' => array('type' => 'select', 'filter_data' => array('' => 'All', 'Yes' => 'Yes', 'No' => 'No'))
            )
        );
    }

    /**
     * SSOT Standard Grid Data Fetcher.
     * 
     * @param string $sort
     * @param string $by
     * @param int    $pageNo
     * @param int    $perPage
     * @param string $searchQuery
     * @return Zend_Db_Select
     */
    public function getGridData($sort, $by, $pageNo, $perPage, $searchQuery = '')
    {
        if ($by == 'status_text') {
            $by = 'n.isactive';
        } elseif ($by == 'citizenship_text') {
            $by = 'n.citizenship';
        }

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('n' => $this->_name), array(
                'id' => 'n.id',
                '*',
                // Alias boolean columns so the grid helper displays readable text
                'status_text'      => new Zend_Db_Expr("CASE WHEN n.isactive = 1 THEN 'Active' ELSE 'Inactive' END"),
                'citizenship_text' => new Zend_Db_Expr("CASE WHEN n.citizenship = 1 THEN 'Yes' ELSE 'No' END")
            ))
            ->where('n.isactive != 5');

        if (!empty($searchQuery)) {
            $select->where($searchQuery);
        }

        $select->order("$by $sort")->limitPage((int)$pageNo, (int)$perPage);

        return $select;
    }

    /**
     * Standard Pairs Retrieval (Used for Dropdowns in Popups/Forms).
     * 
     * @param bool $activeOnly
     * @return array [id => name]
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()->from($this->_name, array('id', 'name'))->order('name ASC');
        if ($activeOnly) {
            $select->where("isactive = 1");
        }
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Standard ID Retrieval.
     * 
     * @param int $id
     * @return array|null
     */
    public function getRecordById($id)
    {
        $select = $this->select()->where('id = ?', (int)$id)->where('isactive != 5');
        $row = $this->fetchRow($select);
        return ($row) ? $row->toArray() : null;
    }

    /* =================================================================================
     * GROUP 2: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    /**
     * Standard Save/Update.
     * 
     * @param array $data DB Columns mapping
     * @param int   $id   Optional ID for updating
     * @return int|string Last insert ID or 'update'
     */
    public function saveRecord(array $data, $id = null)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        if (!empty($id)) {
            $data['modifiedby']   = $userId;
            $data['modifieddate'] = gmdate("Y-m-d H:i:s");
            $this->update($data, array('id = ?' => (int)$id));
            return 'update';
        }

        $data['createdby']   = $userId;
        $data['createddate'] = gmdate("Y-m-d H:i:s");
        $this->insert($data);
        return $this->getAdapter()->lastInsertId($this->_name);
    }

    /* =================================================================================
     * GROUP 3: REMOVAL
     * ================================================================================= */

    /**
     * Soft delete standard execution. Checks dependencies first per SSOT IV.
     * 
     * @param int $id
     * @return int
     * @throws Exception if record is in use
     */
    public function deleteRecord($id)
    {
        if ($this->checkInUse($id)) {
            throw new Exception("Cannot delete this Nationality as it is currently assigned to an employee profile.");
        }

        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = array(
            'isactive'     => 5,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /**
     * Standard active status toggle.
     * 
     * @param int $id
     * @param int $status
     * @return int
     */
    public function toggleStatus($id, $status)
    {
        $auth = Zend_Auth::getInstance();
        $userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;

        $data = array(
            'isactive'     => (int)$status,
            'modifiedby'   => $userId,
            'modifieddate' => gmdate("Y-m-d H:i:s")
        );
        return $this->update($data, array('id = ?' => (int)$id));
    }

    /* =================================================================================
     * GROUP 4: INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

    /**
     * Standard duplicate field checker.
     * 
     * @param string   $field
     * @param string   $value
     * @param int|null $excludeId
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function checkDuplicate($field, $value, $excludeId = null)
    {
        $select = $this->select()
            ->where("$field = ?", trim($value))
            ->where("isactive != 5");

        if ($excludeId) {
            $select->where('id != ?', (int)$excludeId);
        }
        return $this->fetchRow($select);
    }

    /**
     * Dependency Checker (SSOT IV Data Integrity)
     * Verifies if the nationality is assigned to an employee in `main_emppersonaldetails`.
     * 
     * @param int $id
     * @return bool
     */
    public function checkInUse($id)
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from('main_emppersonaldetails', 'id')
            ->where('nationalityid = ?', (int)$id)
            ->limit(1);

        return (bool)$db->fetchOne($select);
    }

    /* =================================================================================
     * GROUP 5: JUNCTION TABLE SYNC (MANY-TO-MANY)
     * ================================================================================= */

    /**
     * Retrieves an array of Country IDs currently mapped to a specific Nationality.
     * Used to pre-populate the multi-select box on the Edit screen.
     * 
     * @param int $nationalityId
     * @return array Flat array of Country IDs
     */
    public function getMappedCountryIds($nationalityId)
    {
        if (empty($nationalityId)) return array();

        $db = $this->getAdapter();
        $select = $db->select()
            ->from('map_country_nationality', 'country_id')
            ->where('nationality_id = ?', (int)$nationalityId)
            ->where('isactive != 5');

        return $db->fetchCol($select);
    }

    /**
     * Synchronizes the Many-to-Many relationship between Nationalities and Countries.
     * Inserts new mappings, removes unchecked mappings, and handles the 'is_default' logic
     * where the first item in the array is treated as the primary country context.
     * 
     * @param int   $nationalityId The ID of the Nationality being saved
     * @param array $countryIds    Array of Country IDs selected in the UI
     * @return void
     */
    public function syncCountryMappings($nationalityId, array $countryIds)
    {
        $db = $this->getAdapter();
        $userId = Zend_Auth::getInstance()->getStorage()->read()->id ?? 0;
        $currentDate = gmdate("Y-m-d H:i:s");

        // 1. Fetch current mappings to determine what to add/remove
        $currentMappedIds = $this->getMappedCountryIds($nationalityId);

        // 2. Determine differences
        $toAdd    = array_diff($countryIds, $currentMappedIds);
        $toRemove = array_diff($currentMappedIds, $countryIds);

        // 3. Remove unselected mappings (Hard delete for junction tables)
        if (!empty($toRemove)) {
            $db->delete('map_country_nationality', array(
                'nationality_id = ?' => (int)$nationalityId,
                'country_id IN (?)'  => $toRemove
            ));
        }

        // 4. Insert new mappings
        if (!empty($toAdd)) {
            foreach ($toAdd as $index => $cId) {
                // Feature: The very first item in the submitted array becomes the default
                $isDefault = ($index === 0) ? 1 : 0;

                $db->insert('map_country_nationality', array(
                    'nationality_id' => (int)$nationalityId,
                    'country_id'     => (int)$cId,
                    'is_default'     => $isDefault,
                    'isactive'       => 1,
                    'createdby'      => $userId,
                    'createddate'    => $currentDate
                ));
            }
        }

        // 5. Update the 'is_default' flag if the user re-ordered existing selections
        // Even if no new items were added, the first item in the array must be enforced as default.
        if (!empty($countryIds)) {
            $firstCountryId = $countryIds[0];

            // Strip default from all mappings for this nationality
            $db->update(
                'map_country_nationality',
                array('is_default' => 0, 'modifiedby' => $userId, 'modifieddate' => $currentDate),
                array('nationality_id = ?' => (int)$nationalityId)
            );

            // Apply default to the chosen first country
            $db->update(
                'map_country_nationality',
                array('is_default' => 1, 'modifiedby' => $userId, 'modifieddate' => $currentDate),
                array('nationality_id = ?' => (int)$nationalityId, 'country_id = ?' => (int)$firstCountryId)
            );
        }
    }
}
