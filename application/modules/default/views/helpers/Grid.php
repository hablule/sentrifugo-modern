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
 *  Sentrifugo Support <support@sentrifugo.com>i
 ********************************************************************************/

/**
 * A View Helper that allows you to easily create Grids with Pagination
 *
 */

class Zend_View_Helper_Grid extends Zend_View_Helper_Abstract
{

	public $view = null;
	public $extra = [];
	private $output; // Container to d the Grid
	private $encrypt_status = "no";
	public $pd_category_id = '';
	public $pd_category_name = '';
	public function grid($dataArray)
	{
		$request = Zend_Controller_Front::getInstance();
		$params = $request->getRequest()->getParams();

		if (isset($dataArray['encrypt_status']) && $dataArray['encrypt_status'] == 'yes')
			$this->encrypt_status = "yes";

		$menu_model = new Default_Model_Menu();
		$session = new Zend_Auth_Storage_Session();
		$data = $session->read();
		$role_id = $data['emprole'];
		$menunamestr = '';
		$sortStr = '';
		$actnArr = [];
		$sortStr = $dataArray['by'];
		$controllers_arr = $menu_model->getControllersByRole($role_id);

		$menuName = '';

		if ($dataArray['objectname'] == 'processes') $actionsobjname = 'empscreening';
		else $actionsobjname = $dataArray['objectname'];
		if (isset($controllers_arr[$actionsobjname . "controller.php"])) {
			$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];

			$menuName = $actions_arr[sizeof($actions_arr) - 1];
		} else {
			$actions_arr = [];
		}

		$gridFieldsArr = [];
		$tmpActionsArr = [];
		$tmpActionsArr = $actions_arr;
		array_pop($tmpActionsArr);	//last element of actions array is menuname so delete that & check the privileges are empty or not...

		$actnArr = $tmpActionsArr;
		if (($key = array_search('add', $actnArr)) !== false) {
			unset($actnArr[$key]);
		}
		if (empty($tmpActionsArr)) {
			$gridFieldsArr = $dataArray['tableheader'];
			unset($gridFieldsArr['action']);
			$dataArray['tableheader'] = $gridFieldsArr;
		}

