<?php
class Assets_Model_Uom extends Zend_Db_Table_Abstract{
    protected $_name = 'main_uom';
    protected $_primary = 'uom_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from('main_uom',['name as name', 'code as code','stdprecision as stdprecision', 'symbol as symobl',  'description as description', 'if(isactive="Y","Yes","No") as isactive', 'uom_id as id'])
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = [];
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'uom';
        $tableFields = [
            'action' => 'Action',
            'code' => 'Code',
            'name' => 'Name',
            'stdprecision' => 'Precision',
            'symbol' => 'Symbol',
            'description' => 'Description',
            'isactive' => 'Active',
        ];

        $tablecontent = $this->getData($sort, $by, $pageNo, $perPage, $searchQuery);
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
				'dashboardcall'=>$dashboardcall,
				'menuName' => 'UOM',
            'search_filters' => [
                'isactive' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
            ]
        ];
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

    public function getassetuom($id)
    {
       
        $assetsubcategoriesData = $this->select()
		->setIntegrityCheck(false)
		->from(['c1'=>$this->_name],['c1.*','c1.uom_id as id'])	
		->where('c1.uom_id='.$id.'');
		return $this->fetchAll($assetsubcategoriesData)->toArray();
	
                        }

    public function getallassetuom()
    {
        $linec = $this->select()
                      ->setIntegrityCheck(false)
                      ->where('isactive="Y"');
        return $this->fetchAll($linec)->toArray();
    }

}
?>