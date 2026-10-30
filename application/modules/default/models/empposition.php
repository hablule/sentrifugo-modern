<?php
class Default_Model_empposition extends Zend_Db_Table_Abstract{
    protected $_name = 'main_empposition';
    protected $_primary = 'id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery,$a)
    {
       // $where = "1";
       if ($a!='') {
        $where= 'employee_id='.$a;
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
            ->from(array('ep' => 'main_empposition'),array('ep.id as id','ep.position_id','ep.description','if(ep.isactive="Y","Yes","No") as isactive'))
            ->joinLeft(array('p' => 'main_project'), "p.project_id =ep.project_id", array('project_id' => 'p.project_id','pro_name'=>'COALESCE(p.name,"--")'))
            ->joinLeft(array('comp' => 'main_company'), "comp.company_id =ep.company_id", array('company_id' => 'comp.company_id','comp_name'=>'COALESCE(comp.name,"--")'))
            ->joinLeft(array('bu' => 'main_sbunit'), "bu.sbunit_id =ep.sbunit_id", array('sbunit_id' => 'bu.sbunit_id','sbunit_name'=>'COALESCE(bu.name,"--")'))
            ->joinLeft(array('di' => 'main_division'), "di.division_id =ep.division_id", array('division_id' => 'di.division_id','div_name'=>'COALESCE(di.name,"--")'))
            ->joinLeft(array('d' => 'main_department'), "d.department_id =ep.department_id", array('departement_id' => 'd.department_id','dpt_name'=>'COALESCE(d.name,"--")'))
            ->joinLeft(array('s' => 'main_section'), "s.section_id =ep.section_id", array('section_id' => 's.section_id','sec_name'=>'COALESCE(s.name,"--")'))  
            ->joinLeft(array('t' => 'main_team'), "t.team_id =ep.team_id", array('team_id' => 't.team_id','team_name'=>'COALESCE(t.name,"--")'))  
            ->joinLeft(array('jt' => 'main_jobtitle'), "jt.id =ep.jobtitle_id", array('jobtitle_id' => 'jt.id','job_name'=>'COALESCE(jt.name,"--")'))  
            ->joinLeft(array('e' => 'main_employees'), "ep.employee_id =e.id", array('emp_id' => 'e.id','userfullname'=>'e.userfullname'))
            ->joinLeft(array('jp' => 'main_position'), "ep.position_id =jp.id", array('jobposition_id' => 'jp.id','positionname'=>'jp.name'))
            
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
       //die($bank);
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        if ($a!='') {
            $objName = 'emppos';
        }
        else{
            $objName = 'empposition';
        }
        $searchArray = array();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $tableFields = array(
            'action' => 'Action',
            'positionname' => 'Position',
            'isactive' => 'Active',
            'comp_name'    =>'Company',
            'sbunit_name'    =>'Business Ubnit',
            'div_name'    =>'Division',
            'dpt_name'    =>'Department',
            'sec_name'    =>'Section',
            'pro_name'    =>'Project',
            'team_name'    =>'Team',



        );

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery,$a);
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
            'add'=>'add',
            'menuName' => 'Employee Position',
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
        if($a!=''){
            $dataTmp = array(
                'sort' => $sort,
                'by' => $by,
                'pageNo' => $pageNo,
                'perPage' => $perPage,
                'tablecontent' => $tablecontent,
                'objectname' => $objName,
                'extra' => array(),
                'add' => 'add',
                'tableheader' => $tableFields,
                'jsGridFnName' => 'getEmployeeAjaxgridData',
                'jsFillFnName' => '',
                'userAction'=>$c,
                'unitId' => $a,
                'menuName' => 'Employee Position',

                'context'=>$b,
                'formgrid'=>'true',
                'searchArray' => $searchArray,
                'call' => $call,
                'dashboardcall' => $dashboardcall,
                'search_filters' => array( 
                    'isactive' => array(
                        'type' => 'select',
                        'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
        )
            );
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
                           ->from(array('h'=>'main_digitalpresence'),array('h.*'))
						   ->join(array('lt'=>'main_digitalmedia'), 'lt.digitalmedia_id=h.digitalmedia_id',array('lt.name as ltname'))
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
    public function getalldp($id)
    {
        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ep' => 'main_empposition'),array('ep.id as id','ep.position_id','ep.description','if(ep.isactive="Y","Yes","No") as isactive'))
            ->joinLeft(array('p' => 'main_project'), "p.project_id =ep.project_id", array('project_id' => 'p.project_id','pro_name'=>'p.name'))
            ->joinLeft(array('comp' => 'main_company'), "comp.company_id =ep.company_id", array('company_id' => 'comp.company_id','comp_name'=>'comp.name'))
            ->joinLeft(array('bu' => 'main_sbunit'), "bu.sbunit_id =ep.sbunit_id", array('sbunit_id' => 'bu.sbunit_id','sbunit_name'=>'bu.name'))
            ->joinLeft(array('di' => 'main_division'), "di.division_id =ep.division_id", array('division_id' => 'di.division_id','div_name'=>'di.name'))
            ->joinLeft(array('d' => 'main_department'), "d.department_id =ep.department_id", array('departement_id' => 'd.department_id','dpt_name'=>'d.name'))
            ->joinLeft(array('s' => 'main_section'), "s.section_id =ep.section_id", array('section_id' => 's.section_id','sec_name'=>'s.name'))  
            ->joinLeft(array('t' => 'main_team'), "t.team_id =ep.team_id", array('team_id' => 't.team_id','team_name'=>'t.name'))  
            ->joinLeft(array('jt' => 'main_jobtitle'), "jt.id =ep.jobtitle_id", array('jobtitle_id' => 'jt.id','job_name'=>'jt.name'))  
            ->joinLeft(array('e' => 'main_employees'), "ep.employee_id =e.id", array('emp_id' => 'e.id','userfullname'=>'e.userfullname'))
            ->joinLeft(array('jp' => 'main_position'), "ep.position_id =jp.id", array('jobposition_id' => 'jp.id','positionname'=>'jp.name'))
            ->where('ep.id= '.$id);
            return $this->fetchRow($bank)->toArray();

    }
    public function getpos_jobtitle($empid)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('empp'=>'main_empposition'),array('coalesce(p.name,jt.name) as pos'))
                        ->joinLeft(array('p'=>'main_position'),'p.id = empp.position_id')
                        ->joinLeft(array('jt'=>'main_jobtitle'),'jt.id = empp.jobtitle_id')
                        ->where('empp.employee_id = '.$empid);
                       
        return $this->fetchAll($select)->toArray();
    }


    public function getReportingManager($empId)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(array('p'=>'main_empposition'),'p.reportto_id')
                       ->join(array('e'=>'main_employees'),'e.id=p.reportto_id',array('e.userfullname','e.emailaddress'))
                       ->where('p.employee_id='.$empId);
        return $this->fetchAll($select)->toArray();

    }

}
?>