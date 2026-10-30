<?php
class Default_Model_Certificate extends Zend_Db_Table_Abstract{
    protected $_name = 'main_certificate';
    protected $_primary = 'certificate_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(['mc'=>'main_certificate'],['mc.name as name', 'mc.code as code', 'mc.description as description', 'if(mc.isactive="Y","Yes","No") as isactive', 'mc.certificate_id as id'])
            ->joinLeft(['mcp'=>'main_certprovider'],"mc.certprovider_id = mcp.certprovider_id",['provider'=>'mcp.name'])
            ->joinLeft(['mcc'=>'main_certificatecategory'],"mc.certificatecategory_id = mcc.certificatecategory_id",['category'=>'mcc.name'])

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
                if($key=='provider'){
                    $searchQuery .= "mcp.name like '%" . $val . "%' AND ";

                }
                if($key=='category'){
                    $searchQuery .= "mcc.name like '%" . $val . "%' AND ";

                }
                else
                $searchQuery .= "mc." . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'certificate';
        $tableFields = [
            'action' => 'Action',
            'code' => 'Code',
            'name' => 'Name',
            'provider'=>'Provider ',
            'category'=>'Category ',
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
            // 'add'=>'add',
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
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

    public function getcertificate($id)
    {
        $linecate = $this->select()
                         ->setIntegrityCheck(false)
                         ->where('certificate_id='.$id);
        return $this->fetchRow($linecate)->toArray();
    }

    public function getallcertificate()
    {
        $linec = $this->select()
                      ->setIntegrityCheck(false)
                      ->from(['mc'=>'main_certificate'],['mc.name as name', 'mc.certificate_id as certificate_id'])
                      ->joinLeft(['mcp'=>'main_certprovider'],"mc.certprovider_id = mcp.certprovider_id",['provider'=>'COALESCE(mcp.name,0)'])
                      ->where('mc.isactive="Y"');
        return $this->fetchAll($linec)->toArray();
    }

}
?>