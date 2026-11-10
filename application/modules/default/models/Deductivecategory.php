<?php

class Default_Model_Deductivecategory extends Zend_Db_Table_Abstract{
    protected $_name = 'main_loadcategory';
    protected $_id = 'load_category_id';


    public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];

        if($searchData!='' && $searchData!='undefined')
        {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                if($key == 'name')
                $searchQuery .= "name like '%".$val."%' AND ";
                else if($key == 'description')
                $searchQuery .= "description like '%".$val."%' AND ";
                else if($key == 'end_time')
                $searchQuery .= "end_time like '%".$val."%' AND ";
                else
                $searchQuery .= " ".$key." like '%".$val."%' AND";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery," AND");
        }
        $objName = 'deductivecategory';
        $objID = 'deductivecategory';
        $tableFields = [
            'action'=>'Action',
            'name'=>'Name',
            'description'=>'description',
            'isactive'=>'Active',

        ];

       $tablecontent = $this->getdeductivecategory($sort, $by, $pageNo, $perPage,$searchQuery);
      
        $dataTmp = [
            'sort'=>$sort,
            'by'=>$by,
            'pageNo'=>$pageNo,
            'perPage' => $perPage,				
			'tablecontent' => $tablecontent,
			'objectname' => $objName,
            'objectId' => $objID,
			'extra' => [],
			'tableheader' => $tableFields,
			'jsGridFnName' => 'getAjaxgridData',
			'jsFillFnName' => '',
			'searchArray' => $searchArray,
			'call'=>$call,
            'add'=>'add',
			'dashboardcall'=>$dashboardcall,
            'menuName' => 'Load Category',
            'search_filters' => [
                'isactive' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
    ]
          

        ];

        return $dataTmp;
    }

    public function getdeductivecategory($sort, $by, $pageNo, $perPage,$searchQuery)
    {
        $where = "1";
        if($searchQuery)
            $where .=' AND '. $searchQuery;

        $pctable = $this->select()
                          ->setIntegrityCheck(false)
                          ->from(['lc'=>'main_loadcategory'],['load_category_id'=>'lc.load_category_id','name'=>'lc.name','description'=>'lc.description','isactive'=>'if(lc.isactive="N","No","Yes")'])
                          ->order("$by $sort")
                          ->where($where)
                          ->limitPage($pageNo ,$perPage);
                      
        //sapp_Global::export_data_to_excel($this->fetchAll($timetable)->toArray(),array('name','start_time','end_time','time','id','created_by','modified','isactive'),'mike.xls');
       
        return $pctable;
    }
    public function addCategory($data){
        $config = [
            'name' => 'main_loadcategory',
            'primary' => 'load_category_id'
        ];
        $this->table = new Zend_Db_Table($config);
        $this->table->insert($data);
        return $this->getAdapter()->lastInsertId($config['name'],$config['primary']);
    }
    public function getSingleDeductivecategory($id){
        $query = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(['lc'=>'main_loadcategory'])
                       ->where('load_category_id='.$id);
       return $this->fetchAll($query)->toArray();
    }
    public function editDeductivecategory($data,$where){
        $this->update($data,$where);
    }
    public function getloadcategory()
    {
        $query = $this->select()
                      ->setIntegrityCheck(false)
                      ->from('main_loadcategory','name')
                      ->where("1");
                
        return $this->fetchAll($query)->toArray();
    }
}