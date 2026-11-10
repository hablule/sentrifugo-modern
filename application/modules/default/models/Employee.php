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

class Default_Model_Employee extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_employees';
    protected $_primary = 'id';

    /*
	   I. This query fetches employees data based on roles.
	   II. If roles are not configured then to eliminate users and other vendors we are using jobtitle clause.
	       As for jobtitle id for vendors and users will always be null.
	*/
    public function getEmployeesData($sort, $by, $pageNo, $perPage, $searchQuery, $managerid = '', $loginUserId = null)
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
        if ($controllerName == 'employee' && ($loginUserRole == SUPERADMINROLE || $loginUserGroup == HR_GROUP || $loginUserGroup == MANAGEMENT_GROUP))
            $where = "  e.isactive != 5 AND e.id != " . $loginUserId . " ";
        else
            $where = "  e.isactive = 1 AND e.id != " . $loginUserId . " ";

        if ($managerid != '')
            // $where .= " AND e.reporting_manager = " . $managerid . " ";
            if ($searchQuery != '')
                $where .= " AND " . $searchQuery;

        $employeesData = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_employees'], ['e.*', 'id' => 'e.id', 'extn' => new Zend_Db_Expr('case when e.extension_number is not null then concat(e.office_number," (ext ",e.extension_number,")") when e.extension_number is null then e.office_number end'), 'astatus' => new Zend_Db_Expr('case when e.isactive = 0 then "Inactive" when e.isactive = 1 then "Active" when e.isactive = 2 then "Resigned"  when e.isactive = 3 then "Left" when e.isactive = 4 then "Suspended" end')])
            ->joinLeft(['u' => 'main_users'], 'e.id=u.empid', ['user_id' => 'u.id'])
            ->joinLeft(['ur' => 'main_userrole'], 'u.id=ur.user_id', ['userrole_id' => 'ur.userrole_id'])
            ->joinLeft(['r' => 'main_role'], 'ur.role_id=r.id', ['role' => 'GROUP_CONCAT(r.name)'])
            ->where($where)
            ->group("e.id")

            ->order("$by $sort")

            ->limitPage($pageNo, $perPage);



        return $employeesData;
    }

    public function getEmployees($managerid = '', $loginUserId = null, $limit = null, $offset = null, $search_val = null, $search_str = null, $role_id = null, $empids = '')
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
        if ($empids != '') {
            if ($controllerName == 'employee' && ($loginUserRole == SUPERADMINROLE || $loginUserGroup == HR_GROUP || $loginUserGroup == MANAGEMENT_GROUP))
                $where = "  e.isactive != 5 AND e.id in ( " . $empids . ") ";
            else
                $where = " e.id in (" . $empids . ") ";
        } else {
            if ($controllerName == 'employee' && ($loginUserRole == SUPERADMINROLE || $loginUserGroup == HR_GROUP || $loginUserGroup == MANAGEMENT_GROUP))
                $where = "  e.isactive != 5 AND e.id !=  " . $loginUserId . " ";
            else {
                $empids = 0;
                $where = " e.id in (" . $empids . ") ";
            }
            //  $where = " e.id !=" . $loginUserId . " ";
        }
        if ($search_val != '') {
            if ($search_val == 'emp_name') {
                $search_str_l = ltrim($search_str);
                $search_str_r = rtrim($search_str_l);
                $where .= " AND e.userfullname like '%" . $search_str_r . "%' ";
            } else if ($search_val == 'emp_id') {
                $where .= " AND e.id = " . $search_str . " ";
            } else {
                $where .= " AND ur.role_id = '" . $role_id . "' ";
            }
        }

        $employeesData = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_employees'], ['e.*', 'id' => 'e.id', 'extn' => new Zend_Db_Expr('case when e.extension_number is not null then concat(e.office_number," (ext ",e.extension_number,")") when e.extension_number is null then e.office_number end'), 'astatus' => new Zend_Db_Expr('case when e.isactive = 0 then "Inactive" when e.isactive = 1 then "Active" when e.isactive = 2 then "Resigned"  when e.isactive = 3 then "Left" when e.isactive = 4 then "Suspended" end')])
            ->joinLeft(['u' => 'main_users'], 'e.id=u.empid', ['u.*', 'user_id' => 'u.id'])
            ->joinLeft(['ur' => 'main_userrole'], 'u.id=ur.user_id', ['userrole_id' => 'ur.userrole_id'])
            ->joinLeft(['r' => 'main_role'], 'ur.role_id=r.id', ['role' => 'GROUP_CONCAT(r.name)'])
            ->where($where)
            ->limit($limit, $offset)
            ->group("e.id")

            ->order('e.userfullname asc');
        //die($employeesData);
        // Zend_Debug::dump($where);
        //Zend_Debug::dump($employeesData);
        // die();
        return $this->fetchAll($employeesData)->toArray();
    }
    
    public function getUserRole()
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        $usersData = $db->query("select GROUP_CONCAT(" . ROLE_ARRAY['colid'] . ") as roles from " . ROLE_ARRAY['tablename'] . " As r Inner join " . GROUP_ARRAY['tablename'] . " As g  on r." . ROLE_ARRAY['colgroupid'] . "=g." . GROUP_ARRAY['colid'] . "  
        where r." . ROLE_ARRAY['colisactive'] . "='Y' AND g.group_id IN(" . MANAGER_GROUP . "," . HR_GROUP . "," . EMPLOYEE_GROUP . "," . SYSTEMADMIN_GROUP . "," . MANAGEMENT_GROUP . ")");

        $usersResult = $usersData->fetchAll();

        return $usersResult;
    }

    public function userAccess($loginid)
    {

        $db = Zend_Db_Table::getDefaultAdapter();
        $empData = $db->query(" SELECT 
                                    ra.*,
                                    rd.*,
                                    mu.empid as loginempid,
                                    CASE
                                        WHEN ra.accesslevel = 'ALL' THEN 1
                                        WHEN ra.accesslevel = 'GROUP' THEN 2
                                        WHEN ra.accesslevel = 'CHAIN' THEN 3
                                        WHEN ra.accesslevel = 'LINE' THEN 4
                                        WHEN ra.accesslevel = 'INDIVIDUAL' THEN 5
                                        ELSE 6
                                    END AS ax,ra.accesslevel 
                                FROM
                                    main_emprecordaccess ra
                                        LEFT JOIN
                                    main_emprecordacessdetail rd ON ra.emprecordaccess_id = rd.emprecordaccess_id
                                    left join main_users mu on ra.user_id = mu.id
                                WHERE
                                    ra.user_id = " . $loginid . " AND ( rd.isactive = 'Y'
                                        OR ra.isactive = 'Y')
                                ORDER BY ax asc, ra.readwrite DESC ");

        $res = $empData->fetchAll();      

        if (isset($res) && !empty($res)) {
            return $res;
        } else
            return 'norows';
    }


    public function getempchain($empid)
    {
        $empid = 1;
        $db = Zend_Db_Table::getDefaultAdapter();

        $select = $db->query("
        
        WITH RECURSIVE cte (employee_id) AS (
            SELECT     employee_id
                       
            FROM       main_empposition
            WHERE      reportto_id = " . $empid . " and isactive='Y'
            UNION ALL
            SELECT     p.employee_id
                       
            FROM       main_empposition p
            INNER JOIN cte
                    ON p.reportto_id = cte.employee_id
          )
          SELECT * FROM cte;
        
        
        ");

        $res = $select->fetchAll();
        return $res;
    }


    public function getempgroup($userid)
    {

        try {
            //code...

            $company_id = '';
            $sbunit_id = '';
            $division_id = '';
            $department_id = '';
            $section_id = '';
            $project_id = '';
            $team_id = '';

            $db = Zend_Db_Table::getDefaultAdapter();

            $select = $db->query("
        SELECT 
        ep.*   
       FROM
        main_empposition ep
         where ep.employee_id=" . $userid . " and ep.isactive='Y';
        ");


            $res = $select->fetchAll();

            foreach ($res  as $value) {
                $company_id .= $value['company_id'] . ',';
                $sbunit_id .= $value['sbunit_id'] . ',';
                $division_id .= $value['division_id'] . ',';
                $department_id .= $value['department_id'] . ',';
                $section_id .= $value['section_id'] . ',';
                $project_id .= $value['project_id'] . ',';
                $team_id .= $value['team_id'] . ',';
            }
            $company_id = trim($company_id, ',');
            $sbunit_id = trim($sbunit_id, ',');
            $division_id = trim($division_id, ',');
            $department_id = trim($department_id, ',');
            $section_id = trim($section_id, ',');
            $project_id = trim($project_id, ',');
            $team_id = trim($team_id, ',');
            $select->closeCursor();
            // $db2 = Zend_Db_Table::getDefaultAdapter();
            $where = "";
            if (!empty($company_id))
                $where .= "ep.company_id in (" . $company_id . ") or ";
            if (!empty($sbunit_id))
                $where .= "ep.sbunit_id in (" . $sbunit_id . ") or ";
            if (!empty($division_id))
                $where .= " ep.division_id in (" . $division_id . ") or";
            if (!empty($department_id))
                $where .= "ep.department_id in (" . $department_id . ") or ";
            if (!empty($section_id))
                $where .= " ep.section_id in (" . $section_id . ") or";
            if (!empty($project_id))
                $where .= "ep.project_id in (" . $project_id . ") or";
            if (!empty($team_id))
                $where .= "ep.team_id in (" . $team_id . ") or ";

            $where = trim($where, 'or ');
            if ($where != '') {

                $where = 'where ' . $where;
            }

            $query = "
             SELECT 
             ep.employee_id    
             FROM 
             main_empposition ep " . $where . "";
            $query = trim($query, ' or');

            $select2 = $db->query($query);


            $res2 = $select2->fetchAll();

            return $res2;
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
            die("popop");
        }
    }
    
    public function getempwithoutchain($empid, $type)
    {

        try {
            //code...


            if ($type == 'ALL') {
                $where = "and 1=1";
            } else if ($type == 'SELF' || $type == 'INDIVIDUAL') {
                $where = "and employee_id=" . $empid;
            } else if ($type == 'LINE') {
                $where = "and reportto_id=" . $empid;
            }

            $db = Zend_Db_Table::getDefaultAdapter();

            $select = $db->query("
       
        SELECT     employee_id        
        FROM       main_empposition
        WHERE      isactive='Y' " . $where . " 
        
        ");

            $res = $select->fetchAll();
            return $res;
        } catch (\Throwable $th) {
            Zend_Debug::dump($th->getMessage());
            die("kk");
        }
    }
    
    /**
     * This function gives full employee details based on user id.
     * @param integer $id  = id of employee
     * @return array  Array of employee details.
     */
    public function getsingleEmployeeData($id)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $empData = $db->query("SELECT e.*,r.id as emprole,mb.branch as branch,p.name as position,
                                        jt.name as jobtitle,emp_branch_id as branch_id,el.userfullname as reporting_manager,t.name,
                                        GROUP_CONCAT(Distinct(r.name)) AS role        
                                FROM  main_employees AS e
                                        LEFT JOIN main_users AS u ON e.id = u.empid
                                        LEFT JOIN main_userrole AS ur ON u.id = ur.user_id
                                        left join main_empposition ep on ep.employee_id=e.id
                                        left JOIN main_title t ON e.title_id = t.id
                                        left JOIN main_emp_branch mb ON e.branch = mb.emp_branch_id
                                        LEFT join main_employees el on ep.reportto_id=el.id
                                        LEFT JOIN main_position p ON ep.position_id=p.id
                                        LEFT JOIN main_jobtitle jt ON ep.jobtitle_id=jt.id
                                        LEFT JOIN tbl_employmentstatus es ON e.emp_status_id=es.id
                                        LEFT JOIN main_role AS r ON ur.role_id = r.id
                                WHERE e.id = ". $id ." AND e.isactive IN (1,2,3,4,0) ");

        // $empData = $db->query("SELECT e.*,u.*,p.prefix,t.name,p.isactive as active_prefix FROM main_employees e 
        //         INNER JOIN main_users u ON e.user_id = u.id left JOIN main_prefix p ON e.prefix_id = p.id left JOIN main_title t ON e.title_id = t.id
        //                    WHERE e.user_id = " . $id . "   AND  u.isactive IN (1,2,3,4,0) AND u.userstatus ='old'");
        //   die($empData);

        $res = $empData->fetchAll();
        if (isset($res) && !empty($res)) {
            return $res;
        } else
            return 'norows';
    }

    /**
     * This function is used to get data in employees report.
     * @param array $param_arr   = array of parameters.
     * @param integer $per_page  = no.of records per page
     * @param integer $page_no   = page number
     * @param string $sort_name  = name of the column to be sort
     * @param string $sort_type  = descending or ascending
     * @return array  Array of all employees.
     */
    public function getdata_emp_report($param_arr, $per_page, $page_no, $sort_name, $sort_type)
    {
        // die($per_page);
        $search_str = " e.isactive != 5 ";

        foreach ($param_arr as $key => $value) {
            if ($value != '') {
                if ($key == 'date_of_joining')
                    $search_str .= " and e." . $key . " = '" . sapp_Global::change_date($value, 'database') . "'";
                if ($key == 'jobtitle_id')
                    $search_str .= " and jt." . $key . " = '" . $value . "'";
                if ($key == 'position_id')
                    $search_str .= " and p." . $key . " = '" . $value . "'";
                if ($key == 'emp_status_id')
                    $search_str .= " and e." . $key . " = '" . $value . "'";
                if ($key == 'reporting_manager')
                    $search_str .= " and el.id = '" . $value . "'";
                if ($key == 'userfullname')
                    $search_str .= " and e.id = '" . $value . "'";
                if ($key == 'modeofentry')
                    $search_str .= " and e." . $key . " = '" . $value . "'";

                if ($key == 'emprole') {
                    $key = 'role_id'; 
                    $search_str .= " and r.id = '" . $value . "'";
                }
            }
        }

        //Zend_Debug::dump($search_str);
        $offset = ($per_page * $page_no) - $per_page;
        $db = Zend_Db_Table::getDefaultAdapter();
        $limit_str = " limit " . $per_page . " offset " . $offset;
        $access = Zend_Session::namespaceGet('access_control');
        $empids = $access['empids'];
        $count_query = "SELECT count(Distinct(e.id)) cnt 
                        FROM main_employees AS e
                                LEFT JOIN main_users AS u ON e.id = u.empid
                                LEFT JOIN main_userrole AS ur ON u.id = ur.user_id
                                LEFT join main_empposition ep on ep.employee_id=e.id
                                LEFT join main_employees el on ep.reportto_id=el.id
                                LEFT JOIN main_position p ON ep.position_id=p.id
                                LEFT JOIN main_jobtitle jt ON ep.jobtitle_id=jt.id
                                LEFT JOIN tbl_employmentstatus es ON e.emp_status_id=es.id
                                LEFT JOIN  main_role AS r ON ur.role_id = r.id
                        WHERE e.id in (" . $empids . ") AND " . $search_str;

        $count_result = $db->query($count_query);
        $count_row = $count_result->fetch();
        $count = $count_row['cnt'];
        $page_cnt = ceil($count / $per_page);

        $query = "  SELECT 
                        e.*,
                        e.id,
                        r.id,
                        el.userfullname as reporting_manager,
                        p.name as position,
                        jt.name as jobtitle,
                        es.employemnt_status as employemnt_status,        
                        CASE
                            WHEN e.extension_number IS NOT NULL THEN CONCAT(e.office_number, ' (ext ', e.extension_number,')')
                            WHEN e.extension_number IS NULL THEN e.office_number
                        END AS extn,
                        CASE 
                            WHEN e.isactive = 0 THEN 'Inactive'
                            WHEN e.isactive = 1 THEN 'Active'
                            WHEN e.isactive = 2 THEN 'Resigned'
                            WHEN e.isactive = 3 THEN 'Left'
                            WHEN e.isactive = 4 THEN 'Suspended'
                        END AS astatus, 
                        u.id AS user_id, 
                        ur.userrole_id, 
                        GROUP_CONCAT(Distinct(r.name)) AS role 
                    FROM
                        main_employees AS e 
                        LEFT JOIN main_users AS u ON e.id = u.empid 
                        LEFT JOIN main_userrole AS ur ON u.id = ur.user_id 
                        left join main_empposition ep on ep.employee_id=e.id 
                        LEFT join main_employees el on ep.reportto_id=el.id 
                        LEFT JOIN main_position p ON ep.position_id=p.id 
                        LEFT JOIN main_jobtitle jt ON ep.jobtitle_id=jt.id 
                        LEFT JOIN tbl_employmentstatus es ON e.emp_status_id=es.id 
                        LEFT JOIN main_role AS r ON ur.role_id = r.id 
                    WHERE e.id IN (" . $empids . ") AND " . $search_str . " 
                    GROUP BY e.id 
                    ORDER BY " . $sort_name . " " . $sort_type . " " . $limit_str;

        //die($query);//Zend_Debug::dump($query);
        //    Zend_Debug::dump($query);

        $result = $db->query($query);
        $rows = $result->fetchAll();
        return ['rows' => $rows, 'page_cnt' => $page_cnt, 'count_emp' => $count];
    }

    /**
     * This function is used to get data for pop up in groups,roles and employees report
     * @param Integer $group_id    = id of the group
     * @param Integer $role_id     = id of the role
     * @param Integer $page_no     = page number
     * @param String $sort_name    = field name to be sort
     * @param String $sort_type    = sort type like asc,desc
     * @return Array Array of employees of given role and group
     */
    public function emprolesgrouppopup($group_id, $role_id, $page_no, $sort_name, $sort_type, $per_page)
    {

        $offset = ($per_page * $page_no) - $per_page;
        $db = Zend_Db_Table::getDefaultAdapter();
        $limit_str = " limit " . $per_page . " offset " . $offset;
        if ($group_id == USERS_GROUP) {
            if ($role_id != '') {
                $role_str = " and role_id in (" . $role_id . ")";
            } else {
                $role_str = " and role_id in (select id from " . ROLE_ARRAY['tablename'] . " where group_id = " . $group_id . " and isactive = 1)";
            }
            $count_query = "select count(*) cnt from main_userrole where isactive = 'Y' " . $role_str;
            $count_result = $db->query($count_query);
            Zend_Debug::dump(die($count_result));
            $count_row = $count_result->fetch();
            $count = $count_row['cnt'];
            $page_cnt = ceil($count / $per_page);
            $query = "select " . ROLE_ARRAY['colname'] . " rolename_p,u.userfullname,u.employeeId,u.emailaddress from main_users u," . ROLE_ARRAY['tablename'] . " r where " . ROLE_ARRAY['colid'] . " = u.emprole and u.isactive = 1 " . $role_str . " order by " . $sort_name . " " . $sort_type . " " . $limit_str;
            $result = $db->query($query);
            $rows = $result->fetchAll();

            return ['rows' => $rows, 'page_cnt' => $page_cnt];
        } else {
            if ($role_id != '') {
                $role_str = " and emprole in (" . $role_id . ")";
            } else {
                $role_str = " and emprole in (select id from " . ROLE_ARRAY['tablename'] . " where group_id = " . $group_id . " and isactive = 'Y')";
            }
            $count_query = "select count(*) cnt from employees_summary where isactive = 1 " . $role_str;
            $count_result = $db->query($count_query);
            $count_row = $count_result->fetch();
            $count = $count_row['cnt'];
            $page_cnt = ceil($count / $per_page);
            $query = "select * from employees_summary where isactive = 1 " . $role_str . " order by " . $sort_name . " " . $sort_type . " " . $limit_str;
            $result = $db->query($query);
            $rows = $result->fetchAll();
            return ['rows' => $rows, 'page_cnt' => $page_cnt];
        }
    }
    
    public function SaveorUpdateEmployeeData($data, $where)
    {

        try {
            //code...
            if ($where != '') {
                //die(json_encode($data+$where));

                $this->update($data, $where);
                return 'update';
            } else {
                //   die(json_encode($data+$where));

                $this->insert($data);
                $id = $this->getAdapter()->lastInsertId('main_employees');
                return $id;
            }
        } catch (\Throwable $th) {
            //throw $th;
            Zend_Debug::dump($th->getMessage());
            die();
        }
    }
    
    public function getEmpPerk($emp_id)
    {
        $result =  $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_employeeperk'], ['e.perk_id', 'e.amount'])
            ->where("e.emp_id = " . $emp_id);
        return $this->fetchAll($result)->toArray();
    }
    
    public function getEmpDeductive($emp_id)
    {
        $result =  $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_employeeload'], ['e.load_id', 'e.amount'])
            ->where("e.emp_id = " . $emp_id);
        return $this->fetchAll($result)->toArray();
    }
    
    public function getEmpPerkDetail($emp_id)
    {
        $result = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_employeeperk'], ['perk_name' => 'p.name', 'perk_description' => 'p.description', 'perk_start' => 'p.start', 'perk_end' => 'p.end', 'perk_type' => 'p.type', 'perk_amount' => 'p.amount', 'perk_active' => 'p.isactive', 'category_name' => 'pc.name', 'category_description' => 'pc.description'])
            ->joinInner(['p' => 'main_perk'], 'e.perk_id=p.perk_id')
            ->joinInner(['pc' => 'main_perkcategory'], 'pc.perkcategory_id=p.category')
            ->where("e.emp_id = " . $emp_id)
            ->group('perk_name');
        //die($result);
        return $this->fetchAll($result)->toArray();
    }
    
    public function getEmpScheduleDetail($emp_id)
    {
        $result = $this->select()
            ->setIntegrityCheck(false)
            ->from(['es' => 'main_emp_schedule'], ['schedule_name' => 's.schedule_name', 'start_date' => 's.start_date', 'end_date' => 's.end_date', 'isactive' => 's.isactive'])
            ->joinInner(['s' => 'main_schedule'], 's.sch_id=es.sch_id')
            ->where("es.emp_id = " . $emp_id);
        return $this->fetchAll($result)->toArray();
    }

    public function getEmpDeductiveDetail($emp_id)
    {
        $result = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'main_employeeload'], ['deductive_name' => 'p.name', 'deductive_description' => 'p.description', 'deductive_start' => 'p.start', 'deductive_end' => 'p.end', 'deductive_type' => 'p.type', 'deductive_amount' => 'p.amount', 'deductive_active' => 'p.isactive', 'category_name' => 'pc.name', 'category_description' => 'pc.description'])
            ->joinInner(['p' => 'main_load'], 'e.load_id=p.load_id')
            ->joinInner(['pc' => 'main_loadcategory'], 'pc.load_category_id=p.load_category_id')
            ->where("e.emp_id = " . $emp_id)
            ->group('deductive_name');
        //die($result);
        return $this->fetchAll($result)->toArray();
    }

    public function getActiveEmployeeData($id)
    {

        try {

            $result =  $this->select()
                ->setIntegrityCheck(false)
                ->from(['e' => 'main_employees'], ['e.*'])
                ->joinLeft(['br' => 'main_emp_branch'], 'e.branch=br.emp_branch_id', 'br.branch as bname')
                ->where("e.isactive = 1 AND e.id = " . $id);
            // die($result);
            //die(json_encode($this->fetchAll($result)->toArray())); 
            return $this->fetchAll($result)->toArray();
        } catch (Exception $e) {
            die("beka");
            die($e->getFile());
        }
    }
    
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1 = '', $exParam2 = '', $exParam3 = '', $exParam4 = '')
    {
        $searchQuery = '';
        $tablecontent = '';
        $emptyroles = 0;
        $empstatus_opt = [];
        $searchArray = [];
        $data = [];
        $id = '';
        $dataTmp = [];

        $auth = Zend_Auth::getInstance();
        $request = Zend_Controller_Front::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserGroup = $auth->getStorage()->read()->group_id;
            $loginUserRole = $auth->getStorage()->read()->emprole;
        }
        $controllerName = $request->getRequest()->getControllerName();
        if ($controllerName == 'employee' && ($loginUserRole == SUPERADMINROLE || $loginUserGroup == HR_GROUP || $loginUserGroup == MANAGEMENT_GROUP))
            $filterArray = ['' => 'All', 1 => 'Active', 0 => 'Inactive', 2 => 'Resigned', 3 => 'Left', 4 => 'Suspended'];
        else
            $filterArray = ['' => 'All', 1 => 'Active'];

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);

            foreach ($searchValues as $key => $val) {

                if ($key == 'astatus')
                    $searchQuery .= " e.isactive like '%" . $val . "%' AND ";
                else if ($key == 'extn')
                    $searchQuery .= " concat(e.office_number,' (ext ',e.extension_number,')') like '%" . $val . "%' AND ";
                else
                    $searchQuery .= $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'employee';


        $tableFields = [
            'action' => 'Action',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'emailaddress' => 'Email',
            'employeeId' => 'Employee ID',
            'businessunit_name' => 'Business Unit',
            'department_name' => 'Department',
            'astatus' => 'User Status',
            'extn' => 'Work Phone',
            'jobtitle_name' => 'Job Title',
            'reporting_manager_name' => 'Reporting Manager',
            'contactnumber' => 'Contact Number',
            'emp_status_name' => 'Employment Status',
            'emprole_name' => "Role"
        ];

        $tablecontent = $this->getEmployeesData($sort, $by, $pageNo, $perPage, $searchQuery, '', $exParam1);

        if ($tablecontent == "emptyroles") {
            $emptyroles = 1;
        } else {
            $employmentstatusModel = new Default_Model_Employmentstatus();
            $employmentStatusData = $employmentstatusModel->getempstatuslist();

            if (count($employmentStatusData) > 0) {
                foreach ($employmentStatusData as $empsdata) {
                    $empstatus_opt[$empsdata['workcodename']] = $empsdata['statusname'];
                }
            }
        }

        $dataTmp = [
            'userid' => $id,
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
            'menuName' => 'Employees',
            'dashboardcall' => $dashboardcall,
            'add' => 'add',
            'call' => $call,
            'search_filters' => [
                'astatus' => [
                    'type' => 'select',
                    'filter_data' => $filterArray
                ],
                'emp_status_id' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All'] + $empstatus_opt
                ],
            ],
            'emptyroles' => $emptyroles
        ];

        return $dataTmp;
    }

    public function getAutoReportEmp($search_str)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "select * from ((select u.id id,u.profileimg,concat(u.userfullname,if(j.name is null,'',concat(' , ',j.name))) emp_name,
                  case when u.userfullname like '" . $search_str . "%' then 4  when u.userfullname like '__" . $search_str . "%' then 2 
                  when u.userfullname like '_" . $search_str . "%' then 3 when u.userfullname like '%" . $search_str . "%' then 1 
                  else 0 end emp 
                  from main_employees u
                  LEFT join main_empposition ep on ep.employee_id = u.id 
                  LEFT join main_jobtitle j on ep.jobtitle_id = j.jobtitle_id 
                  and j.name like '%" . $search_str . "%'  where  u.isactive =1 and j.jobtitle_id is not null 
                  and (u.userfullname like '%" . $search_str . "%' or u.emailaddress like '%" . $search_str . "%') 
                    )
                    union (
                        select u.id user_id,u.profileimg,concat(u.userfullname,', Super Admin') emp_name ,
                    case when u.userfullname like '" . $search_str . "%' then 4 when u.userfullname like '__" . $search_str . "%' then 2 when u.userfullname like '_" . $search_str . "%' then 3 
                    when u.userfullname like '%" . $search_str . "%' then 1 else 0 end emp 
                    from main_employees u where  
                    (u.userfullname like '%" . $search_str . "%' or 'Super Admin' like '%" . $search_str . "%' or u.emailaddress like '%" . $search_str . "%') )
                    ) a
                  order by emp desc
                  limit 0,10";
        $result = $db->query($query);
        $emp_arr = [];
        $emp_arr = $result->fetchAll();
        return $emp_arr;
    }

    /**
     * 
     * Show auto suggestions to user in employees reporing to manager report
     * @param $search_str
     */
    public function getAutoReportEmployee($params)
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "select u.id user_id, u.emailaddress, u.userfullname, u.profileimg,u.employeeId FROM main_users u 
        		  inner join employees_summary e on u.id=e.user_id	
        		  WHERE u." . $params['field'] . " LIKE '%" . $params['term'] . "%' AND e.reporting_manager = $loginUserId
        		  order by u.emailaddress desc limit 0,10";
        $result = $db->query($query);
        $emp_arr = [];
        $emp_arr = $result->fetchAll();
        return $emp_arr;
    }

    public function getEmployeesUnderRM($empid, $bunit = '', $deptid = '', $eligibility = '')
    {
        /***
         *** edited on 12-03-2015 , soujanya
         *** for filtering employees based on business unit id in Initialize appraisal > step -2
         ***/

        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "select * from employees_summary where reporting_manager = " . $empid . " and isactive = 1 ";
        if (!empty($bunit)) $query .= ' and businessunit_id = ' . $bunit;
        if (!empty($deptid)) $query .= ' and department_id = ' . $deptid;
        if (!empty($eligibility)) $query .= ' and emp_status_id IN (' . $eligibility . ') ';

        $result = $db->query($query);
        $emp_arr = [];
        $emp_arr = $result->fetchAll();
        return $emp_arr;
    }

    public function getCurrentOrgHead()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "select id from main_employees where is_orghead = 1 and isactive = 1";
        $result = $db->query($query);
        $emp_arr = [];
        $emp_arr = $result->fetchAll();
        return $emp_arr;
    }

    public function changeRM($oldRM, $newRM, $status, $ishead)
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->beginTransaction();
        $oldRMData = $this->getsingleEmployeeData($oldRM);
        try {
            if ($status == 'active') {
                $data = [
                    'isactive' => 1,
                    'emptemplock' => 0,
                    'modifieddate' => gmdate("Y-m-d H:i:s"),
                    'modifiedby' => $loginUserId
                ];
                $Query1 = "UPDATE main_employees SET isactive = 1, modifieddate = '" . gmdate("Y-m-d H:i:s") . "', modifiedby = " . $loginUserId . " WHERE user_id=" . $oldRM . " ;";
                $db->query($Query1);
            } else if ($status == 'inactive') {
                $data = [
                    'isactive' => 0,
                    'emptemplock' => 1,
                    'modifieddate' => gmdate("Y-m-d H:i:s"),
                    'modifiedby' => $loginUserId
                ];
            }
            $where = "id = " . $oldRM;
            $user_model = new Default_Model_Usermanagement();
            $result = $user_model->SaveorUpdateUserData($data, $where);

            if ($status == 'inactive') {
                $empQuery1 = "UPDATE main_employees SET reporting_manager = " . $newRM . ", modifieddate = '" . gmdate("Y-m-d H:i:s") . "', modifiedby = " . $loginUserId . " WHERE reporting_manager=" . $oldRM . " and isactive = 1 AND user_id <> " . $newRM . ";";

                $empQuery2 = "UPDATE main_employees SET reporting_manager = " . $oldRMData[0]['reporting_manager'] . ", modifieddate = '" . gmdate("Y-m-d H:i:s") . "', modifiedby = " . $loginUserId . " WHERE reporting_manager=" . $oldRM . " and isactive = 1 AND user_id = " . $newRM . ";";


                if ($ishead == '1') {
                    $orgQuery1 = "UPDATE main_employees SET is_orghead = 0,isactive = 0, reporting_manager= " . $newRM . ", modifieddate = '" . gmdate("Y-m-d H:i:s") . "', modifiedby = " . $loginUserId . " WHERE user_id=" . $oldRM . " ;";
                    $db->query($orgQuery1);

                    $orgQuery2 = "UPDATE main_employees SET is_orghead = 1,reporting_manager= 0, modifieddate = '" . gmdate("Y-m-d H:i:s") . "', modifiedby = " . $loginUserId . " WHERE user_id=" . $newRM . " ;";
                    $db->query($orgQuery2);
                }
                $db->query($empQuery1);
                $db->query($empQuery2);
            }
            $db->commit();
            return 'success';
        } catch (Exception $e) {
            return 'failed';
            $db->rollBack();
        }
    }

    public function getEmployeesForOrgHead($userid = '')
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($userid == '') {
            $qry_str = " SELECT e.id, concat(e.userfullname,if(j.name is null,'',concat(' , ',j.name))) as name,e.profileimg 
                                FROM main_employees e 
                                LEFT join main_empposition ep on ep.employee_id = e.id 
                                LEFT join main_jobtitle j on ep.jobtitle_id = j.jobtitle_id  
                                 order by name asc";
        } else {
            $qry_str = " SELECT e.id, concat(e.userfullname,if(j.name is null,'',concat(' , ',j.name))) as bname,e.profileimg 
                                 FROM main_employees e 
                                LEFT join main_empposition ep on ep.employee_id = e.id 
                                LEFT join main_jobtitle j on ep.jobtitle_id = j.jobtitle_id  
                                WHERE   e.id <> " . $userid . " order by name desc";
        }
        $reportingManagersData = $db->query($qry_str);
        $res = $reportingManagersData->fetchAll();
        return $res;
    }

    public function getEmployeesForServiceDesk($bunitid = '', $deptid = '')
    {
        $where = 'e.isactive=1 AND r.' . ROLE_ARRAY['colgroupid'] . ' IN (2,3,4,6)';
        if ($bunitid != '' && $bunitid != 'null')
            $where .= ' AND sbu.sbunit_id = ' . $bunitid . '';
        if ($deptid != '' && $deptid != 'null')
            $where .= ' AND e.department_id = ' . $deptid . '';


        $db = Zend_Db_Table::getDefaultAdapter();
        $qry = "select e.userfullname,e.user_id,e.emprole from employees_summary e
                inner join main_sbunit sbu on sbu.representative_id = e.id
				left join " . ROLE_ARRAY['tablename'] . " r on " . ROLE_ARRAY['colid'] . "=e.emprole and r." . ROLE_ARRAY['colisactive'] . "='Y'
				left join main_privileges p  on e.emprole = p.role  and p.isactive=1 and p.object = " . SERVICEDESK . " where " . $where . " group by e.user_id order by e.userfullname asc";

        $res = $db->query($qry)->fetchAll();
        return $res;
    }

    public function getApproverForServiceDesk($bunitid = '', $deptid = '', $empstring = '')
    {
        $where = 'e.isactive=1 AND r.' . ROLE_ARRAY['colgroupid'] . ' =1';

        if ($empstring != '' && $empstring != 'null')
            $where .= ' AND e.user_id NOT IN(' . $empstring . ')';

        $db = Zend_Db_Table::getDefaultAdapter();
        /*** modified on 18-08-2015 ***
         *** to fix the issue when job title is empty ***
         *** query is returning empty userfullname ***
         ***/
        $qry = "select case when e.jobtitle_name !='' then concat(e.userfullname,concat(' , ',e.jobtitle_name)) else e.userfullname end as userfullname,e.id,e.emprole from employees_summary e
                
				inner join " . ROLE_ARRAY['tablename'] . " r on " . ROLE_ARRAY['colid'] . "=e.emprole and r." . ROLE_ARRAY['colisactive'] . "='Y'
				inner join main_privileges p  on e.emprole = p.role  and p.isactive=1 and p.object = " . SERVICEDESK . " where " . $where . " group by e.id order by e.userfullname asc";


        $res = $db->query($qry)->fetchAll();
        return $res;
    }

    public function getEmployeeDetails($useridstring = '')
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $emparr = [];

        if ($useridstring != '' && $useridstring != 'null') {
            $qry = "select e.userfullname,e.user_id,e.emprole from employees_summary e where e.user_id IN(" . $useridstring . ")";

            $res = $db->query($qry)->fetchAll();
        }
        if (!empty($res)) {
            foreach ($res as $resArr) {
                $emparr[$resArr['user_id']] = $resArr['userfullname'];
            }
        }
        return $emparr;
    }

    public function getIndividualEmpDetails($userid)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $qry = "select e.userfullname,e.user_id,e.emprole from employees_summary e where e.user_id = " . $userid . " ";
        $res = $db->query($qry)->fetch();
        return $res;
    }
    
    public function getEmp_from_summary($userid)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $qry = "select e.* from employees_summary e where e.user_id = " . $userid . " ";
        $res = $db->query($qry)->fetch();
        return $res;
    }
    
    public function getEmplist()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $qry = "select r.* from main_employees r ";
        $res = $db->query($qry)->fetchAll();
        return $res;
    }
    
    public function getEmplistexclude($id)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $qry = "select r.id,r.userfullname from main_employees r where r.id !=" . $id;
        $res = $db->query($qry)->fetchAll();
        return $res;
    }
    public function getPrefix_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select prefix,id from main_prefix where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['prefix'])] = $row['id'];
            }
        }
        return $parray;
    }

    public function getRoles_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select code,id from " . ROLE_ARRAY['tablename'] . " where isactive = 'Y'";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['code'])] = $row['id'];
            }
        }
        return $parray;
    }

    public function getMngRoles_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select code,id from " . ROLE_ARRAY['tablename'] . " where isactive = 'Y' and group_id = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[$row['id']] = strtolower($row['code']);
            }
        }
        return $parray;
    }

    public function getBU_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select unitcode,id from main_businessunits where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['unitcode'])] = $row['id'];
            }
        }
        return $parray;
    }

    public function getPayfrequency_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select id,freqcode from main_payfrequency where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['freqcode'])] = $row['id'];
            }
        }
        return $parray;
    }
    public function getCurrency_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select id,currencycode from main_currency where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['currencycode'])] = $row['id'];
            }
        }
        return $parray;
    }

    public function getDep_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select deptcode,id from main_departments where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['deptcode'])] = $row['id'];
            }
        }
        return $parray;
    }

    public function getJobs_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select jobtitlecode,id from main_jobtitles where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['jobtitlecode'])] = $row['id'];
            }
        }
        return $parray;
    }

    public function getPositions_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select positionname,id from main_positions where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['positionname'])] = $row['id'];
            }
        }
        return $parray;
    }

    public function getUsers_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select employeeId,user_id from employees_summary where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['employeeId'])] = $row['user_id'];
            }
        }
        return $parray;
    }

    public function getEstat_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select workcode,workcodename from main_employmentstatus where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[strtolower($row['workcode'])] = $row['workcodename'];
            }
        }
        return $parray;
    }
    public function getDOLEstat_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $parray = [];
        $query = "select workcode,workcodename from main_employmentstatus where isactive = 1 and workcodename in (8,9,10)";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $parray[$row['workcodename']] = strtolower($row['workcode']);
            }
        }
        return $parray;
    }

    public function getEmps_emp_excel()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $earray = [];
        $iarray = [];
        // $query = "select user_id,emailaddress,employeeId from employees_summary where isactive = 1";
        $query = "select user_id,emailaddress,employeeId from employees_summary";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $iarray[$row['user_id']] = strtolower($row['employeeId']);
            }
        }

        $query = "select user_id,emailaddress,employeeId from employees_summary";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $earray[$row['user_id']] = strtolower($row['emailaddress']);
            }
        }
        return ['email' => $earray, 'ids' => $iarray];
    }

    public function getEmpsDeptWise()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $emp_array = [];
        $query = "select ifnull(department_id,0) department_id,employeeId from employees_summary where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $emp_array[$row['department_id']][] = strtolower($row['employeeId']);
            }
        }
        return $emp_array;
    }

    public function getDeptBUWise()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $emp_array = [];
        $query = "select unitid,deptcode from main_departments where isactive = 1";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $emp_array[$row['unitid']][] = strtolower($row['deptcode']);
            }
        }
        return $emp_array;
    }

    public function getPosJTWise()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $emp_array = [];
        $query = "select jobtitleid,positionname from main_positions where isactive =1 order by jobtitleid";
        $res = $db->query($query)->fetchAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                $emp_array[$row['jobtitleid']][] = strtolower($row['positionname']);
            }
        }
        return $emp_array;
    }

    /**
     * 
     * Get count of employees reporing to manager
     * @$manager_id interger - ID of reporting manager
     */
    public function getCountEmpReporting($manager_id = "")
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $count_query = "select count(id) cnt from employees_summary e where e.isactive != 5 and reporting_manager = '$manager_id'";
        $count_result = $db->query($count_query);
        $count_row = $count_result->fetch();
        return $count_row['cnt'];
    }
    
    public function getMngmntEmployees()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "	select es.id,es.user_id,es.userfullname,es.emailaddress from employees_summary es where es.user_id in (
					select s.user_id from employees_summary s 
					inner join " . ROLE_ARRAY['tablename'] . " r on s.emprole = " . ROLE_ARRAY['colid'] . " where r." . ROLE_ARRAY['colgroupid'] . " = 1  and r." . ROLE_ARRAY['colisactive'] . "='Y' ) and es.isactive =1 ;";
        $data = $db->query($query)->fetchAll();
        return $data;
    }
    
    public function checkemployeeidexist($employeeId, $where_condition)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "select count(id) as emp_count from main_users where empid='" . $employeeId . "' $where_condition ";
        $result = $db->query($query);
        $data = $result->fetch();
        return $data['emp_count'];
    }
    
    // to get hr employees
    public function getHrEmployees()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "	select es.id,es.user_id,es.userfullname from employees_summary es where es.user_id in (
					select s.user_id from employees_summary s
					inner join " . ROLE_ARRAY['tablename'] . " r on s.emprole = " . ROLE_ARRAY['colid'] . " where r." . ROLE_ARRAY['colgroupid'] . " = 3  and r." . ROLE_ARRAY['colisactive'] . "='Y' ) and es.isactive =1 ;";
        // die($query);
        $data = $db->query($query)->fetchAll();
        return $data;
    }

    public function getempidformuserid($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from('main_users', 'empid')
            ->where('id = ' . $id);
        return $this->fetchRow($select)->toArray();
    }

    /**
     * Generates a flat ID => Name array for Select dropdowns.
     * Uses `userfullname` as the descriptor for Employees.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getPairs($activeOnly = true)
    {
        $select = $this->select()
            ->from($this->_name, ['id', 'userfullname'])
            ->order('userfullname ASC');
            
        if ($activeOnly) {
            $select->where('isactive = ?', 1);
        }
        return $this->getAdapter()->fetchPairs($select);
    }


    /**
     * Resolves a generic Organizational Unit into a flat array of eligible Employee IDs.
     * CRITICAL FOR PA MODULE: Used by the Controller when generating the `map_pa_appraisal_audience` snapshot.
     * 
     * @param string $unitType e.g., 'Company', 'BusinessUnit', 'Department', 'Role'
     * @param int    $unitId   The ID of the selected unit (0 if 'All')
     * @return array List of active Employee IDs
     */
    public function getEmployeeIdsByOrgUnit($unitType, $unitId)
    {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from(['e' => 'main_employee'], ['id'])
            ->where('e.isactive = ?', 1);

        if ($unitType === 'All') {
            return $db->fetchCol($select);
        }

        if ($unitType === 'Employee') {
            return [(int)$unitId]; // Direct targeting
        }

        if ($unitType === 'Role') {
            // Join userrole mapping
            $select->joinInner(['u' => 'main_users'], 'e.id = u.empid', [])
                   ->joinInner(['ur' => 'main_userrole'], 'u.id = ur.user_id', [])
                   ->where('ur.role_id = ?', (int)$unitId)
                   ->where('ur.isactive = 1')
                   ->where('ur.datefrom <= CURDATE()')
                   ->where('(ur.dateto IS NULL OR ur.dateto >= CURDATE())');
            return $db->fetchCol($select);
        }

        // For structural units, join the empposition table (Current active position)
        $select->joinInner(['pos' => 'main_empposition'], 'e.id = pos.employee_id', [])
               ->where('pos.isactive = ?', 'Y')
               ->where('pos.datefrom <= CURDATE()')
               ->where('pos.dateto >= CURDATE()');

        // Map the Rulebook string to the actual DB column in main_empposition
        $columnMap = [
            'Company'      => 'company_id',
            'BusinessUnit' => 'sbunit_id',
            'Division'     => 'division_id',
            'Department'   => 'department_id',
            'Section'      => 'section_id'
        ];

        if (isset($columnMap[$unitType])) {
            $select->where('pos.' . $columnMap[$unitType] . ' = ?', (int)$unitId);
        }

        return $db->fetchCol($select);
    }

    /**
     * Hierarchy Resolution: Gets the ID of the employee's current reporting manager.
     * Used for 'Manager' evaluator types in PA.
     * 
     * @param int $employeeId
     * @return int|null
     */
    public function getManagerIdByEmployee($employeeId)
    {
        $select = $this->getAdapter()->select()
            ->from('main_empposition', ['reportto_id'])
            ->where('employee_id = ?', (int)$employeeId)
            ->where('isactive = ?', 'Y')
            ->order('datefrom DESC')
            ->limit(1);
            
        $result = $this->getAdapter()->fetchOne($select);
        return $result ? (int)$result : null;
    }

    /**
     * Hierarchy Resolution: Gets IDs of employees reporting to the specified manager.
     * Used for 'Subordinate' evaluator types in 360 Reviews.
     * 
     * @param int $managerId
     * @return array
     */
    public function getDirectReportIdsByEmployee($managerId)
    {
        $select = $this->getAdapter()->select()
            ->from(['pos' => 'main_empposition'], ['employee_id'])
            ->joinInner(['e' => 'main_employee'], 'pos.employee_id = e.id', [])
            ->where('pos.reportto_id = ?', (int)$managerId)
            ->where('pos.isactive = ?', 'Y')
            ->where('e.isactive = ?', 1);
            
        return $this->getAdapter()->fetchCol($select);
    }

    /**
     * Hierarchy Resolution: Gets IDs of employees sharing the same manager.
     * Used for 'Peer' evaluator types in 360 Reviews.
     * 
     * @param int $employeeId
     * @return array
     */
    public function getPeerIdsByEmployee($employeeId)
    {
        $managerId = $this->getManagerIdByEmployee($employeeId);
        if (!$managerId) return [];

        $select = $this->getAdapter()->select()
            ->from(['pos' => 'main_empposition'], ['employee_id'])
            ->joinInner(['e' => 'main_employee'], 'pos.employee_id = e.id', [])
            ->where('pos.reportto_id = ?', (int)$managerId)
            ->where('pos.employee_id != ?', (int)$employeeId) // Exclude self
            ->where('pos.isactive = ?', 'Y')
            ->where('e.isactive = ?', 1);
            
        return $this->getAdapter()->fetchCol($select);
    }
}
