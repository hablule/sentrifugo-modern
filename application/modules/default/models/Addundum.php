<?php
class Default_Model_Addundum extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_addundum';
    protected $_primary = 'addundum_id';

	public function getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "mp.type='Spot'";
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $AdditionData = $this->select()
            ->setIntegrityCheck(false)
            ->from(['ep' => 'main_employeeperk'], ['employeeperk_id'=>'ep.employeeperk_id','emp_id' => 'ep.emp_id', 'perk_id'=>'ep.perk_id'])           
            ->joinInner(['mp' => 'main_perk'],"mp.perk_id =ep.perk_id a ", ['name' => 'mp.name','type' => 'mp.type','start' => 'mp.start','end' => 'mp.end','amount' => 'mp.amount'])
            ->joinInner(['e' => 'main_users'], "e.id = ep.emp_id  ", ['userfullname' => 'e.userfullname'])
            ->joinInner(['pc' => 'main_perkcategory'], "pc.perkcategory_id = mp.category ", ['c_name' => 'pc.name','c_description' => 'pc.description'])
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
//die($AdditionData);
        return $AdditionData;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];
        $data = [];
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
               
                $searchArray[$key] = $val;
            }

            $searchQuery = rtrim($searchQuery, " AND");
           // die(json_encode($searchQuery));
        }
        $formgrid = '';  
        $objName = 'addundum';

        $objId = 'addundum';

        $tableFields = [
            'action' => 'Action',
            'name' => 'Perk Name',
            'c_name' => 'Category',
            'c_description' => 'Description',
            'start' => 'Start Date',
            'end' => 'End Date',
            'userfullname' => 'Employeee Name',
            'type' => 'Perk Type',
            // 'earned_time'=> 'Earned Minute',
         

        ];
        $tablecontent = $this->getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery);

        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objId,
            'menuName' => 'Employee Addundum Perk ',
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'formgrid' => $formgrid,
            'add' => 'add',
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters'=>[
              
                 
            ],
        ];
        return $dataTmp;
    }
   
   
    public function perkdetail($id)
    {
        $result = $this->select()
        ->setIntegrityCheck(false)
        ->from(['ep' => 'main_employeeperk'], ['employeeperk_id'=>'ep.employeeperk_id','emp_id' => 'ep.emp_id', 'perk_id'=>'ep.perk_id'])
        ->joinInner(['mp' => 'main_perk'], "mp.perk_id =ep.perk_id ", ['name' => 'mp.name','type' => 'mp.type','start' => 'mp.start','end' => 'mp.end','amount' => 'mp.amount'])
        ->joinInner(['e' => 'main_users'], "e.id = ep.emp_id ", ['userfullname' => 'e.userfullname'])
        ->joinInner(['pc' => 'main_perkcategory'], "pc.perkcategory_id = mp.category ", ['c_name' => 'pc.name','c_description' => 'pc.description'])
        ->where('ep.employeeperk_id='.$id);

            return $this->fetchAll($result)->toArray();
        }
        public function perk($id)
        {
            $result = $this->select()
            ->setIntegrityCheck(false)
            ->from(['mp' => 'main_perk'], "mp.perk_id =ep.perk_id ", ['name' => 'mp.name','type' => 'mp.type','start' => 'mp.start','end' => 'mp.end','amount' => 'mp.amount','auto_approved' => 'mp.auto_approved'])
            ->where('mp.perk_id='.$id);
    
                return $this->fetchAll($result)->toArray();
            }
            public function editep($data,$where)
            {
               $config=[
                   'name'=>'main_employeeperk',
                   'primary'=>'employeeperk_id'
               ];
               $table = new Zend_Db_Table($config);
           
                    $table->update($data,$where);
                }
        
            
        
            public function addundum($datas){
                //die(json_encode($datas));
                foreach ($datas as $data) { 
                        $this->insert($data);
                    }
                   
                }

            public function saveorupdate($data,$where)
            {
                if ($where!='') {
                    $this->update($data,$where);
                }
                else
                {
                    $this->insert($data);
                }
            }
            public function getPeriods($date,$where)
            {
                $config = [
                    'name'=>'main_period',
                    'primary'=>'period_id'
                ];
                if ($where!='') {
                    $where = ' AND '.$where;
                }
                $table = new Zend_Db_Table($config);    
                $period = $table->select()
                                ->setIntegrityCheck(false)
                                ->from(['p'=>$config['name']],['p.period_id','p.end_date','P.start_date'])
                                ->joinInner(['pc'=>'main_periodcontrol'],'pc.period_id=p.period_id','pc.isautogenerated')
                                ->where("p.isactive = 'Y' AND pc.control_type = 'REMUNERATION' AND '".$date."' BETWEEN p.start_date AND p.end_date".$where);
                     //die($period);
               return $table->fetchAll($period)->toArray();
        
            }  
        
            public function dedincurr($empid)
            {
                $select = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(['ad'=>'main_addundum'],'ad.emp_id')
                            ->join(['l'=>'main_load'],'l.load_id=ad.load_id',['l.name','l.amount'])
                            ->where('ad.emp_id='.$empid.' AND ad.approved="Y" AND ad.processed="N"');
                            
                return $this->fetchAll($select)->toArray();
            } 
            
            public function benfincurr($empid)
            {
                $select = $this->select()
                               ->setIntegrityCheck(false)
                               ->from(['ad'=>'main_addundum'],'ad.emp_id')
                               ->join(['p'=>'main_perk'],'p.perk_id=ad.perk_id',['p.amount','p.name'])
                               ->where('ad.emp_id='.$empid.' AND ad.approved="Y" AND ad.processed="N"');
                
                        return $this->fetchAll($select)->toArray();
            }


        }







