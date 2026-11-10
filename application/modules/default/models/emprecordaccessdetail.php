<?php
class Default_Model_emprecordaccessdetail extends Zend_Db_Table_Abstract{
    protected $_name = 'main_emprecordacessdetail';
    protected $_primary = 'emprecordacessdetail_id';

	public function getData($sort, $by, $pageNo, $perPage, $searchQuery,$a)
    {
       // $where = "1";
       if ($a!='') {
        $where= 'ad.emp_id='.$a;
    }
    
    if ($searchQuery && $a!='')
        $where .= "  AND " . $searchQuery;
    else
        $where .= " " . $searchQuery;

    if ($where==" ") {
        $where='1';
    }

	$bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ad' => 'main_emprecordacessdetail'],['ad.emprecordaccess_id as emprecordaccess_id','ad.description as description','if(ad.isactive="Y","Yes","No") as isactive','ad.emprecordacessdetail_id as id'])
			->joinInner(['ra'=>'main_emprecordaccess'], 'ra.emprecordaccess_id=ad.emprecordaccess_id',['ra.emprecordaccess_id','recordname'=>'ra.description'])
			// ->joinInner(array('e'=>'main_employees'), 'e.id=ad.emp_id',array('e.emp_id'=>'e.id','userfullname'=>'e.userfullname'))
			->joinInner(['u'=>'main_employees'], 'u.id=ad.emp_id',['emp_id'=>'u.id','userfullname'=>'u.userfullname'])
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
        return $bank;
    }
    
	public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        if ($a!='') {
            $objName = 'emprecordaccess';
        }
        else{
            $objName = 'emprecordaccessdetail';
        }
        $searchArray = [];
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
		$tableFields = [
			        'action' => 'Action',
			        'userfullname' => 'Employee',
			        'recordname' => 'Record Name',
			        'description' => 'Description',
			        'isactive' => 'Active',
					// 'password' => 'Password',
		
			    ];

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery,$a);
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
            'add'=>'add',
            'menuName' => 'Record Access Detail',
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
        if($a!=''){
            $dataTmp = [
                'sort' => $sort,
                'by' => $by,
                'pageNo' => $pageNo,
                'perPage' => $perPage,
                'tablecontent' => $tablecontent,
                'objectname' => $objName,
                'extra' => [],
                'add' => 'add',
                'tableheader' => $tableFields,
                'jsGridFnName' => 'getEmployeeAjaxgridData',
                'jsFillFnName' => '',
                'userAction'=>$c,
                'unitId' => $a,
                'menuName' => ' Employee Record Access',

                'context'=>$b,
                'formgrid'=>'true',
                'searchArray' => $searchArray,
                'call' => $call,
                'dashboardcall' => $dashboardcall,
                'search_filters' => [ 
                    'isactive' => [
                        'type' => 'select',
                        'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
        ]
            ];
        }
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
    public function getdetails($id)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->where('emprecordacessdetail_id='.$id);
        return $this->fetchRow($select)->toArray();
    }
    public function getdetailsforview($id)
{
    $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ad' => 'main_emprecordacessdetail'],['ad.emprecordaccess_id as emprecordaccess_id','ad.description as description','if(ad.isactive="Y","Yes","No") as isactive','ad.emprecordacessdetail_id as id'])
			->joinInner(['ra'=>'main_emprecordaccess'], 'ra.emprecordaccess_id=ad.emprecordaccess_id',['ra.emprecordaccess_id','recordname'=>'ra.description'])
			 ->joinInner(['e'=>'main_employees'], 'e.id=ad.emp_id',['e.emp_id'=>'e.id','userfullname'=>'e.userfullname'])
            ->where("emprecordacessdetail_id=".$id);
            return $this->fetchRow($bank)->toArray();
        }
}
?>