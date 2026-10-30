<?php
class Default_Model_User extends Zend_Db_Table_Abstract{
    protected $_name = 'main_users';
    protected $_primary = 'id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->order("$by $sort")
            ->where('isactive="Y"')
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
                $searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'user';
        $tableFields = array(
            'action' => 'Action',

            'attrib_name_1' => 'Alias_Name_1',
            'attrib_name_2' => 'Alias_Name_2',
            'attrib_name_3' => 'Alias_Name_3',
            'attrib_name_N' => 'Alias_Name_N',
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
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'call' => $call,
            'dashboardcall' => $dashboardcall
        );
        return $dataTmp;
    }

    public function SaveorUpdateData($data, $where)
	{
        try {
            if($where != ''){
                $this->update($data, $where);
                return 'update';
            } else {
                $this->insert($data);
                $id=$this->getAdapter()->lastInsertId($this->_name);
                return $id;
            }
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());die;
        }
		
	}
	public function getsingleuser($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(array('u'=>'main_users'), array('u.*'))
		->where('u.id="'.$id.'" AND u.isactive = 1');
		return $this->fetchAll($select)->toArray();

	}
}
?>