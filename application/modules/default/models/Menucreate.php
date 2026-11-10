<?php
class Default_Model_Menucreate extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_menu';
    protected $_primary = 'id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(['mm' => 'main_menu'], ['id' => 'mm.id', 'menuName' => 'mm.menuName', 'url' => 'mm.url', 'modulename' => 'mm.modulename','isactive'=>'if(mm.isactive="0","No","Yes")'])
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
           // die($bank);
        return $bank;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $a = '', $b = '', $c = '', $d = '')
    {
        $searchQuery = '';
        $searchArray = [];
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
           
            foreach ($searchValues as $key => $val) {
                $searchQuery .= "" . $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
               
                
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'menucreate';
        $tableFields = [
            'action' => 'Action',
            'menuName' => 'Menu name',
            'url' => 'URL',
            'modulename' => 'Module name',
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
            'call' => $call,
            'add' =>'add',

            'dashboardcall' => $dashboardcall,
            'search_filters'=>[
                'isactive'=>[
                    'type'=>'select',
                    'filter_data'=>[''=>'All',1=>'Yes',0=>'No']
                ]
                ],
           
        ];
        return $dataTmp;
    }

    public function SaveorUpdateData($data, $where,$permission_data,$permission_where,$group_data,$group_where)
    {
        $config = [
            'name' => 'main_privileges',
            'primary' => 'id'
        ];
        $privileges_table = new Zend_Db_Table($config);
        if ($where != '' && $permission_where !='') {
            
            $this->update($data, $where);
            $privileges_table->update($permission_data,$permission_where);
            $privileges_table->update($group_data,$group_where);

            $id =  'update';
        } else {
            $this->insert($data);
           
            $id = $this->getAdapter()->lastInsertId($this->_name);
             $permission_data['object'] = $id;
             $privileges_table->insert($group_data);

            $privileges_table->insert($permission_data);

            $temp_data ['nav_ids'] = ','.$id.',';
            $this->update($temp_data,"id=".$id);
            
        }
       
        if($data['parent']!=0){
            
            if($id=="update"){
               $temp_id = substr($where,strpos($where,'=')+1);
               
               
            }else{
                $temp_id = $id;
            }

            $temp_data ['nav_ids'] = ','. $data['parent'] .','.$temp_id.',';
  
            $this->update($temp_data,"id=".$temp_id);
        }
        return $id;
    }

    public function getAllMenus()
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['mm' => 'main_menu'], ['mm.id', 'mm.menuName'])
            ->where('isactive=1');
        return $this->fetchAll($query)->toArray();

    }
    public function getroles()
    {
        $query = $this->select()
            ->setIntegrityCheck(false)
            ->from(['mr' => ROLE_ARRAY['tablename']], ['mr.'.ROLE_ARRAY['colid'], 'mr.'.ROLE_ARRAY['colname']])
            ->where('mr.'.ROLE_ARRAY['colisactive'].'="Y"');
        return $this->fetchAll($query)->toArray();

    }
    public function getmenubyid($id)
    {
        $query = $this->select()
                      ->setIntegrityCheck(false)
                      ->where('id=?',$id);
        return $this->fetchRow($query)->toArray();
    }
    public function getmenupriv($menuid)
    {
        $query = $this->select()
                      ->setIntegrityCheck(false)
                      ->from('main_privileges')
                      ->where('object=?',$menuid);
        return $this->fetchRow($query)->toArray();
    }
    public function DeleteData($data, $where)
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
}
