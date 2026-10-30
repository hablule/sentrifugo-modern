<?php
class Default_Model_Documentprovider extends Zend_Db_Table_Abstract{
    protected $_name = 'main_iddocumentprovider';
    protected $_primary = 'iddocumentprovider_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('l' => 'main_iddocumentprovider'), array('id' => 'l.iddocumentprovider_id', 'name' => 'l.name', 'code' => 'l.code', 'description' => 'l.description','isactive'=>'if(l.isactive="Y","Yes","No")'))
            ->joinLeft(array('c' => 'tbl_countries'), "l.country_id=c.id ", array('country' => 'c.country_name'))
            ->joinLeft(array('s' => 'tbl_states'), "l.state_id=s.id", array('region' => 's.state_name'))
            ->joinLeft(array('ci' => 'tbl_cities'), "l.city_id=ci.id ", array('city' => 'ci.city_name'))            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
       
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = array();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                if($key=='country'){
                    $searchQuery .= "c.country_name like '%" . $val . "%' AND ";

                }
                else if($key=='region'){
                    $searchQuery .= "s.region_name like '%" . $val . "%' AND ";

                }
                else if($key=='city'){
                    $searchQuery .= "c.city_name like '%" . $val . "%' AND ";

                }
                else{
                    $searchQuery .= "l. " . $key . " like '%" . $val . "%' AND ";

                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'documentprovider';
        $tableFields = array(
            'action' => 'Action',
            'name' => 'Name',
            'code' => 'code',
            'description' => 'Description',
            'isactive' => 'Active',
            'country' => 'Country',
            'region' => 'State',
            'city' => 'City',

        );

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery);
        $dataTmp = array(
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'extra' => array(),
            // 'add'=>'add',
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => array(
                'isactive' => array(
                    'type' => 'select',
                    'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
            )
        );
        return $dataTmp;
    }

    public function SaveorUpdateData($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId($this->_name);
			return $id;
		}
		
	}

    public function getprovider($id)
    {
        $linecate = $this->select()
                         ->setIntegrityCheck(false)
                         ->where('iddocumentprovider_id='.$id);
                         return $this->fetchAll($linecate)->toArray();
                        }
   public function getallprovider()
                        {
     $linecate = $this->select()
              ->setIntegrityCheck(false)
             ->where('isactive="Y"');
            return $this->fetchAll($linecate)->toArray();
                }


}
?>