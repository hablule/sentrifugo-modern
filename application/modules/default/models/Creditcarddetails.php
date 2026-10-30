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

class Default_Model_Creditcarddetails extends Zend_Db_Table_Abstract
{	
    protected $_name = 'main_emppaymentcard';
    protected $_primary = 'emppaymentcard_id';
	
       
	public function getcreditcarddetails($sort, $by, $pageNo, $perPage,$searchQuery,$userid)
	{	
		$where = "emp_id = ".$userid ;
		
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		
		$expData = $this->select()
		->setIntegrityCheck(false)
		->from(array('epc' => 'main_emppaymentcard'), array('id'=>'epc.emppaymentcard_id','paymentcard_id'=>'epc.paymentcard_id','nameoncard'=>'epc.nameoncard','cardnumber'=>'epc.cardnumber','verficationcode'=>'epc.verficationcode','description'=>'epc.description','emp_id' => 'epc.emp_id', 'issueddate'=> 'DATE_FORMAT(epc.issueddate,"'.DATEFORMAT_MYSQL.'")', 'expirydate'=> 'DATE_FORMAT(epc.expirydate,"'.DATEFORMAT_MYSQL.'")','isactive'=>'if(epc.isactive="N","No","Yes")'))
		->joinLeft(array('mpc' => 'main_paymentcard'), "epc.paymentcard_id =mpc.paymentcard_id ", array('paymentcard_name' => 'mpc.name'))
		->joinLeft(array('e' => 'main_employees'), "epc.emp_id = e.id ", array('userfullname' => 'e.userfullname'))
		->where($where)
		->order("$by $sort")
		->limitPage($pageNo, $perPage);		
		return $expData;         		
	}
	
	public function getcreditcarddetailsRecord($id=0)
	{ $empdependencyDetailsArr="";$where = "";
		$db = Zend_Db_Table::getDefaultAdapter();		
		if($id != 0)
		{
			$where = "emppaymentcard_id =".$id;
			$empdependencyDetails = $this->select()
									->from(array('c'=>'main_emppaymentcard'))
									->where($where);
		
			$empdependencyDetailsArr = $this->fetchAll($empdependencyDetails)->toArray(); 
        }
		return $empdependencyDetailsArr;   	
	}
    
    public function SaveorUpdateCreditcardDetails($data, $where)
    {
	    if($where != '')
        {
            $this->update($data, $where);
			return 'update';
        }
        else
        {
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('emppaymentcard_id');
			return $id;
		}
		
	
	}
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$exParam1='',$exParam2='',$exParam3='',$exParam4='')
	{		
        $searchQuery = '';$tablecontent = '';
        $searchArray = array();$data = array();$id='';
        $dataTmp = array();
		if($searchData != '' && $searchData!='undefined')
		{
			$searchValues = json_decode($searchData);
			foreach($searchValues as $key => $val)
			{
				if($key=='paymentcard_name'){
					$searchQuery .= " mpc.name like '%".$val."%' AND ";

				}else{
					$searchQuery .= " ".$key." like '%".$val."%' AND ";

				}
				$searchArray[$key] = $val;
			}
			$searchQuery = rtrim($searchQuery," AND");					
		}
		/** search from grid - END **/
		$objName = 'creditcarddetails';
				
		$tableFields = array('action'=>'Action','paymentcard_name'=>'Payment Card','isactive'=>'Active','nameoncard'=>'Name','cardnumber'=>'Number','verficationcode'=>'Code','issueddate'=>'Isue Date','expirydate'=>'Expiry date');
				
						
		$tablecontent = $this->getcreditcarddetails($sort, $by,$pageNo,$perPage,$searchQuery,$exParam1);     
		
		$dataTmp = array('userid'=>$exParam1,
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
						'dashboardcall'=>$dashboardcall,
						'add'=>'add',
						'menuName'=>'Payment Card',
						'formgrid' => 'true',
						'unitId'=>$exParam1,
						'userAction'=>$exParam3,

						'call'=>$call,
						'context'=>$exParam2,
								
						);	
		return $dataTmp;
	}
	public function paymentcarddetailview($id)
	{
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(array('epc' => 'main_emppaymentcard'), array('id'=>'epc.emppaymentcard_id','paymentcard_id'=>'epc.paymentcard_id','nameoncard'=>'epc.nameoncard','cardnumber'=>'epc.cardnumber','verficationcode'=>'epc.verficationcode','description'=>'epc.description','emp_id' => 'epc.emp_id', 'issueddate'=> 'DATE_FORMAT(epc.issueddate,"'.DATEFORMAT_MYSQL.'")', 'expirydate'=> 'DATE_FORMAT(epc.expirydate,"'.DATEFORMAT_MYSQL.'")','isactive'=>'if(epc.isactive="N","No","Yes")'))
		->joinLeft(array('mpc' => 'main_paymentcard'), "epc.paymentcard_id =mpc.paymentcard_id ", array('paymentcard_name' => 'mpc.name'))
		->joinLeft(array('e' => 'main_employees'), "epc.emp_id = e.id ", array('userfullname' => 'e.userfullname'))
		->where('epc.emppaymentcard_id='.$id);
		return $this->fetchAll($select)->toArray();
	}
}