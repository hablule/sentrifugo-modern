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

/**
 * Breadcrumbs View Helper
 *
 * A View Helper that creates the menu
 *
 *
 */
class Zend_View_Helper_Employeedetails extends Zend_View_Helper_Abstract
{


	public  function employeedetails($emparr, $conText, $userId)
	{
		$phoneno = new Default_Model_Phoneno();
		$phoneData  = $phoneno->getPhonenobyempid($userId);
		$emparr['contactnumber'] = $phoneData[0]['phoneno'];

		$session = new Zend_Auth_Storage_Session();
		$data = $session->read();
		$loggedinuser = $data['id'];
		$group_id = $data['group_id'];
		$loggedinUserRole = $data['emprole'];

		$empdata = '';
		$employeetabsStr = '';
		$empdata  = '<div class="ml-alert-1-success" id="empdetailsmsgdiv" style="display:none;">';
		$empdata .= '<div class="style-1-icon success" style="display:block;"></div>';
		$empdata .= '<div id="successtext"></div>';
		$empdata .= '</div>';
		$empdata .= '<div class="all-bg-ctrl">';

		$employeeModal = new Default_Model_Employee();
		$emppos = new Default_Model_empposition();
		$posdata = $emppos->getpos_jobtitle($userId);
		$employessunderEmpId = $employeeModal->getEmployeesUnderRM($userId);

		if (!empty($employessunderEmpId))
			$empdata .= '<input type="hidden" value="true" id="hasteam" name="hasteam" />';
		else
			$empdata .= '<input type="hidden" value="false" id="hasteam" name="hasteam" />';

		if ($conText == 'edit' || $conText == 'view') {
			//If the user has BG status as "Yet to start" then we should enable the link....
			$usersModel = new Default_Model_Users();
			$bgstatusArr = $usersModel->getBGstatus($userId);
			if (!empty($bgstatusArr) && isset($bgstatusArr) && $bgstatusArr[0]['group_id'] != MANAGEMENT_GROUP) {
				if ($bgstatusArr[0]['isactive'] == 1)
					$empdata .= '<div id="hrbgchecklink" style="display:none;" class="action-to-page"><a href="' . BASE_URL . '/empscreening/checkscreeningstatus/empid/' . $userId . '">Send for Background Check</a></div>';
			}
		}

		/* if($conText != 'mydetails')
		{
			$empdata .= '<div class="back-to-page"><input type="button" value="Back" name="Back" onclick="gobacktocontroller(\''.$conText.'\');"></div>';
		}
		 */

		$empdata .= '<div class="emp-screen-view">';
		$empdata .= '<div class="display-img-div" id="displayimg" >';
		$empdata .= '<div class="employee-pic-emp">';
		if ($loggedinUserRole != '' && ($loggedinUserRole == '4' || $loggedinUserRole == '1')) {
			$empdata .=	'<div class="chg-img_profile">';
			if ($emparr['profileimg'] != '') {
				$empdata .=	'<img id="blah" class="imgbrdr"  src="' . DOMAIN . ("public/uploads/profile/") . $emparr['profileimg'] . '" onerror="this.src=\'' . DOMAIN . 'public/media/images/default-profile-pic.jpg\'"/>';
			} else {
				$empdata .=	'<img id="blah" class="imgbrdr"  src="' . DOMAIN . 'public/media/images/employee-deafult-pic.jpg" />';
			}
			$empdata .= '</div>';

			//	<!-- Start Div for updating photoupload-->
			//	<!--End Update div -->
			$empdata .= '<input type="hidden" id="uploadimagepathedit" name="uploadimagepath" value="' . $emparr['profileimg'] . '" />';
			$empdata .= '<input type="hidden" name="profile_image" value="" />';
			//	<!-- End Photo Upload -->
			//	<!-- <div class="uploaderror_profile" style="display:none;"></div> -->

		} else {
			if ($emparr['profileimg'] != '') {
				$empdata .=	'<img id="userImage" src="' . DOMAIN . ("public/uploads/profile/") . $emparr['profileimg'] . '" onerror="this.src=\'' . DOMAIN . 'public/media/images/default-profile-pic.jpg\'"/>';
			} else {
				$empdata .=	'<img id="userImage" src="' . DOMAIN . 'public/media/images/employee-deafult-pic.jpg" />';
			}
		}

		/**
		 ** Active/inactve buttons 18-03-2015
		 ** should not be available in my details page
		 ** should not be available for org head inHR > Emp page
		 ** should be available for all employee, Manager, HR, Sys Adm employees, for Super Admin, Management and HR
		 **/
		if ($conText != 'mydetails' && $emparr['is_orghead'] != 1) {
			if ($group_id == HR_GROUP || $loggedinuser == SUPERADMIN || $group_id == MANAGEMENT_GROUP) //for activate inactivate user
			{
				$sel_act = $sel_dact = "";
				if ($emparr['isactive'] < 2 && $emparr['emptemplock'] == 0) {
					if ($emparr['isactive'] == 1) {
						$sel_act = "selected";
					} else if ($emparr['isactive'] == 0) {
						$sel_dact = "selected";
					}
					/** disable the buttons for organization head **/

					if ($loggedinUserRole != '' && ($loggedinUserRole == '4' || $loggedinUserRole == '1')) {

						$empdata .= '<div class="left_div" >
										<span class="uploadbut uploadbutsel" id="upload_custom_div_profile" style="display:block;"> Edit Profile Photo</span>
										<div id="loaderimgprofileedit" style="display:none;"><img src="' . DOMAIN . 'public/media/images/loaderwhite_21X21.gif" style="width:21px; height: 21px; float: none; "/></div>
									</div> 
									<div id="profile_edit" style="display:none; margin: 0 auto; width: 80px;">
										<div class="mrgetop10 fltleft">
											<input type="button" class="submit_bg" value="Update" onclick="empprofileImageSave(' . $emparr['id'] . ');" /></div>
										</div>
									 	<div class="uploaderror_profile" style="display:none;"></div>
											<p class="field switch">
												<label class="cb-enable  ' . $sel_act . '">
													<span>Active</span>
												</label>
												<label class="cb-disable ' . $sel_dact . '">
													<span>Inactive</span>
												</label> 
											</p>';
					} else {
						$empdata .= '<p class="field switch">
										<label class="cb-enable  ' . $sel_act . '">
											<span>Active</span>
										</label>
										<label class="cb-disable ' . $sel_dact . '">
											<span>Inactive</span>
										</label> 
									</p>';
					}

					if ($sel_act == "selected") {
						$empdata .= "
										<script type='text/javascript' language='javascript'>
											$('.cb-disable').click(function(){             
												makeActiveInactive('inactive','" . $emparr['id'] . "');
											});
										</script> ";
					} else if ($sel_dact == "selected") {
						$empdata .= "
										<script type='text/javascript' language='javascript'>
											$('.cb-enable').click(function(){                
												makeActiveInactive('active','" . $emparr['id'] . "');
											});
										</script> ";
					}
				} else if ($emparr['isactive'] < 2 && $emparr['emptemplock'] == 1) {
					$sel_dact = "selected";
					$sel_act = "";
					$empdata .= '<p class="field switch"><label class="cb-enable  ' . $sel_act . '"><span>Active</span></label><label class="cb-disable ' . $sel_dact . '"><span>Inactive</span></label> </p>';
					$empdata .= "
									<script type='text/javascript' language='javascript'>
										$('.cb-enable').click(function(){                
											makeActiveInactive('active','" . $emparr['id'] . "');
										});
										
									</script>   
									";
				} else {
					$sel_dact = "selected";
					$sel_act = "";
					$empdata .= '<p class="field switch"><label class="cb-enable  ' . $sel_act . '"><span>Active</span></label><label class="cb-disable ' . $sel_dact . '"><span>Inactive</span></label> </p>';
					$empdata .= "
									<script type='text/javascript' language='javascript'>
										$('.cb-enable,.cb-disable').click(function(){                
											makeActiveInactive('other','" . $emparr['isactive'] . "');
										});
										
									</script>   
									";
				}
			}
		}

		$empdata .= '</div>';
		$empdata .=	'<div id="loaderimg" style="display:none; clear:both; margin:0 auto; text-align: center; width:100%;"><img src="' . DOMAIN . ("public/media/images/loaderwhite_21X21.gif") . '" style="width:21px; height: 21px; float: none; "/>';
		$empdata .= '</div>';
		$empdata .=	'</div>';

		$empdata .= '<div id="personalDetailsDiv">';

		/**
		 ** 18-03-2015
		 ** Change organization head should not be available in my details page
		 **/
		if ($conText != 'mydetails' && $emparr['is_orghead'] == '1') {
			if ($group_id == HR_GROUP || $loggedinuser == SUPERADMIN || $group_id == MANAGEMENT_GROUP) //To see Change Line Manager link
			{
				$headicon = '<span class="org-head-div"><img title="Organization head" src="' . DOMAIN . ("public/media/images/org-head.png") . '" class="org-head-icon" /><a class="change_orgn_head" href="' . BASE_URL . ("employee/changeorghead/orgid/" . $emparr['user_id']) . '">Change Organization Head</a></span>';
				//$changeorghead = '<a href="'.BASE_URL.("/employee/changeorghead/orgid/".$emparr['user_id']).'">Change Organization head</a>';
			} else {
				$headicon = '';
			}
		} else {
			$headicon = '';
			//$changeorghead = '';
		}
		if (isset($emparr['active_prefix']) && isset($emparr['prefix']) && $emparr['active_prefix'] == 1 && $emparr['prefix'] != '')
			$empdata .=	'<p><b>Employee Name <i>:</i></b><span class="emp-name-span bold-text">' . $emparr['prefix'] . '.&nbsp;' . $emparr['userfullname'] . '</span>' . $headicon . '</p> ';
		else
			$empdata .=	'<p><b>Employee Name <i>:</i></b><span class="emp-name-span bold-text">' . $emparr['userfullname'] . '</span>' . $headicon . '</p>';
		$empdata .=	'<p><b>Code<i>:</i></b><span class="emp-name-span" id ="spanempid">' . $emparr['code'] . '</span></p>';
		$empdata .=	'<p><b>Email Id <i>:</i></b><span><a href="javascript:void(0)">' . $emparr['emailaddress'] . '</a></span></p>';
		$empdata .= '<p><b>Position <i>:</i></b><span>' . $posdata[0]['pos'] . '</span></p>';
		if ($emparr['contactnumber'] != '') {
			$empdata .=	'<p><b>Contact Number <i>:</i></b>';
			$empdata .= '<span id="contactnospan" >' . $emparr['contactnumber'] . '</span>';
			if ($group_id == HR_GROUP || $loggedinuser == SUPERADMIN || $loggedinuser == $userId || $group_id == MANAGER_GROUP) {
				//$empdata .= '<span class="number-edit"><input type="button" value="Update" id="editcontactnumber" name="Edit Number" onclick="opencontactnumberpopup(\''.$emparr['id'].'\',\'edit\',\''.$emparr['contactnumber'].'\');">';
				//$empdata .= '</span>';
			}
			$empdata .= '</p>';
		} else {
			$empdata .=	'<p><b>Contact Number <i>:</i></b>';
			$empdata .= '<span id="contactnospan" ></span>';
			if ($group_id == HR_GROUP || $loggedinuser == SUPERADMIN || $loggedinuser == $userId || $group_id == MANAGER_GROUP) {
				//$empdata .= '<span class="number-add"><input type="button" value="Add" id="addcontactnumber" name="Add Number" onclick="opencontactnumberpopup(\''.$emparr['id'].'\',\'add\',\'\');">';
				//$empdata .= '</span>';
			}

			$empdata .= '</p>';
		}
		$empdata .= '</div>';
		$empdata .= '</div>';
		$empdata .= '<div id="employeeContainer"  style="display: none; overflow: auto;">
						<div class="heading">
							<a href="javascript:void(0)">
								<img src="' . DOMAIN . 'public/media/images/close.png" name="" align="right" border="0" hspace="3" vspace="5" class="closeAttachPopup" style="margin: -24px 8px 0 0;"> 
							</a>
						</div>
						<iframe id="employeeCont" name="employeeCont" class="business_units_iframe" frameborder="0"></iframe>
					</div>';
		$empdata .= '</div>';

		echo $empdata;
		$employeetabsStr = $this->employeetabs($conText, $userId);
		echo $employeetabsStr;
	}

