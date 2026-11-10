<?php


class Default_Model_payroll extends Zend_Db_Table{
    protected $_name = 'main_payroll';
    protected $_primary = 'id';

    public function getPayroll($sort,$by,$pageNo,$perPage,$searchQuery)
    {
        $where = "approved IS NULL";
        
        if ($searchQuery) {
            $where .= " AND ".$searchQuery;
        }
        $payrolls = $this->select()
                         ->setIntegrityCheck(false)
                         ->order("$by $sort")
                         ->where($where)
                         ->limitPage($pageNo,$perPage);
       
        return $payrolls;
    }

    

    public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$a='',$b='',$c='',$d='')
    {
        $searchQuery = '';
        $searchArray = [];
        if($searchData != '' && $searchData!='undefined')
        {
            $searchValues = json_decode($searchData);
            foreach($searchValues as $key => $val)
            {
                if ($key=='paydate') {
                    $val = sapp_Global::change_date($val,'database');
                }
                $searchQuery .= " ".$key." like '%".$val."%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery," AND");					
        }

        $objName = 'payroll';
        $tableFields = ['action'=>'Action','docment_no'=>'Document No.','paydate'=>'Pay Date'];
        $tablecontent = $this->getPayroll($sort, $by, $pageNo, $perPage,$searchQuery);
        $dataTmp = [
            'sort'=>$sort,
            'by'=>$by,
            'pageNo'=>$pageNo,
            'perPage' => $perPage,				
            'tablecontent' => $tablecontent,
            'menuName'=> 'Payroll',
            'objectname' => $objName,
            'extra' => [],
            'add'=>'add',
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'formgrid'=>'false',
            'searchArray' => $searchArray,
            'call'=>$call,
            'dashboardcall'=>$dashboardcall,
            'search_filters'=>[
                'paydate'=>[
                    'type'=>'datepicker'
                ]
            ]
            
        ];
        return $dataTmp;
    }

    public function saveorupdate($data,$where)
    {
        if ($where!='') {
            
            $this->update($data,$where);
        }
        else{
            
                //code...
               
           
           $this->insert($data);
       
           
            return $this->getAdapter()->lastInsertId();
        }
    }
    public function getpayrollid($id)
    {
        $payroll = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(['a'=>$this->_name],'a.*')
                        ->where('id = '.$id);
                        
            
        return $this->fetchRow($payroll)->toArray();
    }
}

?>