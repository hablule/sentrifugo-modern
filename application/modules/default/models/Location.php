<?php
class Default_Model_Location extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_location';
    protected $_primary = 'location_id';

    public function getAddress($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "l.isactive='Y'";
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $adress = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('l' => 'main_location'), array('location_id' => 'l.location_id', 'address1' => 'l.address1', 'address2' => 'l.address2'))
            ->joinInner(array('c' => 'tbl_countries'), "c.id = l.country_id ", array('country' => 'c.country_name'))
            ->joinInner(array('s' => 'tbl_states'), "s.id = l.region_id ", array('region' => 's.state_name'))
            ->joinInner(array('ci' => 'tbl_cities'), "ci.id = l.city_id ", array('city' => 'ci.city_name'))
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
        return $adress;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = array();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {

                if ($key == 'country') {
                    $searchQuery .= " c.country_name like '%" . $val . "%' AND ";
                } else if ($key == 'region') {
                    $searchQuery .= " s.state_name like '%" . $val . "%' AND ";
                } else if ($key == 'city') {
                    $searchQuery .= " ci.city_name like '%" . $val . "%' AND ";
                } else {


                    $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'location';
        $tableFields = array(
            'action' => 'Action',
            'address1' => 'Address One',
            'address2' => 'Addres Two',

            'country' => 'Country',
            'region' => 'Region',
            'city' => 'city',
        );

        $tablecontent = $this->getAddress($sort, $by, $pageNo, $perPage, $searchQuery);
        $dataTmp = array(
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'extra' => array(),
            'tableheader' => $tableFields,
            'add' => 'add',
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,

            'call' => $call,
            'dashboardcall' => $dashboardcall
        );
        return $dataTmp;
    }
    public function SaveorUpdatelocationData($data, $where)
    {
        if ($where != '') {
            $this->update($data, $where);
            return 'update';
        } else {
            $this->insert($data);
            $id = $this->getAdapter()->lastInsertId('main_location');
            return $id;
        }
    }
    public function DeleteData($where)
    {
        $this->delete($where);
        return 'delete';
    }

    public function getLocations()
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('l' => 'main_location'), array('id' => 'l.location_id', 'location' => 'l.address1'))
            ->where('l.isactive="Y"');

        return $this->fetchAll($query)->toArray();
    }
    public function getLocationsbyid($id)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('l' => 'main_location'))
            ->where('l.isactive="Y" and l.location_id=' . $id);

        return $this->fetchAll($query)->toArray();
    }
    public function getsinglelocationData($id)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $locationData = $db->query("SELECT * FROM main_location WHERE location_id = " . $id . " AND isactive='Y'");
        $res = $locationData->fetchAll();
        if (isset($res) && !empty($res)) {
            return $res;
        } else
            return 'norows';
    }
    public function getsinglelocationviewData($id)
    {
        $where = "l.location_id = " . $id . " AND l.isactive='Y'";
        $adress = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('l' => 'main_location'), array('location_id' => 'l.location_id', 'address1' => 'l.address1', 'address2' => 'l.address2', 'address3' => 'l.address3', 'address4' => 'l.address4', 'map' => 'l.map_coordinate', 'isactive' => 'if(l.isactive="N","No","Yes")'))
            ->joinInner(array('c' => 'tbl_countries'), "c.id = l.country_id ", array('country' => 'c.country_name'))
            ->joinInner(array('s' => 'tbl_states'), "s.id = l.region_id ", array('state' => 's.state_name'))
            ->joinInner(array('ci' => 'tbl_cities'), "ci.id = l.city_id ", array('city' => 'ci.city_name'))
            ->where($where);
        return $this->fetchAll($adress)->toArray();
    }
    public function getlocationList()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from($this->_name, array('location_id', 'address1', 'address2', 'address3'))
            ->where('isactive = ?', 'Y');
        return $this->fetchAll($select)->toArray();
    }

    /**
     * Standard Pairs Retrieval for Rulebook Target Population.
     * Concatenates address fields as main_location lacks a direct 'name' column.
     * 
     * @param bool $activeOnly
     * @return array [location_id => address_string]
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, array(
                'id'   => 'location_id', // Map the PK
                // Build a readable label: e.g., "123 Main St, Suite 400"
                'name' => new Zend_Db_Expr("TRIM(CONCAT_WS(', ', NULLIF(address1, ''), NULLIF(address2, '')))")
            ))
            ->order('name ASC');

        if ($activeOnly) {
            // The schema dump shows isactive is tinyint(1) for this table
            $select->where('isactive = ?', 1);
        }

        return $this->getAdapter()->fetchPairs($select);
    }
}
