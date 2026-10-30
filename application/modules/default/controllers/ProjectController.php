<?php

/**
 * Project Controller
 *
 * Manages organizational Projects.
 * Complies with SSOT v6.5 (Server-side validation, Raw Array JSON).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_ProjectController extends Zend_Controller_Action
{
	protected $_loginUserId;
	protected $_loginuserRole;
	protected $_loginuserGroup;
	protected $_model;
	protected $_options;

	/* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

	public function init()
	{
		$this->_model   = new Default_Model_Project();
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();

		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$user = $auth->getStorage()->read();
			$this->_loginUserId    = (int)$user->id;
			$this->_loginuserRole  = (int)$user->emprole;
			$this->_loginuserGroup = (int)$user->group_id;

			// SSOT v6.5 Mandatory View Assignments
			$this->view->loginuserRole  = $this->_loginuserRole;
			$this->view->loginuserGroup = $this->_loginuserGroup;
		}

		require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
	}

	public function preDispatch()
	{
		$session = sapp_Global::_readSession();
		if (!isset($session)) {
			if ($this->getRequest()->isXmlHttpRequest()) {
				echo Zend_Json::encode(array('login' => 'failed'));
				exit;
			} else {
				$this->_redirect('');
			}
		}

		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('save', 'json')
			->addActionContext('delete', 'json')
			->addActionContext('makeactiveinactive', 'json')
			->initContext();
	}

	/* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

	public function indexAction()
	{
		try {
			$request = $this->getRequest();
			$call    = (string) $request->getParam('call', '');

			if ($call === 'ajaxcall') {
				$this->_helper->layout->disableLayout();
			}

			$refresh       = (string) $request->getParam('refresh', '');
			$dashboardcall = (string) $request->getParam('dashboradcall', '');
			$page          = (int) $request->getParam('page', 1);
			$searchData    = (string) $request->getParam('searchData', '');

			// SSOT v6.5: Default sort human-readable
			$sort    = (string) $request->getParam('sort', 'ASC');
			$by      = (string) $request->getParam('by', 'name');

			$perPage = ($dashboardcall === 'Yes')
				? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
				: (int) $request->getParam('per_page', PERPAGE);

			if ($refresh === 'refresh') {
				$sort = 'ASC';
				$by = 'name';
				$page = 1;
				$searchData = '';
			}

			$dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

			$this->view->dataArray = array($dataTmp);
			$this->view->call      = $call;

			$this->view->messages  = $this->_helper->flashMessenger->getMessages();
		} catch (\Throwable $e) {
			if (APPLICATION_ENV === 'development') Zend_Debug::dump($e->getMessage());
		}
	}

	public function viewAction()
	{
		$id = (int) $this->getRequest()->getParam('id', 0);
		$form = $this->_prepareForm($id, true);

		if (!$form) {
			$this->view->nodata = "nodata";
		} else {
			$this->view->form = $form;
			$this->view->id   = $id;
		}

		$this->view->controllername = 'project';
	}

	/* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

	public function addAction()
	{
		if (sapp_Global::_checkprivileges(PROJECT, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
			$this->_redirect('error/privilege');
		}

		$this->view->form = $this->_prepareForm();
		$this->render('save');
	}

	public function editAction()
	{
		if (sapp_Global::_checkprivileges(PROJECT, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
			$this->_redirect('error/privilege');
		}

		$id = (int) $this->getRequest()->getParam('id', 0);
		$form = $this->_prepareForm($id);

		if (!$form) {
			$this->view->nodata = "nodata";
		} else {
			$this->view->form = $form;
			$this->view->id   = $id;
		}

		$this->render('save');
	}

	public function saveAction()
	{
		$request = $this->getRequest();
		if (!$request->isPost()) {
			return $this->_redirect('project/index');
		}

		$id = (int) $request->getParam('id', 0);
		$form = $this->_prepareForm($id);

		if ($form->isValid($request->getPost())) {

			$db = Zend_Db_Table::getDefaultAdapter();
			$db->beginTransaction();

			try {
				$data = array(
					'code'              => trim((string) $request->getParam('code', '')),
					'name'              => trim((string) $request->getParam('name', '')),
					'representative_id' => (int) $request->getParam('representative_id'),
					'description'       => trim((string) $request->getParam('description', '')),
					'isactive'          => (int) $request->getParam('isactive', 1)
				);

				$actionFlag = 2;
				$saveMode   = 'updated';

				if (!$id) {
					$actionFlag = 1;
					$saveMode   = 'added';
					$savedId    = $this->_model->saveRecord($data);
				} else {
					$this->_model->saveRecord($data, $id);
					$savedId    = $id;
				}

				$participants = [
					'company'    => $request->getParam('participants_company', []),
					'sbunit'     => $request->getParam('participants_businessunit', []),
					'division'   => $request->getParam('participants_division', []),
					'department' => $request->getParam('participants_department', []),
					'section'    => $request->getParam('participants_section', []),
					'team'       => $request->getParam('participants_team', []),
					'employee'   => $request->getParam('participants_employee', [])
				];

				$this->_model->syncParticipantMappings($savedId, $participants);

				// Logging
				sapp_Global::logManager(PROJECT, $actionFlag, $this->_loginUserId, $savedId);
				$db->commit();

				// FlashMessenger for standard redirect
				$this->_helper->flashMessenger->addMessage(array('success' => "Project {$saveMode} successfully."));

				// SSOT v6.5 Strict AJAX JSON Response (Success with Redirect)
				$this->_helper->json(array(
					'result'    => 'saved',
					'nomessage' => 'yes'
				));
			} catch (\Throwable $e) {
				$db->rollBack();
				error_log("Project Save Error: " . $e->getMessage());

				// Exception returned in specific structure
				$this->_helper->json(array(
					'result'  => 'exception',
					'message' => 'System error occurred. Check logs.',
					'msgtype' => 'error'
				));
			}
		} else {
			// Validation Error - Raw Array output directly
			echo Zend_Json::encode($form->getMessages());
			exit;
		}
	}

	/* =================================================================================
     * GROUP 4: REMOVAL & STATUS TOGGLE
     * ================================================================================= */

	public function deleteAction()
	{
		$id = (int) $this->getRequest()->getParam('objid', 0);

		if ($id) {
			if (sapp_Global::_checkprivileges(PROJECT, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
				return $this->_helper->json(array('result' => 'error', 'message' => 'Permission Denied.'));
			}

			try {
				if ($this->_model->checkInUse($id)) {
					throw new Exception("This Project is actively linked to participants or distribution lists and cannot be deleted.");
				}

				$this->_model->deleteRecord($id);
				sapp_Global::logManager(PROJECT, 3, $this->_loginUserId, $id);

				return $this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success'));
			} catch (\Throwable $e) {
				error_log("Project Delete Error: " . $e->getMessage());
				return $this->_helper->json(array('result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error'));
			}
		}

		return $this->_helper->json(array('result' => 'error', 'message' => 'Invalid request'));
	}

	public function makeactiveinactiveAction()
	{
		try {
			$id     = (int) $this->getRequest()->getParam('id', 0);
			$status = (int) $this->getRequest()->getParam('status', 0);

			$this->_model->toggleStatus($id, $status);
			sapp_Global::logManager(PROJECT, 4, $this->_loginUserId, $id);

			$this->_helper->json(array('result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.'));
		} catch (\Throwable $e) {
			error_log("Project Toggle Error: " . $e->getMessage());
			$this->_helper->json(array('result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.'));
		}
	}

	/* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

		$prev_cntrl = (string) $this->getRequest()->getParam('prev_cntrl', '');

		$form = $this->_prepareForm();

		$form->setAction(BASE_URL . 'project/savepopup/prev_cntrl/' . $prev_cntrl);
		$form->setAttrib('id', 'popupformid');

		$this->view->form = $form;
		$this->view->prev_cntrl = $prev_cntrl;
	}

	private function _prepareForm($id = null, $readonly = false)
	{
		$form = new Default_Form_Project();

		// Populate Dropdowns natively
		$empModel = new Default_Model_Employee();
		$form->representative_id->addMultiOption('', 'Select Project Manager');
		$form->representative_id->addMultiOptions($empModel->getPairs());

		$form->participants_employee->addMultiOptions($empModel->getPairs());

		$companyModel = new Default_Model_Company();
		$form->participants_company->addMultiOptions($companyModel->getPairs());

		$businessunitModel = new Default_Model_BusinessUnit();
		$form->participants_businessunit->addMultiOptions($businessunitModel->getPairs());

		$divisionModel = new Default_Model_Division();
		$form->participants_division->addMultiOptions($divisionModel->getPairs());

		$deptModel = new Default_Model_Department();
		$form->participants_department->addMultiOptions($deptModel->getPairs());

		$sectionModel = new Default_Model_Section();
		$form->participants_section->addMultiOptions($sectionModel->getPairs());

		$teamModel = new Default_Model_Team();
		$form->participants_team->addMultiOptions($teamModel->getPairs());

		if ($id) {
			$data = $this->_model->getRecordById($id);
			if (!$data) return null;

			// XSS Prevention / Sanitation
			foreach ($data as $key => $val) {
				if ($val !== null) {
					$data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
				}
			}

			
			$form->populate($data);

			// Fetch and set the currently mapped participants
			$mappedData = $this->_model->getMappedParticipants($id);
			$form->participants_company->setValue($mappedData['company']);
			$form->participants_businessunit->setValue($mappedData['sbunit']);
			$form->participants_division->setValue($mappedData['division']);
			$form->participants_department->setValue($mappedData['department']);
			$form->participants_section->setValue($mappedData['section']);
			$form->participants_team->setValue($mappedData['team']);
			$form->participants_employee->setValue($mappedData['employee']);	

			$form->getElement('submit')->setLabel('Update');
		}

		if ($readonly) {
			foreach ($form->getElements() as $element) {
				$element->setAttrib('disabled', 'disabled');
			}
			$form->removeElement('submit');
		}

		return $form;
	}
}
