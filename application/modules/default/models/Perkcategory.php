<?php

class Default_Model_Perkcategory extends Zend_Db_Table_Abstract{
    protected $_name = 'main_perkcategory';
    protected $_id = 'perkcategory_id';


    public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall)
    {
        $searchQuery = '';
        $searchArray = array();

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
        $objName = 'perkcategory';
        $objID = 'perkcategory';
        $tableFields = array(
            'action'=>'Action',
            'name'=>'Name',
            'description'=>'description',
            'isactive'=>'Active',
        );

       $tablecontent = $this->getperkcategory($sort, $by, $pageNo, $perPage,$searchQuery);
      
        $dataTmp = array(
            'sort'=>$sort,
            'by'=>$by,
            'pageNo'=>$pageNo,
            'perPage' => $perPage,				
			'tablecontent' => $tablecontent,
			'objectname' => $objName,
            'objectId' => $objID,
			'extra' => array(),
			'tableheader' => $tableFields,
			'jsGridFnName' => 'getAjaxgridData',
			'jsFillFnName' => '',
			'searchArray' => $searchArray,
			'call'=>$call,
            'add'=>'add',
			'dashboardcall'=>$dashboardcall,
            'menuName' => 'Perk Category',
            'search_filters' => array(
                'isactive' => array(
                    'type' => 'select',
                    'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
    )

        );

        return $dataTmp;
    }

    public function getperkcategory($sort, $by, $pageNo, $perPage,$searchQuery)
    {
        $where = "1";
        if($searchQuery)
            $where .=' AND '. $searchQuery;

        $pctable = $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('pc'=>'main_perkcategory'),array('perkcategory_id'=>'pc.perkcategory_id','name'=>'pc.name','description'=>'pc.description','isactive'=>'if(pc.isactive="N","No","Yes")'))
                          ->order("$by $sort")
                          ->where($where)
                          ->limitPage($pageNo ,$perPage);
                      
        //sapp_Global::export_data_to_excel($this->fetchAll($timetable)->toArray(),array('name','start_time','end_time','time','id','created_by','modified','isactive'),'mike.xls');
       
        return $pctable;
    }
    public function addCategory($data){
        $config = array(
            'name' => 'main_perkcategory',
            'primary' => 'perkcategory_id'
        );
        $this->table = new Zend_Db_Table($config);
        $this->table->insert($data);
        return $this->getAdapter()->lastInsertId();
    }
    public function getSinglePerkcategory($id){
        $query = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(array('pc'=>'main_perkcategory'))
                       ->where('perkcategory_id='.$id);
       return $this->fetchAll($query)->toArray();
    }
    public function editPerkcategory($data,$where){
        $this->update($data,$where);
    }
}