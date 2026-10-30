<?php
class Default_Model_Cardprovider extends Zend_Db_Table_Abstract{
    protected $_name = 'main_cardprovider';
    protected $_primary = 'cardprovider_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('cp'=>'main_cardprovider'),array('cp.name as name', 'cp.code as code', 'cp.description as description', 'if(cp.isactive="Y","Yes","No") as isactive', 'if(cp.isbank="Y","Yes","No") as isbank', 'cp.cardprovider_id as id'))
            ->joinLeft(array('mb'=>'main_bank'),"cp.bank_id = mb.bank_id",array('bank'=>'mb.name'))
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = array();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                if($key=='bank'){
                    $searchQuery .= "mb.name like '%" . $val . "%' AND ";

                }
              
                else
                $searchQuery .= "cp." . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'cardprovider';
        $tableFields = array(
            'action' => 'Action',
            'code' => 'Code',
            'name' => 'Name',
            'isbank'=>'Is Bank ',
            'isactive' => 'Active',
            'bank'=>'Bank name',
            'description' => 'Description',

        );

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery);
        $dataTmp = array(
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'extra' => array(),
            'add'=>'add',
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => array(
                'isactive' => array(
                    'type' => 'select',
                    'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
                    'isbank' => array(
                        'type' => 'select',
                        'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
            )
        );
        return $dataTmp;
    }

    public function SaveorUpdateData($data, $where)
	{
	    if($where != ''){
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId($this->_name);
			return $id;
		}
		
	}

    public function getcardprovider($id)
    {
        $linecate = $this->select()
                         ->setIntegrityCheck(false)
                         ->where('cardprovider_id='.$id);
        return $this->fetchRow($linecate)->toArray();
    }

    public function getallcardprovider()
    {
        $linec = $this->select()
                      ->setIntegrityCheck(false)
                      ->where('isactive="Y"');
        return $this->fetchAll($linec)->toArray();
    }

}
?>