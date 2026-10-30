<?php
class Default_Plugin_AccessControl extends Zend_Controller_Plugin_Abstract
{
  private $_acl,$id_param;
          
  public function preDispatch(Zend_Controller_Request_Abstract $request)
  {
	$storage = new Zend_Auth_Storage_Session();
	$data = $storage->read();
	$role = $data['emprole'];
	if($role == 1)
		$role = 'admin';
	else if($role == 2)
	 $role = 'management';
	else if($role == 3)
	 $role = 'manager';
	else if($role == 4)
	 $role = 'hrmanager';
	else if($role == 5)
	 $role = 'employee';
	else if($role == 6)
	 $role = 'user';
	else if($role == 7)
	 $role = 'agencyuser';
	else if($role == 8)
	 $role = 'sysadmin';
	else if($role == 9)
	 $role = 'teamlead';
	else if($role == 19)
	 $role = 'qqw';
	
  	$request->getModuleName();
        $request->getControllerName();
        $request->getActionName();
    	
        
        $module = $request->getModuleName();
	$resource = $request->getControllerName();
	$privilege = $request->getActionName();
	$this->id_param = $request->getParam('id');
	$allowed = false;
        $acl = $this->_getAcl();
	$moduleResource = "$module:$resource";
	
	if($resource == 'profile')
            $role = 'viewer';
		
	if($resource == 'services')
            $role = 'services';
		
	if($role != '') 
        {
            if ($acl->has($moduleResource)) 
            {						
                $allowed = $acl->isAllowed($role, $moduleResource, $privilege);	
			    	 
            }	 
            if (!$allowed)//  && $role !='admin') 
            {				
                $request->setControllerName('error');
	        $request->setActionName('error');
            }
	}
  }
  
protected function _getAcl()
{
    if ($this->_acl == null ) 
    {
	   $acl = new Zend_Acl();

	   $acl->addRole('admin');            
	   $acl->addRole('viewer');            
	   
	 $acl->addRole('management');
	 $acl->addRole('manager');
	 $acl->addRole('hrmanager');
	 $acl->addRole('employee');
	 $acl->addRole('user');
	 $acl->addRole('agencyuser');
	 $acl->addRole('sysadmin');
	 $acl->addRole('teamlead');
	 $acl->addRole('qqw');
	   $storage = new Zend_Auth_Storage_Session();
	   $data = $storage->read();
	   $role = $data['emprole'];
		
	$auth = Zend_Auth::getInstance(); 
	$tmroleText=array();
	$tmroleText = array('1'=>'admin','2'=>'management','3'=>'manager','4'=>'hrmanager','5'=>'employee','6'=>'user','7'=>'agencyuser','8'=>'sysadmin','9'=>'teamlead','19'=>'qqw');
	
		if($auth->hasIdentity())
		{
			$tm_role = Zend_Registry::get('tm_role');
			$timeManagementRole = new Zend_Session_Namespace('tm_role');
			if(empty($timeManagementRole->tmrole))
			{
				$tm_role = $timeManagementRole->tmrole;
			}				
		}
			if(!empty($tm_role) && $tm_role == 'Admin') { 
	if(!isset($role))
								$tmroleText[$role] = 'admin';
		 $acl->addResource(new Zend_Acl_Resource('timemanagement:index'));
									$acl->allow($tmroleText[$role], 'timemanagement:index', array('index','week','edit','view','getstates','converdate'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:reports'));
									$acl->allow($tmroleText[$role], 'timemanagement:reports', array('index','employeereports','projectsreports','getempduration','getprojecttaskduration','tmreport'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:clients'));
									$acl->allow($tmroleText[$role], 'timemanagement:clients', array('index','edit','view','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:configuration'));
									$acl->allow($tmroleText[$role], 'timemanagement:configuration', array('index','add'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:currency'));
									$acl->allow($tmroleText[$role], 'timemanagement:currency', array('index'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:defaulttasks'));
									$acl->allow($tmroleText[$role], 'timemanagement:defaulttasks', array('index','edit','view','delete','checkduptask'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:emptimesheets'));
									$acl->allow($tmroleText[$role], 'timemanagement:emptimesheets', array('index','displayweeks','getmonthlyspan','accordion','employeetimesheet','empdisplayweeks','emptimesheetmonthly','emptimesheetweekly','enabletimesheet','approvetimesheet','rejecttimesheet','approvedaytimesheet','rejectdaytimesheet','getweekstartenddates'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:expenses'));
									$acl->allow($tmroleText[$role], 'timemanagement:expenses', array('index','edit','view','delete','download','uploadpreview','getprojectbyclientid','getfilename','submitexpense','expensereports','viewexpenses','viewexpensereports','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:expensecategory'));
									$acl->allow($tmroleText[$role], 'timemanagement:expensecategory', array('index','edit','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:projectresources'));
									$acl->allow($tmroleText[$role], 'timemanagement:projectresources', array('index','resources','view','addresourcesproject','viewemptasks','addresources','deleteprojectresource','assigntasktoresources','taskassign','resourcetaskdelete','resourcetaskassigndelete'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:projects'));
									$acl->allow($tmroleText[$role], 'timemanagement:projects', array('index','edit','view','add','tasks','addtasksproject','addtasks','delete','checkempforprojects'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:projecttasks'));
									$acl->allow($tmroleText[$role], 'timemanagement:projecttasks', array('index','viewtasksresources','deletetask','assignresourcestotask','saveresources','edittaskname'));
 } elseif(!empty($tm_role) && $tm_role == 'Manager') { 
		 $acl->addResource(new Zend_Acl_Resource('timemanagement:index'));
									$acl->allow($tmroleText[$role], 'timemanagement:index', array('index','week','save','submit','eraseweek','getstates','getapprovedtimesheet','closeapprovealert','converdate'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:notifications'));
									$acl->allow($tmroleText[$role], 'timemanagement:notifications', array('pendingsubmissions','pendingsubmissionsweeklyview','weeklymonthlyview'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:clients'));
									$acl->allow($tmroleText[$role], 'timemanagement:clients', array('index','edit','view','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:defaulttasks'));
									$acl->allow($tmroleText[$role], 'timemanagement:defaulttasks', array('index','edit','view','delete','checkduptask'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:projects'));
									$acl->allow($tmroleText[$role], 'timemanagement:projects', array('index','edit','view','add','tasks','addtasksproject','addtasks','delete','checkempforprojects'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:projectresources'));
									$acl->allow($tmroleText[$role], 'timemanagement:projectresources', array('index','resources','view','addresourcesproject','viewemptasks','addresources','deleteprojectresource','assigntasktoresources','taskassign','resourcetaskdelete','resourcetaskassigndelete'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:projecttasks'));
									$acl->allow($tmroleText[$role], 'timemanagement:projecttasks', array('index','viewtasksresources','deletetask','assignresourcestotask','saveresources','edittaskname'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:reports'));
									$acl->allow($tmroleText[$role], 'timemanagement:reports', array('index','employeereports','projectsreports','getempduration','getprojecttaskduration','tmreport'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:emptimesheets'));
									$acl->allow($tmroleText[$role], 'timemanagement:emptimesheets', array('index','displayweeks','getmonthlyspan','accordion','employeetimesheet','empdisplayweeks','emptimesheetmonthly','emptimesheetweekly','enabletimesheet','approvetimesheet','rejecttimesheet','approvedaytimesheet','rejectdaytimesheet','getweekstartenddates'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:expenses'));
									$acl->allow($tmroleText[$role], 'timemanagement:expenses', array('index','edit','view','delete','download','uploadpreview','getprojectbyclientid','getfilename','submitexpense','expensereports','viewexpenses','viewexpensereports','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus'));
 } elseif(!empty($tm_role) && $tm_role == 'Employee') { 
		 $acl->addResource(new Zend_Acl_Resource('timemanagement:index'));
									$acl->allow($tmroleText[$role], 'timemanagement:index', array('index','week','save','submit','eraseweek','getstates','getapprovedtimesheet','closeapprovealert','converdate'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:employeeprojects'));
									$acl->allow($tmroleText[$role], 'timemanagement:employeeprojects', array('index','view','emptasksgrid'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:notifications'));
									$acl->allow($tmroleText[$role], 'timemanagement:notifications', array('getnotifications','index'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:expenses'));
									$acl->allow($tmroleText[$role], 'timemanagement:expenses', array('index','edit','view','delete','download','uploadpreview','getprojectbyclientid','getfilename','submitexpense','expensereports','viewexpenses','viewexpensereports','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus','updateexpensestatus'));

		 $acl->addResource(new Zend_Acl_Resource('timemanagement:reports'));
									$acl->allow($tmroleText[$role], 'timemanagement:reports', array('index','employeereports','projectsreports','getempduration','getprojecttaskduration','tmreport'));
 } 
		
	   $acl->addResource(new Zend_Acl_Resource('login:index'));	
	   $acl->allow('viewer', 'login:index', array('index','confirmlink','forgotpassword','forgotsuccess','login','pass','browserfailure','forcelogout','useractivation'));

	   if($role == 1 ) 
	   {				 		    	
			   
		 $acl->addResource(new Zend_Acl_Resource('default:accountclasstype'));
                    $acl->allow('admin', 'default:accountclasstype', array('index','view','edit','addpopup','saveupdate','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:adddeductive'));
                    $acl->allow('admin', 'default:adddeductive', array('deductive','addcategory','edit','view','index','add','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:addemployeeleaves'));
                    $acl->allow('admin', 'default:addemployeeleaves', array('index','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:addperk'));
                    $acl->allow('admin', 'default:addperk', array('perk','addcategory','edit','delete','view','index','add'));

		 $acl->addResource(new Zend_Acl_Resource('default:address'));
                    $acl->allow('admin', 'default:address', array('index','add','edit','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:addresstype'));
                    $acl->allow('admin', 'default:addresstype', array('index','add','edit','view','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:agencylist'));
                    $acl->allow('admin', 'default:agencylist', array('index','add','view','edit','delete','deletepoc'));

		 $acl->addResource(new Zend_Acl_Resource('default:announcements'));
                    $acl->allow('admin', 'default:announcements', array('index','add','view','edit','getdepts','delete','uploadsave','uploaddelete'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalcategory'));
                    $acl->allow('admin', 'default:appraisalcategory', array('index','add','view','edit','delete','addpopup','getappraisalcategory'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryself'));
                    $acl->allow('admin', 'default:appraisalhistoryself', array('index','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryteam'));
                    $acl->allow('admin', 'default:appraisalhistoryteam', array('index','view','getsearchedempcontent'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalinit'));
                    $acl->allow('admin', 'default:appraisalinit', array('checkappadmin','getdepartmentsadmin','discardsteptwo','displayline','addlinemanager','displayreport','deletelinemanager','deletereportmanager','constructreportacc','constructacc','displayemployees','displaycontentreportacc','displaycontentacc','viewconfmanagers','confmanagers','displaymanagers','displayreportmanagers','getperiod','index','add','delete','view','edit','viewassigngroups','assigngroups','displaygroupedemployees','showgroupedemployees','viewgroupedemployees','savegroupedemployeesajax','changesettings','displaysettings','deletegroupedemployees','initializegroup','completeappraisal','checkemployeeresponse','getemployeeslinemanagers','savemngrorghierarchy','getconfiglinemanagers','validateconfig'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalmanager'));
                    $acl->allow('admin', 'default:appraisalmanager', array('submitmanager','deletemanagergroup','savemanagergroup','index','viewgroup','createnewgroup','showgroups','showviewgroups','view','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalquestions'));
                    $acl->allow('admin', 'default:appraisalquestions', array('index','addpopup','add','view','edit','delete','savequestionpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalratings'));
                    $acl->allow('admin', 'default:appraisalratings', array('index','addratings','add','view','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalself'));
                    $acl->allow('admin', 'default:appraisalself', array('index','edit','save'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalskills'));
                    $acl->allow('admin', 'default:appraisalskills', array('index','add','view','edit','delete','getappraisalskills','saveskillspopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalstatus'));
                    $acl->allow('admin', 'default:appraisalstatus', array('index','manager','managerstatus','checkappraisalimplementation','employee','employeestatus','employeeActi','addlinemanager','displaymanagers','updatelinemanager'));

		 $acl->addResource(new Zend_Acl_Resource('default:approvedrequisitions'));
                    $acl->allow('admin', 'default:approvedrequisitions', array('index','view','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:attendancestatuscode'));
                    $acl->allow('admin', 'default:attendancestatuscode', array('index','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:bank'));
                    $acl->allow('admin', 'default:bank', array('index','add','addpopup','edit','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:bankaccounttype'));
                    $acl->allow('admin', 'default:bankaccounttype', array('index','view','edit','addpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:bgscreeningtype'));
                    $acl->allow('admin', 'default:bgscreeningtype', array('index','view','edit','add','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:branch'));
                    $acl->allow('admin', 'default:branch', array('index','view','edit','addpopup','add','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:businessunits'));
                    $acl->allow('admin', 'default:businessunits', array('index','add','edit','view','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:calendar'));
                    $acl->allow('admin', 'default:calendar', array('index','add','edit','deletecalendar','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:candidatedetails'));
                    $acl->allow('admin', 'default:candidatedetails', array('index','view','edit','addpopup','add','delete','chkcandidate','uploadfile','deleteresume','download','multipleresume','getvendors','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:candidateeducation'));
                    $acl->allow('admin', 'default:candidateeducation', array('index','add','next','prev'));

		 $acl->addResource(new Zend_Acl_Resource('default:cardprovider'));
                    $acl->allow('admin', 'default:cardprovider', array('index','add','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:categories'));
                    $acl->allow('admin', 'default:categories', array('index','add','edit','view','delete','addnewcategory'));

		 $acl->addResource(new Zend_Acl_Resource('default:certificate'));
                    $acl->allow('admin', 'default:certificate', array('index','edit','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:certificatecategory'));
                    $acl->allow('admin', 'default:certificatecategory', array('index','edit','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:certprovider'));
                    $acl->allow('admin', 'default:certprovider', array('index','add','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:certprovidercategory'));
                    $acl->allow('admin', 'default:certprovidercategory', array('index','add','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:cities'));
                    $acl->allow('admin', 'default:cities', array('index','view','edit','delete','getcitiescand','addpopup','addnewcity'));

		 $acl->addResource(new Zend_Acl_Resource('default:clients'));
                    $acl->allow('admin', 'default:clients', array('index','edit','view','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:companyengagment'));
                    $acl->allow('admin', 'default:companyengagment', array('index','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:competencylevel'));
                    $acl->allow('admin', 'default:competencylevel', array('index','view','edit','addpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:countries'));
                    $acl->allow('admin', 'default:countries', array('index','view','edit','saveupdate','delete','getcountrycode','addpopup','addnewcountry'));

		 $acl->addResource(new Zend_Acl_Resource('default:currency'));
                    $acl->allow('admin', 'default:currency', array('index','view','edit','addpopup','delete','gettargetcurrency'));

		 $acl->addResource(new Zend_Acl_Resource('default:currencyconverter'));
                    $acl->allow('admin', 'default:currencyconverter', array('index','add','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:cvadd'));
                    $acl->allow('admin', 'default:cvadd', array('index','addcv','prev','uploadsave'));

		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                    $acl->allow('admin', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:deductivecategory'));
                    $acl->allow('admin', 'default:deductivecategory', array('index','add','edit','delete','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:departments'));
                    $acl->allow('admin', 'default:departments', array('index','view','viewpopup','add','addpopup','edit','editpopup','getdepartments','delete','getempnames'));

		 $acl->addResource(new Zend_Acl_Resource('default:digitalmedia'));
                    $acl->allow('admin', 'default:digitalmedia', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:digitalmediacategory'));
                    $acl->allow('admin', 'default:digitalmediacategory', array('index','addpopup','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:digitalmediatype'));
                    $acl->allow('admin', 'default:digitalmediatype', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:digitalpresence'));
                    $acl->allow('admin', 'default:digitalpresence', array('index','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:disability'));
                    $acl->allow('admin', 'default:disability', array('index','addpopup','addpopupnested','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:disabilitycategory'));
                    $acl->allow('admin', 'default:disabilitycategory', array('index','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:disabilitytype'));
                    $acl->allow('admin', 'default:disabilitytype', array('index','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryallincidents'));
                    $acl->allow('admin', 'default:disciplinaryallincidents', array('index','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryincident'));
                    $acl->allow('admin', 'default:disciplinaryincident', array('index','view','edit','add','getemployees','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinarymyincidents'));
                    $acl->allow('admin', 'default:disciplinarymyincidents', array('index','view','edit','saveemployeeappeal','getdisciplinaryincidentpdf'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryteamincidents'));
                    $acl->allow('admin', 'default:disciplinaryteamincidents', array('index','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryviolation'));
                    $acl->allow('admin', 'default:disciplinaryviolation', array('index','add','view','edit','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:doctype'));
                    $acl->allow('admin', 'default:doctype', array('index','add','edit','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:documentcategory'));
                    $acl->allow('admin', 'default:documentcategory', array('index','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:documentprovider'));
                    $acl->allow('admin', 'default:documentprovider', array('index','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:documentpurpose'));
                    $acl->allow('admin', 'default:documentpurpose', array('index','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:documentstatuslist'));
                    $acl->allow('admin', 'default:documentstatuslist', array('index','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:documenttype'));
                    $acl->allow('admin', 'default:documenttype', array('index','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:educationlevelcode'));
                    $acl->allow('admin', 'default:educationlevelcode', array('index','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:eeoccategory'));
                    $acl->allow('admin', 'default:eeoccategory', array('index','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:emailcontacts'));
                    $acl->allow('admin', 'default:emailcontacts', array('index','add','edit','getgroupoptions','view','delete','getmailcnt'));

		 $acl->addResource(new Zend_Acl_Resource('default:empconfiguration'));
                    $acl->allow('admin', 'default:empconfiguration', array('index','edit','add'));

		 $acl->addResource(new Zend_Acl_Resource('default:empleavesummary'));
                    $acl->allow('admin', 'default:empleavesummary', array('index','statusid','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:employee'));
                    $acl->allow('admin', 'default:employee', array('getemprequi','listview','index','getmoreemployees','changeorghead','add','edit','view','getdepartments','getpositions','delete','getempreportingmanagers','makeactiveinactive','changereportingmanager','addemppopup','uploadexcel','getindividualempdetails'));

		 $acl->addResource(new Zend_Acl_Resource('default:employmentstatus'));
                    $acl->allow('admin', 'default:employmentstatus', array('index','view','edit','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:emprecordaccessdetail'));
                    $acl->allow('admin', 'default:emprecordaccessdetail', array('index','add','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:empscreening'));
                    $acl->allow('admin', 'default:empscreening', array('index','add','edit','view','getemployeedata','getagencylist','getpocdata','forcedfullupdate','delete','checkscreeningstatus','uploadfeedback','download','deletefeedback'));

		 $acl->addResource(new Zend_Acl_Resource('default:ethniccode'));
                    $acl->allow('admin', 'default:ethniccode', array('index','view','edit','saveupdate','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardemployee'));
                    $acl->allow('admin', 'default:feedforwardemployee', array('index','edit','save'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardinit'));
                    $acl->allow('admin', 'default:feedforwardinit', array('index','add','getappraisaldetails','edit','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardmanager'));
                    $acl->allow('admin', 'default:feedforwardmanager', array('index','getmanagersratings','getdetailedratings','getdetailedratingsbyemp','getdetailedratingsbyques'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardquestions'));
                    $acl->allow('admin', 'default:feedforwardquestions', array('index','add','view','edit','delete','savepopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardstatus'));
                    $acl->allow('admin', 'default:feedforwardstatus', array('index','getffstatusemps','getfeedforwardstatus'));

		 $acl->addResource(new Zend_Acl_Resource('default:formview'));
                    $acl->allow('admin', 'default:formview', array('index','addpopup','update','getcolumns','getformelement','getparticulardataforall'));

		 $acl->addResource(new Zend_Acl_Resource('default:gender'));
                    $acl->allow('admin', 'default:gender', array('index','view','edit','saveupdate','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:geographygroup'));
                    $acl->allow('admin', 'default:geographygroup', array('index','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:groups'));
                    $acl->allow('admin', 'default:groups', array('index','add','view','edit','saveupdate','getgroupmenugroup'));

		 $acl->addResource(new Zend_Acl_Resource('default:heirarchy'));
                    $acl->allow('admin', 'default:heirarchy', array('index','edit','addlist','editlist','saveadddata','saveeditdata','deletelist'));

		 $acl->addResource(new Zend_Acl_Resource('default:holidaydates'));
                    $acl->allow('admin', 'default:holidaydates', array('index','add','addpopup','view','viewpopup','edit','editpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:holidaygroups'));
                    $acl->allow('admin', 'default:holidaygroups', array('index','add','view','edit','delete','getempnames','getholidaynames','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:identitycodes'));
                    $acl->allow('admin', 'default:identitycodes', array('index','add','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:identitydocuments'));
                    $acl->allow('admin', 'default:identitydocuments', array('index','add','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                    $acl->allow('admin', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));

		 $acl->addResource(new Zend_Acl_Resource('default:jobtitles'));
                    $acl->allow('admin', 'default:jobtitles', array('index','view','edit','add','addpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:language'));
                    $acl->allow('admin', 'default:language', array('index','view','edit','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:leave'));
                    $acl->allow('admin', 'default:leave', array('index','add','edit','delete','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:leavecategory'));
                    $acl->allow('admin', 'default:leavecategory', array('index','add','addpopup','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:leavemanagement'));
                    $acl->allow('admin', 'default:leavemanagement', array('index','add','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:leaverequest'));
                    $acl->allow('admin', 'default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails'));

		 $acl->addResource(new Zend_Acl_Resource('default:licensetype'));
                    $acl->allow('admin', 'default:licensetype', array('index','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:linecategory'));
                    $acl->allow('admin', 'default:linecategory', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:linetype'));
                    $acl->allow('admin', 'default:linetype', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:loadapprove'));
                    $acl->allow('admin', 'default:loadapprove', array('index','view','editpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:location'));
                    $acl->allow('admin', 'default:location', array('index','map','add','addpopup','addpopupnested','edit','delete','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:managemenus'));
                    $acl->allow('admin', 'default:managemenus', array('index','save'));

		 $acl->addResource(new Zend_Acl_Resource('default:manageremployeevacations'));
                    $acl->allow('admin', 'default:manageremployeevacations', array('index','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:maritalstatus'));
                    $acl->allow('admin', 'default:maritalstatus', array('index','view','edit','saveupdate','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:measurement'));
                    $acl->allow('admin', 'default:measurement', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:measurementindex'));
                    $acl->allow('admin', 'default:measurementindex', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:menucreate'));
                    $acl->allow('admin', 'default:menucreate', array('index','add','edit','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:militaryservice'));
                    $acl->allow('admin', 'default:militaryservice', array('index','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:missedhoursapprove'));
                    $acl->allow('admin', 'default:missedhoursapprove', array('index','view','editpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:mvc'));
                    $acl->allow('admin', 'default:mvc', array('create','addmodule'));

		 $acl->addResource(new Zend_Acl_Resource('default:myemployees'));
                    $acl->allow('admin', 'default:myemployees', array('index','view','perview','comview','skillsview','expview','eduview','trainingview','additionaldetailsview','jobhistoryview','add','edit','skillsedit','jobhistoryedit','expedit','eduedit','trainingedit','additionaldetailsedit','peredit','comedit','docview','docedit','employeereport','getempreportdata','empauto','emprptpdf','exportemployeereport','downloadreport'));

		 $acl->addResource(new Zend_Acl_Resource('default:myholidaycalendar'));
                    $acl->allow('admin', 'default:myholidaycalendar', array('index','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:myteamappraisal'));
                    $acl->allow('admin', 'default:myteamappraisal', array('savelineresponse','savemngresponse','getempcontent','index','getsearchedempcontent','getsearchedstatus','downloadpdf','downloadUploadedFile'));

		 $acl->addResource(new Zend_Acl_Resource('default:nationality'));
                    $acl->allow('admin', 'default:nationality', array('index','view','edit','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:nationalitycontextcode'));
                    $acl->allow('admin', 'default:nationalitycontextcode', array('index','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:numberformats'));
                    $acl->allow('admin', 'default:numberformats', array('index','add','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:organisationinfo'));
                    $acl->allow('admin', 'default:organisationinfo', array('index','edit','edit_old','saveupdate','uploadpreview','validateorgstartdate','getcompleteorgdata','addorghead'));

		 $acl->addResource(new Zend_Acl_Resource('default:pagetab'));
                    $acl->allow('admin', 'default:pagetab', array('index','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:payfrequency'));
                    $acl->allow('admin', 'default:payfrequency', array('index','addpopup','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:paymentcard'));
                    $acl->allow('admin', 'default:paymentcard', array('index','add','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:paymentcardtype'));
                    $acl->allow('admin', 'default:paymentcardtype', array('index','add','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:payroll'));
                    $acl->allow('admin', 'default:payroll', array('index','calculate','add','editpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:pendingleaves'));
                    $acl->allow('admin', 'default:pendingleaves', array('index','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:period'));
                    $acl->allow('admin', 'default:period', array('index','addpopup','edit','delete','add'));

		 $acl->addResource(new Zend_Acl_Resource('default:periodcontroltype'));
                    $acl->allow('admin', 'default:periodcontroltype', array('index','edit','add','checkpcpattern'));

		 $acl->addResource(new Zend_Acl_Resource('default:perkapprove'));
                    $acl->allow('admin', 'default:perkapprove', array('index','view','editpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:perkcategory'));
                    $acl->allow('admin', 'default:perkcategory', array('index','add','edit','delete','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:phoneno'));
                    $acl->allow('admin', 'default:phoneno', array('index','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:policydocuments'));
                    $acl->allow('admin', 'default:policydocuments', array('index','add','edit','view','delete','uploaddoc','deletedocument','addmultiple','uploadmultipledocs'));

		 $acl->addResource(new Zend_Acl_Resource('default:positions'));
                    $acl->allow('admin', 'default:positions', array('index','add','view','edit','addpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:project'));
                    $acl->allow('admin', 'default:project', array('index','add','edit','view','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:projectco'));
                    $acl->allow('admin', 'default:projectco', array('index','add','edit','view','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:projects'));
                    $acl->allow('admin', 'default:projects', array('index','view','delete','viewpopup','editpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:racecode'));
                    $acl->allow('admin', 'default:racecode', array('index','view','edit','saveupdate','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:rejectedrequisitions'));
                    $acl->allow('admin', 'default:rejectedrequisitions', array('index','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:religion'));
                    $acl->allow('admin', 'default:religion', array('index','view','edit','saveupdate','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:remunerationbasis'));
                    $acl->allow('admin', 'default:remunerationbasis', array('index','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:reports'));
                    $acl->allow('admin', 'default:reports', array('getrolepopup','emprolesgrouppopup','performancereport','previousappraisals','getselectedappraisaldata','getinterviewroundsdata','interviewrounds','rolesgroup','exportemprolesgroup','exportrolesgroupreport','exportinterviewrpt','exportactiveuserrpt','exportemployeereport','rolesgrouprptpdf','activeuserrptpdf','emprptpdf','interviewrptpdf','rolesgroupdata','emprolesgroup','emprolesgroupdata','activeuser','getactiveuserdata','getempreportdata','empauto','servicedeskreport','getsddata','servicedeskpdf','servicedeskexcel','employeereport','getdeptsemp','index','holidaygroupreports','getpdfreportholiday','getexcelreportholiday','leavesreport','getpdfreportleaves','getexcelreportleaves','leavesreporttabheader','leavemanagementreport','getpdfreportleavemanagement','getexcelreportleavemanagement','bunitauto','bunitcodeauto','getexcelreportbusinessunit','getbusinessunitspdf','businessunits','userlogreport','departments','exportdepartmentpdf','getexcelreportdepartment','candidaterptexcel','candidaterptpdf','getcandidatesreportdata','candidatesreport','requisitionauto','requisitionrptexcel','requisitionrptpdf','getrequisitionsstatusreportdata','requisitionstatusreport','activitylogreport','downloadreport','agencylistreport','agencynameauto','agencysebsiteauto','empscreening','getspecimennames','getagencynames','getexcelreportempscreening','getempscreeningpdf'));

		 $acl->addResource(new Zend_Acl_Resource('default:requisition'));
                    $acl->allow('admin', 'default:requisition', array('index','add','edit','viewhr','approverequisition','addcandidate','interview','getdepartments','getpositions','viewpopup','view','delete','getapprreqdata','chkreqforclose','getempreportingmanagers','getemailcount','getapprovers','approverejectrequisition','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:resume'));
                    $acl->allow('admin', 'default:resume', array('index','add','language','next','resume'));

		 $acl->addResource(new Zend_Acl_Resource('default:roles'));
                    $acl->allow('admin', 'default:roles', array('index','view','edit','saveupdate','delete','getgroupmenu'));

		 $acl->addResource(new Zend_Acl_Resource('default:scheduleinterviews'));
                    $acl->allow('admin', 'default:scheduleinterviews', array('candidatepopup','index','view','add','downloadresume','edit','getcandidates','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:section'));
                    $acl->allow('admin', 'default:section', array('index','add','edit','view','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicedeskconf'));
                    $acl->allow('admin', 'default:servicedeskconf', array('index','add','view','edit','delete','getemployees','getapprover','getbunitimplementation','getassets'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicedeskdepartment'));
                    $acl->allow('admin', 'default:servicedeskdepartment', array('index','add','view','edit','delete','addpopup','getrequests'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicedeskrequest'));
                    $acl->allow('admin', 'default:servicedeskrequest', array('index','add','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:shortlistedcandidates'));
                    $acl->allow('admin', 'default:shortlistedcandidates', array('index','edit','view','add','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:sitepreference'));
                    $acl->allow('admin', 'default:sitepreference', array('index','add','view','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:skill'));
                    $acl->allow('admin', 'default:skill', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:skillcategory'));
                    $acl->allow('admin', 'default:skillcategory', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:skillsource'));
                    $acl->allow('admin', 'default:skillsource', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:states'));
                    $acl->allow('admin', 'default:states', array('index','view','edit','delete','getstates','getstatescand','addpopup','addnewstate'));

		 $acl->addResource(new Zend_Acl_Resource('default:structure'));
                    $acl->allow('admin', 'default:structure', array('index'));

		 $acl->addResource(new Zend_Acl_Resource('default:tabgroups'));
                    $acl->allow('admin', 'default:tabgroups', array('index','add','view','edit','saveupdate','getgroupmenugroup'));

		 $acl->addResource(new Zend_Acl_Resource('default:tabroles'));
                    $acl->allow('admin', 'default:tabroles', array('index','view','edit','saveupdate','delete','getgroupmenu'));

		 $acl->addResource(new Zend_Acl_Resource('default:team'));
                    $acl->allow('admin', 'default:team', array('index','add','edit','view','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:timezone'));
                    $acl->allow('admin', 'default:timezone', array('index','view','edit','saveupdate','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:title'));
                    $acl->allow('admin', 'default:title', array('index','add','view','delete','addpopup','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:user'));
                    $acl->allow('admin', 'default:user', array('index','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:usermanagement'));
                    $acl->allow('admin', 'default:usermanagement', array('index','view','edit','saveupdate','delete','getemailofuser'));

		 $acl->addResource(new Zend_Acl_Resource('default:vendors'));
                    $acl->allow('admin', 'default:vendors', array('index','view','delete','edit','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:veteranstatus'));
                    $acl->allow('admin', 'default:veteranstatus', array('index','view','edit','addpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:wizard'));
                    $acl->allow('admin', 'default:wizard', array('index','managemenu','savemenu','configuresite','configureorganisation','updatewizardcompletion','configureunitsanddepartments','division','team','departments','section','project','savebusinessunit','configureservicerequest','savecategory'));

		 $acl->addResource(new Zend_Acl_Resource('default:workeligibilitydoctypes'));
                    $acl->allow('admin', 'default:workeligibilitydoctypes', array('index','view','edit','addpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:workexp'));
                    $acl->allow('admin', 'default:workexp', array('index','add','next','addpersonaldetail'));

		 $acl->addResource(new Zend_Acl_Resource('default:year'));
                    $acl->allow('admin', 'default:year', array('index','add','edit','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:businessdomain'));
                    $acl->allow('admin', 'default:businessdomain', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:division'));
                    $acl->allow('admin', 'default:division', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:entrymode'));
                    $acl->allow('admin', 'default:entrymode', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthissue'));
                    $acl->allow('admin', 'default:healthissue', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthissuecategory'));
                    $acl->allow('admin', 'default:healthissuecategory', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthissuetype'));
                    $acl->allow('admin', 'default:healthissuetype', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthseverity'));
                    $acl->allow('admin', 'default:healthseverity', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthseveritylevel'));
                    $acl->allow('admin', 'default:healthseveritylevel', array('index','addpopup','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:iddocument'));
                    $acl->allow('admin', 'default:iddocument', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:iddocumentstatus'));
                    $acl->allow('admin', 'default:iddocumentstatus', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:institution'));
                    $acl->allow('admin', 'default:institution', array('index','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:militarydischargetype'));
                    $acl->allow('admin', 'default:militarydischargetype', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:militaryrank'));
                    $acl->allow('admin', 'default:militaryrank', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:militaryservicebranch'));
                    $acl->allow('admin', 'default:militaryservicebranch', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:militaryservicetype'));
                    $acl->allow('admin', 'default:militaryservicetype', array('index','add','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:recordaccess'));
                    $acl->allow('admin', 'default:recordaccess', array('index','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:relationship'));
                    $acl->allow('admin', 'default:relationship', array('index','add','addpopup','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:sbuengagment'));
                    $acl->allow('admin', 'default:sbuengagment', array('index','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:studyfield'));
                    $acl->allow('admin', 'default:studyfield', array('index','add','edit','view','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:studylevel'));
                    $acl->allow('admin', 'default:studylevel', array('index','add','edit','view','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:theme'));
                    $acl->allow('admin', 'default:theme', array('index','add','edit','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('assets:assetcategories'));
                    $acl->allow('admin', 'assets:assetcategories', array('index','edit','view','delete','addpopup','addsubcatpopup','assetuserlog'));

		 $acl->addResource(new Zend_Acl_Resource('assets:assetcategory'));
                    $acl->allow('admin', 'assets:assetcategory', array('index','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('assets:assetinstant'));
                    $acl->allow('admin', 'assets:assetinstant', array('index','edit','delete','uploadsave','uploaddelete','view','getsubcategories','deleteimage','downloadimage','getemployeesdata','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('assets:assets'));
                    $acl->allow('admin', 'assets:assets', array('index','edit','addpopup','addpopupnested'));

		 $acl->addResource(new Zend_Acl_Resource('assets:assettype'));
                    $acl->allow('admin', 'assets:assettype', array('index','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('assets:uom'));
                    $acl->allow('admin', 'assets:uom', array('index','edit','addpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:advances'));
                    $acl->allow('admin', 'expenses:advances', array('index','getprojects','myadvances','viewmoreadvances','clearadvancesdata','addreturnpopup'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:employeeadvances'));
                    $acl->allow('admin', 'expenses:employeeadvances', array('index','edit','delete','view'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:expensecategories'));
                    $acl->allow('admin', 'expenses:expensecategories', array('index','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:expenses'));
                    $acl->allow('admin', 'expenses:expenses', array('index','edit','clone','view','delete','addpopup','uploadsave','uploaddelete','displayreceipts','addtrippopup','submitexpense','addreceiptimage','expensestatus','listreportingmangers','viewmoremanagers','forwardexpenseto','downloadexpensepdf','bulkexpenses','getcategories','getprojects','getcurrency','uploadedfiles'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:myemployeeexpenses'));
                    $acl->allow('admin', 'expenses:myemployeeexpenses', array('index','view'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:paymentmode'));
                    $acl->allow('admin', 'expenses:paymentmode', array('index','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:receipts'));
                    $acl->allow('admin', 'expenses:receipts', array('index','downloadreceipt','downloadexpensereceipt','deletereceipt','uploadsave','displayreceipts','viewmorereceipts','listexpenses','addreceipttoexpense','viewmoreexpenses','cleardata','showreceiptspopup','listtrips','viewmoretrips','addexpensetotrip'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:trips'));
                    $acl->allow('admin', 'expenses:trips', array('index','edit','view','delete','addpopup','tripstatus','deleteexpense','downloadtrippdf'));

		 $acl->addResource(new Zend_Acl_Resource('exit:allexitproc'));
                    $acl->allow('admin', 'exit:allexitproc', array('index','edit','view','editpopup','updateexitprocess','assignquestions'));

		 $acl->addResource(new Zend_Acl_Resource('exit:configureexitqs'));
                    $acl->allow('admin', 'exit:configureexitqs', array('index','add','edit','view','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitproc'));
                    $acl->allow('admin', 'exit:exitproc', array('index','questions','view','add','savequestions'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitprocsettings'));
                    $acl->allow('admin', 'exit:exitprocsettings', array('index','view','add','edit','delete','getdepartments'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exittypes'));
                    $acl->allow('admin', 'exit:exittypes', array('index','add','edit','view','delete','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:attendancereport'));
                    $acl->allow('admin', 'schedule:attendancereport', array('index','printreport'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:cycleshift'));
                    $acl->allow('admin', 'schedule:cycleshift', array('index','edit','view','addpopup','editpopup'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:manageremployeeovertime'));
                    $acl->allow('admin', 'schedule:manageremployeeovertime', array('index','view','editpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:overtimecategory'));
                    $acl->allow('admin', 'schedule:overtimecategory', array('index','add','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:overtime'));
                    $acl->allow('admin', 'schedule:overtime', array('index','add','edit','view','delete','addnewot','addcategory','checktime'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:schedulecycle'));
                    $acl->allow('admin', 'schedule:schedulecycle', array('index','dayofexists','edit','view','addpopup','editpopup','getcycledata'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:timecategory'));
                    $acl->allow('admin', 'schedule:timecategory', array('index','edit','delete'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:timeschedule'));
                    $acl->allow('admin', 'schedule:timeschedule', array('index','edit','view','addpopup'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:timetable'));
                    $acl->allow('admin', 'schedule:timetable', array('index','addtime','addpopup','edit','view','viewall','delete'));

		 $acl->addResource(new Zend_Acl_Resource('payroll:report'));
                    $acl->allow('admin', 'payroll:report', array('index','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:processes'));
                    $acl->allow('admin', 'default:processes', array('index','addpopup','editpopup','viewpopup','delete','savecomments','displaycomments','savefeedback'));

		 $acl->addResource(new Zend_Acl_Resource('default:interviewrounds'));
                    $acl->allow('admin', 'default:interviewrounds', array('index','addpopup','editpopup','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:empperformanceappraisal'));
                    $acl->allow('admin', 'default:empperformanceappraisal', array('index','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:emppayslips'));
                    $acl->allow('admin', 'default:emppayslips', array('index','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empbenefits'));
                    $acl->allow('admin', 'default:empbenefits', array('index','edit','view','editempperk','perktype','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:emprequisitiondetails'));
                    $acl->allow('admin', 'default:emprequisitiondetails', array('index','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empremunerationdetails'));
                    $acl->allow('admin', 'default:empremunerationdetails', array('index','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empsecuritycredentials'));
                    $acl->allow('admin', 'default:empsecuritycredentials', array('index','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:apprreqcandidates'));
                    $acl->allow('admin', 'default:apprreqcandidates', array('index','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:emppersonaldetails'));
                    $acl->allow('admin', 'default:emppersonaldetails', array('index','view','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:empiddocument'));
                    $acl->allow('admin', 'default:empiddocument', array('index','add','edit','uploadpreview','editpopup','viewpopup','checkisnumbered','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empcommunicationdetails'));
                    $acl->allow('admin', 'default:empcommunicationdetails', array('index','view','edit'));

		 $acl->addResource(new Zend_Acl_Resource('default:trainingandcertificationdetails'));
                    $acl->allow('admin', 'default:trainingandcertificationdetails', array('index','edit','view','uploadpreview','addpopup','editpopup','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:experiencedetails'));
                    $acl->allow('admin', 'default:experiencedetails', array('index','edit','view','addpopup','editpopup','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:educationdetails'));
                    $acl->allow('admin', 'default:educationdetails', array('index','edit','view','addpopup','viewpopup','editpopup','uploadpreview','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:medicalclaims'));
                    $acl->allow('admin', 'default:medicalclaims', array('index','edit','addpopup','viewpopup','editpopup','delete','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empleaves'));
                    $acl->allow('admin', 'default:empleaves', array('index','addpopup','editpopup','edit','view','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:empskills'));
                    $acl->allow('admin', 'default:empskills', array('index','edit','view','uploadpreview','addpopup','viewpopup','editpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:empphoneno'));
                    $acl->allow('admin', 'default:empphoneno', array('index','edit','view','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empbankaccount'));
                    $acl->allow('admin', 'default:empbankaccount', array('index','edit','addpopup','editpopup','viewpopup','view','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:empdeductive'));
                    $acl->allow('admin', 'default:empdeductive', array('index','edit','view','editpopup','editempdeductive','deductivetype','addpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:emppresence'));
                    $acl->allow('admin', 'default:emppresence', array('index','edit','addpopup','editpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empschedule'));
                    $acl->allow('admin', 'default:empschedule', array('index','getschedules','edit','view','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empaddress'));
                    $acl->allow('admin', 'default:empaddress', array('index','edit','addpopup','editpopup','view','delete','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:emppos'));
                    $acl->allow('admin', 'default:emppos', array('index','edit','addpopup','editpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:emprole'));
                    $acl->allow('admin', 'default:emprole', array('index','edit','addpopup','editpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empuser'));
                    $acl->allow('admin', 'default:empuser', array('index','edit','addpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:emprecordaccess'));
                    $acl->allow('admin', 'default:emprecordaccess', array('index','edit','addpopup','editpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:disabilitydetails'));
                    $acl->allow('admin', 'default:disabilitydetails', array('index','addpopup','editpopup','edit','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:workeligibilitydetails'));
                    $acl->allow('admin', 'default:workeligibilitydetails', array('index','add','edit','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:emphealthissue'));
                    $acl->allow('admin', 'default:emphealthissue', array('index','addpopup','uploadpreview','viewpopup','edit','view','editpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empadditionaldetails'));
                    $acl->allow('admin', 'default:empadditionaldetails', array('index','edit','view','addpopup','uploadpreview','viewpopup','downloadfiles','editpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:visaandimmigrationdetails'));
                    $acl->allow('admin', 'default:visaandimmigrationdetails', array('index','edit','view','addpopup','editpopup','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:creditcarddetails'));
                    $acl->allow('admin', 'default:creditcarddetails', array('index','add','edit','view','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:dependencydetails'));
                    $acl->allow('admin', 'default:dependencydetails', array('index','edit','view','addpopup','editpopup','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:empholidays'));
                    $acl->allow('admin', 'default:empholidays', array('index','edit','addpopup','editpopup','view','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empjobhistory'));
                    $acl->allow('admin', 'default:empjobhistory', array('index','edit','view','addpopup','viewpopup','editpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:assetdetails'));
                    $acl->allow('admin', 'default:assetdetails', array('index','edit','view','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empsalarydetails'));
                    $acl->allow('admin', 'default:empsalarydetails', array('index','edit','addpopup','editpopup','view','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:logmanager'));
                    $acl->allow('admin', 'default:logmanager', array('index','view','empnamewithidauto'));

		 $acl->addResource(new Zend_Acl_Resource('default:userloginlog'));
                    $acl->allow('admin', 'default:userloginlog', array('index','empnameauto','empidauto','empipaddressauto','empemailauto'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:logreport'));
                    $acl->allow('admin', 'schedule:logreport', array('index'));
			   		  	   				   
	   }  
	   if($role == 2 )
           {
		 $acl->addResource(new Zend_Acl_Resource('default:accountclasstype'));
                            $acl->allow('management', 'default:accountclasstype', array('index','addpopup','saveupdate','add','edit','delete','view','Account Class Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:adddeductive'));
                            $acl->allow('management', 'default:adddeductive', array('deductive','addcategory','index','add','edit','delete','view','Load'));

		 $acl->addResource(new Zend_Acl_Resource('default:addemployeeleaves'));
                            $acl->allow('management', 'default:addemployeeleaves', array('index','add','edit','view','Add Employee Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:addperk'));
                            $acl->allow('management', 'default:addperk', array('perk','addcategory','index','add','edit','delete','view','Perk'));

		 $acl->addResource(new Zend_Acl_Resource('default:address'));
                            $acl->allow('management', 'default:address', array('index','add','edit','view','Address'));

		 $acl->addResource(new Zend_Acl_Resource('default:addresstype'));
                            $acl->allow('management', 'default:addresstype', array('index','addpopup','add','edit','delete','view','Address Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:agencylist'));
                            $acl->allow('management', 'default:agencylist', array('index','deletepoc','add','edit','delete','view','Agencies'));

		 $acl->addResource(new Zend_Acl_Resource('default:announcements'));
                            $acl->allow('management', 'default:announcements', array('index','getdepts','uploadsave','uploaddelete','view','Announcements'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalcategory'));
                            $acl->allow('management', 'default:appraisalcategory', array('index','addpopup','getappraisalcategory','view','Parameters'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryself'));
                            $acl->allow('management', 'default:appraisalhistoryself', array('index','view','My Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryteam'));
                            $acl->allow('management', 'default:appraisalhistoryteam', array('index','getsearchedempcontent','view','Team Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalinit'));
                            $acl->allow('management', 'default:appraisalinit', array('checkappadmin','getdepartmentsadmin','discardsteptwo','displayline','addlinemanager','displayreport','deletelinemanager','deletereportmanager','constructreportacc','constructacc','displayemployees','displaycontentreportacc','displaycontentacc','viewconfmanagers','confmanagers','displaymanagers','displayreportmanagers','getperiod','index','viewassigngroups','assigngroups','displaygroupedemployees','showgroupedemployees','viewgroupedemployees','savegroupedemployeesajax','changesettings','displaysettings','deletegroupedemployees','initializegroup','completeappraisal','checkemployeeresponse','getemployeeslinemanagers','savemngrorghierarchy','getconfiglinemanagers','validateconfig','add','edit','view','Initialize Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalmanager'));
                            $acl->allow('management', 'default:appraisalmanager', array('submitmanager','deletemanagergroup','savemanagergroup','index','viewgroup','createnewgroup','showgroups','showviewgroups','edit','view','Manager Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalquestions'));
                            $acl->allow('management', 'default:appraisalquestions', array('index','addpopup','savequestionpopup','add','edit','delete','view','Questions'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalratings'));
                            $acl->allow('management', 'default:appraisalratings', array('index','addratings','add','edit','view','Ratings'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalself'));
                            $acl->allow('management', 'default:appraisalself', array('index','save','edit','view','Self Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalskills'));
                            $appraisalskills_add = 'yes';
                                if($this->id_param == '' && $appraisalskills_add == 'yes')
                                    $acl->allow('management','default:appraisalskills', array('index','getappraisalskills','saveskillspopup','add','view','Skills','edit'));

                                else
                                    $acl->allow('management','default:appraisalskills', array('index','getappraisalskills','saveskillspopup','add','view','Skills'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:appraisalstatus'));
                            $acl->allow('management', 'default:appraisalstatus', array('index','manager','managerstatus','checkappraisalimplementation','employee','employeestatus','employeeActi','addlinemanager','displaymanagers','updatelinemanager','add','edit','delete','view','Employee Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:approvedrequisitions'));
                            $acl->allow('management', 'default:approvedrequisitions', array('index','view','Approved Requisitions'));

		 $acl->addResource(new Zend_Acl_Resource('default:attendancestatuscode'));
                            $acl->allow('management', 'default:attendancestatuscode', array('index','add','edit','delete','view','Attendance Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:bank'));
                            $acl->allow('management', 'default:bank', array('index','addpopup','add','edit','delete','view','Bank'));

		 $acl->addResource(new Zend_Acl_Resource('default:bankaccounttype'));
                            $acl->allow('management', 'default:bankaccounttype', array('index','addpopup','add','edit','view','Bank Account Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:bgscreeningtype'));
                            $acl->allow('management', 'default:bgscreeningtype', array('index','addpopup','add','edit','delete','view','Screening Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:branch'));
                            $acl->allow('management', 'default:branch', array('index','addpopup','add','edit','delete','view','Branch'));

		 $acl->addResource(new Zend_Acl_Resource('default:businessunits'));
                            $acl->allow('management', 'default:businessunits', array('index','addpopup','view','Business Units'));

		 $acl->addResource(new Zend_Acl_Resource('default:calendar'));
                            $acl->allow('management', 'default:calendar', array('index','deletecalendar','addpopup','add','edit','delete','calendar'));

		 $acl->addResource(new Zend_Acl_Resource('default:candidatedetails'));
                            $acl->allow('management', 'default:candidatedetails', array('index','addpopup','chkcandidate','uploadfile','deleteresume','download','multipleresume','getvendors','viewpopup','view','Candidates'));

		 $acl->addResource(new Zend_Acl_Resource('default:categories'));
                            $acl->allow('management', 'default:categories', array('index','addnewcategory','add','edit','delete','view','Manage Categories'));

		 $acl->addResource(new Zend_Acl_Resource('default:cities'));
                            $cities_add = 'yes';
                                if($this->id_param == '' && $cities_add == 'yes')
                                    $acl->allow('management','default:cities', array('index','getcitiescand','addpopup','addnewcity','add','delete','view','Cities','edit'));

                                else
                                    $acl->allow('management','default:cities', array('index','getcitiescand','addpopup','addnewcity','add','delete','view','Cities'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:clients'));
                            $acl->allow('management', 'default:clients', array('index','addpopup','add','edit','delete','view','Clients'));

		 $acl->addResource(new Zend_Acl_Resource('default:companyengagment'));
                            $acl->allow('management', 'default:companyengagment', array('index','add','edit','delete','view','Company Engagment'));

		 $acl->addResource(new Zend_Acl_Resource('default:competencylevel'));
                            $acl->allow('management', 'default:competencylevel', array('index','addpopup','add','edit','delete','view','Competency Levels'));

		 $acl->addResource(new Zend_Acl_Resource('default:countries'));
                            $countries_add = 'yes';
                                if($this->id_param == '' && $countries_add == 'yes')
                                    $acl->allow('management','default:countries', array('index','saveupdate','getcountrycode','addpopup','addnewcountry','add','delete','view','Countries','edit'));

                                else
                                    $acl->allow('management','default:countries', array('index','saveupdate','getcountrycode','addpopup','addnewcountry','add','delete','view','Countries'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:currency'));
                            $acl->allow('management', 'default:currency', array('index','addpopup','gettargetcurrency','add','edit','delete','view','Currencies'));

		 $acl->addResource(new Zend_Acl_Resource('default:currencyconverter'));
                            $acl->allow('management', 'default:currencyconverter', array('index','add','edit','delete','view','Currency Conversions'));

		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                        $acl->allow('management', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:deductivecategory'));
                            $acl->allow('management', 'default:deductivecategory', array('index','add','edit','delete','view','Load Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:departments'));
                            $acl->allow('management', 'default:departments', array('index','viewpopup','addpopup','editpopup','getdepartments','getempnames','view','Departments'));

		 $acl->addResource(new Zend_Acl_Resource('default:digitalmedia'));
                            $acl->allow('management', 'default:digitalmedia', array('index','addpopup','add','edit','view','Digital Media'));

		 $acl->addResource(new Zend_Acl_Resource('default:digitalmediacategory'));
                            $acl->allow('management', 'default:digitalmediacategory', array('index','addpopup','add','edit','view','Digital Media Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:digitalmediatype'));
                            $acl->allow('management', 'default:digitalmediatype', array('index','addpopup','add','edit','view','Digital Media Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:digitalpresence'));
                            $acl->allow('management', 'default:digitalpresence', array('index','add','edit','view','Digital Presence'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryallincidents'));
                            $acl->allow('management', 'default:disciplinaryallincidents', array('index','view','All Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryincident'));
                            $acl->allow('management', 'default:disciplinaryincident', array('index','getemployees','add','edit','delete','view','Raise An Incident'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinarymyincidents'));
                            $acl->allow('management', 'default:disciplinarymyincidents', array('index','saveemployeeappeal','getdisciplinaryincidentpdf','edit','view','My Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryteamincidents'));
                            $acl->allow('management', 'default:disciplinaryteamincidents', array('index','view','Team Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryviolation'));
                            $acl->allow('management', 'default:disciplinaryviolation', array('index','addpopup','add','edit','delete','view','Violation Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:doctype'));
                            $acl->allow('management', 'default:doctype', array('index','add','edit','delete','view','Document Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:educationlevelcode'));
                            $acl->allow('management', 'default:educationlevelcode', array('index','add','edit','delete','view','Education Levels'));

		 $acl->addResource(new Zend_Acl_Resource('default:eeoccategory'));
                            $acl->allow('management', 'default:eeoccategory', array('index','add','edit','delete','view','EEOC Categories'));

		 $acl->addResource(new Zend_Acl_Resource('default:emailcontacts'));
                            $acl->allow('management', 'default:emailcontacts', array('index','getgroupoptions','getmailcnt','add','edit','delete','view','Email Contacts'));

		 $acl->addResource(new Zend_Acl_Resource('default:empconfiguration'));
                            $acl->allow('management', 'default:empconfiguration', array('index','edit','Employee Tabs'));

		 $acl->addResource(new Zend_Acl_Resource('default:empleavesummary'));
                            $acl->allow('management', 'default:empleavesummary', array('index','statusid','view','Employee Leave Summary'));

		 $acl->addResource(new Zend_Acl_Resource('default:employee'));
                            $acl->allow('management', 'default:employee', array('getemprequi','listview','index','getmoreemployees','changeorghead','getdepartments','getpositions','getempreportingmanagers','makeactiveinactive','changereportingmanager','addemppopup','uploadexcel','getindividualempdetails','add','edit','view','Employees'));

		 $acl->addResource(new Zend_Acl_Resource('default:employmentstatus'));
                            $acl->allow('management', 'default:employmentstatus', array('index','addpopup','add','edit','delete','view','Employment Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:empscreening'));
                            $acl->allow('management', 'default:empscreening', array('index','getemployeedata','getagencylist','getpocdata','forcedfullupdate','checkscreeningstatus','uploadfeedback','download','deletefeedback','add','edit','delete','view','Employee/Candidate Screening'));

		 $acl->addResource(new Zend_Acl_Resource('default:ethniccode'));
                            $acl->allow('management', 'default:ethniccode', array('index','saveupdate','addpopup','add','edit','delete','view','Ethnic Codes'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardemployee'));
                            $acl->allow('management', 'default:feedforwardemployee', array('index','save','edit','view','Appraise Your Manager'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardinit'));
                            $acl->allow('management', 'default:feedforwardinit', array('index','getappraisaldetails','add','edit','view','Initialize Feedforward'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardmanager'));
                            $acl->allow('management', 'default:feedforwardmanager', array('index','getmanagersratings','getdetailedratings','getdetailedratingsbyemp','getdetailedratingsbyques','view','Manager Feedforward'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardquestions'));
                            $acl->allow('management', 'default:feedforwardquestions', array('index','savepopup','add','edit','delete','view','Questions'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardstatus'));
                            $acl->allow('management', 'default:feedforwardstatus', array('index','getffstatusemps','getfeedforwardstatus','view','Employee Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:formview'));
                            $acl->allow('management', 'default:formview', array('index','addpopup','update','getcolumns','getformelement','getparticulardataforall','add','edit','delete','view','Form option'));

		 $acl->addResource(new Zend_Acl_Resource('default:gender'));
                            $acl->allow('management', 'default:gender', array('index','saveupdate','addpopup','add','edit','delete','view','Gender'));

		 $acl->addResource(new Zend_Acl_Resource('default:geographygroup'));
                            $acl->allow('management', 'default:geographygroup', array('index','add','edit','delete','view','Geo Groups'));

		 $acl->addResource(new Zend_Acl_Resource('default:heirarchy'));
                            $acl->allow('management', 'default:heirarchy', array('index','addlist','editlist','saveadddata','saveeditdata','deletelist','Organization Hierarchy'));

		 $acl->addResource(new Zend_Acl_Resource('default:holidaydates'));
                            $acl->allow('management', 'default:holidaydates', array('index','addpopup','viewpopup','editpopup','add','edit','delete','view','Manage Holidays'));

		 $acl->addResource(new Zend_Acl_Resource('default:holidaygroups'));
                            $acl->allow('management', 'default:holidaygroups', array('index','getempnames','getholidaynames','addpopup','add','edit','delete','view','Manage Holiday Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:identitycodes'));
                            $acl->allow('management', 'default:identitycodes', array('index','addpopup','add','edit','Identity Codes'));

		 $acl->addResource(new Zend_Acl_Resource('default:identitydocuments'));
                            $acl->allow('management', 'default:identitydocuments', array('index','add','edit','delete','view','Identity Documents'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                        $acl->allow('management', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));

		 $acl->addResource(new Zend_Acl_Resource('default:jobtitles'));
                            $acl->allow('management', 'default:jobtitles', array('index','addpopup','add','edit','delete','view','Job Titles'));

		 $acl->addResource(new Zend_Acl_Resource('default:language'));
                            $acl->allow('management', 'default:language', array('index','addpopup','add','edit','delete','view','Languages'));

		 $acl->addResource(new Zend_Acl_Resource('default:leave'));
                            $acl->allow('management', 'default:leave', array('index','add','edit','delete','view','Leave '));

		 $acl->addResource(new Zend_Acl_Resource('default:leavecategory'));
                            $acl->allow('management', 'default:leavecategory', array('index','addpopup','add','edit','delete','view','Leave Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:leavemanagement'));
                            $acl->allow('management', 'default:leavemanagement', array('index','add','edit','delete','view','Leave Management Options'));

		 $acl->addResource(new Zend_Acl_Resource('default:leaverequest'));
                            $leaverequest_add = 'yes';
                                if($this->id_param == '' && $leaverequest_add == 'yes')
                                    $acl->allow('management','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request','edit'));

                                else
                                    $acl->allow('management','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:licensetype'));
                            $acl->allow('management', 'default:licensetype', array('index','add','edit','delete','view','License Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:linecategory'));
                            $acl->allow('management', 'default:linecategory', array('index','addpopup','add','edit','view','Line category'));

		 $acl->addResource(new Zend_Acl_Resource('default:linetype'));
                            $acl->allow('management', 'default:linetype', array('index','addpopup','add','edit','view','Line type'));

		 $acl->addResource(new Zend_Acl_Resource('default:loadapprove'));
                            $acl->allow('management', 'default:loadapprove', array('index','editpopup','edit','delete','view','Employee Load'));

		 $acl->addResource(new Zend_Acl_Resource('default:location'));
                            $acl->allow('management', 'default:location', array('index','map','addpopup','addpopupnested','add','edit','delete','view','Location'));

		 $acl->addResource(new Zend_Acl_Resource('default:managemenus'));
                            $acl->allow('management', 'default:managemenus', array('index','save','add','edit','delete','view','Modules'));

		 $acl->addResource(new Zend_Acl_Resource('default:manageremployeevacations'));
                            $acl->allow('management', 'default:manageremployeevacations', array('index','edit','view','Employee Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:maritalstatus'));
                            $acl->allow('management', 'default:maritalstatus', array('index','saveupdate','addpopup','add','edit','delete','view','Marital Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:menucreate'));
                            $acl->allow('management', 'default:menucreate', array('index','view','add','edit','delete','Create Menu'));

		 $acl->addResource(new Zend_Acl_Resource('default:militaryservice'));
                            $acl->allow('management', 'default:militaryservice', array('index','add','edit','delete','view','Military Service Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:missedhoursapprove'));
                            $acl->allow('management', 'default:missedhoursapprove', array('index','editpopup','edit','delete','view','Employee Missed Hours'));

		 $acl->addResource(new Zend_Acl_Resource('default:mvc'));
                            $acl->allow('management', 'default:mvc', array('create','addmodule','add','edit','delete','MVC create'));

		 $acl->addResource(new Zend_Acl_Resource('default:myemployees'));
                            $acl->allow('management', 'default:myemployees', array('index','perview','comview','skillsview','expview','eduview','trainingview','additionaldetailsview','jobhistoryview','skillsedit','jobhistoryedit','expedit','eduedit','trainingedit','additionaldetailsedit','peredit','comedit','docview','docedit','employeereport','getempreportdata','empauto','emprptpdf','exportemployeereport','downloadreport','add','edit','view','My Team'));

		 $acl->addResource(new Zend_Acl_Resource('default:myholidaycalendar'));
                            $acl->allow('management', 'default:myholidaycalendar', array('index','view','My Holiday Calendar'));

		 $acl->addResource(new Zend_Acl_Resource('default:myteamappraisal'));
                            $acl->allow('management', 'default:myteamappraisal', array('savelineresponse','savemngresponse','getempcontent','index','getsearchedempcontent','getsearchedstatus','downloadpdf','downloadUploadedFile','My Team Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:nationality'));
                            $acl->allow('management', 'default:nationality', array('index','addpopup','add','edit','delete','view','Nationalities'));

		 $acl->addResource(new Zend_Acl_Resource('default:nationalitycontextcode'));
                            $acl->allow('management', 'default:nationalitycontextcode', array('index','add','edit','delete','view','Nationality Context Codes'));

		 $acl->addResource(new Zend_Acl_Resource('default:numberformats'));
                            $acl->allow('management', 'default:numberformats', array('index','add','edit','delete','view','Number Formats'));

		 $acl->addResource(new Zend_Acl_Resource('default:organisationinfo'));
                            $acl->allow('management', 'default:organisationinfo', array('index','edit_old','saveupdate','uploadpreview','validateorgstartdate','getcompleteorgdata','addorghead','Organization Info'));

		 $acl->addResource(new Zend_Acl_Resource('default:payfrequency'));
                            $acl->allow('management', 'default:payfrequency', array('index','addpopup','add','edit','delete','view','Pay Frequency'));

		 $acl->addResource(new Zend_Acl_Resource('default:payroll'));
                            $acl->allow('management', 'default:payroll', array('index','calculate','editpopup','add','edit','view','Payroll'));

		 $acl->addResource(new Zend_Acl_Resource('default:pendingleaves'));
                            $acl->allow('management', 'default:pendingleaves', array('index','delete','view','My Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:period'));
                            $acl->allow('management', 'default:period', array('index','addpopup','add','edit','delete','period'));

		 $acl->addResource(new Zend_Acl_Resource('default:periodcontroltype'));
                            $acl->allow('management', 'default:periodcontroltype', array('index','checkpcpattern','add','edit','delete','view','Period Control'));

		 $acl->addResource(new Zend_Acl_Resource('default:perkapprove'));
                            $acl->allow('management', 'default:perkapprove', array('index','editpopup','edit','delete','view','Employee Perk'));

		 $acl->addResource(new Zend_Acl_Resource('default:perkcategory'));
                            $acl->allow('management', 'default:perkcategory', array('index','add','edit','delete','view','Perk Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:phoneno'));
                            $acl->allow('management', 'default:phoneno', array('index','add','edit','view','Phone number'));

		 $acl->addResource(new Zend_Acl_Resource('default:policydocuments'));
                            $acl->allow('management', 'default:policydocuments', array('index','uploaddoc','deletedocument','addmultiple','uploadmultipledocs','view','View/Manage Policy Documents'));

		 $acl->addResource(new Zend_Acl_Resource('default:positions'));
                            $acl->allow('management', 'default:positions', array('index','addpopup','add','edit','delete','view','Positions'));

		 $acl->addResource(new Zend_Acl_Resource('default:project'));
                            $acl->allow('management', 'default:project', array('index','addpopup','add','edit','delete','view','Project'));

		 $acl->addResource(new Zend_Acl_Resource('default:projectco'));
                            $acl->allow('management', 'default:projectco', array('index','addpopup','add','edit','delete','view','Project Participant'));

		 $acl->addResource(new Zend_Acl_Resource('default:projects'));
                            $acl->allow('management', 'default:projects', array('index','viewpopup','editpopup','add','edit','delete','view','Projects'));

		 $acl->addResource(new Zend_Acl_Resource('default:racecode'));
                            $acl->allow('management', 'default:racecode', array('index','saveupdate','addpopup','add','edit','delete','view','Race Codes'));

		 $acl->addResource(new Zend_Acl_Resource('default:rejectedrequisitions'));
                            $acl->allow('management', 'default:rejectedrequisitions', array('index','view','Rejected Requisitions'));

		 $acl->addResource(new Zend_Acl_Resource('default:religion'));
                            $acl->allow('management', 'default:religion', array('index','saveupdate','addpopup','add','edit','delete','view','Religions'));

		 $acl->addResource(new Zend_Acl_Resource('default:remunerationbasis'));
                            $acl->allow('management', 'default:remunerationbasis', array('index','add','edit','delete','view','Remuneration Basis'));

		 $acl->addResource(new Zend_Acl_Resource('default:reports'));
                            $acl->allow('management', 'default:reports', array('getrolepopup','emprolesgrouppopup','performancereport','previousappraisals','getselectedappraisaldata','getinterviewroundsdata','interviewrounds','rolesgroup','exportemprolesgroup','exportrolesgroupreport','exportinterviewrpt','exportactiveuserrpt','exportemployeereport','rolesgrouprptpdf','activeuserrptpdf','emprptpdf','interviewrptpdf','rolesgroupdata','emprolesgroup','emprolesgroupdata','activeuser','getactiveuserdata','getempreportdata','empauto','servicedeskreport','getsddata','servicedeskpdf','servicedeskexcel','employeereport','getdeptsemp','index','holidaygroupreports','getpdfreportholiday','getexcelreportholiday','leavesreport','getpdfreportleaves','getexcelreportleaves','leavesreporttabheader','leavemanagementreport','getpdfreportleavemanagement','getexcelreportleavemanagement','bunitauto','bunitcodeauto','getexcelreportbusinessunit','getbusinessunitspdf','businessunits','userlogreport','departments','exportdepartmentpdf','getexcelreportdepartment','candidaterptexcel','candidaterptpdf','getcandidatesreportdata','candidatesreport','requisitionauto','requisitionrptexcel','requisitionrptpdf','getrequisitionsstatusreportdata','requisitionstatusreport','activitylogreport','downloadreport','agencylistreport','agencynameauto','agencysebsiteauto','empscreening','getspecimennames','getagencynames','getexcelreportempscreening','getempscreeningpdf','add','edit','delete','view','Analytics'));

		 $acl->addResource(new Zend_Acl_Resource('default:requisition'));
                            $acl->allow('management', 'default:requisition', array('index','viewhr','approverequisition','addcandidate','interview','getdepartments','getpositions','viewpopup','getapprreqdata','chkreqforclose','getempreportingmanagers','getemailcount','getapprovers','approverejectrequisition','addpopup','add','edit','delete','view','Openings/Positions'));

		 $acl->addResource(new Zend_Acl_Resource('default:resume'));
                            $acl->allow('management', 'default:resume', array('index','language','next','resume','add','edit','delete','view','Resume '));

		 $acl->addResource(new Zend_Acl_Resource('default:roles'));
                            $acl->allow('management', 'default:roles', array('index','saveupdate','getgroupmenu','add','edit','delete','view','Roles & Privileges'));

		 $acl->addResource(new Zend_Acl_Resource('default:scheduleinterviews'));
                            $acl->allow('management', 'default:scheduleinterviews', array('candidatepopup','index','downloadresume','getcandidates','edit','view','Interviews'));

		 $acl->addResource(new Zend_Acl_Resource('default:section'));
                            $acl->allow('management', 'default:section', array('index','addpopup','add','edit','delete','view','Sections'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicedeskconf'));
                            $acl->allow('management', 'default:servicedeskconf', array('index','getemployees','getapprover','getbunitimplementation','getassets','add','edit','delete','view','Settings'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicedeskdepartment'));
                            $acl->allow('management', 'default:servicedeskdepartment', array('index','addpopup','getrequests','add','edit','delete','view','Categories'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicedeskrequest'));
                            $acl->allow('management', 'default:servicedeskrequest', array('index','add','edit','delete','view','Request Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicerequests'));
                            $servicerequests_add = 'yes';
                                if($this->id_param == '' && $servicerequests_add == 'yes')
                                    $acl->allow('management','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','edit'));

                                else
                                    $acl->allow('management','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:shortlistedcandidates'));
                            $acl->allow('management', 'default:shortlistedcandidates', array('index','view','Shortlisted & Selected Candidates'));

		 $acl->addResource(new Zend_Acl_Resource('default:sitepreference'));
                            $acl->allow('management', 'default:sitepreference', array('index','view','add','edit','Site Preferences'));

		 $acl->addResource(new Zend_Acl_Resource('default:states'));
                            $states_add = 'yes';
                                if($this->id_param == '' && $states_add == 'yes')
                                    $acl->allow('management','default:states', array('index','getstates','getstatescand','addpopup','addnewstate','add','delete','view','States','edit'));

                                else
                                    $acl->allow('management','default:states', array('index','getstates','getstatescand','addpopup','addnewstate','add','delete','view','States'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:structure'));
                            $acl->allow('management', 'default:structure', array('index','Organization Structure'));

		 $acl->addResource(new Zend_Acl_Resource('default:team'));
                            $acl->allow('management', 'default:team', array('index','addpopup','add','edit','delete','view','Team'));

		 $acl->addResource(new Zend_Acl_Resource('default:timezone'));
                            $acl->allow('management', 'default:timezone', array('index','saveupdate','addpopup','add','edit','delete','view','Time Zones'));

		 $acl->addResource(new Zend_Acl_Resource('default:title'));
                            $acl->allow('management', 'default:title', array('index','addpopup','add','edit','delete','view','Title'));

		 $acl->addResource(new Zend_Acl_Resource('default:usermanagement'));
                            $acl->allow('management', 'default:usermanagement', array('index','saveupdate','getemailofuser','add','edit','view','External Users'));

		 $acl->addResource(new Zend_Acl_Resource('default:vendors'));
                            $acl->allow('management', 'default:vendors', array('index','addpopup','add','edit','delete','view','Vendors'));

		 $acl->addResource(new Zend_Acl_Resource('default:veteranstatus'));
                            $acl->allow('management', 'default:veteranstatus', array('index','addpopup','add','edit','delete','view','Veteran Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:workeligibilitydoctypes'));
                            $acl->allow('management', 'default:workeligibilitydoctypes', array('index','addpopup','add','edit','delete','view','Work Eligibility Document Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:year'));
                            $acl->allow('management', 'default:year', array('index','addpopup','add','edit','delete','Year'));

		 $acl->addResource(new Zend_Acl_Resource('default:businessdomain'));
                            $acl->allow('management', 'default:businessdomain', array('index','addpopup','add','edit','delete','view','Business Domain'));

		 $acl->addResource(new Zend_Acl_Resource('default:division'));
                            $acl->allow('management', 'default:division', array('index','addpopup','add','edit','delete','view','Divisions'));

		 $acl->addResource(new Zend_Acl_Resource('default:sbuengagment'));
                            $acl->allow('management', 'default:sbuengagment', array('index','add','edit','delete','view','Business Unit Engagment'));

		 $acl->addResource(new Zend_Acl_Resource('default:theme'));
                            $acl->allow('management', 'default:theme', array('index','add','edit','delete','view','Theme'));

		 $acl->addResource(new Zend_Acl_Resource('assets:assetcategories'));
                            $acl->allow('management', 'assets:assetcategories', array('index','view','addpopup','addsubcatpopup','assetuserlog','Asset Categories'));

		 $acl->addResource(new Zend_Acl_Resource('assets:assets'));
                            $acl->allow('management', 'assets:assets', array('index','addpopup','addpopupnested','Asset'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:advances'));
                            $acl->allow('management', 'expenses:advances', array('index','getprojects','myadvances','viewmoreadvances','clearadvancesdata','addreturnpopup','add','edit','delete','view','Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:employeeadvances'));
                            $acl->allow('management', 'expenses:employeeadvances', array('index','add','edit','delete','view','Employee Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:expensecategories'));
                            $acl->allow('management', 'expenses:expensecategories', array('index','Category'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:expenses'));
                            $acl->allow('management', 'expenses:expenses', array('index','clone','addpopup','uploadsave','uploaddelete','displayreceipts','addtrippopup','submitexpense','addreceiptimage','expensestatus','listreportingmangers','viewmoremanagers','forwardexpenseto','downloadexpensepdf','bulkexpenses','getcategories','getprojects','getcurrency','uploadedfiles','add','edit','delete','view','Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:myemployeeexpenses'));
                            $acl->allow('management', 'expenses:myemployeeexpenses', array('index','add','edit','delete','view','My Employee Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:paymentmode'));
                            $acl->allow('management', 'expenses:paymentmode', array('index','Payment Mode'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:receipts'));
                            $acl->allow('management', 'expenses:receipts', array('index','downloadreceipt','downloadexpensereceipt','deletereceipt','uploadsave','displayreceipts','viewmorereceipts','listexpenses','addreceipttoexpense','viewmoreexpenses','cleardata','showreceiptspopup','listtrips','viewmoretrips','addexpensetotrip','add','edit','delete','view','Receipts'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:trips'));
                            $acl->allow('management', 'expenses:trips', array('index','addpopup','tripstatus','deleteexpense','downloadtrippdf','add','edit','delete','view','Trips'));

		 $acl->addResource(new Zend_Acl_Resource('exit:allexitproc'));
                            $acl->allow('management', 'exit:allexitproc', array('index','editpopup','updateexitprocess','assignquestions','add','edit','view','All Exit Procedures'));

		 $acl->addResource(new Zend_Acl_Resource('exit:configureexitqs'));
                            $acl->allow('management', 'exit:configureexitqs', array('index','addpopup','add','edit','delete','view','Exit Interview Questions'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitproc'));
                            $acl->allow('management', 'exit:exitproc', array('index','questions','savequestions','add','edit','view','Initiate/Check Status'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitprocsettings'));
                            $acl->allow('management', 'exit:exitprocsettings', array('index','getdepartments','add','edit','delete','view','Settings'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exittypes'));
                            $acl->allow('management', 'exit:exittypes', array('index','addpopup','add','edit','delete','view','Exit Types'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:attendancereport'));
                            $acl->allow('management', 'schedule:attendancereport', array('index','printreport','edit','delete','view','Attendance Report'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:manageremployeeovertime'));
                            $acl->allow('management', 'schedule:manageremployeeovertime', array('index','editpopup','edit','delete','view','Employee Overtime'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:overtimecategory'));
                            $acl->allow('management', 'schedule:overtimecategory', array('index','add','edit','delete','view','Overtime Category'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:overtime'));
                            $acl->allow('management', 'schedule:overtime', array('index','addnewot','addcategory','checktime','add','edit','delete','view','Overtime'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:timeschedule'));
                            $acl->allow('management', 'schedule:timeschedule', array('index','addpopup','add','edit','delete','view','Schedule'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:timetable'));
                            $acl->allow('management', 'schedule:timetable', array('index','addtime','addpopup','viewall','add','edit','view','Time Table'));

		 $acl->addResource(new Zend_Acl_Resource('payroll:report'));
                            $acl->allow('management', 'payroll:report', array('index','edit','delete','view','payroll report'));
}if($role == 3 )
           {
		 $acl->addResource(new Zend_Acl_Resource('default:announcements'));
                            $acl->allow('manager', 'default:announcements', array('index','getdepts','uploadsave','uploaddelete','view','Announcements'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalcategory'));
                            $acl->allow('manager', 'default:appraisalcategory', array('index','addpopup','getappraisalcategory','view','Parameters'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryself'));
                            $acl->allow('manager', 'default:appraisalhistoryself', array('index','view','My Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryteam'));
                            $acl->allow('manager', 'default:appraisalhistoryteam', array('index','getsearchedempcontent','view','Team Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalmanager'));
                            $acl->allow('manager', 'default:appraisalmanager', array('submitmanager','deletemanagergroup','savemanagergroup','index','viewgroup','createnewgroup','showgroups','showviewgroups','edit','view','Manager Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalquestions'));
                            $acl->allow('manager', 'default:appraisalquestions', array('index','addpopup','savequestionpopup','add','edit','delete','view','Questions'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalself'));
                            $acl->allow('manager', 'default:appraisalself', array('index','save','edit','view','Self Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalskills'));
                            $appraisalskills_add = 'yes';
                                if($this->id_param == '' && $appraisalskills_add == 'yes')
                                    $acl->allow('manager','default:appraisalskills', array('index','getappraisalskills','saveskillspopup','add','view','Skills','edit'));

                                else
                                    $acl->allow('manager','default:appraisalskills', array('index','getappraisalskills','saveskillspopup','add','view','Skills'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:approvedrequisitions'));
                            $acl->allow('manager', 'default:approvedrequisitions', array('index','view','Approved Requisitions'));

		 $acl->addResource(new Zend_Acl_Resource('default:candidatedetails'));
                            $acl->allow('manager', 'default:candidatedetails', array('index','addpopup','chkcandidate','uploadfile','deleteresume','download','multipleresume','getvendors','viewpopup','view','Candidates'));

		 $acl->addResource(new Zend_Acl_Resource('default:clients'));
                            $acl->allow('manager', 'default:clients', array('index','addpopup','add','edit','delete','view','Clients'));

		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                        $acl->allow('manager', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:departments'));
                            $acl->allow('manager', 'default:departments', array('index','viewpopup','addpopup','editpopup','getdepartments','getempnames','view','Departments'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinarymyincidents'));
                            $acl->allow('manager', 'default:disciplinarymyincidents', array('index','saveemployeeappeal','getdisciplinaryincidentpdf','edit','view','My Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryteamincidents'));
                            $acl->allow('manager', 'default:disciplinaryteamincidents', array('index','view','Team Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:employee'));
                            $acl->allow('manager', 'default:employee', array('getemprequi','listview','index','getmoreemployees','changeorghead','getdepartments','getpositions','getempreportingmanagers','makeactiveinactive','changereportingmanager','addemppopup','uploadexcel','getindividualempdetails','add','edit','delete','view','Employees'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardemployee'));
                            $acl->allow('manager', 'default:feedforwardemployee', array('index','save','edit','view','Appraise Your Manager'));

		 $acl->addResource(new Zend_Acl_Resource('default:heirarchy'));
                            $acl->allow('manager', 'default:heirarchy', array('index','addlist','editlist','saveadddata','saveeditdata','deletelist','Organization Hierarchy'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                        $acl->allow('manager', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));

		 $acl->addResource(new Zend_Acl_Resource('default:leaverequest'));
                            $leaverequest_add = 'yes';
                                if($this->id_param == '' && $leaverequest_add == 'yes')
                                    $acl->allow('manager','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request','edit'));

                                else
                                    $acl->allow('manager','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:manageremployeevacations'));
                            $acl->allow('manager', 'default:manageremployeevacations', array('index','edit','view','Employee Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:myholidaycalendar'));
                            $acl->allow('manager', 'default:myholidaycalendar', array('index','view','My Holiday Calendar'));

		 $acl->addResource(new Zend_Acl_Resource('default:myteamappraisal'));
                            $acl->allow('manager', 'default:myteamappraisal', array('savelineresponse','savemngresponse','getempcontent','index','getsearchedempcontent','getsearchedstatus','downloadpdf','downloadUploadedFile','My Team Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:organisationinfo'));
                            $acl->allow('manager', 'default:organisationinfo', array('index','edit_old','saveupdate','uploadpreview','validateorgstartdate','getcompleteorgdata','addorghead','Organization Info'));

		 $acl->addResource(new Zend_Acl_Resource('default:pendingleaves'));
                            $acl->allow('manager', 'default:pendingleaves', array('index','delete','view','My Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:policydocuments'));
                            $acl->allow('manager', 'default:policydocuments', array('index','uploaddoc','deletedocument','addmultiple','uploadmultipledocs','view','View/Manage Policy Documents'));

		 $acl->addResource(new Zend_Acl_Resource('default:projects'));
                            $acl->allow('manager', 'default:projects', array('index','viewpopup','editpopup','add','edit','delete','view','Projects'));

		 $acl->addResource(new Zend_Acl_Resource('default:rejectedrequisitions'));
                            $acl->allow('manager', 'default:rejectedrequisitions', array('index','view','Rejected Requisitions'));

		 $acl->addResource(new Zend_Acl_Resource('default:requisition'));
                            $acl->allow('manager', 'default:requisition', array('index','viewhr','approverequisition','addcandidate','interview','getdepartments','getpositions','viewpopup','getapprreqdata','chkreqforclose','getempreportingmanagers','getemailcount','getapprovers','approverejectrequisition','addpopup','add','edit','delete','view','Openings/Positions'));

		 $acl->addResource(new Zend_Acl_Resource('default:scheduleinterviews'));
                            $acl->allow('manager', 'default:scheduleinterviews', array('candidatepopup','index','downloadresume','getcandidates','edit','view','Interviews'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicerequests'));
                            $servicerequests_add = 'yes';
                                if($this->id_param == '' && $servicerequests_add == 'yes')
                                    $acl->allow('manager','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','edit'));

                                else
                                    $acl->allow('manager','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:shortlistedcandidates'));
                            $acl->allow('manager', 'default:shortlistedcandidates', array('index','view','Shortlisted & Selected Candidates'));

		 $acl->addResource(new Zend_Acl_Resource('default:structure'));
                            $acl->allow('manager', 'default:structure', array('index','Organization Structure'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:advances'));
                            $acl->allow('manager', 'expenses:advances', array('index','getprojects','myadvances','viewmoreadvances','clearadvancesdata','addreturnpopup','add','edit','delete','view','Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:employeeadvances'));
                            $acl->allow('manager', 'expenses:employeeadvances', array('index','add','edit','delete','view','Employee Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:expenses'));
                            $acl->allow('manager', 'expenses:expenses', array('index','clone','addpopup','uploadsave','uploaddelete','displayreceipts','addtrippopup','submitexpense','addreceiptimage','expensestatus','listreportingmangers','viewmoremanagers','forwardexpenseto','downloadexpensepdf','bulkexpenses','getcategories','getprojects','getcurrency','uploadedfiles','add','edit','delete','view','Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:myemployeeexpenses'));
                            $acl->allow('manager', 'expenses:myemployeeexpenses', array('index','add','edit','delete','view','My Employee Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:receipts'));
                            $acl->allow('manager', 'expenses:receipts', array('index','downloadreceipt','downloadexpensereceipt','deletereceipt','uploadsave','displayreceipts','viewmorereceipts','listexpenses','addreceipttoexpense','viewmoreexpenses','cleardata','showreceiptspopup','listtrips','viewmoretrips','addexpensetotrip','add','edit','delete','view','Receipts'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:trips'));
                            $acl->allow('manager', 'expenses:trips', array('index','addpopup','tripstatus','deleteexpense','downloadtrippdf','add','edit','delete','view','Trips'));

		 $acl->addResource(new Zend_Acl_Resource('exit:allexitproc'));
                            $acl->allow('manager', 'exit:allexitproc', array('index','editpopup','updateexitprocess','assignquestions','add','edit','view','All Exit Procedures'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitproc'));
                            $acl->allow('manager', 'exit:exitproc', array('index','questions','savequestions','add','edit','view','Initiate/Check Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:empskills'));
                            $acl->allow('manager', 'default:empskills', array('view','uploadpreview','addpopup','viewpopup','editpopup','edit','view','uploadpreview','addpopup','viewpopup','editpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:empschedule'));
                            $acl->allow('manager', 'default:empschedule', array('view','addpopup','editpopup','viewpopup','edit','view','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:dependencydetails'));
                            $acl->allow('manager', 'default:dependencydetails', array('view','addpopup','editpopup','viewpopup','edit','view','addpopup','editpopup','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:empbankaccount'));
                            $acl->allow('manager', 'default:empbankaccount', array('addpopup','viewpopup','view','addpopup','viewpopup','view','delete'));
}if($role == 4 )
           {
		 $acl->addResource(new Zend_Acl_Resource('default:adddeductive'));
                            $acl->allow('hrmanager', 'default:adddeductive', array('deductive','addcategory','index','add','edit','delete','view','upload','uploadview','Load'));

		 $acl->addResource(new Zend_Acl_Resource('default:addemployeeleaves'));
                            $acl->allow('hrmanager', 'default:addemployeeleaves', array('index','add','edit','view','Add Employee Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:agencylist'));
                            $acl->allow('hrmanager', 'default:agencylist', array('index','deletepoc','add','edit','delete','view','Agencies'));

		 $acl->addResource(new Zend_Acl_Resource('default:announcements'));
                            $acl->allow('hrmanager', 'default:announcements', array('index','getdepts','uploadsave','uploaddelete','add','edit','delete','view','Announcements'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalcategory'));
                            $acl->allow('hrmanager', 'default:appraisalcategory', array('index','addpopup','getappraisalcategory','add','edit','delete','view','Parameters'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryself'));
                            $acl->allow('hrmanager', 'default:appraisalhistoryself', array('index','view','My Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryteam'));
                            $acl->allow('hrmanager', 'default:appraisalhistoryteam', array('index','getsearchedempcontent','view','Team Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalinit'));
                            $acl->allow('hrmanager', 'default:appraisalinit', array('checkappadmin','getdepartmentsadmin','discardsteptwo','displayline','addlinemanager','displayreport','deletelinemanager','deletereportmanager','constructreportacc','constructacc','displayemployees','displaycontentreportacc','displaycontentacc','viewconfmanagers','confmanagers','displaymanagers','displayreportmanagers','getperiod','index','viewassigngroups','assigngroups','displaygroupedemployees','showgroupedemployees','viewgroupedemployees','savegroupedemployeesajax','changesettings','displaysettings','deletegroupedemployees','initializegroup','completeappraisal','checkemployeeresponse','getemployeeslinemanagers','savemngrorghierarchy','getconfiglinemanagers','validateconfig','add','edit','view','Initialize Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalmanager'));
                            $acl->allow('hrmanager', 'default:appraisalmanager', array('submitmanager','deletemanagergroup','savemanagergroup','index','viewgroup','createnewgroup','showgroups','showviewgroups','edit','view','Manager Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalquestions'));
                            $acl->allow('hrmanager', 'default:appraisalquestions', array('index','addpopup','savequestionpopup','add','edit','delete','view','Questions'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalratings'));
                            $acl->allow('hrmanager', 'default:appraisalratings', array('index','addratings','add','edit','view','Ratings'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalself'));
                            $acl->allow('hrmanager', 'default:appraisalself', array('index','save','edit','view','Self Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalskills'));
                            $appraisalskills_add = 'yes';
                                if($this->id_param == '' && $appraisalskills_add == 'yes')
                                    $acl->allow('hrmanager','default:appraisalskills', array('index','getappraisalskills','saveskillspopup','add','view','Skills','edit'));

                                else
                                    $acl->allow('hrmanager','default:appraisalskills', array('index','getappraisalskills','saveskillspopup','add','view','Skills'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:appraisalstatus'));
                            $acl->allow('hrmanager', 'default:appraisalstatus', array('index','manager','managerstatus','checkappraisalimplementation','employee','employeestatus','employeeActi','addlinemanager','displaymanagers','updatelinemanager','view','Employee Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:approvedrequisitions'));
                            $acl->allow('hrmanager', 'default:approvedrequisitions', array('index','edit','view','Approved Requisitions'));

		 $acl->addResource(new Zend_Acl_Resource('default:attendancestatuscode'));
                            $acl->allow('hrmanager', 'default:attendancestatuscode', array('index','add','edit','view','Attendance Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:bankaccounttype'));
                            $acl->allow('hrmanager', 'default:bankaccounttype', array('index','addpopup','add','edit','view','Bank Account Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:bgscreeningtype'));
                            $acl->allow('hrmanager', 'default:bgscreeningtype', array('index','addpopup','add','edit','delete','view','Screening Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:businessunits'));
                            $acl->allow('hrmanager', 'default:businessunits', array('index','addpopup','add','edit','delete','view','Business Units'));

		 $acl->addResource(new Zend_Acl_Resource('default:candidatedetails'));
                            $acl->allow('hrmanager', 'default:candidatedetails', array('index','addpopup','chkcandidate','uploadfile','deleteresume','download','multipleresume','getvendors','viewpopup','add','edit','view','Candidates'));

		 $acl->addResource(new Zend_Acl_Resource('default:categories'));
                            $acl->allow('hrmanager', 'default:categories', array('index','addnewcategory','add','edit','delete','view','Manage Categories'));

		 $acl->addResource(new Zend_Acl_Resource('default:clients'));
                            $acl->allow('hrmanager', 'default:clients', array('index','addpopup','add','edit','delete','view','Clients'));

		 $acl->addResource(new Zend_Acl_Resource('default:competencylevel'));
                            $acl->allow('hrmanager', 'default:competencylevel', array('index','addpopup','add','edit','view','Competency Levels'));

		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                        $acl->allow('hrmanager', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:departments'));
                            $acl->allow('hrmanager', 'default:departments', array('index','viewpopup','addpopup','editpopup','getdepartments','getempnames','add','edit','delete','view','Departments'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryallincidents'));
                            $acl->allow('hrmanager', 'default:disciplinaryallincidents', array('index','view','All Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinarymyincidents'));
                            $acl->allow('hrmanager', 'default:disciplinarymyincidents', array('index','saveemployeeappeal','getdisciplinaryincidentpdf','edit','view','My Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryteamincidents'));
                            $acl->allow('hrmanager', 'default:disciplinaryteamincidents', array('index','view','Team Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:educationlevelcode'));
                            $acl->allow('hrmanager', 'default:educationlevelcode', array('index','add','edit','view','Education Levels'));

		 $acl->addResource(new Zend_Acl_Resource('default:eeoccategory'));
                            $acl->allow('hrmanager', 'default:eeoccategory', array('index','add','edit','view','EEOC Categories'));

		 $acl->addResource(new Zend_Acl_Resource('default:empconfiguration'));
                            $acl->allow('hrmanager', 'default:empconfiguration', array('index','edit','Employee Tabs'));

		 $acl->addResource(new Zend_Acl_Resource('default:empleavesummary'));
                            $acl->allow('hrmanager', 'default:empleavesummary', array('index','statusid','view','Employee Leave Summary'));

		 $acl->addResource(new Zend_Acl_Resource('default:employee'));
                            $acl->allow('hrmanager', 'default:employee', array('getemprequi','listview','index','getmoreemployees','changeorghead','getdepartments','getpositions','getempreportingmanagers','makeactiveinactive','changereportingmanager','addemppopup','uploadexcel','getindividualempdetails','add','edit','view','Employees'));

		 $acl->addResource(new Zend_Acl_Resource('default:employmentstatus'));
                            $acl->allow('hrmanager', 'default:employmentstatus', array('index','addpopup','add','edit','view','Employment Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:empscreening'));
                            $acl->allow('hrmanager', 'default:empscreening', array('index','getemployeedata','getagencylist','getpocdata','forcedfullupdate','checkscreeningstatus','uploadfeedback','download','deletefeedback','add','edit','view','Employee/Candidate Screening'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardemployee'));
                            $acl->allow('hrmanager', 'default:feedforwardemployee', array('index','save','edit','view','Appraise Your Manager'));

		 $acl->addResource(new Zend_Acl_Resource('default:heirarchy'));
                            $acl->allow('hrmanager', 'default:heirarchy', array('index','addlist','editlist','saveadddata','saveeditdata','deletelist','Organization Hierarchy'));

		 $acl->addResource(new Zend_Acl_Resource('default:holidaydates'));
                            $acl->allow('hrmanager', 'default:holidaydates', array('index','addpopup','viewpopup','editpopup','add','edit','view','Manage Holidays'));

		 $acl->addResource(new Zend_Acl_Resource('default:holidaygroups'));
                            $acl->allow('hrmanager', 'default:holidaygroups', array('index','getempnames','getholidaynames','addpopup','add','edit','view','Manage Holiday Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:hrwizard'));
                            $acl->allow('hrmanager', 'default:hrwizard', array('index','configureleavetypes','configureholidays','saveholidaygroup','configureperformanceappraisal','savecategory','updatewizardcompletion','index','configureleavetypes','configureholidays','saveholidaygroup','configureperformanceappraisal','savecategory','updatewizardcompletion'));

		 $acl->addResource(new Zend_Acl_Resource('default:identitydocuments'));
                            $acl->allow('hrmanager', 'default:identitydocuments', array('index','add','edit','view','Identity Documents'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                        $acl->allow('hrmanager', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));

		 $acl->addResource(new Zend_Acl_Resource('default:jobtitles'));
                            $acl->allow('hrmanager', 'default:jobtitles', array('index','addpopup','add','edit','view','Job Titles'));

		 $acl->addResource(new Zend_Acl_Resource('default:leavemanagement'));
                            $acl->allow('hrmanager', 'default:leavemanagement', array('index','add','edit','view','Leave Management Options'));

		 $acl->addResource(new Zend_Acl_Resource('default:leaverequest'));
                            $leaverequest_add = 'yes';
                                if($this->id_param == '' && $leaverequest_add == 'yes')
                                    $acl->allow('hrmanager','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request','edit'));

                                else
                                    $acl->allow('hrmanager','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:manageremployeevacations'));
                            $acl->allow('hrmanager', 'default:manageremployeevacations', array('index','edit','view','Employee Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:myemployees'));
                            $acl->allow('hrmanager', 'default:myemployees', array('index','perview','comview','skillsview','expview','eduview','trainingview','additionaldetailsview','jobhistoryview','skillsedit','jobhistoryedit','expedit','eduedit','trainingedit','additionaldetailsedit','peredit','comedit','docview','docedit','employeereport','getempreportdata','empauto','emprptpdf','exportemployeereport','downloadreport','view','My Team'));

		 $acl->addResource(new Zend_Acl_Resource('default:myholidaycalendar'));
                            $acl->allow('hrmanager', 'default:myholidaycalendar', array('index','view','My Holiday Calendar'));

		 $acl->addResource(new Zend_Acl_Resource('default:myteamappraisal'));
                            $acl->allow('hrmanager', 'default:myteamappraisal', array('savelineresponse','savemngresponse','getempcontent','index','getsearchedempcontent','getsearchedstatus','downloadpdf','downloadUploadedFile','My Team Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:organisationinfo'));
                            $acl->allow('hrmanager', 'default:organisationinfo', array('index','edit_old','saveupdate','uploadpreview','validateorgstartdate','getcompleteorgdata','addorghead','edit','view','Organization Info'));

		 $acl->addResource(new Zend_Acl_Resource('default:payfrequency'));
                            $acl->allow('hrmanager', 'default:payfrequency', array('index','addpopup','add','edit','view','Pay Frequency'));

		 $acl->addResource(new Zend_Acl_Resource('default:pendingleaves'));
                            $acl->allow('hrmanager', 'default:pendingleaves', array('index','delete','view','My Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:policydocuments'));
                            $acl->allow('hrmanager', 'default:policydocuments', array('index','uploaddoc','deletedocument','addmultiple','uploadmultipledocs','add','edit','delete','view','View/Manage Policy Documents'));

		 $acl->addResource(new Zend_Acl_Resource('default:positions'));
                            $acl->allow('hrmanager', 'default:positions', array('index','addpopup','add','edit','view','Positions'));

		 $acl->addResource(new Zend_Acl_Resource('default:projects'));
                            $acl->allow('hrmanager', 'default:projects', array('index','viewpopup','editpopup','add','edit','delete','view','Projects'));

		 $acl->addResource(new Zend_Acl_Resource('default:rejectedrequisitions'));
                            $acl->allow('hrmanager', 'default:rejectedrequisitions', array('index','view','Rejected Requisitions'));

		 $acl->addResource(new Zend_Acl_Resource('default:remunerationbasis'));
                            $acl->allow('hrmanager', 'default:remunerationbasis', array('index','add','edit','view','Remuneration Basis'));

		 $acl->addResource(new Zend_Acl_Resource('default:reports'));
                            $acl->allow('hrmanager', 'default:reports', array('getrolepopup','emprolesgrouppopup','performancereport','previousappraisals','getselectedappraisaldata','getinterviewroundsdata','interviewrounds','rolesgroup','exportemprolesgroup','exportrolesgroupreport','exportinterviewrpt','exportactiveuserrpt','exportemployeereport','rolesgrouprptpdf','activeuserrptpdf','emprptpdf','interviewrptpdf','rolesgroupdata','emprolesgroup','emprolesgroupdata','activeuser','getactiveuserdata','getempreportdata','empauto','servicedeskreport','getsddata','servicedeskpdf','servicedeskexcel','employeereport','getdeptsemp','index','holidaygroupreports','getpdfreportholiday','getexcelreportholiday','leavesreport','getpdfreportleaves','getexcelreportleaves','leavesreporttabheader','leavemanagementreport','getpdfreportleavemanagement','getexcelreportleavemanagement','bunitauto','bunitcodeauto','getexcelreportbusinessunit','getbusinessunitspdf','businessunits','userlogreport','departments','exportdepartmentpdf','getexcelreportdepartment','candidaterptexcel','candidaterptpdf','getcandidatesreportdata','candidatesreport','requisitionauto','requisitionrptexcel','requisitionrptpdf','getrequisitionsstatusreportdata','requisitionstatusreport','activitylogreport','downloadreport','agencylistreport','agencynameauto','agencysebsiteauto','empscreening','getspecimennames','getagencynames','getexcelreportempscreening','getempscreeningpdf','Analytics'));

		 $acl->addResource(new Zend_Acl_Resource('default:requisition'));
                            $acl->allow('hrmanager', 'default:requisition', array('index','viewhr','approverequisition','addcandidate','interview','getdepartments','getpositions','viewpopup','getapprreqdata','chkreqforclose','getempreportingmanagers','getemailcount','getapprovers','approverejectrequisition','addpopup','add','edit','delete','view','Openings/Positions'));

		 $acl->addResource(new Zend_Acl_Resource('default:scheduleinterviews'));
                            $acl->allow('hrmanager', 'default:scheduleinterviews', array('candidatepopup','index','downloadresume','getcandidates','add','edit','view','Interviews'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicedeskconf'));
                            $acl->allow('hrmanager', 'default:servicedeskconf', array('index','getemployees','getapprover','getbunitimplementation','getassets','add','edit','delete','view','Settings'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicedeskdepartment'));
                            $acl->allow('hrmanager', 'default:servicedeskdepartment', array('index','addpopup','getrequests','add','edit','delete','view','Categories'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicedeskrequest'));
                            $acl->allow('hrmanager', 'default:servicedeskrequest', array('index','add','edit','delete','view','Request Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicerequests'));
                            $servicerequests_add = 'yes';
                                if($this->id_param == '' && $servicerequests_add == 'yes')
                                    $acl->allow('hrmanager','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','edit'));

                                else
                                    $acl->allow('hrmanager','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:shortlistedcandidates'));
                            $acl->allow('hrmanager', 'default:shortlistedcandidates', array('index','view','Shortlisted & Selected Candidates'));

		 $acl->addResource(new Zend_Acl_Resource('default:structure'));
                            $acl->allow('hrmanager', 'default:structure', array('index','Organization Structure'));

		 $acl->addResource(new Zend_Acl_Resource('default:usermanagement'));
                            $acl->allow('hrmanager', 'default:usermanagement', array('index','saveupdate','getemailofuser','add','edit','view','External Users'));

		 $acl->addResource(new Zend_Acl_Resource('default:vendors'));
                            $acl->allow('hrmanager', 'default:vendors', array('index','addpopup','add','edit','delete','view','Vendors'));

		 $acl->addResource(new Zend_Acl_Resource('default:workeligibilitydoctypes'));
                            $acl->allow('hrmanager', 'default:workeligibilitydoctypes', array('index','addpopup','add','edit','view','Work Eligibility Document Types'));

		 $acl->addResource(new Zend_Acl_Resource('assets:assetcategories'));
                            $acl->allow('hrmanager', 'assets:assetcategories', array('index','addpopup','addsubcatpopup','assetuserlog','add','edit','delete','view','Asset Categories'));

		 $acl->addResource(new Zend_Acl_Resource('assets:assets'));
                            $acl->allow('hrmanager', 'assets:assets', array('index','addpopup','addpopupnested','add','edit','delete','view','Asset'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:advances'));
                            $acl->allow('hrmanager', 'expenses:advances', array('index','getprojects','myadvances','viewmoreadvances','clearadvancesdata','addreturnpopup','add','edit','delete','view','Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:employeeadvances'));
                            $acl->allow('hrmanager', 'expenses:employeeadvances', array('index','add','edit','delete','view','Employee Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:expensecategories'));
                            $acl->allow('hrmanager', 'expenses:expensecategories', array('index','add','edit','delete','view','Category'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:expenses'));
                            $acl->allow('hrmanager', 'expenses:expenses', array('index','clone','addpopup','uploadsave','uploaddelete','displayreceipts','addtrippopup','submitexpense','addreceiptimage','expensestatus','listreportingmangers','viewmoremanagers','forwardexpenseto','downloadexpensepdf','bulkexpenses','getcategories','getprojects','getcurrency','uploadedfiles','add','edit','delete','view','Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:myemployeeexpenses'));
                            $acl->allow('hrmanager', 'expenses:myemployeeexpenses', array('index','add','edit','delete','view','My Employee Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:paymentmode'));
                            $acl->allow('hrmanager', 'expenses:paymentmode', array('index','add','edit','delete','view','Payment Mode'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:receipts'));
                            $acl->allow('hrmanager', 'expenses:receipts', array('index','downloadreceipt','downloadexpensereceipt','deletereceipt','uploadsave','displayreceipts','viewmorereceipts','listexpenses','addreceipttoexpense','viewmoreexpenses','cleardata','showreceiptspopup','listtrips','viewmoretrips','addexpensetotrip','add','edit','delete','view','Receipts'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:trips'));
                            $acl->allow('hrmanager', 'expenses:trips', array('index','addpopup','tripstatus','deleteexpense','downloadtrippdf','add','edit','delete','view','Trips'));

		 $acl->addResource(new Zend_Acl_Resource('exit:allexitproc'));
                            $acl->allow('hrmanager', 'exit:allexitproc', array('index','editpopup','updateexitprocess','assignquestions','add','edit','view','All Exit Procedures'));

		 $acl->addResource(new Zend_Acl_Resource('exit:configureexitqs'));
                            $acl->allow('hrmanager', 'exit:configureexitqs', array('index','addpopup','add','edit','delete','view','Exit Interview Questions'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitproc'));
                            $acl->allow('hrmanager', 'exit:exitproc', array('index','questions','savequestions','add','edit','view','Initiate/Check Status'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitprocsettings'));
                            $acl->allow('hrmanager', 'exit:exitprocsettings', array('index','getdepartments','add','edit','delete','view','Settings'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exittypes'));
                            $acl->allow('hrmanager', 'exit:exittypes', array('index','addpopup','add','edit','delete','view','Exit Types'));
}if($role == 5 )
           {
		 $acl->addResource(new Zend_Acl_Resource('default:announcements'));
                            $acl->allow('employee', 'default:announcements', array('index','getdepts','uploadsave','uploaddelete','view','Announcements'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryself'));
                            $acl->allow('employee', 'default:appraisalhistoryself', array('index','view','My Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryteam'));
                            $acl->allow('employee', 'default:appraisalhistoryteam', array('index','getsearchedempcontent','view','Team Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalmanager'));
                            $acl->allow('employee', 'default:appraisalmanager', array('submitmanager','deletemanagergroup','savemanagergroup','index','viewgroup','createnewgroup','showgroups','showviewgroups','edit','view','Manager Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalself'));
                            $acl->allow('employee', 'default:appraisalself', array('index','save','edit','view','Self Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:clients'));
                            $acl->allow('employee', 'default:clients', array('index','addpopup','add','edit','delete','view','Clients'));

		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                        $acl->allow('employee', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:departments'));
                            $acl->allow('employee', 'default:departments', array('index','viewpopup','addpopup','editpopup','getdepartments','getempnames','view','Departments'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinarymyincidents'));
                            $acl->allow('employee', 'default:disciplinarymyincidents', array('index','saveemployeeappeal','getdisciplinaryincidentpdf','edit','view','My Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryteamincidents'));
                            $acl->allow('employee', 'default:disciplinaryteamincidents', array('index','view','Team Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:employee'));
                            $acl->allow('employee', 'default:employee', array('getemprequi','listview','index','getmoreemployees','changeorghead','getdepartments','getpositions','getempreportingmanagers','makeactiveinactive','changereportingmanager','addemppopup','uploadexcel','getindividualempdetails','add','edit','delete','view','Employees'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardemployee'));
                            $acl->allow('employee', 'default:feedforwardemployee', array('index','save','edit','view','Appraise Your Manager'));

		 $acl->addResource(new Zend_Acl_Resource('default:heirarchy'));
                            $acl->allow('employee', 'default:heirarchy', array('index','addlist','editlist','saveadddata','saveeditdata','deletelist','Organization Hierarchy'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                        $acl->allow('employee', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));

		 $acl->addResource(new Zend_Acl_Resource('default:leaverequest'));
                            $leaverequest_add = 'yes';
                                if($this->id_param == '' && $leaverequest_add == 'yes')
                                    $acl->allow('employee','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request','edit'));

                                else
                                    $acl->allow('employee','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:manageremployeevacations'));
                            $acl->allow('employee', 'default:manageremployeevacations', array('index','edit','view','Employee Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:myemployees'));
                            $acl->allow('employee', 'default:myemployees', array('index','perview','comview','skillsview','expview','eduview','trainingview','additionaldetailsview','jobhistoryview','skillsedit','jobhistoryedit','expedit','eduedit','trainingedit','additionaldetailsedit','peredit','comedit','docview','docedit','employeereport','getempreportdata','empauto','emprptpdf','exportemployeereport','downloadreport','view','My Team'));

		 $acl->addResource(new Zend_Acl_Resource('default:myholidaycalendar'));
                            $acl->allow('employee', 'default:myholidaycalendar', array('index','view','My Holiday Calendar'));

		 $acl->addResource(new Zend_Acl_Resource('default:myteamappraisal'));
                            $acl->allow('employee', 'default:myteamappraisal', array('savelineresponse','savemngresponse','getempcontent','index','getsearchedempcontent','getsearchedstatus','downloadpdf','downloadUploadedFile','My Team Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:organisationinfo'));
                            $acl->allow('employee', 'default:organisationinfo', array('index','edit_old','saveupdate','uploadpreview','validateorgstartdate','getcompleteorgdata','addorghead','Organization Info'));

		 $acl->addResource(new Zend_Acl_Resource('default:pendingleaves'));
                            $acl->allow('employee', 'default:pendingleaves', array('index','delete','view','My Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:policydocuments'));
                            $acl->allow('employee', 'default:policydocuments', array('index','uploaddoc','deletedocument','addmultiple','uploadmultipledocs','view','View/Manage Policy Documents'));

		 $acl->addResource(new Zend_Acl_Resource('default:projects'));
                            $acl->allow('employee', 'default:projects', array('index','viewpopup','editpopup','add','edit','delete','view','Projects'));

		 $acl->addResource(new Zend_Acl_Resource('default:scheduleinterviews'));
                            $acl->allow('employee', 'default:scheduleinterviews', array('candidatepopup','index','downloadresume','getcandidates','edit','view','Interviews'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicerequests'));
                            $servicerequests_add = 'yes';
                                if($this->id_param == '' && $servicerequests_add == 'yes')
                                    $acl->allow('employee','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','edit'));

                                else
                                    $acl->allow('employee','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:structure'));
                            $acl->allow('employee', 'default:structure', array('index','Organization Structure'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:advances'));
                            $acl->allow('employee', 'expenses:advances', array('index','getprojects','myadvances','viewmoreadvances','clearadvancesdata','addreturnpopup','add','edit','delete','view','Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:employeeadvances'));
                            $acl->allow('employee', 'expenses:employeeadvances', array('index','add','edit','delete','view','Employee Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:expenses'));
                            $acl->allow('employee', 'expenses:expenses', array('index','clone','addpopup','uploadsave','uploaddelete','displayreceipts','addtrippopup','submitexpense','addreceiptimage','expensestatus','listreportingmangers','viewmoremanagers','forwardexpenseto','downloadexpensepdf','bulkexpenses','getcategories','getprojects','getcurrency','uploadedfiles','add','edit','delete','view','Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:myemployeeexpenses'));
                            $acl->allow('employee', 'expenses:myemployeeexpenses', array('index','add','edit','delete','view','My Employee Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:receipts'));
                            $acl->allow('employee', 'expenses:receipts', array('index','downloadreceipt','downloadexpensereceipt','deletereceipt','uploadsave','displayreceipts','viewmorereceipts','listexpenses','addreceipttoexpense','viewmoreexpenses','cleardata','showreceiptspopup','listtrips','viewmoretrips','addexpensetotrip','add','edit','delete','view','Receipts'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:trips'));
                            $acl->allow('employee', 'expenses:trips', array('index','addpopup','tripstatus','deleteexpense','downloadtrippdf','add','edit','delete','view','Trips'));

		 $acl->addResource(new Zend_Acl_Resource('exit:allexitproc'));
                            $acl->allow('employee', 'exit:allexitproc', array('index','editpopup','updateexitprocess','assignquestions','add','edit','view','All Exit Procedures'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitproc'));
                            $acl->allow('employee', 'exit:exitproc', array('index','questions','savequestions','add','edit','view','Initiate/Check Status'));
}if($role == 6 )
           {
		 $acl->addResource(new Zend_Acl_Resource('default:adddeductive'));
                            $acl->allow('user', 'default:adddeductive', array('deductive','addcategory','index','add','edit','delete','view','Load'));

		 $acl->addResource(new Zend_Acl_Resource('default:announcements'));
                            $acl->allow('user', 'default:announcements', array('index','getdepts','uploadsave','uploaddelete','view','Announcements'));

		 $acl->addResource(new Zend_Acl_Resource('default:businessunits'));
                            $businessunits_add = 'yes';
                                if($this->id_param == '' && $businessunits_add == 'yes')
                                    $acl->allow('user','default:businessunits', array('index','view','addpopup','add','Business Units','edit'));

                                else
                                    $acl->allow('user','default:businessunits', array('index','view','addpopup','add','Business Units'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:clients'));
                            $acl->allow('user', 'default:clients', array('index','addpopup','add','edit','delete','view','Clients'));

		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                        $acl->allow('user', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:departments'));
                            $acl->allow('user', 'default:departments', array('index','viewpopup','addpopup','editpopup','getdepartments','getempnames','view','Departments'));

		 $acl->addResource(new Zend_Acl_Resource('default:empscreening'));
                            $acl->allow('user', 'default:empscreening', array('index','getemployeedata','getagencylist','getpocdata','forcedfullupdate','checkscreeningstatus','uploadfeedback','download','deletefeedback','add','edit','delete','view','Employee/Candidate Screening'));

		 $acl->addResource(new Zend_Acl_Resource('default:heirarchy'));
                            $acl->allow('user', 'default:heirarchy', array('index','addlist','editlist','saveadddata','saveeditdata','deletelist','Organization Hierarchy'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                        $acl->allow('user', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));

		 $acl->addResource(new Zend_Acl_Resource('default:organisationinfo'));
                            $acl->allow('user', 'default:organisationinfo', array('index','edit_old','saveupdate','uploadpreview','validateorgstartdate','getcompleteorgdata','addorghead','Organization Info'));

		 $acl->addResource(new Zend_Acl_Resource('default:projects'));
                            $acl->allow('user', 'default:projects', array('index','viewpopup','editpopup','add','edit','delete','view','Projects'));

		 $acl->addResource(new Zend_Acl_Resource('default:structure'));
                            $acl->allow('user', 'default:structure', array('index','Organization Structure'));

		 $acl->addResource(new Zend_Acl_Resource('exit:allexitproc'));
                            $acl->allow('user', 'exit:allexitproc', array('index','editpopup','updateexitprocess','assignquestions','add','edit','view','All Exit Procedures'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitproc'));
                            $acl->allow('user', 'exit:exitproc', array('index','questions','savequestions','add','edit','view','Initiate/Check Status'));
}if($role == 7 )
           {
		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                        $acl->allow('agencyuser', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:empscreening'));
                            $acl->allow('agencyuser', 'default:empscreening', array('index','getemployeedata','getagencylist','getpocdata','forcedfullupdate','checkscreeningstatus','uploadfeedback','download','deletefeedback','edit','view','Employee/Candidate Screening'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                        $acl->allow('agencyuser', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));
}if($role == 8 )
           {
		 $acl->addResource(new Zend_Acl_Resource('default:adddeductive'));
                            $acl->allow('sysadmin', 'default:adddeductive', array('deductive','addcategory','index','add','edit','delete','view','Load'));

		 $acl->addResource(new Zend_Acl_Resource('default:addperk'));
                            $acl->allow('sysadmin', 'default:addperk', array('perk','addcategory','index','add','edit','delete','view','Perk'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryself'));
                            $acl->allow('sysadmin', 'default:appraisalhistoryself', array('index','view','My Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryteam'));
                            $acl->allow('sysadmin', 'default:appraisalhistoryteam', array('index','getsearchedempcontent','view','Team Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalmanager'));
                            $acl->allow('sysadmin', 'default:appraisalmanager', array('submitmanager','deletemanagergroup','savemanagergroup','index','viewgroup','createnewgroup','showgroups','showviewgroups','edit','view','Manager Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalself'));
                            $acl->allow('sysadmin', 'default:appraisalself', array('index','save','edit','view','Self Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:calendar'));
                            $acl->allow('sysadmin', 'default:calendar', array('index','deletecalendar','addpopup','add','edit','delete','view','calendar'));

		 $acl->addResource(new Zend_Acl_Resource('default:cardprovider'));
                            $acl->allow('sysadmin', 'default:cardprovider', array('index','addpopup','add','edit','Card Provider'));

		 $acl->addResource(new Zend_Acl_Resource('default:certificate'));
                            $acl->allow('sysadmin', 'default:certificate', array('index','addpopup','add','edit','Certificate'));

		 $acl->addResource(new Zend_Acl_Resource('default:certificatecategory'));
                            $acl->allow('sysadmin', 'default:certificatecategory', array('index','addpopup','add','edit','Certificate  Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:certprovider'));
                            $acl->allow('sysadmin', 'default:certprovider', array('index','addpopup','add','edit','Certificate Provider '));

		 $acl->addResource(new Zend_Acl_Resource('default:certprovidercategory'));
                            $acl->allow('sysadmin', 'default:certprovidercategory', array('index','addpopup','add','edit','Certificate Provider Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:clients'));
                            $acl->allow('sysadmin', 'default:clients', array('index','addpopup','add','edit','delete','view','Clients'));

		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                        $acl->allow('sysadmin', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:disability'));
                            $acl->allow('sysadmin', 'default:disability', array('index','addpopup','addpopupnested','add','edit','Disability'));

		 $acl->addResource(new Zend_Acl_Resource('default:disabilitycategory'));
                            $acl->allow('sysadmin', 'default:disabilitycategory', array('index','addpopup','add','edit','Disability Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:disabilitytype'));
                            $acl->allow('sysadmin', 'default:disabilitytype', array('index','addpopup','add','edit','Disability Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:documentcategory'));
                            $acl->allow('sysadmin', 'default:documentcategory', array('index','addpopup','add','edit','Document Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:documentprovider'));
                            $acl->allow('sysadmin', 'default:documentprovider', array('index','addpopup','add','edit','Document Provider'));

		 $acl->addResource(new Zend_Acl_Resource('default:documentpurpose'));
                            $acl->allow('sysadmin', 'default:documentpurpose', array('index','addpopup','add','edit','Document  Purpose'));

		 $acl->addResource(new Zend_Acl_Resource('default:documentstatuslist'));
                            $acl->allow('sysadmin', 'default:documentstatuslist', array('index','addpopup','add','edit','Document Status List'));

		 $acl->addResource(new Zend_Acl_Resource('default:documenttype'));
                            $acl->allow('sysadmin', 'default:documenttype', array('index','addpopup','add','edit','Document Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:employee'));
                            $acl->allow('sysadmin', 'default:employee', array('getemprequi','listview','index','getmoreemployees','changeorghead','getdepartments','getpositions','getempreportingmanagers','makeactiveinactive','changereportingmanager','addemppopup','uploadexcel','getindividualempdetails','view','Employees'));

		 $acl->addResource(new Zend_Acl_Resource('default:emprecordaccessdetail'));
                            $acl->allow('sysadmin', 'default:emprecordaccessdetail', array('index','add','edit','Access detail'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardemployee'));
                            $acl->allow('sysadmin', 'default:feedforwardemployee', array('index','save','edit','view','Appraise Your Manager'));

		 $acl->addResource(new Zend_Acl_Resource('default:formview'));
                            $acl->allow('sysadmin', 'default:formview', array('index','addpopup','update','getcolumns','getformelement','getparticulardataforall','add','edit','delete','view','Form option'));

		 $acl->addResource(new Zend_Acl_Resource('default:holidaygroups'));
                            $acl->allow('sysadmin', 'default:holidaygroups', array('index','getempnames','getholidaynames','addpopup','add','edit','delete','view','Manage Holiday Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                        $acl->allow('sysadmin', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));

		 $acl->addResource(new Zend_Acl_Resource('default:leave'));
                            $acl->allow('sysadmin', 'default:leave', array('index','add','edit','delete','view','Leave '));

		 $acl->addResource(new Zend_Acl_Resource('default:leavecategory'));
                            $acl->allow('sysadmin', 'default:leavecategory', array('index','addpopup','add','edit','delete','view','Leave Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:leaverequest'));
                            $leaverequest_add = 'yes';
                                if($this->id_param == '' && $leaverequest_add == 'yes')
                                    $acl->allow('sysadmin','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request','edit'));

                                else
                                    $acl->allow('sysadmin','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:loadapprove'));
                            $acl->allow('sysadmin', 'default:loadapprove', array('index','editpopup','edit','delete','view','Employee Load'));

		 $acl->addResource(new Zend_Acl_Resource('default:location'));
                            $acl->allow('sysadmin', 'default:location', array('index','map','addpopup','addpopupnested','add','edit','delete','view','Location'));

		 $acl->addResource(new Zend_Acl_Resource('default:manageremployeevacations'));
                            $acl->allow('sysadmin', 'default:manageremployeevacations', array('index','edit','view','Employee Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:measurement'));
                            $acl->allow('sysadmin', 'default:measurement', array('index','addpopup','view','add','edit','Measurement'));

		 $acl->addResource(new Zend_Acl_Resource('default:measurementindex'));
                            $acl->allow('sysadmin', 'default:measurementindex', array('index','addpopup','view','add','edit','Measurement Index'));

		 $acl->addResource(new Zend_Acl_Resource('default:militaryservice'));
                            $acl->allow('sysadmin', 'default:militaryservice', array('index','add','edit','delete','view','Military Service Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:missedhoursapprove'));
                            $acl->allow('sysadmin', 'default:missedhoursapprove', array('index','editpopup','edit','delete','view','Employee Missed Hours'));

		 $acl->addResource(new Zend_Acl_Resource('default:myemployees'));
                            $acl->allow('sysadmin', 'default:myemployees', array('index','perview','comview','skillsview','expview','eduview','trainingview','additionaldetailsview','jobhistoryview','skillsedit','jobhistoryedit','expedit','eduedit','trainingedit','additionaldetailsedit','peredit','comedit','docview','docedit','employeereport','getempreportdata','empauto','emprptpdf','exportemployeereport','downloadreport','view','My Team'));

		 $acl->addResource(new Zend_Acl_Resource('default:myholidaycalendar'));
                            $acl->allow('sysadmin', 'default:myholidaycalendar', array('index','view','My Holiday Calendar'));

		 $acl->addResource(new Zend_Acl_Resource('default:myteamappraisal'));
                            $acl->allow('sysadmin', 'default:myteamappraisal', array('savelineresponse','savemngresponse','getempcontent','index','getsearchedempcontent','getsearchedstatus','downloadpdf','downloadUploadedFile','My Team Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:pagetab'));
                            $acl->allow('sysadmin', 'default:pagetab', array('index','add','edit','Page Tab'));

		 $acl->addResource(new Zend_Acl_Resource('default:paymentcard'));
                            $acl->allow('sysadmin', 'default:paymentcard', array('index','addpopup','add','edit','Payment Card'));

		 $acl->addResource(new Zend_Acl_Resource('default:paymentcardtype'));
                            $acl->allow('sysadmin', 'default:paymentcardtype', array('index','addpopup','add','edit','Payment Card Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:payroll'));
                            $acl->allow('sysadmin', 'default:payroll', array('index','calculate','editpopup','add','edit','delete','view','Payroll'));

		 $acl->addResource(new Zend_Acl_Resource('default:pendingleaves'));
                            $acl->allow('sysadmin', 'default:pendingleaves', array('index','delete','view','My Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:period'));
                            $acl->allow('sysadmin', 'default:period', array('index','addpopup','add','edit','delete','view','period'));

		 $acl->addResource(new Zend_Acl_Resource('default:perkapprove'));
                            $acl->allow('sysadmin', 'default:perkapprove', array('index','editpopup','edit','delete','view','Employee Perk'));

		 $acl->addResource(new Zend_Acl_Resource('default:projects'));
                            $acl->allow('sysadmin', 'default:projects', array('index','viewpopup','editpopup','add','edit','delete','view','Projects'));

		 $acl->addResource(new Zend_Acl_Resource('default:resume'));
                            $acl->allow('sysadmin', 'default:resume', array('index','language','next','resume','add','edit','delete','view','Resume '));

		 $acl->addResource(new Zend_Acl_Resource('default:roles'));
                            $acl->allow('sysadmin', 'default:roles', array('index','saveupdate','getgroupmenu','add','edit','delete','view','Roles & Privileges'));

		 $acl->addResource(new Zend_Acl_Resource('default:scheduleinterviews'));
                            $acl->allow('sysadmin', 'default:scheduleinterviews', array('candidatepopup','index','downloadresume','getcandidates','edit','view','Interviews'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicerequests'));
                            $servicerequests_add = 'yes';
                                if($this->id_param == '' && $servicerequests_add == 'yes')
                                    $acl->allow('sysadmin','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','edit'));

                                else
                                    $acl->allow('sysadmin','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:skill'));
                            $acl->allow('sysadmin', 'default:skill', array('index','addpopup','add','edit','view','Skill'));

		 $acl->addResource(new Zend_Acl_Resource('default:skillcategory'));
                            $acl->allow('sysadmin', 'default:skillcategory', array('index','addpopup','view','add','edit','Skill Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:skillsource'));
                            $acl->allow('sysadmin', 'default:skillsource', array('index','addpopup','view','add','edit','Skill Source'));

		 $acl->addResource(new Zend_Acl_Resource('default:tabgroups'));
                            $acl->allow('sysadmin', 'default:tabgroups', array('index','saveupdate','getgroupmenugroup','edit','view','Groups Tab Access'));

		 $acl->addResource(new Zend_Acl_Resource('default:tabroles'));
                            $acl->allow('sysadmin', 'default:tabroles', array('index','saveupdate','getgroupmenu','edit','delete','view','Role Tab Access'));

		 $acl->addResource(new Zend_Acl_Resource('default:user'));
                            $acl->allow('sysadmin', 'default:user', array('index','add','edit','delete','view','User'));

		 $acl->addResource(new Zend_Acl_Resource('default:usermanagement'));
                            $acl->allow('sysadmin', 'default:usermanagement', array('index','saveupdate','getemailofuser','add','edit','view','External Users'));

		 $acl->addResource(new Zend_Acl_Resource('default:vendors'));
                            $acl->allow('sysadmin', 'default:vendors', array('index','addpopup','add','edit','delete','view','Vendors'));

		 $acl->addResource(new Zend_Acl_Resource('default:veteranstatus'));
                            $acl->allow('sysadmin', 'default:veteranstatus', array('index','addpopup','add','edit','delete','view','Veteran Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:year'));
                            $acl->allow('sysadmin', 'default:year', array('index','addpopup','add','edit','delete','view','Year'));

		 $acl->addResource(new Zend_Acl_Resource('default:entrymode'));
                            $acl->allow('sysadmin', 'default:entrymode', array('index','addpopup','add','edit','delete','view','Entry Mode'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthissue'));
                            $acl->allow('sysadmin', 'default:healthissue', array('index','addpopup','add','edit','view','Health Issue'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthissuecategory'));
                            $acl->allow('sysadmin', 'default:healthissuecategory', array('index','addpopup','view','add','edit','Health Issue Category'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthissuetype'));
                            $acl->allow('sysadmin', 'default:healthissuetype', array('index','addpopup','view','add','edit','Health Issue Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthseverity'));
                            $acl->allow('sysadmin', 'default:healthseverity', array('index','addpopup','view','add','edit','Health Severity'));

		 $acl->addResource(new Zend_Acl_Resource('default:healthseveritylevel'));
                            $acl->allow('sysadmin', 'default:healthseveritylevel', array('index','addpopup','add','edit','view','Health Severity Level'));

		 $acl->addResource(new Zend_Acl_Resource('default:iddocument'));
                            $acl->allow('sysadmin', 'default:iddocument', array('index','addpopup','add','edit','view','Document'));

		 $acl->addResource(new Zend_Acl_Resource('default:iddocumentstatus'));
                            $acl->allow('sysadmin', 'default:iddocumentstatus', array('index','addpopup','view','add','edit','Document Status'));

		 $acl->addResource(new Zend_Acl_Resource('default:institution'));
                            $acl->allow('sysadmin', 'default:institution', array('index','view','add','edit','institution'));

		 $acl->addResource(new Zend_Acl_Resource('default:militarydischargetype'));
                            $acl->allow('sysadmin', 'default:militarydischargetype', array('index','addpopup','view','add','edit','Military Discharge Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:militaryrank'));
                            $acl->allow('sysadmin', 'default:militaryrank', array('index','addpopup','view','add','edit','Military Rank'));

		 $acl->addResource(new Zend_Acl_Resource('default:militaryservicebranch'));
                            $acl->allow('sysadmin', 'default:militaryservicebranch', array('index','addpopup','view','add','edit','Military Service Branch'));

		 $acl->addResource(new Zend_Acl_Resource('default:militaryservicetype'));
                            $acl->allow('sysadmin', 'default:militaryservicetype', array('index','addpopup','view','add','edit','Military Service Type'));

		 $acl->addResource(new Zend_Acl_Resource('default:recordaccess'));
                            $acl->allow('sysadmin', 'default:recordaccess', array('index','add','edit','view','Record Access'));

		 $acl->addResource(new Zend_Acl_Resource('default:relationship'));
                            $acl->allow('sysadmin', 'default:relationship', array('index','addpopup','view','add','edit','Relationship'));

		 $acl->addResource(new Zend_Acl_Resource('default:studyfield'));
                            $acl->allow('sysadmin', 'default:studyfield', array('index','view','addpopup','add','edit','Study Field'));

		 $acl->addResource(new Zend_Acl_Resource('default:studylevel'));
                            $acl->allow('sysadmin', 'default:studylevel', array('index','view','addpopup','add','edit','Study Level'));

		 $acl->addResource(new Zend_Acl_Resource('default:theme'));
                            $acl->allow('sysadmin', 'default:theme', array('index','add','edit','delete','view','Theme'));

		 $acl->addResource(new Zend_Acl_Resource('exit:allexitproc'));
                            $acl->allow('sysadmin', 'exit:allexitproc', array('index','editpopup','updateexitprocess','assignquestions','add','edit','view','All Exit Procedures'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitproc'));
                            $acl->allow('sysadmin', 'exit:exitproc', array('index','questions','savequestions','add','edit','view','Initiate/Check Status'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:attendancereport'));
                            $acl->allow('sysadmin', 'schedule:attendancereport', array('index','printreport','edit','delete','view','Attendance Report'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:manageremployeeovertime'));
                            $acl->allow('sysadmin', 'schedule:manageremployeeovertime', array('index','editpopup','edit','delete','view','Employee Overtime'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:overtimecategory'));
                            $acl->allow('sysadmin', 'schedule:overtimecategory', array('index','add','edit','delete','view','Overtime Category'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:overtime'));
                            $acl->allow('sysadmin', 'schedule:overtime', array('index','addnewot','addcategory','checktime','add','edit','delete','view','Overtime'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:timeschedule'));
                            $acl->allow('sysadmin', 'schedule:timeschedule', array('index','addpopup','add','edit','delete','view','Schedule'));

		 $acl->addResource(new Zend_Acl_Resource('schedule:timetable'));
                            $acl->allow('sysadmin', 'schedule:timetable', array('index','addtime','addpopup','viewall','add','edit','view','Time Table'));

		 $acl->addResource(new Zend_Acl_Resource('payroll:report'));
                            $acl->allow('sysadmin', 'payroll:report', array('index','add','edit','delete','view','payroll report'));

		 $acl->addResource(new Zend_Acl_Resource('default:empskills'));
                            $acl->allow('sysadmin', 'default:empskills', array('view','uploadpreview','addpopup','viewpopup','editpopup','edit','view','uploadpreview','addpopup','viewpopup','editpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:empiddocument'));
                            $acl->allow('sysadmin', 'default:empiddocument', array('uploadpreview','editpopup','viewpopup','view','add','edit','uploadpreview','editpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empleaves'));
                            $acl->allow('sysadmin', 'default:empleaves', array('addpopup','editpopup','view','viewpopup','addpopup','editpopup','edit','view','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:empholidays'));
                            $acl->allow('sysadmin', 'default:empholidays', array('addpopup','editpopup','view','viewpopup','edit','addpopup','editpopup','view','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empaddress'));
                            $acl->allow('sysadmin', 'default:empaddress', array('addpopup','editpopup','view','viewpopup','edit','addpopup','editpopup','view','delete','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:emphealthissue'));
                            $acl->allow('sysadmin', 'default:emphealthissue', array('addpopup','uploadpreview','viewpopup','view','editpopup','addpopup','uploadpreview','viewpopup','edit','view','editpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empsalarydetails'));
                            $acl->allow('sysadmin', 'default:empsalarydetails', array('addpopup','editpopup','view','viewpopup','edit','addpopup','editpopup','view','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empbenefits'));
                            $acl->allow('sysadmin', 'default:empbenefits', array('view','editempperk','addpopup','editpopup','viewpopup','edit','view','editempperk','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empdeductive'));
                            $acl->allow('sysadmin', 'default:empdeductive', array('view','editpopup','editempdeductive','addpopup','viewpopup','edit','view','editpopup','editempdeductive','addpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:emppos'));
                            $acl->allow('sysadmin', 'default:emppos', array('addpopup','editpopup','viewpopup','view','edit','addpopup','editpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empschedule'));
                            $acl->allow('sysadmin', 'default:empschedule', array('view','addpopup','editpopup','viewpopup','edit','view','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empphoneno'));
                            $acl->allow('sysadmin', 'default:empphoneno', array('view','addpopup','editpopup','viewpopup','edit','view','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:emppresence'));
                            $acl->allow('sysadmin', 'default:emppresence', array('addpopup','editpopup','viewpopup','view','edit','addpopup','editpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:empuser'));
                            $acl->allow('sysadmin', 'default:empuser', array('addpopup','viewpopup','view','edit','addpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:emprole'));
                            $acl->allow('sysadmin', 'default:emprole', array('addpopup','editpopup','viewpopup','view','edit','addpopup','editpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:emprecordaccess'));
                            $acl->allow('sysadmin', 'default:emprecordaccess', array('addpopup','editpopup','viewpopup','view','edit','addpopup','editpopup','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:experiencedetails'));
                            $acl->allow('sysadmin', 'default:experiencedetails', array('view','addpopup','editpopup','viewpopup','edit','view','addpopup','editpopup','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:educationdetails'));
                            $acl->allow('sysadmin', 'default:educationdetails', array('view','addpopup','viewpopup','editpopup','uploadpreview','edit','view','addpopup','viewpopup','editpopup','uploadpreview','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:trainingandcertificationdetails'));
                            $acl->allow('sysadmin', 'default:trainingandcertificationdetails', array('view','uploadpreview','addpopup','editpopup','viewpopup','edit','view','uploadpreview','addpopup','editpopup','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:disabilitydetails'));
                            $acl->allow('sysadmin', 'default:disabilitydetails', array('addpopup','editpopup','viewpopup','view','addpopup','editpopup','edit','viewpopup','view'));

		 $acl->addResource(new Zend_Acl_Resource('default:dependencydetails'));
                            $acl->allow('sysadmin', 'default:dependencydetails', array('view','addpopup','editpopup','viewpopup','edit','view','addpopup','editpopup','viewpopup','delete'));

		 $acl->addResource(new Zend_Acl_Resource('default:creditcarddetails'));
                            $acl->allow('sysadmin', 'default:creditcarddetails', array('view','addpopup','editpopup','viewpopup','add','edit','view','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:assetdetails'));
                            $acl->allow('sysadmin', 'default:assetdetails', array('view','addpopup','editpopup','viewpopup','edit','view','addpopup','editpopup','viewpopup'));

		 $acl->addResource(new Zend_Acl_Resource('default:empadditionaldetails'));
                            $acl->allow('sysadmin', 'default:empadditionaldetails', array('view','addpopup','uploadpreview','viewpopup','editpopup','edit','view','addpopup','uploadpreview','viewpopup','editpopup','delete'));
}if($role == 9 )
           {
		 $acl->addResource(new Zend_Acl_Resource('default:announcements'));
                            $acl->allow('teamlead', 'default:announcements', array('index','getdepts','uploadsave','uploaddelete','view','Announcements'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryself'));
                            $acl->allow('teamlead', 'default:appraisalhistoryself', array('index','view','My Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalhistoryteam'));
                            $acl->allow('teamlead', 'default:appraisalhistoryteam', array('index','getsearchedempcontent','view','Team Appraisal History'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalmanager'));
                            $acl->allow('teamlead', 'default:appraisalmanager', array('submitmanager','deletemanagergroup','savemanagergroup','index','viewgroup','createnewgroup','showgroups','showviewgroups','edit','view','Manager Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:appraisalself'));
                            $acl->allow('teamlead', 'default:appraisalself', array('index','save','edit','view','Self Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:clients'));
                            $acl->allow('teamlead', 'default:clients', array('index','addpopup','add','edit','delete','view','Clients'));

		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                        $acl->allow('teamlead', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:departments'));
                            $acl->allow('teamlead', 'default:departments', array('index','viewpopup','addpopup','editpopup','getdepartments','getempnames','view','Departments'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinarymyincidents'));
                            $acl->allow('teamlead', 'default:disciplinarymyincidents', array('index','saveemployeeappeal','getdisciplinaryincidentpdf','edit','view','My Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:disciplinaryteamincidents'));
                            $acl->allow('teamlead', 'default:disciplinaryteamincidents', array('index','view','Team Incidents'));

		 $acl->addResource(new Zend_Acl_Resource('default:employee'));
                            $acl->allow('teamlead', 'default:employee', array('getemprequi','listview','index','getmoreemployees','changeorghead','getdepartments','getpositions','getempreportingmanagers','makeactiveinactive','changereportingmanager','addemppopup','uploadexcel','getindividualempdetails','view','Employees'));

		 $acl->addResource(new Zend_Acl_Resource('default:feedforwardemployee'));
                            $acl->allow('teamlead', 'default:feedforwardemployee', array('index','save','edit','view','Appraise Your Manager'));

		 $acl->addResource(new Zend_Acl_Resource('default:heirarchy'));
                            $acl->allow('teamlead', 'default:heirarchy', array('index','addlist','editlist','saveadddata','saveeditdata','deletelist','Organization Hierarchy'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                        $acl->allow('teamlead', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));

		 $acl->addResource(new Zend_Acl_Resource('default:leaverequest'));
                            $leaverequest_add = 'yes';
                                if($this->id_param == '' && $leaverequest_add == 'yes')
                                    $acl->allow('teamlead','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request','edit'));

                                else
                                    $acl->allow('teamlead','default:leaverequest', array('index','getshift','setalloted','isleavearequesttype','saveleaverequestdetails','gethalfdaydetails','editpopup','updateleavedetails','add','Leave Request'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:manageremployeevacations'));
                            $acl->allow('teamlead', 'default:manageremployeevacations', array('index','edit','view','Employee Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:myemployees'));
                            $acl->allow('teamlead', 'default:myemployees', array('index','perview','comview','skillsview','expview','eduview','trainingview','additionaldetailsview','jobhistoryview','skillsedit','jobhistoryedit','expedit','eduedit','trainingedit','additionaldetailsedit','peredit','comedit','docview','docedit','employeereport','getempreportdata','empauto','emprptpdf','exportemployeereport','downloadreport','view','My Team'));

		 $acl->addResource(new Zend_Acl_Resource('default:myholidaycalendar'));
                            $acl->allow('teamlead', 'default:myholidaycalendar', array('index','view','My Holiday Calendar'));

		 $acl->addResource(new Zend_Acl_Resource('default:myteamappraisal'));
                            $acl->allow('teamlead', 'default:myteamappraisal', array('savelineresponse','savemngresponse','getempcontent','index','getsearchedempcontent','getsearchedstatus','downloadpdf','downloadUploadedFile','My Team Appraisal'));

		 $acl->addResource(new Zend_Acl_Resource('default:organisationinfo'));
                            $acl->allow('teamlead', 'default:organisationinfo', array('index','edit_old','saveupdate','uploadpreview','validateorgstartdate','getcompleteorgdata','addorghead','Organization Info'));

		 $acl->addResource(new Zend_Acl_Resource('default:pendingleaves'));
                            $acl->allow('teamlead', 'default:pendingleaves', array('index','delete','view','My Leave'));

		 $acl->addResource(new Zend_Acl_Resource('default:policydocuments'));
                            $acl->allow('teamlead', 'default:policydocuments', array('index','uploaddoc','deletedocument','addmultiple','uploadmultipledocs','view','View/Manage Policy Documents'));

		 $acl->addResource(new Zend_Acl_Resource('default:projects'));
                            $acl->allow('teamlead', 'default:projects', array('index','viewpopup','editpopup','add','edit','delete','view','Projects'));

		 $acl->addResource(new Zend_Acl_Resource('default:scheduleinterviews'));
                            $acl->allow('teamlead', 'default:scheduleinterviews', array('candidatepopup','index','downloadresume','getcandidates','edit','view','Interviews'));

		 $acl->addResource(new Zend_Acl_Resource('default:servicerequests'));
                            $servicerequests_add = 'yes';
                                if($this->id_param == '' && $servicerequests_add == 'yes')
                                    $acl->allow('teamlead','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','edit'));

                                else
                                    $acl->allow('teamlead','default:servicerequests', array('index','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets','index','add','uploadsave','uploaddelete','view','getrequests','changestatus','checkrequeststatus','getuserassets'));

                                
		 $acl->addResource(new Zend_Acl_Resource('default:structure'));
                            $acl->allow('teamlead', 'default:structure', array('index','Organization Structure'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:advances'));
                            $acl->allow('teamlead', 'expenses:advances', array('index','getprojects','myadvances','viewmoreadvances','clearadvancesdata','addreturnpopup','add','edit','delete','view','Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:employeeadvances'));
                            $acl->allow('teamlead', 'expenses:employeeadvances', array('index','add','edit','delete','view','Employee Advances'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:expenses'));
                            $acl->allow('teamlead', 'expenses:expenses', array('index','clone','addpopup','uploadsave','uploaddelete','displayreceipts','addtrippopup','submitexpense','addreceiptimage','expensestatus','listreportingmangers','viewmoremanagers','forwardexpenseto','downloadexpensepdf','bulkexpenses','getcategories','getprojects','getcurrency','uploadedfiles','add','edit','delete','view','Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:myemployeeexpenses'));
                            $acl->allow('teamlead', 'expenses:myemployeeexpenses', array('index','add','edit','delete','view','My Employee Expenses'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:receipts'));
                            $acl->allow('teamlead', 'expenses:receipts', array('index','downloadreceipt','downloadexpensereceipt','deletereceipt','uploadsave','displayreceipts','viewmorereceipts','listexpenses','addreceipttoexpense','viewmoreexpenses','cleardata','showreceiptspopup','listtrips','viewmoretrips','addexpensetotrip','add','edit','delete','view','Receipts'));

		 $acl->addResource(new Zend_Acl_Resource('expenses:trips'));
                            $acl->allow('teamlead', 'expenses:trips', array('index','addpopup','tripstatus','deleteexpense','downloadtrippdf','add','edit','delete','view','Trips'));

		 $acl->addResource(new Zend_Acl_Resource('exit:allexitproc'));
                            $acl->allow('teamlead', 'exit:allexitproc', array('index','editpopup','updateexitprocess','assignquestions','add','edit','view','All Exit Procedures'));

		 $acl->addResource(new Zend_Acl_Resource('exit:exitproc'));
                            $acl->allow('teamlead', 'exit:exitproc', array('index','questions','savequestions','add','edit','view','Initiate/Check Status'));
}if($role == 19 )
           {
		 $acl->addResource(new Zend_Acl_Resource('default:agencylist'));
                            $acl->allow('qqw', 'default:agencylist', array('index','deletepoc','add','edit','delete','view','Agencies'));

		 $acl->addResource(new Zend_Acl_Resource('default:bgscreeningtype'));
                            $acl->allow('qqw', 'default:bgscreeningtype', array('index','addpopup','add','edit','delete','view','Screening Types'));

		 $acl->addResource(new Zend_Acl_Resource('default:dashboard'));
                        $acl->allow('qqw', 'default:dashboard', array('index','saveuserdashboard','getwidgtes','upgradeapplication','emailsettings','changepassword','changemenucolor','reset','editpassword','update','uploadpreview','viewprofile','viewsettings','savemenuwidgets','getmenuname','fetchmenuname','getnavids','getopeningpositondate','menuwork','employeeimageupdate'));

		 $acl->addResource(new Zend_Acl_Resource('default:empscreening'));
                            $acl->allow('qqw', 'default:empscreening', array('index','getemployeedata','getagencylist','getpocdata','forcedfullupdate','checkscreeningstatus','uploadfeedback','download','deletefeedback','add','edit','delete','view','Employee/Candidate Screening'));

		 $acl->addResource(new Zend_Acl_Resource('default:index'));
                        $acl->allow('qqw', 'default:index', array('index','signin','loginpopupsave','checkchildtable','logout','clearsessionarray','forcelogout','browserfailure','sendpassword','updatecontactnumber','getstates','getstatesnormal','getdomainnormal','getcert','getschedulecycle','getdomainnormalinstant','getperiodsnormal','getcities','getcitiesnormal','getdepartments','getpositions','gettargetcurrency','calculatedays','calculatebusinessdays','calculatecalendardays','fromdatetodate','fromdatetodateorg','validateorgheadjoiningdate','medicalclaimdates','gettimeformat','chkcurrenttime','popup','createorremoveshortcut','sessiontour','getissuingauthority','setsessionval','checkisactivestatus','updatetheme','welcome','getmultidepts','getmultiemps'));
}

     // setup acl in the registry for more
           Zend_Registry::set('acl', $acl);
           $this->_acl = $acl;
    }
   return $this->_acl;
}
  }
  
  ?>