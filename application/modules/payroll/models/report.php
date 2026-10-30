<?php
class Payroll_Model_report extends Zend_Db_Table
{
    protected $_name = 'main_payroll';
    protected $_primary = 'id';

    public function getPayrollDetails($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where ='1';

        if ($searchQuery) {
            $where .= " AND ".$searchQuery;
           
        }

        $payrollsdetail = $this->select()
                               ->setIntegrityCheck(false)
                               ->from(['pr'=>'main_payroll'],['pr.docment_no','DATE_FORMAT(pr.paydate,"'.DATEFORMAT_MYSQL.'") as paydate','DATE_FORMAT(pr.approved,"'.DATEFORMAT_MYSQL.'") as approved'])
                               ->joinInner(['prl'=>'main_payroll_line'],'pr.id = prl.payroll_id')
                               ->joinInner(['prd'=>'main_payroll_detail'],'prl.id=prd.pay_line_id')
                               ->where($where)
                               ->order("$by $sort")
                               ->limitPage($pageNo,$perPage);
       // die($payrollsdetail);
        return $payrollsdetail;
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                if ($key == 'docment_no')
                    $searchQuery .= " docment_no like '%" . $val . "%' AND ";
                else if($key == 'paydate')
                $searchQuery .= " paydate like '%".sapp_Global::change_date($val,'database'). "%' AND ";
                else if($key == 'approved')
                    $searchQuery .= " approved like '%".sapp_Global::change_date($val,'database'). "%' AND ";
                
                    
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
            
        }
        $objName = 'report';

        $objId = 'report';
        $tableFields = [
            'action' => 'Action',
            'docment_no' => 'Document No.',
            'paydate' => 'Pay Date',
            'approved' => 'Approved Date'
            // 'earned_time'=> 'Earned Minute',
         

        ];
        $tablecontent = $this->getPayrollDetails($sort, $by, $pageNo, $perPage, $searchQuery);
        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objId,
            'menuName' => 'Payroll List',
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'formGrid'=>'',
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'paydate' =>['type'=>'datepicker'],
                'approved'=>['type'=>'datepicker']
            ]
            
        ];
        return $dataTmp;
    }

    public function getpayrollinfo($id)
    {
        $payrollsdetail = $this->select()
        ->setIntegrityCheck(false)
        ->from(['pr'=>'main_payroll'])
        ->where('pr.id = '.$id);
   
        return $this->fetchAll($payrollsdetail)->toArray();
    }
    
    public function getpayrolldetail($id)
    {
        $paydetail = $this->select()
                          ->setIntegrityCheck(false)
                          ->from(['pd'=>'main_payroll_detail'])
                          ->where('pay_line_id = '.$id);
                          
        return $this->fetchAll($paydetail)->toArray();

    }

    public function getpayline($id,$empid)
    {
        $paydet = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(['pl'=>'main_payroll_line'])
                       ->where('payroll_id = '.$id.' AND emp_id = '.$empid);
        return $this->fetchRow($paydet)->toArray();
    }

    public function getemp()
    {
        $config = [
            'name'=>'main_employees',
            'primary'=>'id'
        ];
        $table = new Zend_Db_Table($config);
        $emp = $table->select()
                     ->setIntegrityCheck(false)
                     ->from('main_employees',['id','userfullname'])
                     ->where('isactive = 1 AND id!=0 AND id!=1');
                  
        return $table->fetchAll($emp)->toArray();
    }

    public function getempbyid($empid)
    {
        
   
        $emp = $this->select()
                     ->setIntegrityCheck(false)
                     ->from('main_users',['id','userfullname'])
                     ->where('isactive = 1 AND id = '.$empid);
                  
        return $this->fetchRow($emp)->toArray();
    }
}



?>