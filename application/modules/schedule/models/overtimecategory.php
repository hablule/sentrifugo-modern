<?php


class Schedule_Model_OvertimeCategory extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_overtime_category';
    protected $_primary = 'OT_category_id';

  public function getOTcategoryGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $p1, $p2, $p3, $p4)
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
                else if($key == 'isActive')
                $searchQuery .= " isActive like '%".$val."%' AND ";
                else $searchQuery .= "" . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
            //die(json_encode($searchQuery));
        }
    }

    $objName = 'overtimecategory';
    $objID = 'overtimecategory';
    $tableFields = array(
        'action' => 'Action',
        'name' => 'Name',
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
        'add' => 'add',
        'call' => $call,
        'fromgrid'=>'true',
        'menuName' => 'Overtime category',
        'dashboardcall' => $dashboardcall,
        'search_filters' => array(
            'isactive' => array(
                'type' => 'select',
                'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
        ),
    );
    
    return $dataTmp;
  }

  public function getOTcategoryData($sort, $by, $pageNo, $perPage, $searchQuery){
    $where = "1";
    if ($searchQuery) {
        $where .= " AND " . $searchQuery;
    }

    $roleData =  $this->select()
        ->setIntegrityCheck(false)
        ->from('main_overtime_category',array('OT_category_id','name','description','if(isactive="Y","Yes","No") as isactive'))
        ->where($where)
        ->order("$by $sort")
        ->limitPage($pageNo, $perPage);
    
    return $roleData;
  }
  
public function saveORupdateCategory($data, $where)
  {
      if ($where != '' && $where != 'undefind') {
          return $this->update($data, $where);
  } 
      else {
          $this->insert($data);
          return $this->getAdapter()->lastInsertId();
      }

  }

  public function getSingleCategory($id)
  {
    $catData =  $this->select()
    ->setIntegrityCheck(false)
    ->from('main_overtime_category',array('name','description','isactive'))
    ->where('OT_category_id = '.$id);
    return $this->fetchRow($catData)->toArray();
  }

}

?>