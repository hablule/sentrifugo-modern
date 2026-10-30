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

class Default_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

	protected function _initAppAutoload()
	{

		$autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->registerNamespace('Ingot_');
		$autoloader->registerNamespace('ZendX_');
		$autoloader->registerNamespace('Jqgrid_');
		$autoloader->registerNamespace('sapp_');

		$this->options = $this->getOptions();

		return $autoloader;
	}

	protected function _initView()
	{

		$theme = 'default';
		$templatePath  = APPLICATION_PATH . '/../public/themes/' . $theme . '/templates';
		Zend_Registry::set('user_date_format', 'm-d-Y');
		Zend_Registry::set('calendar_date_format', 'mm-dd-yy');
		Zend_Registry::set('db_date_format', 'Y-m-d');
		Zend_Registry::set('perpage', 10);
		Zend_Registry::set('menu', 'home');
		Zend_Registry::set('eventid', '');

		$dir_name = $_SERVER['DOCUMENT_ROOT'] . rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/');
		Zend_Registry::set('acess_file_path', $dir_name . SEPARATOR . "application" . SEPARATOR . "modules" . SEPARATOR . "default" . SEPARATOR . "plugins" . SEPARATOR . "AccessControl.php");
		Zend_Registry::set('siteconstant_file_path', $dir_name . SEPARATOR . "public" . SEPARATOR . "site_constants.php");
		Zend_Registry::set('emailconstant_file_path', $dir_name . SEPARATOR . "public" . SEPARATOR . "email_constants.php");
		Zend_Registry::set('emptab_file_path', $dir_name . SEPARATOR . "public" . SEPARATOR . "emptabconfigure.php");
		Zend_Registry::set('emailconfig_file_path', $dir_name . SEPARATOR . "public" . SEPARATOR . "mail_settings_constants.php");
		Zend_Registry::set('application_file_path', $dir_name . SEPARATOR . "public" . SEPARATOR . "application_constants.php");

		$date = new Zend_Date();
		Zend_Registry::set('currentdate', ($date->get('yyyy-MM-dd HH:mm:ss')));

		Zend_Registry::set('currenttime', ($date->get('HH:mm:ss')));

		Zend_Registry::set('logo_url', '/public/images/landing_header.jpg');
		$view = new Zend_View();
		$view->setEscape('stripslashes');
		$view->setBasePath($templatePath);
		$view->setScriptPath(APPLICATION_PATH);
		$view->addHelperPath('ZendX/JQuery/View/Helper', 'ZendX_JQuery_View_Helper');

		$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
		$viewRenderer->setView($view);

		Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
		return $this;
	}

	public function _initViewHelpers()
	{
		$this->bootstrap('layout');
		$layout = $this->getResource('layout');
		$view = $layout->getView();
		$view->doctype('HTML5');
	}

	protected function _initDbProfiler()
	{

		$this->bootstrap('db');
		$profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
		$profiler->setEnabled(true);
		$this->getPluginResource('db')->getDbAdapter()->setProfiler($profiler);
	}

	public function _initFilter()
	{
		// Do ...
	}

	public function _initRoutes()
	{

		$router = Zend_Controller_Front::getInstance()->getRouter();




		$leaverequestshiftroute = new Zend_Controller_Router_Route('leaverequest/getshift', array(
			'module' => 'default',
			'controller' => 'leaverequest',
			'action' => 'getshift'
		));
		$payrollindexroute = new Zend_Controller_Router_Route('payroll', array(
			'module' => 'default',
			'controller' => 'payroll',
			'action' => 'index'
		));
		$payrolladdroute = new Zend_Controller_Router_Route('payroll/add', array(
			'module' => 'default',
			'controller' => 'payroll',
			'action' => 'add'
		));
		$payrolleditpoproute = new Zend_Controller_Router_Route('payroll/editpopup/id/:id', array(
			'module' => 'default',
			'controller' => 'payroll',
			'action' => 'editpopup'
		));
		$payrolleditroute = new Zend_Controller_Router_Route('payroll/edit/id/:id', array(
			'module' => 'default',
			'controller' => 'payroll',
			'action' => 'edit'
		));
		$payrollcalculateroute = new Zend_Controller_Router_Route('payroll/calculate', array(
			'module' => 'default',
			'controller' => 'payroll',
			'action' => 'calculate'
		));

		/* 
		$empbenefitperktyperout = new Zend_Controller_Router_Route('empbenefits/getperktype', array(
			'module' => 'default',
			'controller' => 'empbenefits',
			'action' => 'perktype'
		));

		$empdeductiveeditempperkroute = new Zend_Controller_Router_Route('empbenefits/editempperk', array(
			'module' => 'default',
			'controller' => 'empbenefits',
			'action' => 'editempperk'
		)); 
		
		$empbenfiteditempperkroute = new Zend_Controller_Router_Route('empdeductive/editempdeductive', array(
			'module' => 'default',
			'controller' => 'empdeductive',
			'action' => 'editempdeductive'
		));

		$empdeductiveperktyperout = new Zend_Controller_Router_Route('empdeductive/getdeductivetype', array(
			'module' => 'default',
			'controller' => 'empdeductive',
			'action' => 'deductivetype'
		));

		$empdeductiveaddpopuprout = new Zend_Controller_Router_Route('empdeductive/addpopup', array(
			'module' => 'default',
			'controller' => 'empdeductive',
			'action' => 'addpopup'
		)); 
		
		$perkcateaddrout = new Zend_Controller_Router_Route('empbenefits/addcategory',array(
			'module'=>'default',
			'controller'=>'empbenefits',
			'action'=>'addcategory'
		));

		$addpayrollroute = new Zend_Controller_Router_Route('addperk', array(
			'module' => 'default',
			'controller' => 'addperk',
			'action' => 'index'
		));

		$addpayrolladdroute = new Zend_Controller_Router_Route('addperk/add', array(
			'module' => 'default',
			'controller' => 'addperk',
			'action' => 'add'
		));

		$deletepayrolladdroute = new Zend_Controller_Router_Route('addperk/delete', array(
			'module' => 'default',
			'controller' => 'addperk',
			'action' => 'delete'
		));

		$perkindexroute = new Zend_Controller_Router_Route('perk', array(
			'module' => 'default',
			'controller' => 'perk',
			'action' => 'index'
		));

		$Emplistindexroute = new Zend_Controller_Router_Route('Emplist', array(
			'module' => 'default',
			'controller' => 'Emplist',
			'action' => 'index'
		));

		$perkaddroute = new Zend_Controller_Router_Route('perk/add', array(
			'module' => 'default',
			'controller' => 'perk',
			'action' => 'add'
		));

		$perkaddcategoryroute = new Zend_Controller_Router_Route('perk/addcategory', array(
			'module' => 'default',
			'controller' => 'perk',
			'action' => 'addcategory'
		));

		$perkeditroute = new Zend_Controller_Router_Route('perk/edit', array(
			'module' => 'default',
			'controller' => 'perk',
			'action' => 'edit'
		));

		$perkdeleteroute = new Zend_Controller_Router_Route('perk/delete', array(
			'module' => 'default',
			'controller' => 'perk',
			'action' => 'delete'
		));

		$perkviewroute = new Zend_Controller_Router_Route('perk/view', array(
			'module' => 'default',
			'controller' => 'perk',
			'action' => 'view'
		));

		$addcategoryroute = new Zend_Controller_Router_Route('deductive/addcategory', array(
			'module' => 'default',
			'controller' => 'deductive',
			'action' => 'addcategory'
		));

		$deductiveaddroute = new Zend_Controller_Router_Route('deductive/add', array(
			'module' => 'default',
			'controller' => 'deductive',
			'action' => 'add'
		));

		$deductiveeditroute = new Zend_Controller_Router_Route('deductive/edit', array(
			'module' => 'default',
			'controller' => 'deductive',
			'action' => 'edit'
		));

		$deductiveviewroute = new Zend_Controller_Router_Route('deductive/view', array(
			'module' => 'default',
			'controller' => 'deductive',
			'action' => 'view'
		));

		$deductivedeleteroute = new Zend_Controller_Router_Route('deductive/delete', array(
			'module' => 'default',
			'controller' => 'deductive',
			'action' => 'delete'
		));

		$deductiveindexroute = new Zend_Controller_Router_Route('deductive', array(
			'module' => 'default',
			'controller' => 'deductive',
			'action' => 'index'
		));
		*/

		$adddeductiveindexroute = new Zend_Controller_Router_Route('adddeductive', array(
			'module' => 'default',
			'controller' => 'adddeductive',
			'action' => 'index'
		));

		/* 

		$plusadddeductiveindexroute = new Zend_Controller_Router_Route('adddeductive/add', array(
			'module' => 'default',
			'controller' => 'adddeductive',
			'action' => 'add'
		));

		$deleteadddeductiveindexroute = new Zend_Controller_Router_Route('adddeductive/delete', array(
			'module' => 'default',
			'controller' => 'adddeductive',
			'action' => 'delete'
		));

 		*/

		$listcandroute = new Zend_Controller_Router_Route('listcandidates', array(
			'module' => 'default',
			'controller' => 'listcandidates',
			'action' => 'index'
		));
		$candidatelistroute = new Zend_Controller_Router_Route('list/selected', array(
			'module' => 'default',
			'controller' => 'listcandidates',
			'action' => 'getcandidates'
		));
		$route = new Zend_Controller_Router_Route('login', array(
			'module' => 'default',
			'controller' => 'index',
			'action' => 'login'
		));
		$signinroute = new Zend_Controller_Router_Route('signin', array(
			'module' => 'default',
			'controller' => 'index',
			'action' => 'signin'
		));




		$welcomeroute = new Zend_Controller_Router_Route('welcome', array(
			'module' => 'default',
			'controller' => 'index',
			'action' => 'welcome'
		));

		$candidateeducationroute = new Zend_Controller_Router_Route('education/index', array(
			'module' => 'default',
			'controller' => 'candidateeducation',
			'action' => 'index'
		));
		$candidateeducationadd = new Zend_Controller_Router_Route('education/add', array(
			'module' => 'default',
			'controller' => 'candidateeducation',
			'action' => 'add'
		));
		$candidateeducationnext = new Zend_Controller_Router_Route('education/next', array(
			'module' => 'default',
			'controller' => 'candidateeducation',
			'action' => 'next'
		));
		$formview = new Zend_Controller_Router_Route('formview', array(
			'module' => 'default',
			'controller' => 'formview',
			'action' => 'index'
		));
		$formaddelement = new Zend_Controller_Router_Route('formview/addelement', array(
			'module' => 'default',
			'controller' => 'formview',
			'action' => 'addpopup'
		));
		$formupdateelement = new Zend_Controller_Router_Route('formview/updateelement', array(
			'module' => 'default',
			'controller' => 'formview',
			'action' => 'update'
		));
		$candidateeducationprevroute = new Zend_Controller_Router_Route('education/prev', array(
			'module' => 'default',
			'controller' => 'candidateeducation',
			'action' => 'prev'
		));


		$selfcandidateviewroute = new Zend_Controller_Router_Route('resume', array(
			'module' => 'default',
			'controller' => 'resume',
			'action' => 'index'
		));

		$thisisstupidroute = new Zend_Controller_Router_Route('selfcandidateadd/add', array(
			'module' => 'default',
			'controller' => 'resume',
			'action' => 'addcandidate'
		));

		$cvroute = new Zend_Controller_Router_Route('addcv', array(
			'module' => 'default',
			'controller' => 'cvadd',
			'action' => 'index'
		));

		$addcvroute = new Zend_Controller_Router_Route('addcv/add', array(
			'module' => 'default',
			'controller' => 'cvadd',
			'action' => 'uploadsave'
		));
		$savecvroute = new Zend_Controller_Router_Route('addcv/save', array(
			'module' => 'default',
			'controller' => 'cvadd',
			'action' => 'addcv'
		));
		$addcvroute1 = new Zend_Controller_Router_Route('gechcv', array(
			'module' => 'default',
			'controller' => 'gechcv',
			'action' => 'index'
		));

		$personaldetailroute = new Zend_Controller_Router_Route('resume/add', array(
			'module' => 'default',
			'controller' => 'resume',
			'action' => 'add',
		));

		$personaldetailnextroute = new Zend_Controller_Router_Route('resume/next', array(
			'module' => 'default',
			'controller' => 'resume',
			'action' => 'next',
		));

		$personaldetaillanguageroute = new Zend_Controller_Router_Route('resume/addlanguage', array(
			'module' => 'default',
			'controller' => 'resume',
			'action' => 'language'
		));
		$getTableColumns = new Zend_Controller_Router_Route('getTableColumns', array(
			'module' => 'default',
			'controller' => 'formview',
			'action' => 'getcolumns'
		));

		$getFormElement = new Zend_Controller_Router_Route('getformelement', array(
			'module' => 'default',
			'controller' => 'formview',
			'action' => 'getformelement'
		));

		$getParticualrData = new Zend_Controller_Router_Route('getparticulardataforall', array(
			'module' => 'default',
			'controller' => 'formview',
			'action' => 'getparticulardataforall'
		));
		$workexproute = new Zend_Controller_Router_Route('workexp', array(
			'module' => 'default',
			'controller' => 'workexp',
			'action' => 'index'
		));
		$addworkexproute = new Zend_Controller_Router_Route('workexp/add', array(
			'module' => 'default',
			'controller' => 'workexp',
			'action' => 'add'
		));
		$workexpnextproute = new Zend_Controller_Router_Route('workexp/next', array(
			'module' => 'default',
			'controller' => 'workexp',
			'action' => 'next'
		));






		/* $empbankaccounteditroute = new Zend_Controller_Router_Route('empbankaccount\edit\userid\:id', array(
			'module' => 'default',
			'controller' => 'Empbankaccount',
			'action' => 'edit'
		)); */

		$empbankchild = new Zend_Controller_Router_Route('index\checkchildtable', array(
			'module' => 'default',
			'controller' => 'index',
			'action' => 'checkchildtable'
		));
		/* $empbankaccountviewroute = new Zend_Controller_Router_Route('empbankaccount\view\userid\:id', array(
			'module' => 'default',
			'controller' => 'Empbankaccount',
			'action' => 'view'
		)); */
		$viewprofileroute = new Zend_Controller_Router_Route('viewprofile', array(
			'module' => 'default',
			'controller' => 'dashboard',
			'action' => 'viewprofile'
		));

		$viewsettingsroute = new Zend_Controller_Router_Route('viewsettings/:tab', array(
			'module' => 'default',
			'controller' => 'dashboard',
			'action' => 'viewsettings',
		));

		$changepasswordroute = new Zend_Controller_Router_Route('changepassword', array(
			'module' => 'default',
			'controller' => 'dashboard',
			'action' => 'changepassword'
		));

		$empleavesummaryroute = new Zend_Controller_Router_Route('empleavesummary/:status', array(
			'module' => 'default',
			'controller' => 'empleavesummary',
			'action' => 'index',
		));

		$approvedrequisitionroute = new Zend_Controller_Router_Route('approvedrequisitions/:status', array(
			'module' => 'default',
			'controller' => 'approvedrequisitions',
			'action' => 'index',
		));

		$shortlistedroute = new Zend_Controller_Router_Route('shortlistedcandidates/:status', array(
			'module' => 'default',
			'controller' => 'shortlistedcandidates',
			'action' => 'index',
		));

		$empscreeningroute = new Zend_Controller_Router_Route('empscreening/con/:status', array(
			'module' => 'default',
			'controller' => 'empscreening',
			'action' => 'index',
		));

		$error_route = new Zend_Controller_Router_Route('error', array(
			'module' => 'default',
			'controller' => 'error',
			'action' => 'error',
		));

		/** route for policy documents **/
		$polidydocs_route = new Zend_Controller_Router_Route('policydocuments/id/:id/*', array(
			'module' => 'default',
			'controller' => 'policydocuments',
			'action' => 'index',
		));

		/** route for adding multiple policy documents **/
		$multiplepolidydocs_route = new Zend_Controller_Router_Route('policydocuments/addmultiple/:id', array(
			'module' => 'default',
			'controller' => 'policydocuments',
			'action' => 'addmultiple',
		));

		$myleavesroute = new Zend_Controller_Router_Route('pendingleaves/:flag', array(
			'module' => 'default',
			'controller' => 'pendingleaves',
			'action' => 'index',
		));


		$addundumindexroute = new Zend_Controller_Router_Route('addundum', array(
			'module' => 'default',
			'controller' => 'addundum',
			'action' => 'index',
		));


		$userdefinedclassroute = new Zend_Controller_Router_Route('userdefinedclass', array(
			'module' => 'default',
			'controller' => 'userdefinedclass',
			'action' => 'index'
		));
		$userdefinedclassgetfieldroute = new Zend_Controller_Router_Route('userdefinedclass/getfields', array(
			'module' => 'default',
			'controller' => 'userdefinedclass',
			'action' => 'getfields'
		));
		$userdefinedclasscreatefieldroute = new Zend_Controller_Router_Route('userdefinedclass/rundefinedfunction', array(
			'module' => 'default',
			'controller' => 'userdefinedclass',
			'action' => 'rundefinedfunction'
		));

		$empviewroute = new Zend_Controller_Router_Route('employee/index2', array(
			'module' => 'default',
			'controller' => 'employee',
			'action' => 'index2'
		));

		/* 
		$userroleroute = new Zend_Controller_Router_Route('userrole/index', array(
			'module' => 'default',
			'controller' => 'userrole',
			'action' => 'index'
		));
 		*/

		/* 

		$myovertimeseditroute = new Zend_Controller_Router_Route('manageremployeeovertime/edit/id/:id', array(
			'module' => 'default',
			'controller' => 'manageremployeeovertime',
			'action' => 'edit'
		)); 
		
		$myovertimeseditpopuproute = new Zend_Controller_Router_Route('manageremployeeovertime/editpopup/id/:id', array(
			'module' => 'default',
			'controller' => 'manageremployeeovertime',
			'action' => 'editpopup'
		)); 
		
		$myovertimesviewroute = new Zend_Controller_Router_Route('manageremployeeovertime/view/id/:id', array(
			'module' => 'default',
			'controller' => 'manageremployeeovertime',
			'action' => 'view'
		)); 

		*/

		$router->addRoute('editpayroll', $payrolleditroute);
		$router->addRoute('editpoppayroll', $payrolleditpoproute);
		$router->addRoute('addpayroll', $payrolladdroute);
		$router->addRoute('login', $route);
		$router->addRoute('welcome', $welcomeroute);
		$router->addRoute('viewsettings', $viewsettingsroute);
		$router->addRoute('empleavesummary', $empleavesummaryroute);
		$router->addRoute('addundumindexroute', $addundumindexroute);




		$router->addRoute('empviewroute', $empviewroute);






		$router->addRoute('savecv', $savecvroute);
		$router->addRoute('cvadd', $cvroute);
		$router->addRoute('approvedrequisitions', $approvedrequisitionroute);
		$router->addRoute('shortlistedcandidates', $shortlistedroute);
		$router->addRoute('empscreening', $empscreeningroute);
		$router->addRoute('policydocuments', $polidydocs_route);
		$router->addRoute('selfcandidate', $selfcandidateviewroute);
		$router->addRoute('addcandidate', $thisisstupidroute);
		$router->addRoute('resume', $personaldetailroute);
		$router->addRoute('workexp', $workexproute);
		$router->addRoute('addworkexp', $addworkexproute);

		//	$router->addRoute('perkcateaddrout',$perkcateaddrout);

		$router->addRoute('formview', $formview);

		// 	$router->addRoute('empdeductiveaddpopuprout', $empdeductiveaddpopuprout); 


		$router->addRoute('listcandidate', $listcandroute);
		$router->addRoute('addcv', $addcvroute);
		$router->addRoute('multiplepolicydocuments', $multiplepolidydocs_route);
		$router->addRoute('myleaves', $myleavesroute);

		/* 
		$router->addRoute('myovertimesviewroute',$myovertimesviewroute);
		$router->addRoute('myovertimeseditroute',$myovertimeseditroute);
		$router->addRoute('myovertimeseditpopuproute',$myovertimeseditpopuproute); 
		*/



		$router->addRoute('signin', $signinroute);
		$router->addRoute('candidateeducationroute', $candidateeducationroute);
		$router->addRoute('candidateeducationadd', $candidateeducationadd);
		$router->addRoute('formaddelement', $formaddelement);
		$router->addRoute('formupdateelement', $formupdateelement);
		$router->addRoute('getTableColumns', $getTableColumns);
		$router->addRoute('getFormElement', $getFormElement);
		$router->addRoute('getParticualrData', $getParticualrData);

		$router->addRoute('list', $candidatelistroute);
		$router->addRoute('workexpnextproute', $workexpnextproute);
		$router->addRoute('candidateeducationnext', $candidateeducationnext);
		$router->addRoute('personaldetailnextroute', $personaldetailnextroute);
		$router->addRoute('personaldetaillanguageroute', $personaldetaillanguageroute);





		/* 

		$router->addRoute('empbenefitperktyperout', $empbenefitperktyperout); 
		 
		$router->addRoute('empbenfiteditempperkroute', $empbenfiteditempperkroute);
		$router->addRoute('empdeductiveperktyperout', $empdeductiveperktyperout); 
		
		$router->addRoute('empdeductiveeditempperkroute', $empdeductiveeditempperkroute); 

		*/






		$router->addRoute('payrollindexroute', $payrollindexroute);
		$router->addRoute('payrollcalculateroute', $payrollcalculateroute);


		$router->addRoute('leaverequestshiftroute', $leaverequestshiftroute);

		$router->addRoute('userdefinedclassroute', $userdefinedclassroute);
		$router->addRoute('userdefinedclassgetfieldroute', $userdefinedclassgetfieldroute);
		$router->addRoute('userdefinedclasscreatefieldroute', $userdefinedclasscreatefieldroute);

		/* 
		$router->addRoute('empbankaccounteditroute', $empbankaccounteditroute);
		$router->addRoute('empbankaccountviewroute', $empbankaccountviewroute); 
		*/

		$router->addRoute('checkchildtable', $empbankchild);
		/* $router->addRoute('userroleroute', $userroleroute); */
	}

	protected function _initPlugins()
	{
		$front = Zend_Controller_Front::getInstance();

		// Adjust this path based on where you actually saved AclEnforcer.php!
		$pluginPath = APPLICATION_PATH . '/../library/Sentrifugo/Plugin/AclEnforcer.php';

		if (file_exists($pluginPath)) {
			require_once $pluginPath;
			// Register the ACL Enforcer Plugin
			// We use a high priority number (like 100) to ensure it runs early
			$front->registerPlugin(new Sentrifugo_Plugin_AclEnforcer());
		} else {
			// Failsafe: Prevents the white-screen crash if the file is moved/missing
			error_log("CRITICAL ERROR: AclEnforcer plugin not found at " . $pluginPath);
		}
	}

	/**
	 * Initializes the Zend Cache Manager resource.
	 * This method is automatically called by Zend_Application when it finds
	 * 'resources.cache' settings in application.ini.
	 */
	protected function _initCache()
	{
		$options = $this->getOptions();
		$cacheConfig = $options['cache'];

		$cache = Zend_Cache::factory(
			$cacheConfig['frontend']['name'],
			$cacheConfig['backend']['name'],
			$cacheConfig['frontend']['options'],
			$cacheConfig['backend']['options']
		);

		Zend_Registry::set('cache', $cache);
		return $cache;
	}
}
