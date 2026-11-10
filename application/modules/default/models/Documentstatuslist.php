<?php
class Default_Model_Documentstatuslist extends Zend_Db_Table_Abstract{
    protected $_name = 'main_iddocumentstatuslist';
    protected $_primary = 'iddocumentstatuslist_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['sl' => 'main_iddocumentstatuslist'], ['id' => 'sl.iddocumentstatuslist_id', 'value' => 'sl.value', 'iddocumentstatus_id' => 'sl.iddocumentstatus_id', 'description' => 'sl.description','isactive'=>'if(sl.isactive="Y","Yes","No")'])
            ->joinLeft(['s' => 'main_iddocumentstatus'], "sl.iddocumentstatus_id =s.iddocumentstatus_id", ['status_name'=>'s.name'])   
            ->order("$by $sort")
            ->where($where)
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
                if($key=='status_name'){
                    $searchQuery .= "s. " . $key . " like '%" . $val . "%' AND ";

                }else{
                    $searchQuery .= "sl. " . $key . " like '%" . $val . "%' AND ";

                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'documentstatuslist';
        $tableFields = [
            'action' => 'Action',
            'value' => 'Value',
            'status_name' => 'Status Name',
            'description' => 'Description',
            'isactive' => 'Active',
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
            // 'add'=>'add',
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

    public function getstatuslist($id)
    {
        $linecate = $this->select()
                         ->setIntegrityCheck(false)
                         ->where('iddocumentstatuslist_id='.$id);
                         return $this->fetchAll($linecate)->toArray();
    }

    public function getallstatuslist()
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('isactive="Y"');
        return $this->fetchAll($select)->toArray();
    }





}
?>