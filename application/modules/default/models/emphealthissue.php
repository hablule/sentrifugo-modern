<?php
class Default_Model_emphealthissue extends Zend_Db_Table_Abstract{
    protected $_name = 'main_emphealthissue';
    protected $_primary = 'emphealthissue_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery,$id)
    {
        $where = " ehi.emp_id = ".$id." AND ehi.isactive = 'Y' ";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ehi'=>'main_emphealthissue'],['emphealthissue_id as id','description','diagnosedon','attention'])
            ->join(['hi'=>'main_healthissue'],'hi.healthissue_id = ehi.healthissue_id',['hi.name as hi_name'])
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
       
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall,$exParam1='',$exParam2='',$exParam3='',$exParam4='')
    {
        $searchQuery = '';
		$tablecontent = '';
		$searchArray = [];
		$data = []; 
		$dataTmp = [];
       
		/** search from grid - START **/
		if($searchData != '' && $searchData!='undefined')
		{
			$searchValues = json_decode($searchData);
			foreach($searchValues as $key => $val)
			{
				if($key == 'createdate')
				{
					$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
				}
				else if($key == 'modifieddate')
				{
					$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
				}
				else
				$searchQuery .= " ".$key." like '%".$val."%' AND ";

				$searchArray[$key] = $val;
			}
			$searchQuery = rtrim($searchQuery," AND");
		}
		
		$objName = 'emphealthissue';

		$tableFields = ['action'=>'Action','hi_name'=>'Name','description'=>'Description','diagnosedon'=>'Diagnosed on'];
			
		$tablecontent = $this->getData($sort,$by,$pageNo,$perPage,$searchQuery,$exParam1);

		$bool_arr = ['' => 'All',1 => 'Yes',2 => 'No'];
		
		$dataTmp = ['userid'=>$exParam1,
						'sort' => $sort,
						'by' => $by,
						'pageNo' => $pageNo,
						'perPage' => $perPage,				
						'tablecontent' => $tablecontent,
						'objectname' => $objName,
						'extra' => [],
						'tableheader' => $tableFields,
						'jsGridFnName' => 'getEmployeeAjaxgridData',
						'jsFillFnName' => '',
						'searchArray' => $searchArray,
						'add'=>'add',
						'menuName'=>'Health Issue',
						'formgrid'=>'true',
						'unitId'=>$exParam1,
						'userAction' => $exParam3,	

						'dashboardcall'=>$dashboardcall,
						'call'=>$call,
						'context'=>$exParam2
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

    public function Getemphealthissuebyid($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
					   ->from(['eh'=>'main_emphealthissue'],['eh.*'])
					   ->joinLeft(['e'=>'main_employees'],'e.id = eh.emp_id',['e.userfullname'])
					   ->joinLeft(['hi'=>'main_healthissue'],'hi.healthissue_id = eh.healthissue_id',['hi.name as hiname'])
					   ->joinLeft(['hsl'=>'main_healthseveritylevel'],'hsl.severitylevel_id = eh.severitylevel_id',['hsl.value as hslname'])
                       ->where('emphealthissue_id='.$id);
					
        return $this->fetchAll($select)->toArray();
    }
	public function Getemphealthissuebyidview($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
					   ->from(['eh'=>'main_emphealthissue'],['eh.*,if(eh.isactive="Y","Yes","No") as isactive'])
					   ->joinLeft(['e'=>'main_employees'],'e.id = eh.emp_id',['e.userfullname'])
					   ->joinLeft(['hi'=>'main_healthissue'],'hi.healthissue_id = eh.healthissue_id',['hi.name as hiname'])
					   ->joinLeft(['hsl'=>'main_healthseveritylevel'],'hsl.severitylevel_id = eh.severitylevel_id',['hsl.value as hslname'])
                       ->where('emphealthissue_id='.$id);
					
        return $this->fetchAll($select)->toArray();
    }

}
?>