<?php
class Default_Model_Companyengagment extends Zend_Db_Table_Abstract{
    protected $_name = 'main_companyengage';
    protected $_primary = array('businessdomain_id','company_id');

    public function getAddress($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $adress = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('c' => 'main_companyengage'), array('comp_id' => 'c.company_id','bd_id'=>'c.businessdomain_id'))
            ->joinInner(array('bd' => 'main_businessdomain'), "bd.businessdomain_id = c.businessdomain_id ", array('bd_name' => 'bd.name'))
            ->joinInner(array('comp' => 'main_company'), "c.company_id = comp.company_id", array('comp_name' => 'comp.name'))
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
            // die($adress);
        return $adress;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = array();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {

                if($key=='bd_name'){
                    $searchQuery .= " bd.name like '%".$val."%' AND ";					

                }
                else if($key=='comp_name'){
                    $searchQuery .= " comp.name like '%".$val."%' AND ";					

                }
            
                else{

              
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
            }
                $searchArray[$key] = $val;
           
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'companyengagment';
        $tableFields = array(
            'action' => 'Action',
            'comp_name' => 'Company',
            'bd_name' => 'Business Domain',

           
        );

        $tablecontent = $this->getAddress($sort, $by, $pageNo, $perPage, $searchQuery);
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
			$id=$this->getAdapter()->lastInsertId('main_companyengage');
			return $id;
		}
		
	}
    public function getsingledata($comp_id,$bdid)
    {
      
        $adress = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('c' => 'main_companyengage'), array('comp_id' => 'c.company_id','bd_id'=>'c.businessdomain_id'))
            ->joinInner(array('bd' => 'main_businessdomain'), "bd.businessdomain_id = c.businessdomain_id ", array('bd_name' => 'bd.name'))
            ->joinInner(array('comp' => 'main_company'), "c.company_id = comp.company_id ", array('comp_name' => 'comp.name'))
            ->where('c.company_id= '.$comp_id. ' and c.businessdomain_id='.$bdid);
            return $this->fetchAll($adress)->toArray();    }

public function getdatacompany($comp_id)
{
  
    $adress = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('c' => 'main_companyengage'), array('com_id' => 'c.company','bd_id'=>'c.businessdomain_id'))
        ->joinInner(array('bd' => 'main_businessdomain'), "bd.businessdomain_id = c.businessdomain_id ", array('bd_name' => 'bd.name'))
        ->joinInner(array('comp' => 'main_company'), "c.company_id = comp.company_id ", array('comp_name' => 'comp.name'))      
          ->where('c.company_id= '.$comp_id);
        return $this->fetchAll($adress)->toArray();    }
        public function getajaxdata($id)
{
  
    $adress = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('c' => 'main_companyengage'), array('id' => 'c.company_id','bd_id'=>'c.businessdomain_id'))
        ->joinLeft(array('bd' => 'main_businessdomain'), "bd.businessdomain_id = c.businessdomain_id ", array('name' => 'bd.name','businessdomain_id'=>'bd.businessdomain_id'))
        ->joinLeft(array('comp' => 'main_company'), "c.company_id = comp.company_id ", array('comp_name' => 'comp.name'))
        ->where('c.company_id= '.$id);
        return $this->fetchAll($adress)->toArray();
    }
}

?>