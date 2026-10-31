<?php


class Zend_View_Helper_Gridtms extends Zend_View_Helper_Abstract
{
	public $view = null;
	public $extra = [];
	private $output; // Container to hold the Grid
	private $encrypt_status = "no";
	private $moduleName;
	public function gridtms($dataArray)
	{
		$request = Zend_Controller_Front::getInstance();
		$params = $request->getRequest()->getParams();
		$this->moduleName = $request->getRequest()->getModuleName();
		
		if ($this->moduleName != 'default') {
			$this->moduleName = $this->moduleName . '/';
		} else {
			//$this->moduleName = '';
		}
		if ($this->moduleName == 'default') {
			if ($dataArray['objectname'] == 'projects') {
				$this->moduleName = $this->moduleName . '/';
			}
		}

		if (isset($dataArray['encrypt_status']) && $dataArray['encrypt_status'] == 'yes')
			$this->encrypt_status = "yes";

		$session = new Zend_Auth_Storage_Session();
		$data = $session->read();

		$menunamestr = '';
		$sortStr = '';
		$actnArr = [];
		$sortStr = $dataArray['by'];


		$menu_model = new Default_Model_Menu();
		$role_id = $data['emprole'];
		$controllers_arr = $menu_model->getControllersByRole($role_id);
		
		$actionsobjname = $dataArray['objectname'];
		if ($this->moduleName == 'expenses/')
			$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];
		else
			$actions_arr = ['add', 'edit', 'delete', 'view'];
		//$controllers_arr[$actionsobjname."controller.php"]['actions'];
		
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


		$msgtitle = $dataArray['objectname'] . '_delete';
		$msgtitle = strtoupper($msgtitle);
		$msgflag = constant($msgtitle);
		$msgAr = explode(' ', $msgflag);
		$msgdta = implode('@#$', $msgAr);


