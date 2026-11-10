<?php
class Default_Model_theme extends Zend_Db_Table_Abstract{
    protected $_name = 'main_theme';
    protected $_primary = 'theme_id';

    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;
        $bank = $this->select()
        ->setIntegrityCheck(false)
        ->from(['t' => 'main_theme'], ['theme_id' => 't.theme_id', 'name'=>'t.theme_name','font'=>'t.font_style','menustyle'=>'t.menu_style','headercolor'=>'t.header_color','sidebarcolor'=>'t.sidebar_color','sidebarfgcolor'=>'t.sidebarfg_color','tableheadercolor'=>'t.tableheader_color','tableheaderfgcolor'=>'t.tableheaderfg_color','menucolor'=>'t.menu_color','bodycolor'=>'t.body_color','tablebgcolor'=>'t.tablebg_color','tablefgcolor'=>'t.tablefg_color','isactive'=>'if(t.isactive="N","No","Yes")','isdefault'=>'if(t.isdefault="N","No","Yes")'])
        ->where($where)
        ->order("$by $sort")
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
                if($key == 'name')
                $searchQuery .= " t.theme_name like '%".$val."%' AND ";					
               else if($key == 'menucolor')
               {
                   $searchQuery .= " t.menu_color like '%".$val."%' AND ";					
               }
               else if($key == 'headercolor')
               {
                   $searchQuery .= " t.header_color like '%".$val."%' AND ";					
               }   else if($key == 'bodycolor')
               {
                   $searchQuery .= " t.body_color like '%".$val."%' AND ";					
               }   else if($key == 'tablebgcolor')
               {
                   $searchQuery .= " t.tablebg_color like '%".$val."%' AND ";					
               }
               else if($key == 'isactive')
               {
                   $searchQuery .= " t.isactive like '%".$val."%' AND ";					
               }
               else if($key == 'isdefault')
               {
                   $searchQuery .= " t.isdefault like '%".$val."%' AND ";					
               }
               else if($key == 'menudirection')
               {
                   $searchQuery .= " t.menu_style like '%".$val."%' AND ";					
               }
              
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'theme';
        $tableFields = [
            'action' => 'Action',

            'name' => 'Theam Name',
            'font' => 'Font Style',
            'menustyle' => 'Menu Style',
            'submenudirection' => 'Sub Menu Direction',
            'bodycolor' => 'Body Color',
            'menucolor' => 'Menu Color',
            'tablecolor' => 'Table Color',
            'headercolor' => 'Header Color',
            'sidebarcolor' => 'Sidebar Background Color',
            'sidebarfgcolor' => 'Sidebar Forground Color',
            'tableheadercolor' => 'Table Header BackgroundColor',
            'tableheaderfgcolor' => 'Table Header ForgroundColor',
            'isactive' => 'Active',
            'isdefault' => 'Default',


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
              'add'=>'add',
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'isactive' => [
                    'type' => 'select',
                    'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
                    'isdefault' => [
                        'type' => 'select',
                        'filter_data' => [''=>'All','Y'=>'Yes','N'=>'No']],
    ]
        ];
        return $dataTmp;
    }

    public function SaveorUpdateData($data, $where)
	{
            if($where != ''){

if($data['isdefault']=="Y"){
$check=$this->getdefaulttheme();
if(empty($check)){
    // if($where != ''){
    //     $this->update($data, $where);
    //     return 'update';
    // } else {
    //     $this->insert($data);
    //     $id=$this->getAdapter()->lastInsertId($this->_name);
    //     return $id;
    // }
    
}
else{
    $def=$this->getdefaulttheme();
$id=$def[0]['theme_id'];
$datab = [
'isdefault'=>'N',
];
$this->update($datab, $id);
$this->update($data, $where);
return 'update';
}
}else{
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
else{
    $this->insert($data);
        $id=$this->getAdapter()->lastInsertId($this->_name);
        return $id;
}  
	}
    public function getsingleData($id)
	{
	
		$select = $this->select()
						->setIntegrityCheck(false)
                        ->from(['t' => 'main_theme'], ['theme_id' => 't.theme_id', 'name'=>'t.theme_name','menustyle'=>'t.menu_style','font'=>'t.font_style','headercolor'=>'t.header_color','menucolor'=>'t.menu_color','menufgcolor'=>'t.menufg_color','menuhocolor'=>'t.menuho_color','bodycolor'=>'t.body_color','tablebgcolor'=>'t.tablebg_color','tablefgcolor'=>'t.tablefg_color','tableheadercolor'=>'t.tableheader_color','tableheaderfgcolor'=>'t.tableheaderfg_color','sidebarcolor'=>'t.sidebar_color','sidebarfgcolor'=>'t.sidebarfg_color','sidebarhocolor'=>'t.sidebarho_color','isactivecheck'=>'t.isactive','isdefaultcheck'=>'t.isdefault','menuorentation'=>'t.menu_orentation'])
						->where('t.theme_id='.$id.'');
		return $this->fetchAll($select)->toArray();
	}
    public function getactivetheme(){
        $query = $this->select()
                   ->setIntegrityCheck(false)
                   ->from(['t'=>'main_theme'])
                   ->where('t.isactive="Y"');
       return $this->fetchAll($query)->toArray();
    }
    public function getdefaulttheme(){
        $query = $this->select()
                   ->setIntegrityCheck(false)
                   ->from(['t'=>'main_theme'])
                   ->where('t.isactive="Y" and t.isdefault="Y"');
       return $this->fetchAll($query)->toArray();
    }
}
?>