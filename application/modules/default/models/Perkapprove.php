<?php
class Default_Model_Perkapprove extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_employeeperk';
    protected $_primary = 'employeeperk_id';

	public function getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery,$empids,$perm)
    {
        $where = " e.id in (" . $empids . ") ";     
           if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $AdditionData = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ep' => 'main_employeeperk'), array('id'=>'ep.employeeperk_id','emp_id' => 'ep.emp_id', 'perk_id'=>'ep.perk_id','approved'=>'if(ep.approved="N","No","Yes")'))
            ->joinInner(array('mp' => 'main_perk'), "mp.perk_id =ep.perk_id ", array('name' => 'mp.name','type' => 'mp.type','start' =>  'DATE_FORMAT(mp.start,"'.DATEFORMAT_MYSQL.'")','end' =>  'DATE_FORMAT(mp.end,"'.DATEFORMAT_MYSQL.'")','amount' => 'mp.amount'))
            ->joinInner(array('e' => 'main_employees'), "e.id = ep.emp_id ", array('userfullname' => 'e.userfullname','profileimg'=>'e.profileimg'))
            ->joinInner(array('pc' => 'main_perkcategory'), "pc.perkcategory_id = mp.category ", array('c_name' => 'pc.name','c_description' => 'pc.description'))
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
//die($AdditionData);
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
                    $searchQuery .= " mp.type like '%" . $val . "%' AND ";
                else if ($key == 'name')
                    $searchQuery .= " mp.name  like '%" . $val . "%' AND ";
                    else if ($key == 'c_name')
                    $searchQuery .= " pc.name like '%" . $val . "%' AND ";
                    else if ($key == 'c_description')
                    $searchQuery .= " pc.description like '%" . $val . "%' AND ";
                else if ($key == 'userfullname')
                    $searchQuery .= " e.userfullname like '%" . $val . "%' AND ";
                    else if ($key == 'start')
                    $searchQuery .= " mp.start like '%".  sapp_Global::change_date(urldecode($val),'database')."%' AND ";
                    else if ($key == 'end')
                    $searchQuery .= " mp.end like '%".  sapp_Global::change_date(urldecode($val),'database')."%' AND ";
                    elseif ($key == 'approved')
                    $searchQuery .= " ep.approved like'%".$val."%' AND ";
               
                $searchArray[$key] = $val;
            }

            $searchQuery = rtrim($searchQuery, " AND");
           // die(json_encode($searchQuery));
        }
        $objName = 'perkapprove';

        $objId = 'perkapprove';

        $tableFields = array(
            'action' => 'Action',
            'name' => 'Perk Name',
            'c_name' => 'Category',
            'c_description' => 'Description',
            'start' => 'Start Date',
            'end' => 'End Date',
            'userfullname' => 'Employeee Name',
            'type' => 'Perk Type',
            'approved' => 'Is Approved',

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
            'perm'=>$perm,
            'menuName' => 'Employee Perk Request List',
            'extra' => array(),
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'add' => 'add',
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

    public function perkdetail($id)
    {
        $result = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('ep' => 'main_employeeperk'), array('employeeperk_id'=>'ep.employeeperk_id','emp_id' => 'ep.emp_id', 'perk_id'=>'ep.perk_id','approved'=>'ep.approved','modifiedby'=>'ep.modifiedby'))
        ->joinInner(array('mp' => 'main_perk'), "mp.perk_id =ep.perk_id ", array('name' => 'mp.name','type' => 'mp.type','start' => 'mp.start','end' => 'mp.end','amount' => 'mp.amount'))
        ->joinInner(array('e' => 'main_employees'), "e.id = ep.emp_id ", array('userfullname' => 'e.userfullname'))
        ->joinInner(array('pc' => 'main_perkcategory'), "pc.perkcategory_id = mp.category ", array('c_name' => 'pc.name','c_description' => 'pc.description'))
        ->where('ep.employeeperk_id='.$id);

            return $this->fetchAll($result)->toArray();
        }
        public function employeeperkdetail($id)
        {
            $result = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('ep' => 'main_employeeperk'), array('employeeperk_id'=>'ep.employeeperk_id','emp_id' => 'ep.emp_id', 'perk_id'=>'ep.perk_id','approved'=>'ep.approved','amount' => 'ep.amount','modifiedby'=>'ep.modifiedby','isactive'=>'ep.isactive'))
            ->joinInner(array('mp' => 'main_perk'), "mp.perk_id =ep.perk_id ", array('perk_id'=>'mp.perk_id','name' => 'mp.name','type' => 'mp.type','start' => 'mp.start','end' => 'mp.end'))
            ->joinInner(array('e' => 'main_employees'), "e.id = ep.emp_id ", array('userfullname' => 'e.userfullname'))
            ->joinInner(array('pc' => 'main_perkcategory'), "pc.perkcategory_id = mp.category ", array('c_name' => 'pc.name','c_description' => 'pc.description'))
            ->where('ep.employeeperk_id='.$id);
                return $this->fetchAll($result)->toArray();
            }
        public function perk($id)
        {
            $result = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('mp' => 'main_perk'), "mp.perk_id =ep.perk_id ", array('name' => 'mp.name','type' => 'mp.type','start' => 'mp.start','end' => 'mp.end','amount' => 'mp.amount','auto_approved' => 'mp.auto_approved'))
            ->where('mp.perk_id='.$id);
    
                return $this->fetchAll($result)->toArray();
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
        public function editempperk($data,$where){
            $this->update($data,$where);
        }
        public function addundumperk()
        {
            $AdditionData = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('ep' => 'main_employeeperk'), array('employeeperk_id'=>'ep.employeeperk_id','emp_id' => 'ep.emp_id', 'perk_id'=>'ep.perk_id','amount' => 'ep.amount','approved' => 'ep.approved','modifiedby' => 'ep.modifiedby','modifieddate' => 'ep.modifieddate','amount_approved' => 'ep.amount_approved'))
                ->joinInner(array('mp' => 'main_perk'), "mp.perk_id =ep.perk_id ", array('name' => 'mp.name','type' => 'mp.type','start' => 'mp.start','end' => 'mp.end'))
                ->joinInner(array('e' => 'main_employees'), "e.id = ep.emp_id ", array('userfullname' => 'e.userfullname'))
                ->joinInner(array('pc' => 'main_perkcategory'), "pc.perkcategory_id = mp.category ", array('c_name' => 'pc.name','c_description' => 'pc.description'))
                ->where("ep.approved='Y' and ep.isactive='Y'");
                //die($AdditionData);
                return $this->fetchAll($AdditionData)->toArray();
            }
}






