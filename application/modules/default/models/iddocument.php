<?php
class Default_Model_iddocument extends Zend_Db_Table_Abstract{
    protected $_name = 'main_iddocument';
    protected $_primary = 'iddocument_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1=1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->order("$by $sort")
            ->from('main_iddocument',array('*','iddocument_id as id','if(isactive="Y","Yes","No") as isactive'))
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
        $objName = 'iddocument';
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

    public function getIddocumentById($id)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('idd'=>'main_iddocument'),array('idd.*'))
                        ->join(array('iddc'=>'main_iddocumentcategory'),'iddc.iddocumentcategory_id = idd.iddocumentcategory_id',array('iddc.name as Cname'))
                        ->join(array('iddp'=>'main_iddocumentpurpose'),'iddp.iddocumentpurpose_id = idd.iddocumentpurpose_id',array('iddp.name as Pname'))
                        ->join(array('iddt'=>'main_iddocumenttype'),'iddt.iddocumenttype_id = idd.iddocumenttype_id',array('iddt.name as Tname'))
                        ->where('iddocument_id = '.$id);
        return $this->fetchAll($select)->toArray();
    }

    public function getalldocument()
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->where('isactive = "Y"');

        return $this->fetchAll($select)->toArray();
    }

}
?>