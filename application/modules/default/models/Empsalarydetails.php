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

class Default_Model_Empsalarydetails extends Zend_Db_Table_Abstract
{
	protected $_name = 'main_empsalarydetails';
	protected $_primary = 'empsalary_id';


	public function getEmpSalaryData($sort, $by, $pageNo, $perPage, $searchQuery, $id)
	{
		$where = " e.emp_id = " . $id ;

		if ($searchQuery)
			$where .= " AND " . $searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();

		$empsalarydetailData = $this->select()
			->setIntegrityCheck(false)
			->from(array('e' => 'main_empsalarydetails'), array('id'=>'e.empsalary_id', 'e.amount', 'e.amount_frequency', 'from_date'=>'DATE_FORMAT(e.from_date,"'.DATEFORMAT_MYSQL.'")', 'end_date'=>'DATE_FORMAT(e.end_date,"'.DATEFORMAT_MYSQL.'")', 'e.description'))
			->joinLeft(array('c'=>'main_currency'),'c.id=e.currency_id','c.currencyname')
			->joinLeft(array('b'=>'main_empbankaccount'),'b.empbankaccount_id=e.empbankaccount_id','b.accountno')
			->where($where)
			->order("$by $sort")
			->limitPage($pageNo, $perPage);
			
		return $empsalarydetailData;
	}
	public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1 = '', $exParam2 = '', $exParam3 = '', $exParam4 = '')
	{
		$searchQuery = '';
		$searchArray = array();
		$data = array();
		if ($searchData != '' && $searchData != 'undefined') {
			$searchValues = json_decode($searchData);
			foreach ($searchValues as $key => $val) {

				 if ($key == 'from_date')
				$searchQuery .= " e.from_date like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
				else if ($key == 'end_date')
				$searchQuery .= " e.end_Date like '%" .  sapp_Global::change_date(urldecode($val), 'database') . "%' AND ";
				else
				$searchQuery .= " " . $key . " like '%" . $val . "%' AND ";
				$searchArray[$key] = $val;
			}
			$searchQuery = rtrim($searchQuery, " AND");
		}
		$objName = 'empsalarydetails';

		$tableFields = array(
			'action' => 'Action',
			'currencyname' => 'Currency',
			'amount' => 'Amount',
			'amount_frequency' => 'Pay Frequency',
			'accountno'=> 'Bank Account',
			'description' => 'Description',
			'from_date' => 'Start Date',
			'end_date' => 'End Date',
		);

		$tablecontent = $this->getEmpSalaryData($sort, $by, $pageNo, $perPage, $searchQuery, $exParam1);

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
			'menuName' => 'Salaray Details',
			'formgrid' => 'true',
			'unitId' => $exParam1,
			'userAction' => $exParam3,	
			'call' => $call,
			'context' => $exParam2,
			'search_filters' => array(
				'active_company' => array(
					'type' => 'select',
					'filter_data' => $bool_arr,
				),
			'from_date' => array('type' => 'datepicker'),
			'end_date' => array('type' => 'datepicker')
			)
		);
		return $dataTmp;
	}
	public function getsingleEmpSalaryDetailsData($id)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('s' => 'main_empsalarydetails'), array('s.*'))
			->joinLeft(array('c'=>'main_currency'),'c.id=s.currency_id','c.currencyname')
			->joinLeft(array('b'=>'main_empbankaccount'),'b.empbankaccount_id=s.empbankaccount_id','b.accountno')
			->where('s.emp_id=' . $id . ' AND s.isactive = 1');

		return $this->fetchAll($select)->toArray();
	}

	public function getsingleSalaryDetailsData($id)
	{
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('s' => 'main_empsalarydetails'), array('s.*'))
			->joinLeft(array('c'=>'main_currency'),'c.id=s.currency_id','c.currencyname')
			->joinLeft(array('b'=>'main_empbankaccount'),'b.empbankaccount_id=s.empbankaccount_id','b.accountno')
			->where('s.empsalary_id=' . $id);

		return $this->fetchAll($select)->toArray();
	}

	public function SaveorUpdateEmpSalaryData($data, $where)
	{
		if ($where != '') {
			$this->update($data, $where);
			return 'update';
		} else {
			$this->insert($data);
			$id = $this->getAdapter()->lastInsertId('main_empsalarydetails');
			return $id;
		}
	}
}
