<?php

class Default_Model_empdevice extends Zend_Db_Table_Abstract{
    protected $_name = 'main_device_user_config';
    protected $_primary = 'config_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery, $empid)
    {
        $where = "md.employee_id = "+$empid;
        if($searchQuery)
        $where .= " AND ".$searchQuery;
        
    
        $device = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(['md'=>'main_device_user_config'], ['id'=>'md.config_id','num_of_fingers' => 'md.num_of_fingers','num_of_card' => 'md.num_of_card', 'num_of_faces'=>'md.num_of_faces', 'security_level'=>'md.security_level'])
                    ->join(['du'=>'main_device_users'], 'du.config_id = md.config_id')
                    ->where("md.employee_id = $empid")
                    ->order("$by $sort")
                    ->limitPage($pageNo, $perPage);
        
        return $device;
    }

    public function getSyncStatus($id)
    {
        $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(['md'=>'main_device_user_config'])
                    ->join(['du'=>'main_device_users'], 'du.employee_id = md.employee_id')
                    ->where("md.config_id = $id");
        $result = $this->fetchRow($select)->toArray();
        //Zend_Debug::dump($result['isDataSynced']);die;
        return $result;
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        if ($a!='') {
            $objName = 'empdevice';
        }
        else{
            $objName = 'empdevice';
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
        //Zend_Debug::dump($a);die;
		$tableFields = [
			        'action' => 'Action',
			        'num_of_fingers' => 'Number of Fingers',
			        'num_of_card' => 'Number of Cards',
			        'num_of_faces' => 'Number of Faces',
                    'security_level' => 'Security Level',
                    'isDataSynced' => 'Sync Status'
			
			    ];

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery,$a);
       
        $dataTmp = [
            'unitId' => $a,
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
            'add'=>'add',
            'menuName' => 'Device User Configuration',
            'jsFillFnName' => '',
            'formgrid' => 'true',
            'userAction'=>$c,
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [ 
                'isactive' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
    ]
        ];
        
        return $dataTmp;
    }

    public function saveorupdate($data, $id)
    {
        if ($id == '') {
            $this->insert($data);
            $id = $this->getAdapter()->lastInsertId('main_device_user_config', 'config_id');
           return $id;
        } else {
            $this->update($data, "config_id = $id");
           
        }
    }

    public function getUserConfig($id)
    {
        $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(['md'=>'main_device_user_config'])
                    ->join(['du'=>'main_device_users'], 'du.employee_id = md.employee_id')
                    ->where("md.config_id = $id");
        $result = $this->fetchRow($select);
        return $result;
    }
    
   
}
