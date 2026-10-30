<?php
class Default_Model_militaryservicebranch extends Zend_Db_Table_Abstract{
    protected $_name = 'main_militaryservicebranch';
    protected $_primary = 'servicebranch_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1=1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->order("$by $sort")
            ->from('main_militaryservicebranch',array('*','servicebranch_id as id ,if(isactive="Y","Yes","No") as isactive'))
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
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'militaryservicebranch';
        $tableFields = array(
            'action' => 'Action',

            'name' => 'Name',
            'code' => 'Code',
            'description' => 'Description',
            'isactive' => 'Active',
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

    public function GetMilitaryBranch()
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('isactive="Y"');
        return $this->fetchAll($select)->toArray();
    }

    public function GetMilitaryServiceBranchById($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('servicebranch_id='.$id);
        return $this->fetchAll($select)->toArray();
    }

}
?>