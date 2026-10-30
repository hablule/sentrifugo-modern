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
            ->from(array('empidd'=>'main_empiddocument'),array('empidd.*','empidd.empiddocument_id as id'))
            ->join(array('idd'=>'main_iddocument'),'idd.iddocument_id = empidd.iddocument_id',array('idd.name as Dname'))
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
       
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1='',$exParam2='',$exParam3='',$exParam4='')
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
        $objName = 'empiddocument';
        $tableFields = array(
            'action' => 'Action',
            'idnumber' => 'ID',
            'Dname' => 'Document',
            'description' => 'Description',
            'issuedate' => 'Issue Date',
            'expirydate' => 'Expire Date',
            'iddocumentstatus_id'=>'Status'
        );

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery,$exParam1);
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
            'menuName' => 'Document',

            'formgrid'=>'true',
            'unitId'=>$exParam1,
			'userAction' => $exParam3,
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall
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

    public function getempiddocumentbyid($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(array('eidd'=>'main_empiddocument'))
                       ->join(array('idp'=>'main_iddocumentprovider'),'idp.iddocumentprovider_id = eidd.iddocumentprovider_id',array('idp.name as Pname'))
                       ->join(array('ids'=>'main_iddocumentstatuslist'),'ids.iddocumentstatuslist_id = eidd.iddocumentstatuslist_id',array('ids.value as Sname'))
                       ->join(array('idd'=>'main_iddocument'),'idd.iddocument_id = eidd.iddocument_id',array('idd.name as Iname'))
                       ->where('eidd.empiddocument_id='.$id);
        return $this->fetchAll($select)->toArray();
    }

    public function getDocumentByEmpId($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(array('eidd'=>'main_empiddocument'))
                       ->join(array('idp'=>'main_iddocumentprovider'),'idp.iddocumentprovider_id = eidd.iddocumentprovider_id',array('idp.name as Pname'))
                       ->join(array('ids'=>'main_iddocumentstatuslist'),'ids.iddocumentstatuslist_id = eidd.iddocumentstatuslist_id',array('ids.value as Sname'))
                       ->join(array('idd'=>'main_iddocument'),'idd.iddocument_id = eidd.iddocument_id',array('idd.name as Iname'))
                       ->where('eidd.empid='.$id);
        return $this->fetchAll($select)->toArray();
    }
  
}
?>