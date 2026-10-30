<?php

class Default_Model_Empbankaccount extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_empbankaccount';
	protected $_primary = 'empbankaccount_id';

	public function getEmpBankAccountData($sort, $by, $pageNo, $perPage, $searchQuery, $id)
	{
		$where = " e.emp_id = " . $id ;//. " AND e.isactive = 'Y' ";

		if ($searchQuery)
			$where .= " AND " . $searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();

		$empbankaccount = $this->select()
			->setIntegrityCheck(false)
			->from(array('e' => 'main_empbankaccount'), array('id' => 'e.empbankaccount_id', 'e.accountno', 'e.bank_account_type', 'isdefault'=>'if(e.isdefault="Y","Yes","No")', 'e.BBAN', 'e.IBAN', 'e.description','isactive'=>'if(e.isactive="Y","Yes","No")'))
			->joinLeft(array('b' => 'main_bank'), 'b.bank_id=e.bank_id', array('bankname' => 'b.name'))
			->joinLeft(array('l'=>'main_location'),'l.location_id=e.location_id',array('location'=>'l.address1'))
			->where($where)
			->order("$by $sort")
			->limitPage($pageNo, $perPage);

		return $empbankaccount;
	}

	public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1 = '', $exParam2 = '', $exParam3 = '', $exParam4 = '')
	{
		$searchQuery = '';
		$searchArray = array();
		$data = array();
		if ($searchData != '' && $searchData != 'undefined') {
			$searchValues = json_decode($searchData);
		
			foreach ($searchValues as $key => $val) {
				if($key == 'bankname'){
					$searchQuery.=" b.name like '%" . $val . "%' AND "; 
					$searchArray[$key] = $val;
				}else if($key == 'location'){
					$searchQuery.=" l.address1 like '%" . $val . "%' AND "; 
					$searchArray[$key] = $val;
				}else{
					$searchQuery .= "e. " . $key . " like '%" . $val . "%' AND ";
					$searchArray[$key] = $val;
				}
			
			}
			$searchQuery = rtrim($searchQuery, " AND");
		}
		$objName = 'empbankaccount';

		$tableFields = array(
			'action' => 'Action',
			'bankname' => 'Bank Name',
			'accountno' => 'Account No.',
			'bank_account_type' => 'Type',
			'BBAN' => 'BBAN',
			'IBAN' => 'IBAN',
			'isactive'=>'Active',
			'isdefault'=>'Default',
			'location' => 'Address',
			'description' => 'Description',

		);

		$tablecontent = $this->getEmpBankAccountData($sort, $by, $pageNo, $perPage, $searchQuery, $exParam1);

		$bool_arr = array('' => 'All', 1 => 'Yes', 2 => 'No');
		$dataTmp = array(
			'userid' => $exParam1,
			'sort' => $sort,
			'by' => $by,
			'pageNo' => $pageNo,
			'perPage' => $perPage,
			'tablecontent' => $tablecontent,
			'objectname' => $objName,
			'extra' => array(),
			'tableheader' => $tableFields,
			'jsGridFnName' => 'getEmployeeAjaxgridData',
			'jsFillFnName' => '',
			'searchArray' => $searchArray,
			'add' => 'add',
			'menuName' => 'Bank Account',
			'formgrid' => 'true',
			'unitId' => $exParam1,
			'call' => $call,
			'userAction' => $exParam3,	

			'context' => $exParam2,
			'search_filters' => array(
				'isdefault' => array(
					'type' => 'select',
					'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
					'isactive' => array(
						'type' => 'select',
						'filter_data' => array(''=>'All','Y'=>'Yes','N'=>'No')),
		)
			
		);
		return $dataTmp;
	}

	public function SaveorUpdateBankAccount($data, $where)
	{
		// Zend_Debug::dump($data);
		// die('ow');
		if ($where != '') {
			$temp = $this->update($data, $where);
			if ($temp <= 0) {
				$this->insert($data);
			}
			return 'update';
		} else {
			$this->insert($data);
			$id = $this->getAdapter()->lastInsertId('main_empbankaccount');
			return $id;
		}
	}

	public function deleteEmpBankAccount($where){
		$this->delete($where);
	}
	public function getEmpSingleBankAccount($account_id)
	{
		$query = $this->select()
			->setIntegrityCheck(false)
			->from(array('e' => 'main_empbankaccount'))
			->joinLeft(array('b'=>'main_bank'),'b.bank_id=e.bank_id',array('bankname'=>'b.name'))
			->joinLeft(array('l'=>'main_location'),'l.location_id=e.location_id',array('address1'=>'l.address1','address2'=>'l.address2','address3'=>'l.address3'))
			->where('e.empbankaccount_id=' . $account_id);
		
		return $this->fetchAll($query)->toArray();
	}
	public function getEmpBankAccounts(){
		$query = $this->select()
		->setIntegrityCheck(false)
		->from(array('e'=>'main_empbankaccount'),array('e.empbankaccount_id', 'e.accountno'))
		->where("e.isactive='Y'");
		return $this->fetchAll($query)->toArray();
	}

	public function getdata($maintable,$main_id,$child_id,$childtable)
	{
		$query = $this->select()
					  ->setIntegrityCheck(false)
					  ->from($maintable)
					  ->joinInner($childtable,$maintable.'.'.$main_id.' = '.$childtable.'.'.$child_id);
					  
		return $this->fetchAll($query)->toArray();

	}
}
