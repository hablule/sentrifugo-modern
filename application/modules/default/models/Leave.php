<?php
class Default_Model_Leave extends Zend_Db_Table_Abstract{
    protected $_name = 'main_leave';
    protected $_primary = 'leave_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(['ml' => 'main_leave'], ['leave_id' => 'ml.leave_id', 'name' => 'ml.name', 'durationtype' => 'ml.durationtype', 'paytype' => 'ml.paytype','baseperiod'=>'ml.baseperiod','approvedrequired'=>'if(ml.approvedrequired="N","No","Yes")','ispreset'=>'if(ml.ispreset="N","No","Yes")','isactive'=>'if(ml.isactive="N","No","Yes")'])
        ->joinInner(['lc' => 'main_leavecategory'], "lc.leavecategory_id = ml.leavecategory_id", ['categoryname' => 'lc.name'])
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = [];
        if($searchData != '' && $searchData!='undefined')
        {
            $searchValues = json_decode($searchData);
            foreach($searchValues as $key => $val)
            {
                if($key == 'name')
                 $searchQuery .= " ml.name like '%".$val."%' AND ";					
                else if($key == 'categoryname')
                {
                    $searchQuery .= " lc.name like '%".$val."%' AND ";					
                }
                else if($key == 'durationtype')
                {
                    $searchQuery .= " ml.durationtype like '%".$val."%' AND ";					
 
                }
                else if($key == 'paytype')
                {
                    $searchQuery .= " ml.paytype like '%".$val."%' AND ";					

                }
                else if($key == 'baseperiod')
                {	
                    $searchQuery .= " ml.baseperiod like '%".$val."%' AND ";					

                }
                else if($key == 'approvedrequired')
                {
                    $searchQuery .= " ml.approvedrequired like '%".$val."%' AND ";					
                }
                else if($key == 'ispreset')
                {
                    $searchQuery .= " ml.ispreset like '%".$val."%' AND ";					
                }
                else if($key == 'isactive')
                {
                    $searchQuery .= " ml.isactive like '%".$val."%' AND ";					
                }
                else 
                 $searchQuery .= " ".$key." like '%".$val."%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery," AND");	
            //die($searchQuery);				
        }
        $objName = 'leave';
        $tableFields = [
            'action' => 'Action',
            'name' => 'Leave Name',
            'categoryname' => 'Leave Category',
            'durationtype' => 'Duration Type',
            'paytype' => 'Pay Type',
            'baseperiod' => 'Base Period',
            'approvedrequired' => 'Approval Required',
            'ispreset' => 'Is Preset',
            'isactive'=>'Active',


        ];

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery);
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
            'add' =>'add',
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
              
                'approvedrequired' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
                    'ispreset' => [
                        'type' => 'select',
                        'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
                        'isactive' => [
                            'type' => 'select',
                            'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
            ]
        ];
        return $dataTmp;
    }

    public function getsingleleaveData($id)
	{
		$select = $this->select()
						->setIntegrityCheck(false)
                        ->from(['ml' => 'main_leave'], ['leave_id' => 'ml.leave_id','leavecategoryid' => 'ml.leavecategory_id', 'name' => 'ml.name','isact' => 'ml.isactive','isactivename' => 'if(ml.isactive="N","No","Yes")','duarationtype' => 'ml.durationtype', 'paytype' => 'ml.paytype','description'=>'ml.description','baseperiod'=>'ml.baseperiod','approvedrequired'=>'if(ml.approvedrequired="N","No","Yes")','isapprovecheck'=>'ml.approvedrequired','ispreset'=>'if(ml.ispreset="N","No","Yes")','isactivecheck'=>'ml.isactive','ispresetcheck'=>'ml.ispreset','allowday'=>'ml.alloteddays','classPath'=>'ml.classPath','classfun'=>'ml.method','parameters'=>'ml.parameters'])
                        ->joinInner(['lc' => 'main_leavecategory'], "lc.leavecategory_id = ml.leavecategory_id", ['categoryname' => 'lc.name'])
						->where('ml.leave_id='.$id.'');
		return $this->fetchAll($select)->toArray();
	}
	
	public function SaveorUpdateData($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_leave');
			return $id;
		}
		
	}
    public function getleavelist()
	{
		$select = $this->select()
						->setIntegrityCheck(false)
                        ->from(['ml' => 'main_leave'], ['leave_id' => 'ml.leave_id', 'name' => 'ml.name'])
                        ->joinInner(['lc' => 'main_leavecategory'], "lc.leavecategory_id = ml.leavecategory_id", ['category' => 'lc.name']);
		return $this->fetchAll($select)->toArray();
	}
    public function DeleteData($where)
	{
			$this->delete($where);
			return 'delete';
	
		
	}

}
?>