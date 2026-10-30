<?php
class Default_Model_Leavecategory extends Zend_Db_Table_Abstract{
    protected $_name = 'main_leavecategory';
    protected $_primary = 'leavecategory_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;
        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('lc' => 'main_leavecategory'), array('leavecategory_id' => 'lc.leavecategory_id', 'name'=>'lc.name','description'=>'lc.description','isactive'=>'if(lc.isactive="N","No","Yes")'))
        ->where($where)
        ->order("$by $sort")
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
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'leavecategory';
        $tableFields = array(
            'action' => 'Action',
            'name' => 'Name',
            'description' => 'Description',
			'isactive'=>'Active',
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
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'call' => $call,
            'add' => 'add',

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
    public function getsingleData($id)
	{
	
		$select = $this->select()
						->setIntegrityCheck(false)
                        ->from(array('lc' => 'main_leavecategory'),array('name' => 'lc.name','description' => 'lc.description','isactivecheck' => 'lc.isactive'))
						->where('lc.leavecategory_id='.$id.'');
		return $this->fetchAll($select)->toArray();
	}
    public function getleavecategory(){
        $query = $this->select()
                   ->setIntegrityCheck(false)
                   ->from(array('lc'=>'main_leavecategory'))
                   ->where('lc.isactive="Y"');
       return $this->fetchAll($query)->toArray();
    }
   
}
?>