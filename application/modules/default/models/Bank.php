<?php
class Default_Model_Bank extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_bank';
    protected $_primary = 'bank_id';

    public function getBank($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
        {
            $where .= " AND " . $searchQuery;
         
        }
        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from($this->_name,['bank_id','name','description','routingno','bank_verification_class','if(isactive="Y","Yes","No") as isactive'])
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
        //die($periodcontroltype);
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = [];
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                if ($key=='isactive') {
                    $searchQuery .= " isactive like '%".$val."%' AND ";
                }
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
                
            }
            
            	
            $searchQuery = rtrim($searchQuery, " AND");
            //die( $searchQuery);
        }
        $objName = 'bank';
        $tableFields = [
            'action' => 'Action',

            'name' => 'Bank Name',
            'description' => 'Description',
            'routingno' => 'Routing No.',
            'bank_verification_class' => 'Verfication Class',
            'isactive'=>'Active'
        ];
        $bool_arr = ['' => 'All', 'Y' => 'Yes', 'N' => 'No'];
        $tablecontent = $this->getBank($sort, $by, $pageNo, $perPage, $searchQuery);
        $dataTmp = [
			'sort' => $sort,
			'by' => $by,
			'pageNo' => $pageNo,
			'perPage' => $perPage,				
			'tablecontent' => $tablecontent,
			'objectname' => $objName,
			'extra' => [],
			'tableheader' => $tableFields,
			'jsGridFnName' => 'getAjaxgridData',
			'jsFillFnName' => '',
			'searchArray' => $searchArray,
			'call'=>$call,
            'add'=>'add',

			'dashboardcall'=>$dashboardcall,
			'search_filters' => [
                'isactive' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
    ]
		];	
        return $dataTmp;
    }


    public function SaveorUpdateBankData($data, $where)
    {
        if ($where != '') {
            $this->update($data, $where);
            return 'update';
        } else {
            $this->insert($data);
            $id = $this->getAdapter()->lastInsertId('main_bank');
            return $id;
        }
    }

    public function getSingleBank($id)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['b' => 'main_bank'], ['b.*'])
            ->where('bank_id=' . $id);
        return $this->fetchAll($query)->toArray();
    }
    public function getBanks()
    {
        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->order("name ASC")
            ->where('isactive="Y"');
      
        return $this->fetchAll($bank)->toArray();
    }
}
