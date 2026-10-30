<?php
class Default_Model_Paymentcard extends Zend_Db_Table_Abstract{
    protected $_name = 'main_paymentcard';
    protected $_primary = 'paymentcard_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('mp'=>'main_paymentcard'),array('mp.name as name', 'mp.code as code', 'mp.description as description', 'if(mp.isactive="Y","Yes","No") as isactive', 'mp.paymentcard_id as id'))
            ->joinLeft(array('mcp'=>'main_cardprovider'),"mp.cardprovider_id = mcp.cardprovider_id",array('cardproviders'=>'mcp.name'))
            ->joinLeft(array('mpct'=>'main_paymentcardtype'),"mp.paymentcardtype_id = mpct.paymentcardtype_id",array('type'=>'mpct.name'))
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
                if($key=='cardproviders'){
                    $searchQuery .= "mcp.name like '%" . $val . "%' AND ";

                }
                else if($key=='type'){
                    $searchQuery .= "mpct.name like '%" . $val . "%' AND ";

                }
                else{
                    $searchQuery .= "mp." . $key . " like '%" . $val . "%' AND ";

                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'paymentcard';
        $tableFields = array(
            'action' => 'Action',
            'code' => 'Code',
            'name' => 'Name',
            'cardproviders'=>'Card Provider',
            'type'=>'Type ',
            'description' => 'Description',
            'isactive' => 'Active',
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

    public function getpaymentcard($id)
    {
        $linecate = $this->select()
                         ->setIntegrityCheck(false)
                         ->where('paymentcard_id='.$id);
        return $this->fetchRow($linecate)->toArray();
    }

    public function getallpaymentcard()
    {
        $linec = $this->select()
                      ->setIntegrityCheck(false)
                      ->from(array('mp'=>'main_paymentcard'),array('mp.name as name', 'mp.paymentcard_id as paymentcard_id'))
                      ->where('mp.isactive="Y"');
        return $this->fetchAll($linec)->toArray();
    }

}
?>