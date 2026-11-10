<?php

use JetBrains\PhpStorm\ArrayShape;

class Schedule_Model_Logreport extends Zend_Db_Table_Abstract
{
    protected $_name = 'bm_event';
    protected $_primary = 'bm_event_id';
    public function getLogData($sort_order, $sort_field, $pageNo, $perPage, $searchQuery)
    {
        $where = 'bm.bm_event_id';
        if ($searchQuery) {
            $where .= " AND " . $searchQuery;
        }

        $roleData =  $this->select()
            ->setIntegrityCheck(false)
            ->from(['bm' => 'bm_event'], ['datetime' => 'DATE_FORMAT(FROM_UNIXTIME(bm.datetime),"'.DATEFORMAT_MYSQL.'")', 'bm_id' => 'bm.bm_id', 'event_type_id' => 'bm.event_type_id', 'user' => 'es.userfullname', 'shift_name' => 'if (tm.name != "",tm.name,bm.shift_flag)', 'shift_flag' => 'bm.shift_flag', 'status' => 'bm.status'])
            ->order("$sort_field $sort_order")
            ->joinLeft(['tm' => 'main_time_table'], 'tm.time_id=bm.shift_id')
            ->joinInner(['es'=>'employees_summary'],'es.user_id = bm.user_id')
            ->where($where)
            ->limitPage($pageNo, $perPage);
            //die($roleData);
        return $roleData;
    }
    public function getempLis()
    {
        $config = [
            'name' => 'employees_summary',
            'primary' => 'id'
        ];
        $db = new Zend_Db_Table($config);
        $emplist = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive=1');
        return $db->fetchAll($emplist)->toArray();
    }
    
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $p1, $p2, $p3, $p4)
    {
        $searchQuery = '';
        $searchArray = [];
        $data = [];
        $db = Zend_Db_Table::getDefaultAdapter();
        if($searchData != '' && $searchData!='undefined')
        {
            $searchValues = json_decode($searchData);
            foreach($searchValues as $key => $val)
            {
              
                 if($key == 'bm_id')
                    $searchQuery .= " bm.".$key." like '%".$val."%' AND ";	
                    else if($key == 'datetime')
                    $searchQuery .= "FROM_UNIXTIME(bm.datetime) like '%".  sapp_Global::change_date(urldecode($val),'database')."%' AND "; 
                    else if($key == 'event_type_id')
                     $searchQuery .= " bm.shift_id like '%".$val."%' AND ";	
                     else if($key == 'user')
                     $searchQuery .= " es.userfullname like '%".$val."%' AND ";	
                     else if($key == 'shift_name')
                     $searchQuery .= " tm.name like '%".$val."%' AND ";	
                 else	
                     $searchQuery .= " bm.".$key." like '%".$val."%' AND ";
                       $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery," AND");	
            //die($searchQuery);  		
        }
        $objName = 'logreport';
        $objId='logreport';
        $tableFields = [
            'datetime' => 'Log Date and Time',
            'bm_id' => 'Biometric Machine ID',
            //'event_type_id' => 'Event Type',
            'user' => 'Employee',
            'shift_name' => 'Shift',
            'status' => 'Shift Status',
            'shift_flag' => 'Shift Flag',
        ];

        $tablecontent = $this->getLogData($sort, $by, $pageNo, $perPage, $searchQuery);
        $group_model = new Schedule_Model_Timetable();
        $group_data=$group_model->getallshifts();

        $group_arr = [];

		foreach($group_data as $gkey => $gdata)
		{
			$group_arr[$gdata['name']] = $gdata['name'];
		}
        $log_model = new Schedule_Model_Logreport();
        $emp_data=$log_model->getempLis();

        $emp_arr = [];

		foreach($emp_data as $ekey => $edata)
		{
			$emp_arr[$edata['userfullname']] = $edata['userfullname'];
		}


        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objId,
            'menuName' => 'Bm Events',
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'add' => 'add',
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'datetime'=>['type'=>'datepicker'],
                // 'shift_name' =>array(
                //     'type'=>'select',
                //     'filter_data' => array(''=>'All')+$group_arr,),
                 
                //     'status' =>array(
                //         'type'=>'select',
                //         'filter_data' => array(''=>'All','start'=>'Start','end'=>'End')),
                        'shift_flag' =>[
                            'type'=>'select',
                            'filter_data' => [''=>'All','OverTime'=>'OverTime','Offshift'=>'Offshift','absent'=>'Absent','Early'=>'Early','Late'=>'Late']],
    ],

        ];
        return $dataTmp;
    }
}
