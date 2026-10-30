<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_Model_Tabprivilage extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_pagetabprivilege';
    protected $_primary = 'pagetabprivilage_id';
   
    /*
     * This function is used for saving and updating privileges data.
     */
    public function SaveorUpdatePrivilegesData($data, $where)
    {
        try {
            //code...
       
        if($where != '')
        {
            $this->update($data, $where);
            return 'update';
        }
        else 
        {
            $this->insert($data);
            $id=$this->getAdapter()->lastInsertId($this->_name);
            return $id;
        }
    } catch (\Throwable $th) {
die(Zend_Debug::dump($th->getMessage())) ;   }
    }
    /*
     * This function is used to get all menu items assigned to particular role id
     * @parameters
     * @role_id   = id of role
     * 
     * returns Array of menus.
     */
    public function getMenuItemsByRoleId($role_id,$groupid='')
    {                  

        if(empty($role_id)){
            $db = Zend_Db_Table::getDefaultAdapter();

            $query = "select *, if(canadd='Y','Yes','No') as addpermission,if(canedit='Y','Yes','No') as editpermission,if(candelete='Y','Yes','No') as deletepermission,if(canview='Y','Yes','No') as viewpermission, if(canupload='Y','Yes','No') as uploadattachments,if(canviewupload='Y','Yes','No') as viewattachments,isactive from main_pagetabprivilege where isactive = 'Y'";
			$select = $db->query($query)->fetchAll();

            $result = $select;  

        }
        elseif(!empty($groupid)){
            $db = Zend_Db_Table::getDefaultAdapter();

            $query = "select *, if(canadd='Y','Yes','No') as addpermission,if(canedit='Y','Yes','No') as editpermission,if(candelete='Y','Yes','No') as deletepermission,if(canview='Y','Yes','No') as viewpermission, if(canupload='Y','Yes','No') as uploadattachments,if(canviewupload='Y','Yes','No') as viewattachments ,isactive from main_pagetabprivilege where isactive = 'Y' 
            and group_id=".$groupid;
			$select = $db->query($query)->fetchAll();

            $result = $select;  

        }
        else{
            $db = Zend_Db_Table::getDefaultAdapter();

            $query = "select *, if(canadd='Y','Yes','No') as addpermission,if(canedit='Y','Yes','No') as editpermission,if(candelete='Y','Yes','No') as deletepermission,if(canview='Y','Yes','No') as viewpermission, if(canupload='Y','Yes','No') as uploadattachments,if(canviewupload='Y','Yes','No') as viewattachments,isactive from main_pagetabprivilege where isactive = 'Y' 
            and role_id=".$role_id."";
			$select = $db->query($query)->fetchAll();
            $result = $select;  

        }
        return $result;        
    }
    
	/*
		This function is to get privileges of a particular menu of particular group.
	*/
	public function getObjPrivileges($objId,$groupId = "",$role_id,$idCsv=0)
	{
       

		$privilege_arr=array();
		$db = Zend_Db_Table::getDefaultAdapter();
        if($objId !="" && $role_id != "" && $idCsv == 0)
		{
			$query = "select addpermission,editpermission,deletepermission,viewpermission,uploadattachments,viewattachments,isactive from main_privileges where isactive = 1  and object =".$objId." and role =".$role_id;
			$result = $db->query($query);

			$privilege_arr = $result->fetch();
			
			
		}
		else if($objId !="" && $role_id != "" && $idCsv == 1)
		{
			$query = "select object,addpermission,viewpermission,viewattachments,isactive from main_privileges where isactive = 1  and object in(".$objId.") and role =".$role_id;
			$result = $db->query($query); 
			$privilege_arr = $result->fetchAll();
			
			
		}
       
        return $privilege_arr;
	}
    public function getPrivilages($id)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "SELECT r.id, priv.pagetabprivilage_id , priv.group_id, r.code, priv.canviewupload, priv.canupload, priv.candelete, priv.canedit, 
        priv.canview, priv.canadd, mpt.tabcode, priv.isactive FROM main_pagetabprivilege AS priv
                      INNER JOIN main_pagetab AS mpt ON priv.pagetab_id = mpt.pagetab_id
                      INNER JOIN main_role AS r ON r.id = priv.role_id 
                      WHERE priv.isactive = 'Y' AND r.id = ".$id;
                     
        $res = $db->query($query)->fetchAll();
        return $res;
        
    }

    public function getPrivilagesById($id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->where('pagetab_id = '.$id.' AND role_id IS  NULL');
        return $this->fetchAll($select)->toArray();
    }
    public function gettabcode($id)
    { 
        $select = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('t'=>'main_pagetab'),array('tabcode'=>'t.tabcode'))
        ->joinInner(array('tp'=>'main_pagetabprivilege'),'tp.pagetab_id=t.pagetab_id',array())

        ->where('tp.isactive="Y" and t.isactive="Y" and tp.group_id='.$id.'');
return $this->fetchAll($select)->toArray();
    }
    public function gettabcodeedit($id)
    { 
        if($id==1){
            $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('t'=>'main_pagetab'),array('tabcode'=>'t.tabcode'))
            ->joinInner(array('tp'=>'main_pagetabprivilege'),'tp.pagetab_id=t.pagetab_id',array())
            ->where('tp.isactive="Y" and t.isactive="Y"  and tp.canedit="Y"');
    return $this->fetchAll($select)->toArray();
        }else{
            $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('t'=>'main_pagetab'),array('tabcode'=>'t.tabcode'))
            ->joinInner(array('tp'=>'main_pagetabprivilege'),'tp.pagetab_id=t.pagetab_id',array())
    
            ->where('tp.isactive="Y" and t.isactive="Y" and tp.role_id='.$id.' and tp.canedit="Y"');
    return $this->fetchAll($select)->toArray();
        }
       
    }
    public function gettabcodeview($id)
    { 
        if($id==1){
            $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('t'=>'main_pagetab'),array('tabcode'=>'t.tabcode'))
            ->joinInner(array('tp'=>'main_pagetabprivilege'),'tp.pagetab_id=t.pagetab_id',array())

            ->where('tp.isactive="Y" and t.isactive="Y" and tp.canview="Y"');
    return $this->fetchAll($select)->toArray();
        }else{
            $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('t'=>'main_pagetab'),array('tabcode'=>'t.tabcode'))
            ->joinInner(array('tp'=>'main_pagetabprivilege'),'tp.pagetab_id=t.pagetab_id',array())
    
            ->where('tp.isactive="Y" and t.isactive="Y" and tp.role_id='.$id.' and tp.canview="Y"');
    return $this->fetchAll($select)->toArray();
        }
       
    }
    public function deleteprivilage($id)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "DELETE FROM main_pagetabprivilege WHERE pagetabprivilage_id = ".$id;
        
        $db->query($query);
    }
}//end of class