		if (isset($dataArray['formgrid']) && $dataArray['formgrid'] == 'true') {


			if (isset($dataArray['unitId']))
				$con = '/unitId/' . $dataArray['unitId'];

			else {

				if (isset($params['id']))
					$con = '/unitId/' . $params['id'];
			}

			if (isset($dataArray['projectId']))
				$con = '/projectId/' . $dataArray['projectId'];

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
				$extra['action'] = [];
			} else {
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
									' . ((in_array('view', $actions_arr) ? $viewpopup_str : '')) . '
									' . ((in_array('edit', $actions_arr) ? $editpopup_str : '')) . '
									' . ((in_array('delete', $actions_arr) ? $deletepopup_str : '')) . '
								</div>'];
			}
		} else {
			$formgridVal = '';
			$view_str = '<a href= "' . BASE_URL . $this->moduleName . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';



			if ($dataArray['objectname'] == 'paymentmode' || $dataArray['objectname'] == 'expensecategories') {
				$view_str = '';
			}
			if ($dataArray['objectname'] == 'employeeadvances') {
				$view_str = '<a href= "' . BASE_URL . $this->moduleName . $dataArray['objectname'] . '/view/id/{{employee_id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
			}


			/* if($dataArray['objectname']='assetcategories')
		{
			$view_str='';
		} */
			$edit_str = '';
			$delete_str = '';
			if ($dataArray['objectname'] != 'employeeprojects' && $dataArray['objectname'] != 'leadprojects') {
				$edit_str = '<a id="edit{{id}}" href= "' . BASE_URL . $this->moduleName . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
			}
			if ($dataArray['objectname'] == 'employeeadvances' || $dataArray['objectname'] == 'myemployeeexpenses') {
				$edit_str = '';
				$delete_str = '';
			}

			if (!in_array('view', $actions_arr) && !in_array('edit', $actions_arr) && !in_array('delete', $actions_arr)) {
				$extra['action'] = [];
			} else {


				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
				if ($dataArray['objectname'] == "assets") {
					$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\',\'{{allocated_to}}\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
					//	$view_str_assets = '<a href= "'.BASE_URL.$this->moduleName.$dataArray['objectname'].'/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
				}
				if ($dataArray['objectname'] == "logreport") {
					$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\',\'{{allocated_to}}\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
					//	$view_str_assets = '<a href= "'.BASE_URL.$this->moduleName.$dataArray['objectname'].'/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
				}
				if ($dataArray['objectname'] == "assetcategories") {

					// $view_str='';
					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
				}

				//onclick ="javascript:editlocdata(\'{{id}}\')" 
				$sub = '';

				if ($dataArray['objectname'] == 'report') {
					$view_str = '<a href= "' . BASE_URL . 'payroll/' .  $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
					$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
												
												' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
												
											</div>'];
				}
				if ($dataArray['objectname'] == 'expenses' && $menuName == 'Expenses')
					$sub = '<span id=\'expense_status-{{id}}\' onclick="submitExpense(\'{{id}}\')" class="submitexpense">Sub</span>&nbsp;';
				$extra['att'] = ['name' => 'Options', 'value' => $sub . '
																<span id=\'attachment-{{id}}\' onclick="downloadAttachment(\'{{id}}\')" class="attachment">Att</span>&nbsp;
																<span id=\'note-{{id}}\' onclick= "openNotes(\'{{note}}\')" class="notes">Notes</span>
																<script> if(\'{{receipt_file}}\' == \'\') $("#attachment-{{id}}").attr("style","visibility:hidden;");
																		if(\'{{note}}\' == \'\') $("#note-{{id}}").attr("style","visibility:hidden;");
																		if(\'{{expense_status}}\' != \'saved\') $("#expense_status-{{id}}").attr("style","visibility:hidden;");
																</script>
																
																'];
				$extra['acc'] = ['name' => 'Options', 'value' => '<span id=\'acc_expense_status-{{id}}\' class="{{expense_status}} capitalize"><span class="expense_approve" onclick="updateExpenseStatus(\'{{id}}\',1)">Appr</span>   <span class="expense_reject" onclick="updateExpenseStatusPopup(\'{{id}}\',0)">Rej</span></span>&nbsp;
																<script> 
																		if(\'{{expense_status}}\' != \'submitted\') $("#acc_expense_status-{{id}}").html("{{expense_status}}");
																</script>
																
																'];
			}
		}

		if ($this->moduleName == 'default') {
			if ($dataArray['objectname'] == 'missedhoursapprove') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];

				$view_str = '<a href= "' . BASE_URL . 'missedhoursapprove' . '/view/id/{{missed_hours_id}}" name="{{missed_hours_id}}" class="sprite view"  title=\'View\'></a>';
				$edit_str = '<a name="{{missed_hours_id}}" onclick= displaydeptform(\'' . BASE_URL . 'missedhoursapprove/editpopup/id/{{missed_hours_id}}' . '\',\'missedhoursapprove\')	href= javascript:void(0) title=\'Approve or Reject or Cancel Overtime\' class="fa fa-ellipsis-v" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
		' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
		' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
	
	

	</div>'];
			}
			if ($dataArray['objectname'] == 'clients') {

				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];

				$edit_str = '<a id="edit{{id}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';


				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}
		}

		if ($this->moduleName == 'default/') {
			if ($dataArray['objectname'] == 'projects') {

				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];

				$edit_str = '<a id="edit{{id}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';


				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}

			if ($dataArray['objectname'] == 'perkcategory') {


				$edit_str = '<a id="pc{{perkcategory_id}}" href= "percategory/edit/id/{{perkcategory_id}}" name="{{perkcategory_id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="pc{{perkcategory_id}}" name="{{perkcategory_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{perkcategory_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}
		}

		if ($this->moduleName == 'schedule/') {

			if ($dataArray['objectname'] == 'timeschedule' || $dataArray['objectname'] == 'schedulecycle') {
		
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];

				$edit_str = '<a id="edit{{id}}" href= "' . BASE_URL .'schedule/' . $dataArray['objectname'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				$view_str = '<a href= "' . BASE_URL.'schedule/' . $dataArray['objectname'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';


				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										'. ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}
			if ($dataArray['objectname'] == 'timetable' and $dataArray['menuName'] == 'Time Table') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];


				$edit_str = '<a id="edit{{time_id}}" href= "' . BASE_URL.'schedule/' . $dataArray['objectId'] . '/edit/id/{{time_id}}" name="{{time_id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{time_id}}" name="{{time_id}}" onclick= changestatus(\'' . $dataArray['objectId'] . '\',\'{{time_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL.'schedule/' . $dataArray['objectId'] . '/view/id/{{time_id}}" name="{{time_id}}" class="sprite view"  title=\'View\'></a>';



				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' /*. ((in_array('view', $actions_arr) ? $view_str : '')) */ . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}

			
			if ($dataArray['objectname'] == "logreport") {
				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\',\'{{allocated_to}}\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';
				//	$view_str_assets = '<a href= "'.BASE_URL.$this->moduleName.$dataArray['objectname'].'/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';
				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}
			if ($dataArray['objectname'] == "overtimecategory") {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];


				$edit_str = '<a id="edit{{OT_category_id}}" href= "' . BASE_URL.'schedule/' . $dataArray['objectId'] . '/edit/id/{{OT_category_id}}" name="{{OT_category_id}}" class="sprite edit"  title=\'Edit\'></a>';

				// $delete_str = '<a id="del{{OT_category_id}}" name="{{OT_category_id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{OT_category_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL . $dataArray['objectId'] . '/view/id/{{OT_category_id}}" name="{{time_id}}" class="sprite view"  title=\'View\'></a>';



				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' /*. ((in_array('view', $actions_arr) ? $view_str : '')) */ . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' /*. ((in_array('delete', $actions_arr) ? $delete_str : '')) */. '
									</div>'];
			}

			if ($dataArray['objectname'] == "timecategory") {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];


				$edit_str = '<a id="edit{{id}}" href= "' . BASE_URL.'schedule/' . $dataArray['objectId'] . '/edit/id/{{id}}" name="{{id}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{id}}" name="{{id}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL .'schedule/'. $dataArray['objectId'] . '/view/id/{{id}}" name="{{id}}" class="sprite view"  title=\'View\'></a>';



				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : ''))  . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}

			if ($dataArray['objectname'] == 'attendancereport') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];
				$edit_str = '<a id="edit{{mid}}" href= "' . BASE_URL . $dataArray['objectname'] . '/edit/id/{{mid}}" name="{{mid}}" class="sprite edit"  title=\'Edit\'></a>';

				$delete_str = '<a id="del{{mid}}" name="{{mid}}" onclick= changestatus(\'' . $dataArray['objectname'] . '\',\'{{id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL . $dataArray['objectname'] . '/view/id/{{mid}}" name="{{mid}}" class="sprite view"  title=\'View\'></a>';


				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '
										' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '
									</div>'];
			}
			if ($dataArray['objectId'] == 'overtime') {
				$actions_arr = $controllers_arr[$actionsobjname . "controller.php"]['actions'];


				$edit_str = '<a id="edit{{overtime_id}}" href= "' . BASE_URL .'schedule/' . $dataArray['objectId'] . '/edit/id/{{overtime_id}}" name="{{overtime_id}}" class="sprite edit"  title=\'Edit\'></a>';

				// $delete_str = '<a id="del{{overtime_id}}" name="{{overtime_id}}" onclick= changestatus(\'' . $dataArray['objectId'] . '\',\'{{overtime_id}}\',\'' . $msgdta . '\')	href= javascript:void(0) title=\'Delete\' class="sprite delete" ></a>';

				$view_str = '<a href= "' . BASE_URL . $dataArray['objectId'] . '/view/id/{{overtime_id}}" name="{{overtime_id}}" class="sprite view"  title=\'View\'></a>';
				$checkbox = '<a><input type="checkbox" value="{{overtime_id}}"  class="check2"  /></a>';

				//' . ((in_array('delete', $actions_arr) ? $delete_str : '')) . '

				$extra['action'] = ['name' => 'edit', 'value' => '<div class="grid-action-align">
					' /* . ((in_array('view', $actions_arr) ? $checkbox : '')) */ . '
										' . ((in_array('view', $actions_arr) ? $view_str : '')) . '
										' . ((in_array('edit', $actions_arr) ? $edit_str : '')) . '

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
		if (isset($dataArray['unitId'])) $unitId = $dataArray['unitId'];  //echo $dataArray['projectId'];
		$otherAction = 'index';
		if (isset($dataArray['otheraction'])) $otherAction = $dataArray['otheraction'];

		return $this->generateGridTms($dataArray['objectId'], $dataArray['objectname'], $dataArray['tableheader'], $paginator, $extra, true, $dataArray['jsGridFnName'], $dataArray['perPage'], $dataArray['pageNo'], $dataArray['jsFillFnName'], $dataArray['searchArray'], $formgridVal, $addaction, $menuName, $unitId, $addpermission, $menunamestr, $dataArray['call'] ?? "", $sortStr, $dataArray['search_filters'] ?? "", $dataArray['dashboardcall'] ?? "No", $dataArray['empstatus'] ?? "", $actnArr, $dataArray['empscreentotalcount'] ?? "", $dataArray['sort'] ?? "", $dataArray['by'] ?? "");
	}
	public function generateGridTms($objectId = null, $name = null, $fields = null, $paginator = null, $extracolumn = [], $sorting = false, $jsGridFnname = '', $perPage = '5', $page = '1', $jsFillFnName = '', $searchArray = '', $formgrid = 'false', $addaction = '', $menuName = '', $unitId = null, $addpermission = null, $menunamestr = null, $call = '', $sortStr = '', $search_filters = '', $dashboardCall = 'No', $empstatus = '', $actnArr = null, $empscreenTotalCount = '', $sortname = '', $by = '',$perm='')
	{
		
	$request = Zend_Controller_Front::getInstance();
	$params = $request->getRequest()->getParams();
	$controllerName = $request->getRequest()->getControllerName();
	$moduleName = $request->getRequest()->getModuleName();

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
		} */
	
		
		 elseif ($objectId == 'emp') {
			$output .= "";
		}
		
		
		elseif ($objectId == 'employee') {
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
		} 
		
		
	
		
		 elseif ($name == 'allexitproc') {
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
		} 
		
		else if ($moduleName == 'schedule')
		$output .= "<a href='" . BASE_URL .'schedule/'. $name . '/' . $action . "'><input type='button' title = 'Add' value='Add Record' class='sprite addrecord' /></a>";

		else {
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
							
							

							if ($controllerName == 'addperk'  && $objectId='add' && ($p['type'] == 'Individual' || $p['isactive'] == 'No')) {
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
						
						 if (($controllerName == 'adddeductive' && $objectId=='add') && ($p['type'] == 'Individual' || $p['isactive'] == 'No')) {
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
								if($p['status']!='Pending'){
								echo "<script type='text/javascript'>
											$(document).ready(function() {
											$('#edit'+" . $p['id'] . ").remove();
											});
											</script>";
										}
										if($p['approved'] == 'Yes'){
											echo "<script type='text/javascript'>
														$(document).ready(function() {
														$('#del'+" . $p['id'] . ").remove();
														});
														</script>";
													}
												
												if($perm[$p['emp_id']]['permission']=="N"){
													echo "<script type='text/javascript'>
																$(document).ready(function() {
																	$('#edit'+" . $p['id'] . ").remove();
																$('#del'+" . $p['id'] . ").remove();
																});
																</script>";
															}
												
							}
							
							if (($controllerName == 'addperk' || $controllerName == 'adddeductive' ) &&  ($perm[$p['id']]['permission']=="N")) {
								echo "<script type='text/javascript'>
											$(document).ready(function() {
												$('#emp'+" . $p['id'] . ").remove();

											});
											</script>";
							}
							if (( $controllerName == 'missedhoursapprove' || $controllerName == 'manageremployeeovertime' || $controllerName == 'loadapprove' || $controllerName == 'perkapprove' )  &&  ($p['approved'] == 'Yes'|| $p['approved'] == 'Approved' || $perm[$p['emp_id']]['permission']=="N")) {
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
								default:
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
	}
}
