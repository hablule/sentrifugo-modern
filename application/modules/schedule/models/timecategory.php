<?php


class Schedule_Model_timecategory extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_timecategory';
    protected $_primary = 'timecategory_id';

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
                if($key == 'name')
                $searchQuery .= "name like '%".$val."%' AND ";
                else if($key == 'description')
                $searchQuery .= " description like '%". $val."%' AND ";
                else if($key == 'isactive')
                $searchQuery .= " isactive like '%".$val."%' AND ";
                else $searchQuery .= "" . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
            //die(json_encode($searchQuery));
        }
    }

    $objName = 'timecategory';
    $objID = 'timecategory';
    $tableFields = array(
        'action' => 'Action',
        'name' => 'Name',
        'code' => 'Code',

        'description' => 'Description',
        'isactive' => 'Active'
    );

    $tablecontent = $this->getOTcategoryData($sort, $by, $pageNo, $perPage, $searchQuery);
   
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
        'call' => $call,
        'fromgrid'=>'',
        'menuName' => 'Time Category',
        'dashboardcall' => $dashboardcall,
        'search_filters' => array(
            'isactive' => array(
                'type' => 'select',
                'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
        ),
    );
    
    return $dataTmp;
  }

  public function deleteData($where,$tablename,$id)
  {
      $config=array(
          'name'=>$tablename,
          'primary'=>$id
      );
      $table = new Zend_Db_Table($config);
     
      return $table->delete($where);
  }

  public function getOTcategoryData($sort, $by, $pageNo, $perPage, $searchQuery){
    $where = "1";
    if ($searchQuery) {
        $where .= " AND " . $searchQuery;
    }

    $roleData =  $this->select()
        ->setIntegrityCheck(false)
        ->from('main_timecategory',array('timecategory_id as id','name','code','description','if(isactive="Y","Yes","No") as isactive'))
        ->where($where)
        ->order("$by $sort")
        ->limitPage($pageNo, $perPage);
   
    return $roleData;
  }

  public function getTimeCategory()
  {
    $timeCategoryData = $this->select()
                            ->setIntegrityCheck(false)
                            ->from('main_timecategory',array('timecategory_id','name'))
                            ->where('isactive="Y"');
    return $this->fetchAll($timeCategoryData)->toArray();
  }
  
public function saveORupdateCategory($data, $where)
  {
      if ($where != '' && $where != 'undefind') {
          $this->update($data, $where);
          return 'update';

  } 
      else {
          $this->insert($data);
          $id=$this->getAdapter()->lastInsertId();
          return $id;

      }

  }
 

  public function getSingleCategory($id)
  {
    $catData =  $this->select()
    ->setIntegrityCheck(false)
    ->from('main_timecategory',array('timecategory_id','name','lablecolor','code','description','isactive'))
    ->where('timecategory_id = '.$id);
    return $this->fetchAll($catData)->toArray();
  }

  public function getCategory($shiftid)
  {
      $select = $this->select()
          ->setIntegrityCheck(false)
          ->from(array('tc' => 'main_timecategory'), 'tc.timecategory_id')
          ->join(array('m'=>'main_shift'), 'm.timecategory_id = tc.timecategory_id')
          ->join(array('n' => 'main_schedulecycleshift'), 'm.shift_id = n.shift_id')
          ->join(array('l' => 'main_schedulecycle'), 'l.schedulecycle_id = n.schedulecycle_id')
          ->join(array('ms' =>'main_schedule'),'ms.schedule_id = l.schedule_id')
          ->join(array('es' => 'main_empschedule'), 'es.schedule_id = ms.schedule_id')
          ->where('tc.timecategory_id in (' . $shiftid . ')');
     
      return $this->fetchAll($select)->toArray();
  }

}

?>