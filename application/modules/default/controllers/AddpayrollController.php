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

class Default_AddperkController extends Zend_Controller_Action
{

    private $options;
    public function preDispatch()
    {
    }

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function indexAction()
    {


        $payrollmodel = new Default_Model_Addperk();
        $empmodel = new Default_Model_Emplist();
        $form = new Default_Form_addperk();
        $inter_options = array();
        $call = $this->_getParam('call');
        if ($this->_request->isPost()) {
           
            if (isset($_POST['perk_ids'])) {
                $perk =  $_POST['perk_ids']; 
                //die($perk);
                $empid = $payrollmodel->getEmpIdperk($perk);
                die(json_encode($empid));
            }
           
            
        }

        if ($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();

        $view = Zend_Layout::getMvcInstance()->getView();
        $objname = $this->_getParam('objname');
        $refresh = $this->_getParam('refresh');
        $data = array();
        $data2 = array();
        $searchQuery2 = '';
        $searchArray2 = array();

        $searchQuery = '';
        $searchArray = array();
        $tablecontent = '';
        $tablecontent2 = '';

        $dashboardcall = $this->_getParam('dashboardcall');

        if ($refresh == 'refresh') {
            if ($dashboardcall == 'Yes')
                $perPage = DASHBOARD_PERPAGE;
            else
                $perPage = PERPAGE;

            $sort = 'DESC';
            $by = 'p.name';
            $pageNo = 1;
            $searchData = '';
            $searchQuery = '';
            $sort2 = 'DESC';
            $by2 = 'e.user_id';
            $pageNo2 = 1;
            $searchData2 = '';
            $searchQuery2 = '';

            $searchArray = array();
            $searchArray2 = array();
        } else {
            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $sort2 = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'p.name';
            $by2 = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'e.user_id';

            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else $perPage = $this->_getParam('per_page', PERPAGE);
            $searchData = $this->_getParam('searchData');
        }
        if ($call == 'ajaxcall') {
            if ($objname == 'addperk') {
                $dataTmp = $payrollmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
                $this->view->dataArray = $dataTmp;
            } else if ($objname == 'overtime') {
                $dataTmp2 = $empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
                $this->view->dataArray2 = $dataTmp2;
            }
        } else {
            $dataTmp = $payrollmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');
            $dataTmp2 = $empmodel->getGrid($sort2, $by2, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');

            $this->view->dataArray = $dataTmp;
            $this->view->dataArray2 = $dataTmp2;
        }
        array_push($data, $dataTmp);
        array_push($data, $dataTmp2);

        $this->view->call = $call;
        $this->view->objectName = $objname;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->form = $form;
    }

    public function addAction()
    {
        $payrollmodel = new Default_Model_Addperk();

        $temp_id = $_POST['emp_ids'];
        $temp_perks_id = $_POST['perk_ids'];
        $datas = array();
        foreach ($temp_id as $id) {

            foreach ($temp_perks_id as $perk_id) {
                $data = array();
                //$emp_id = $payrollmodel->getEmpId($id);

                //$data['emp_id'] = (int)$emp_id[0]['user_id'];
                $data['emp_id'] = (int) $id;
                $data['perk_id'] = (int)$perk_id;
                $data['amount'] = 0;
                array_push($datas, $data);
            }
        }
        //die(json_encode($datas));
        $payrollmodel->saveMultiEmpPerk($datas);
    }
}
