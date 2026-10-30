<?php
class Default_Model_healthseveritylevel extends Zend_Db_Table_Abstract{
    protected $_name = 'main_healthseveritylevel';
    protected $_primary = 'severitylevel_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('hsl'=>'main_healthseveritylevel'),array('hsl.*','hsl.severitylevel_id as id,if(hsl.isactive="Y","Yes","No") as isactive'))
            ->join(array('hs'=>'main_healthseverity'),'hsl.healthseverity_id=hs.healthseverity_id',array('hs.name'))
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
        $objName = 'healthseveritylevel';
        $tableFields = array(
            'action' => 'Action',
            'name' => 'Severity',
            'value' => 'Value',
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

    public function GetHealthSeverityLevel($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('severitylevel_id='.$id);
        return $this->fetchAll($select)->toArray();
    }

    public function GetHealthseveritylevelSelect()
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('isactive="Y"');
        return $this->fetchAll($select)->toArray();
    }

}
?>