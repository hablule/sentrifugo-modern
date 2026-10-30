<?php


class Schedule_Model_Schedulecycleshift extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_schedulecycleshift';
    protected $_primary = 'schedulecycleshift_id';

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
                    if ($key == 'description')
                        $searchQuery .= "s.description like '%" . $val . "%' AND ";
                    else if ($key == 'isactive')
                        $searchQuery .= " s.isactive like '%" . $val."%' AND ";
                    else if ($key == 'shift')
                        $searchQuery .= " sc.shift_id like '%" . $val . "%' AND ";
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
                //die(json_encode($searchQuery));
            }
        }

        $objName = 'schedulecycleshift';
        $objID = 'schedulecycleshift';
        $tableFields = array(
            'action' => 'Action',
            'shift' => 'Shift',
            'description'=>'Description',
            'isactive' => 'Active',
        );

        $tablecontent = $this->getSchedulecycleshiftData($sort, $by, $pageNo, $perPage, $searchQuery);

      
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
                ),
            ),


        );
        return $dataTmp;
    }

    public function getSchedulecycleshiftData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";
        if($searchQuery!='')
            $where .= ' AND '. $searchQuery;
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('s' => 'main_schedulecycleshift'))
                        ->join(array('sc'=>'main_shift'),'s.shift_id = sc.shift_id')
                        ->where($where)
                        ->order("$by $sort")
                        ->limitPage($pageNo, $perPage);

        return $select;
    }

    public function getSchedulecycleshiftById($id)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->where('id='.$id);
        return $this->fetchAll($select)->toArray();
    }

    public function saveOrUpdate($data, $where)
    {
        if($where!='')
        {
            $this->update($data, $where);
            $id = 'update';
        }
        else
        {
            $this->insert($data);
            $id=$this->getAdapter()->lastInsertId();
            return $id;
        }
    }
}

?>