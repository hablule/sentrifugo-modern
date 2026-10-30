<?php


class Payroll_Model_payroll extends Zend_Db_Table{
    protected $_name = 'main_payroll';
    protected $_primary = 'id';

    public function getPayroll($sort,$by,$pageNo,$perPage,$searchQuery)
    {
        $where = "";
        
        if ($searchQuery) {
            $where .= " AND ".$searchQuery;
        }
        $payrolls = $this->select()
                         ->setIntegrityCheck(false)
                         ->from($this->_name,['document_no'=>'document_no','paydate'=>'DATE_FORMAT(paydate,"'.DATEFORMAT_MYSQL.'")'])
                         ->order("$by $sort")
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
                 if ($key == 'paydate'){

                
                $searchQuery .= "paydate like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
            }else
                $searchQuery .= " ".$key." like '%".$val."%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery," AND");					
        }

        $objName = 'payroll';
        $tableFields = ['action'=>'Action','document_no'=>'Document No.','paydate'=>'Pay Date'];
        $tablecontent = $this->getPayroll($sort, $by, $pageNo, $perPage,$searchQuery);
        $dataTmp = [
            'sort'=>$sort,
            'by'=>$by,
            'pageNo'=>$pageNo,
            'menunName'=>'Payrolls',
            'perPage' => $perPage,				
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'formgrid' => 'true',
            'call'=>$call,
            'dashboardcall'=>$dashboardcall,
            'search_filters' => [
                'paydate' =>['type'=>'datepicker']
            ]
            
        ];
        return $dataTmp;
    }
}

?>