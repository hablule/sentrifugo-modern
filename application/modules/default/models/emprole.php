<?php
class Default_Model_emprole extends Zend_Db_Table_Abstract{
    protected $_name = 'main_userrole';
    protected $_primary = 'userrole_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery,$a)
    {
       // $where = "1";
       if ($a!='') {
        $where= 'er.user_id='.$a;
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
            ->from(['er' => 'main_userrole'],['er.userrole_id as id','er.role_id','er.description','if(er.isactive="Y","Yes","No") as isactive'])
            ->joinLeft(['e' => 'main_employees'], "er.user_id =e.id", ['emp_id' => 'e.id','userfullname'=>'e.userfullname'])
            ->joinLeft(['r' => 'main_role'], "er.role_id =r.id", ['role_id' => 'r.id','rolename'=>'r.name'])
            
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
       
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        if ($a!='') {
            $objName = 'emprole';
        }
        else{
            $objName = 'emprole';
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
            'rolename' => 'Role',
            'userfullname' => 'Employee',
            'isactive' => 'Active',
            'description' => 'description',
          



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
            'menuName' => 'User Role',
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
                'unitId' => $d,
                'menuName' => 'User Role',

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
    // public function getparticularpresenceData($id)
	// {
	// 	$select = $this->select()
	// 					->setIntegrityCheck(false)
	// 					->from(array('a' => 'main_digitalpresence'), array('digitaladdress' => 'a.digitaladdress', 'isactive' => 'a.isactive','a.description'))
	// 					->joinInner(array('l' => 'main_digitalmedia'), "l.digitalmedia_id = a.digitalmedia_id ", array('dname' => 'l.name'))
    //                     ->joinLeft(array('e' => 'main_users'), "a.employee_id=e.id", array('userfullname'=>'e.userfullname'))
    //                     ->joinLeft(array('comp' => 'main_company'), "a.company_id =comp.company_id", array('comp_id' => 'comp.company_id','comp_name'=>'comp.name'))
    //                     ->joinLeft(array('bu' => 'main_sbunit'), "bu.sbunit_id =a.sbunitid", array('sbunit_id' => 'bu.sbunit_id','sbunit_name'=>'bu.name'))
    //                     ->joinLeft(array('di' => 'main_division'), "di.division_id =a.division_id", array('division_id' => 'di.division_id','div_name'=>'di.name'))
    //                     ->joinLeft(array('d' => 'main_department'), "d.department_id =a.department_id", array('departement_id' => 'd.department_id','dpt_name'=>'d.name'))
    //                     ->joinLeft(array('s' => 'main_sectionn'), "s.section_id =a.section_id", array('section_id' => 's.section_id','sec_name'=>'s.name'))
    //                     ->joinLeft(array('p' => 'main_project'), "p.project_id =a.project_id", array('project_id' => 'p.project_id','pro_name'=>'p.name'))
    //                     ->joinLeft(array('lt'=>'main_digitalmediatype'), 'lt.degitalmedtype_id=l.digitalmediatype_id',array('lt.name as ltname'))
    //                     ->joinLeft(array('lc'=>'main_digitalmediacategory'), 'lc.digitalcategory_id=l.digitalmediacategory_id',array('lc.name as lcname'))
	// 					->where('a.digitalpresence_id='.$id.' ');
	// 	return $this->fetchRow($select)->toArray();
	// }

    public function getpersenceData($sort, $by, $pageNo, $perPage,$searchQuery,$empid)
	{
		$where = "h.isactive = 'Y' AND lt.isactive='Y' AND h.employee_id =".$empid;
		//if($groupid) 
		//$where .= " AND h.groupid = ".$groupid." AND h.holidayyear between year(now())-1 and year(now())+1 ";
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		
		$groupData = $this->select()
    					   ->setIntegrityCheck(false)	
                           ->from(['h'=>'main_digitalpresence'],['h.*'])
						   ->join(['lt'=>'main_digitalmedia'], 'lt.digitalmedia_id=h.digitalmedia_id',['lt.name as ltname'])
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);
		//echo 	$groupData ; exit;

		return $groupData;       		
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
    public function getDPers($emp_id,$pos_id)
    {
        $DPdata = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('employee_id = '.$emp_id.' and position_id='.$pos_id);
        return $this->fetchRow($DPdata)->toArray();
    }
    public function getDPersemp($emp_id)
    {
        $DPdata = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('id = '.$emp_id);
        return $this->fetchRow($DPdata)->toArray();
    }
    public function getalldatas($id)
    {
        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['er' => 'main_userrole'],['er.userrole_id as id','er.role_id','er.description','if(er.isactive="Y","Yes","No") as isactive','er.*'])                       
            ->joinLeft(['e' => 'main_employees'], "er.user_id =e.id", ['emp_id' => 'e.id','userfullname'=>'e.userfullname'])
            ->joinLeft(['r' => 'main_role'], "er.role_id =r.id", ['role_id' => 'r.id','rolename'=>'r.name'])
            ->where('er.userrole_id= '.$id);
            return $this->fetchRow($bank)->toArray();

    }

}
?>