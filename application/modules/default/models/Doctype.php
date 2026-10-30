<?php
class Default_Model_Doctype extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_doctype';
    protected $_primary = 'doctype_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";
        if ($searchQuery) {
            $where .= " AND " . $searchQuery;
        }
        $roleData = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('dt' => 'main_doctype'),
                array(
                    'doctype_id' => 'dt.doctype_id',
                    'name' => 'dt.name',
                    'description' => 'dt.description',
                    'isactive' => 'if(dt.isactive="N","No","Yes")'
                )
            )
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
        // die($roleData);
        return $roleData;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = array();
        $data = array();
        
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($searchData != '' && $searchData != 'undefined') {
            
            $searchValues = json_decode($searchData);
         
            if (count($searchValues) > 0) {
                foreach ($searchValues as $key => $val) {
if($key=='name'){
    $searchQuery .= "dt.name like '%". $val."%' AND ";
}
else if ($key=='description') {
    $searchQuery .= "dt.description like '%". $val."%' AND ";
}
else if ($key=='isactive') {
    $searchQuery .= "dt.isactive like '%". $val."%' AND ";
}
else{
$searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
}
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
            }
        }
        $objName = 'doctype';
        $tableFields = array(
            'action' => 'Action',
            'name' => 'Document Type',
            'description' => 'Description',
            'isactive'=>'Active',

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
            'add' => 'add',

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
        if ($where != '') {
            $this->update($data, $where);
            return 'update';
        } else {
            $this->insert($data);
            $id = $this->getAdapter()->lastInsertId($this->_name);
            return $id;
        }
    }

    public function getSingleDoctype($id)
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('d'=>'main_doctype'))
            ->where("d.doctype_id=".$id."");
            return $this->fetchAll($query)->toArray();
    }

    public function getDoctypes(){
        $query = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('d'=>'main_doctype'))
        ->where("d.isactive = 'Y'");
        
        return $this->fetchAll($query)->toArray();
    }
}
