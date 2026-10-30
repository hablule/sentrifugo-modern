<?php
class Schedule_Model_Device extends Zend_Db_Table_Abstract
{
    protected $_name = "main_device";
    public $_primary = "id";

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall)
    {
        $searchQuery = '';
        $searchArray = [];
        $data = [];
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);

            if (count($searchValues) > 0) {
                foreach ($searchValues as $key => $val) {
                    $searchQuery .= "d." . $key . " like '%" . $val . "%' AND ";
                    $searchArray[$key] = $val;
                }
                $searchQuery = rtrim($searchQuery, " AND");
                //die(json_encode($searchQuery));
            }
        }

        $objName = 'device';
        $objID = 'device';
        $tableFields = [
            'action' => 'Action',
            'device_id' => 'Device ID',
            'device_model' => 'Device Model',
            'device_ip' => 'IPAddress',
            'device_port' => 'Port',
            'is_master' => 'Master'

        ];

        $tablecontent = $this->getDevice($sort, $by, $pageNo, $perPage, $searchQuery);


        $dataTmp = [
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'objectId' => $objID,
            'extra' => [],
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'add' => 'addconnection',
            'formgrid' => '',
            'call' => $call,
            'menuName' => 'Device',
            'dashboardcall' => $dashboardcall,



        ];

        return $dataTmp;
    }
    public function getDevice($sort, $by, $pageNo, $perPage, $searchQuery)
    {
        $where = "1";
        if ($searchQuery != '')
            $where .= " AND " . $searchQuery;

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'main_device'])
            ->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
        //die($select);
        return $select;
    }
    public  function deleteDevice($id)
    {
        $where = $this->getAdapter()->quoteInto('id = ?', $id);
        $this->delete($where);
    }
    public function getDevices()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'main_device']);
        return $this->fetchAll($select)->toArray();
    }

    public function getDeviceByFingerPrint()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'main_device'])
            ->where('d.finger_supported = 1');
        return $select;
    }
    public function getDeviceByFace()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'main_device'])
            ->where('d.face_supported = 1');
        return $select;
    }
    public function getDeviceByCard()
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'main_device'])
            ->where('d.card_supported = 1');
        return $select;
    }
    public function filterDevice($authType)
    {
        $authType = explode(',', $authType);
        $facestmt = $fingerstmt = $cardstmt = $select = '';
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'main_device']);
        if (in_array(1, $authType)) {
            $select->where('d.finger_supported = ?', 1);
        }
        if (in_array(2, $authType)) {
            $select->where('d.face_supported = ?', 1);
        }
        if (in_array(3, $authType)) {
            $select->where('d.card_supported = ?', 1);
        }

        return $this->fetchAll($select)->toArray();
    }

    public function getDeviceById($id)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['d' => 'main_device'])
            ->where('d.id = ?', $id);
        return $this->fetchRow($select)->toArray();
    }

    public function saveDevice($data, $where)
    {
        if ($where == '') {
            $this->insert($data);
        } else {
            $this->update($data, $where);
        }
    }
}
