<?php
class Default_Model_Pagetab extends Zend_Db_Table_Abstract{
    protected $_name = 'main_pagetab';
    protected $_primary = 'pagetab_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('pt'=>'main_pagetab'),array('pt.tabname as tabname', 'pt.tabcode as tabcode', 'pt.description as description', 'if(pt.isactive="Y","Yes","No") as isactive', 'pt.pagetab_id as id'))
            ->joinInner(array('mm'=>'main_menu'),"pt.parentpage_id = mm.id",array('menu'=>'mm.menuName'))
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
                if($key=='menu'){
                    $searchQuery .= "mm.menuName like '%" . $val . "%' AND ";

                }else{
                    $searchQuery .= "pt." . $key . " like '%" . $val . "%' AND ";

                }
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'pagetab';
        $tableFields = array(
            'action' => 'Action',
            'tabcode' => 'Code',
            'tabname' => 'Name',
            'menu' => 'Parent Page',

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

    public function getpagetab($id)
    {
        $linecate = $this->select()
                         ->setIntegrityCheck(false)
                         ->where('pagetab_id='.$id);
        return $this->fetchRow($linecate)->toArray();
    }

    public function getallpagetab()
    {
        $linec = $this->select()
                      ->setIntegrityCheck(false)
                      ->where('isactive="Y"');
        return $this->fetchAll($linec)->toArray();
    }

}
?>