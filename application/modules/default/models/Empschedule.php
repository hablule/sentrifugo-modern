<?php

class Default_Model_Empschedule extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_empschedule';
    protected $primary = 'empschedule_id';
    public function getempsch($id)
    {
        $emptbl = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('empschedule_id = '.$id);
                   
        return $this->fetchAll($emptbl)->toArray();
    }
    public function getempschbyid($id)
    {
        $emptbl = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(['emp'=>$this->_name],['empschid'=>'emp.empschedule_id'])
                       ->join(['sch'=>'main_schedule'],'emp.schedule_id=sch.schedule_id','sch.*')
                       ->where('emp.empschedule_id = '.$id);
                      
        return $this->fetchRow($emptbl)->toArray();
    }
    public function getSchedulebyemp($userid)
    {
        $config = [
            'name' => 'main_empschedule',
            'primary' => 'empschedule_id'
        ];
        $db = new Zend_Db_Table($config);
        $tblweeks = $db->select()
            ->from($config['name'])
            ->setIntegrityCheck(false)
            ->where('isactive="Y" and emp_id ='.$userid);
       
        return $db->fetchAll($tblweeks)->toArray();
    }
    public function getSchedule()
    {
        $config = [
            'name' => 'main_schedule',
            'primary' => 'schedule_id'
        ];
        $db = new Zend_Db_Table($config);
        $tblweeks = $db->select()
            ->setIntegrityCheck(false)
            ->where('isactive="Y"');
        return $db->fetchAll($tblweeks)->toArray();
    }

    public function getSchedulebyid($id)
    {
        $config = [
            'name' => 'main_schedule',
            'primary' => 'schedule_id'
        ];
        $db = new Zend_Db_Table($config);
        $tblweeks = $db->select()
            ->setIntegrityCheck(false)
            ->from(['s' => 'main_schedule'], ['id' => 's.schedule_id', 'schedule_name' => 's.schedule_name'])
            ->where('schedule_id=' . $id);
        return $db->fetchAll($tblweeks)->toArray();
    }
    public function getSingleSchedule($emp)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['emp_sch' => 'main_empschedule'], '')
            ->joinInner(['sch' => 'main_schedule'], 'emp_sch.schedule_id = sch.schedule_id', ['schedule_name' => 'sch.name', 'sch_id' => 'sch.schedule_id'])
            ->where('emp_sch.emp_id=' . $emp);
        //die($query);
        $data = $this->fetchAll($query)->toArray();
        return $data;
    }
    public function getEmpScheduleBySchedule($id)
    {
        $query = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(['emp_sch'=>'main_empschedule'])
                        ->where('schedule_id = '.$id);
        return $this->fetchAll($query)->toArray();
    }
    public function saveOrUpdate($data,$where)
    {
        
             if ($where != '' && $where != 'undefined' && $where != null) {
               $this->update($data,'empschedule_id='.$where);
             } else {
            $this->insert($data);
        }
    }

    public function deleteEmpSch($emp_id)
    {
        $this->delete('emp_id=' . $emp_id);
    }


        public function getData($sort, $by, $pageNo, $perPage, $searchQuery,$id)
    {
		$where = " l.emp_id = " . $id  ;
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(['a' => 'main_schedule'], ['sch_id'=>'a.schedule_id','name' => 'a.name', 'startdate' => 'DATE_FORMAT(l.start_date,"'.DATEFORMAT_MYSQL.'")', 'enddate' => 'DATE_FORMAT(l.end_date,"'.DATEFORMAT_MYSQL.'")'])
        ->joinInner(['l' => 'main_empschedule'], "l.schedule_id = a.schedule_id ", ['id' => 'l.empschedule_id','isactive'=>'if(l.isactive="Y","Yes","No")'])
        ->joinInner(['u'=>'main_employees'],'l.emp_id = u.id',['eid'=>'u.id'])
        ->order("$by $sort")
        ->where($where)
        ->limitPage($pageNo, $perPage);
        //die($bank);
        return $bank;
    }

    public function getEmpSchedule($ids)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(['m'=>'main_empschedule'], 'm.schedule_id')
                        ->where('m.schedule_id in('.$ids.')');
        return $this->fetchAll($select)->toArray();
    }

   
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1 = '', $exParam2 = '', $exParam3 = '', $exParam4 = '')
    {
		$searchQuery = '';
		$searchArray = [];
		$data = [];

		if ($searchData != '' && $searchData != 'undefined') {
			$searchValues = json_decode($searchData);

			foreach ($searchValues as $key => $val) {
                if($key == 'name'){
                    $searchQuery.=" a.schedule_name like '%" . $val . "%' AND "; 
                }
                if($key == 'name'){
                    $searchQuery.=" a.schedule_name like '%" . $val . "%' AND "; 
                }
                else if ($key == 'startdate')
				$searchQuery .= " a.start_date like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
				else if ($key == 'enddate')
				$searchQuery .= " a.end_Date like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                else if($key == 'isactive')
                {
                    $searchQuery.=" l.isactive like '%" . $val . "%' AND "; 
                }
           
            $searchArray[$key] = $val;
				
			
			}
			$searchQuery = rtrim($searchQuery, " AND");
		}
        $objName = 'empschedule';
        $tableFields = [
            'action' => 'Action',
            'name' => 'Schedule Name',
            'startdate' => 'Start Date',
            'enddate' => 'End Date',
            'isactive' => 'Active'
        ];

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery, $exParam1);
        $dataTmp = [
            'userid' => $exParam1,
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
			'add' => 'add',
			'menuName' => 'Schedule',
			'formgrid' => 'true',
			'unitId' => $exParam1,
			'call' => $call,
			'userAction' => $exParam3,	
			'context' => $exParam2,
            'search_filters' => [
                'isactive' => [
					'type' => 'select',
					'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No'],
				],	
            'startdate' => ['type' => 'datepicker'],
		    'enddate' => ['type' => 'datepicker']
            ]
        ];
        return $dataTmp;
    }


    }

