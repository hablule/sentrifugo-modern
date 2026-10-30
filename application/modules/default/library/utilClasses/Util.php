<?php
require_once 'application/modules/default/library/utilClasses/tree.php';
class utlis
{
    public function getfields($fieldType, $tables, $colname)
    {

        $type = '';
        $tablenames = str_replace('"', "'", str_replace(["[", "]"], '', json_encode($tables)));
        if (isset($fieldType)) {

            if ($fieldType == 'string') {
                $type = 'varchar';
            } else if ($fieldType == 'int') {
                $type = $fieldType;
            } else if ($fieldType == 'float') {
                $type = $fieldType;
            } else {
                $type = 'double';
            }
            $model = new Default_Model_Userdefinedclass();
            $data = $model->getTypeMatchedFields($type, $tablenames, $colname);
            die(json_encode($data));
        } else {
            $error['message'] = "Field Type is Required!";
            return json_encode($error);
        }
    }
    public function paramstaff($userDefined)
    {

        $model = new Default_Model_Userdefinedclass();
        foreach ($userDefined as $defined) {
            $parameterValues = [];
            $parameterArray = explode(',', $defined['parameters']);
            foreach ($parameterArray as $parameter) {
                $tempArray = explode('.', $parameter);
                $parameterValue = $model->getParameterValues($tempArray[0], $tempArray[1]);
                array_push($parameterValues, $parameterValue);
            }
            //die(json_encode($parameterValues));
            $method = str_replace('.', '::', $defined['method']);
            $smallestLen = -1;
            foreach ($parameterValues as $parameterValue) {
                if ($smallestLen == -1 || $smallestLen > count($parameterValue)) {
                    $smallestLen = count($parameterValue);
                }
            }
            for ($i = 0; $i < $smallestLen; $i++) {
                $argArray = [];
                foreach ($parameterValues as $parameterValue) {
                    //Here I was tring to figure out how to access each 
                    //arguments and there value efficently and correctly
                }
            }
        }
        $classname = explode('.', $userDefined[0]['method'])[0];
        $methodname = explode('.', $userDefined[0]['method'])[1];
        require 'Classes/UserDefined/Classes/' . $classname . '.php';
        die(call_user_func_array(array($classname, $methodname), array(1, '', '')));
        die(json_encode($userDefined));
    }
    public function tablesList()
    {
        $x = fopen('tablelists.txt', 'r', true);
        $list = [];
        while (!feof($x)) {
            $list[] = str_replace(PHP_EOL, '', fgets($x));
        }
        return $list;
    }
    public function treehouse($rootnode, $umodel, $start, $end, $nextnode, $children)
    {
        $res = $umodel->getReferencedTables($start);

        foreach ($res as $key => $value) {
            $node = new TreeNode($value);
            Zend_Debug::dump($value, 'help');
            if ($nextnode == null) {
                $rootnode->addchild($node);
                array_push($children, $node);
            } else {
                $nextnode->addchild($node);
                array_push($children, $node);
            }
        }

        $nextnode = array_shift($children);
        if ($nextnode == null) {

            //Zend_Debug::dump($node);
            return $rootnode;
        } else {

            $val = $nextnode->data;

            $start = $val['referenced_table'];
            $children = $children;
            $umodel = $umodel;
            $rootnode = $rootnode;
            $end = $end;
            $this->treehouse($rootnode, $umodel, $start, $end, $nextnode, $children);
        }

        /*  if ($nextnode==null) {
            $nextnode = $rootnode;
        }
        else{
            $nextnode = $nextnode;
        } */
    }
    public function sqlBuilder($nodearray)
    {
        $query = 'SELECT * FROM ' . array_pop($nodearray);

        for ($i = count($nodearray) - 1; $i >= 0; $i--) {

            $query .= ' INNER JOIN ' . $nodearray[$i]['referenced_table'] . ' ON ' . $nodearray[$i]['referenced_table'] . '.' . $nodearray[$i]['referenced_column'] . ' = ' . $nodearray[$i]['table'] . '.' . $nodearray[$i]['column'];
        }
    }

