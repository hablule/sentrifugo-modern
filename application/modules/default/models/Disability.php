<?php
class Default_Model_Disability extends Zend_Db_Table_Abstract{
    protected $_name = 'main_disability';
    protected $_primary = 'disability_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('md'=>'main_disability'),array('md.name as name', 'md.code as code', 'md.description as description', 'if(md.isactive="Y","Yes","No") as isactive', 'md.disability_id as id'))
            ->joinLeft(array('mdt'=>'main_disabilitytype'),"md.disabilitytype_id = mdt.disabilitytype_id",array('type'=>'mdt.name'))
            ->joinLeft(array('mdc'=>'main_disabilitycategory'),"md.disabilitycategory_id = mdc.disabilitycategory_id",array('category'=>'mdc.name'))

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
                if($key=='type'){
                    $searchQuery .= "mdt.name like '%" . $val . "%' AND ";

                }
                if($key=='category'){
                    $searchQuery .= "mdc.name like '%" . $val . "%' AND ";

                }
                else
                $searchQuery .= "md." . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'disability';
        $tableFields = array(
            'action' => 'Action',
            'code' => 'Code',
            'name' => 'Name',
            'type'=>'Type ',
            'category'=>'Category ',
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
            // 'add'=>'add',
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

    public function getdisability($id)
    {
        $linecate = $this->select()
                         ->setIntegrityCheck(false)
                         ->where('disability_id='.$id);
        return $this->fetchRow($linecate)->toArray();
    }

    public function getalldisability()
    {
        $linec = $this->select()
                      ->setIntegrityCheck(false)
                      ->where('isactive="Y"');
        return $this->fetchAll($linec)->toArray();
    }

}
?>