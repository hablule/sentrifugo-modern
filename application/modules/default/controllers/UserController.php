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

	protected $_loginUserId;
	protected $_loginuserRole;
	protected $_loginuserGroup;
	protected $_model;
	protected $_options;

	/* =================================================================================
     * 1: INITIALIZATION
     * ================================================================================= */

	public function init()
	{
		$this->_model   = new Default_Model_User();
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();

		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$user = $auth->getStorage()->read();
			$this->_loginUserId    = $user->id;
			$this->_loginuserRole  = $user->emprole;
			$this->_loginuserGroup = $user->group_id;

			$this->view->loginuserRole  = $this->_loginuserRole;
			$this->view->loginuserGroup = $this->_loginuserGroup;
		}

		require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
	}

	public function preDispatch()
	{
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		//	$ajaxContext->addActionContext('save', 'json')
		$ajaxContext->addActionContext('delete', 'json')
			->addActionContext('getmoreusers', 'html') // For Card View Pagination
			->initContext();
	}

	/* =================================================================================
     * 2: DATA DISPLAY
     * ================================================================================= */

	public function indexAction()
	{

		try {
			$request = $this->getRequest();

			// 1. View Preference Logic (Session)
			$session = new Zend_Session_Namespace('user_view_pref');
			if ($request->getParam('view_type')) {
				$session->view_type = $request->getParam('view_type');
			}
			$viewType = isset($session->view_type) ? $session->view_type : 'card';
			$this->view->viewType = $viewType;

			// 2. Setup Context
			$call = $request->getParam('call');
			if ($call == 'ajaxcall') {
				$this->_helper->layout->disableLayout();
			}

			$refresh = $this->_getParam('refresh');
			$dashboardcall = $this->_getParam('dashboardcall');

			// 3. Prepare Parameters
			if ($refresh == 'refresh') {
				$sort = 'ASC';
				$by = 'username'; // Standard sorting for new records
				$pageNo = 1;
				$searchData = '';
				$perPage = ($dashboardcall == 'Yes') ? DASHBOARD_PERPAGE : PERPAGE;
			} else {
				$sort          = $request->getParam('sort', 'ASC');
				$by            = $request->getParam('by', 'username');
				$pageNo        = $request->getParam('page', 1);
				if ($dashboardcall == 'Yes') {
					$perPage = $this->_getParam('per_page', DASHBOARD_PERPAGE);
				} else {
					$perPage = $this->_getParam('per_page', PERPAGE);
				}
				$searchData    = $request->getParam('searchData');
			}

			// 4. Permissions (For Add Button)
			$popConfigPermission = array();
			if (sapp_Global::_checkprivileges(USERMANAGEMENT, $this->_loginuserGroup, $this->_loginuserRole, 'add') == 'Yes') {
				$popConfigPermission[] = 'user';
			}
			$this->view->popConfigPermission = $popConfigPermission;

			// 5. Fetch Grid Data (Always needed for Grid Helper)
			$data = array();
			$dataTmp = $this->_model->getGrid($sort, $by, $perPage, $pageNo, rtrim($searchData, ','), $call, $dashboardcall);
			array_push($data, $dataTmp);

			$this->view->dataArray = $data;

			// 6. Fetch Card Data (Only if in Card Mode)
			if ($viewType == 'card' && $call != 'ajaxcall') {
				$limit = LIMIT; // Standard Limit from constants
				$offset = 0;

				// Search Params for Card View
				$search_val = $this->_getParam('search_val', null);
				$search_str = $this->_getParam('search_str', null);

				// Construct Search Query String for Model
				$cardSearchQuery = '';
				if ($search_val && $search_str) {
					$cardSearchQuery = "$search_val LIKE '%$search_str%'";
				}

				// Reuse getGridData to get the Select Object
				$select = $this->_model->getGridData('ASC', 'username', 1, $limit, $cardSearchQuery);

				// A. Get Total Count (for Pagination)
				$countSelect = clone $select;
				$countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
				$countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
				$totalRecords = count($this->_model->fetchAll($countSelect));

				// B. Get Current Page Data
				$select->limit($limit, $offset);
				$users = $this->_model->fetchAll($select)->toArray();

				$this->view->users          = $users;
				$this->view->totalcount     = $totalRecords;
				$this->view->limit          = $limit;
				$this->view->offset         = $limit; // Next offset
				$this->view->remainingcount = max(0, $totalRecords - $limit);
				$this->view->search_val     = $search_val;
				$this->view->search_str     = $search_str;
			}

			$this->view->call     = $call;
			$this->view->messages = $this->_helper->flashMessenger->getMessages();
		} catch (Exception $e) {
			if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
		}
	}

	public function viewAction()
	{
		$id = (int)$this->getRequest()->getParam('id');
		$form = $this->_prepareForm($id, true);

		if (!$form) {
			$this->view->nodata = "nodata";
		} else {
			$data = $this->_model->getRecordById($id);
			$this->view->isactive = ($data['isactive'] == 1) ? 'Active' : 'Inactive';
			$this->view->form = $form;
			$this->view->id = $id;
		}
		$this->view->controllername = 'user';
	}

	/* =================================================================================
     * GROUP 3: PERSISTENCE
     * ================================================================================= */

	public function addAction()
	{
		$this->view->form = $this->_prepareForm();
	}

	public function editAction()
	{
		$id = (int)$this->getRequest()->getParam('id');
		$this->view->form = $this->_prepareForm($id);
	}

	public function deleteAction()
	{
		$id = (int)$this->getRequest()->getParam('id');
		if ($id) {
			$this->_model->deleteRecord($id);
			sapp_Global::logManager(USERMANAGEMENT, 3, $this->_loginUserId, $id);
			$this->_helper->json(array('msgtype' => 'success', 'message' => 'User deleted successfully.'));
		}
		$this->_helper->json(array('msgtype' => 'error', 'message' => 'Invalid Request.'));
	}

	public function saveAction()
	{
		if (!$this->getRequest()->isPost()) {
			return $this->_redirect('user/index');
		}

		$form = new Default_Form_User();
		$request = $this->getRequest();
		$response = array('result' => 'error', 'message' => 'Invalid data');

		if ($form->isValid($request->getPost())) {

			// Password Matching Check
			$pwd = $request->getParam('password');
			$cnf = $request->getParam('confirmpassword');
			if (!empty($pwd) && $pwd != $cnf) {
				$msgarray['confirmpassword'] = "Passwords do not match.";
			} else {

				$db = Zend_Db_Table::getDefaultAdapter();
				$db->beginTransaction();

				try {
					$id = (int)$request->getParam('id');
					$util = new utlis();

					$data = array(
						'code'         => $request->getParam('code'),
						'username'     => $request->getParam('username'),
						'emailaddress' => $request->getParam('emailaddress'),
						'empid'        => $request->getParam('employee_id'),
						'description'  => $request->getParam('description'),
						// Form returns 1/0, DB expects 1/0 (int) for users, handled by form values
						'isactive'     => (int)$request->getParam('isactive'),
						'modifiedby'   => $this->_loginUserId,
						'modifieddate' => gmdate("Y-m-d H:i:s")
					);

					if (!empty($pwd)) {
						$data['emppassword'] = password_hash($pwd, PASSWORD_DEFAULT);
					}

					$actionFlag = 2; // Edit
					$saveMode   = 'Updated';

					if (!$id) {
						$data['createdby']   = $this->_loginUserId;
						$data['createddate'] = gmdate("Y-m-d H:i:s");
						$data['userstatus']  = 'new';
						$actionFlag = 1; // Add
						$saveMode   = 'Added';
					}

					$savedId = $this->_model->saveRecord($data, $id);
					$userId  = ($id) ? $id : $savedId;

					sapp_Global::logManager(USERMANAGEMENT, $actionFlag, $this->_loginUserId, $userId);

					// Send Email on creation
					if (!$id && $data['emailaddress']) {
						$this->_sendWelcomeEmail($data['username'], $data['emailaddress'], $pwd);
					}

					$db->commit();

					$this->_helper->flashMessenger->addMessage(array('success' => "User $saveMode successfully."));
					return $this->_redirect('user/index');
					
				} catch (Exception $e) {
					$db->rollBack();
					if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
					$response['message'] = 'Something went wrong, please try again later.';
				}
			}
		} else {
			$messages = $form->getMessages();
			$msgarray = array();

			// Flatten the nested array into simple strings
			foreach ($messages as $field => $errors) {
				// This takes the first error message for each field
				$msgarray[$field] = current($errors);
			}

			$this->view->msgarray = $msgarray;
		}

		// If validation fails, re-render the save view
		$this->view->form     = $form;
		$this->view->msgarray = $msgarray;
		$this->view->id       = $id;
		$this->render('add');
	}


	/* =================================================================================
     * GROUP 5: AJAX & HELPERS
     * ================================================================================= */

	public function getmoreusersAction()
	{
		// Security & Context
		$auth = Zend_Auth::getInstance();
		if (!$auth->hasIdentity()) {
			echo "Session Expired";
			exit;
		}

		$loginuserRole = $auth->getStorage()->read()->emprole;
		$loginuserGroup = $auth->getStorage()->read()->group_id;

		if (sapp_Global::_checkprivileges(USERMANAGEMENT, $loginuserGroup, $loginuserRole, 'view') != 'Yes') {
			echo "<div class='ml-alert-1-error'>You do not have permission to view more records.</div>";
			exit;
		}

		$this->_helper->layout->disableLayout();
		$request = $this->getRequest();

		$limit           = $this->_getParam('limit', LIMIT);
		$offset          = $this->_getParam('offset', 0);
		$count_remaining = $this->_getParam('count_remaining', 0);
		$search_val      = $this->_getParam('search_val');
		$search_str      = $this->_getParam('search_str');
		$viewType        = $this->_getParam('view_type', 'card');

		// Reuse getGridData for consistency
		$dataTmp = $this->_model->getAllData('ASC', 'username', $limit, $offset, $search_val, $search_str);

		$this->view->users = $dataTmp;
		$this->view->viewType = $viewType;

		$itemsAdded = count($dataTmp);
		$this->view->remainingcount = $count_remaining - $itemsAdded;

		$this->render('getmoreusers'); // Partial view
	}

	private function _prepareForm($id = null, $readonly = false)
	{
		$form = new Default_Form_User();
		$util = new utlis();

		// Populate Employee Dropdown
		$empModel = new Default_Model_Employee();
		$emps = $empModel->getEmplist();
		$form->employee_id = $util->populate_select($form->employee_id, $emps, 'id', 'userfullname', 'Select Employee');

		if ($id) {
			$data = $this->_model->getRecordById($id);
			if (!$data) return null;

			// Edit Mode adjustments
			$form->getElement('password')->setRequired(false)->removeValidator('NotEmpty');
			$form->getElement('confirmpassword')->setRequired(false)->removeValidator('NotEmpty');

			$form->populate($data);
			$form->getElement('submit')->setLabel('Update');
			$form->setAttrib('action', BASE_URL . 'user/save');
		} else {
			$form->setAttrib('action', BASE_URL . 'user/save');
		}

		if ($readonly) {
			foreach ($form->getElements() as $element) {
				$element->setAttrib('disabled', 'disabled');
			}
			$form->removeElement('submit');
		}

		return $form;
	}

	private function _sendWelcomeEmail($username, $email, $password)
	{
		try {
			$base_url = 'http://' . $this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
			$view = $this->getHelper('ViewRenderer')->view;
			$view->emp_name = $username;
			$view->password = $password;
			$view->base_url = $base_url;

			$text = $view->render('mailtemplates/newpassword.phtml');

			$options = array(
				'subject' => APPLICATION_NAME . ': Login Credentials',
				'header'  => 'Greetings from Sentrifugo',
				'toEmail' => $email,
				'toName'  => $username,
				'message' => $text
			);

			sapp_Global::_sendEmail($options);
		} catch (Exception $e) {
			// Email failure should not rollback transaction
		}
	}
}
