<?php
class Default_Model_iddocumentstatus extends Zend_Db_Table_Abstract{
    protected $_name = 'main_iddocumentstatus';
    protected $_primary = 'iddocumentstatus_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1=1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from('main_iddocumentstatus',['*','iddocumentstatus_id as id,if(isactive="Y","Yes","No") as isactive'])
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
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'iddocumentstatus';
        $tableFields = [
            'action' => 'Action',

            'name' => 'Name',
            'code' => 'Code',
            'description' => 'Description',
            'valuetype' => 'Value',
            'isactive' => 'Active'
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
            'search_filters'=>[
                'isactive'=>[
                    'type'=>'select',
                    'filter_data'=>[''=>'All',1=>'Yes',0=>'No']
                ]
                ],
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

    public function GetIdDocumentById($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('iddocumentstatus_id='.$id);
        return $this->fetchAll($select)->toArray();
    }
    public function GetIdDocumentByvalue()
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('valuetype="LIST"');
        return $this->fetchAll($select)->toArray();
    }

    public function getallstatus()
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->where('isactive="Y"');

        return $this->fetchAll($select)->toArray();

    }
}
?>