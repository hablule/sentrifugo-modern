<?php
class Default_Model_recordaccess extends Zend_Db_Table_Abstract{
    protected $_name = 'main_emprecordaccess';
    protected $_primary = 'emprecordaccess_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ra'=>'main_emprecordaccess'),array('ra.emprecordaccess_id as id','ra.*','if(ra.isactive="Y","Yes","No") as isactive','if(ra.readwrite="Y","Yes","No") as readwrite'))
            ->joinLeft(array('u'=>'main_users'),'ra.user_id = id','u.username')
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
        $objName = 'recordaccess';
        $tableFields = array(
            'action' => 'Action',
            'username' => 'User',
            'accesslevel' => 'Access Level',
            'description' => 'Description',
            'readwrite' => 'Read Write Mode',
            'isactive' => 'Active'
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
    public function getrecordlistList()
	{
	    $select = $this->select()
						->setIntegrityCheck(false)
						->from(array('ra'=>'main_emprecordaccess'),array('id'=>'ra.emprecordaccess_id','name'=>'ra.description'))
					    ->where("ra.isactive = 'Y'")
						 ->order('ra.emprecordaccess_id');
		return $this->fetchAll($select)->toArray();
	
	}
    public function getrecordlistListforemp()
	{
	    $select = $this->select()
						->setIntegrityCheck(false)
						->from(array('ra'=>'main_emprecordaccess'),array('id'=>'ra.emprecordaccess_id','name'=>'ra.description'))
					    ->where("ra.accesslevel = 'INDIVIDUAL'")
						 ->order('ra.emprecordaccess_id');
		return $this->fetchAll($select)->toArray();
	
	}
    public function getrecordaccessbyid($id)
    {
        $select = $this->select()->setIntegrityCheck(false)->where('emprecordaccess_id='.$id);
        return $this->fetchRow($select)->toArray();

    }
}
?>