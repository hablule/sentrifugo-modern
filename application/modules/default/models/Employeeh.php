<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
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

class Default_Model_Employeeh extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_employees';
    protected $_primary = 'id';

    public function getEmpData($sort2, $by2, $perPage2, $pageNo2, $searchData2, $call2, $dashboardcall2,$a='', $b='',$c='',$d='',$managerid='',$loginUserId,$limit,$offset,$search_val,$search_str,$role_id,$empids,$perm,$searchQuery2)
    {
        $auth = Zend_Auth::getInstance();
                $request = Zend_Controller_Front::getInstance();
                if ($auth->hasIdentity()) {
                    $loginUserGroup = $auth->getStorage()->read()->group_id;
                    $loginUserRole = $auth->getStorage()->read()->emprole;
                }
                $controllerName = $request->getRequest()->getControllerName();
                //the below code is used to get data of employees from summary table.
                $employeesData = "";
            if($empids!=''){
                if ($controllerName == 'employee' && ($loginUserRole == SUPERADMINROLE || $loginUserGroup == HR_GROUP || $loginUserGroup == MANAGEMENT_GROUP))
                    $where = "  e.isactive != 5 AND e.id in ( " . $empids . ") ";
                else
                    $where = " e.id in (" . $empids . ") ";
        }
        else{
            if ($controllerName == 'employee' && ($loginUserRole == SUPERADMINROLE || $loginUserGroup == HR_GROUP || $loginUserGroup == MANAGEMENT_GROUP))
                    $where = "  e.isactive != 5 ";
                else
                    $where = " e.id !=" . $loginUserId . " ";
        }
        if ($searchQuery2) {
            $where .= " AND " . $searchQuery2;

        }



        $EmpData = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'main_employees'), array('e.*','date_of_joining' => 'DATE_FORMAT(e.date_of_joining,"'.DATEFORMAT_MYSQL.'")'))
            ->joinLeft(array('u' => 'main_users'), 'e.id=u.empid', array('user_id' => 'u.id'))
            ->joinLeft(array('ur' => 'main_userrole'), 'u.id=ur.user_id', array('userrole_id' => 'ur.userrole_id'))
            ->joinLeft(array('r' => 'main_role'), 'ur.role_id=r.id', array('role' => 'GROUP_CONCAT(r.name)'))
            ->where($where)
            ->group("e.id")

            ->order("$by2 $sort2")
            ->limitPage($pageNo2, $perPage2);
            // die($EmpData);
        return $EmpData;
    }
    public function getGrid($sort2, $by2, $perPage2, $pageNo2, $searchData2, $call2, $dashboardcall2,$a='', $b='',$c='',$d='',$managerid='',$loginUserId,$limit,$offset,$search_val,$search_str,$role_id,$empids,$perm)
    {
        $searchQuery2 = '';
        $searchArray2 = array();
        $data = array();
        $db = Zend_Db_Table::getDefaultAdapter();

        if ($searchData2 != '' && $searchData2 != 'undefined') {
            $searchValues = json_decode($searchData2);
            foreach ($searchValues as $key => $val) {
                if ($key == 'name'||$key == 'code'||$key == 'category'||$key == 'description'||$key == 'start'||$key == 'end'||$key == 'type'|| $key == 'isactive'){

                }
                else if ($key == 'userfullname')
                    $searchQuery2 .= " e." . $key . " like '%" . $val . "%' AND ";
                else if ($key == 'pos_name')
                    $searchQuery2 .= " mp." . $key . " like '%" . $val . "%' AND ";
                else if ($key == 'emailaddress')
                    $searchQuery2 .= " e.emailaddress like '%" . $val . "%' AND ";
                else if ($key == 'backgroundchk_status')
                    $searchQuery2 .= " e.backgroundchk_status like '%" . $val . "%' AND ";
                else if ($key == 'role')
                    $searchQuery2 .= " r.name like '%" . $val . "%' AND ";
                    else if ($key == 'date_of_joining')
                    $searchQuery2 .= " e.date_of_joining like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                 else	
                    $searchQuery2 .= " e.".$key." like '%".$val."%' AND ";
                $searchArray2[$key] = $val;
               // die($searchQuery2);

            }

            $searchQuery2 = rtrim($searchQuery2, " AND");
        }
        $objName = 'employee';
        $objId = 'employee';
        $tableFields = array(
            'action' => 'Action',
            'userfullname' => 'Full Name',
            'emailaddress' => 'Email',
            'office_number' => 'Contact',
            'role' => 'Role',
            'backgroundchk_status' => 'Background Check',
            'date_of_joining' => 'Date of Joining'
        );

        $tablecontent = $this->getEmpData($sort2, $by2, $perPage2, $pageNo2, $searchData2, $call2, $dashboardcall2,$a='', $b='',$c='',$d='',$managerid='',$loginUserId,$limit,$offset,$search_val,$search_str,$role_id,$empids,$perm,$searchQuery2);
        $dataTmp = array(
                        'sort' => $sort2,
                        'by' => $by2,
                        'pageNo' => $pageNo2,
                        'perPage' => $perPage2,
                        'tablecontent' => $tablecontent,
                        'objectname' => $objName,
                        'menuName' => 'Employees List',
                        'objectId' => $objId,
                        'extra' => array(),
                        'tableheader' => $tableFields,
                        'jsGridFnName' => 'getAjaxgridData',
                        'jsFillFnName' => '',
                        'add' => 'add',
                        'call' => $call2,
                        'perm' => $perm,
                        'dashboardcall2' => $dashboardcall2,
                        'search_filters' => array(
                        'date_of_joining' => array('type' => 'datepicker'),  
                            ),
        );
        return $dataTmp;
    }
    
}
