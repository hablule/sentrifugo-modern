<?php
class Default_Model_Projectco extends Zend_Db_Table_Abstract{
    protected $_name = 'main_projectco';
    protected $_primary = 'projectco_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('pco' => 'main_projectco'), array('id' => 'pco.projectco_id','isactive'=>'if(pco.isactive="Y","Yes","No")'))
        ->joinLeft(array('p' => 'main_project'), "p.project_id =pco.project_id", array('project_id' => 'p.project_id','pro_name'=>'COALESCE(p.name,"--")'))
        ->joinLeft(array('comp' => 'main_company'), "comp.company_id =pco.company_id", array('comp_id' => 'comp.company_id','comp_name'=>'COALESCE(comp.name,"--")'))
        ->joinLeft(array('bu' => 'main_sbunit'), "bu.sbunit_id =pco.sbunit_id", array('sbunit_id' => 'bu.sbunit_id','sbunit_name'=>'COALESCE(bu.name,"--")'))
        ->joinLeft(array('di' => 'main_division'), "di.division_id =pco.division_id", array('division_id' => 'di.division_id','div_name'=>'COALESCE(di.name,"--")'))
        ->joinLeft(array('d' => 'main_department'), "d.department_id =pco.department_id", array('departement_id' => 'd.department_id','dpt_name'=>'COALESCE(d.name,"--")'))
        ->joinLeft(array('s' => 'main_section'), "s.section_id =pco.section_id", array('section_id' => 's.section_id','sec_name'=>'COALESCE(s.name,"--")'))  
        ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = array();
        if($searchData != '' && $searchData!='undefined')
        {
            $searchValues = json_decode($searchData);
            foreach($searchValues as $key => $val)
            {
                if($key == 'pro_name')
                 $searchQuery .= " p.name like '%".$val."%' AND ";					
                else if($key == 'comp_name')
                {
                    $searchQuery .= " comp.name like '%".$val."%' AND ";					
                }
                else if($key == 'sbunit_name')
                {
                    $searchQuery .= " bu.name like '%".$val."%' AND ";					
                } else if($key == 'div_name')
                {
                    $searchQuery .= " di.name like '%".$val."%' AND ";					
                } else if($key == 'dpt_name')
                {
                    $searchQuery .= " d.name like '%".$val."%' AND ";					
                } else if($key == 'sec_name')
                {
                    $searchQuery .= " s.name like '%".$val."%' AND ";					
                }
                else if($key == 'isactive')
                {
                    $searchQuery .= " pco.isactive like '%".$val."%' AND ";					
 
                }
                else if($key == 'description')
                {
                    $searchQuery .= " pco.description like '%".$val."%' AND ";					
 
                }
                else 
                 $searchQuery .= " ".$key." like '%".$val."%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery," AND");	
            //die($searchQuery);				
        }
        $objName = 'projectco';
        $tableFields = array(
            'action' => 'Action',
            'pro_name' => 'Project Name',
            'description' => 'Description',
            'isactive' => 'Active',
            'comp_name' => 'Company Name',
            'sbunit_name' => 'Business Unit Name',
            'div_name' => 'Division Name',
            'dpt_name' => 'Department Name',
            'sec_name' => 'Section Name',

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
            'add'=>'add',
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
			$id=$this->getAdapter()->lastInsertId('main_projectco');
			return $id;
		}
		
	}
	public function getsectionlist()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $qry = "select s.section_id,s.name from main_section s ";
        $res = $db->query($qry)->fetchAll();
        return $res;
    }
    public function getParicularoprojectco($id)
	{
			$bank = $this->select()
			->setIntegrityCheck(false)
			->from(array('pco' => 'main_projectco'), array('id' => 'pco.projectco_id','isactive'=>'pco.isactive'))
			 ->joinLeft(array('p' => 'main_project'), "p.project_id =pco.project_id", array('project_id' => 'p.project_id','pro_name'=>'p.name'))
			 ->joinLeft(array('comp' => 'main_company'), "pco.company_id =comp.company_id", array('comp_id' => 'comp.company_id','comp_name'=>'comp.name'))
			 ->joinLeft(array('bu' => 'main_sbunit'), "bu.sbunit_id =pco.sbunit_id", array('sbunit_id' => 'bu.sbunit_id','sbunit_name'=>'bu.name'))
			 ->joinLeft(array('di' => 'main_division'), "di.division_id =pco.division_id", array('division_id' => 'di.division_id','div_name'=>'di.name'))
             ->joinLeft(array('d' => 'main_department'), "d.department_id =pco.department_id", array('departement_id' => 'd.department_id','dpt_name'=>'d.name'))
             ->joinLeft(array('s' => 'main_section'), "s.section_id =pco.section_id", array('section_id' => 's.section_id','sec_name'=>'s.name'))           
			 ->where("projectco_id=".$id);
			 return $this->fetchRow($bank)->toArray();
	
	}
}
?>