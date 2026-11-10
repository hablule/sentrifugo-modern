<?php
class Default_Model_healthissue extends Zend_Db_Table_Abstract{
    protected $_name = 'main_healthissue';
    protected $_primary = 'healthissue_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1=1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['hi'=>'main_healthissue'],['hi.*','hi.healthissue_id as id','if(hi.isactive="Y","Yes","No") as isactive'])
            ->join(['hs'=>'main_healthseverity'],'hi.healthseverity_id = hs.healthseverity_id',['hs.name as severity_name'])
            ->join(['hit'=>'main_healthissuetype'],'hit.issuetype_id = hi.issuetype_id',['hit.name as issue_name'])
            ->join(['isc'=>'main_healthissuecategory'],'hi.issuecategory_id=isc.issuecategory_id',['isc.name as isc_name'])
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
       
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = [];
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
               /*  $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val; */
                if($key=='severity_name'){
                    $searchQuery .= "hs.name like '%" . $val . "%' AND ";

                }
                if($key=='isc_name'){
                    $searchQuery .= "isc.name like '%" . $val . "%' AND ";

                }
                if($key=='issue_name'){
                    $searchQuery .= "hit.name like '%" . $val . "%' AND ";

                }
                else
                $searchQuery .= "hi." . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'healthissue';
        $tableFields = [
            'action' => 'Action',

            'name' => 'Name',
            'code' => 'code',
            'severity_name' => 'Severity',
            'issue_name' => 'Issue type',
            'isc_name'=>'Issue category',
            'isactive'=>'Active'
        ];

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery);
        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
            
                'isactive' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
    ]
        ];
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

    public function GetHealthIssueById($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('healthissue_id='.$id);
      return $this->fetchAll($select)->toArray();
    }

    public function GetHealthIssue()
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('isactive="Y"');
        return $this->fetchAll($select)->toArray();
    }

  

}
?>