		if (isset($dataArray['menuName']))
			$menuName = $dataArray['menuName'];

		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($dataArray['tablecontent']));
		$paginator->setItemCountPerPage($dataArray['perPage'])
			->setCurrentPageNumber($dataArray['pageNo']);

		if (empty($dataArray['tableheader'])) {
			$widgetsModel = new Default_Model_Widgets();
			$columnData = $widgetsModel->getTableFields('/' . $dataArray['objectname']);
			$dataArray['tableheader'] = json_decode($columnData['menufields'], true);
		}
		$msgtitle = $dataArray['objectname'] . '_delete';
		$msgtitle = strtoupper($msgtitle);
		$msgflag = constant($msgtitle);
		$msgAr = explode(' ', $msgflag);
		$msgdta = implode('@#$', $msgAr);
		/* Zend_Debug::dump($dataArray); */
		if (isset($dataArray['formgrid']) && $dataArray['formgrid'] == 'true') {

			if (isset($dataArray['unitId']))
				$con = '/unitId/' . $dataArray['unitId'];
			else {
				if (isset($params['user_id'])) {


					$con = '/unitId/' . $params['user_id'];
				}
				//if(isset($params['perk_id'])){
				//$con = '/unitId/'.$params['perk_id'];
				//}
			}

			$formgridVal = $dataArray['formgrid'];

			if ($dataArray['objectname'] == 'departments') {
				$viewaction = 'view';
			} else {
				$viewaction = 'viewpopup';
			}
			$editaction = 'editpopup';
			if (isset($dataArray['menuName']) && $dataArray['menuName'] != '')
				$menunamestr = $dataArray['menuName'];

			$viewpopup_str = '<a onclick="displaydeptform(\'' . BASE_URL . $dataArray['objectname'] . '/' . $viewaction . '/id/{{id}}' . $con . '/popup/1\',\'' . $menunamestr . '\')" name="{{id}}" class="sprite view"  title=\'View\'></a>';
			$editpopup_str = '<a id="edit{{id}}" onclick="displaydeptform(\'' . BASE_URL . $dataArray['objectname'] . '/' . $editaction . '/id/{{id}}' . $con . '/popup/1\',\'' . $menunamestr . '\')" name="{{id}}" class="sprite edit"  title=\'Edit\' ></a>';
			$deletepopup_str = '<a name="{{id}}" id="del{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';


			if (!in_array('view', $actions_arr) && !in_array('edit', $actions_arr) && !in_array('delete', $actions_arr)) {
				if ($dataArray['objectname'] == 'processes') {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								<a onclick="displaydeptform(\'' . BASE_URL . $dataArray['objectname'] . '/' . $viewaction . '/id/{{id}}' . $con . '/popup/1\',\'' . $menunamestr . '\')" name="{{id}}" class="sprite view"  title=\'View\'></a>
								<a onclick="displaydeptform(\'' . BASE_URL . $dataArray['objectname'] . '/' . $editaction . '/id/{{id}}' . $con . '/popup/1\',\'' . $menunamestr . '\')" name="{{id}}" class="sprite edit"  title=\'Edit\' ></a>
								<a name="{{id}}" id="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>
							</div>'];
				} else
					$extra['action'] = [];
			} else {

				if ($dataArray['objectname'] ==  'empleavesummary' || $dataArray['objectname'] ==  'empscreening') {
					$view_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
					$edit_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					$delete_str = '<a name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
				} else {
					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
									' . ((in_array('view', $actions_arr) ? $viewpopup_str : '')) . '
									' . ((in_array('edit', $actions_arr) ? $editpopup_str : '')) . '
									' . ((in_array('delete', $actions_arr) ? $deletepopup_str : '')) . '
								</div>'];
				}
			}
		} else {
			$formgridVal = '';
			$view_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
			if ($dataArray['objectname'] == 'appraisalconfig' || $dataArray['objectname'] == 'appraisalcategory' || $dataArray['objectname'] == 'appraisalquestions' || $dataArray['objectname'] == 'appraisalmanager' || $dataArray['objectname'] == 'feedforwardquestions' || $dataArray['objectname'] == 'announcements' || $dataArray['objectname'] == 'disciplinarymyincidents')
				$edit_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" id="edit{{id}}" class="sprite edit"  title=\'Edit\'></a>';

			else if ($dataArray['objectname'] == 'attendancereport') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];
				$edit_str = '<a id="edit{{mid}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{mid}}" name="{{mid}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{mid}}" name="{{mid}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/view/id/{{mid}}" name="{{mid}}" class="sprite view"  title=\'View\'></a>';


				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			} elseif ($dataArray['objectname'] == 'manageremployeevacations') {



				$edit_str = '<a  id="edit{{id}}"name="{{id}}" onclick= displaydeptform(\'' . BASE_URL . 'leaverequest/editpopup/id/{{id}}' . '\',\'\')	href= javascript:void(0) title=\'Approve or Reject or Cancel Leave\' class="fa fa-ellipsis-v" ></a>';
			} else {
				$edit_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
			}

			if ($dataArray['objectname'] == 'pendingleaves') {
				$delete_str = '<a id="cancel_leave_{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Cancel Leave\' class="sprite cancel-lev" ></a>';
			} else if ($dataArray['objectname'] == 'usermanagement')
				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
			else if ($dataArray['objectname'] == 'appraisalcategory' || $dataArray['objectname'] == 'appraisalquestions' || $dataArray['objectname'] == 'feedforwardquestions' || $dataArray['objectname'] == 'announcements') {
				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
			} else
				$delete_str = '<a  id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

			if (!in_array('view', $actions_arr) && !in_array('edit', $actions_arr) && !in_array('delete', $actions_arr)) {
				$extra['action'] = [];
			} else {
				//$delete_str = '<a id="del{{id}}"  name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
									</div>']; //onclick ="javascript:editlocdata(\'{{id}}\')" 
			}
			if ($dataArray['objectname'] == 'candidatedetails') {


				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					$schedule_str = '<a href= "' . BASE_URL . 'scheduleinterviews' . '/add/cid/{{id}}" name="{{id}}" class="sprite schedule_interview" id="cv{{id}}" title=\'Schedule interview\'></a>';
					$checkbox = '<a><input type="checkbox" value="{{id}}" class="check1" id="cv{{id}}"/></a>';
					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
											  ' . ((in_array('schedule', $actions_arr) ? '' : $schedule_str)) . '
											  ' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '
										</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
										</div>'];
				}
			}

			// 						if($dataArray['objectname'] == 'schedule')
			// {


			// 							$auth = Zend_Auth::getInstance();
			// 							if($auth->hasIdentity())
			// 							{
			// 								$loginUserId = $auth->getStorage()->read()->id;
			// 								$loginuserGroup = $auth->getStorage()->read()->group_id;
			// 								$loginuserRole = $auth->getStorage()->read()->emprole;
			// 							}
			// 							if($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP)
			// 							{
			// 								$schedule_str = '<a href= "'.BASE_URL.'scheduleinterviews'.'/add/cid/{{id}}" name="{{id}}" class="sprite schedule_interview" id="cv{{id}}" title=\'Schedule interview\'></a>';
			// 								$checkbox = '<a><input type="checkbox" value="{{id}}" class="check1" id="cv{{id}}"/></a>';
			// 								$extra['action'] = array('name' => 'edit', 'value' =>'<div class="grid-action-align">
			// 											'.((in_array('view',$actions_arr)?$view_str:'')).'
			// 											'.((in_array('edit',$actions_arr)?$edit_str:'')).'
			// 											'.((in_array('delete',$actions_arr)?$delete_str:'')).'
			// 											  '.((in_array('schedule',$actions_arr)?'':$schedule_str)).'
			// 											  '.((in_array('view',$actions_arr)?$checkbox:'')).'
			// 										</div>');
			// 							}
			// 							else{

			// 								$extra['action'] = array('name' => 'edit', 'value' =>'<div class="grid-action-align">
			// 											'.((in_array('view',$actions_arr)?$view_str:'')).'
			// 											'.((in_array('edit',$actions_arr)?$edit_str:'')).'
			// 											'.((in_array('delete',$actions_arr)?$delete_str:'')).'
			// 										</div>');
			// 							}
			// 						}


			if ($dataArray['objectname'] == 'logreport') {
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
										
										</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
										</div>'];
				}
			}
			if ($dataArray['objectname'] == 'perkapprove' and $dataArray['objectId'] == 'perkapprove') {

				$view_str = '<a href= "' . BASE_URL . 'perkapprove' . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
				$edit_str = '<a id="edit{{id}}" name="{{id}}" onclick= displaydeptform(\'' . BASE_URL . 'perkapprove/editpopup/id/{{id}}' . '\',\'\')	href= javascript:void(0) title=\'Approve or Reject \' class="fa fa-ellipsis-v" ></a>';
				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
					' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
					' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
					' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

				
			
				</div>'];
			}

			if ($dataArray['objectname'] == 'missedhoursapprove' and $dataArray['objectId'] == 'missedhoursapprove') {
				$view_str = '<a href= "' . BASE_URL . 'missedhoursapprove' . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
				$edit_str = '<a id="edit{{id}}" name="{{missed_hours_id}}" onclick= displaydeptform(\'' . BASE_URL . 'missedhoursapprove/editpopup/id/{{id}}' . '\',\'missedhoursapprove\')	href= javascript:void(0) title=\'Approve or Reject or Cancel Overtime\' class="fa fa-ellipsis-v" ></a>';
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
					' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
					' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
				</div>'];
			}

			if ($dataArray['objectname'] == 'addundum' and $dataArray['objectId'] == 'addundum') {
				$view_str = '<a href= "' . BASE_URL . 'addundum' . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
				$edit_str = '<a name="{{id}}" onclick= displaydeptform(\'' . BASE_URL . 'addundum/editpopup/id/{{id}}' . '\',\'\')	href= javascript:void(0) title=\'Approve or Reject or Cancel Overtime\' class="fa fa-ellipsis-v" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
					' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
					' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
				</div>'];
			}


			if ($dataArray['objectname'] == 'loadapprove' and $dataArray['objectId'] == 'loadapprove') {

				$view_str = '<a href= "' . BASE_URL . 'loadapprove' . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
				$edit_str = '<a  id="edit{{id}}" name="{{id}}" onclick= displaydeptform(\'' . BASE_URL . 'loadapprove/editpopup/id/{{id}}' . '\',\'\')	href= javascript:void(0) title=\'Approve or Reject or Cancel Overtime\' class="fa fa-ellipsis-v" ></a>';
				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
					' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
					' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
					' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

				
			
				</div>'];
			}


			if ($dataArray['objectname'] == 'perkcategory') {
				$edit_str = '<a href= "' . BASE_URL . 'perkcategory/edit/id/{{perkcategory_id}}" name="{{perkcategory_id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{perkcategory_id}}" name="{{perkcategory_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{perkcategory_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
													' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
													
												</div>'];
			}

			if ($dataArray['objectname'] == 'deductivecategory') {
				$edit_str = '<a href= "' . BASE_URL . 'deductivecategory/edit/id/{{load_category_id}}" name="{{load_category_id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{load_category_id}}" name="{{load_category_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{load_category_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
													' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
													
												</div>'];
			}

			if ($dataArray['objectname'] == 'addperk' and  $dataArray['objectId'] == 'add') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'addperk/edit/id/{{perk_id}}" name="{{perk_id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'addperk/view/id/{{perk_id}}" class="sprite view"  title=\'View\'></a>';
					$delete_str = '<a id="del{{perk_id}}" name="{{perk_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{perk_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

					$checkbox = '<a><input type="checkbox"  id="check{{perk_id}}" value="{{perk_id}}" name="{{auto_approved}}" class="check2" id="perk{{perk_id}}" onclick="oncheck({{perk_id}},\'addperk\')"/></a>';
					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
							
								' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
											' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

										</div>'];
				}
			}

			if ($dataArray['objectname'] == 'menucreate') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'menucreate/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					// $view_str = '<a href= "' . BASE_URL . 'perk/view/perk_id/{{perk_id}}" class="sprite view"  title=\'View\'></a>';
					$delete_str = '<a id="del{{id}}" name="{{perk_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

					$checkbox = '<a><input type="checkbox"  id="check{{perk_id}}" value="{{perk_id}}" name="{{auto_approved}}" class="check2" id="perk{{perk_id}}"/></a>';
					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
							
								' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
											' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

										</div>'];
				}
			}


			if ($dataArray['objectname'] == 'branch') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'branch/edit/id/{{emp_branch_id}}" name="{{emp_branch_id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'branch/view/id/{{emp_branch_id}}" class="sprite view"  title=\'View\'></a>';
					$delete_str = '<a id="del{{emp_branch_id}}" name="{{emp_branch_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{emp_branch_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
							
							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}
			if ($dataArray['objectname'] == 'location') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'location/edit/id/{{location_id}}" name="{{location_id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'location/view/id/{{location_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}
			if ($dataArray['objectname'] == 'theme') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'theme/edit/id/{{theme_id}}" name="{{theme_id}}" class="sprite edit"  title=\'Edit\'></a>';
					//$view_str = '<a href= "' . BASE_URL . 'theme/view/id/{{theme_id}}" class="sprite view"  title=\'View\'></a>';
					//$delete_str = '<a id="del{{theme_id}}" name="{{theme_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{theme_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								


							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
									
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

										</div>'];
				}
			}
			if ($dataArray['objectname'] == 'addresstype') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'addresstype/edit/id/{{address_type_id}}" name="{{address_type_id}}" class="sprite edit"  title=\'Edit\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}
			if ($dataArray['objectname'] == 'leavecategory') {

				$auth = Zend_Auth::getInstance();

				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'leavecategory/edit/id/{{leavecategory_id}}" name="{{leavecategory_id}}" class="sprite edit"  title=\'Edit\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}
			if ($dataArray['objectname'] == 'leave') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'leave/edit/id/{{leave_id}}" name="{{leave_id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'leave/view/id/{{leave_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}
			/* if ($dataArray['objectname'] == 'division') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'division/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'division/view/id/{{id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '

							</div>');
				} else {

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>');
				}
			} */
			if ($dataArray['objectname'] == 'empposition') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'empposition/edit/emp_id/{{emp_id}}/pos_id/{{jobposition_id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'empposition/view/emp_id/{{emp_id}}/pos_id/{{jobposition_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}


			if ($dataArray['objectname'] == 'jobtitles') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'jobtitles/edit/id/{{jobtitle_id}}" name="{{jobtitile_id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'jobtitles/view/id/{{jobtitle_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}
			if ($dataArray['objectname'] == 'positions') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'positions/edit/id/{{position_id}}" name="{{position_id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'positions/view/id/{{position_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}
			/* if ($dataArray['objectname'] == 'departments') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'departments/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'departments/view/id/{{id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '

							</div>');
				} else {

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>');
				}
			} */
			/* if ($dataArray['objectname'] == 'section') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'section/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'section/view/id/{{id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '

							</div>');
				} else {

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>');
				}
			} */
			/* if ($dataArray['objectname'] == 'team') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'team/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'team/view/id/{{id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '

							</div>');
				} else {

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>');
				}
			} */
			/* if ($dataArray['objectname'] == 'businessunits') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'businessunits/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'businessunits/view/id/{{id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '

							</div>');
				} else {

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>');
				}
			} */
			/* if ($dataArray['objectname'] == 'project') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'project/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'project/view/id/{{id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>');
				} else {

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>');
				}
			} */
			/* if ($dataArray['objectname'] == 'projectco') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'projectco/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'projectco/view/id/{{id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>');
				} else {

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>');
				}
			} */
			/* if ($dataArray['objectname'] == 'sbuengagment') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'sbuengagment/edit/bu_id/{{id}}/bd_id/{{bd_id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					// $view_str = '<a href= "' . BASE_URL . 'sbuengagment/view/bu_id/{{id}}/bd_id/{{bd_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>');
				} else {

					$extra['action'] = array('name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>');
				}
			} */

			if ($dataArray['objectname'] == 'companyengagment') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'companyengagment/edit/comp_id/{{comp_id}}/bd_id/{{bd_id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					// $view_str = '<a href= "' . BASE_URL . 'sbuengagment/view/bu_id/{{id}}/bd_id/{{bd_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}

			if ($dataArray['objectname'] == 'businessdomain') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'businessdomain/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
					// $view_str = '<a href= "' . BASE_URL . 'sbuengagment/view/bu_id/{{id}}/bd_id/{{bd_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}
			if ($dataArray['objectname'] == 'address') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'address/edit/id/{{address_id}}" name="{{address_id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'address/view/id/{{address_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}


			if ($dataArray['objectname'] == 'overtime' and  $dataArray['objectId'] == 'overtime') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					// Zend_Debug::dump($dataArray);
					$edit_str = '<a href= "' . BASE_URL . 'overtime/edit/id/{{overtime_id}}" name="{{overtime_id}}" class="sprite edit"  title=\'Edit\'></a>';
					$view_str = '<a href= "' . BASE_URL . 'overtime/view/id/{{overtime_id}}" class="sprite view"  title=\'View\'></a>';

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

							</div>'];
				} else {

					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

										</div>'];
				}
			}

			if ($dataArray['objectname'] == 'employee' and  $dataArray['objectId'] == 'employee') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				$edit_str = '<a  id="edit{{id}}" href= "' . BASE_URL . 'employee/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
				$view_str = '<a  id="view{{id}}" href= "' . BASE_URL . 'employee/view/id/{{id}}" class="sprite view"  title=\'View\'></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>'];
			}
			if ($dataArray['objectname'] == 'title') {
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				// Zend_Debug::dump($dataArray);
				$edit_str = '<a href= "' . BASE_URL . 'title/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';
				$view_str = '<a href= "' . BASE_URL . 'title/view/id/{{id}}" class="sprite view"  title=\'View\'></a>';
				// $delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

							</div>'];
			}
			if ($dataArray['objectname'] == 'bank') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];
				$edit_str = '<a id="edit{{bank_id}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{bank_id}}" name="{{bank_id}}" class="sprite edit"  title=\'Edit\'></a>';
				$delete_str = '<a id="del{{bank_id}}" name="{{bank_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{bank_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}
			if ($dataArray['objectname'] == 'doctype') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];
				$edit_str = '<a id="edit{{doctype_id}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{doctype_id}}" name="{{doctype_id}}" class="sprite edit"  title=\'Edit\'></a>';
				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{doctype_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
									</div>'];
			}

			if ($dataArray['objectname'] == 'adddeductive' and  $dataArray['objectId'] == 'add') {

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				// Zend_Debug::dump($dataArray);
				$edit_str = '<a href= "' . BASE_URL . 'adddeductive/edit/id/{{load_id}}" name="{{load_id}}" class="sprite edit"  title=\'Edit\'></a>';
				$view_str = '<a href= "' . BASE_URL . 'adddeductive/view/id/{{load_id}}" class="sprite view"  title=\'View\'></a>';
				$checkbox = '<a><input  id="check{{load_id}}" type="checkbox" value="{{load_id}}" name="{{autoapprove}}"  class="check2" id="dedductive{{load_id}}" onclick="oncheck({{load_id}},\'adddeductive\')"/></a>';
				$delete_str = '<a id="del{{load_id}}" name="{{load_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{load_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
								' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
								' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
								
								' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

							</div>'];
			}


			if ($dataArray['objectname'] == 'overtime' and  $dataArray['objectId'] == 'emp') {
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				// Zend_Debug::dump($dataArray);
				$checkbox = '<a><input type="checkbox" value="{{id}}"  class="check1"  id="emp{{id}}"/></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
							
								' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

							</div>'];
			}
			if ($dataArray['objectname'] == 'employee' and  $dataArray['objectId'] == 'emp') {
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				// Zend_Debug::dump($dataArray);
				$checkbox = '<a><input type="checkbox" value="{{id}}"  class="check1"  /></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
							
								' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

							</div>'];
			}
			if ($dataArray['objectname'] == 'manageremployeeovertime' and $dataArray['objectId'] == 'manageremployeeovertime') {

				$view_str = '<a href= "' . BASE_URL . 'manageremployeeovertime' . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
				$edit_str = '<a id="edit{{id}}" name="{{id}}" onclick= displaydeptform(\'' . BASE_URL . 'manageremployeeovertime/editpopup/id/{{id}}' . '\',\'\')	href= javascript:void(0) title=\'Approve or Reject or Cancel Overtime\' class="fa fa-ellipsis-v" ></a>';
				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectId'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
		' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
		' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
		' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

	

	</div>'];
			}
			if ($dataArray['objectname'] == 'schedule' and  $dataArray['objectId'] == 'emp') {
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}

				// Zend_Debug::dump($dataArray);
				$checkbox = '<a><input type="checkbox"  value="{{id}}" class="check1" id="emp{{id}}" /></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
							
								' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

							</div>'];
			}
			//




			//
			if ($dataArray['objectname'] == 'calendar') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];

				$edit_str = '<a id="edit{{id}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';


				$view_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';


				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										
									</div>'];
			}

			if ($dataArray['objectname'] == 'periodcontroltype') {

				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];
				$edit_str = '<a id="edit{{periodcontrol_id}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{periodcontrol_id}}" name="{{periodcontrol_id}}" class="sprite edit"  title=\'Edit\'></a>';
				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}
			if ($dataArray['objectname'] == 'period') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];

				$edit_str = '<a id="edit{{period_id}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';


				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
													' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
													' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
												</div>'];
			}
			if ($dataArray['objectname'] == 'year') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];

				$edit_str = '<a id="edit{{id}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';


				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
									</div>'];
			}

			if ($dataArray['objectname'] == 'schedule') {
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
							
								' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

							</div>'];
			}

			if ($dataArray['objectname'] == 'report') {


				// die("hghg");
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
							
								' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

							</div>'];
			}



			if ($dataArray['objectname'] == 'addperk' and  $dataArray['objectId'] == 'schedule') {
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
							
								' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '

							</div>'];
			}

			//for exit types grid and all exit types grid
			if ($dataArray['objectname'] == 'exittypes' || $dataArray['objectname'] == 'configureexitqs') {

				$view_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
				$edit_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" id="edit{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
										</div>'];
			}


			if ($dataArray['objectname'] == 'timetable') {

				$view_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
				$edit_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" id="edit{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
										</div>'];
			}

			if ($dataArray['objectname'] == 'payroll') {
				$edit_str = '<a href= "' . BASE_URL  . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" id="edit{{id}}" class="sprite edit"  title=\'Edit\'></a>';
				$approval_str = '<a name="{{id}}" href= ' . BASE_URL . $dataArray['objectname'] . '/editpopup/id/{{id}} title=\'Approve or Reject Payroll\' class="fa fa-ellipsis-v" ></a>';
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
					' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
					' . ((in_array('edit', $actions_arr) ? $approval_str : '')) . '
				
				
			
				</div>'];
			}

			if ($dataArray['objectname'] == 'listcandidates') {

				$view_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
				$edit_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" id="edit{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				//	$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\''.$dataArray['objectname'].'\',\'{{id}}\',\''.$msgdta.'\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
				$checkbox = '<a id="del{{id}}" name="{{id}}" href="#" ><input type="checkbox" value={{id}} class="check" /></a>';
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											
											' . ((in_array('view', $actions_arr) ? $checkbox : '')) . '
											
										</div>'];
			}
			//Zend_Debug::dump($dataArray['objectId']);





			if ($dataArray['objectname'] == 'allexitproc' || $dataArray['objectname'] == 'exitproc') {
				$view_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';

				/*if($dataArray['objectname'] == 'exitproc')
								$edit_str = '<a href= "'.BASE_URL.'exit'.'/'.$dataArray['objectname'].'/questions/id/{{id}}" name="{{id}}" id="questions{{id}}" class="sprite assign_view_questions"  title=\'Edit\'></a>';
							else
								$edit_str = '<a href= "'.BASE_URL.'exit'.'/'.$dataArray['objectname'].'/edit/id/{{id}}" name="{{id}}" id="edit{{id}}" class="sprite assign_view_questions"  title=\'Edit\'></a>';*/

				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
					$is_og_head = $auth->getStorage()->read()->is_orghead;
				}
				if ($dataArray['objectname'] == 'exitproc')
					$edit_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/questions/id/{{id}}" name="{{id}}" id="questions{{id}}" class="sprite assign_view_questions"  title=\'Answer and View Questions/Comments\'></a>';
				elseif ($loginuserRole != SUPERADMINROLE && $is_og_head != 1)
					$edit_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" id="edit{{id}}" class="sprite edit"  title=\'Edit\'></a>';
				else
					$edit_str = '';

				// assign questions icon only for hr, management and superadmin
				if (($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) && $dataArray['objectname'] == 'allexitproc') {
					$assign_questions_str = '<a href= "' . BASE_URL . 'exit' . '/' . $dataArray['objectname'] . '/assignquestions/id/{{id}}" name="{{id}}" class="sprite assign_view_questions"  id="assign_ques{{id}}"  title=\'Assign and View Questions/Comments\'></a>';
				} else {
					$assign_questions_str = '';
				}

				// no delete action for all exit process
				if ($loginuserRole == SUPERADMINROLE || $loginuserGroup == HR_GROUP || $loginuserGroup == MANAGEMENT_GROUP) {
					$delete_str = '<a name="{{id}}" id="overallupdate_{{id}}" onclick= displayexitform(\'' . BASE_URL . 'exit/allexitproc/editpopup/id/{{id}}' . '\',\'\')	href= javascript:void(0) title=\'Update Overall Status\' class="fa fa-ellipsis-v" ></a>';
				} else {
					$delete_str = '';
				}



				if ($dataArray['objectname'] == 'exitproc')
					$delete_str = '';
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
											' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
											' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
											' . ((in_array('assign', $actions_arr) ? '' : $assign_questions_str)) . '
											' . ((in_array('update', $actions_arr) ? '' : $delete_str)) . '
											
										</div>'];
			}
		}
		$extra['options'] = [];
		$addaction = '';
		if (isset($dataArray['add']) && $dataArray['add'] != '') {
			$addaction = $dataArray['add'];
		} else {
			$addaction = '';
		}
		$unitId = '';

		if (in_array('add', $actions_arr)) {
			$addpermission = "true";
		} else {
			$addpermission = "false";
		}
		if (isset($dataArray['unitId'])) $unitId = $dataArray['unitId'];

		/** capture category id, for policy documents context **/
		if (isset($dataArray['category_id'])) $this->pd_category_id = $dataArray['category_id'];

		/** capture category name, for policy documents context **/
		if (isset($dataArray['categoryName'])) $this->pd_category_name = $dataArray['categoryName'];


		return $this->generateGrid($dataArray['objectId'], $dataArray['objectname'], $dataArray['tableheader'], $paginator, $extra, true, $dataArray['jsGridFnName'], $dataArray['perPage'], $dataArray['pageNo'], $dataArray['jsFillFnName'], $dataArray['searchArray'], $formgridVal, $addaction, $menuName, $unitId, $addpermission, $menunamestr, $dataArray['call'] ?? "", $sortStr, $dataArray['search_filters'] ?? "", $dataArray['dashboardcall'] ?? "No", $dataArray['empstatus'] ?? "", $actnArr, $dataArray['empscreentotalcount'] ?? "", $dataArray['sort'] ?? "", $dataArray['by'] ?? "", $dataArray['perm'] ?? "");
	}

	/**
	 *
	 * @param string $name
	 * @param array $fields
	 * @param Zend_Paginator Instance $paginator
	 * @param array $extracolumn
	 * @param Bool  $sorting
	 *
	 * @return string
	 */

	public function generateGrid($objectId = null, $name = null, $fields = null, $paginator = null, $extracolumn = [], $sorting = false, $jsGridFnname = '', $perPage = '5', $page = '1', $jsFillFnName = '', $searchArray = '', $formgrid = 'false', $addaction = '', $menuName = '', $unitId = null, $addpermission = null, $menunamestr = null, $call = '', $sortStr = '', $search_filters = '', $dashboardCall = 'No', $empstatus = '', $actnArr = null, $empscreenTotalCount = '', $sortname = '', $by = '', $perm = '')
	{

		$request = Zend_Controller_Front::getInstance();
		$params = $request->getRequest()->getParams();
		$controllerName = $request->getRequest()->getControllerName();

		$actionName = $request->getRequest()->getActionName();
		$dataclass = '';
		// Store Extra Columns
		$this->extra = $extracolumn;
		$sortIconStr = "";

		$sort = Zend_Controller_Front::getInstance()->getRequest()->getParam('sort', 'DESC');
		// checking and handling sorting.


		if ($sort == "") {
			$sortIconStr = "<span class='s-ico'>
			<span class='ui-icon-desc ui-state-disabled ui-icon ui-icon-triangle-1-n'></span>
			<span class='ui-icon-asc ui-state-disabled ui-icon ui-icon-triangle-1-s'></span></span>";
		} else if ($sort  ==  'ASC') {
			$sort = 'DESC';
			//For Sort Icons....
			$sortIconStr = "<span class='s-ico'>
			<span class='ui-icon-desc ui-icon ui-icon-triangle-1-n'></span>
			</span>";
		} else {
			$sort = 'ASC';
			//For Sort Icons....
			$sortIconStr = "<span class='s-ico'>
			<span class='ui-icon-asc  ui-icon ui-icon-triangle-1-s'></span></span>";
		}


		if ($call != "ajaxcall")		$sortIconStr = "";


		if ($addaction != '') {
			$action = $addaction;
			$popupaction = 'addpopup';
		} else {
			$action = "edit";
			$popupaction = 'editpopup';
		}


		$con = '';
		//Zend_Debug::dump($name);
		if ($formgrid != '') {
			$urlString = trim($_SERVER['REQUEST_URI']);
			$serverUrl = $_SERVER['HTTP_HOST'];

			$urlData = explode('/', $urlString);
			if ($unitId != '')
				$con = 'unitId/' . $unitId;
			else {

				if (isset($params['id']))
					$con = 'unitId/' . $params['id'];

				//if(isset($params['perk_id']))
				//$con = 'unitId/'.$params['perk_id'];
			}


			if ($name == 'empscreening') {
				$empaction = 'add';
				$output = "<div class='table-header'><span>" . $menuName . "</span><a href='" . BASE_URL . $name . '/' . $empaction . "'><input type='button' title = 'Adds'  value='Add Record' class='sprite addrecord' /></a></div>";
			} else {
				if ($name == 'processes' && $empstatus != 'Active' && $empstatus != '')
					$output = "<div class='table-header'><span>" . $menuName . "</span></div>";
				else
					$output = "<div class='table-header'><span>" . $menuName . "</span><input type='button' title = 'Add1'  onclick='displaydeptform(\"" . BASE_URL . $name . '/' . $popupaction . "/$con/popup/1\",\"" . $menunamestr . "\")' value='Add Record' class='sprite addrecord' /></div>";
			}
		} else {
			if ($name == 'policydocuments' && !empty($this->pd_category_name))
				$output = "<div class='table-header'><span>" . $this->pd_category_name . "</span>";
			else
				$output = "<div class='table-header'><span>" . $menuName . "</span>";
			if ($name == 'candidatedetails') {
				$output .= "<div class='add-multi-resume'><a href='" . BASE_URL . "candidatedetails/multipleresume'>Add multiple CVs</a></div>";
			}
			if ($name == 'policydocuments') {
				$output .= "<a href='" . BASE_URL . $name . '/' . $action . "/cat/" . $this->pd_category_id . "'><input type='button' title = 'Add' value='Add Record' class='sprite addrecord' /></a>";
			} elseif ($name == 'disciplinaryincident') {
				$output .= "<div class='add-btn-div'>";
				$output .= "<input type='button' onclick='window.location.href=\"" . BASE_URL . $name . '/' . $action . "\"' title = 'Raise an Incident' value='Raise an Incident' class='sprite addrequest' />";
				$output .= "</div>";
			} elseif ($name == 'exittypes' || $name == 'configureexitqs') {
				$output .= "<a href='" . BASE_URL . 'exit' . '/' . $name . '/' . $action . "'><input type='button' title = 'Add' value='Add Record' class='sprite addrecord' /></a>";
			}

			/* elseif ($name == 'timetable') {
				$output .= "<a href='" . BASE_URL . 'exit' . '/' . $name . '/' . $action . "'><input type='button' title = 'Add' value='Add Record' class='sprite addrecord' /></a>";
			} */ elseif ($objectId == 'emp') {
				$output .= "";
			} elseif ($objectId == 'employee') {
				$output .= "";
			}


			// elseif($name == 'doctype'){
			// 	$output .= "<a href='" . BASE_URL . $name . '/add' . "'><input type='button' title = 'Add' value='Add Record' class='sprite addrecord' /></a>";
			// } 
			// elseif($name == 'location'){
			// 	$output .= "<a href='" . BASE_URL . $name . '/add' . "'><input type='button' title = 'Add' value='Add Record' class='sprite addrecord' /></a>";
			// }
			// elseif ($objectId == 'report') {
			// 	$output ='';
			// } 






			elseif ($name == 'addperk') {
				$output .= "<a href='" . BASE_URL . 'addperk/perk' . "'><input type='button' title = 'Add' value='Add Record' class='sprite addrecord' /></a>";
			} elseif ($name == 'allexitproc') {
			} else if ($name == 'exitproc') {
				$auth = Zend_Auth::getInstance();
				if ($auth->hasIdentity()) {
					$loginUserId = $auth->getStorage()->read()->id;
					$loginuserGroup = $auth->getStorage()->read()->group_id;
					$loginuserRole = $auth->getStorage()->read()->emprole;
				}
				if ($loginuserRole == SUPERADMINROLE)
					$output .= "";
				else
					$output .= "<a href='" . BASE_URL . 'exit' . '/' . $name . '/' . $action . "'><input type='button' title = 'Add' value='Add Record' class='sprite addrecord' /></a>";
			} else {
				$output .= "<a href='" . BASE_URL . $name . '/' . $action . "'><input type='button' title = 'Add' value='Add Record' class='sprite addrecord' /></a>";
			}
			$output .= "</div>";
		}

		if ($addpermission == 'false') {
			if ($name == 'policydocuments' && !empty($this->pd_category_name))
				$output = "<div class='table-header'><span>" . $this->pd_category_name . "</span></div>";
			else
				$output = "<div class='table-header'><span>" . $menuName . "</span></div>";
		}
		$output .= "<div id='" . $name . "' class='details_data_display_block newtablegrid'>";
		$output .= "<table class='grid' align='center'  width='100%' cellspacing='0' cellpadding='4' border='0'><thead><tr>";
		// this foreach loop display the column header  in �th� tag.
		$colinr = 0;
		if (!empty($fields)) {
			$tabindx = 0;
			if (empty($actnArr)) unset($fields['action']);
			foreach ($fields as $key => $value) {

				if (isset($value['align'])) $align = (@$value['align'] != '') ? 'align="' . $value['align'] . '" ' : '';
				if (isset($value['sortkey'])) $sortkey = (@$value['sortkey'] != '') ? 'align="' . $value['sortkey'] . '" ' : '';

				if (isset($value['style'])) $style = (@$value['style'] != '') ? 'style="' . $value['style'] . '" ' : '';

				$value = (is_array($value) && !isset($value['sortkey'])) ? $value['value'] : $value;
				if ($value == 'Action') $width = 'width=90';
				else $width =  ''; //'width='.$eachColumnWidth;
				$output .= "<th " . $width . ">";

				// Check if Sorting is set to True
				if ($sorting) {

					// Disable Sorting if Key is in Extra Columns
					if (@$this->extra[$key]['name'] != '' && !is_array($value)) {
						if ($value == "Action")
							$output .= "<span class='action-text'>Action</span>";
						else
							$output .= $value;
					} else {
						if (is_array($value)) {
							$key = $value['sortkey'];
							$value = $value['value'];
						}
						$welcome = 'false';
						$urlString = $_SERVER['REQUEST_URI'];
						if (str_contains($urlString, 'welcome') || str_contains($urlString, 'pendingleaves')) {
							$welcome = 'true';
						}
						if ($formgrid == 'true') {


							if ($value == 'Explanation' && $name = "processes") {

								$output .= "<a href='javascript:void(0);' onclick=javascript:paginationndsorting('" . BASE_URL . $name . "/index/sort/" . $sort . "/by/" . $key . "/objname/" . $name . "/page/" . $page . "/per_page/" . $perPage . "/call/ajaxcall/$con/');>" . $value . "<img title='Reason to complete the process.' src='" . DOMAIN . "public/media/images/help.png' /></a>";
							} else {
								$output .= "<a href='javascript:void(0);' onclick=javascript:paginationndsorting('" . BASE_URL . $name . "/index/sort/" . $sort . "/by/" . $key . "/objname/" . $name . "/page/" . $page . "/per_page/" . $perPage . "/call/ajaxcall/$con/');>" . $value . "</a>";
							}
							//For Sort Icons....
							if ($key == $sortStr)
								$output .= $sortIconStr;
						} else if ($welcome == 'true') {

							$output .= "<a href='javascript:void(0);' onclick=javascript:paginationndsorting('" . BASE_URL . $name . "/index/sort/" . $sort . "/by/" . $key . "/objname/" . $name . "/page/" . $page . "/per_page/" . $perPage . "/call/ajaxcall/$con/');>" . $value . "</a>";
							//For Sort Icons....
							if ($key == $sortStr)
								$output .= $sortIconStr;
						} else if ($name == 'policydocuments') {
							$output .= "<a href='javascript:void(0);' onclick=javascript:paginationndsorting('" . BASE_URL . $name . "/id/" . $this->pd_category_id . "/sort/" . $sort . "/by/" . $key . "/objname/" . $name . "/page/" . $page . "/per_page/" . $perPage . "/call/ajaxcall/$con/');>" . $value . "</a>";
							//For Sort Icons....
							if ($key == $sortStr)
								$output .= $sortIconStr;
						} else {

							$output .= "<a href='javascript:void(0);' onclick=javascript:paginationndsorting('" . $this->view->url(['sort' => $sort, 'by' => $key, 'objname' => $name, 'page' => $page, 'per_page' => $perPage]) . "');>" . $value . "</a>";
							//For Sort Icons....
							if ($key == $sortStr)
								$output .= $sortIconStr;
						}
					}
				} else {
					//For Sort Icons....
					if ($key == $sortStr)
						$output .= $sortIconStr;
					$output .= $value;
				}

				$output .= "</th>";
				$colinr++;
				$tabindx++;
			} //end of for each loop
			$output .= "</tr><tr id='search_tr_$name'>";
			$tabindx = 0;
			foreach ($fields as $key => $value) {

				if (isset($value['align'])) $align = (@$value['align'] != '') ? 'align="' . $value['align'] . '" ' : '';
				if (isset($value['sortkey'])) $sortkey = (@$value['sortkey'] != '') ? 'align="' . $value['sortkey'] . '" ' : '';

				if (isset($value['style'])) $style = (@$value['style'] != '') ? 'style="' . $value['style'] . '" ' : '';

				$value = (is_array($value) && !isset($value['sortkey'])) ? $value['value'] : $value;
				if ($value == 'Action') $width = 'width=90';
				else $width =  '';
				$output .= "<th " . $width . ">";
				// Check if Sorting is set to True
				if ($sorting) {

					// Disable Sorting if Key is in Extra Columns
					if (@$this->extra[$key]['name'] != '' && !is_array($value)) {
						if ($value == "Action")
							$output .= "<span class='action-text'></span>";
						else
							$output .= $value;
					} else {
						if (is_array($value)) {
							$key = $value['sortkey'];
							$value = $value['value'];
						}
						$welcome = 'false';
						$urlString = $_SERVER['REQUEST_URI'];
						if ($key != 'id' && $key != 'initialize_status' && $key != 'appraisal_process_status') {
							$sText = '';

							if (!empty($searchArray)) $display = 'display: block;';
							else $display = 'display: none;';

							if ($controllerName == 'myemployees' && $actionName == 'index')
								$display = '';

							if (is_array($searchArray)) {
								if (array_key_exists($key, $searchArray)) $sText = $searchArray[$key];
								else $sText = '';
							}

							if (isset($search_filters[$key])) {

								$search_function =  'getsearchdata("' . $name . '","",this.id,event';
								$output .= sapp_Global::grid_data($search_filters, $key, $name, $display, $sText, $tabindx, $search_function);
							} else {
								$output .= "<input tabIndex=$tabindx type='text' name='$name' id='$key' style='$display' class='searchtxtbox_$name table_inputs grid_search_inputs' value=\"$sText\" onKeypress='if (!((event.keyCode || event.which) > 47 && (event.keyCode || event.which) < 58) && !((event.keyCode || event.which) > 64  && (event.keyCode || event.which) < 91)  && !((event.keyCode || event.which) > 96  && (event.keyCode || event.which) < 123) && ((event.keyCode || event.which) != 45) && ((event.keyCode || event.which) != 63) && ((event.keyCode || event.which) != 39) && ((event.keyCode || event.which) != 46) && ((event.keyCode || event.which) != 44) && ((event.keyCode || event.which) != 47) && ((event.keyCode || event.which) != 35) && ((event.keyCode || event.which) != 64) && ((event.keyCode || event.which) != 36) && ((event.keyCode || event.which) != 38) && ((event.keyCode || event.which) != 42) && ((event.keyCode || event.which) != 40) && ((event.keyCode || event.which) != 41) && ((event.keyCode || event.which) != 33) && ((event.keyCode || event.which) != 32) && ((event.keyCode || event.which) != 8)) event.preventDefault();' onkeydown='getsearchdata(\"$name\",\"\",this.id,event,\"text\",\"objectId\")' />";
							}
						}
					}
				} else {
					//For Sort Icons....
					if ($key == $sortStr)
						$output .= $sortIconStr;
					$output .= $value;
				}
				$output .= "</th>";
				$colinr++;
				$tabindx++;
			} //end of for each loop
		}
		$output .= "</tr>
        </thead>";
		$output .= "<tbody>";
		// Start Looping Data
		$ii = 0;
		foreach ($paginator as $p) {
			$cell_color = ($ii % 2 == 0 ? "row1" : "row2");
			$ii++;
			$bodyCount = 0;
			$output .= "<tr onclick='selectrow($name,this);' class='$cell_color'>";
			// Reset Fields Array to Top
			if (!empty($fields)) {
				reset($fields);
				foreach ($fields as $k => $v) {
					$tdclass = '';
					// Look for additional attributes
					$characterlimit = 40;
					if (is_array($v)) {
						$class = (@$v['class'] != '') ? 'class="' . $v['class'] . '" ' : '';
						$align = (@$v['align'] != '') ? 'align="' . $v['align'] . '" ' : '';
						$valign = (@$v['valign'] != '') ? 'valign="' . $v['valign'] . '" ' : '';
						if (isset($v['characterlimit']))
							$characterlimit = $v['characterlimit'];
						$output .= "<td {$tdclass}{$align}{$valign}>";
					} else {
						if ($k == 'description' && $menuName == 'Screening Types')
							$characterlimit = 80;
						$output .= "<td {$tdclass}>";
					}
					// Check to see if this Field is in Extra Columns
					if (isset($this->extra[$k]['value'])) {
						$output .= $this->_parseExtra($k, $p);
					} else {
						if ($bodyCount == 0 && $jsFillFnName != '') {
							$valToInclude = (strlen($p[$k]) > $characterlimit) ? substr($p[$k], 0, $characterlimit) . ".." : $p[$k];
							$output .= "<a onclick= " . $jsFillFnName . "(\"/id/$p[id]\") href= 'javascript:void(0)' title='" . htmlentities(trim($p[$k]), ENT_QUOTES, "UTF-8") . "' >" .  htmlentities($valToInclude, ENT_QUOTES, "UTF-8") . "</a>";
						} else {

							$p = (array)$p;
							if (isset($p[$k])) {

								// Customize grid field data
								switch ($menuName) {
									case 'Announcements':
										switch ($k) {
											// Strip tags
											case 'description':
												$stip_tags_text = strip_tags($p[$k]);
												$valToInclude = (strlen($stip_tags_text) > $characterlimit) ? substr($stip_tags_text, 0, $characterlimit) . ".." : $stip_tags_text;
												break;
											default:
												$valToInclude = (strlen($p[$k]) > $characterlimit) ? substr($p[$k], 0, $characterlimit) . ".." : $p[$k];
												break;
										}
										break;
									case 'View/Manage Policy Documents': //case to handle file names in policy documents module
										switch ($k) {
											// Strip tags
											case 'description':
												$stip_tags_text = strip_tags($p[$k]);
												$valToInclude = (strlen($stip_tags_text) > $characterlimit) ? substr($stip_tags_text, 0, $characterlimit) . ".." : $stip_tags_text;
												break;
											case 'file_name':
												$valToInclude = $p[$k];
												break;
											default:
												$valToInclude = (strlen($p[$k]) > $characterlimit) ? substr($p[$k], 0, $characterlimit) . ".." : $p[$k];
												break;
										}
										break;
									default:
										$valToInclude = (strlen($p[$k]) > $characterlimit) ? substr($p[$k], 0, $characterlimit) . ".." : $p[$k];
										break;
								}

								if ($k == 'isactive' && ($p[$k] == 'Process deleted' || $p[$k] == 'Agency User deleted' || $p[$k] == 'POC deleted' || $p[$k] == 'Agency deleted') && $menuName == 'Background check Process') {
									$dataclass = 'class="reddata"';
									echo "<script type='text/javascript'>
											$(document).ready(function() { 
											$('#del'+" . $p['id'] . ").remove();
											$('#edit'+" . $p['id'] . ").remove();
											});
											</script>";
								} else if ($k == 'userfullname') {
									$errorImg = DOMAIN . 'public/media/images/profile_pic.png';
									if (isset($p['profileimg']) && !empty($p['profileimg'])) {
										$imageUrl = DOMAIN . 'public/uploads/profile/' . $p['profileimg'];
									} else {
										$imageUrl = DOMAIN . 'public/media/images/profile_pic.png';
									}
									$imgtag = '<img src="' . $imageUrl . '" onerror="this.src=\'' . $errorImg . '\'" class="pic-grid">';
								} else if ($menuName == 'Manage External Users') {
									echo "<script type='text/javascript'>
											$(document).ready(function() { 
											$('#del'+" . $p['id'] . ").remove();
											});
											</script>";
								}
								if ($empstatus != 'Active' && $empstatus != '' && $menuName == 'Background check Process') {
									echo "<script type='text/javascript'>
											$(document).ready(function() { 
											$('#del'+" . $p['id'] . ").remove();
											$('#edit'+" . $p['id'] . ").remove();
											});
											</script>";
								}



								if ($controllerName == 'addperk'  && $objectId = 'add' && ($p['type'] == 'Individual' || $p['isactive'] == 'No')) {
									//die($p['approved']);
									//Zend_Debug::dump($p['perk_id']);
									echo "<script type='text/javascript'>
												$(document).ready(function() {
												$('#check'+" . $p['perk_id'] . ").remove();
												});
												</script>";
								}
								if ($controllerName == 'menucreate' && $p['isactive'] == 0) {
									//die($p['approved']);
									//Zend_Debug::dump($p['perk_id']);
									echo "<script type='text/javascript'>
												$(document).ready(function() {
												$('#del'+" . $p['id'] . ").remove();
												});
												</script>";
								}

								if (($controllerName == 'adddeductive' && $objectId == 'add') && ($p['type'] == 'Individual' || $p['isactive'] == 'No')) {
									//die($p['isactive']);
									//Zend_Debug::dump($p['load_id']);
									echo "<script type='text/javascript'>
							 		$(document).ready(function() {
							 		$('#check'+" . $p['load_id'] . ").remove();
									});
							 		</script>";
								}
								if ($k == 'backgroundchk_status' && $p[$k] == 'Complete' && $menuName == 'Employee/Candidate Screening') {
									$dataclass = 'class="greendata"';
								}
								if ($controllerName == 'appraisalmanager' && ($p['org_status'] == 2 || $p['org_status'] == 3 || $p['enable_step'] == 2 || $p['managerid_status'] != 0)) {
									//echo '<pre>';print_r($p);
									//if($p['org_status'] == 2 || $p['org_status'] == 3 || $p['enable_step']==2 || $p['managerid_status']!=0)
									//{
									echo "<script type='text/javascript'>
												$(document).ready(function() { 
												$('#del" . $p['id'] . "').remove();
												$('#edit'+" . $p['id'] . ").remove();
												});
												</script>";
									//}
								}
								if ($controllerName == 'appraisalquestions' || $controllerName == 'appraisalcategory' || $controllerName == 'feedforwardquestions') {
									if ($p['isused'] == 1) {
										echo "<script type='text/javascript'>
												$(document).ready(function() { 
												$('#del" . $p['id'] . "').remove();
												$('#edit'+" . $p['id'] . ").remove();
												});
												</script>";
									}
								}
								if ($controllerName == 'pendingleaves' && isset($p['approved_cancel_flag'])) {
									die("llll");
									if ($p['approved_cancel_flag'] == 'no') {
										echo "<script type='text/javascript'>
												$(document).ready(function() { 
												$('#cancel_leave_" . $p['id'] . "').remove();
												});
												</script>";
									}
								}

								if ($controllerName == 'disciplinarymyincidents' && isset($p['incident_status']) && isset($p['dateexpired'])) {
									if ($p['incident_status'] != 'Initiated' || $p['dateexpired'] == 'expired') {
										echo "<script type='text/javascript'>
												$(document).ready(function() { 
												$('#edit" . $p['id'] . "').remove();
												});
												</script>";
									}
								}
								/** added on 27-04-2015 by sapplica
								 **  to remove edit buttons in appraisal settings page when status is not empty
								 **/
								if ($controllerName == 'appraisalconfig' && $p['status'] != '') {
									echo "<script type='text/javascript'>
												$(document).ready(function() { 
												$('#edit'+" . $p['id'] . ").remove();
												});
												</script>";
								}

								if ($controllerName == 'candidatedetails' && $p['cand_status'] != 'Not Scheduled') {

									echo "<script type='text/javascript'>
												$(document).ready(function() {
											 
												$('#cv'+" . $p['id'] . ").remove();
												});
												</script>";
								}

								// if ($controllerName == 'perkapprove' && $perm[$p['emp_id']]['permission']=="N") {
								// 	echo "<script type='text/javascript'>
								// 				$(document).ready(function() {

								// 				$('#edit'+" . $p['id'] . ").remove();
								// 				});
								// 				</script>";
								// }




								if ($controllerName == 'manageremployeevacations') {
									if ($p['status'] != 'Pending') {
										echo "<script type='text/javascript'>
												$(document).ready(function() {
												$('#edit'+" . $p['id'] . ").remove();
												});
												</script>";
									}
									if ($p['approved'] == 'Yes') {
										echo "<script type='text/javascript'>
															$(document).ready(function() {
															$('#del'+" . $p['id'] . ").remove();
															});
															</script>";
									}

									if ($perm[$p['emp_id']]['permission'] == "N") {
										echo "<script type='text/javascript'>
																	$(document).ready(function() {
																		$('#edit'+" . $p['id'] . ").remove();
																	$('#del'+" . $p['id'] . ").remove();
																	});
																	</script>";
									}
								}

								if (($controllerName == 'addperk' || $controllerName == 'adddeductive') &&  ($perm[$p['id']]['permission'] == "N")) {
									echo "<script type='text/javascript'>
												$(document).ready(function() {
													$('#emp'+" . $p['id'] . ").remove();

												});
												</script>";
								}
								if (($controllerName == 'missedhoursapprove' || $controllerName == 'manageremployeeovertime' || $controllerName == 'loadapprove' || $controllerName == 'perkapprove')  &&  ($p['approved'] == 'Yes' || $p['approved'] == 'Approved' || $perm[$p['emp_id']]['permission'] == "N")) {
									echo "<script type='text/javascript'>
												$(document).ready(function() {
												$('#del'+" . $p['id'] . ").remove();
												$('#edit'+" . $p['id'] . ").remove();

												});
												</script>";
								}
								// remove edit and delete if isused=1
								if ($controllerName == 'exittypes' || $controllerName == 'configureexitqs') {
									if ($p['isused'] == 1) {
										echo "<script type='text/javascript'>
												$(document).ready(function() {
												$('#del" . $p['id'] . "').remove();
												$('#edit'+" . $p['id'] . ").remove();
												});
												</script>";
									}
								}

								/* if($controllerName == 'listcandidates' || $controllerName == 'configureexitqs')
								{
									if($p['isused'] == 1)
									{
										echo "<script type='text/javascript'>
												$(document).ready(function() {
												$('#del".$p['id']."').remove();
												$('#edit'+".$p['id'].").remove();
												});
												</script>";
									}
								} */


								//hide edit when overall status is approved in exitprocess
								if ($controllerName == 'allexitproc' && $p['overall_status'] == 'Approved') {
									echo "<script type='text/javascript'>
												$(document).ready(function() {
											 
												$('#edit'+" . $p['id'] . ").remove();
												});
												</script>";
									echo "<script type='text/javascript'>
												$(document).ready(function() {
											 
												$('#overallupdate_'+" . $p['id'] . ").remove();
												});
												</script>";
								}
								//hide edit when overall status is approved in exitprocess
								if ($controllerName == 'allexitproc' && $p['overall_status'] != 'Approved') {
									echo "<script type='text/javascript'>
												$(document).ready(function() {
								
												$('#assign_ques'+" . $p['id'] . ").remove();
												});
												</script>";
								}

								if ($controllerName == 'exitproc' && $p['overall_status'] != 'Approved') {
									echo "<script type='text/javascript'>
												$(document).ready(function() {
											 
												$('#questions'+" . $p['id'] . ").remove();
												});
												</script>";
								}

								// hr can edit and delete any businessunit and dept announcemets---3.1
								//removing edit,dele icons for announcements
								/* if($controllerName == 'announcements')
								{
									//echo "<pre>";print_r($p);
									$businessunit_id = $department_id ='';
									$auth = Zend_Auth::getInstance();
			     						if($auth->hasIdentity())
			     						{
											$loginUserId = $auth->getStorage()->read()->id;
											$loginuserRole = $auth->getStorage()->read()->emprole;
											$loginuserGroup = $auth->getStorage()->read()->group_id;
											
										}
										//checking roles and Removing Edit & Delete previliges
										if(($loginuserGroup == HR_GROUP ) && (!empty($businessunit_id) || !empty($department_id)))
										{
											$bunitArr = isset($p['businessunit_id'])? explode(',',$p['businessunit_id']):array();
											$deptArr = isset($p['department_id'])? explode(',',$p['department_id']):array();
											
												if(!in_array($businessunit_id,$bunitArr) && !in_array($department_id,$deptArr))
												{
													echo "<script type='text/javascript'>
													$(document).ready(function() { 
													$('#del".$p['id']."').remove();
													$('#edit'+".$p['id'].").remove();
													});
													</script>";
												}
											
											
										}
									
								} */

								// 
								/**
								 * Customize grid fields data - START
								 * In the below code, First Case - Menu name
								 * Second Case - Grid field
								 * Keep 'default' case to allow display other Grids, normal.
								 */
								// die($menuName);
								switch ($menuName) {
									case 'CV Management':
										switch ($k) {
											case 'cand_resume':
												if (!empty($valToInclude)) {
													$output .= "<span " . $dataclass . " title='" . htmlentities(trim($p[$k]), ENT_QUOTES, "UTF-8") . "' ><a href='" . BASE_URL . $name . '/download/id/' . $p["id"] . "'>View Resume</a></span>";
												}
												break;
											default:
												$output .= "<span " . $dataclass . " title='" . htmlentities(trim($p[$k]), ENT_QUOTES, "UTF-8") . "' >" . htmlentities($valToInclude, ENT_QUOTES, "UTF-8") . "</span>";
												break;
										}
										break;

									// Strip HTML tags for 'description' field in Organization->Announcements grid
									case 'Announcements':
										switch ($k) {
											case 'description':
												$output .= "<span " . $dataclass . " title='" . html_entity_decode($p[$k]) . "' >" . html_entity_decode($valToInclude) . "</span>";

												break;
											default:
												$output .= "<span " . $dataclass . " title='" . htmlentities(trim($p[$k]), ENT_QUOTES, "UTF-8") . "' >" . htmlentities($valToInclude, ENT_QUOTES, "UTF-8") . "</span>";
												break;
										}
										break;
									case 'View/Manage Policy Documents': //case to handle file names in policy documents module
										switch ($k) {
											case 'description':
												$output .= "<span " . $dataclass . " title='" . htmlentities(strip_tags(trim($p[$k])), ENT_QUOTES, "UTF-8") . "' >" . htmlentities($valToInclude, ENT_QUOTES, "UTF-8") . "</span>";
												break;
											case 'file_name':
												$tmpFiles = json_decode($valToInclude);
												if ($tmpFiles[0]) {
													$strFileName = (strlen($tmpFiles[0]->original_name) > 40) ? substr($tmpFiles[0]->original_name, 0, 37) . "..." : htmlentities($tmpFiles[0]->original_name, ENT_QUOTES, "UTF-8");
													$output .= "<a target='_blank' title='" . htmlentities(strip_tags(trim($tmpFiles[0]->original_name)), ENT_QUOTES, "UTF-8") . "' href='" . POLICY_DOC_PATH . $tmpFiles[0]->new_name . "'>" . $strFileName . "</a>";
												} else {
													$output .= "<span " . $dataclass . "></span>";
												}
												break;
											default:
												$output .= "<span " . $dataclass . " title='" . htmlentities(trim($p[$k]), ENT_QUOTES, "UTF-8") . "' >" . htmlentities($valToInclude, ENT_QUOTES, "UTF-8") . "</span>";
												break;
										}
										break;
									case 'All Exit Procedures':
										if ($k == 'initiateddate') {
											$output .= "<span " . $dataclass . ">" . sapp_Global::change_date($p[$k], 'view') . "</span>";
										} else {
											$output .= "<span " . $dataclass . " title='" . htmlentities(trim($p[$k]), ENT_QUOTES, "UTF-8") . "' >" . htmlentities($valToInclude, ENT_QUOTES, "UTF-8") . "</span>";
										}
										break;
									case 'Initiate/Check Status':
										if ($k == 'initiateddate') {
											$output .= "<span " . $dataclass . ">" . sapp_Global::change_date($p[$k], 'view') . "</span>";
										} else {
											$output .= "<span " . $dataclass . " title='" . htmlentities(trim($p[$k]), ENT_QUOTES, "UTF-8") . "' >" . htmlentities($valToInclude, ENT_QUOTES, "UTF-8") . "</span>";
										}
										break;
									case 'Employees List' || 'Employee Overtime Request List' || 'Attendance Report' || 'My Leave' || 'Candidates':
										if ($k == 'userfullname') {
											$output .= $imgtag . " <span " . $dataclass . " title='" . htmlentities($p[$k], ENT_QUOTES, "UTF-8") . "' class='emp-name' >" . htmlentities($valToInclude, ENT_QUOTES, "UTF-8") . "</span>";
										} else {
											$output .= "<span " . $dataclass . " title='" . htmlentities(trim($p[$k]), ENT_QUOTES, "UTF-8") . "' >" . htmlentities($valToInclude, ENT_QUOTES, "UTF-8") . "</span>";
										}
										break;
									default:
										// $output .= $imgtag." <span ".$dataclass." title='".htmlentities($p[$k], ENT_QUOTES, "UTF-8")."' class='emp-name' >".htmlentities($valToInclude, ENT_QUOTES, "UTF-8")."</span>";

										$output .= "<span " . $dataclass . " title='" . trim($p[$k]) . "' >" . htmlentities($valToInclude, ENT_QUOTES, "ISO-8859-1") . "</span>";
										break;
								}
								// Customize grid fields data - END					htmlentities(trim($p[$k]), ENT_QUOTES, "ISO-8859-1")		
							}
						}
					}
					$dataclass = '';
					$output .= "</td>";
					$bodyCount++;
				}
			}
			// Close the Table Row
			$output .= "</tr>";
		}
		if ($ii == 0) {
			$output .= "<tr><td colspan='$colinr' class='no-data-td'><p class='no-data'>No data found.</p></td></tr>";
		}
		$output .= "</tbody>";
		$output .= "</table>
                <script type='text/javascript' language='javascript'>
                $(document).ready(function(){
                                                                        
                                    if($('.searchtxtbox_" . $name . "').is(':visible'))
                                    {
                                        
                                        $('#search_tr_" . $name . "').show();	
                                    }
                                    else 
                                        
                                        $('#search_tr_" . $name . "').hide();	
                                    });
                </script>    
                </div>";

		// Attach Pagination
		if ($paginator) {

			$params = [];
			$params['jsGridFnName'] = $jsGridFnname;
			$params['perPage'] = $perPage;
			/* if ($objectId=='overtime') {
			 	$params['objname'] = $objectId;
			 }
			 else {
			 	$params['objname'] = $name;
			 } */
			$params['objname'] = $name;
			$params['objectId'] = $objectId;

			$params['searchArray'] = $searchArray;
			$params['formgrid'] = $formgrid;
			$params['con'] = $con;
			$params['dashboardcall'] = $dashboardCall;
			$params['sortname'] = $sortname;
			$params['by'] = $by;

			/** for policy documents **/
			$params['pd_category_id'] = $this->pd_category_id;

			if (isset($empscreenTotalCount) && $perPage != 0) {
				$empscreen_lastpage = ceil($empscreenTotalCount / $perPage);
				$params['empscreen_lastpage'] = $empscreen_lastpage;
			}

			$output .= $this->view->paginationControl(
				$paginator,

				'Sliding',

				'partials/pagination.phtml',
				$params
			);
			//die(json_encode($params));

		}

		$output .= "<script type='text/javascript' language='javascript'>
		</script>";


		$output .= "<script type='text/javascript' language='javascript'>$('#$name').slimScrollHorizontal({
									  alwaysVisible: false,
									  start: 'left',
									  position: 'bottom',
									 
									}).css({ background: '#ccc', paddingBottom: '10px' }); </script>";
		$output .= "<script type='text/javascript' language='javascript'>
						var id = $('#columnId').val();
						var coldata = $('#'+id).val();
						var focusID = $('#columnId').val();
                                                var fval = $('#'+focusID).attr('data-focus');
                                                if(fval == '' || fval == null)
						$('#'+focusID).focus().val('').val(coldata);
                                                
					</script>";
		return $output;
	}

	/**
	 * Function that Parses Extra Column info
	 *
	 * Regex looks for {{field_name}}
	 *
	 * @param string $column
	 * @param array $p
	 * @return string
	 */
	public function _parseExtra($column, $p)
	{
		if (isset($this->extra[$column])) {
			$val = '';

			$characterlimit = 15;
			if (isset($this->extra[$column]['characterlimit']))
				$characterlimit = $this->extra[$column]['characterlimit'];
			preg_match_all('/\{\{(.*?)\}\}/', $this->extra[$column]['value'], $matches);
			if (count($matches[1]) > 0) {
				$matches[1] = array_unique($matches[1]);
				$a = $this->extra[$column]['value'];

				foreach ($matches[1] as $match) {
					$p = (array)$p;
					$replaced_str = $p[$match];
					if ($this->encrypt_status == 'yes')
						$replaced_str = sapp_Global::_encrypt($p[$match]);
					$a = str_replace('{{' . $match . '}}', $replaced_str, $a);
					preg_match_all('/\[\[(.*?)\]\]/', $a, $newMaches);
					if (count($newMaches[1]) > 0) {
						foreach ($newMaches[1] as $matchNew) {
							$valToInclude = (strlen($p[$matchNew]) > $characterlimit) ? substr($p[$matchNew], 0, $characterlimit) . ".." : $p[$matchNew];
							$a = str_replace('[[' . $matchNew . ']]', $valToInclude, $a);
						}
					}
				}
				$val = $a;
			}
			return $val;
		}
		return '';
	} //end of _parseExtra

}//end of class