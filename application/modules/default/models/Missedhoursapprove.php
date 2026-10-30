<?php
class Default_Model_Missedhoursapprove extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_missed_hours';
    protected $_primary = 'missed_hours_id';

    public function getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery, $empids, $perm)
    {
        $where = " e.id in (" . $empids . ") ";
        if ($searchQuery)
            $where .= " AND " . $searchQuery;

        $AdditionData = $this->select()
            ->setIntegrityCheck(false)
            ->from(['mh' => 'main_missed_hours'], ['id' => 'mh.missed_hours_id', 'amount_lost' => 'mh.amount_lost', 'submitted_date' => 'DATE_FORMAT(mh.submitted_date,"' . DATEFORMAT_MYSQL . '")', 'approved' => 'if(mh.approved="N","No","Yes")', 'processed' => 'if(mh.processed="N","No","Yes")'])
            ->joinInner(['ms' => 'main_schedule'], "ms.schedule_id = mh.sch_id ", ['schedule_name' => 'ms.name'])
            ->joinInner(['e' => 'main_employees'], "e.id = mh.emp_id ", ['emp_id' => 'e.id', 'userfullname' => 'e.userfullname', 'profileimg' => 'e.profileimg'])
            ->joinInner(['tt' => 'main_time_table'], "tt.time_id = mh.shift_id ", ['name' => 'tt.name'])
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);

        return $AdditionData;
    }
    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $empids, $perm)
    {
        $searchQuery = '';
        $searchArray = [];
        $data = [];
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);
            foreach ($searchValues as $key => $val) {
                if ($key == 'amount_lost')
                    $searchQuery .= " mh.amount_lost like '%" . $val . "%' AND ";
                else if ($key == 'schedule_name')
                    $searchQuery .= " ms.name  like '%" . $val . "%' AND ";
                else if ($key == 'name')
                    $searchQuery .= " tt.name like '%" . $val . "%' AND ";
                else if ($key == 'userfullname')
                    $searchQuery .= " e.userfullname like '%" . $val . "%' AND ";
                else if ($key == 'approved')
                    $searchQuery .= " mh.approved like '%" . $val . "%' AND ";
                else if ($key == 'processed')
                    $searchQuery .= " mh.processed like '%" . $val . "%' AND ";
                else if ($key == 'submitted_date')
                    $searchQuery .= " mh.submitted_date like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
                $searchArray[$key] = $val;
            }

            $searchQuery = rtrim($searchQuery, " AND");
            //die(json_encode($searchQuery));
        }
        $objName = 'missedhoursapprove';

        $objId = 'missedhoursapprove';

        $tableFields = [
            'action' => 'Action',
            'schedule_name' => 'Schedule Name',
            'name' => 'Shift Name',
            'userfullname' => 'Employee Name',
            'amount_lost' => 'Amount Lost',
            'submitted_date' => 'Submitted Date',
            'approved' => 'Is Approved',
            'processed' => 'Is Processed',

            // 'earned_time'=> 'Earned Minute',


        ];
        $tablecontent = $this->getAdditionData($sort, $by, $pageNo, $perPage, $searchQuery, $empids, $perm);

        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objId,
            'menuName' => 'Employee Missed Hours',
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'add' => 'add',
            'perm' => $perm,
            'call' => $call,
            'dashboardcall' => $dashboardcall,
            'search_filters' => [
                'submitted_date' => ['type' => 'datepicker'],
                'approved' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Y' => 'Yes', 'N' => 'No']
                ],
                'processed' => [
                    'type' => 'select',
                    'filter_data' => ['' => 'All', 'Y' => 'Yes', 'N' => 'No']
                ],
            ],

        ];
        return $dataTmp;
    }


    public function missedhoursdetail($id)
    {
        $result = $this->select()
            ->setIntegrityCheck(false)
            ->from(['mh' => 'main_missed_hours'], ['missed_hours_id' => 'mh.missed_hours_id', 'amount_lost' => 'mh.amount_lost', 'emp_id' => 'mh.emp_id', 'submitted_date' => 'DATE_FORMAT(mh.submitted_date,"' . DATEFORMAT_MYSQL . '")'])
            ->joinInner(['ms' => 'main_schedule'], "ms.sch_id =mh.sch_id ", ['schedule_name' => 'ms.schedule_name'])
            ->joinLeft(['e' => 'main_employees'], "e.id = mh.emp_id ", ['userfullname' => 'e.userfullname'])
            ->joinInner(['tt' => 'main_time_table'], "tt.time_id = mh.shift_id ", ['name' => 'tt.name'])
            ->where('mh.missed_hours_id=' . $id);
        //die($result."dddddd");
        return $this->fetchAll($result)->toArray();
    }

    public function editmissedhours($data, $where)
    {
        $this->update($data, $where);
    }
}
