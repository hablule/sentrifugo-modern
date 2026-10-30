<?php
/**
 * User Controller
 * Controller for user management
 * handles user CRUD operations
 * 
 * @category   Application_Module
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */

class Default_UserController extends Zend_Controller_Action
{
	public function init()
	{
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		require_once 'application/modules/default/library/utilClasses/Util.php';
	}
	public function indexAction()
	{

		$model = new Default_Model_users();
		$call = $this->_getParam('call');
		if ($call == 'ajaxcall')
			$this->_helper->layout->disableLayout();

		$view = Zend_Layout::getMvcInstance()->getView();
		$objname = $this->_getParam('objname');
		$refresh = $this->_getParam('refresh');
		$dashboardcall = $this->_getParam('dashboardcall');

		$data = array();
		$searchQuery = '';
		$searchArray = array();
		$tablecontent = '';

		if ($refresh == 'refresh') {
			if ($dashboardcall == 'Yes')
				$perPage = DASHBOARD_PERPAGE;
			else
				$perPage = PERPAGE;
			$sort = 'DESC';
			$by = 'id';
			$pageNo = 1;
			$searchData = '';
			$searchQuery = '';
			$searchArray = '';
		} else {
			$sort = ($this->_getParam('sort') != '') ? $this->_getParam('sort') : 'DESC';
			$by = ($this->_getParam('by') != '') ? $this->_getParam('by') : 'id';
			if ($dashboardcall == 'Yes')
				$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
			else
				$perPage = $this->_getParam('per_page', PERPAGE);
			$pageNo = $this->_getParam('page', 1);
			/** search from grid - START **/
			$searchData = $this->_getParam('searchData');
			$searchData = rtrim($searchData, ',');
			/** search from grid - END **/
		}

		$dataTmp = $model->getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall);

