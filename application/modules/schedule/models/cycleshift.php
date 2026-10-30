<?php

class Schedule_Model_Cycleshift extends Zend_Db_Table_Abstract
{
    protected $_name = "main_schedulecycleshift";
    protected $_primary = "schedulecycleshift_id";

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $p1, $p2, $p3, $p4)
    {
        $searchQuery = '';
        $searchArray = array();
        $data = array();
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);

            if (count($searchValues) > 0) {
                foreach ($searchValues as $key => $val) {
                    if ($key == 'shift')
                        $searchQuery .= "s.name like '%" . $val . "%' AND ";
                    else if ($key == 'isactive')
                        $searchQuery .= " sc.isactive like '%" . $val . "%' AND ";    
                    /* else if ($key == 'description')
                        $searchQuery .= " sc.description like '%" . $val . "%' AND "; */
             
                    else $searchQuery .= "sc." . $key . " like '%" . $val . "%' AND ";
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
                //die(json_encode($searchQuery));
            }
        }

        $objName = 'cycleshift';
        $objID = 'cycleshift';
        $tableFields = array(
            'action' => 'Action',
            'shift' => 'Shift',
            'description'=>'Description',
            'isactive' => 'Active'
            
        );

        $tablecontent = $this->getShiftCycle($sort, $by, $pageNo, $perPage, $searchQuery);

      
        $dataTmp = array(
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objID,
            'extra' => array(),
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            // 'add' => 'add',
            'formgrid'=>'',
            'call' => $call,
            'menuName' => 'Schedule Cycle',
            'dashboardcall' => $dashboardcall,
            'search_filters' => array(
                'isactive' => array(
                    'type' => 'select',
                    'filter_data' => array('' => 'All', 'Y' => 'Yes', 'N' => 'No')
                )
            ),


        );
        return $dataTmp;
    }

    public function getShiftCycle($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";
        if($searchQuery != '')
            $where .= " AND ".$searchQuery;
      
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('sc'=>'main_schedulecycleshift'),array('sc.schedulecycleshift_id as id','sc.isactive'))
                        ->join(array('s'=>'main_shift'),'s.shift_id = sc.shift_id',array('s.name as shift'))
                        ->where($where)
                        ->order("$by $sort")
                        ->limitPage($pageNo, $perPage);
        
        return $select;
    }

    public function SaveorUpdateData($data,$where)
    {
        if ($where != ''){
             $this->update($data, $where);
             $Id = 'update';
        }
        else{
            $this->insert($data);
        }
        return $Id;
    }

    public function getCycleShift($id)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->where('schedulecycle_id='.$id);
                      
        return $this->fetchAll($select)->toArray();
    }

    public function getCycleShiftById($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('schedulecycleshift_id='.$id);
        return $this->fetchRow($select)->toArray();
    }

    public function getCycleShifts($schedule_cycleids)
    {
        $select = $this->select()
                        ->distinct()
                        ->setIntegrityCheck(false)
                        ->where('schedulecycle_id in ('.$schedule_cycleids.')');
        return $this->fetchAll($select)->toArray();
    }

    public function deleteCycleshift($shiftid,$id)
    {
        $query = "DELETE FROM main_schedulecycleshift where shift_id=".$shiftid." AND schedulecycle_id=".$id;
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query($query);
    }

    

}


?>