<?php
class Default_Model_Empaddress extends Zend_Db_Table_Abstract{
    protected $_name = 'main_address';
    protected $_primary = 'address_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery,$id)
    {
		

		$where = " a.emp_id = " . $id . " ";
		 //Zend_Debug::dump($searchQuery);

        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('a' => 'main_address'), array('id' => 'a.address_id', 'name' => 'a.name','typeid'=>'a.address_type_id','locationid'=>'a.location_id','iscurrent'=>'if(a.iscurrent="N","No","Yes")'))
        ->joinInner(array('l' => 'main_location'), "l.location_id = a.location_id ", array('location' => 'CONCAT(l.address1,",",l.address2,",",l.address3,",",l.address4)'))
        ->joinInner(array('at' => 'main_address_type'), "at.address_type_id = a.address_type_id ", array('typename' => 'at.name'))
        ->order("$by $sort")
        ->where($where)
        ->limitPage($pageNo, $perPage);

        return $bank;
				//code...
	

    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1 , $exParam2 = '', $exParam3 = '', $exParam4 = '')
    {
		$searchQuery = '';
		$searchArray = array();
		$data = array();
	

		if ($searchData != '' && $searchData != 'undefined') {
			$searchValues = json_decode($searchData);

			foreach ($searchValues as $key => $val) {
               
				if($key == 'name'){
					$searchQuery.=" a.name like '%" . $val . "%' AND "; 
					$searchArray[$key] = $val;
				}else if($key == 'location'){
					$searchQuery.=" l.address1 like '%" . $val . "%' AND "; 
					$searchArray[$key] = $val;
				}
					else if($key == 'typename'){
						$searchQuery.=" at.name like '%" . $val . "%' AND "; 
						$searchArray[$key] = $val;
					}
					else if($key == 'iscurrent'){
						$searchQuery.=" a.iscurrent like '%" . $val . "%' AND "; 
						$searchArray[$key] = $val;
					}
				else{
					$searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
					$searchArray[$key] = $val;
				}
			
			}
			$searchQuery = rtrim($searchQuery, " AND");
		}
        $objName = 'empaddress';
        $tableFields = array(
            'action' => 'Action',
            'name' => 'Address Name',
            'typename' => 'Type',
            'location' => 'Location',
            'iscurrent' => 'Current ',


        );
        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery, $exParam1);
        $dataTmp = array(
            'userid' => $exParam1,
			'sort' => $sort,
			'by' => $by,
			'pageNo' => $pageNo,
			'perPage' => $perPage,
			'tablecontent' => $tablecontent,
			'objectname' => $objName,
			'extra' => array(),
			'tableheader' => $tableFields,
			'jsGridFnName' => 'getEmployeeAjaxgridData',
			'jsFillFnName' => '',
			'searchArray' => $searchArray,
			'add' => 'add',
			'menuName' => 'Address',
			'formgrid' => 'true',
			'unitId' => $exParam1,
			'call' => $call,
			'userAction' => $exParam3,	

			'context' => $exParam2,	'search_filters' => array(
				'iscurrent' => array(
					'type' => 'select',
					'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
		),
        );
        return $dataTmp;
    }
    public function getsingleEmpaddressData($id)
	{
		$select = $this->select()
						->setIntegrityCheck(false)
						->from(array('a' => 'main_address'), array('address_id' => 'a.address_id', 'name' => 'a.name','typeid'=>'a.address_type_id','locationid'=>'a.location_id','iscurrent'=>'if(a.iscurrent="N",0,1)','isactive'=>'if(a.isactive="N",0,1)','isactivename'=>'if(a.isactive="N","No","Yes")'))
						->joinInner(array('l' => 'main_location'), "l.location_id = a.location_id ", array('locationname' => 'CONCAT(l.address1,",",l.address2,",",l.address3,",",l.address4)'))
						->joinInner(array('at' => 'main_address_type'), "at.address_type_id = a.address_type_id ", array('typename' => 'at.name'))
						->where('a.address_id='.$id.'');
						die($select);
		return $this->fetchAll($select)->toArray();
	}
	

	public function getsingleempaddressdatas($id)
	{
		$select = $this->select()
					   ->setIntegrityCheck(false)
					   ->where('address_id='.$id.'');
		return $this->fetchRow($select)->toArray();
	}
	public function getsingleEmpaddressDataemp($id)
	{
		$select = $this->select()
						->setIntegrityCheck(false)
						->from(array('a' => 'main_address'), array('address_id' => 'a.address_id', 'name' => 'a.name','typeid'=>'a.address_type_id','locationid'=>'a.location_id','iscurrent'=>'if(a.iscurrent="N",0,1)','isactive'=>'if(a.isactive="N",0,1)','isactivename'=>'if(a.isactive="N","No","Yes")'))
						->joinInner(array('l' => 'main_location'), "l.location_id = a.location_id ", array('location' => 'CONCAT(l.address1,",",l.address2,",",l.address3,",",l.address4)'))
						->joinInner(array('at' => 'main_address_type'), "at.address_type_id = a.address_type_id ", array('typename' => 'at.name'))
						->where('a.emp_id='.$id.'');
						
		return $this->fetchAll($select)->toArray();
	}
	public function getparticularaddressData($id)
	{
		$select = $this->select()
						->setIntegrityCheck(false)
						->from(array('a' => 'main_address'), array('address_id' => 'a.address_id', 'name' => 'a.name','typeid'=>'a.address_type_id','locationid'=>'a.location_id','iscurrent'=>'if(a.iscurrent="N",0,1)','isactive'=>'if(a.isactive="N",0,1)','isactivename'=>'if(a.isactive="N","No","Yes")'))
						->joinInner(array('l' => 'main_location'), "l.location_id = a.location_id ", array('location' => 'CONCAT(l.address1,",",l.address2,",",l.address3,",",l.address4)'))
						->joinInner(array('at' => 'main_address_type'), "at.address_type_id = a.address_type_id ", array('typename' => 'at.name'))
                        ->joinLeft(array('e' => 'main_employees'), "a.emp_id=e.id", array('emp_id' => 'e.id','userfullname'=>'e.userfullname'))
                        ->joinLeft(array('comp' => 'main_company'), "a.company_id =comp.company_id", array('comp_id' => 'comp.company_id','comp_name'=>'comp.name'))
                        ->joinLeft(array('bu' => 'main_sbunit'), "bu.sbunit_id =a.sbunit_id", array('sbunit_id' => 'bu.sbunit_id','sbunit_name'=>'bu.name'))
                        ->joinLeft(array('di' => 'main_division'), "di.division_id =a.division_id", array('division_id' => 'di.division_id','div_name'=>'di.name'))
                        ->joinLeft(array('d' => 'main_department'), "d.department_id =a.department_id", array('departement_id' => 'd.department_id','dpt_name'=>'d.name'))
                        ->joinLeft(array('s' => 'main_section'), "s.section_id =a.section_id", array('section_id' => 's.section_id','sec_name'=>'s.name'))
                        ->joinLeft(array('p' => 'main_project'), "p.project_id =a.project_id", array('project_id' => 'p.project_id','pro_name'=>'p.name'))
						->where('a.address_id='.$id.' ');
						return $this->fetchRow($select)->toArray();
					}

					public function getparticularempaddressData($id)
					{
						$select = $this->select()
										->setIntegrityCheck(false)
										->from(array('a' => 'main_address'), array('address_id' => 'a.address_id', 'name' => 'a.name','typeid'=>'a.address_type_id','locationid'=>'a.location_id','iscurrent'=>'if(a.iscurrent="N",0,1)','isactive'=>'if(a.isactive="N",0,1)','isactivename'=>'if(a.isactive="N","No","Yes")'))
										->joinInner(array('l' => 'main_location'), "l.location_id = a.location_id ", array('location' => 'CONCAT(l.address1,",",l.address2,",",l.address3,",",l.address4)'))
										->joinInner(array('at' => 'main_address_type'), "at.address_type_id = a.address_type_id ", array('typename' => 'at.name'))
										->joinLeft(array('e' => 'main_users'), "a.emp_id=e.id", array('emp_id' => 'e.id','userfullname'=>'e.userfullname'))
										->joinLeft(array('comp' => 'main_company'), "a.company_id =comp.company_id", array('comp_id' => 'comp.company_id','comp_name'=>'comp.name'))
										->joinLeft(array('bu' => 'main_sbunit'), "bu.sbunit_id =a.sbunit_id", array('sbunit_id' => 'bu.sbunit_id','sbunit_name'=>'bu.name'))
										->joinLeft(array('di' => 'main_division'), "di.division_id =a.division_id", array('division_id' => 'di.division_id','div_name'=>'di.name'))
										->joinLeft(array('d' => 'main_department'), "d.department_id =a.department_id", array('departement_id' => 'd.department_id','dpt_name'=>'d.name'))
										->joinLeft(array('s' => 'main_section'), "s.section_id =a.section_id", array('section_id' => 's.section_id','sec_name'=>'s.name'))
										->joinLeft(array('p' => 'main_project'), "p.project_id =a.project_id", array('project_id' => 'p.project_id','pro_name'=>'p.name'))
										->where('a.emp_id='.$id.'');
										return $this->fetchRow($select)->toArray();
									}

	public function SaveorUpdateData($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);

			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_address');
			return $id;
		}
		
	}
	public function DeleteData($where)
	{
	    
			$this->delete($where);
			return 'delete';
	
	}

}
?>