		array_push($data, $dataTmp);
		$this->view->dataArray = $data;
		$this->view->call = $call;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}

	public function addAction()
	{
		try {
			$form = new Default_Form_User();
			$model = new Default_Model_User();
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$loginUserId = $auth->getStorage()->read()->id;
			}
			$modelemp = new Default_Model_Employee();
			$empdatas = $modelemp->getEmplist();

			$sitepreferencemodel = new Default_Model_Sitepreference();
			$sitepreferenceArr = $sitepreferencemodel->SitePreferanceData();

			if (!empty($sitepreferenceArr)) {
				if ($sitepreferenceArr[0]['passwordid'] == 1) {

					$form->password->addValidator("regex", true, array(
						'pattern' => '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/',
						'messages' => array(
							'regexNotMatch' => 'Please enter only alphanumeric characters.'
						)
					));

					$form->confirmpassword->addValidator("regex", true, array(
						'pattern' => '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/',
						'messages' => array(
							'regexNotMatch' => 'Please enter only alphanumeric characters.'
						)
					));
				} else if ($sitepreferenceArr[0]['passwordid'] == 2) {
					$form->password->addValidator("regex", true, array(
						'pattern' => '/^(?=.*[0-9])(?=.*[a-zA-Z])(?=.*[.\-#$@&\_*])([a-zA-Z0-9.\-#$@&\_*]+)$/',
						'messages' => array(
							'regexNotMatch' => 'Please enter only characters,numbers and special characters.'
						)
					));

					$form->confirmpassword->addValidator("regex", true, array(
						'pattern' => '/^(?=.*[0-9])(?=.*[a-zA-Z])(?=.*[.\-#$@&\_*])([a-zA-Z0-9.\-#$@&\_*]+)$/',
						'messages' => array(
							'regexNotMatch' => 'Please enter only characters,numbers and special characters.'
						)
					));
				} else if ($sitepreferenceArr[0]['passwordid'] == 3) {
					$form->password->addValidator("regex", true, array(
						'pattern' => '/^[0-9]+$/',
						'messages' => array(
							'regexNotMatch' => 'Please enter numbers only.'
						)
					));

					$form->confirmpassword->addValidator("regex", true, array(
						'pattern' => '/^[0-9]+$/',
						'messages' => array(
							'regexNotMatch' => 'Please enter numbers only.'
						)
					));
				} else {
					$form->password->addValidator("regex", true, array(
						'pattern' => '/^(?=.*[0-9])(?=.*[.\-#$@&\_*])([0-9.\-#$@&\_*]+)$/',
						'messages' => array(
							'regexNotMatch' => 'Please enter only numbers and special characters.'
						)
					));

					$form->confirmpassword->addValidator("regex", true, array(
						'pattern' => '/^(?=.*[0-9])(?=.*[.\-#$@&\_*])([0-9.\-#$@&\_*]+)$/',
						'messages' => array(
							'regexNotMatch' => 'Please enter only numbers and special characters.'
						)
					));
				}
			} else {
				$form->password->addValidator("regex", true, array(
					'pattern' => '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/',
					'messages' => array(
						'regexNotMatch' => 'Please enter only alphanumeric characters.'
					)
				));

				$form->confirmpassword->addValidator("regex", true, array(
					'pattern' => '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/',
					'messages' => array(
						'regexNotMatch' => 'Please enter only alphanumeric characters.'
					)
				));
			}


			$util = new utlis();
			$form->employee_id = $util->populate_select($form->employee_id, $empdatas, 'id', 'userfullname', 'Select');

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($this->getRequest()->getPost())) {
					if ($this->_request->getParam('password') != $this->_request->getParam('confirmpassword')) {
						$msgarray['allfk'] = 'Password and confirm password in not matched';
					} else {

						// $salt = $this->_getParam('username').uniqid();
						$hashpassword = password_hash($this->_request->getParam('password'), PASSWORD_DEFAULT);

						$emailaddress = $this->_request->getParam('emailaddress');
						$data = array(
							'username' => $this->_request->getParam('username'),
							'emailaddress' => $this->_request->getParam('emailaddress'),
							'code' => $this->_request->getParam('code'),
							'description' => $this->_request->getParam('description'),
							'emppassword' => $hashpassword,
							'empid' => $this->_request->getParam('employee_id'),

							'createdby' => $loginUserId,
							'createddate' => gmdate('Y-m-d H:i:s')
						);
						$model->SaveorUpdateData($data, '');
						//die(json_encode($data));
						$base_url = 'http://' . $this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
						$view = $this->getHelper('ViewRenderer')->view;
						$this->view->emp_name = $this->_request->getParam('username');
						$this->view->password = $this->_request->getParam('password');
						$this->view->emp_id = $this->_getParam('username');
						$this->view->base_url = $base_url;
						$text = $view->render('mailtemplates/newpassword.phtml');
						$options['subject'] = APPLICATION_NAME . ': Login Credentials';
						$options['header'] = 'Greetings from Sentrifugo';
						$options['toEmail'] = $emailaddress;
						$options['toName'] = $this->view->emp_name;
						$options['message'] = $text;
						$result = sapp_Global::_sendEmail($options);

						//end of mailing
						$this->_helper->getHelper("FlashMessenger")->addMessage(array('success' => 'User Added Successful'));
						$this->_redirect('user');
					}
				}

				$messages = $form->getMessages();
				//die(json_encode($messages));

				foreach ($messages as $key => $val) {
					foreach ($val as $key2 => $val2) {
						$msgArray[$key] = $val2;
						break;
					}
				}
			}
			$this->view->msgarray = $msgArray;
			$this->view->form = $form;
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function editAction()
	{
		try {

			$User_Form = new Default_Form_User();
			$userId = $this->_getParam('id');
			$userModel = new Default_Model_User();
			$userData = $userModel->getsingleuser($userId);

			$User_Form->code->setValue($userData[0]['username']);
			$User_Form->username->setValue($userData[0]['code']);
			$User_Form->description->setValue($userData[0]['description']);
			$User_Form->isactive->setValue($userData[0]['isactive']);

			
			$this->view->form = $User_Form;
			//code...
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
	public function viewAction()
	{
		$User_Form = new Default_Form_User();
		//
		// die("here");
		$this->view->form = $User_Form;
	}
}