    public function joinBuilder($nodes)
    {
        $query = '';
        array_pop($nodes);
        for ($i = count($nodes) - 1; $i >= 0; $i--) {

            $query .= ' INNER JOIN ' . $nodes[$i]['referenced_table'] . ' ON ' . $nodes[$i]['referenced_table'] . '.' . $nodes[$i]['referenced_column'] . ' = ' . $nodes[$i]['table'] . '.' . $nodes[$i]['column'];
        }
        return $query;
    }
    public static function getYesno($check, $type = 'short')
    {
        if ($type == 'short') {
            if ($check == 'Yes') {
                return 'Y';
            } else {
                return 'N';
            }

            if ($type == 'long') {
                if ($check == 'Y') {
                    return 'Yes';
                } else {
                    return 'No';
                }
            }
        }
    }
    public static function getcheckboxvalue($check, $type = 'database')
    {
        if ($type == 'database') {
            if ($check == 1) {
                return 'Y';
            } else {
                return 'N';
            }
        } else if ($type == 'form') {
            if ($check == 'Y') {
                return 1;
            } else {
                return 0;
            }
        } else if ($type == 'longdatabase') {
            if ($check == 1) {
                return 'Yes';
            } else {
                return 'No';
            }
        } elseif ($type == 'longform') {
            if ($check == 'Yes') {
                return 1;
            } else {
                return 0;
            }
        }
    }

    public function populate_select($element, $data, $selectvalue, $selecttext, $emptyselect = '')
    {

        $element->addMultiOption('', $emptyselect);

        foreach ($data as $value) {
            $element->addMultiOption($value[$selectvalue], $value[$selecttext]);
        }

        return $element;
    }

    public function checkexist($table, $primary, $coloumn1, $coloumn2, $where1, $where2)
    {
        // die("hh");
        $config = array(
            'name' => $table,
            'primary' => $primary
        );
        $table = new Zend_Db_Table($config);
        $data = $table->select()
            ->setIntegrityCheck(false)
            ->from($table)
            ->where($coloumn1 . '=' . $where1 . '&&' . $coloumn2 . '=' . $where2);
        //die($data);
        $yes = $table->fetchAll($data)->toArray();

        if (empty($yes)) {
            return 'no';
        } else {
            return 'yes';
        }
    }

    public static function isoverlappingshift($shift)
    {

        $shiftmodel = new Schedule_Model_Timetable();
        if($shift == '')
        {
            return false;
        }
        $shifts = implode(',', $shift);
  

        $shiftData = $shiftmodel->getShifts($shifts);
       
        for ($i = 0; $i < count($shiftData); $i++) {
            if (utlis::compareTime(strtotime($shiftData[$i]['starttime']), strtotime($shiftData[$i]['endtime']), $shiftData, $i + 1))
                return true;
        }

        return false;
    }

    public static function isoverlappingschedule($scheduleid, $empid)
    {
        $scheduleModel = new Schedule_Model_Timeschedule();
        $empscheduleModel = new Default_Model_Empschedule();
        $schedule_data = $scheduleModel->getscheduleByid($scheduleid);
        $empschedule_data = $empscheduleModel->getSchedulebyemp($empid);
        foreach ($empschedule_data as $value) {
            if (utlis::compareDate($schedule_data['startdate'], $schedule_data['enddate'], $value))
                return true;
        }
       
        return false;
    }

    private function compareDate($startDate, $endDate, $schedule)
    {
        if (
            ($startDate >= $schedule['start_date'] && $startDate <= $schedule['end_date'])
            || ($endDate >= $schedule['start_date'] && $endDate <= $schedule['end_date'])
        )
            return true;
        return false;
    }


    private  function compareTime($startTime, $endTime, $shift, $i)
    {
       
        for ($j = $i; $j < count($shift); $j++) {
            if (($startTime >= strtotime($shift[$j]['starttime'])
                    && $startTime <= strtotime($shift[$j]['endtime']))
                ||
                ($endTime >= strtotime($shift[$j]['starttime'])
                    && $endTime <= strtotime($shift[$j]['endtime']))
            )
                return true;
        }

        return false;
    }
}
