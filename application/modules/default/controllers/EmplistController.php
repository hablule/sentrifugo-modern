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

class Default_EmplistController extends Zend_Controller_Action
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
            // if($dashboardcall == 'Yes')
            // 	$perPage = DASHBOARD_PERPAGE;
            // else	
            // 	$perPage = PERPAGE;
            // $sort = 'DESC';$by = 'p.name';$pageNo = 1;$searchData = '';$searchQuery = '';
            // $searchArray = array();
        } else {

            $sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';

            $by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'e.user_id';
            if ($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
            else $perPage = $this->_getParam('per_page', PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            /** search from grid - START **/
            $searchData = $this->_getParam('searchData');

            /** search from grid - END **/
        }
        // echo '<script language="javascript">alert("juas");</script>';


        $dataTmp2 = $empmodel->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, '', '', '', '');


        array_push($data, $dataTmp2);

        $this->view->dataArray2 = $dataTmp2;

        $this->view->call = $call;
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $this->view->form = $form;
        // foreach ($ids as $key => $value) {
        //     $data = array(
        //         'emp_id' => $value

        //     );
        // $payrollmodel->addpayroll($data);
        // }
       

        // echo '<script language="javascript">alert('$id1');</script>';



    }

}
