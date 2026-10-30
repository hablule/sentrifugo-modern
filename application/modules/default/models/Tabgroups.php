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

class Default_Model_Tabgroups extends Zend_Db_Table_Abstract
{
	protected $_name = GROUP_ARRAY['tablename'];
	protected $_primary = GROUP_ARRAY['colid'];

	/**
	 * This function is used to save/update data in database.
	 * @parameters
	 * @param data  =  array of form data.
	 * @param where =  where condition in case of update.
	 *
	 * @return  Primary id when new record inserted,'update' string when a record updated.
	 */
	
    public function getData($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";

        if ($searchQuery)
            $where .= " AND " . $searchQuery;


        $bank = $this->select()
            ->setIntegrityCheck(false)
            ->from('main_group',array('name as bname','code as code','description as description','if(isactive="Y","Yes","No") as isactive','group_id as id'))
            ->order("$by $sort")
            ->where($where)
            ->limitPage($pageNo, $perPage);
       
        return $bank;
    }
    public function getgroups($id)
    {
        $linet = $this->select()
                      ->setIntegrityCheck(false)
                      ->where('group_id='.$id);
                    
        return $this->fetchRow($linet)->toArray();
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
        $objName = 'tabgroups';
        $tableFields = array(
            'action' => 'Action',
            'bname' => ' Group Name',
			'code' => 'Group Code',
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
	public function SaveorUpdateGroupsData($data, $where)
	{
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
	}

	/**
	 * This function is used to get all groups for any dropdown list.
	 *
	 * @return Array of group names and ids.
	 */
	public function getGroupsListForRoles()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select g.".GROUP_ARRAY['colid'].",g.".GROUP_ARRAY['colname'].",(select count(*) from ".ROLE_ARRAY['tablename']." r where r.".ROLE_ARRAY['colisactive']." ='Y' and r.".ROLE_ARRAY['colgroupid']." = g.".GROUP_ARRAY['colid'].") cnt
                   from ".GROUP_ARRAY['tablename']." g where g.".GROUP_ARRAY['colisactive']." = 'Y'";
		$result = $db->query($query);
		$group_arr = array();
		while($row = $result->fetch())
		{
			$group_arr[$row['group_id']]['name'] = $row['name'];
			$group_arr[$row['group_id']]['cnt'] = $row['cnt'];
		}

		return $group_arr;
	}
	public function getGroupsListForRolesview()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select g.".GROUP_ARRAY['colid']." as id,g.".GROUP_ARRAY['colname']." as group_name,(select count(*) from ".ROLE_ARRAY['tablename']." r where r.".ROLE_ARRAY['colisactive']." ='Y' and r.".ROLE_ARRAY['colgroupid']." = g.group_id) cnt
                   from ".GROUP_ARRAY['tablename']." g where g.".GROUP_ARRAY['colisactive']." = 'Y'";
		$result = $db->query($query);
		$group_arr = array();
		while($row = $result->fetch())
		{
			$group_arr[$row['id']]['group_name'] = $row['group_name'];
			$group_arr[$row['id']]['cnt'] = $row['cnt'];
		}

		return $group_arr;
	}
	/**
	 * This function is used to get all groups for any dropdown list.
	 *
	 * @return Array of group names and ids.
	 */
	public function getGroupsListForUserLoginLog()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select g.".GROUP_ARRAY['colid'].",g.".GROUP_ARRAY['colname'].",(select count(*) from main_userloginlog where group_id = g.group_id) cnt
                   from ".GROUP_ARRAY['tablename']." g where g.".GROUP_ARRAY['colisactive']." = 'Y'";
		$result = $db->query($query);
		$group_arr = array();
		while($row = $result->fetch())
		{
			$group_arr[$row['group_id']]['name'] = $row['name'];
			$group_arr[$row['group_id']]['cnt'] = $row['cnt'];
		}

		return $group_arr;
	}
	/**
	 * This function will fetch all data of a particular group id.
	 * @param Integer $group_id = id of group.
	 * @return Array of group data.
	 */
	public function getGroupDataById($group_id)
	{
            if($group_id != '')
            {
		$data = $this->fetchRow("group_id = ".$group_id)->toArray();
		return $data;
            }
            else 
                return array();
	}

	/**
	 * This function is used to get all group for any dropdown list.
	 *
	 * @return Array array of group names and ids.
	 */
	public function getGroupList()
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$query = "select group_id,name from ".GROUP_ARRAY['tablename']." where ".GROUP_ARRAY['colisactive']." = 'Y' order by ".GROUP_ARRAY['colname']."";
		$result = $db->query($query);
		$group_arr = array();
		while($row = $result->fetch())
		{
			$group_arr[$row['group_id']] = $row['name'];
		}

		return $group_arr;
	}

	/**
	 * This function is used to get all groupnames by passing id's.
	 *
	 * @return Array array of group names and ids.
	 */
	public function getGroupNamesByIds($groupIdArray)
	{
		$resultstring = implode(',', $groupIdArray);
		$groupArray = array();
		if($resultstring)
		{
			try
			{
				$qry = "select ob.group_id, ob.name from ".GROUP_ARRAY['tablename']." ob
                                        where ob.".GROUP_ARRAY['colid']." IN (".$resultstring.") and ob.".GROUP_ARRAY['colisactive']." = 'Y'";		
				$db = Zend_Db_Table::getDefaultAdapter();
				$sqlRes = $db->query($qry);
				$groupRes = $sqlRes->fetchAll();

				if(!empty($groupRes))
				{
					foreach($groupRes as $group)
					{
						$groupArray[$group['group_id']]= $group['name'];
					}
				}
			}
			catch(Exception $e)
			{
				echo "Error Encountered - ".$e->getFile();
			}
		}
		return $groupArray;
	}

}//end of class