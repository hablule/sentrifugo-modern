<?php

/**
 * Address Controller
 *
 * Manages the core physical addresses and their polymorphic owner mappings.
 * Complies with SSOT v6.6 (Explicit AJAX, Junction Table Sync, Cascading Dropdowns).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_AddressController extends Zend_Controller_Action
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
		$this->_model   = new Default_Model_Address();
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();

		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$user = $auth->getStorage()->read();
			$this->_loginUserId    = (int)$user->id;
			$this->_loginuserRole  = (int)$user->emprole;
			$this->_loginuserGroup = (int)$user->group_id;

			// SSOT v6.6 Mandatory View Assignments
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
				echo Zend_Json::encode(['login' => 'failed']);
				exit;
			} else {
				$this->_redirect('');
			}
		}

		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('save', 'json')
			->addActionContext('delete', 'json')
			->addActionContext('makeactiveinactive', 'json')
			// AJAX endpoints for cascading dropdowns
			->addActionContext('getstatesbycountry', 'json')
			->addActionContext('getcitiesbystate', 'json')
			->addActionContext('getpostalcodesbycity', 'json')
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

			// SSOT v6.6: Default sort human-readable
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

			$this->view->dataArray = [$dataTmp];
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

		$this->view->controllername = 'address';
	}

	/* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

	public function addAction()
	{
		// SSOT v6.6: Ensure ADDRESS_MENU_ID is defined in constants.php
		if (sapp_Global::_checkprivileges(ADDRESS, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
			$this->_redirect('error/privilege');
		}

		$this->view->form = $this->_prepareForm();
		$this->render('save');
	}

	public function editAction()
	{
		if (sapp_Global::_checkprivileges(ADDRESS, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
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
			return $this->_redirect('address/index');
		}

		$id = (int) $request->getParam('id', 0);
		$form = $this->_prepareForm($id);

		if ($form->isValid($request->getPost())) {

			$db = Zend_Db_Table::getDefaultAdapter();
			$db->beginTransaction();

			try {
				$data = [
					'code'             => trim((string) $request->getParam('code', '')),
					'name'             => trim((string) $request->getParam('name', '')),
					'address_type_id'  => (int) $request->getParam('address_type_id'),
					'address_line_1'   => trim((string) $request->getParam('address_line_1', '')),
					'address_line_2'   => trim((string) $request->getParam('address_line_2', '')),
					'address_line_3'   => trim((string) $request->getParam('address_line_3', '')),
					'country_id'       => (int) $request->getParam('country_id'),
					'state_id'         => (int) $request->getParam('state_id'),
					'city_id'          => (int) $request->getParam('city_id'),
					'postal_code_id'   => $request->getParam('postal_code_id') ?: null,
					'map_coordinate'   => trim((string) $request->getParam('map_coordinate', '')),
					'description'      => trim((string) $request->getParam('description', '')),
					'isactive'         => (int) $request->getParam('isactive', 1)
				];

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

				// --- SSOT v6.6 Atomic Mapping Sync (Offloaded to Model) ---
				$owners = [
					'employee'   => $request->getParam('owners_employee', []),
					'company'    => $request->getParam('owners_company', []),
					'sbunit'     => $request->getParam('owners_sbunit', []),
					'division'   => $request->getParam('owners_division', []),
					'department' => $request->getParam('owners_department', []),
					'section'    => $request->getParam('owners_section', []),
					'team'       => $request->getParam('owners_team', []),
				];

				$this->_model->syncOwnerMappings($savedId, $owners);
				// --- END SYNC LOGIC ---

				// Logging
				sapp_Global::logManager(ADDRESS, $actionFlag, $this->_loginUserId, $savedId);
				$db->commit();

				// FlashMessenger for standard redirect
				$this->_helper->flashMessenger->addMessage(['success' => "Address {$saveMode} successfully."]);

				// SSOT Strict AJAX JSON Response (Success with Redirect)
				$this->_helper->json([
					'result'    => 'saved',
					'nomessage' => 'yes'
				]);
			} catch (\Throwable $e) {
				$db->rollBack();
				error_log("Address Save Error: " . $e->getMessage());

				// Exception returned in specific structure
				$this->_helper->json([
					'result'  => 'exception',
					'message' => 'System error occurred. Check logs.',
					'msgtype' => 'error'
				]);
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
			if (sapp_Global::_checkprivileges(ADDRESS, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
				return $this->_helper->json(['result' => 'error', 'message' => 'Permission Denied.']);
			}

			try {
				if ($this->_model->checkInUse($id)) {
					throw new Exception("This record is actively in use and cannot be deleted.");
				}

				$this->_model->deleteRecord($id);
				sapp_Global::logManager(ADDRESS, 3, $this->_loginUserId, $id);

				return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success']);
			} catch (\Throwable $e) {
				error_log("Address Delete Error: " . $e->getMessage());
				return $this->_helper->json(['result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error']);
			}
		}

		return $this->_helper->json(['result' => 'error', 'message' => 'Invalid request']);
	}

	public function makeactiveinactiveAction()
	{
		try {
			$id     = (int) $this->getRequest()->getParam('id', 0);
			$status = (int) $this->getRequest()->getParam('status', 0);

			$this->_model->toggleStatus($id, $status);
			sapp_Global::logManager(ADDRESS, 4, $this->_loginUserId, $id);

			$this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.']);
		} catch (\Throwable $e) {
			error_log("Address Toggle Error: " . $e->getMessage());
			$this->_helper->json(['result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.']);
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

		$form->setAction(BASE_URL . 'address/savepopup/prev_cntrl/' . $prev_cntrl);
		$form->setAttrib('id', 'popupformid');

		$this->view->form = $form;
		$this->view->prev_cntrl = $prev_cntrl;
	}

	// AJAX Endpoint for cascading state dropdown
	public function getstatesbycountryAction()
	{
		$countryId = (int)$this->getRequest()->getParam('country_id');
		$model = new Default_Model_State();
		$pairs = $model->getPairsByCountry($countryId);
		$this->_helper->json($pairs);
	}

	// AJAX Endpoint for cascading city dropdown
	public function getcitiesbystateAction()
	{
		$stateId = (int)$this->getRequest()->getParam('state_id');
		$model = new Default_Model_City();
		$pairs = $model->getPairsByState($stateId);
		$this->_helper->json($pairs);
	}

	// AJAX Endpoint for cascading postal code dropdown
	public function getpostalcodesbycityAction()
	{
		$cityId = (int)$this->getRequest()->getParam('city_id');
		$model = new Default_Model_Postalcode();
		$pairs = $model->getPairsByCity($cityId);
		$this->_helper->json($pairs);
	}

	private function _prepareForm($id = null, $readonly = false)
	{
		$form = new Default_Form_Address();

		// 1. Populate Primary Dropdowns
		$addrTypeModel = new Default_Model_Addresstype();
		$form->address_type_id->addMultiOption('', 'Select Type...');
		$form->address_type_id->addMultiOptions($addrTypeModel->getPairs());

		$countryModel = new Default_Model_Country();
		$form->country_id->addMultiOption('', 'Select Country...');
		$form->country_id->addMultiOptions($countryModel->getPairs());

		// 2. Populate MultiCheckbox Owner Options
		$empModel = new Default_Model_Employee();
		$form->owners_employee->addMultiOptions($empModel->getPairs());

		$compModel = new Default_Model_Company();
		$form->owners_company->addMultiOptions($compModel->getPairs());

		$sbuModel = new Default_Model_Businessunit();
		$form->owners_sbunit->addMultiOptions($sbuModel->getPairs());

		$divModel = new Default_Model_Division();
		$form->owners_division->addMultiOptions($divModel->getPairs());

		$deptModel = new Default_Model_Department();
		$form->owners_department->addMultiOptions($deptModel->getPairs());

		$secModel = new Default_Model_Section();
		$form->owners_section->addMultiOptions($secModel->getPairs());

		$teamModel = new Default_Model_Team();
		$form->owners_team->addMultiOptions($teamModel->getPairs());

		if ($id) {
			$data = $this->_model->getRecordById($id);
			if (!$data) return null;

			// 3. Populate dependent dropdowns if editing
			if (!empty($data['country_id'])) {
				$stateModel = new Default_Model_State();
				$form->state_id->addMultiOptions($stateModel->getPairsByCountry($data['country_id']));
			}
			if (!empty($data['state_id'])) {
				$cityModel = new Default_Model_City();
				$form->city_id->addMultiOptions($cityModel->getPairsByState($data['state_id']));
			}
			if (!empty($data['city_id'])) {
				$postalModel = new Default_Model_Postalcode();
				$form->postal_code_id->addMultiOptions($postalModel->getPairsByCity($data['city_id']));
			}

			// Fetch and set the currently mapped owners
			$mappedData = $this->_model->getMappedOwners($id);
			$form->owners_employee->setValue($mappedData['employee']);
			$form->owners_department->setValue($mappedData['department']);
			$form->owners_team->setValue($mappedData['team']);
			$form->owners_company->setValue($mappedData['company']);
			$form->owners_sbunit->setValue($mappedData['sbunit']);
			$form->owners_division->setValue($mappedData['division']);
			$form->owners_section->setValue($mappedData['section']);

			$form->populate($data);
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
