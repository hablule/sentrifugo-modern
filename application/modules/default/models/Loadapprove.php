<?php
class Default_Model_Loadapprove extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_employeeload';
    protected $_primary = 'employeeload_id';

	public function getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery,$empids,$perm)
    {
        $where = " e.id in (" . $empids . ") ";     
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $AdditionData = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('el' => 'main_employeeload'), array('id'=>'el.employeeload_id','emp_id' => 'el.emp_id', 'load_id'=>'el.load_id','approved'=>'if(el.approved="N","No","Yes")'))
            ->joinInner(array('ml' => 'main_load'), "ml.load_id =el.load_id ", array('name' => 'ml.name','type' => 'ml.type','start' =>  'DATE_FORMAT(ml.start,"'.DATEFORMAT_MYSQL.'")','end' => 'DATE_FORMAT(ml.end,"'.DATEFORMAT_MYSQL.'")','amount' => 'ml.amount'))
            ->joinLeft(array('e' => 'main_employees'), "e.id = el.emp_id ", array('userfullname' => 'e.userfullname','profileimg'=>'e.profileimg'))
            ->joinInner(array('lc' => 'main_loadcategory'), "lc.load_category_id = ml.load_category_id ", array('c_name' => 'lc.name','c_description' => 'lc.description'))
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
        return $AdditionData;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall,$empids,$perm)
    {
        $searchQuery = '';
        $searchArray = array();
        $data = array();
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
             if ($key == 'type')
                    $searchQuery .= " ml.type like '%" . $val . "%' AND ";
                else if ($key == 'name')
                    $searchQuery .= " ml.name  like '%" . $val . "%' AND ";
                    else if ($key == 'c_name')
                    $searchQuery .= " lc.name like '%" . $val . "%' AND ";
                    else if ($key == 'c_description')
                    $searchQuery .= " lc.description like '%" . $val . "%' AND ";
                else if ($key == 'userfullname')
                    $searchQuery .= " e.userfullname like '%" . $val . "%' AND ";
                    else if ($key == 'start')
                    $searchQuery .= " ml.start like '%".  sapp_Global::change_date(urldecode($val),'database')."%' AND ";
                    else if ($key == 'end')
                    $searchQuery .= " ml.end like '%".  sapp_Global::change_date(urldecode($val),'database')."%' AND ";
                    else if ($key == 'approved')
                    $searchQuery .= " el.approved like '%" . $val . "%' AND ";             
                   $searchArray[$key] = $val;
            }

            $searchQuery = rtrim($searchQuery, " AND");
           // die(json_encode($searchQuery));
        }
        $objName = 'loadapprove';

        $objId = 'loadapprove';

        $tableFields = array(
            'action' => 'Action',
            'name' => 'Load Name',
            'c_name' => 'Category',
            'c_description' => 'Description',
            'start' => 'Start Date',
            'end' => 'End Date',
            'userfullname' => 'Employeee Name',
            'type' => 'Load Type',
            'approved' => 'Approved',

            // 'earned_time'=> 'Earned Minute',
         

        );
        $tablecontent = $this->getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery,$empids,$perm);

        $dataTmp = array(
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objId,
            'menuName' => 'Employee Load Request List',
            'extra' => array(),
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'add' => 'add',
            'perm'=>$perm,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters'=>array(
                'start'=>array('type'=>'datepicker'),
                'end'=>array('type'=>'datepicker'),
                'approved' => array(
                    'type' => 'select',
                    'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
                 
            ),
        );
        return $dataTmp;
    }
   
    public function DeleteData($where)
    {
            $this->delete($where);
            return 'delete';
         
    }
    public function loaddetail($id)
    {
        $result = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('el' => 'main_employeeload'), array('employeeload_id'=>'el.employeeload_id','emp_id' => 'el.emp_id', 'load_id'=>'el.load_id','approved'=>'el.approved'))
            ->joinInner(array('ml' => 'main_load'), "ml.load_id =el.load_id ", array('name' => 'ml.name','type' => 'ml.type','start' => 'ml.start','end' => 'ml.end','amount' => 'ml.amount'))
            ->joinInner(array('e' => 'main_employees'), "e.id = el.emp_id ", array('userfullname' => 'e.userfullname'))
            ->joinInner(array('lc' => 'main_loadcategory'), "lc.load_category_id = ml.load_category_id ", array('c_name' => 'lc.name','c_description' => 'lc.description'))
        ->where('el.employeeload_id='.$id);
            return $this->fetchAll($result)->toArray();
        }
     
        public function editempload($data,$where){
            $this->update($data,$where);
        }
        public function addundumload()
        {
            $AdditionData = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('el' => 'main_employeeload'), array('employeeload_id'=>'el.employeeload_id','emp_id' => 'el.emp_id', 'load_id'=>'el.load_id','approved' =>'el.approved','modifiedby' => 'el.modifiedby','modifieddate' => 'el.modifieddate','amount_approved' => 'el.amount_approved'))
            ->joinInner(array('ml' => 'main_load'), "ml.load_id =el.load_id ", array('name' => 'ml.name','type' => 'ml.type','start' =>  'DATE_FORMAT(ml.start,"'.DATEFORMAT_MYSQL.'")','end' =>  'DATE_FORMAT(ml.end,"'.DATEFORMAT_MYSQL.'")','amount' => 'ml.amount'))
            ->joinInner(array('e' => 'main_employees'), "e.id = el.emp_id ", array('userfullname' => 'e.userfullname'))
            ->joinInner(array('lc' => 'main_loadcategory'), "lc.load_category_id = ml.load_category_id ", array('c_name' => 'lc.name','c_description' => 'lc.description'))
            ->where("el.approved='Y' and e.isactive='Y'");
           // die($AdditionData);
                return $this->fetchAll($AdditionData)->toArray();
            }
            public function getsalary($id)
            {
                $result = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('s' => 'main_empsalarydetails'),array('amount' => 's.amount'))
                ->where('s.emp_id='.$id.' AND s.isactive="Y"');
   // die($result);
                    return $this->fetchAll($result)->toArray();
                }
                public function employeeloaddetail($id)
                {
                    $result = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('el' => 'main_employeeload'), array('employeeload_id'=>'el.employeeload_id','emp_id' => 'el.emp_id', 'load_id'=>'el.load_id','approved'=>'el.approved','amount' => 'el.amount','modifiedby'=>'el.modifiedby','isactive'=>'el.isactive'))
                    ->joinInner(array('ml' => 'main_load'), "ml.load_id =el.load_id ", array('load_id'=>'ml.load_id','name' => 'ml.name','type' => 'ml.type','start' => 'ml.start','end' => 'ml.end'))
                    ->joinInner(array('e' => 'main_employees'), "e.id = el.emp_id ", array('userfullname' => 'e.userfullname'))
                    ->joinInner(array('lc' => 'main_loadcategory'), "lc.load_category_id = ml.load_category_id ", array('c_name' => 'lc.name','c_description' => 'lc.description'))
                    ->where('el.employeeload_id='.$id);
            
                        return $this->fetchAll($result)->toArray();
                    }
}