	public  function employeetabs($conText, $userId)
	{
		$tabconfig = array();
		$tabHeightClass = "";
		$session = new Zend_Auth_Storage_Session();
		$data = $session->read();


		if (defined('EMPTABCONFIGS'))
			$empOrganizationTabs = explode(",", EMPTABCONFIGS);
		$tabp = new Default_Model_Tabprivilage();


		$group_id = $data['group_id'];
		$loggedinuser = $data['id'];
		$role_id = $data['emprole'];

		$tabarrayedit = $tabp->gettabcodeedit($role_id);

		if (!empty($tabarrayedit)) {
			$i = 0;
			foreach ($tabarrayedit as $tabarrayedits) {
				$List = implode(', ', $tabarrayedits);
				$tabarrayedit[$i] = $List;
				$i++;
			}
		}
		$tabarrayview = $tabp->gettabcodeview($role_id);
		if (!empty($tabarrayview)) {
			$i = 0;
			foreach ($tabarrayview as $tabarrayviews) {
				$List = implode(', ', $tabarrayviews);
				$tabarrayview[$i] = $List;
				$i++;
			}
		}
		// die();
		// die("here");
		if ($conText == "mydetails") {
			$tabHeightClass = "mydetails-height";
		} else if ($conText == "edit" || $conText == "view") {
			if ($group_id == HR_GROUP || $loggedinuser == SUPERADMIN || $group_id == MANAGEMENT_GROUP) {
				$tabHeightClass = "hr-employee-height";
			} else if ($group_id == MANAGER_GROUP || $group_id == EMPLOYEE_GROUP || $group_id == SYSTEMADMIN_GROUP) {
				$tabHeightClass = "mydetails-height";
			}
		}

		$tabsHtml = '
						<div class="poc-ui-data-control" id="' . $tabHeightClass . '">
							<div class="left-block-ui-data">
								<div class="agency-ui">
									<ul>';
		if ($conText == "edit") {

			//View all tabs with all privileges....	onclick - changeempeditscreen...

			$tabsHtml .= '<li  id="empdetails" onclick="changeeditscreen(\'employee\',' . $userId . ');">' . TAB_EMP_OFFICIAL . '</li>';

			if (!empty($tabarrayedit) && in_array("emppersonaldetails", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_personaldetails" onclick="changeempeditscreen(\'emppersonaldetails\',' . $userId . ');">' . TAB_EMP_PERSONAL . '</li>';
			if (!empty($tabarrayedit) && in_array("empiddocument", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_iddocument" onclick="changeempeditscreen(\'empiddocument\',' . $userId . ');">' . TAB_EMP_IDDOCUMENT . '</li>';
			if (!empty($tabarrayedit) && in_array("empleaves", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_leaves" onclick="changeempeditscreen(\'empleaves\',' . $userId . ');">' . TAB_EMP_LEAVES . '</li>';
			if (!empty($tabarrayedit) && in_array("empholidays", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_holidays" onclick="changeempeditscreen(\'empholidays\',' . $userId . ');">' . TAB_EMP_HOLIDAYS . '</li>';
			if (!empty($tabarrayedit) && in_array("empbankaccount", $tabarrayedit))
				$tabsHtml .= '<li id= "emp_bankaccount" onclick="changeempeditscreen(\'empbankaccount\',' . $userId . ');">' . TAB_EMP_BANK_ACCOUNT . '</li>';			
			if (!empty($tabarrayedit) && in_array("empaddress", $tabarrayedit))
				$tabsHtml .= '<li id= "emp_address" onclick="changeempeditscreen(\'empaddress\',' . $userId . ');">' . TAB_EMP_ADDRESS . '</li>';
			if (!empty($tabarrayedit) && in_array('emphealthissue', $tabarrayedit))
				$tabsHtml .= '<li id="emp_healthissue" onclick="changeempeditscreen(\'emphealthissue\',' . $userId . ')">' . TAB_EMP_HEALTHISSUE . '</li>';
			if (!empty($tabarrayedit) && in_array("empsalarydetails", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_salarydetails" onclick="changeempeditscreen(\'empsalarydetails\',' . $userId . ');">' . TAB_EMP_SALARY . '</li>';
			if (!empty($tabarrayedit) && in_array("empbenefits", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_benefits" onclick="changeempeditscreen(\'empbenefits\',' . $userId . ');">' . TAB_EMP_BENEFITS . '</li>';
			if (!empty($tabarrayedit) && in_array("empdeductive", $tabarrayedit))
				$tabsHtml .= '<li id= "emp_deductive" onclick="changeempeditscreen(\'empdeductive\',' . $userId . ');">' . TAB_EMP_DEDUCTIVE . '</li>';
			if (!empty($tabarrayedit) && in_array("emppos", $tabarrayedit))
				$tabsHtml .= '<li id= "emp_position" onclick="changeempeditscreen(\'emppos\',' . $userId . ');">' . TAB_EMP_POSITION . '</li>';
			if (!empty($tabarrayedit) && in_array("empschedule", $tabarrayedit))
				$tabsHtml .= '<li id= "emp_schedule" onclick="changeempeditscreen(\'empschedule\',' . $userId . ');">' . TAB_EMP_SCHEDULE . '</li>';
			if (!empty($tabarrayedit) && in_array("empphoneno", $tabarrayedit))
				$tabsHtml .= '<li id="emp_phoneno" onclick="changeempeditscreen(\'empphoneno\',' . $userId . ');">' . TAB_EMP_PHONENO . '</li>';
			if (!empty($tabarrayedit) && in_array("emppresence", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_presence" onclick="changeempeditscreen(\'emppresence\',' . $userId . ');">' . TAB_EMP_PRESENCE . '</li>';
			if (!empty($tabarrayedit) && in_array("empuser", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_user" onclick="changeempeditscreen(\'empuser\',' . $userId . ');">' . TAB_EMP_USER . '</li>';
			if (!empty($tabarrayedit) && in_array("emprole", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_role" onclick="changeempeditscreen(\'emprole\',' . $userId . ');">' . TAB_EMP_ROLE . '</li>';
			if (!empty($tabarrayedit) && in_array("emprecordaccess", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_recordaccess" onclick="changeempeditscreen(\'emprecordaccess\',' . $userId . ');">' . TAB_EMP_RECORDACCESS . '</li>';
			if (!empty($tabarrayedit) && in_array("empdevice", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_device" onclick="changeempeditscreen(\'empdevice\',' . $userId . ');">' . TAB_EMP_DEVICE . '</li>';
			if (!empty($tabarrayedit) && in_array("empskills", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_skills" onclick="changeempeditscreen(\'empskills\',' . $userId . ');">' . TAB_EMP_SKILLS . '</li>';
			if (!empty($tabarrayedit) && in_array("experiencedetails", $tabarrayedit))
				$tabsHtml .= '<li id= "experience_details" onclick="changeempeditscreen(\'experiencedetails\',' . $userId . ');">' . TAB_EMP_EXPERIENCE . '</li>';
			if (!empty($tabarrayedit) && in_array("educationdetails", $tabarrayedit))
				$tabsHtml .= '<li id = "education_details" onclick="changeempeditscreen(\'educationdetails\',' . $userId . ');">' . TAB_EMP_EDUCATION . '</li>';
			if (!empty($tabarrayedit) && in_array("trainingandcertificationdetails", $tabarrayedit))
				$tabsHtml .= '<li id = "trainingandcertification_details" onclick="changeempeditscreen(\'trainingandcertificationdetails\',' . $userId . ');">' . TAB_EMP_TRAINING_CERTIFY . '</li>';
			if (!empty($tabarrayedit) && in_array("disabilitydetails", $tabarrayedit))
				$tabsHtml .= '<li id = "disability_details" onclick="changeempeditscreen(\'disabilitydetails\',' . $userId . ');">' . TAB_EMP_DISABILITY . '</li>';
			if (!empty($tabarrayedit) && in_array("dependencydetails", $tabarrayedit))
				$tabsHtml .= '<li id = "dependency_details" onclick="changeempeditscreen(\'dependencydetails\',' . $userId . ');">' . TAB_EMP_DEPENDENCY . '</li>';
			if (!empty($tabarrayedit) && in_array("creditcarddetails", $tabarrayedit))
				$tabsHtml .= '<li id= "creditcard_details" onclick="changeempeditscreen(\'creditcarddetails\',' . $userId . ');">' . TAB_EMP_CORPORATE_CARD . '</li>';
			if (!empty($tabarrayedit) && in_array("empadditionaldetails", $tabarrayedit))
				$tabsHtml .= '<li id = "emp_additionaldetails" onclick="changeempeditscreen(\'empadditionaldetails\',' . $userId . ');">' . TAB_EMP_ADDITIONAL . '</li>';
			if (!empty($tabarrayedit) && in_array("assetdetails", $tabarrayedit))
				$tabsHtml .= '<li id= "asset_details" onclick="changeempeditscreen(\'assetdetails\',' . $userId . ');">' . TAB_EMP_ASSETS . '</li>';

		} else if ($conText == "view") {

			$tabsHtml .= '<li id="empdetails" onclick="changeviewscreen(\'employee\',' . $userId . ');">' . TAB_EMP_OFFICIAL . '</li>';

			if (!empty($tabarrayview) && in_array("emppersonaldetails", $tabarrayview))
				$tabsHtml .= '<li id = "emp_personaldetails" onclick="changeempviewscreen(\'emppersonaldetails\',' . $userId . ');">' . TAB_EMP_PERSONAL . '</li>';
			if (!empty($tabarrayview) && in_array("empiddocument", $tabarrayview))
				$tabsHtml .= '<li id = "emp_iddocument" onclick="changemyempviewscreen(\'empiddocument\',\'view\',' . $userId . ');">' . TAB_EMP_DOCUMENTS . '<span class="beta_menu"></span></li>';
			if (!empty($tabarrayview) && in_array("empleaves", $tabarrayview))
				$tabsHtml .= '<li id = "emp_leaves" onclick="changeempviewscreen(\'empleaves\',' . $userId . ');">' . TAB_EMP_LEAVES . '</li>';
			if (!empty($tabarrayview) && in_array("empholidays", $tabarrayview))
				$tabsHtml .= '<li id = "emp_holidays" onclick="changeempviewscreen(\'empholidays\',' . $userId . ');">' . TAB_EMP_HOLIDAYS . '</li>';
			if (!empty($tabarrayview) && in_array("empbankaccount", $tabarrayview))
				$tabsHtml .= '<li id= "emp_bankaccount" onclick="changeempviewscreen(\'empbankaccount\',' . $userId . ');">' . TAB_EMP_BANK_ACCOUNT . '</li>';
			if (!empty($tabarrayview) && in_array("empaddress", $tabarrayview))
				$tabsHtml .= '<li id= "emp_address" onclick="changeempviewscreen(\'empaddress\',' . $userId . ');">' . TAB_EMP_ADDRESS . '</li>';
			if (!empty($tabarrayview) && in_array('emphealthissue', $tabarrayview))
				$tabsHtml .= '<li id="emp_healthissue" onclick="changeempviewscreen(\'emphealthissue\',' . $userId . ')">' . TAB_EMP_HEALTHISSUE . '</li>';
			if (!empty($tabarrayview) && in_array("empsalarydetails", $tabarrayview))
				$tabsHtml .= '<li id = "emp_salarydetails" onclick="changeempviewscreen(\'empsalarydetails\',' . $userId . ');">' . TAB_EMP_SALARY . '</li>';
			if (!empty($tabarrayview) && in_array("empbenefits", $tabarrayview))
				$tabsHtml .= '<li id = "emp_benefits" onclick="changeempviewscreen(\'empbenefits\',' . $userId . ');">' . TAB_EMP_BENEFITS . '</li>';
			if (!empty($tabarrayview) && in_array("empdeductive", $tabarrayview))
				$tabsHtml .= '<li id= "emp_deductive" onclick="changeempviewscreen(\'empdeductive\',' . $userId . ');">' . TAB_EMP_DEDUCTIVE . '</li>';
			if (!empty($tabarrayview) && in_array("emppos", $tabarrayview))
				$tabsHtml .= '<li id= "emp_position" onclick="changeempviewscreen(\'emppos\',' . $userId . ');">' . TAB_EMP_POSITION . '</li>';
			if (!empty($tabarrayview) && in_array("empschedule", $tabarrayview))
				$tabsHtml .= '<li id= "emp_schedule" onclick="changeempviewscreen(\'empschedule\',' . $userId . ');">' . TAB_EMP_SCHEDULE . '</li>';
			if (!empty($tabarrayview) && in_array("empphoneno", $tabarrayview))
				$tabsHtml .= '<li id="emp_phoneno" onclick="changeempviewscreen(\'empphoneno\',' . $userId . ')">' . TAB_EMP_PHONENO . '</li>';
			if (!empty($tabarrayview) && in_array("emppresence", $tabarrayview))
				$tabsHtml .= '<li id = "emp_presence" onclick="changeempviewscreen(\'emppresence\',' . $userId . ');">' . TAB_EMP_PRESENCE . '</li>';
			if (!empty($tabarrayview) && in_array("empuser", $tabarrayview))
				$tabsHtml .= '<li id = "emp_user" onclick="changeempviewscreen(\'empuser\',' . $userId . ');">' . TAB_EMP_USER . '</li>';
			if (!empty($tabarrayview) && in_array("emprole", $tabarrayview))
				$tabsHtml .= '<li id = "emp_role" onclick="changeempviewscreen(\'emprole\',' . $userId . ');">' . TAB_EMP_ROLE . '</li>';
			if (!empty($tabarrayview) && in_array("emprecordaccess", $tabarrayview))
				$tabsHtml .= '<li id = "emp_recordaccess" onclick="changeempviewscreen(\'emprecordaccess\',' . $userId . ');">' . TAB_EMP_RECORDACCESS . '</li>';
			if (!empty($tabarrayview) && in_array("empskills", $tabarrayview))
				$tabsHtml .= '<li id = "emp_skills" onclick="changeempviewscreen(\'empskills\',' . $userId . ');">' . TAB_EMP_SKILLS . '</li>';
			if (!empty($tabarrayview) && in_array("experiencedetails", $tabarrayview))
				$tabsHtml .= '<li id= "experience_details" onclick="changeempviewscreen(\'experiencedetails\',' . $userId . ');">' . TAB_EMP_EXPERIENCE . '</li>';
			if (!empty($tabarrayview) && in_array("educationdetails", $tabarrayview))
				$tabsHtml .= '<li id = "education_details" onclick="changeempviewscreen(\'educationdetails\',' . $userId . ');">' . TAB_EMP_EDUCATION . '</li>';
			if (!empty($tabarrayview) && in_array("trainingandcertificationdetails", $tabarrayview))
				$tabsHtml .= '<li id = "trainingandcertification_details" onclick="changeempviewscreen(\'trainingandcertificationdetails\',' . $userId . ');">' . TAB_EMP_TRAINING_CERTIFY . '</li>';
			if (!empty($tabarrayview) && in_array("disabilitydetails", $tabarrayview))
				$tabsHtml .= '<li id = "disability_details" onclick="changeempviewscreen(\'disabilitydetails\',' . $userId . ');">' . TAB_EMP_DISABILITY . '</li>';
			if (!empty($tabarrayview) && in_array("dependencydetails", $tabarrayview))
				$tabsHtml .= '<li id = "dependency_details" onclick="changeempviewscreen(\'dependencydetails\',' . $userId . ');">' . TAB_EMP_DEPENDENCY . '</li>';
			if (!empty($tabarrayview) && in_array("creditcarddetails", $tabarrayview))
				$tabsHtml .= '<li id= "creditcard_details" onclick="changeempviewscreen(\'creditcarddetails\',' . $userId . ');">' . TAB_EMP_CORPORATE_CARD . '</li>';
			if (!empty($tabarrayview) && in_array("empadditionaldetails", $tabarrayview))
				$tabsHtml .= '<li id = "emp_additionaldetails" onclick="changeempviewscreen(\'empadditionaldetails\',' . $userId . ');">' . TAB_EMP_ADDITIONAL . '</li>';
			if (!empty($tabarrayview) && in_array("assetdetails", $tabarrayview))
				$tabsHtml .= '<li id= "asset_details" onclick="changeempviewscreen(\'assetdetails\',' . $userId . ');">' . TAB_EMP_ASSETS . '</li>';

		} else if ($conText == "mydetails") {

			$tabsHtml .= '<li id="empdetails"><a href="' . BASE_URL . 'mydetails">' . TAB_EMP_OFFICIAL . '</a></li>';
			
			if (!empty($tabarrayview) && in_array("emppersonaldetails", $tabarrayview))
				$tabsHtml .= '<li id = "emp_personaldetails"><a href="' . BASE_URL . 'mydetails/personaldetailsview/userid/' . $userId . '">' . TAB_EMP_PERSONAL . '</a></li>';
			if (!empty($tabarrayview) && in_array("empiddocument", $tabarrayview))
				$tabsHtml .= '<li id = "emp_iddocument"><a href="' . BASE_URL . 'mydetails/documents/userid/' . $userId . '">' . TAB_EMP_DOCUMENTS . '</a><span class="beta_menu"></span></li>';
			if (!empty($tabarrayview) && in_array("empleaves", $tabarrayview))
				$tabsHtml .= '<li id = "emp_leaves"><a href="' . BASE_URL . 'mydetails/empleaves/userid/' . $userId . '">' . TAB_EMP_LEAVES . '</a><span class="beta_menu"></span></li>';
			if (!empty($tabarrayview) && in_array("empholidays", $tabarrayview))
				$tabsHtml .= '<li id = "emp_holidays"><a href="' . BASE_URL . 'mydetails/holidays/userid/' . $userId . '">' . TAB_EMP_HOLIDAYS . '</a><span class="beta_menu"></span></li>';
			if (!empty($tabarrayview) && in_array("empbankaccount", $tabarrayview))
				$tabsHtml .= '<li id = "emp_bankaccount"><a href="' . BASE_URL . 'mydetails/empbankaccount/userid/' . $userId . '">' . TAB_EMP_BANK_ACCOUNT . '</a><span class="beta_menu"></span></li>';
			if (!empty($tabarrayview) && in_array("empaddress", $tabarrayview))
				$tabsHtml .= '<li id = "emp_address"><a href="' . BASE_URL . 'mydetails/addressdetails/userid/' . $userId . '">' . TAB_EMP_ADDRESS . '</a><span class="beta_menu"></span></li>';
			if (!empty($tabarrayview) && in_array("emphealthissue", $tabarrayview))
				$tabsHtml .= '<li id = "emp_healthissue"><a href="' . BASE_URL . 'mydetails/health/userid/' . $userId . '">' . TAB_EMP_HEALTHISSUE . '</a><span class="beta_menu"></span></li>';
			if (!empty($tabarrayview) && in_array("empsalarydetails", $tabarrayview))
				$tabsHtml .= '<li id = "emp_salarydetails"><a href="' . BASE_URL . 'mydetails/empsalarydetails/userid/' . $userId . '">' . TAB_EMP_SALARY . '</a></li>';
			if (!empty($tabarrayview) && in_array("empbenifits", $tabarrayview))
				$tabsHtml .= '<li id = "emp_benifits"><a href="' . BASE_URL . 'mydetails/benefitsdetail/userid/' . $userId . '">' . TAB_EMP_BENEFITS . '</a></li>';
			if (!empty($tabarrayview) && in_array("empdeductive", $tabarrayview))
				$tabsHtml .= '<li id = "emp_deductive"><a href="' . BASE_URL . 'mydetails/deductivedetail/userid/' . $userId . '">' . TAB_EMP_DEDUCTIVE . '</a></li>';
			if (!empty($tabarrayview) && in_array("emppos", $tabarrayview))
				$tabsHtml .= '<li id = "emp_position"><a href="' . BASE_URL . 'mydetails/emppos/userid/' . $userId . '">' . TAB_EMP_POSITION . '</a></li>';
			if (!empty($tabarrayview) && in_array("empschedule", $tabarrayview))
				$tabsHtml .= '<li id = "emp_schedule"><a href="' . BASE_URL . 'mydetails/scheduledetail/userid/' . $userId . '">' . TAB_EMP_SCHEDULE . '</a></li>';
			if (!empty($tabarrayview) && in_array("empphoneno", $tabarrayview))
				$tabsHtml .= '<li id="emp_phoneno"><a href="' . BASE_URL . 'mydetails/empphoneno/userid/' . $userId . '">' . TAB_EMP_PHONENO . '</a></li>';
			if (!empty($tabarrayview) && in_array("emppresence", $tabarrayview))
				$tabsHtml .= '<li id = "emp_presence"> <a href="' . BASE_URL . 'mydetails/emppresence/userid/' . $userId . '">' . TAB_EMP_PRESENCE . '</a></li>';
			if (!empty($tabarrayview) && in_array("empuser", $tabarrayview))
				$tabsHtml .= '<li id = "emp_user"> <a href="' . BASE_URL . 'mydetails/empuser/userid/' . $userId . '">' . TAB_EMP_USER . '</a></li>';
			if (!empty($tabarrayview) && in_array("emprecordaccess", $tabarrayview))
				$tabsHtml .= '<li id = "emp_recordaccess"> <a href="' . BASE_URL . 'mydetails/emprecordaccess/userid/' . $userId . '">' . TAB_EMP_RECORDACCESS . '</a></li>';
			if (!empty($tabarrayview) && in_array("emprole", $tabarrayview))
				$tabsHtml .= '<li id = "emp_role"> <a href="' . BASE_URL . 'mydetails/emprole/userid/' . $userId . '">' . TAB_EMP_ROLE . '</a></li>';
			if (!empty($tabarrayview) && in_array("empskills", $tabarrayview))
				$tabsHtml .= '<li id = "emp_skills"><a href="' . BASE_URL . 'mydetails/skills/userid/' . $userId . '">' . TAB_EMP_SKILLS . '</a></li>';
			// if(!empty($tabarrayview) && in_array("emp_jobhistory", $tabarrayview))
			// 	$tabsHtml .= '<li id = "emp_jobhistory"><a href="'.BASE_URL.'mydetails/jobhistory/userid/'.$userId.'">'.TAB_EMP_JOB_HISTORY.'</a></li>';
			if (!empty($tabarrayview) && in_array("experiencedetails", $tabarrayview))
				$tabsHtml .= '<li id= "experience_details"><a href="' . BASE_URL . 'mydetails/experience/userid/' . $userId . '">' . TAB_EMP_EXPERIENCE . '</a></li>';
			if (!empty($tabarrayview) && in_array("educationdetails", $tabarrayview))
				$tabsHtml .= '<li id = "education_details"><a href="' . BASE_URL . 'mydetails/education/userid/' . $userId . '">' . TAB_EMP_EDUCATION . '</a></li>';
			if (!empty($tabarrayview) && in_array("trainingandcertificationdetails", $tabarrayview))
				$tabsHtml .= '<li id = "trainingandcertification_details"><a href="' . BASE_URL . 'mydetails/certification/userid/' . $userId . '">' . TAB_EMP_TRAINING_CERTIFY . '</a></li>';
			// if(!empty($tabarrayview) && in_array("medical_claims", $tabarrayview))
			// $tabsHtml .='<li id = "medical_claims"><a href="'.BASE_URL.'mydetails/medicalclaims/userid/'.$userId.'">'.TAB_EMP_MEDICAL_CLAIMS.'</a></li>';
			if (!empty($tabarrayview) && in_array("disabilitydetails", $tabarrayview))
				$tabsHtml .= '<li id = "disability_details"><a href="' . BASE_URL . 'mydetails/disabilitydetailsview/userid/' . $userId . '">' . TAB_EMP_DISABILITY . '</a></li>';
			if (!empty($tabarrayview) && in_array("dependencydetails", $tabarrayview))
				$tabsHtml .= '<li id = "dependency_details"><a href="' . BASE_URL . 'mydetails/dependency/userid/' . $userId . '">' . TAB_EMP_DEPENDENCY . '</a></li>';
			// if(!empty($tabarrayview) && in_array("visadetails", $tabarrayview))
			// $tabsHtml .='<li id="visadetails"><a href="'.BASE_URL.'mydetails/visadetailsview/userid/'.$userId.'">'.TAB_EMP_VISA_EMIGRATION.'</a></li>';
			if (!empty($tabarrayview) && in_array("creditcarddetails", $tabarrayview))
				$tabsHtml .= '<li id= "creditcard_details"><a href="' . BASE_URL . 'mydetails/creditcarddetailsview/userid/' . $userId . '">' . TAB_EMP_CORPORATE_CARD . '</a></li>';
			// if(!empty($tabarrayview) && in_array("workeligibilitydetails", $tabarrayview))
			// $tabsHtml .='<li id="workeligibilitydetails"><a href="'.BASE_URL.'mydetails/workeligibilitydetailsview/userid/'.$userId.'">'.TAB_EMP_WORK_ELIGIBILITY.'</a></li>';
			if (!empty($tabarrayview) && in_array("empadditionaldetails", $tabarrayview))
				$tabsHtml .= '<li id = "emp_additionaldetails"><a href="' . BASE_URL . 'mydetails/additionaldetailsedit/userid/' . $userId . '">' . TAB_EMP_ADDITIONAL . '</a></li>';
			if (!empty($tabarrayview) && in_array("assetdetails", $tabarrayview))
				$tabsHtml .= '<li id = "asset_details"><a href="' . BASE_URL . 'mydetails/assetdetailsview/userid/' . $userId . '">' . TAB_EMP_ASSETS . '</a></li>';

		} else if ($conText == "myemployees") {

			$tabsHtml .= '<li id="empdetails" onclick="changeviewscreen(\'myemployees\',' . $userId . ');">' . TAB_EMP_OFFICIAL . '</li>';

			if (!empty($empOrganizationTabs) && in_array("emppersonaldetails", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emppersonaldetails" onclick="changemyempviewscreen(\'myemployees\',\'perview\',' . $userId . ');">' . TAB_EMP_PERSONAL . '</li>';
			if (!empty($empOrganizationTabs) && in_array("employeedocs", $empOrganizationTabs))
				$tabsHtml .= '<li id = "employeedocs" onclick="changemyempviewscreen(\'myemployees\',\'docview\',' . $userId . ');">' . TAB_EMP_DOCUMENTS . '<span class="beta_menu"></span></li>';
			// if(!empty($empOrganizationTabs) && in_array("empcommunicationdetails", $empOrganizationTabs))
			// $tabsHtml .= '<li id = "empcommunicationdetails" onclick="changemyempviewscreen(\'myemployees\',\'comview\','.$userId .');">'.TAB_EMP_CONTACT.'</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_skills", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emp_skills" onclick="changemyempviewscreen(\'myemployees\',\'skillsview\',' . $userId . ');">' . TAB_EMP_SKILLS . '</li>';
			// if(!empty($empOrganizationTabs) && in_array("emp_jobhistory", $empOrganizationTabs))
			// $tabsHtml .= '<li id = "emp_jobhistory" onclick="changemyempviewscreen(\'myemployees\',\'jobhistoryview\','.$userId .');">'.TAB_EMP_JOB_HISTORY.'</li>';
			if (!empty($empOrganizationTabs) && in_array("experience_details", $empOrganizationTabs))
				$tabsHtml .= '<li id = "experience_details" onclick="changemyempviewscreen(\'myemployees\',\'expview\',' . $userId . ');">' . TAB_EMP_EXPERIENCE . '</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_phoneno", $empOrganizationTabs))
				$tabsHtml .= '<li id="emp_phoneno" onclick="changemyempviewscreen(\'empphoneno\',\'empphoneno\'' . $userId . ');">' . TAB_EMP_PHONENO . '</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_presence", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emp_presence" onclick="changeempviewscreen(\'emppresence\',' . $userId . ');">' . TAB_EMP_PRESENCE . '</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_user", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emp_user" onclick="changeempviewscreen(\'empuser\',' . $userId . ');">' . TAB_EMP_USER . '</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_recordaccess", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emp_recordaccess" onclick="changeempviewscreen(\'emprecordaccess\',' . $userId . ');">' . TAB_EMP_RECORDACCESS . '</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_role", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emp_role" onclick="changeempviewscreen(\'emprole\',' . $userId . ');">' . TAB_EMP_ROLE . '</li>';
			if (!empty($empOrganizationTabs) && in_array("education_details", $empOrganizationTabs))
				$tabsHtml .= '<li id = "education_details" onclick="changemyempviewscreen(\'myemployees\',\'eduview\',' . $userId . ');">' . TAB_EMP_EDUCATION . '</li>';
			if (!empty($empOrganizationTabs) && in_array("trainingandcertification_details", $empOrganizationTabs))
				$tabsHtml .= '<li id = "trainingandcertification_details" onclick="changemyempviewscreen(\'myemployees\',\'trainingview\',' . $userId . ');">' . TAB_EMP_TRAINING_CERTIFY . '</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_additional", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emp_additional" onclick="changemyempviewscreen(\'myemployees\',\'additionaldetailsview\',' . $userId . ');">' . TAB_EMP_ADDITIONAL . '</li>';

		} else if ($conText == "myemployeesedit") {

			$tabsHtml .= '<li id="empdetails" onclick="changeeditscreen(\'myemployees\',' . $userId . ');">' . TAB_EMP_OFFICIAL . '</li>';

			if (!empty($empOrganizationTabs) && in_array("emppersonaldetails", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emppersonaldetails" onclick="changemyempviewscreen(\'myemployees\',\'peredit\',' . $userId . ');">' . TAB_EMP_PERSONAL . '</li>';
			if (!empty($empOrganizationTabs) && in_array("employeedocs", $empOrganizationTabs))
				$tabsHtml .= '<li id = "employeedocs" onclick="changemyempviewscreen(\'myemployees\',\'docedit\',' . $userId . ');">' . TAB_EMP_DOCUMENTS . '<span class="beta_menu"></span></li>';
			// if(!empty($empOrganizationTabs) && in_array("empcommunicationdetails", $empOrganizationTabs))
			// $tabsHtml .= '<li id = "empcommunicationdetails" onclick="changemyempviewscreen(\'myemployees\',\'comedit\','.$userId .');">'.TAB_EMP_CONTACT.'</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_skills", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emp_skills" onclick="changemyempviewscreen(\'myemployees\',\'skillsedit\',' . $userId . ');">' . TAB_EMP_SKILLS . '</li>';
			// if(!empty($empOrganizationTabs) && in_array("emp_jobhistory", $empOrganizationTabs))
			// $tabsHtml .= '<li id = "emp_jobhistory" onclick="changemyempviewscreen(\'myemployees\',\'jobhistoryedit\','.$userId .');">'.TAB_EMP_JOB_HISTORY.'</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_phoneno", $empOrganizationTabs))
				$tabsHtml .= '<li id="emp_phoneno" onclick="changemyempviewscreen(\'empphoneno\',\'empphoneno\'' . $userId . ')">' . TAB_EMP_PHONENO . '</li>';
			if (!empty($empOrganizationTabs) && in_array("experience_details", $empOrganizationTabs))
				$tabsHtml .= '<li id = "experience_details" onclick="changemyempviewscreen(\'myemployees\',\'expedit\',' . $userId . ');">' . TAB_EMP_EXPERIENCE . '</li>';
			if (!empty($empOrganizationTabs) && in_array("education_details", $empOrganizationTabs))
				$tabsHtml .= '<li id = "education_details" onclick="changemyempviewscreen(\'myemployees\',\'eduedit\',' . $userId . ');">' . TAB_EMP_EDUCATION . '</li>';
			if (!empty($empOrganizationTabs) && in_array("trainingandcertification_details", $empOrganizationTabs))
				$tabsHtml .= '<li id = "trainingandcertification_details" onclick="changemyempviewscreen(\'myemployees\',\'trainingedit\',' . $userId . ');">' . TAB_EMP_TRAINING_CERTIFY . '</li>';
			if (!empty($empOrganizationTabs) && in_array("emp_additional", $empOrganizationTabs))
				$tabsHtml .= '<li id = "emp_additional" onclick="changemyempviewscreen(\'myemployees\',\'additionaldetailsedit\',' . $userId . ');">' . TAB_EMP_ADDITIONAL . '</li>';

		}

		$tabsHtml .= '			</ul>
							</div>
						</div>
					';

		echo $tabsHtml;
	}
}
