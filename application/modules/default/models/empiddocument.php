<?php
class Default_Model_empiddocument extends Zend_Db_Table_Abstract{
    protected $_name = 'main_empiddocument';
    protected $_primary = 'empiddocument_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery, $id)
    {
        $where = "empid=".$id;
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

         
        
        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['empidd'=>'main_empiddocument'],['empidd.*','empidd.empiddocument_id as id'])
            ->join(['idd'=>'main_iddocument'],'idd.iddocument_id = empidd.iddocument_id',['idd.name as Dname'])
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
       
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1='',$exParam2='',$exParam3='',$exParam4='')
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
        $objName = 'empiddocument';
        $tableFields = [
            'action' => 'Action',
            'idnumber' => 'ID',
            'Dname' => 'Document',
            'description' => 'Description',
            'issuedate' => 'Issue Date',
            'expirydate' => 'Expire Date',
            'iddocumentstatus_id'=>'Status'
        ];

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery,$exParam1);
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
            'menuName' => 'Document',

            'formgrid'=>'true',
            'unitId'=>$exParam1,
			'userAction' => $exParam3,
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall
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

    public function getempiddocumentbyid($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(['eidd'=>'main_empiddocument'])
                       ->join(['idp'=>'main_iddocumentprovider'],'idp.iddocumentprovider_id = eidd.iddocumentprovider_id',['idp.name as Pname'])
                       ->join(['ids'=>'main_iddocumentstatuslist'],'ids.iddocumentstatuslist_id = eidd.iddocumentstatuslist_id',['ids.value as Sname'])
                       ->join(['idd'=>'main_iddocument'],'idd.iddocument_id = eidd.iddocument_id',['idd.name as Iname'])
                       ->where('eidd.empiddocument_id='.$id);
        return $this->fetchAll($select)->toArray();
    }

    public function getDocumentByEmpId($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(['eidd'=>'main_empiddocument'])
                       ->join(['idp'=>'main_iddocumentprovider'],'idp.iddocumentprovider_id = eidd.iddocumentprovider_id',['idp.name as Pname'])
                       ->join(['ids'=>'main_iddocumentstatuslist'],'ids.iddocumentstatuslist_id = eidd.iddocumentstatuslist_id',['ids.value as Sname'])
                       ->join(['idd'=>'main_iddocument'],'idd.iddocument_id = eidd.iddocument_id',['idd.name as Iname'])
                       ->where('eidd.empid='.$id);
        return $this->fetchAll($select)->toArray();
    }
  
}
?>