<?php
class Default_Model_sbuengagment extends Zend_Db_Table_Abstract{
    protected $_name = 'main_sbuengagment';
    protected $_primary = 'id';

    public function getAddress($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $adress = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_sbuengagment'], ['id' => 'e.sbunit_id','bd_id'=>'e.businessdomain_id'])
            ->joinInner(['bd' => 'main_businessdomain'], "bd.businessdomain_id = e.businessdomain_id ", ['bd_name' => 'bd.name'])
            ->joinInner(['bu' => 'main_sbunit'], "e.sbunit_id = bu.sbunit_id ", ['bu_name' => 'bu.name'])
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
        return $adress;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = [];
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {

                if($key=='bd_name'){
                    $searchQuery .= " bd.name like '%".$val."%' AND ";					

                }
                else if($key=='bu_name'){
                    $searchQuery .= " bu.name like '%".$val."%' AND ";					

                }
            
                else{

              
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
            }
                $searchArray[$key] = $val;
           
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'sbuengagment';
        $tableFields = [
            'action' => 'Action',
            'bu_name' => 'Business Unit',
            'bd_name' => 'Business Domain',

           
        ];

        $tablecontent = $this->getAddress($sort, $by, $pageNo, $perPage, $searchQuery);
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

            'searchArray' => $searchArray,
            'call' => $call,
            'add'=>'add',
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
    public function getsingledata($buid,$bdid)
    {
      
        $adress = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_sbuengagment'], ['id' => 'e.sbunit_id','bd_id'=>'e.businessdomain_id'])
            ->joinInner(['bd' => 'main_businessdomain'], "bd.businessdomain_id = e.businessdomain_id ", ['bd_name' => 'bd.name','bd_id'=>'bd.businessdomain_id'])
            ->joinInner(['bu' => 'main_sbunit'], "e.sbunit_id = bu.sbunit_id ", ['bu_name' => 'bu.name'])
            ->where('e.sbunit_id= '.$buid. ' and e.businessdomain_id='.$bdid);
            return $this->fetchAll($adress)->toArray();    }
            public function getajaxdata($buid)
            {
              
                $adress = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(['e' => 'main_sbuengagment'], ['id' => 'e.sbunit_id','bd_id'=>'e.businessdomain_id'])
                    ->joinLeft(['bd' => 'main_businessdomain'], "bd.businessdomain_id = e.businessdomain_id ", ['name' => 'bd.name','businessdomain_id'=>'bd.businessdomain_id'])
                    ->joinLeft(['bu' => 'main_sbunit'], "e.sbunit_id = bu.sbunit_id ", ['bu_name' => 'bu.name'])
                    ->where('e.sbunit_id= '.$buid);
                    return $this->fetchAll($adress)->toArray();
                }
public function getdatabubu($buid)
{
  
    $adress = $this->select()
        ->setIntegrityCheck(false)
        ->from(['e' => 'main_sbuengagment'], ['id' => 'e.sbunit_id','bd_id'=>'e.businessdomain_id'])
        ->joinInner(['bd' => 'main_businessdomain'], "bd.businessdomain_id = e.businessdomain_id ", ['bd_name' => 'bd.name'])
        ->joinInner(['bu' => 'main_sbunit'], "e.sbunit_id = bu.sbunit_id ", ['bu_name' => 'bu.name'])
        ->where('e.sbunit_id= '.$buid);
        return $this->fetchAll($adress)->toArray();    }
}

?>